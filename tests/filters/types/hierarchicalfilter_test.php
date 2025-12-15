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
use context_system;
use local_wunderbyte_table\wunderbyte_table;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

/**
 * Unit tests for hourlist_test class.
 */
final class hierarchicalfilter_test extends advanced_testcase {
    /**
     * Test get_operatoroptions_name() method.
     * @covers \local_wunderbyte_table\filters\types\hierarchicalfilter::add_options
     *
     */
    public function test_add_options(): void {
        $columnidentifier = 'columnidentifier';
        $hierarchicalfilter = new hierarchicalfilter($columnidentifier);
        $options = [
            'key1' => 'Value 1',
            'key2' => 'Value 2',
        ];

        $hierarchicalfilter->add_options($options);

        $reflection = new ReflectionClass($hierarchicalfilter);
        $property = $reflection->getProperty('options');
        $property->setAccessible(true);
        $storedoptions = $property->getValue($hierarchicalfilter);

        $this->assertSame($options, $storedoptions);
    }

    /**
     * Test validate_input() method.
     * @covers \local_wunderbyte_table\filters\types\hierarchicalfilter::validate_input
     */
    public function test_validate_input(): void {
        $validdata = [
            'keyvaluepairs' => [
                'filter1' => ['key' => 'valid', 'parent' => 'someparent', 'localizedname' => 'Localized Name'],
            ],
        ];
        $result = hierarchicalfilter::validate_input($validdata);
        $this->assertEmpty($result);

        $invaliddata = [
            'keyvaluepairs' => [
                'filter1' => ['key' => '', 'parent' => 'someparent', 'localizedname' => 'Localized Name'],
            ],
        ];
        $result = hierarchicalfilter::validate_input($invaliddata);
        $this->assertArrayHasKey('filter1_group', $result);
        $this->assertSame('Either all or no values have to be filled out', $result['filter1_group']);
    }

    /**
     * Test get_filterspecific_values() method.
     * @covers \local_wunderbyte_table\filters\types\hierarchicalfilter::get_filterspecific_values
     */
    public function test_get_filterspecific_values(): void {
        $data = [
            'localizedname' => 'Localized Filter',
            'wbfilterclass' => 'custom_filter_class',
            'testcolumn_wb_checked' => '1',
            'filter1' => ['key' => 'key1', 'parent' => 'parent1', 'localizedname' => 'Localized 1'],
            'filter2' => ['key' => 'key2', 'parent' => 'parent2', 'localizedname' => 'Localized 2'],
        ];

        $expected = [
            'filter1' => ['key' => 'key1', 'parent' => 'parent1', 'localizedname' => 'Localized 1'],
            'filter2' => ['key' => 'key2', 'parent' => 'parent2', 'localizedname' => 'Localized 2'],
        ];

        [$result, $filterspecific] = hierarchicalfilter::get_filterspecific_values($data, 'testcolumn');
        $this->assertSame($expected, $result);
    }

    /**
     * Test render_mandatory_fields() method.
     * @covers \local_wunderbyte_table\filters\types\hierarchicalfilter::render_mandatory_fields
     */
    public function test_render_mandatory_fields(): void {
        $mformmock = $this->getMockBuilder(\MoodleQuickForm::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addElement', 'addGroup', 'createElement', 'setDefault'])
            ->getMock();

        $data = [
            'Filter1' => ['key' => 'Key1', 'parent' => 'Parent1', 'localizedname' => 'Localized1'],
            'Filter2' => ['key' => 'Key2', 'parent' => 'Parent2', 'localizedname' => 'Localized2'],
        ];

        $mformmock->expects($this->exactly(2))
            ->method('addGroup');

        hierarchicalfilter::render_mandatory_fields($mformmock, $data);
    }

    /**
     * Test get_new_filter_values() method.
     * @covers \local_wunderbyte_table\filters\types\hierarchicalfilter::get_new_filter_values
     */
    public function test_get_new_filter_values(): void {
        $data = new stdClass();
        $data->localizedname = 'Localized Filter';
        $data->wbfilterclass = 'custom_filter_class';
        $data->testcolumn_wb_checked = '1';
        $data->keyvaluepairs = [
            'filter1' => ['key' => 'key1', 'parent' => 'parent1', 'localizedname' => 'Localized 1'],
            'filter2' => ['key' => 'key2', 'parent' => 'parent2', 'localizedname' => 'Localized 2'],
        ];

        $expected = [
            'localizedname' => 'Localized Filter',
            'wbfilterclass' => 'custom_filter_class',
            'testcolumn_wb_checked' => '1',
            'key1' => (object) ['parent' => 'parent1', 'localizedname' => 'Localized 1'],
            'key2' => (object) ['parent' => 'parent2', 'localizedname' => 'Localized 2'],
        ];

        $result = hierarchicalfilter::get_new_filter_values($data, 'testcolumn');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test apply_filter() method.
     * @covers \local_wunderbyte_table\filters\types\hierarchicalfilter::apply_filter
     */
    public function test_apply_filter(): void {
        $columnidentifier = 'columnidentifier';
        $filter = 'initial filter';
        $columnname = 'test_column';
        $categoryvalue = [];

        $tablemock = $this->createMock(\local_wunderbyte_table\wunderbyte_table::class);

        $hierarchicalfilter = new hierarchicalfilter($columnidentifier);
        $hierarchicalfilter->apply_filter($filter, $columnname, $categoryvalue, $tablemock);

        $this->assertSame('initial filter (  ) ', $filter);
    }

    /**
     * Test add_to_categoryobject() method.
     * @covers \local_wunderbyte_table\filters\types\hierarchicalfilter::add_to_categoryobject
     */
    public function test_add_to_categoryobject(): void {
        $categoryobject = [];
        $filtersettings = [
            'testkey' => [
                'jsonattribute' => 'attribute',
                'filtervalue1' => ['localizedname' => 'Localized 1', 'parent' => 'Parent1'],
                'filtervalue2' => ['localizedname' => 'Localized 2', 'parent' => 'Parent2'],
            ],
        ];
        $values = [
            '{"attribute": "filtervalue1"}' => true,
            '{"attribute": "filtervalue2"}' => true,
        ];

        hierarchicalfilter::add_to_categoryobject($categoryobject, $filtersettings, 'testkey', $values);

        $this->assertArrayHasKey('hierarchy', $categoryobject);
        $this->assertCount(2, $categoryobject['hierarchy']);
        $this->assertArrayHasKey('label', $categoryobject['hierarchy'][0]);
        $this->assertArrayHasKey('values', $categoryobject['hierarchy'][0]);
    }

    /**
     * In this test, we check whether the hierarchical filter works on custom fields
     * when they are not selected via a join in the main query.
     *
     * @covers \local_wunderbyte_table\filters\types\hierarchicalfilter
     * @return void
     */
    public function test_hierarchical_filter_works_on_customfields_without_joining(): void {
        $this->resetAfterTest(true);

        // Create custom field category in area course for courses.
        $categorydata = new stdClass();
        $categorydata->name = 'My course desired fields';
        $categorydata->component = 'core_course';
        $categorydata->area = 'course';
        $categorydata->itemid = 0;
        $categorydata->contextid = context_system::instance()->id;
        $category = $this->getDataGenerator()->create_custom_field_category((array) $categorydata);
        $category->save();
        // Create custom field for the courses.
        $fielddata = new stdClass();
        $fielddata->categoryid = $category->get('id');
        $fielddata->name = 'Owner departement contact';
        $fielddata->shortname = 'depcontact';
        $fielddata->type = 'text';
        $fielddata->configdata = "";
        $bookingfield1 = $this->getDataGenerator()->create_custom_field((array) $fielddata);
        $bookingfield1->save();

        // Create course category.
        $category1 = $this->getDataGenerator()->create_category(['name' => 'My Category 1']);

        // Create some courses & fill the custom field,
        // 4 options have depconatct custom filed with value 12345 (indexes 0,3,6,9) and
        // 6 options have depconatct custom filed with value 56789.
        $totalcourses = 10;
        for ($i = 0; $i < $totalcourses; $i++) {
            // Create course.
            $course = $this->getDataGenerator()->create_course([
                'fullname' => 'Course ' . $i,
                'category' => $category1->id,
                'customfield_depcontact' => ($i % 3 === 0) ? 12345 : 56789,
            ]);
        }

        $cfid1 = $bookingfield1->get('id');
        $hierarchicalfilter = new hierarchicalfilter('depcontact', 'Owner departement contact');
        $hierarchicalfilter->set_sql_for_fieldid($cfid1);
        $hierarchicalfilter->add_options([
            'explode' => ',',
            'cat 1' => [
                'parent' => 'cat 1',
                'localizedname' => '12345',
            ],
            'cat 2' => [
                'parent' => 'cat 2',
                'localizedname' => '56789',
            ],
        ]);

        // Now we want to check whether the customfieldfilter is working correctly
        // and whether the table shows the correct results when a filter is applied.
        $table = new wunderbyte_table('sample_course_table');

        // Add filter.
        $table->add_filter($hierarchicalfilter);

        $table->set_filter_sql('*', '{course}', 'category=' . $category1->id, '', []);
        $renderedtablehtml = $table->outhtml(10, true);
        // We created 10 courses.
        $this->assertCount(10, $table->rawdata);

        // Get encodedtable string.
        preg_match('/<div[^>]*\sdata-encodedtable=["\']?([^"\'>\s]+)["\']?/i', $renderedtablehtml, $matches);
        $encodedtable = $matches[1];
        $this->assertNotEmpty($encodedtable);

        // Now we apply filter via url. We expect to see 4 records.
        $_GET['wbtfilter'] = '{"depcontact":["12345"]}';
        $cachedtable = wunderbyte_table::instantiate_from_tablecache_hash($encodedtable);
        $cachedtable->printtable($cachedtable->pagesize, $cachedtable->useinitialsbar, $cachedtable->downloadhelpbutton);
        $this->assertEquals(4, $cachedtable->totalrows);
    }
}
