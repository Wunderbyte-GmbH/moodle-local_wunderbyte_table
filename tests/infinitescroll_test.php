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
use local_wunderbyte_table\filters\types\datepicker;
use local_wunderbyte_table\filters\types\standardfilter;
use local_wunderbyte_table\local\sortables\types\standardsortable;

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
final class infinitescroll_test extends advanced_testcase {
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
    public function test_infinite_scroll_pages(): void {
        // First, we create ten courses.
        $numberofcourses = 50;
        $this->create_test_courses($numberofcourses);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $table = $this->create_demo2_table();
        $table->infinitescroll = 10;

        // First, we make one request to inverse the order.
        // Here, we inverse the sorting of the fullname column.
        $rows = $this->get_rows_for_table($table, 0, 'fullname', null, null, 3);

        // Now we fetch all the rows.
        $allrows = [];
        $page = 0;
        while (count($rows) > 0) {
            $rows = $this->get_rows_for_table($table, $page);
            $allrows = array_merge($allrows, $rows);
            $page++;
        }

        $shortnames = [];

        // We check if the order is correct until the last course.
        foreach ($allrows as $row) {
            $name = $row->datafields[1]->value;
            $shortnames[] = $row->datafields[2]->value;
            [$prefix1, $prefix2, $number] = explode(' ', $name);
            $this->assertEquals((int)$number, $numberofcourses);
            $numberofcourses--;
        }

        // Now we inverse it again.
        $rows = $this->get_rows_for_table($table, 0, 'fullname', null, null, 4);

        // Now we fetch all the rows.
        $allrows = [];
        $page = 0;
        while (count($rows) > 0) {
            $rows = $this->get_rows_for_table($table, $page);
            $allrows = array_merge($allrows, $rows);
            $page++;
        }

        $numberofcourses = 1;
        // We check if the order is correct until the last course.
        foreach ($allrows as $row) {
            $name = $row->datafields[1]->value;
            [$prefix1, $prefix2, $number] = explode(' ', $name);
            $this->assertEquals((int)$number, $numberofcourses);
            $numberofcourses++;
        }

        // Now we get inversed by shortnames.
        $rows = $this->get_rows_for_table($table, 0, 'shortname', null, null, 3);

        // We expect the shortnames to come sorted like this.
        usort($shortnames, function ($a, $b) {
            return strnatcmp($b, $a); // Reverse natural sorting.
        });

        // Now we fetch all the rows.
        $allrows = [];
        $page = 0;

        while (count($rows) > 0) {
            $rows = $this->get_rows_for_table($table, $page);
            $allrows = array_merge($allrows, $rows);
            $page++;
        }

        $numberofcourses = 1;
        // We check if the order is correct until the last course.
        foreach ($allrows as $row) {
            $name = $row->datafields[2]->value;
            $shortname = array_shift($shortnames);
            $this->assertEquals($name, $shortname);
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
     * Create a defined number of testcourses.
     *
     * @param int $coursestocreate
     * @param array $options
     * @return array
     *
     */
    public function create_test_courses(int $coursestocreate = 1, $options = []): array {
        global $DB;

        $shortnames = $this->return_shortnames();

        $returnarray = [];
        // We add another three courses.
        $counter = 0;
        $length = strlen((string)$coursestocreate);
        while ($counter < $coursestocreate) {
            $counter++;

            $courseoptions = $options;
            if (!isset($options['fullname'])) {
                $courseoptions['fullname'] = 'Test course ' . str_pad($counter, $length, '0', STR_PAD_LEFT);
            }
            if (!isset($options['fullname'])) {
                $shortname = array_shift($shortnames);
                $courseoptions['shortname'] = "$shortname " . str_pad($counter, $length, '0', STR_PAD_LEFT);
                $shortnames[] = $shortname;
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
            $rows = [];
        } else {
            $rows = $jsonobject->table->rows ?? 0;
        }
        return $rows;
    }

    /**
     * Just a function to return shortnames for testing purposes.
     *
     * @return array
     *
     */
    public function return_shortnames() {
        $shortnames = [
            "skadjflsafds",
            "qweopzxmcnvb",
            "plmoknijbuhv",
            "zxcvbnmasdfg",
            "ytrewqlkjhgf",
            "mnbvcxzlkjhg",
            "poiuytrewqsd",
            "aslkdjfhgqwe",
            "bnvcmxzpoiuy",
            "qazwsxedcrfv",
            "lkjhgfdspoiu",
            "mxcvbnalkjhg",
            "poiuytrewqlk",
            "qwertyuiopas",
            "zxcvbnmlkjhg",
            "asdfghjklzxc",
            "lkjhgfdsmnbv",
            "plokmijnuhbv",
            "zsexdrcftvgy",
            "qazxswedcvfr",
            "bvcxzlkjhgfq",
            "mnbvcxzqwert",
            "oiuytrewplkm",
            "asdkfjhglpoi",
            "zxcvbnmawert",
            "mnbvcxzasdfg",
            "poiuytrewqlk",
            "zsexdrcftvgb",
            "lkjhgfdsqazw",
            "qazwsxedcrfv",
            "mnbvcxzasdfg",
            "oiuytrewplkm",
            "zxcvbnmlkjhg",
            "poiuytrewqsd",
            "aslkdjfhgqwe",
            "bnvcmxzpoiuy",
            "lkjhgfdsplok",
            "mxcvbnalkjhg",
            "qwertyuiopas",
            "lkjhgfdsmnbv",
        ];
        return $shortnames;
    }
}
