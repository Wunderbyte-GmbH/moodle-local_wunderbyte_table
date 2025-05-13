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
use local_wunderbyte_table\wunderbyte_table;
use MoodleQuickForm;
use PHPUnit\Framework\TestCase;
use ReflectionClass;


/**
 * Unit tests for hourlist_test class.
 */
final class callback_test extends TestCase {
    /**
     * Test get_data_for_filter_options() method.
     * @covers \local_wunderbyte_table\filters\types\callback::get_data_for_filter_options
     */
    public function test_get_data_for_filter_options(): void {
        $table = $this->createMock(wunderbyte_table::class);
        $key = 'default_key';

        $result = callback::get_data_for_filter_options($table, $key);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
    }

    /**
     * Test get_data_for_filter_options() method.
     * @covers \local_wunderbyte_table\filters\types\callback::apply_filter
     */
    public function test_apply_filter(): void {
        $table = $this->createMock(wunderbyte_table::class);

        $filter = 'testing the filter';
        $columnname = 'testing_column_name';
        $categoryvalue = ['testing_column_name'];
        $callbackmanager = new callback('testcolumn', 'Test Label');
        $callbackmanager->apply_filter($filter, $columnname, $categoryvalue, $table);

        $this->assertStringContainsString('1 = 1', $filter);
    }

    /**
     * Test get_data_for_filter_options() method.
     * @covers \local_wunderbyte_table\filters\types\callback::define_callbackfunction
     */
    public function test_define_callbackfunction(): void {
        $functionname = 'testing_functionname';
        $callbackfunction = 'callbackfunction';

        $callbackmanager = new callback('testcolumn', 'Test Label');
        $callbackmanager->define_callbackfunction($functionname);

        $reflection = new ReflectionClass($callbackmanager);
        $property = $reflection->getProperty($callbackfunction);
        $property->setAccessible(true);
        $callbackfunctionvalue = $property->getValue($callbackmanager);
        $this->assertEquals($functionname, $callbackfunctionvalue);
    }

    /**
     * Test add_filter() method.
     * @covers \local_wunderbyte_table\filters\types\callback::filter_by_callback
     */
    public function test_filter_by_callback(): void {
        $columnidentifier = 'columnidentifier';
        $records = [
            'new_option' => 'Something new',
        ];
        $not = false;
        $callbackmanager = new callback($columnidentifier);
        $result = $callbackmanager->filter_by_callback($records, $not);
        $this->assertEquals($result, $records);
    }

    /**
     * Test add_filter() method.
     * @covers \local_wunderbyte_table\filters\types\callback::add_options
     */
    public function test_add_options(): void {
        $newoptions = [
            'new_option' => 'Something new',
        ];
        $columnidentifier = 'columnidentifier';
        $hourlistmanager = new callback($columnidentifier);
        $hourlistmanager->add_options($newoptions);

        $reflection = new ReflectionClass($hourlistmanager);
        $property = $reflection->getProperty('options');
        $property->setAccessible(true);
        $optionsvalue = $property->getValue($hourlistmanager);

        $this->assertCount(1, $optionsvalue);
        $this->assertArrayHasKey('new_option', $optionsvalue);
    }

    /**
     * Test add_filter() method.
     * @covers \local_wunderbyte_table\filters\types\callback::render_mandatory_fields
     */
    public function test_render_mandatory_fields(): void {
        $mform = $this->createMock(MoodleQuickForm::class);
        $mform->expects($this->once())
            ->method('addElement')
            ->with('html', '<p id="no-pairs-message" class="alert alert-info">No further seetings needed</p>');

        callback::render_mandatory_fields($mform);
    }

    /**
     * Test add_filter() method.
     * @covers \local_wunderbyte_table\filters\types\callback::get_filterspecific_values
     */
    public function test_get_filterspecific_values(): void {
        $data = new \stdClass();
        $data->localizedname = 'Test Filter';
        $data->wbfilterclass = 'callback';
        $data->testcolumn_wb_checked = '1';

        [$result, $filterspecific] = callback::get_filterspecific_values($data, 'testcolumn');

        $this->assertEquals('Test Filter', $result['localizedname']);
        $this->assertEquals('1', $result['testcolumn_wb_checked']);
        $this->assertEquals('callback', $result['wbfilterclass']);
    }
}
