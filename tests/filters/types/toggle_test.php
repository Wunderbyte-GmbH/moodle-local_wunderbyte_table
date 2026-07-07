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
 * @copyright 2026 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\filters\types;

use advanced_testcase;
use coding_exception;
use context_system;
use local_wunderbyte_table\wunderbyte_table;

/**
 * Unit tests for toggle filter class.
 * @covers \local_wunderbyte_table\filters\types\toggle
 */
final class toggle_test extends advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        // Clear before each test.
        $_GET = [];
        $_POST = [];
    }

    /**
     * A toggle has exactly one single value. The default value is '1' with the localized
     * filter name as label. set_toggle_value() and add_options() replace the single value.
     *
     * @covers \local_wunderbyte_table\filters\types\toggle::set_toggle_value
     * @covers \local_wunderbyte_table\filters\types\toggle::add_options
     *
     * @return void
     */
    public function test_toggle_has_exactly_one_value(): void {
        $this->resetAfterTest(true);

        $table = new wunderbyte_table('test_table_toggle');
        $table->set_filter_sql('*', '{course}', '1=1', '');

        $toggle = new toggle('bookable', 'Bookable soon');
        $table->add_filter($toggle);

        // Default toggle value is '1', labelled with the localized filter name.
        $records = toggle::get_data_for_filter_options($table, 'bookable');
        $this->assertCount(1, $records);
        $this->assertArrayHasKey('1', $records);
        $this->assertFalse($records['1']->keycount);

        // Set a specific toggle value: it replaces the default.
        $toggle->set_toggle_value('12345', 'My label');
        $records = toggle::get_data_for_filter_options($table, 'bookable');
        $this->assertCount(1, $records);
        $this->assertArrayHasKey('12345', $records);

        // The inherited add_options() also keeps only one single value.
        $toggle->add_options([
            '0' => 'Off value',
            '1' => 'On value',
        ]);
        $records = toggle::get_data_for_filter_options($table, 'bookable');
        $this->assertCount(1, $records);
        $this->assertArrayHasKey('0', $records);
    }

    /**
     * set_second_column() accepts '=', '<', '<=', '>', '>=' and '!=' (normalized to '<>')
     * and throws on anything else, as well as on invalid column names.
     *
     * @covers \local_wunderbyte_table\filters\types\toggle::set_second_column
     *
     * @dataProvider operator_provider
     *
     * @param string $operator
     * @param string|null $expectedinsql expected operator in generated sql, null if exception expected
     * @return void
     */
    public function test_set_second_column_operators(string $operator, ?string $expectedinsql): void {
        $this->resetAfterTest(true);

        $table = new wunderbyte_table('test_table_toggle');
        $table->set_filter_sql('*', '{course}', '1=1', '');

        $toggle = new toggle('bookable', 'Bookable soon');
        $subsql = "id IN (
            SELECT id FROM (
                SELECT c.id, c.startdate, shortname AS bookable FROM {course} c
            ) toggletbl
            WHERE :where
        )";
        $toggle->set_sql($subsql, 'bookable');
        $table->add_filter($toggle);

        if ($expectedinsql === null) {
            $this->expectException(coding_exception::class);
        }
        $toggle->set_second_column('startdate', 1234567890, $operator);

        $filter = '';
        $toggle->apply_filter($filter, 'bookable', ['1'], $table);
        $this->assertStringContainsString("startdate $expectedinsql :", $filter);
    }

    /**
     * Data provider for test_set_second_column_operators.
     * @return array
     */
    public static function operator_provider(): array {
        return [
            'equal' => ['=', '='],
            'lower' => ['<', '<'],
            'lower or equal' => ['<=', '<='],
            'greater' => ['>', '>'],
            'greater or equal' => ['>=', '>='],
            'not equal' => ['!=', '<>'],
            'sql not equal' => ['<>', '<>'],
            'invalid operator' => ['LIKE', null],
        ];
    }

    /**
     * An invalid column name for the second column throws an exception.
     *
     * @covers \local_wunderbyte_table\filters\types\toggle::set_second_column
     *
     * @return void
     */
    public function test_set_second_column_invalid_columnname(): void {
        $this->resetAfterTest(true);

        $toggle = new toggle('bookable', 'Bookable soon');
        $this->expectException(coding_exception::class);
        $toggle->set_second_column('startdate; DROP TABLE x', 1234567890, '<=');
    }

    /**
     * Without custom SQL and without fieldid, the toggle filters directly on the table
     * column using exact match, optionally combined with the second column condition
     * passed via the constructor (which then filters on the toggle value as well).
     *
     * @covers \local_wunderbyte_table\filters\types\toggle::apply_filter
     *
     * @return void
     */
    public function test_apply_filter_on_plain_column(): void {
        $this->resetAfterTest(true);

        $table = new wunderbyte_table('test_table_toggle');
        $table->set_filter_sql('*', '{course}', '1=1', '');

        // Without second column.
        $toggle = new toggle('bookable', 'Bookable soon');
        $table->add_filter($toggle);
        $filter = '';
        $toggle->apply_filter($filter, 'bookable', ['1'], $table);
        $this->assertSame('(bookable=:param1)', trim($filter));
        $this->assertSame('1', $table->sql->params['param1']);

        // With a second column passed via the constructor:
        // it is filtered on the toggle value as well.
        $table = new wunderbyte_table('test_table_toggle2');
        $table->set_filter_sql('*', '{course}', '1=1', '');
        $toggle = new toggle('bookable', 'Bookable soon', 'secondcolumn');
        $table->add_filter($toggle);
        $filter = '';
        $toggle->apply_filter($filter, 'bookable', ['1'], $table);
        $this->assertSame('((bookable=:param1) AND secondcolumn = :param2)', trim($filter));
        $this->assertSame('1', $table->sql->params['param1']);
        $this->assertSame('1', $table->sql->params['param2']);
    }

    /**
     * With a custom SQL subquery, the toggle value condition and the second column
     * condition are combined via AND and injected into the single :where placeholder.
     *
     * @covers \local_wunderbyte_table\filters\types\toggle::apply_filter
     * @covers \local_wunderbyte_table\filters\types\toggle::set_second_column
     *
     * @return void
     */
    public function test_apply_filter_with_custom_sql_and_second_column(): void {
        $this->resetAfterTest(true);

        $table = new wunderbyte_table('test_table_toggle');
        $table->set_filter_sql('*', '{course}', '1=1', '');

        $toggle = new toggle('bookable', 'Bookable soon');
        $subsql = "id IN (
            SELECT id FROM (
                SELECT c.id, c.startdate, shortname AS bookable FROM {course} c
            ) toggletbl
            WHERE :where
        )";
        $toggle->set_sql($subsql, 'bookable');
        $toggle->set_toggle_value('12345');
        $cutoff = 1234567890;
        $toggle->set_second_column('startdate', $cutoff, '<=');
        $table->add_filter($toggle);

        $filter = '';
        $toggle->apply_filter($filter, 'bookable', ['12345'], $table);

        // The :where placeholder must be replaced by both conditions combined via AND.
        $this->assertStringNotContainsString(':where', $filter);
        $this->assertStringContainsString('id IN (', $filter);
        $this->assertStringContainsString('(bookable=:param1) AND startdate <= :param2', $filter);
        $this->assertSame('12345', $table->sql->params['param1']);
        $this->assertSame((string)$cutoff, $table->sql->params['param2']);
    }

    /**
     * The toggle renders as a single switch: add_to_categoryobject() must build the
     * toggle structure with value, label and columnname instead of the default value list.
     *
     * @covers \local_wunderbyte_table\filters\types\toggle::add_to_categoryobject
     * @covers \local_wunderbyte_table\filters\types\toggle::prepare_filter_for_rendering
     *
     * @return void
     */
    public function test_add_to_categoryobject_and_rendering(): void {
        $this->resetAfterTest(true);

        $filtersettings = [
            'bookable' => [
                '12345' => 'Only bookable options',
                'localizedname' => 'Bookable soon',
                'wbfilterclass' => toggle::class,
                'wbbypasscache' => true,
                'showalloptions' => false,
                'bookable_wb_checked' => 1,
            ],
        ];
        $values = ['12345' => false];

        $categoryobject = [];
        toggle::add_to_categoryobject($categoryobject, $filtersettings, 'bookable', $values);

        $this->assertArrayHasKey('toggle', $categoryobject);
        $this->assertArrayNotHasKey('default', $categoryobject);
        $this->assertSame('12345', $categoryobject['toggle']['value']);
        $this->assertSame('Only bookable options', $categoryobject['toggle']['label']);
        $this->assertSame('bookable', $categoryobject['toggle']['columnname']);

        // When the filter is active, prepare_filter_for_rendering() checks the switch.
        $tableobject = [0 => ['toggle' => $categoryobject['toggle']]];
        toggle::prepare_filter_for_rendering($tableobject, ['bookable' => ['12345']], 0);
        $this->assertSame('checked', $tableobject[0]['toggle']['checked']);
        $this->assertSame('show', $tableobject[0]['show']);
    }

    /**
     * End-to-end: the toggle filters courses on a customfield value combined with an
     * upper bound on the startdate column ("taking place within the next weeks").
     *
     * We create 20 courses: 10 with customfield depcontact=12345 (of which 5 start within
     * the next week and 5 in eight weeks) and 10 with depcontact=56789. With the toggle
     * switched on, only the 5 courses with depcontact=12345 starting within the next
     * four weeks must remain. This also covers the cache roundtrip via
     * instantiate_from_tablecache_hash, so the toggle configuration must survive it.
     *
     * @covers \local_wunderbyte_table\filters\types\toggle::apply_filter
     *
     * @return void
     */
    public function test_toggle_filters_the_records(): void {
        $this->resetAfterTest(true);

        // Create course category.
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

        $soon = strtotime('today + 1 week');
        $later = strtotime('today + 8 weeks');
        $cutoff = strtotime('today + 4 weeks');

        // Create 20 courses: 10 with depcontact=12345 (5 starting soon, 5 later)
        // and 10 with depcontact=56789 (all starting soon).
        $totalcourses = 20;
        for ($i = 0; $i < $totalcourses; $i++) {
            $this->getDataGenerator()->create_course([
                'fullname' => 'Course ' . $i,
                'category' => $category1->id,
                'startdate' => ($i < 5 || $i >= 10) ? $soon : $later,
                'customfield_depcontact' => ($i < 10) ? 12345 : 56789,
            ]);
        }

        $customfieldid = $bookingfield->get('id');

        $toggle = new toggle('bookable', 'Bookable soon');
        $toggle->set_toggle_value('12345', 'Bookable soon');
        $subsql = "id IN (
            SELECT id FROM (
                SELECT c.id, c.startdate,
                (SELECT cfd.value
                 FROM {customfield_data} cfd
                 WHERE cfd.fieldid = {$customfieldid}
                 AND cfd.instanceid = c.id) AS bookable
                FROM {course} c
            ) toggletbl
            WHERE :where
        )";
        $toggle->set_sql($subsql, 'bookable');
        $toggle->set_second_column('startdate', $cutoff, '<=');

        $table = new wunderbyte_table('sample_course_table_toggle');
        $table->add_filter($toggle);
        $table->set_filter_sql('*', '{course}', 'category=' . $category1->id, '', []);
        $renderedtablehtml = $table->outhtml(30, true);
        // We created 20 courses.
        $this->assertCount(20, $table->rawdata);

        // Get encodedtable string.
        preg_match('/<div[^>]*\sdata-encodedtable=["\']?([^"\'>\s]+)["\']?/i', $renderedtablehtml, $matches);
        $encodedtable = $matches[1];
        $this->assertNotEmpty($encodedtable);

        // Toggle off: no filter applied, we expect to see all 20 records.
        $cachedtable = wunderbyte_table::instantiate_from_tablecache_hash($encodedtable);
        $cachedtable->printtable($cachedtable->pagesize, $cachedtable->useinitialsbar, $cachedtable->downloadhelpbutton);
        $this->assertEquals(20, $cachedtable->totalrows);

        // Toggle on: we expect the 5 courses with depcontact=12345 starting within 4 weeks.
        $_GET['wbtfilter'] = '{"bookable":["12345"]}';
        $cachedtable = wunderbyte_table::instantiate_from_tablecache_hash($encodedtable);
        $cachedtable->printtable($cachedtable->pagesize, $cachedtable->useinitialsbar, $cachedtable->downloadhelpbutton);
        $this->assertEquals(5, $cachedtable->totalrows);
    }

    protected function tearDown(): void {
        parent::tearDown();
        // Clean up globals after each test.
        $_GET = [];
        $_POST = [];
    }
}
