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

namespace local_wunderbyte_table\filters;

use advanced_testcase;
use local_wunderbyte_table\filters\types\customfieldfilter;
use local_wunderbyte_table\wunderbyte_table;

/**
 * Unit test to check how a filter affects reading data from the cache.
 *
 * @package local_wunderbyte_table
 * @copyright 2023 Wunderbyte Gmbh <info@wunderbyte.at>
 * @author Mahdi Poustini
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \local_wunderbyte_table\wunderbyte_table::apply_filter
 */
final class caching_test extends advanced_testcase {
    /**
     *
     *
     * This test checks the value of the `bypasscache` property in a table instance.
     * When we call `bypass_cache()` on a filter, we expect the table to read data directly from the database
     * instead of reading it from the cache.
     *
     * The logic caches the data for a query regardless of whether this property in the table is set to true or false,
     * but the difference occurs at the moment when the data is being read.
     * We cannot directly test this part, but we can verify that after applying a filter,
     * this property is equal to true.
     *
     * To test that, we need to make sure that the filter is being applied
     * due to the parameters in the URL.
     * Therefore, we expect the `bypasscache` property in the table to be equal to true
     * as we call the `bypass_cache()` function of any filter.
     *
     * @dataProvider data_provider
     *
     * @param bool $bypasscache
     * @return void
     */
    public function test_bypasscache_value_of_table(bool $bypasscache): void {
        // Reset the test environment.
        $this->resetAfterTest(true);

        // Create two course categories.
        $category1 = $this->getDataGenerator()->create_category(['name' => 'My Category 1']);
        $category2 = $this->getDataGenerator()->create_category(['name' => 'My Category 2']);

        // Now create courses inside their respective categories.
        $course1 = $this->getDataGenerator()->create_course([
            'fullname' => 'Course 1',
            'category' => $category1->id,
        ]);
        $course2 = $this->getDataGenerator()->create_course([
            'fullname' => 'Course 2',
            'category' => $category2->id,
        ]);

        $students = [];

        // Enrol 5 students in course 1.
        for ($i = 1; $i <= 5; $i++) {
            $students[$i] = $this->getDataGenerator()->create_user(['firstname' => "Student{$i}", 'username' => "student{$i}"]);
            $this->getDataGenerator()->enrol_user($students[$i]->id, $course1->id, 'student');
        }

        // Enrol 5 students in course 2.
        for ($i = 6; $i <= 10; $i++) {
            $students[$i] = $this->getDataGenerator()->create_user(['firstname' => "Student{$i}", 'username' => "student{$i}"]);
            $this->getDataGenerator()->enrol_user($students[$i]->id, $course2->id, 'student');
        }

        // Now instantiate Wunderbyte table.
        $table = new wunderbyte_table('test_table');

        // Add template switcher to table.
        $table->add_template_to_switcher(
            'local_wunderbyte_table/twtable_list',
            get_string('viewlist', 'local_wunderbyte_table'),
            true
        );

        $columns = [
            'id' => 'Course ID',
            'fullname' => 'Course name',
        ];

        // Number of items must be equal.
        $table->define_headers(array_values($columns));
        $table->define_columns(array_keys($columns));

        $table->sort_default_column = 'fullname';
        $table->sort_default_order = SORT_ASC; // Or SORT_DESC.

        $customfieldfilter = new customfieldfilter('category');
        $customfieldfilter->set_sql(
            'category IN ( SELECT id FROM {course_categories} WHERE :where)',
            'name'
        );

        if ($bypasscache) {
            $customfieldfilter->bypass_cache(); // We expected diffrent behaviour when we call this function on filter.
        }

        $table->add_filter($customfieldfilter);

        $from = "(
            SELECT ue.id as id, u.username, u.firstname, u.lastname, u.email, c.fullname, c.category
            FROM {user} u
            JOIN {user_enrolments} ue ON ue.userid = u.id
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {course} c ON e.courseid = c.id
        ) as s1";

        // Work out the sql for the table.
        $table->set_filter_sql('*', $from, '1=1', '');

        // Add customfield filter.
        $_GET['wbtfilter'] = '{"category":["My Category 2"]}';
        // Test if optional_param works when passing filter options via $_GET.
        $wbtfilter = optional_param('wbtfilter', '', PARAM_RAW);
        $this->assertEquals('{"category":["My Category 2"]}', $wbtfilter);

        $table->cardsort = true;
        $table->outhtml(10000, true);

        if ($bypasscache) {
            // When we have a filter which bypasses the cache so the expected value
            // for the bypasscache should be true in the table instance.
            $this->assertTrue($table->bypasscache);
        } else {
            // When we have a filter which DOES bypasses the cache so the expected value
            // for the bypasscache should be false in the table instance.
            $this->assertFalse($table->bypasscache);
        }
    }

    /**
     * Data provider for  test_if_filters_caching
     *
     * @return array
     */
    public static function data_provider(): array {
        return [
            'filter with bypasscache = false' => [
                'bypasscache' => false,
            ],
            'filter with bypasscache = true' => [
                'bypasscache' => true,
            ],
        ];
    }
}
