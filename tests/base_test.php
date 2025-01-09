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
use Exception;
use local_wunderbyte_table\external\load_data;
use local_wunderbyte_table\filters\types\datepicker;
use local_wunderbyte_table\filters\types\standardfilter;
use moodle_exception;

/**
 * Test base functionality of wunderbyte_table
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * // @runTestsInSeparateProcesses
 *
 */
final class base_test extends advanced_testcase {
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
        // Mandatory clean-up.
        cache_helper::purge_by_event('changesinwunderbytetable');
    }

    /**
     * Test wb base functionality via webservice external class.
     *
     * @covers \wunderbyte_table::query_db_cached
     * // @runInSeparateProcess
     *
     * @throws \coding_exception
     * @throws \dml_exception
     *
     */
    public function test_query_db_cached(): void {
        // First, we create ten courses.
        $this->create_test_courses(10);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $table = $this->create_demo2_table();
        $nrofrows = $this->get_rowscount_for_table($table);

        // Now we get back exactly 10.
        $this->assertEquals(10, $nrofrows);

        // Now we create another three courses.
        $this->create_test_courses(3, ['fullname' => 'filtercourse']);

        $nrofrows = $this->get_rowscount_for_table($table);

        // Because of caching kicking in, we still get 10 items.
        $this->assertEquals(10, $nrofrows);

        // Now we purge the cache.
        cache_helper::purge_by_event('changesinwunderbytetable');

        $nrofrows = $this->get_rowscount_for_table($table);

        // After purging, we expect 13.
        $this->assertEquals(13, $nrofrows);

        // Now we want to test pagination.
        $this->create_test_courses(30);

        // Now we purge the cache.
        cache_helper::purge_by_event('changesinwunderbytetable');

        $nrofrows = $this->get_rowscount_for_table($table);

        $this->assertEquals(20, $nrofrows);

        // Now we fetch the third page. With 43 coures, we expect only three rows now.
        $nrofrows = $this->get_rowscount_for_table($table, 2);

        $this->assertEquals(3, $nrofrows);
    }

    /**
     * Test wb base search and filtering functionality via webservice external class.
     *
     * @covers \wunderbyte_table::query_db_cached
     * // @runInSeparateProcess
     *
     * @throws \coding_exception
     * @throws \dml_exception
     *
     */
    public function test_basic_search_filtering_cached(): void {
        // First, we create ten courses.
        $this->create_test_courses(10);
        // Now we create another three courses for basic searching and filtering.
        $this->create_test_courses(3, ['fullname' => 'filtercourse']);
        // Create 2 courses for end date filtering.
        $this->create_test_courses(1, [
            'fullname' => 'ended1',
            'startdate' => strtotime('2 May 2010'),
            'enddate' => strtotime('20 May 2010'),
        ]);
        $this->create_test_courses(1, [
            'fullname' => 'ended2',
            'startdate' => strtotime('5 Jun 2020 14:00'),
            'enddate' => strtotime('15 Jun 2020 15:00'),
        ]);
        $this->create_test_courses(1, [
            'fullname' => 'future1',
            'startdate' => strtotime('1 March 2050 14:00'),
            'enddate' => strtotime('10 March 2050 15:00'),
        ]);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $table = $this->create_demo2_table();

        $nrofrows = $this->get_rowscount_for_table($table);
        $this->assertEquals(16, $nrofrows);

        // Search for courses by name.
        $nrofrows = $this->get_rowscount_for_table(
            $table,
            0,
            null,
            null,
            null,
            null,
            null,
            null,
            'filtercourse'
        );
        $this->assertEquals(3, $nrofrows);

        $nrofrows = $this->get_rowscount_for_table(
            $table,
            0,
            null,
            null,
            null,
            null,
            null,
            null,
            'Test course 9'
        );
        $this->assertEquals(1, $nrofrows);

        $nrofrows = $this->get_rowscount_for_table(
            $table,
            0,
            null,
            null,
            null,
            null,
            null,
            null,
            'ended'
        );
        $this->assertEquals(2, $nrofrows);

        // Validate basic filtering by course fullname.
        $nrofrows = $this->get_rowscount_for_table(
            $table,
            0,
            null,
            null,
            null,
            null,
            null,
            '{"fullname":["filtercourse"]}'
        );
        $this->assertEquals(3, $nrofrows);

        $nrofrows = $this->get_rowscount_for_table(
            $table,
            0,
            null,
            null,
            null,
            null,
            null,
            '{"fullname":["ended2"]}'
        );
        $this->assertEquals(1, $nrofrows);

        $nrofrows = $this->get_rowscount_for_table(
            $table,
            0,
            null,
            null,
            null,
            null,
            null,
            //'{"enddate":{"Course end date":{"<":' . strtotime('today') . '}}}'
            "{\"enddate\":{\"Course end date\":{\"<\":1763528940}}}"
        );
        $this->assertEquals(2, $nrofrows);
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

        $table->define_fulltextsearchcolumns(['fullname', 'shortname']);
        $table->define_sortablecolumns($columns);

        $standardfilter = new standardfilter('fullname', 'fullname');
        $table->add_filter($standardfilter);

        $datepicker = new datepicker('enddate', get_string('enddate'));
        // For the datepicker, we need to add special options.
        $datepicker->add_options(
            'standard',
            '<',
            get_string('apply_filter', 'local_wunderbyte_table'),
            'now',
        );
        $table->add_filter($datepicker);

        $table->set_filter_sql('*', "(SELECT * FROM {course} ORDER BY id ASC LIMIT 112) as s1", 'id > 1', '');

        $table->pageable(true);

        $table->pagesize = 20;

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
     * @param array $options
     * @return array
     *
     */
    public function create_test_courses(int $coursestocreate = 1, $options = []): array {
        global $DB;

        $returnarray = [];
        // We add another three courses.
        $counter = 0;
        while ($counter < $coursestocreate) {
            $counter++;
            $returnarray[] = $this->getDataGenerator()->create_course($options);
        }
        return $returnarray;
    }

    /**
     * Returns rows via webservice static function from given table.
     *
     * @param wunderbyte_table $table
     * @param int $page
     * @param string $tsort
     * @param string $thide
     * @param string $tshow
     * @param int $tdir
     * @param int $treset
     * @param string $filterobjects
     * @param string $searchtext
     *
     * @return int
     *
     */
    public function get_rowscount_for_table(
        wunderbyte_table $table,
        $page = null,
        $tsort = null,
        $thide = null,
        $tshow = null,
        $tdir = null,
        $treset = null,
        $filterobjects = null,
        $searchtext = null
    ): int {

        $rows = $this->get_rows_for_table(
            $table,
            $page,
            $tsort,
            $thide,
            $tshow,
            $tdir,
            $treset,
            $filterobjects,
            $searchtext
        );

        return count($rows);
    }

    /**
     * Returns the actual rows for a table. This only retrieves the rows for the current page.
     *
     * @param wunderbyte_table $table
     * @param int $page
     * @param string $tsort
     * @param string $thide
     * @param string $tshow
     * @param int $tdir
     * @param int $treset
     * @param string $filterobjects
     * @param string $searchtext
     *
     * @return array
     *
     */
    public function get_rows_for_table(
        wunderbyte_table $table,
        $page = null,
        $tsort = null,
        $thide = null,
        $tshow = null,
        $tdir = null,
        $treset = null,
        $filterobjects = null,
        $searchtext = null
    ): array {

        $encodedtable = $table->return_encoded_table();
        $result = load_data::execute(
            $encodedtable,
            $page,
            $tsort,
            $thide,
            $tshow,
            $tdir,
            $treset,
            $filterobjects,
            $searchtext
        );
        $jsonobject = json_decode($result['content']);

        if (!isset($jsonobject->table->rows)) {
            throw new moodle_exception('test', 'test', '', json_encode($jsonobject));
        }
        $rows = $jsonobject->table->rows ?? 0;
        return $rows;
    }
}
