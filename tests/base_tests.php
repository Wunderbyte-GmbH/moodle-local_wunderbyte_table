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
 * Tests for booking option events.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @author 2025 Wunderbyte GmbH <info@wunderbyte.at> Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table;

use advanced_testcase;
use cache_helper;
use coding_exception;
use local_wunderbyte_table\external\load_data;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();
global $CFG;

/**
 * Test base functionality of wunderbyte_table
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
final class base_tests extends advanced_testcase {
    /**
     * Tests set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Test wb base functionality
     *
     * @covers \wunderbyte_table::query_db_cached
     * @runInSeparateProcess
     *
     * @throws \coding_exception
     * @throws \dml_exception
     *
     */
    public function test_query_db_cached(): void {
        global $DB, $CFG;

        // First, we create ten courses.
        $this->create_test_courses(10);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $table = $this->create_demo2_table();
        $nrofrows = $this->return_rows_for_table($table);

        // Now we get back exactly 10.
        $this->assertEquals($nrofrows, 10);

        // Now we create another three courses.
        $this->create_test_courses(3);

        $table = $this->create_demo2_table();
        $nrofrows = $this->return_rows_for_table($table);

        // Because of caching kicking in, we still get 10 items.
        $this->assertEquals($nrofrows, 10);

        // Now we purge the cache.
        cache_helper::purge_by_event('changesinwunderbytetable');

        $table = $this->create_demo2_table();
        $nrofrows = $this->return_rows_for_table($table);

        // After purging, we expect 13.
        $this->assertEquals($nrofrows, 13);

        $this->setAdminUser();

        // Now we want to test pagination.
        $this->create_test_courses(30);

        $this->setUser($user);

        // Now we purge the cache.
        cache_helper::purge_by_event('changesinwunderbytetable');

        $table = $this->create_demo2_table();
        $nrofrows = $this->return_rows_for_table($table);

        // Now we expect exactly 20 items, for pagesize.
        $this->assertEquals($nrofrows, 20);
    }

    /**
     * Function to create and return wunderbyte table class.
     *
     * @return wunderbyte_table
     *
     */
    public function create_demo2_table() {
        $table = new demo_table('demotable_1');

        $columns = [
            'id' => get_string('id', 'local_wunderbyte_table'),
            'fullname' => get_string('fullname'),
            'shortname' => get_string('shortname'),
            'action' => get_string('action'),
            'startdate' => get_string('startdate'),
            'enddate' => get_string('enddate'),
        ];

        // Number of items must be equal.
        $table->define_headers(array_values($columns));
        $table->define_columns(array_keys($columns));

        $table->set_filter_sql('*', "(SELECT * FROM {course} ORDER BY id ASC LIMIT 112) as s1", 'id > 1', '');

        $table->pageable(true);

        $table->stickyheader = false;
        $table->showcountlabel = true;
        $table->showdownloadbutton = true;
        $table->showreloadbutton = true;
        $table->showrowcountselect = true;
        $table->filteronloadinactive = true;

        return $table;
    }

    /**
     * Create a defined number of testcourses.
     *
     * @param int $coursestocreate
     * @return array
     *
     */
    public function create_test_courses(int $coursestocreate = 1): array {
        global $DB;

        $returnarray = [];
        // We add another three courses.
        $counter = 0;
        while ($counter < $coursestocreate) {
            $counter++;
            $returnarray[] = $this->getDataGenerator()->create_course();
        }
        return $returnarray;
    }

    /**
     * Returns rows via webservice static function from given table.
     *
     * @param wunderbyte_table $table
     *
     * @return int
     *
     */
    public function return_rows_for_table(wunderbyte_table $table): int {

        $encodedtable = $table->return_encoded_table();
        $result = load_data::execute($encodedtable, 0);
        $jsonobject = json_decode($result['content']);
        $rows = $jsonobject->table->rows;

        return count($rows);
    }
}
