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

/**
 * Tests for alloptionshierarchicalfilter.
 *
 * Key behaviours verified:
 *  - All statically-defined options appear in the category object, not only those
 *    that have matching DB records.
 *  - Items with DB records carry the correct count; items without carry count=0.
 *  - count=0 is set explicitly (prevents Mustache context-stack walk-up that would
 *    otherwise inherit the parent subcategory count and show "N Veranstaltungen").
 *  - Non-array filter-metadata keys (e.g. 'explode') are skipped and never appear
 *    as competency items.
 *  - Filtering still works correctly: selecting an empty option returns 0 rows;
 *    selecting a populated option returns the right row count.
 *  - The standard hierarchicalfilter (regression) still hides options with no DB
 *    records.
 *
 * @package local_wunderbyte_table
 * @copyright 2024 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\filters\types;

use advanced_testcase;
use cache_helper;
use context_system;
use local_wunderbyte_table\wunderbyte_table;
use stdClass;

/**
 * Unit tests for alloptionshierarchicalfilter.
 *
 * @covers \local_wunderbyte_table\filters\types\alloptionshierarchicalfilter
 */
final class alloptionshierarchicalfilter_test extends advanced_testcase {

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    /**
     * Create a custom field category + text field and return the field object.
     *
     * @param string $shortname
     * @return \core_customfield\field_controller
     */
    private function create_custom_text_field(string $shortname): \core_customfield\field_controller {
        $categorydata = new stdClass();
        $categorydata->name = 'Test Fields';
        $categorydata->component = 'core_course';
        $categorydata->area = 'course';
        $categorydata->itemid = 0;
        $categorydata->contextid = context_system::instance()->id;
        $category = $this->getDataGenerator()->create_custom_field_category((array)$categorydata);
        $category->save();

        $fielddata = new stdClass();
        $fielddata->categoryid = $category->get('id');
        $fielddata->name = $shortname;
        $fielddata->shortname = $shortname;
        $fielddata->type = 'text';
        $fielddata->configdata = '';
        $field = $this->getDataGenerator()->create_custom_field((array)$fielddata);
        $field->save();
        return $field;
    }

    /**
     * Build a table backed by {course}, render it, and return both the table and
     * its encoded hash for subsequent filter requests.
     *
     * @param int $categoryid  Course category id to restrict the query.
     * @param alloptionshierarchicalfilter|hierarchicalfilter $filter
     * @return array{0: wunderbyte_table, 1: string}  [$table, $encodedtable]
     */
    private function build_and_render_table(int $categoryid, $filter): array {
        $table = new wunderbyte_table('allopt_test_' . uniqid());
        $table->add_filter($filter);
        $table->set_filter_sql('*', '{course}', 'category = ' . $categoryid, '', []);
        $html = $table->outhtml(100, true);

        preg_match('/<div[^>]*\sdata-encodedtable=["\']?([^"\'>\s]+)["\']?/i', $html, $matches);
        return [$table, $matches[1] ?? ''];
    }

    /**
     * Apply a wbtfilter URL param and recount rows via the cached table.
     *
     * @param string $encodedtable
     * @param string $filterjson  e.g. '{"depcontact":["1"]}'
     * @return int
     */
    private function count_rows_with_filter(string $encodedtable, string $filterjson): int {
        $_GET['wbtfilter'] = $filterjson;
        $cachedtable = wunderbyte_table::instantiate_from_tablecache_hash($encodedtable);
        $cachedtable->printtable(
            $cachedtable->pagesize,
            $cachedtable->useinitialsbar,
            $cachedtable->downloadhelpbutton
        );
        return (int)$cachedtable->totalrows;
    }

    // ---------------------------------------------------------------------------
    // Unit tests: add_to_categoryobject
    // ---------------------------------------------------------------------------

    /**
     * All options from the sortarray appear in the hierarchy even when $values is
     * completely empty (no DB records at all).
     *
     * @covers \local_wunderbyte_table\filters\types\alloptionshierarchicalfilter::add_to_categoryobject
     */
    public function test_all_options_appear_when_values_is_empty(): void {
        $categoryobject = [];
        $filtersettings = [
            'competency' => [
                '1' => ['parent' => 'Group A', 'localizedname' => 'Option A1'],
                '2' => ['parent' => 'Group A', 'localizedname' => 'Option A2'],
                '3' => ['parent' => 'Group B', 'localizedname' => 'Option B1'],
            ],
        ];

        // $values is empty — simulates zero DB records for this filter column.
        alloptionshierarchicalfilter::add_to_categoryobject($categoryobject, $filtersettings, 'competency', []);

        $this->assertArrayHasKey('hierarchy', $categoryobject);

        // Collect all rendered item labels across subcategories.
        $renderedlabels = [];
        foreach ($categoryobject['hierarchy'] as $subcategory) {
            foreach ($subcategory['values'] as $item) {
                $renderedlabels[] = $item['key'];
            }
        }

        $this->assertContains('Option A1', $renderedlabels, 'Option A1 must appear even with no DB records.');
        $this->assertContains('Option A2', $renderedlabels, 'Option A2 must appear even with no DB records.');
        $this->assertContains('Option B1', $renderedlabels, 'Option B1 must appear even with no DB records.');
    }

    /**
     * Options that DO have DB records carry the correct count; options that do NOT
     * have DB records carry count = 0 (not omitted).
     *
     * This explicit count=0 prevents the Mustache context-stack walk-up that
     * caused the "(1 Veranstaltungen)" bug in the filterview template.
     *
     * @covers \local_wunderbyte_table\filters\types\alloptionshierarchicalfilter::add_to_categoryobject
     */
    public function test_count_is_correct_for_populated_and_empty_options(): void {
        $categoryobject = [];
        $filtersettings = [
            'competency' => [
                '1' => ['parent' => 'Group A', 'localizedname' => 'Option A1'],
                '2' => ['parent' => 'Group A', 'localizedname' => 'Option A2'],
                '3' => ['parent' => 'Group B', 'localizedname' => 'Option B1'],
            ],
        ];

        // Only key '1' has a DB record with count 5.
        $values = ['1' => 5];

        alloptionshierarchicalfilter::add_to_categoryobject($categoryobject, $filtersettings, 'competency', $values);

        $itemsbyvalue = [];
        foreach ($categoryobject['hierarchy'] as $subcategory) {
            foreach ($subcategory['values'] as $item) {
                $itemsbyvalue[$item['value']] = $item;
            }
        }

        // '1' has 5 records.
        $this->assertArrayHasKey('1', $itemsbyvalue, 'Value 1 must be present.');
        $this->assertSame(5, $itemsbyvalue['1']['count']);

        // '2' and '3' have no DB records — count must be explicitly 0, not missing.
        $this->assertArrayHasKey('2', $itemsbyvalue, 'Value 2 must be present even with no DB records.');
        $this->assertArrayHasKey('count', $itemsbyvalue['2'],
            'count key must exist on empty item to prevent Mustache context walk-up.');
        $this->assertSame(0, $itemsbyvalue['2']['count']);

        $this->assertArrayHasKey('3', $itemsbyvalue, 'Value 3 must be present even with no DB records.');
        $this->assertArrayHasKey('count', $itemsbyvalue['3'],
            'count key must exist on empty item to prevent Mustache context walk-up.');
        $this->assertSame(0, $itemsbyvalue['3']['count']);
    }

    /**
     * Non-array entries in the filtersettings (e.g. 'wbfilterclass', 'localizedname',
     * 'competency_wb_checked') must not produce spurious items in the rendered hierarchy.
     * The 'explode' key is consumed by apply_filtercount before the sort iteration,
     * while other non-array metadata keys are guarded by the is_array() check.
     *
     * @covers \local_wunderbyte_table\filters\types\alloptionshierarchicalfilter::add_to_categoryobject
     */
    public function test_metadata_keys_are_not_rendered_as_items(): void {
        $categoryobject = [];
        $filtersettings = [
            'competency' => [
                'explode'                => ',',
                'wbfilterclass'          => 'some\\class',
                'localizedname'          => 'Competencies',
                'competency_wb_checked'  => 1,
                '1' => ['parent' => 'Group A', 'localizedname' => 'Option A1'],
                '2' => ['parent' => 'Group A', 'localizedname' => 'Option A2'],
            ],
        ];

        alloptionshierarchicalfilter::add_to_categoryobject($categoryobject, $filtersettings, 'competency', []);

        $allvalues = [];
        $allkeys = [];
        foreach ($categoryobject['hierarchy'] as $subcategory) {
            foreach ($subcategory['values'] as $item) {
                $allvalues[] = $item['value'];
                $allkeys[]   = $item['key'];
            }
        }

        // Only real option sort-keys should appear as 'value' (as integers, since PHP converts numeric string keys).
        $this->assertContains(1, $allvalues);
        $this->assertContains(2, $allvalues);

        // Metadata values must not appear.
        $this->assertNotContains(',', $allvalues, "'explode' separator must not appear as a filter item value.");
        $this->assertNotContains('some\\class', $allvalues, "'wbfilterclass' must not appear as a filter item value.");
        $this->assertNotContains('Competencies', $allvalues, "'localizedname' must not appear as a filter item value.");

        // Localized names for real options must appear; metadata keys must not.
        $this->assertContains('Option A1', $allkeys);
        $this->assertContains('Option A2', $allkeys);
        $this->assertNotContains('wbfilterclass', $allkeys);
        $this->assertNotContains('Competencies', $allkeys);
    }

    /**
     * Both subcategories appear in the hierarchy even when neither has any DB records.
     * The parent hierarchicalfilter would return early on the first empty subcategory;
     * alloptionshierarchicalfilter uses continue instead.
     *
     * @covers \local_wunderbyte_table\filters\types\alloptionshierarchicalfilter::add_to_categoryobject
     */
    public function test_multiple_empty_subcategories_all_appear(): void {
        $categoryobject = [];
        $filtersettings = [
            'competency' => [
                '1' => ['parent' => 'Teaching', 'localizedname' => 'Lesson planning'],
                '2' => ['parent' => 'Teaching', 'localizedname' => 'Digital tools'],
                '3' => ['parent' => 'Research', 'localizedname' => 'Open Science'],
                '4' => ['parent' => 'Research', 'localizedname' => 'Publishing'],
            ],
        ];

        alloptionshierarchicalfilter::add_to_categoryobject($categoryobject, $filtersettings, 'competency', []);

        $subcategorylabels = array_column($categoryobject['hierarchy'], 'label');

        $this->assertContains('Teaching', $subcategorylabels,
            'Teaching subcategory must appear even with no DB records.');
        $this->assertContains('Research', $subcategorylabels,
            'Research subcategory must appear even with no DB records.');
    }

    // ---------------------------------------------------------------------------
    // Regression: standard hierarchicalfilter still hides empty options
    // ---------------------------------------------------------------------------

    /**
     * Regression guard: the standard hierarchicalfilter must still omit options
     * that have no DB records.
     *
     * @covers \local_wunderbyte_table\filters\types\hierarchicalfilter::add_to_categoryobject
     */
    public function test_standard_hierarchicalfilter_hides_empty_options(): void {
        $categoryobject = [];
        $filtersettings = [
            'competency' => [
                '1' => ['parent' => 'Group A', 'localizedname' => 'Option A1'],
                '2' => ['parent' => 'Group A', 'localizedname' => 'Option A2'],
            ],
        ];

        // Only '1' is in $values (has DB record).
        hierarchicalfilter::add_to_categoryobject($categoryobject, $filtersettings, 'competency', ['1' => 3]);

        $renderedvalues = [];
        foreach ($categoryobject['hierarchy'] as $subcategory) {
            foreach ($subcategory['values'] as $item) {
                $renderedvalues[] = $item['value'];
            }
        }

        $this->assertContains(1, $renderedvalues, 'Option 1 with DB record must appear.');
        $this->assertNotContains(2, $renderedvalues,
            'Option 2 without DB record must NOT appear in standard hierarchicalfilter.');
    }

    // ---------------------------------------------------------------------------
    // Integration tests: full table + custom field + filter application
    // ---------------------------------------------------------------------------

    /**
     * Integration test: all statically-defined options appear in the filterjson
     * even when some options have no matching courses/records.
     *
     * @covers \local_wunderbyte_table\filters\types\alloptionshierarchicalfilter
     */
    public function test_all_options_appear_in_filterjson(): void {
        $this->resetAfterTest(true);
        $_GET = [];
        $_POST = [];

        $field = $this->create_custom_text_field('competency');
        $cfid = $field->get('id');

        $category = $this->getDataGenerator()->create_category(['name' => 'Test']);

        // 3 courses have competency=1, none have competency=2.
        for ($i = 0; $i < 3; $i++) {
            $this->getDataGenerator()->create_course([
                'fullname' => 'Course ' . $i,
                'category' => $category->id,
                'customfield_competency' => '1',
            ]);
        }

        $filter = new alloptionshierarchicalfilter('competency', 'Competency');
        $filter->set_sql_for_fieldid($cfid);
        $filter->add_options([
            'explode' => ',',
            '1' => ['parent' => 'Group A', 'localizedname' => 'Option A1'],
            '2' => ['parent' => 'Group A', 'localizedname' => 'Option A2 (no records)'],
        ]);

        $table = new wunderbyte_table('allopt_json_' . uniqid());
        $table->add_filter($filter);
        $table->set_filter_sql('*', '{course}', 'category = ' . $category->id, '', []);
        $table->outhtml(100, true);

        $this->assertNotEmpty($table->filterjson, 'filterjson must not be empty.');

        $filterjson = json_decode($table->filterjson, true);
        $compcategory = null;
        foreach ($filterjson['categories'] as $cat) {
            if ($cat['columnname'] === 'competency') {
                $compcategory = $cat;
                break;
            }
        }
        $this->assertNotNull($compcategory, 'competency category must be present in filterjson.');

        $renderedvalues = [];
        foreach ($compcategory['hierarchy'] as $subcategory) {
            foreach ($subcategory['values'] as $item) {
                $renderedvalues[$item['value']] = $item;
            }
        }

        $this->assertArrayHasKey('1', $renderedvalues, 'Option 1 (has records) must be in filterjson.');
        $this->assertArrayHasKey('2', $renderedvalues, 'Option 2 (no records) must ALSO be in filterjson.');
        $this->assertSame(0, $renderedvalues['2']['count'],
            'Option 2 must have count=0, not inherited from parent subcategory (Mustache walk-up prevention).');
    }

    /**
     * Integration test: filtering by a populated option returns the correct row count.
     *
     * @covers \local_wunderbyte_table\filters\types\alloptionshierarchicalfilter
     */
    public function test_filter_by_populated_option_returns_correct_rows(): void {
        $this->resetAfterTest(true);
        $_GET = [];
        $_POST = [];

        $field = $this->create_custom_text_field('comp2');
        $cfid = $field->get('id');
        $category = $this->getDataGenerator()->create_category(['name' => 'Test2']);

        // 4 courses with comp2=1, 2 courses with comp2=2.
        for ($i = 0; $i < 4; $i++) {
            $this->getDataGenerator()->create_course([
                'fullname' => 'Course1-' . $i,
                'category' => $category->id,
                'customfield_comp2' => '1',
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            $this->getDataGenerator()->create_course([
                'fullname' => 'Course2-' . $i,
                'category' => $category->id,
                'customfield_comp2' => '2',
            ]);
        }

        $filter = new alloptionshierarchicalfilter('comp2', 'Competency 2');
        $filter->set_sql_for_fieldid($cfid);
        $filter->add_options([
            '1' => ['parent' => 'Group A', 'localizedname' => 'Option 1'],
            '2' => ['parent' => 'Group A', 'localizedname' => 'Option 2'],
            '3' => ['parent' => 'Group B', 'localizedname' => 'Option 3 (empty)'],
        ]);

        [, $encodedtable] = $this->build_and_render_table($category->id, $filter);
        $this->assertNotEmpty($encodedtable);

        $this->assertEquals(4, $this->count_rows_with_filter($encodedtable, '{"comp2":["1"]}'));

        // Clear cache and reset state before applying a different filter.
        cache_helper::purge_by_event('changesinwunderbytetable');
        $_GET = [];
        $_POST = [];
        $this->assertEquals(2, $this->count_rows_with_filter($encodedtable, '{"comp2":["2"]}'));
    }

    /**
     * Integration test: filtering by an option with no DB records returns 0 rows
     * without errors.
     *
     * @covers \local_wunderbyte_table\filters\types\alloptionshierarchicalfilter
     */
    public function test_filter_by_empty_option_returns_zero_rows(): void {
        $this->resetAfterTest(true);
        $_GET = [];
        $_POST = [];

        $field = $this->create_custom_text_field('comp3');
        $cfid = $field->get('id');
        $category = $this->getDataGenerator()->create_category(['name' => 'Test3']);

        // Only option '1' has courses; option '99' has none.
        for ($i = 0; $i < 3; $i++) {
            $this->getDataGenerator()->create_course([
                'fullname' => 'Course-' . $i,
                'category' => $category->id,
                'customfield_comp3' => '1',
            ]);
        }

        $filter = new alloptionshierarchicalfilter('comp3', 'Competency 3');
        $filter->set_sql_for_fieldid($cfid);
        $filter->add_options([
            '1'  => ['parent' => 'Group A', 'localizedname' => 'Option 1'],
            '99' => ['parent' => 'Group A', 'localizedname' => 'Option 99 (empty)'],
        ]);

        [, $encodedtable] = $this->build_and_render_table($category->id, $filter);
        $this->assertNotEmpty($encodedtable);

        $this->assertEquals(0, $this->count_rows_with_filter($encodedtable, '{"comp3":["99"]}'));
    }

    /**
     * Integration test: when NO courses exist for the column at all, the filter is
     * still rendered (not suppressed) and all static options appear.
     *
     * This exercises the get_data_for_filter_options() override that returns []
     * instead of ['continue' => true], preventing the filter from being silently skipped.
     *
     * @covers \local_wunderbyte_table\filters\types\alloptionshierarchicalfilter::get_data_for_filter_options
     * @covers \local_wunderbyte_table\filters\types\alloptionshierarchicalfilter::add_to_categoryobject
     */
    public function test_filter_is_shown_when_no_db_records_exist_at_all(): void {
        $this->resetAfterTest(true);
        $_GET = [];
        $_POST = [];

        $field = $this->create_custom_text_field('comp4');
        $cfid = $field->get('id');

        // Category with no courses at all.
        $emptycategory = $this->getDataGenerator()->create_category(['name' => 'Empty']);

        $filter = new alloptionshierarchicalfilter('comp4', 'Competency 4');
        $filter->set_sql_for_fieldid($cfid);
        $filter->add_options([
            '1' => ['parent' => 'Group A', 'localizedname' => 'Option 1'],
            '2' => ['parent' => 'Group A', 'localizedname' => 'Option 2'],
        ]);

        $table = new wunderbyte_table('allopt_empty_' . uniqid());
        $table->add_filter($filter);
        $table->set_filter_sql('*', '{course}', 'category = ' . $emptycategory->id, '', []);
        $table->outhtml(100, true);

        $this->assertNotEmpty($table->filterjson,
            'filterjson must be populated even when no DB records exist — filter must not be suppressed.');

        $filterjson = json_decode($table->filterjson, true);
        $compcategory = null;
        foreach ($filterjson['categories'] as $cat) {
            if ($cat['columnname'] === 'comp4') {
                $compcategory = $cat;
                break;
            }
        }

        $this->assertNotNull($compcategory,
            'comp4 filter category must appear in filterjson even with no DB records.');

        $renderedvalues = [];
        foreach ($compcategory['hierarchy'] as $subcategory) {
            foreach ($subcategory['values'] as $item) {
                $renderedvalues[] = $item['value'];
            }
        }

        $this->assertContains(1, $renderedvalues, 'Option 1 must appear even with no DB records.');
        $this->assertContains(2, $renderedvalues, 'Option 2 must appear even with no DB records.');
    }
}
