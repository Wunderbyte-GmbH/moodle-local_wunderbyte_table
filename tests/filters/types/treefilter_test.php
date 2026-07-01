<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_wunderbyte_table\filters\types;

use advanced_testcase;
use local_wunderbyte_table\wunderbyte_table;
use stdClass;

/**
 * Tests the generic recursive treefilter in isolation (with a DB-free stub provider).
 *
 * @package    local_wunderbyte_table
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_wunderbyte_table\filters\types\treefilter
 */
final class treefilter_test extends advanced_testcase {

    /**
     * Loads the stub provider fixture.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        require_once(__DIR__ . '/../../fixtures/treefilter_test_provider.php');
    }

    /**
     * add_to_categoryobject builds an N-level nested mustache context (not just two levels), with subtree
     * totals as counts, the column as the checkbox field name, unique identifiers and correct haschildren.
     *
     * @return void
     */
    public function test_add_to_categoryobject_is_recursive(): void {
        $filtersettings = ['entityid' => ['wbtreeprovider' => treefilter_test_provider::class]];
        $categoryobject = ['columnname' => 'entityid'];

        treefilter::add_to_categoryobject($categoryobject, $filtersettings, 'entityid', [4 => 2, 5 => 1]);

        $this->assertArrayHasKey('treehierarchy', $categoryobject);
        $tree = $categoryobject['treehierarchy'];

        // One root: Root, with subtree total 3 and two children (Building, BuildingB).
        $this->assertCount(1, $tree);
        $root = $tree[0];
        $this->assertSame('Root', $root['key']);
        $this->assertSame(1, $root['value']);
        $this->assertSame(3, $root['count']);
        $this->assertSame('entityid', $root['category']);
        $this->assertTrue($root['haschildren']);
        $this->assertCount(2, $root['children']);

        // Descend Building → Floor → Room: proves the context is 4 levels deep, not clamped to 2.
        $building = $root['children'][0];
        $this->assertSame('Building', $building['key']);
        $floor = $building['children'][0];
        $this->assertSame('Floor', $floor['key']);
        $room = $floor['children'][0];
        $this->assertSame('Room', $room['key']);
        $this->assertSame(4, $room['value']);
        $this->assertSame(2, $room['count']);
        $this->assertFalse($room['haschildren']);
        $this->assertSame([], $room['children']);

        // The other branch is a shallow leaf.
        $buildingb = $root['children'][1];
        $this->assertSame('BuildingB', $buildingb['key']);
        $this->assertFalse($buildingb['haschildren']);

        // Identifiers are unique across the whole tree.
        $identifiers = [];
        $collect = function ($nodes) use (&$collect, &$identifiers) {
            foreach ($nodes as $node) {
                $identifiers[] = $node['identifier'];
                $collect($node['children']);
            }
        };
        $collect($tree);
        $this->assertSame(array_values(array_unique($identifiers)), $identifiers);
    }

    /**
     * With no (or an invalid) provider, the filter adds nothing — it must never guess a structure.
     *
     * @return void
     */
    public function test_add_to_categoryobject_without_provider_is_noop(): void {
        $categoryobject = ['columnname' => 'entityid'];
        treefilter::add_to_categoryobject($categoryobject, ['entityid' => []], 'entityid', [4 => 2]);
        $this->assertArrayNotHasKey('treehierarchy', $categoryobject);

        $categoryobject2 = ['columnname' => 'entityid'];
        treefilter::add_to_categoryobject(
            $categoryobject2,
            ['entityid' => ['wbtreeprovider' => '\\this\\does\\not\\exist']],
            'entityid',
            [4 => 2]
        );
        $this->assertArrayNotHasKey('treehierarchy', $categoryobject2);
    }

    /**
     * apply_filter delegates the SQL condition to the provider and emits it (params registered on table).
     *
     * @return void
     */
    public function test_apply_filter_delegates_to_provider(): void {
        $this->resetAfterTest();

        $table = new wunderbyte_table('treefiltertest');
        $table->sql = new stdClass();
        $table->sql->params = [];
        $table->subcolumns['datafields']['entityid']['wbtreeprovider'] = treefilter_test_provider::class;

        $tf = new treefilter('entityid');
        $filter = '';
        $tf->apply_filter($filter, 'entityid', [0 => '2', 1 => '3'], $table);

        $this->assertStringContainsString('entityid IN (:param1,:param2)', $filter);
        $this->assertSame('2', $table->sql->params['param1']);
        $this->assertSame('3', $table->sql->params['param2']);
    }

    /**
     * With no selected ids, apply_filter must emit a valid, match-all expression so the surrounding
     * "AND ( ... )" wrapper never becomes empty (which would be a SQL syntax error).
     *
     * @return void
     */
    public function test_apply_filter_empty_selection_matches_all(): void {
        $this->resetAfterTest();

        $table = new wunderbyte_table('treefiltertest2');
        $table->sql = new stdClass();
        $table->sql->params = [];
        $table->subcolumns['datafields']['entityid']['wbtreeprovider'] = treefilter_test_provider::class;

        $tf = new treefilter('entityid');
        $filter = '';
        $tf->apply_filter($filter, 'entityid', [], $table);

        $this->assertStringContainsString('1=1', $filter);
    }

    /**
     * get_data_for_filter_options turns the provider's present counts into the row shape the generic
     * filter builder consumes (one object per node carrying the column value and its count).
     *
     * @return void
     */
    public function test_get_data_for_filter_options_shapes_rows(): void {
        $this->resetAfterTest();

        $table = new wunderbyte_table('treefiltertest3');
        $table->subcolumns['datafields']['entityid']['wbtreeprovider'] = treefilter_test_provider::class;

        $rows = treefilter::get_data_for_filter_options($table, 'entityid');

        $bykey = [];
        foreach ($rows as $row) {
            $bykey[$row->entityid] = $row->keycount;
        }
        $this->assertSame([4 => 2, 5 => 1], $bykey);
    }

    /**
     * Without a provider, get_data_for_filter_options returns nothing (so the filter is simply absent).
     *
     * @return void
     */
    public function test_get_data_for_filter_options_without_provider(): void {
        $this->resetAfterTest();
        $table = new wunderbyte_table('treefiltertest4');
        $this->assertSame([], treefilter::get_data_for_filter_options($table, 'entityid'));
    }
}
