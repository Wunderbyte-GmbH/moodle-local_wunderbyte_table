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
 * The Wunderbyte table class is an extension of the tablelib table_sql class.
 *
 * @package local_wunderbyte_table
 * @copyright 2023 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\filters\types;
use advanced_testcase;
use context_course;
use local_wunderbyte_table\wunderbyte_table;


/**
 * Unit tests for standardfilter class.
 */
final class customfieldfilter_test extends advanced_testcase {
    /**
     * Summary of test_if_customfiledfilter_filters_the_records
     * @return void
     */
    public function test_if_customfiledfilter_filters_the_records(): void {
        global $DB;

        // Reset the test environment.
        $this->resetAfterTest(true);

        // Create two course categories.
        $category1 = $this->getDataGenerator()->create_category(['name' => 'Category 1']);
        $category2 = $this->getDataGenerator()->create_category(['name' => 'Category 2']);

        // Now create courses inside their respective categories.
        $course1 = $this->getDataGenerator()->create_course([
            'fullname' => 'Course 1',
            'category' => $category1->id,
        ]);
        $course2 = $this->getDataGenerator()->create_course([
            'fullname' => 'Course 2',
            'category' => $category2->id,
        ]);

        // Create an array to hold students for both courses.
        $studentscourse1 = [];
        $studentscourse2 = [];
        $students = [];

        // Enrol 5 students in course 1.
        for ($i = 1; $i <= 5; $i++) {
            $students[$i] = $this->getDataGenerator()->create_user(['firstname' => "Student{$i}", 'username' => "student{$i}"]);
            $this->getDataGenerator()->enrol_user($students[$i]->id, $course1->id, 'student');
            $studentscourse1[] = $students[$i];
        }

        // Enrol 5 students in course 2.
        for ($i = 6; $i <= 10; $i++) {
            $students[$i] = $this->getDataGenerator()->create_user(['firstname' => "Student{$i}", 'username' => "student{$i}"]);
            $this->getDataGenerator()->enrol_user($students[$i]->id, $course2->id, 'student');
            $studentscourse2[] = $students[$i];
        }

        // Enroll student 1 in course2. So the student1 is enrolled i both courses.
        $this->getDataGenerator()->enrol_user($students[1]->id, $course2->id, 'student');
        $studentscourse2[] = $students[1];

        // Assert that each course has 5 enrolled students.
        $enrolled1 = get_enrolled_users(context_course::instance($course1->id));
        $enrolled2 = get_enrolled_users(context_course::instance($course2->id));

        $this->assertCount(5, $enrolled1, 'Course 1 should have 5 enrolled students.');
        $this->assertCount(6, $enrolled2, 'Course 2 should have 5 enrolled students.');

        // Now instantiate Wunderbyte table.
        $table = new wunderbyte_table('test_table');

        // Add template switcher to table.
        $table->add_template_to_switcher('local_wunderbyte_table/twtable_list', get_string('viewlist', 'local_wunderbyte_table'), true);
        $table->add_template_to_switcher('local_wunderbyte_table/twtable_cards', get_string('viewcards', 'local_wunderbyte_table'));

        $columns = [
            'id' => 'Course ID',
            'fullname' => 'Course name',
        ];

        // Number of items must be equal.
        $table->define_headers(array_values($columns));
        $table->define_columns(array_keys($columns));

        $table->sort_default_column = 'fullname';
        $table->sort_default_order = SORT_ASC; // Or SORT_DESC.

        $from = "(
            SELECT ue.id as id, u.username, u.firstname, u.lastname, u.email, c.fullname
            FROM {user} u
            JOIN {user_enrolments} ue ON ue.userid = u.id
            JOIN {enrol} e ON e.id = ue.enrolid
            JOIN {course} c ON e.courseid = c.id
        ) as s1";

        // Work out the sql for the table.
        $table->set_filter_sql('*', $from, '1=1', '');

        $table->cardsort = true;

        $table->outhtml(10000, true);
        $this->assertCount(11, $table->rawdata);

        // Add standard filter.
        // $standardfilter = new standardfilter('username', 'Username');
        // $table->add_filter($standardfilter);
        // $table->apply_filter('{"username":["student1", "student2"]}');
        // $table->outhtml(10000, true);
        // // As student1 is enrolled in 2 courses, we expect to see 2 records.
        // $this->assertCount(3, $table->rawdata);

        // Add customfield filter.
        $customfieldfilter = new customfieldfilter(
            'category,course_categories,name',
            'category name',
        );
        $table->add_filter($customfieldfilter);
        $table->apply_filter('{"category,course_categories,name":["Category 1", "Category 2"]}');
        $table->outhtml(10000, true);
        $this->assertCount(5, $table->rawdata);
    }
}
