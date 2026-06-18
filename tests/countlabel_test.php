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
 * Tests for the records count label rendering.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table;

use advanced_testcase;
use cache_helper;
use moodle_url;

/**
 * Test that the count label is rendered through the AJAX reload path and that
 * define_countlabel() overrides survive instantiation from the table cache.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_wunderbyte_table\wunderbyte_table::define_countlabel
 * @covers \local_wunderbyte_table\output\table::return_as_list
 */
final class countlabel_test extends advanced_testcase {
    /**
     * Tests set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Mandatory clean-up after each test.
     */
    public function tearDown(): void {
        parent::tearDown();
        cache_helper::purge_by_event('changesinwunderbytetable');
        $_POST = [];
    }

    /**
     * Build a minimal demo table backed by a known number of course rows.
     *
     * @param int $nrcourses
     * @param string $uniqueid
     * @return demo_table
     */
    private function build_table(int $nrcourses, string $uniqueid): demo_table {
        for ($i = 0; $i < $nrcourses; $i++) {
            $this->getDataGenerator()->create_course();
        }

        $table = new demo_table($uniqueid);
        $columns = [
            'id' => 'id',
            'fullname' => 'fullname',
        ];
        $table->define_headers(array_values($columns));
        $table->define_columns(array_keys($columns));
        $table->define_sortablecolumns(array_keys($columns));

        // Only the created courses (exclude the site course, id = 1).
        $table->set_filter_sql('*', '(SELECT * FROM {course} WHERE id > 1) as s1', '1=1', '');
        $table->pageable(true);
        $table->pagesize = 100;
        $table->showcountlabel = true;
        $table->define_baseurl(new moodle_url('/local/wunderbyte_table/download.php'));

        return $table;
    }

    /**
     * Render the table the same way the AJAX load_data webservice does: cache the
     * table, re-instantiate it from the cache hash and export it for the template.
     * This is the path that produces the count label shown after lazy loading.
     *
     * @param demo_table $table
     * @return \stdClass
     */
    private function render(demo_table $table): \stdClass {
        global $PAGE;

        $hash = $table->return_encoded_table();
        $reloaded = wunderbyte_table::instantiate_from_tablecache_hash($hash);

        if (empty($reloaded->baseurl)) {
            $reloaded->baseurl = new moodle_url('/local/wunderbyte_table/download.php');
        }

        $tableobject = $reloaded->printtable($reloaded->pagesize, $reloaded->useinitialsbar, $reloaded->downloadhelpbutton);
        $output = $PAGE->get_renderer('local_wunderbyte_table');
        return (object) $tableobject->export_for_template($output);
    }

    /**
     * The default count label string is rendered.
     */
    public function test_default_countlabel(): void {
        $table = $this->build_table(2, 'countlabeldefault');
        $data = $this->render($table);

        $this->assertObjectHasProperty('countlabelstring', $data);
        $this->assertStringContainsString('2 of 2 records found', $data->countlabelstring);
    }

    /**
     * A custom string defined via define_countlabel() survives re-instantiation
     * from the table cache and is honoured at render time.
     *
     * Regression test: the count label properties are private, so the output
     * table class must read them through the public getters - otherwise the
     * override is silently dropped and the generic string is shown.
     *
     * A core string ('total') is used so the test stays self-contained and does
     * not depend on any consumer plugin.
     */
    public function test_define_countlabel_override(): void {
        $expected = get_string('total');

        $table = $this->build_table(2, 'countlabeloverride');
        $table->define_countlabel('total', 'core');
        $data = $this->render($table);

        $this->assertObjectHasProperty('countlabelstring', $data);
        $this->assertSame($expected, $data->countlabelstring);
        $this->assertStringNotContainsString('records found', $data->countlabelstring);
    }
}
