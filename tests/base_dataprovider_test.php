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
use local_wunderbyte_table\filters\types\callback;
use local_wunderbyte_table\filters\types\datepicker;
use local_wunderbyte_table\filters\types\standardfilter;
use local_wunderbyte_table\filters\types\hierarchicalfilter;
use local_wunderbyte_table\filters\types\hourlist;
use local_wunderbyte_table\filters\types\intrange;
use local_wunderbyte_table\filters\types\weekdays;
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
     * @covers \local_wunderbyte_table\wunderbyte_table::query_db_cached
     * @covers \local_wunderbyte_table\wunderbyte_table::define_sortablecolumns
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
     * Test wb base full text search.
     *
     * @param string $tablecallback
     * @param array $coursedata
     * @param array $expected
     *
     * @covers \local_wunderbyte_table\wunderbyte_table::query_db_cached
     * @covers \local_wunderbyte_table\wunderbyte_table::define_fulltextsearchcolumns
     * @covers \local_wunderbyte_table\filters\types\standardfilter
     * @covers \local_wunderbyte_table\filters\types\datepicker
     *
     * @throws \coding_exception
     * @throws \dml_exception
     *
     * @dataProvider wb_table_common_settings_provider
     */
    public function test_dataprovider(string $tablecallback, array $coursedata, array $expected): void {
        // Create the courses, depending on data provider.
        $courses = [];
        foreach ($coursedata as $coursearray) {
            $courses[] = $this->create_test_courses($coursearray['coursestocreate'], $coursearray);
        }

        $this->setAdminUser();

        $table = $this->{$tablecallback}();

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
    public function create_demo_table() {
        $table = new demo_table('demotable_1');

        $columns = [
            'id' => get_string('id', 'local_wunderbyte_table'),
            'fullname' => get_string('fullname'),
            'shortname' => get_string('shortname'),
            'action' => get_string('action'),
            'startdate' => get_string('startdate'),
            'enddate' => get_string('enddate'),
            'timecreated' => get_string('timecreated'),
        ];

        // Number of items must be equal.
        $table->define_headers(array_values($columns));
        $table->define_columns(array_keys($columns));

        $table->define_fulltextsearchcolumns(['fullname', 'shortname']);
        $table->define_sortablecolumns($columns);

        $intrangefilter = new intrange('fullname', "Range of numbers given in course fullname");
        $table->add_filter($intrangefilter);

        $weekdaysfilter = new weekdays(
            'startdate',
            get_string('startdate')
        );
        $table->add_filter($weekdaysfilter);

        $intrangefilter = new intrange('enddate', "Unix timestamp given in course enddate");
        $table->add_filter($intrangefilter);

        $weekdaysfilter = new weekdays('timecreated', get_string('timecreated'));
        $table->add_filter($weekdaysfilter);

        $standardfilter = new standardfilter('shortname', 'shortname');
        $table->add_filter($standardfilter);

        $table->set_filter_sql('*', "(SELECT * FROM {course} ORDER BY id ASC LIMIT 112) as s1", 'id > 1', '');

        $table->pageable(true);

        $table->pagesize = 20;

        $table->stickyheader = false;
        $table->showcountlabel = true;
        $table->gotopage = true;
        $table->showdownloadbutton = true;
        $table->showreloadbutton = true;
        $table->showrowcountselect = true;
        $table->filteronloadinactive = true;

        return $table;
    }

    /**
     * Function to create and return wunderbyte table class.
     *
     * @return wunderbyte_table
     *
     */
    public function create_demo2_table() {
        $table = new demo_table('demotable_2');

        $columns = [
            'id' => get_string('id', 'local_wunderbyte_table'),
            'fullname' => get_string('fullname'),
            'shortname' => get_string('shortname'),
            'action' => get_string('action'),
            'startdate' => get_string('startdate'),
            'enddate' => get_string('enddate'),
            'timecreated' => get_string('timecreated'),
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

        $hierarchicalfilter = new hierarchicalfilter('shortname', get_string('shortname'));
        $hierarchicalfilter->add_options(
            [
                'short-ended1' => [
                    'parent' => 'e',
                ],
                'short-future1' => [
                    'parent' => 'f',
                ],
                'other' => [
                    'localizedname' => get_string('other', 'local_wunderbyte_table'),
                ],
            ]
        );
        $table->add_filter($hierarchicalfilter);

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

        $datepicker = new datepicker(
            'startdate',
            get_string('timespan', 'local_wunderbyte_table'),
            'enddate'
        );
        // For the datepicker, we need to add special options.
        $datepicker->add_options(
            'in between',
            '<',
            get_string('apply_filter', 'local_wunderbyte_table'),
            '1680130800',
            'now'
        );
        $table->add_filter($datepicker);

        $hourslistfilter = new hourlist('timecreated', "timecreated");
        $table->add_filter($hourslistfilter);

        $table->set_filter_sql('*', "(SELECT * FROM {course} ORDER BY id ASC LIMIT 112) as s1", 'id > 1', '');

        $table->pageable(true);

        $table->pagesize = 20;

        $table->stickyheader = false;
        $table->showcountlabel = true;
        $table->gotopage = true;
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

        $plus40month = strtotime('+40 month');
        $plusfifftymonth = strtotime('+50 month');
        $plus55month = strtotime('+55 month');
        $plussixtymonth = strtotime('+60 month');
        $plus80month = strtotime('+80 month');
        $standardcourses = [
            [
                'coursestocreate' => 10,
                // No fullname, so default 'Test Course xx' will be applied.
                'users' => $standardusers,
                'timecreated' => strtotime('Tuesday, 15 April 2014 05:00'),
            ],
            [
                'coursestocreate' => 3,
                'fullname' => 'filtercourse',
                'users' => $standardusers,
                'timecreated' => strtotime('Saturday, 12 June 2005 10:00'),
            ],
            [
                'coursestocreate' => 1,
                'fullname' => 'ended1',
                'shortname' => 'short-ended1',
                'startdate' => strtotime('Sunday, 2 May 2010 13:00'),
                'enddate' => strtotime('Thursday, 20 May 2010 14:20'),
                'users' => $standardusers,
                'timecreated' => strtotime('Wednesday, 10 December 2015 22:00'),
            ],
            [
                'coursestocreate' => 1,
                'fullname' => 'ended2',
                'shortname' => 'short-ended2',
                'startdate' => strtotime('Friday, 5 Jun 2020 15:00'),
                'enddate' => strtotime('Monday, 15 Jun 2020 17:00'),
                'users' => $standardusers,
                'timecreated' => strtotime('Saturday, 18 January 2020 19:00'),
            ],
            [
                'coursestocreate' => 1,
                'fullname' => 'future1',
                'shortname' => 'short-future1',
                'startdate' => $plusfifftymonth,
                'enddate' => $plussixtymonth,
                'users' => $standardusers,
                'timecreated' => strtotime('Monday, 10 March 2025 19:30'),
            ],
        ];

        // Some pre-defined filter strings.
        $timespanwithin1 = '{"startdate":{"Timespan":{">=":' . strtotime('4 May 2010')
            . '}},"enddate":{"Timespan":{"<=":' . strtotime('18 May 2010') . '}}}';
        $timespanwithin2 = '{"startdate":{"Timespan":{">=":' . strtotime('1 May 2010')
            . '}},"enddate":{"Timespan":{"<=":' . strtotime('21 May 2010') . '}}}';
        $timespanwithin3 = '{"startdate":{"Timespan":{">=":' . strtotime('1 May 2010 13:00')
            . '}},"enddate":{"Timespan":{"<=":' . strtotime('20 Jun 2020 14:20') . '}}}';
        $timespanoverlapboth = '{"startdate":{"Timespan":{"<=":' . strtotime('4 May 2010')
            . '}},"enddate":{"Timespan":{">=":' . strtotime('18 May 2010') . '}}}';
        $timespanoverlapbeginning1 = '{"startdate":{"Timespan":{"<=":' . strtotime('4 May 2010')
            . '}},"enddate":{"Timespan":{"<=":' . strtotime('22 May 2010') . '},"Timespana":{">=":'
            . strtotime('4 May 2010') . '}}}';
        $timespanoverlapbeginning2 = '{"startdate":{"Timespan":{"<=":' . strtotime('7 Jun 2020')
            . '}},"enddate":{"Timespan":{"<=":' . strtotime('22 Jun 2020') . '},"Timespana":{">=":'
            . strtotime('7 Jun 2020') . '}}}';
        $timespanoverlapending1 = '{"startdate":{"Timespan":{">=":' . strtotime('1 May 2010')
            . '},"Timespana":{"<=":' . strtotime('18 May 2010') . '}},"enddate":{"Timespan":{">=":'
            . strtotime('18 May 2010') . '}}}';
        $timespanoverlapending2 = '{"startdate":{"Timespan":{">=":' . strtotime('3 Jun 2020')
            . '},"Timespana":{"<=":' . strtotime('12 Jun 2020') . '}},"enddate":{"Timespan":{">=":'
            . strtotime('12 Jun 2020') . '}}}';
        $timespanbefore1 = '{"startdate":{"Timespan":{"<":' . strtotime('4 May 2015')
            . '}},"enddate":{"Timespan":{"<":' . strtotime('5 May 2015') . '},"Timespana":{"<=":'
            . strtotime('4 May 2015') . '}}}';
        $timespanbefore2 = '{"startdate":{"Timespan":{"<":' . strtotime('4 May 2022')
            . '}},"enddate":{"Timespan":{"<":' . strtotime('5 May 2022') . '},"Timespana":{"<=":'
            . strtotime('4 May 2022') . '}}}';
        $timespanafter1 = '{"startdate":{"Timespan":{">":' . strtotime('10 April 2010')
            . '},"Timespana":{">=":' . strtotime('18 April 2010') . '}},"enddate":{"Timespan":{">=":'
            . strtotime('18 April 2010') . '}}}';
        $timespanafter2 = '{"startdate":{"Timespan":{">":' . strtotime('10 April 2015')
            . '},"Timespana":{">=":' . strtotime('18 April 2015') . '}},"enddate":{"Timespan":{">=":'
            . strtotime('18 April 2015') . '}}}';
        $timespanoverlap = '{"startdate":{"Timespan":{"fo":' . strtotime('8 Jun 2020')
            . '}},"enddate":{"Timespan":{"fo":' . strtotime('13 Jun 2020') . '}}}';

        // Array of tests.
        $returnarray = [
            // Test name (description).
            'filter_intrange_enddate' => [
                'tablecallback' => 'create_demo_table',
                'courses' => $standardcourses,
                'expected' => [
                    'getrowscount' => [
                        [
                            'assert' => 16,
                        ],
                        [
                            'filterobjects' => '{"enddate":"1273449600,1596240000"}',
                            'assert' => 2,
                        ],
                        [
                            'filterobjects' => '{"enddate":"' . $plus40month . ',' . $plus80month . '"}',
                            'assert' => 1,
                        ],
                        [
                            'filterobjects' => '{"enddate":"' . $plus55month . ',' . $plus80month . '"}',
                            'assert' => 1,
                        ],
                        [
                            'filterobjects' => '{"enddate":"1273449600,' . $plussixtymonth . '"}',
                            'assert' => 3,
                        ],
                    ],
                ],
            ],
            'filter_weekdays' => [
                'tablecallback' => 'create_demo_table',
                'courses' => $standardcourses,
                'expected' => [
                    'getrowscount' => [
                        [
                            'assert' => 16,
                        ],
                        [
                            'filterobjects' => '{"timecreated":["tuesday"]}',
                            'assert' => 10,
                        ],
                        [
                            'filterobjects' => '{"timecreated":["saturday"]}',
                            'assert' => 4,
                        ],
                        [
                            'filterobjects' => '{"timecreated":["monday"]}',
                            'assert' => 1,
                        ],
                        [
                            'filterobjects' => '{"timecreated":["wednesday"]}',
                            'assert' => 1,
                        ],
                    ],
                ],
            ],
            'filter_hourlist' => [
                'tablecallback' => 'create_demo2_table',
                'courses' => $standardcourses,
                'expected' => [
                    'getrowscount' => [
                        [
                            'assert' => 16,
                        ],
                        [
                            'filterobjects' => '{"timecreated":["5"]}',
                            'assert' => 10,
                        ],
                        [
                            'filterobjects' => '{"timecreated":["10"]}',
                            'assert' => 3,
                        ],
                        [
                            'filterobjects' => '{"timecreated":["19"]}',
                            'assert' => 2,
                        ],
                        [
                            'filterobjects' => '{"timecreated":["22"]}',
                            'assert' => 1,
                        ],
                    ],
                ],
            ],
            'filter_cmbine_shortname_weekdays' => [
                'tablecallback' => 'create_demo_table',
                'courses' => $standardcourses,
                'expected' => [
                    'getrowscount' => [
                        [
                            'assert' => 16,
                        ],
                        [
                            'filterobjects' => '{"shortname":["short-ended1","short-ended2"],"startdate":["sunday"]}',
                            'assert' => 1,
                        ],
                        [
                            'filterobjects' => '{"shortname":["short-ended1","short-ended2"],"startdate":["friday"]}',
                            'assert' => 1,
                        ],
                    ],
                ],
            ],
            'filter_intrange' => [
                'tablecallback' => 'create_demo_table',
                'courses' => $standardcourses,
                'expected' => [
                    'getrowscount' => [
                        [
                            'assert' => 16,
                        ],
                        [
                            'filterobjects' => '{"fullname":"1,3"}',
                            'assert' => 6,
                        ],
                        [
                            'filterobjects' => '{"fullname":"3,5"}',
                            'assert' => 3,
                        ],
                        [
                            'filterobjects' => '{"fullname":"9,11"}',
                            'assert' => 2,
                        ],
                    ],
                ],
            ],
            'filter_hierarchicalfilter' => [
                'tablecallback' => 'create_demo2_table',
                'courses' => $standardcourses,
                'expected' => [
                    'getrowscount' => [
                        [
                            'assert' => 16,
                        ],
                        [
                            'filterobjects' => '{"shortname":["short-future1"]}',
                            'assert' => 1,
                        ],
                        [
                            'filterobjects' => '{"shortname":["short-ended1"]}',
                            'assert' => 1,
                        ],
                        [
                            'filterobjects' => '{"shortname":["short-ended%"]}',
                            'assert' => 2,
                        ],
                        [
                            'filterobjects' => '{"shortname":["filtercourse","tc_%"]}',
                            'assert' => 13,
                        ],
                    ],
                ],
            ],
            // The below dataset cause "Failed asserting that 10 matches expected 11." failure in 50% cases.
            // phpcs:disable
            /*'filter_callback' => [
                'tablecallback' => 'create_demo2_table',
                'courses' => $standardcourses,
                'expected' => [
                    'getrowscount' => [
                        [
                            'assert' => 16,
                        ],
                        [
                            'filterobjects' => '{"iddivisblebythree":["0"]}',
                            'assert' => 11,
                        ],
                        [
                            'filterobjects' => '{"iddivisblebythree":["1"]}',
                            'assert' => 5,
                        ],
                    ],
                ],
            ],*/
            // phpcs:enable
            'filter_datepicker_in_between' => [
                'tablecallback' => 'create_demo2_table',
                'courses' => $standardcourses,
                'expected' => [
                    'getrowscount' => [
                        [
                            'assert' => 16,
                        ],
                        [
                            'filterobjects' => $timespanwithin1,
                            'assert' => 13, // Because default courses has dates == 0.
                        ],
                        [
                            'filterobjects' => $timespanwithin2,
                            'assert' => 14,
                        ],
                        [
                            'filterobjects' => $timespanwithin3,
                            'assert' => 15,
                        ],
                        [
                            'filterobjects' => $timespanoverlapboth,
                            'assert' => 1,
                        ],
                        [
                            'filterobjects' => $timespanoverlapbeginning1,
                            'assert' => 1,
                        ],
                        [
                            'filterobjects' => $timespanoverlapbeginning2,
                            'assert' => 1,
                        ],
                        [
                            'filterobjects' => $timespanoverlapending1,
                            'assert' => 1,
                        ],
                        [
                            'filterobjects' => $timespanoverlapending2,
                            'assert' => 1,
                        ],
                        [
                            'filterobjects' => $timespanbefore1,
                            'assert' => 1,
                        ],
                        [
                            'filterobjects' => $timespanbefore2,
                            'assert' => 2,
                        ],
                        [
                            'filterobjects' => $timespanafter1,
                            'assert' => 3,
                        ],
                        [
                            'filterobjects' => $timespanafter2,
                            'assert' => 2,
                        ],
                        [
                            'filterobjects' => $timespanoverlap,
                            'assert' => 1,
                        ],
                    ],
                ],
            ],
            'fulltextsearchcolumns' => [
                'tablecallback' => 'create_demo2_table',
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
                'tablecallback' => 'create_demo2_table',
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
                        [
                            'filterobjects' => '{"fullname":["filtercourse","ended%"]}',
                            'assert' => 5,
                        ],
                    ],
                ],
            ],
            'filter_datepicker' => [
                'tablecallback' => 'create_demo2_table',
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
