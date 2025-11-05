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
 * @covers \local_wunderbyte_table\filters\types\customfieldfilter
 */
final class customfieldfilter_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        // Clear before each test.
        $_GET = [];
        $_POST = [];
    }
    /**
     * test_if_customfieldfilter_filters_the_records_sample1
     * @return void
     */
    public function test_if_customfieldfilter_filters_the_records_sample1(): void {
        global $DB, $_GET;

        $_GET = [];
        $_POST = [];

        $this->preventResetByRollback();

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

        // Enroll student 1 in course2. So the student1 is enrolled i both courses.
        $this->getDataGenerator()->enrol_user($students[1]->id, $course2->id, 'student');

        // Assert that each course1 has 5 enrolled students.
        $enrolled1 = get_enrolled_users(context_course::instance($course1->id));
        $this->assertCount(5, $enrolled1, 'Course 1 should have 5 enrolled students.');

        // Assert that each course2 has 6 enrolled students.
        $enrolled2 = get_enrolled_users(context_course::instance($course2->id));
        $this->assertCount(6, $enrolled2, 'Course 2 should have 5 enrolled students.');

        // Now instantiate Wunderbyte table.
        $table = new wunderbyte_table('test_table');

        // Add template switcher to table.
        $table->add_template_to_switcher(
            'local_wunderbyte_table/twtable_list',
            get_string('viewlist', 'local_wunderbyte_table'),
            true
        );
        $table->add_template_to_switcher(
            'local_wunderbyte_table/twtable_cards',
            get_string('viewcards', 'local_wunderbyte_table')
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

        $from = "(
            SELECT ue.id as id, u.username, u.firstname, u.lastname, u.email, c.fullname, c.category
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

        // Add customfield filter.
        $_GET['wbtfilter'] = '{"category":["My Category 2"]}';
        // Test if optional_param works when passing filter options via $_GET.
        $wbtfilter = optional_param('wbtfilter', '', PARAM_RAW);
        $this->assertEquals('{"category":["My Category 2"]}', $wbtfilter);

        $customfieldfilter = new customfieldfilter('category');
        $customfieldfilter->set_sql(
            'category IN ( SELECT id FROM {course_categories} WHERE :where)',
            'name'
        );
        $table->add_filter($customfieldfilter);
        $table->outhtml(10000, true);
        $this->assertCount(6, $table->rawdata);
    }

    /**
     * test_if_customfieldfilter_filters_the_records_sample2
     * @return void
     */
    public function test_if_customfieldfilter_filters_the_records_sample2(): void {
        global $CFG, $DB, $_GET;

        $_GET = [];
        $_POST = [];

        $this->preventResetByRollback();

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

        // Enroll student 1 in course2. So the student1 is enrolled i both courses.
        $this->getDataGenerator()->enrol_user($students[1]->id, $course2->id, 'student');

        // The user ID of supervisor will be set in a custom profile for each user.
        // Here we define a new custom field.
        $this->create_custom_profile_field('supervisor', 'Supervisor');
        $exists = $DB->record_exists('user_info_field', ['shortname' => 'supervisor']);
        $this->assertTrue($exists, 'Custom profile field "supervisor" was not created.');

        require_once($CFG->dirroot . '/user/profile/lib.php');
        // We set a supervisor ID for students 1, 3, 5, 7, 9.
        foreach ([1, 3, 5, 7, 9] as $id) {
            profile_save_data((object)['id' => $students[$id]->id, 'profile_field_supervisor' => 11111]);
        }

        // Assert that each course has 5 enrolled students.
        $enrolled1 = get_enrolled_users(context_course::instance($course1->id));
        $enrolled2 = get_enrolled_users(context_course::instance($course2->id));

        $this->assertCount(5, $enrolled1, 'Course 1 should have 5 enrolled students.');
        $this->assertCount(6, $enrolled2, 'Course 2 should have 6 enrolled students.');

        // Now instantiate Wunderbyte table.
        $table = new wunderbyte_table('test_table2');

        // Add template switcher to table.
        $table->add_template_to_switcher(
            'local_wunderbyte_table/twtable_list',
            get_string('viewlist', 'local_wunderbyte_table'),
            true
        );
        $table->add_template_to_switcher(
            'local_wunderbyte_table/twtable_cards',
            get_string('viewcards', 'local_wunderbyte_table')
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

        $from = "(
            SELECT ue.id as id, u.id as userid, u.username, u.firstname, u.lastname, u.email, c.fullname
            ,(
                SELECT uid.data
                FROM {user_info_data} uid
                JOIN {user_info_field} uif ON uid.fieldid = uif.id
                WHERE uif.name = 'supervisor'
                AND uid.userid = ue.id
            ) as supervisor
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

        // Add customfield filter.
        $_GET['wbtfilter'] = '{"supervisor":["11111"]}';
        // Test if optional_param works when passing filter options via $_GET.
        $wbtfilter = optional_param('wbtfilter', '', PARAM_RAW);
        $this->assertEquals('{"supervisor":["11111"]}', $wbtfilter);

        $customfieldfilter = new customfieldfilter('supervisor');
        $subsql = "
            userid IN (
                SELECT userid
                FROM {user_info_data} uid
                JOIN {user_info_field} uif ON uid.fieldid = uif.id
                WHERE uif.shortname = 'supervisor'
                AND :where
            )
        ";
        $customfieldfilter->set_sql($subsql, 'uid.data');
        $table->add_filter($customfieldfilter);
        $table->outhtml(10000, true);
        // We expected to see 6 records because Students 1,2,5,7,9
        // have supervisor with id 11111 and student1 is enrolled in 2 courses.
        $this->assertCount(6, $table->rawdata);
    }

    /**
     * Tests whether the custom field filter returns a generated WHERE condition
     * with either the ILIKE or EQUAL operator.
     *
     * In this case, we just want to make sure that the following functions work properly.
     * We don’t create a real environment.
     * - use_operator_ilike()
     * - use_operator_equal()
     *
     * @covers \local_wunderbyte_table\filters\types\customfieldfilter::use_operator_ilike
     * @covers \local_wunderbyte_table\filters\types\customfieldfilter::use_operator_equal
     *
     * @dataProvider data_provider
     *
     * @param string $operator
     * @return void
     */
    public function test_ilike_or_equal_where_condition(string $operator): void {
        // Instantiate Wunderbyte table.
        $table = new wunderbyte_table('test_table2');
        $table->set_filter_sql('*', "", '1=1', '');

        $customfieldfilter = new customfieldfilter('supervisor');
        $subsql = "
            userid IN (
                SELECT userid
                FROM {user_info_data} uid
                JOIN {user_info_field} uif ON uid.fieldid = uif.id
                WHERE uif.shortname = 'supervisor'
                AND :where
            )
        ";
        $customfieldfilter->set_sql($subsql, 'uid.data');

        $coulmname = 'anything';
        switch ($operator) {
            case '=':
                $customfieldfilter->use_operator_equal();
                break;
            case 'ILIKE':
            default:
                $customfieldfilter->use_operator_ilike();
        }
        $table->add_filter($customfieldfilter);

        $values = [1, 2, 3];
        $filter = "";
        $customfieldfilter->apply_filter($filter, $coulmname, $values, $table);
        $this->assertStringContainsString($operator, $filter);
    }

    /**
     * Test if dont_count_keys function sets the countkey to false.
     * If yes, we expect to get our desired result from get_data_for_filter_options() function.
     *
     * @covers \local_wunderbyte_table\filters\types\customfieldfilter::dont_count_keys
     * @covers \local_wunderbyte_table\filters\types\customfieldfilter::get_data_for_filter_options
     *
     * @return void
     */
    public function test_dont_count_keys_function(): void {
        // Instantiate Wunderbyte table.
        $table = new wunderbyte_table('test_table2');
        $table->set_filter_sql('*', "", '1=1', '');
        // Instantiate a customfieldfilter.
        $filter = new customfieldfilter('something', 'something');
        // Add options to this filter.
        $options = [
            '1' => 'option 1',
            '2' => 'option 2',
        ];
        $filter->add_options($options);
        // It's very important.
        $filter->dont_count_keys();
        $table->add_filter($filter);
        // As we called dont_count_keys(), the property countkeys will be set to false.
        // Based on the logic, we expect to get a result containing our injected options from this function.
        $records = $filter->get_data_for_filter_options($table, 'something');
        $this->assertNotEmpty($records);
        $this->assertCount(count($options), $records);
        foreach ($records as $record) {
            $this->assertObjectHasProperty('something', $record);
            $this->assertObjectHasProperty('keycount', $record);
            $this->assertContains($record->something, array_keys($options));
            $this->assertFalse($record->keycount);
        }
    }

    /**
     * Creates a custom user profile field.
     *
     * @param string $shortname Field shortname (e.g. 'supervisor')
     * @param string $name Field name shown in UI
     * @return void
     */
    private function create_custom_profile_field(string $shortname, string $name): void {
        global $DB;

        // Insert into user_info_field_category (required).
        if (!$DB->record_exists('user_info_category', ['name' => 'Test Category'])) {
            $cat = new \stdClass();
            $cat->name = 'Test Category';
            $cat->sortorder = 1;
            $cat->id = $DB->insert_record('user_info_category', $cat);
        } else {
            $cat = $DB->get_record('user_info_category', ['name' => 'Test Category']);
        }

        // Define the profile field.
        $field = new \stdClass();
        $field->shortname = $shortname;
        $field->name = $name;
        $field->datatype = 'text'; // Could be 'text', 'menu', 'checkbox', etc.
        $field->description = '';
        $field->descriptionformat = FORMAT_HTML;
        $field->categoryid = $cat->id;
        $field->sortorder = 1;
        $field->required = 0;
        $field->locked = 0;
        $field->visible = 1;
        $field->forceunique = 0;
        $field->signup = 0;
        $field->defaultdata = '';
        $field->defaultdataformat = FORMAT_HTML;
        $field->param1 = 30; // Max length for 'text'.
        $field->id = $DB->insert_record('user_info_field', $field);
    }

    /**
     * Data provider which providers string.
     * @return array
     */
    public static function data_provider(): array {
        return [
            'ILIKE' => [
                'operator' => 'ILIKE',
            ],
            '=' => [
                'operator' => '=',
            ],
        ];
    }
    protected function tearDown(): void {
        parent::tearDown();
        // Clean up globals after each test.
        $_GET = [];
        $_POST = [];
    }
}
