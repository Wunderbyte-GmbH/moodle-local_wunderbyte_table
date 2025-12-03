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
use context_system;
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

        $customfieldfilter = new customfieldfilter('category');
        $customfieldfilter->set_sql(
            'category IN ( SELECT id FROM {course_categories} WHERE :where)',
            'name'
        );
        $table->add_filter($customfieldfilter);

        // Add customfield filter.
        $_GET['wbtfilter'] = '{"category":["My Category 2"]}';
        // Test if optional_param works when passing filter options via $_GET.
        $wbtfilter = optional_param('wbtfilter', '', PARAM_RAW);
        $this->assertEquals('{"category":["My Category 2"]}', $wbtfilter);

        // When countkeys is true (default) and a custom SQL statement exists.
        // In this situation, the logic must run the SQL that uses filter::get_db_filter_column()
        // and count the keys using the main SQL query.
        $data = customfieldfilter::get_data_for_filter_options($table, 'category');
        $this->assertCount(2, $data);
        $actual = array_map(fn($item) => $item->keycount, $data);
        $expected = ['5', '6'];
        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual);

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
     * with either the ILIKE (or LIKE in MySQL) or EQUAL operator.
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
            case 'LIKE':
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
            $this->assertTrue(property_exists($record, 'something'));
            $this->assertTrue(property_exists($record, 'keycount'));
            $this->assertContains($record->something, array_keys($options));
            $this->assertFalse($record->keycount);
        }
    }

    /**
     * This test checks the functionality of the get_data_for_filter_options() method.
     * We need to verify three scenarios:
     *
     * Scenario 1: When countkeys is false.
     *     In this case, no SQL query is executed to count the keys.
     *     This scenario is already covered by test_dont_count_keys_function().
     *
     * Scenario 2: When countkeys is true (default) and a custom SQL statement exists.
     *     In this situation, we must run the SQL that uses filter::get_db_filter_column()
     *     and count the keys using the main SQL query.
     *
     * Scenario 3: When countkeys is true (default) and no custom SQL statement exists.
     *     In this case, we count the keys using the customfield_field table directly,
     *     along with the main SQL query.
     *
     * For all three scenarios, we create several courses with custom fields and verify
     * that the method behaves correctly.
     *
     * @covers \local_wunderbyte_table\filters\types\customfieldfilter::get_data_for_filter_options
     *
     * @return void
     */
    public function test_get_data_for_filter_options(): void {
        // Scenario 1: This scenario is already covered by test_dont_count_keys_function().

        // Scenario 2: test_if_customfieldfilter_filters_the_records_sample1().

        // Scenario 3: For this scenario we create 19 courses that have 2 custom fields.
        // We first count the keys for each custom field, then apply one custom filter
        // and count the keys again.
        $category1 = $this->getDataGenerator()->create_category(['name' => 'My Category 1']);

        // Create custom field category in area course for courses.
        $categorydata = new \stdClass();
        $categorydata->name = 'My course desired fields';
        $categorydata->component = 'core_course';
        $categorydata->area = 'course';
        $categorydata->itemid = 0;
        $categorydata->contextid = context_system::instance()->id;
        $category = $this->getDataGenerator()->create_custom_field_category((array) $categorydata);
        $category->save();
        // Create custom field for the courses.
        $fielddata = new \stdClass();
        $fielddata->categoryid = $category->get('id');
        $fielddata->name = 'Owner departement contact';
        $fielddata->shortname = 'depcontact';
        $fielddata->type = 'text';
        $fielddata->configdata = "";
        $bookingfield1 = $this->getDataGenerator()->create_custom_field((array) $fielddata);
        $bookingfield1->save();

        $fielddata = new \stdClass();
        $fielddata->categoryid = $category->get('id');
        $fielddata->name = 'Owner departement contact 2';
        $fielddata->shortname = 'depcontact2';
        $fielddata->type = 'text';
        $fielddata->configdata = "";
        $bookingfield2 = $this->getDataGenerator()->create_custom_field((array) $fielddata);
        $bookingfield2->save();

        // Create some ourses & fill the custom field,
        // 10 options have depconatct custom filed with value 12345
        // and 9 options have depconatct custom filed with value 56789.
        $totalcourses = 19;
        for ($i = 0; $i < $totalcourses; $i++) {
            // Create course.
            $course = $this->getDataGenerator()->create_course([
                'fullname' => 'Course ' . $i,
                'category' => $category1->id,
                'customfield_depcontact' => ($i % 2 === 0) ? 12345 : 56789,
                'customfield_depcontact2' => ($i < 10) ? 'AAAA' : 'BBBB',
            ]);
        }

        $cfid1 = $bookingfield1->get('id');
        $cff1 = new customfieldfilter('depcontact', 'Owner departement contact');
        $cff1->set_sql_for_fieldid($cfid1);

        $cfid2 = $bookingfield2->get('id');
        $cff2 = new customfieldfilter('depcontact2', 'Owner departement contact 2');
        $cff2->set_sql_for_fieldid($cfid2);

        // Now we want to check whether the customfieldfilter is working correctly
        // and whether the table shows the correct results when a filter is applied.
        $table = new wunderbyte_table('sample_course_table');
        $table->add_filter($cff1);
        $table->add_filter($cff2);
        $table->set_filter_sql('*', '{course}', 'category=' . $category1->id, '', []);
        $renderedtablehtml = $table->outhtml(10, true);
        // We created 19 courses.
        $this->assertCount(19, $table->rawdata);

        // Count keys for depcontact.
        $data = customfieldfilter::get_data_for_filter_options($table, 'depcontact');
        $this->assertCount(2, $data);
        $actual = array_map(fn($item) => $item->keycount, $data);
        $expected = ['10', '9'];
        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual);

        // Count keys for depcontact2.
        $data = customfieldfilter::get_data_for_filter_options($table, 'depcontact2');
        $this->assertCount(2, $data);
        $actual = array_map(fn($item) => $item->keycount, $data);
        $expected = ['10', '9']; // We craeted 19 course (10 course = 12345 | 9 course = 56789).
        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual);

        // Get encodedtable string.
        preg_match('/<div[^>]*\sdata-encodedtable=["\']?([^"\'>\s]+)["\']?/i', $renderedtablehtml, $matches);
        $encodedtable = $matches[1];
        $this->assertNotEmpty($encodedtable);

        // Now we apply filter via url. We expect to see 10 records.
        $_GET['wbtfilter'] = '{"depcontact":["12345"]}';
        $cachedtable = wunderbyte_table::instantiate_from_tablecache_hash($encodedtable);
        $cachedtable->printtable($cachedtable->pagesize, $cachedtable->useinitialsbar, $cachedtable->downloadhelpbutton);
        $this->assertEquals(10, $cachedtable->totalrows);

        // Count keys for depcontact2 again.
        $data = customfieldfilter::get_data_for_filter_options($cachedtable, 'depcontact2');
        $this->assertCount(2, $data);
        $actual = array_map(fn($item) => $item->keycount, $data);
        $expected = ['5', '5']; // We craeted 19 course with depconatct = 12345 and (5 course = AAAA | 5 course = BBBB).
        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual);
    }

    /**
     * This test first checks whether the following methods in the custom field filter
     * work as expected:
     *   - set_sql_for_fieldid
     *   - use_operator_equal
     *   - apply_filter
     *
     * It then verifies that the custom field filter actually filters the table results
     * with real data. We create a custom field with the short name 'depcontact' and
     * 20 courses—10 of them have this custom field set to the value 12345, and the
     * other 10 have the value 56789.
     *
     * Finally, we check whether filtering works when applying a filter that should
     * return only the courses with the custom field value 12345.
     *
     * @covers \local_wunderbyte_table\filters\types\customfieldfilter::set_sql_for_fieldid
     *
     * @return void
     */
    public function test_set_sql_for_fieldid(): void {
        // Reset the test environment.
        $this->resetAfterTest(true);

        // Create course categories.
        $category1 = $this->getDataGenerator()->create_category(['name' => 'My Category 1']);

        // Create custom field category in area course for courses.
        $categorydata = new \stdClass();
        $categorydata->name = 'My course desired fields';
        $categorydata->component = 'core_course';
        $categorydata->area = 'course';
        $categorydata->itemid = 0;
        $categorydata->contextid = context_system::instance()->id;
        $category = $this->getDataGenerator()->create_custom_field_category((array) $categorydata);
        $category->save();
        // Create custom field for the courses.
        $fielddata = new \stdClass();
        $fielddata->categoryid = $category->get('id');
        $fielddata->name = 'Owner departement contact';
        $fielddata->shortname = 'depcontact';
        $fielddata->type = 'text';
        $fielddata->configdata = "";
        $bookingfield = $this->getDataGenerator()->create_custom_field((array) $fielddata);
        $bookingfield->save();

        // Create some ourses & fill the custom field,
        // 10 options have depconatct custom filed with value 12345
        // and 10 options have depconatct custom filed with value 56789.
        $totalcourses = 20;
        for ($i = 0; $i < $totalcourses; $i++) {
            // Create course.
            $course = $this->getDataGenerator()->create_course([
                'fullname' => 'Course ' . $i,
                'category' => $category1->id,
                'customfield_depcontact' => ($i % 2 === 0) ? 12345 : 56789,
            ]);
        }

        $customfieldid = $bookingfield->get('id');
        $customfieldfilter = new customfieldfilter('depcontact', 'Owner departement contact');
        $customfieldfilter->set_sql_for_fieldid($customfieldid);
        // Check if filter returns a SQL query which contains customfield_data table
        // since when we call set_sql_for_fieldid function that means there is not custom SQL
        // and the logic should use the default SQL.
        // Here we dont check the table's result, we just check if customfieldfilter returns the correct SQL.
        $filterstring = '';
        $temptable = new wunderbyte_table('temp_table');
        $temptable->add_filter($customfieldfilter);
        $temptable->set_filter_sql('*', '{course}', 'category=' . $category1->id, '', []);
        $temptable->outhtml(10, true);
        $customfieldfilter->apply_filter($filterstring, 'depcontact', ['12345'], $temptable);
        $this->assertNotEmpty($filterstring);
        $expectedsql = "id IN (SELECT instanceid FROM {customfield_data} cfd
                        WHERE cfd.fieldid = {$customfieldid}
                        AND ( '' || ',' || cfd.value || ','  ILIKE :param1 ESCAPE '\'))";
        $normalizedfilter = $this->remove_spaces_from_string($filterstring);
        $normalizedexpected = $this->remove_spaces_from_string($expectedsql);
        $this->assertStringContainsString($normalizedexpected, $normalizedfilter);

        // We laso check the functionality of use_operator_equal.
        // In this case the return SQL must contains = operator instead of ilike operator.
        $customfieldfilter->use_operator_equal();
        $filterstring = '';
        $temptable = new wunderbyte_table('temp_table');
        $temptable->add_filter($customfieldfilter);
        $temptable->set_filter_sql('*', '{course}', 'category=' . $category1->id, '', []);
        $temptable->outhtml(10, true);
        $customfieldfilter->apply_filter($filterstring, 'depcontact', ['12345'], $temptable);
        $this->assertNotEmpty($filterstring);
        $expectedsql = "id IN (SELECT instanceid FROM {customfield_data} cfd
                        WHERE cfd.fieldid = {$customfieldid}
                        AND (cfd.value = :param1))";
        $normalizedfilter = $this->remove_spaces_from_string($filterstring);
        $normalizedexpected = $this->remove_spaces_from_string($expectedsql);
        $this->assertStringContainsString($normalizedexpected, $normalizedfilter);

        // Now we want to chech if custom fieldfilter is working correctly
        // and we see correct result in the table when appliying a filter.
        $table = new wunderbyte_table('sample_course_table');
        $table->add_filter($customfieldfilter);
        $table->set_filter_sql('*', '{course}', 'category=' . $category1->id, '', []);
        $renderedtablehtml = $table->outhtml(10, true);
        // We created 20 courses.
        $this->assertCount(20, $table->rawdata);
        preg_match('/<div[^>]*\sdata-encodedtable=["\']?([^"\'>\s]+)["\']?/i', $renderedtablehtml, $matches);
        $encodedtable = $matches[1];
        $this->assertNotEmpty($encodedtable);

        // We still have no filter applied so we expect to see 20 records.
        $cachedtable = wunderbyte_table::instantiate_from_tablecache_hash($encodedtable);
        $cachedtable->printtable($cachedtable->pagesize, $cachedtable->useinitialsbar, $cachedtable->downloadhelpbutton);
        $this->assertEquals(20, $cachedtable->totalrows);

        // Now we apply filter via url. We expect to see 10 records.
        $_GET['wbtfilter'] = '{"depcontact":["12345"]}';
        $cachedtable = wunderbyte_table::instantiate_from_tablecache_hash($encodedtable);
        $cachedtable->printtable($cachedtable->pagesize, $cachedtable->useinitialsbar, $cachedtable->downloadhelpbutton);
        $this->assertEquals(10, $cachedtable->totalrows);
    }

    /**
     * Removes any spaces from the given string.
     * @param string $string
     * @return array|string|null
     */
    private function remove_spaces_from_string(string $string): string {
        // Remove leading/trailing junk.
        $string = trim($string);
        // Collapse repeated whitespace (including newlines) into a single space.
        $string = preg_replace('/\s+/', '', $string);
        return $string;
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
            'ILIKE/LIKE' => [
                'operator' => 'LIKE',
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
