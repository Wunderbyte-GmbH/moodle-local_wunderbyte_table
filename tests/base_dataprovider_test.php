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
use local_wunderbyte_table\filters\types\callback;
use local_wunderbyte_table\filters\types\datepicker;
use local_wunderbyte_table\filters\types\standardfilter;
use local_wunderbyte_table\local\sortables\types\standardsortable;
use moodle_exception;

/**
 * Test base functionality of wunderbyte_table
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @runInSeparateProcess
 * @runTestsInSeparateProcesses
 *
 */
final class base_dataprovider_test extends advanced_testcase {
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
        $_POST = [];
    }

    /**
     * Test wb base functionality via webservice external class.
     *
     * @covers \local_wunderbyte_table\wunderbyte_table::query_db_cached
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
     * Test wb filter functionality via webservice external class.
     *
     * @covers \wunderbyte_table::query_db_cached
     * @covers \wunderbyte_table::define_sortablecolumns
     * @covers \local_wunderbyte_table\local\sortables\types\standardsortable
     *
     * @throws \coding_exception
     * @throws \dml_exception
     *
     */
    public function test_sortable(): void {

        // First, we create ten courses.
        $courses = $this->create_test_courses(45);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        // We enrol users to the course in order to test sorting.
        $this->getDataGenerator()->enrol_user($user1->id, $courses[8]->id);
        $this->getDataGenerator()->enrol_user($user2->id, $courses[8]->id);
        $this->getDataGenerator()->enrol_user($user3->id, $courses[8]->id);
        $this->getDataGenerator()->enrol_user($user2->id, $courses[5]->id);
        $this->getDataGenerator()->enrol_user($user3->id, $courses[5]->id);
        $this->getDataGenerator()->enrol_user($user2->id, $courses[4]->id);
        $this->getDataGenerator()->enrol_user($user2->id, $courses[3]->id);

        $this->setAdminUser();

        $table = $this->create_demo2_table();

        $table->pagesize = 10;
        $nrofrows = $this->get_rowscount_for_table($table);

        // Now we get back exactly 10.
        $this->assertEquals(10, $nrofrows);

        // On the 5th page, we expect exactly 5 items.
        $nrofrows = $this->get_rowscount_for_table(
            $table,
            4,
            null,
        );
        // Now we get back exactly 10.
        $this->assertEquals(5, $nrofrows);

        $rows = $this->get_rows_for_table(
            $table,
            null,
            'fullname',
        );

        $fullname = $rows[0]->datafields[1]->value;
        // Sorted by fullname, we get Test course 1 as first item.
        $this->assertEquals("Test course 1", $fullname);

        $rows = $this->get_rows_for_table(
            $table,
            null,
            'enrolledusers',
            null,
            null,
            SORT_DESC
        );

        // Sorted by enrolled users, we get Test course 8 as first item.
        $fullname = $rows[0]->datafields[1]->value;
        $this->assertEquals("Test course 8", $fullname);

        // The second item will be Test course 5.
        $fullname = $rows[1]->datafields[1]->value;
        $this->assertEquals("Test course 5", $fullname);
    }

    /**
     * Test wb filter functionality via webservice external class.
     *
     * @covers \local_wunderbyte_table\filters\types\callback
     *
     * @throws \coding_exception
     * @throws \dml_exception
     *
     */
    public function test_filter_callback(): void {

        // First, we create default test courses.
        $courses = $this->create_test_courses(45);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        // We enrol users to the course in order to test sorting.
        $this->getDataGenerator()->enrol_user($user1->id, $courses[8]->id);
        $this->getDataGenerator()->enrol_user($user2->id, $courses[8]->id);
        $this->getDataGenerator()->enrol_user($user3->id, $courses[8]->id);
        $this->getDataGenerator()->enrol_user($user2->id, $courses[5]->id);
        $this->getDataGenerator()->enrol_user($user3->id, $courses[5]->id);
        $this->getDataGenerator()->enrol_user($user2->id, $courses[4]->id);
        $this->getDataGenerator()->enrol_user($user2->id, $courses[3]->id);

        $this->setAdminUser();

        $table = $this->create_demo2_table();

        $table->pagesize = 30;
        $nrofrows = $this->get_rowscount_for_table($table);

        // Now we get back exactly 10.
        $this->assertEquals(30, $nrofrows);

        $nrofrows = $this->get_rowscount_for_table(
            $table,
            null,
            null,
            null,
            null,
            null,
            null,
            "{\"iddivisblebythree\":[\"0\"]}",
        );

        // Now we get back exactly 30.
        $this->assertEquals(30, $nrofrows);

        $nrofrows = $this->get_rowscount_for_table(
            $table,
            null,
            null,
            null,
            null,
            null,
            null,
            "{\"iddivisblebythree\":[\"1\"]}",
        );

        // Now we get back exactly 15.
        $this->assertEquals(15, $nrofrows);
    }

    /**
     * Test wb base full text search.
     *
     * @param array $coursedata
     * @param array $expected
     *
     * @covers \wunderbyte_table::query_db_cached
     * @covers \wunderbyte_table::define_fulltextsearchcolumns
     *
     * @throws \coding_exception
     * @throws \dml_exception
     *
     * @dataProvider wb_table_common_settings_provider
     */
    public function test_dataprovider(array $coursedata, array $expected): void {
        // Create the courses, depending on data provider.
        $courses = [];
        foreach ($coursedata as $coursearray) {
            $courses[] = $this->create_test_courses($coursearray['coursestocreate'], $coursearray);
        }

        $this->setAdminUser();

        $table = $this->create_demo2_table();

        // Set pagesize.
        $table->pagesize = $expected['pagesize'] ?? 20;
        // Process "getrowscount" section - validate row count for table under different settings.
        foreach ($expected['getrowscount'] as $getrowscount) {
            $nrofrows = $this->get_rowscount_for_table(
                $table,
                $getrowscount['page'] ?? null,
                $getrowscount['tsort'] ?? null,
                $getrowscount['thide'] ?? null,
                $getrowscount['tshow'] ?? null,
                $getrowscount['tdir'] ?? null,
                $getrowscount['treset'] ?? null,
                $getrowscount['filterobjects'] ?? null,
                $getrowscount['searchtext'] ?? null
            );
            $this->assertEquals($getrowscount['assert'], $nrofrows);
        }
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

        $standardsortable = new standardsortable(
            'enrolledusers',
            'enrolledusers'
        );
        $select = '(SELECT COUNT(ue.id)
                    FROM {user_enrolments} ue
                    JOIN {enrol} e ON ue.enrolid = e.id
                    WHERE e.courseid = s1.id) AS enrolledusers';
        $from = '';
        $where = '';
        $standardsortable->define_sql($select, $from, $where);

        $table->add_sortable($standardsortable);

        $standardfilter = new standardfilter('fullname', 'fullname');
        $table->add_filter($standardfilter);

        $callbackfilter = new callback('iddivisblebythree', 'iddivisblebythree');
        $callbackfilter->add_options([
            0 => 'notdivisblebythree',
            1 => 'divisblebythree',
        ]);
        // This filter expects a record from booking options table.
        // We check if it is bookable for the user.
        $callbackfilter->define_callbackfunction('local_wunderbyte_table\base_dataprovider_test::filter_iddivisiblebythree');
        $table->add_filter($callbackfilter);

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

            $courseoptions = $options;
            if (!isset($options['fullname'])) {
                $courseoptions['fullname'] = 'Test course ' . $counter;
            }
            $returnarray[$counter] = $this->getDataGenerator()->create_course($courseoptions);
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

    /**
     * Function to be used by the callback filter.
     *
     * @param mixed $record
     *
     * @return bool
     *
     */
    public static function filter_iddivisiblebythree($record): bool {
        return $record->id % 3 === 0;
    }

    /**
     * Data provider for condition_bookingpolicy_test
     *
     * @return array
     * @throws \UnexpectedValueException
     */
    public static function wb_table_common_settings_provider(): array {
        $standardusers = [
            [ // User 0 in tests.
                'firstname' => "Student",
                'lastname' => "Tester",
                'email' => 'student.tester1@example.com',
                'role' => 'student',
            ],
            [
                // User 1 in tests.
                'firstname' => "Teacher",
                'lastname' => "Tester",
                'email' => 'teacher.tester1@example.com',
                'role' => 'teacher',
            ],
            [
                // User 2 in tests.
                'firstname' => "Booking",
                'lastname' => "Manager",
                'email' => 'booking.manager@example.com',
                'role' => 'bookingmanager',
            ],
        ];

        $plusfifftymonth = strtotime('+50 month');
        $plussixtymonth = strtotime('+60 month');
        $standardcourses = [
            [
                'coursestocreate' => 10,
                'fullname' => 'Test Course',
                'users' => $standardusers,
            ],
            [
                'coursestocreate' => 3,
                'fullname' => 'filtercourse',
                'users' => $standardusers,
            ],
            [
                'coursestocreate' => 1,
                'fullname' => 'ended1',
                'startdate' => strtotime('2 May 2010'),
                'enddate' => strtotime('20 May 2010'),
                'users' => $standardusers,
            ],
            [
                'coursestocreate' => 1,
                'fullname' => 'ended2',
                'startdate' => strtotime('5 Jun 2020 14:00'),
                'enddate' => strtotime('15 Jun 2020 15:00'),
                'users' => $standardusers,
            ],
            [
                'coursestocreate' => 1,
                'fullname' => 'future1',
                'startdate' => $plusfifftymonth,
                'enddate' => $plussixtymonth,
                'users' => $standardusers,
            ],
        ];

        // Array of tests.
        $returnarray = [
            // Test name (description).
            'fulltextsearchcolumns' => [
                'courses' => $standardcourses,
                'expected' => [
                    'getrowscount' => [
                        [
                            'assert' => 16,
                        ],
                        [
                            'searchtext' => 'filtercourse',
                            'assert' => 3,
                        ],
                        [
                            'searchtext' => 'Test course 9',
                            'assert' => 1,
                        ],
                        [
                            'searchtext' => 'ended',
                            'assert' => 2,
                        ],
                    ],
                ],
            ],
            'filter_standardfilter' => [
                'courses' => $standardcourses,
                'expected' => [
                    'getrowscount' => [
                        [
                            'assert' => 16,
                        ],
                        [
                            'filterobjects' => '{"fullname":["filtercourse"]}',
                            'assert' => 3,
                        ],
                        [
                            'filterobjects' => '{"fullname":["ended2"]}',
                            'assert' => 1,
                        ],
                        [
                            'filterobjects' => '{"fullname":["ended%"]}',
                            'assert' => 2,
                        ],
                    ],
                ],
            ],
            'filter_datepicker' => [
                'courses' => $standardcourses,
                'expected' => [
                    'getrowscount' => [
                        [
                            'assert' => 16,
                        ],
                        [
                            'filterobjects' => '{"enddate":{"Course end date":{">":' . $plusfifftymonth . '}}}',
                            'assert' => 1,
                        ],
                        [
                            'filterobjects' => '{"enddate":{"Course end date":{"<":' . strtotime('1 January 2020') . '}}}',
                            'assert' => 14,
                        ],
                        [
                            'filterobjects' => '{"enddate":{"Course end date":{"<":' . strtotime('+1 year') . '}}}',
                            'assert' => 15,
                        ],
                    ],
                ],
            ],
        ];

        return $returnarray;
    }
}
