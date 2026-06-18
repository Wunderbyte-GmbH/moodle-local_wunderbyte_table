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
use cache_helper;
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

    /**
     * With show_all_options() enabled, all options from the sortarray appear in the hierarchy
     * even when $values is completely empty (no DB records at all).
     *
     * @covers \local_wunderbyte_table\filters\types\hierarchicalfilter::add_to_categoryobject
     */
    public function test_show_all_options_appear_when_values_is_empty(): void {
        $categoryobject = [];
        $filtersettings = [
            'competency' => [
                'showalloptions' => true,
                '1' => ['parent' => 'Group A', 'localizedname' => 'Option A1'],
                '2' => ['parent' => 'Group A', 'localizedname' => 'Option A2'],
                '3' => ['parent' => 'Group B', 'localizedname' => 'Option B1'],
            ],
        ];

        // Values is empty — simulates zero DB records for this filter column.
        hierarchicalfilter::add_to_categoryobject($categoryobject, $filtersettings, 'competency', []);

        $this->assertArrayHasKey('hierarchy', $categoryobject);

        // Collect all rendered item labels across subcategories.
        $renderedlabels = [];
        foreach ($categoryobject['hierarchy'] as $subcategory) {
            foreach ($subcategory['values'] as $item) {
                $renderedlabels[] = $item['key'];
            }
        }

        $this->assertContains('Option A1', $renderedlabels, 'Option A1 must appear even with no DB records.');
        $this->assertContains('Option A2', $renderedlabels, 'Option A2 must appear even with no DB records.');
        $this->assertContains('Option B1', $renderedlabels, 'Option B1 must appear even with no DB records.');
    }

    /**
     * With show_all_options() enabled, options that DO have DB records carry the correct count;
     * options that do NOT have DB records carry count = 0 (not omitted).
     *
     * This explicit count=0 prevents the Mustache context-stack walk-up that would otherwise
     * inherit the parent subcategory count.
     *
     * @covers \local_wunderbyte_table\filters\types\hierarchicalfilter::add_to_categoryobject
     */
    public function test_show_all_options_count_is_correct_for_populated_and_empty_options(): void {
        $categoryobject = [];
        $filtersettings = [
            'competency' => [
                'showalloptions' => true,
                '1' => ['parent' => 'Group A', 'localizedname' => 'Option A1'],
                '2' => ['parent' => 'Group A', 'localizedname' => 'Option A2'],
                '3' => ['parent' => 'Group B', 'localizedname' => 'Option B1'],
            ],
        ];

        // Only key '1' has a DB record with count 5.
        $values = ['1' => 5];

        hierarchicalfilter::add_to_categoryobject($categoryobject, $filtersettings, 'competency', $values);

        $itemsbyvalue = [];
        foreach ($categoryobject['hierarchy'] as $subcategory) {
            foreach ($subcategory['values'] as $item) {
                $itemsbyvalue[$item['value']] = $item;
            }
        }

        // Option '1' has 5 records.
        $this->assertArrayHasKey('1', $itemsbyvalue, 'Value 1 must be present.');
        $this->assertSame(5, $itemsbyvalue['1']['count']);

        // Options '2' and '3' have no DB records — count must be explicitly 0, not missing.
        $this->assertArrayHasKey('2', $itemsbyvalue, 'Value 2 must be present even with no DB records.');
        $this->assertArrayHasKey(
            'count',
            $itemsbyvalue['2'],
            'count key must exist on empty item to prevent Mustache context walk-up.'
        );
        $this->assertSame(0, $itemsbyvalue['2']['count']);

        $this->assertArrayHasKey('3', $itemsbyvalue, 'Value 3 must be present even with no DB records.');
        $this->assertArrayHasKey('count', $itemsbyvalue['3']);
        $this->assertSame(0, $itemsbyvalue['3']['count']);
    }

    /**
     * Non-array entries in the filtersettings (e.g. 'wbfilterclass', 'localizedname',
     * 'showalloptions', 'competency_wb_checked') must not produce spurious items in the
     * rendered hierarchy.
     *
     * @covers \local_wunderbyte_table\filters\types\hierarchicalfilter::add_to_categoryobject
     */
    public function test_show_all_options_metadata_keys_are_not_rendered_as_items(): void {
        $categoryobject = [];
        $filtersettings = [
            'competency' => [
                'showalloptions'         => true,
                'explode'                => ',',
                'wbfilterclass'          => 'some\\class',
                'localizedname'          => 'Competencies',
                'competency_wb_checked'  => 1,
                '1' => ['parent' => 'Group A', 'localizedname' => 'Option A1'],
                '2' => ['parent' => 'Group A', 'localizedname' => 'Option A2'],
            ],
        ];

        hierarchicalfilter::add_to_categoryobject($categoryobject, $filtersettings, 'competency', []);

        $allvalues = [];
        $allkeys = [];
        foreach ($categoryobject['hierarchy'] as $subcategory) {
            foreach ($subcategory['values'] as $item) {
                $allvalues[] = $item['value'];
                $allkeys[]   = $item['key'];
            }
        }

        // Only real option sort-keys should appear as 'value' (as integers, since PHP converts numeric string keys).
        $this->assertContains(1, $allvalues);
        $this->assertContains(2, $allvalues);

        // Metadata values must not appear.
        $this->assertNotContains(',', $allvalues, "'explode' separator must not appear as a filter item value.");
        $this->assertNotContains('some\\class', $allvalues, "'wbfilterclass' must not appear as a filter item value.");
        $this->assertNotContains('Competencies', $allvalues, "'localizedname' must not appear as a filter item value.");

        // Localized names for real options must appear; metadata keys must not.
        $this->assertContains('Option A1', $allkeys);
        $this->assertContains('Option A2', $allkeys);
        $this->assertNotContains('wbfilterclass', $allkeys);
        $this->assertNotContains('Competencies', $allkeys);
    }

    /**
     * With show_all_options(), both subcategories appear in the hierarchy even when neither has
     * any DB records. The standard mode would return early on the first empty subcategory.
     *
     * @covers \local_wunderbyte_table\filters\types\hierarchicalfilter::add_to_categoryobject
     */
    public function test_show_all_options_multiple_empty_subcategories_all_appear(): void {
        $categoryobject = [];
        $filtersettings = [
            'competency' => [
                'showalloptions' => true,
                '1' => ['parent' => 'Teaching', 'localizedname' => 'Lesson planning'],
                '2' => ['parent' => 'Teaching', 'localizedname' => 'Digital tools'],
                '3' => ['parent' => 'Research', 'localizedname' => 'Open Science'],
                '4' => ['parent' => 'Research', 'localizedname' => 'Publishing'],
            ],
        ];

        hierarchicalfilter::add_to_categoryobject($categoryobject, $filtersettings, 'competency', []);

        $subcategorylabels = array_column($categoryobject['hierarchy'], 'label');

        $this->assertContains('Teaching', $subcategorylabels, 'Teaching subcategory must appear even with no DB records.');
        $this->assertContains('Research', $subcategorylabels, 'Research subcategory must appear even with no DB records.');
    }

    /**
     * Regression guard: with the default (show_all_options() off), the hierarchical filter must
     * still omit options that have no DB records.
     *
     * @covers \local_wunderbyte_table\filters\types\hierarchicalfilter::add_to_categoryobject
     */
    public function test_default_hides_empty_options(): void {
        $categoryobject = [];
        $filtersettings = [
            'competency' => [
                '1' => ['parent' => 'Group A', 'localizedname' => 'Option A1'],
                '2' => ['parent' => 'Group A', 'localizedname' => 'Option A2'],
            ],
        ];

        // Only '1' is in $values (has DB record); showalloptions is not set.
        hierarchicalfilter::add_to_categoryobject($categoryobject, $filtersettings, 'competency', ['1' => 3]);

        $renderedvalues = [];
        foreach ($categoryobject['hierarchy'] as $subcategory) {
            foreach ($subcategory['values'] as $item) {
                $renderedvalues[] = $item['value'];
            }
        }

        $this->assertContains(1, $renderedvalues, 'Option 1 with DB record must appear.');
        $this->assertNotContains(
            2,
            $renderedvalues,
            'Option 2 without DB record must NOT appear by default (show_all_options off).'
        );
    }

    /**
     * Integration test: with show_all_options() all statically-defined options appear in the
     * filterjson even when some options have no matching courses/records.
     *
     * @covers \local_wunderbyte_table\filters\types\hierarchicalfilter
     */
    public function test_show_all_options_appear_in_filterjson(): void {
        $this->resetAfterTest(true);
        $_GET = [];
        $_POST = [];

        $field = $this->create_custom_text_field('competency');
        $cfid = $field->get('id');

        $category = $this->getDataGenerator()->create_category(['name' => 'Test']);

        // 3 courses have competency=1, none have competency=2.
        for ($i = 0; $i < 3; $i++) {
            $this->getDataGenerator()->create_course([
                'fullname' => 'Course ' . $i,
                'category' => $category->id,
                'customfield_competency' => '1',
            ]);
        }

        $filter = new hierarchicalfilter('competency', 'Competency');
        $filter->set_sql_for_fieldid($cfid);
        $filter->show_all_options();
        $filter->add_options([
            'explode' => ',',
            '1' => ['parent' => 'Group A', 'localizedname' => 'Option A1'],
            '2' => ['parent' => 'Group A', 'localizedname' => 'Option A2 (no records)'],
        ]);

        $table = new wunderbyte_table('hier_allopt_json_' . uniqid());
        $table->add_filter($filter);
        $table->set_filter_sql('*', '{course}', 'category = ' . $category->id, '', []);
        $table->outhtml(100, true);

        $this->assertNotEmpty($table->filterjson, 'filterjson must not be empty.');

        $filterjson = json_decode($table->filterjson, true);
        $compcategory = null;
        foreach ($filterjson['categories'] as $cat) {
            if ($cat['columnname'] === 'competency') {
                $compcategory = $cat;
                break;
            }
        }
        $this->assertNotNull($compcategory, 'competency category must be present in filterjson.');

        $renderedvalues = [];
        foreach ($compcategory['hierarchy'] as $subcategory) {
            foreach ($subcategory['values'] as $item) {
                $renderedvalues[$item['value']] = $item;
            }
        }

        $this->assertArrayHasKey('1', $renderedvalues, 'Option 1 (has records) must be in filterjson.');
        $this->assertArrayHasKey('2', $renderedvalues, 'Option 2 (no records) must ALSO be in filterjson.');
        $this->assertSame(
            0,
            $renderedvalues['2']['count'],
            'Option 2 must have count=0, not inherited from parent subcategory (Mustache walk-up prevention).'
        );
    }

    /**
     * Integration test: with show_all_options(), filtering by a populated option returns the
     * correct row count.
     *
     * @covers \local_wunderbyte_table\filters\types\hierarchicalfilter
     */
    public function test_show_all_options_filter_by_populated_option_returns_correct_rows(): void {
        $this->resetAfterTest(true);
        $_GET = [];
        $_POST = [];

        $field = $this->create_custom_text_field('comp2');
        $cfid = $field->get('id');
        $category = $this->getDataGenerator()->create_category(['name' => 'Test2']);

        // 4 courses with comp2=1, 2 courses with comp2=2.
        for ($i = 0; $i < 4; $i++) {
            $this->getDataGenerator()->create_course([
                'fullname' => 'Course1-' . $i,
                'category' => $category->id,
                'customfield_comp2' => '1',
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            $this->getDataGenerator()->create_course([
                'fullname' => 'Course2-' . $i,
                'category' => $category->id,
                'customfield_comp2' => '2',
            ]);
        }

        $filter = new hierarchicalfilter('comp2', 'Competency 2');
        $filter->set_sql_for_fieldid($cfid);
        $filter->show_all_options();
        $filter->add_options([
            '1' => ['parent' => 'Group A', 'localizedname' => 'Option 1'],
            '2' => ['parent' => 'Group A', 'localizedname' => 'Option 2'],
            '3' => ['parent' => 'Group B', 'localizedname' => 'Option 3 (empty)'],
        ]);

        [, $encodedtable] = $this->build_and_render_table($category->id, $filter);
        $this->assertNotEmpty($encodedtable);

        $this->assertEquals(4, $this->count_rows_with_filter($encodedtable, '{"comp2":["1"]}'));

        // Clear cache and reset state before applying a different filter.
        cache_helper::purge_by_event('changesinwunderbytetable');
        $_GET = [];
        $_POST = [];
        $this->assertEquals(2, $this->count_rows_with_filter($encodedtable, '{"comp2":["2"]}'));
    }

    /**
     * Integration test: with show_all_options(), filtering by an option with no DB records
     * returns 0 rows without errors.
     *
     * @covers \local_wunderbyte_table\filters\types\hierarchicalfilter
     */
    public function test_show_all_options_filter_by_empty_option_returns_zero_rows(): void {
        $this->resetAfterTest(true);
        $_GET = [];
        $_POST = [];

        $field = $this->create_custom_text_field('comp3');
        $cfid = $field->get('id');
        $category = $this->getDataGenerator()->create_category(['name' => 'Test3']);

        // Only option '1' has courses; option '99' has none.
        for ($i = 0; $i < 3; $i++) {
            $this->getDataGenerator()->create_course([
                'fullname' => 'Course-' . $i,
                'category' => $category->id,
                'customfield_comp3' => '1',
            ]);
        }

        $filter = new hierarchicalfilter('comp3', 'Competency 3');
        $filter->set_sql_for_fieldid($cfid);
        $filter->show_all_options();
        $filter->add_options([
            '1'  => ['parent' => 'Group A', 'localizedname' => 'Option 1'],
            '99' => ['parent' => 'Group A', 'localizedname' => 'Option 99 (empty)'],
        ]);

        [, $encodedtable] = $this->build_and_render_table($category->id, $filter);
        $this->assertNotEmpty($encodedtable);

        $this->assertEquals(0, $this->count_rows_with_filter($encodedtable, '{"comp3":["99"]}'));
    }

    /**
     * Integration test: with show_all_options(), when NO courses exist for the column at all,
     * the filter is still rendered (not suppressed) and all static options appear.
     *
     * This exercises the centralised get_data_for_filter_options() 'continue' handling that
     * keeps the filter instead of skipping it when there are no records.
     *
     * @covers \local_wunderbyte_table\filters\types\hierarchicalfilter
     */
    public function test_show_all_options_filter_is_shown_when_no_db_records_exist_at_all(): void {
        $this->resetAfterTest(true);
        $_GET = [];
        $_POST = [];

        $field = $this->create_custom_text_field('comp4');
        $cfid = $field->get('id');

        // Category with no courses at all.
        $emptycategory = $this->getDataGenerator()->create_category(['name' => 'Empty']);

        $filter = new hierarchicalfilter('comp4', 'Competency 4');
        $filter->set_sql_for_fieldid($cfid);
        $filter->show_all_options();
        $filter->add_options([
            '1' => ['parent' => 'Group A', 'localizedname' => 'Option 1'],
            '2' => ['parent' => 'Group A', 'localizedname' => 'Option 2'],
        ]);

        $table = new wunderbyte_table('hier_allopt_empty_' . uniqid());
        $table->add_filter($filter);
        $table->set_filter_sql('*', '{course}', 'category = ' . $emptycategory->id, '', []);
        $table->outhtml(100, true);

        $this->assertNotEmpty(
            $table->filterjson,
            'filterjson must be populated even when no DB records exist — filter must not be suppressed.'
        );

        $filterjson = json_decode($table->filterjson, true);
        $compcategory = null;
        foreach ($filterjson['categories'] as $cat) {
            if ($cat['columnname'] === 'comp4') {
                $compcategory = $cat;
                break;
            }
        }

        $this->assertNotNull(
            $compcategory,
            'comp4 filter category must appear in filterjson even with no DB records.'
        );

        $renderedvalues = [];
        foreach ($compcategory['hierarchy'] as $subcategory) {
            foreach ($subcategory['values'] as $item) {
                $renderedvalues[] = $item['value'];
            }
        }

        $this->assertContains(1, $renderedvalues, 'Option 1 must appear even with no DB records.');
        $this->assertContains(2, $renderedvalues, 'Option 2 must appear even with no DB records.');
    }

    /**
     * Create a custom field category + text field and return the field object.
     *
     * @param string $shortname
     * @return \core_customfield\field_controller
     */
    private function create_custom_text_field(string $shortname): \core_customfield\field_controller {
        $categorydata = new stdClass();
        $categorydata->name = 'Test Fields';
        $categorydata->component = 'core_course';
        $categorydata->area = 'course';
        $categorydata->itemid = 0;
        $categorydata->contextid = context_system::instance()->id;
        $category = $this->getDataGenerator()->create_custom_field_category((array)$categorydata);
        $category->save();

        $fielddata = new stdClass();
        $fielddata->categoryid = $category->get('id');
        $fielddata->name = $shortname;
        $fielddata->shortname = $shortname;
        $fielddata->type = 'text';
        $fielddata->configdata = '';
        $field = $this->getDataGenerator()->create_custom_field((array)$fielddata);
        $field->save();
        return $field;
    }

    /**
     * Build a table backed by {course}, render it, and return both the table and
     * its encoded hash for subsequent filter requests.
     *
     * @param int $categoryid  Course category id to restrict the query.
     * @param hierarchicalfilter $filter
     * @return array{0: wunderbyte_table, 1: string}  [$table, $encodedtable]
     */
    private function build_and_render_table(int $categoryid, $filter): array {
        $table = new wunderbyte_table('hier_allopt_test_' . uniqid());
        $table->add_filter($filter);
        $table->set_filter_sql('*', '{course}', 'category = ' . $categoryid, '', []);
        $html = $table->outhtml(100, true);

        preg_match('/<div[^>]*\sdata-encodedtable=["\']?([^"\'>\s]+)["\']?/i', $html, $matches);
        return [$table, $matches[1] ?? ''];
    }

    /**
     * Apply a wbtfilter URL param and recount rows via the cached table.
     *
     * @param string $encodedtable
     * @param string $filterjson  e.g. '{"depcontact":["1"]}'
     * @return int
     */
    private function count_rows_with_filter(string $encodedtable, string $filterjson): int {
        $_GET['wbtfilter'] = $filterjson;
        $cachedtable = wunderbyte_table::instantiate_from_tablecache_hash($encodedtable);
        $cachedtable->printtable(
            $cachedtable->pagesize,
            $cachedtable->useinitialsbar,
            $cachedtable->downloadhelpbutton
        );
        return (int)$cachedtable->totalrows;
    }
}
