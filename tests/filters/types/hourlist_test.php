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
use MoodleQuickForm;
use PHPUnit\Framework\TestCase;
use ReflectionClass;


/**
 * Unit tests for hourlist_test class.
 */
final class hourlist_test extends TestCase {
    /**
     * Test get_data_for_filter_options() method.
     * @covers \local_wunderbyte_table\filters\types\hourlist::__construct
     * @covers \local_wunderbyte_table\filters\types\hourlist::get_possible_timed_options
     * @covers \local_wunderbyte_table\filters\types\hourlist::add_filter
     */
    public function test_add_filter(): void {
        $filter = [];
        $columnidentifier = 'columnidentifier';
        $hourlistmanager = new hourlist($columnidentifier);
        $hourlistmanager->add_filter($filter);
        $this->assertArrayHasKey('id', $filter);
        $this->assertArrayHasKey('columnidentifier', $filter);
        $this->assertEquals('ID', $filter['id']['localizedname']);
        $this->assertEquals(1, $filter['columnidentifier']['columnidentifier_wb_checked']);
    }

    /**
     * Test add_filter() method.
     * @covers \local_wunderbyte_table\filters\types\hourlist::add_options
     */
    public function test_add_options(): void {
        $newoptions = [
            'new_option' => 'Something new',
        ];
        $columnidentifier = 'columnidentifier';
        $hourlistmanager = new hourlist($columnidentifier);
        $hourlistmanager->add_options($newoptions);

        $reflection = new ReflectionClass($hourlistmanager);
        $property = $reflection->getProperty('options');
        $property->setAccessible(true);
        $optionsvalue = $property->getValue($hourlistmanager);

        $this->assertCount(25, $optionsvalue);
        $this->assertArrayHasKey('new_option', $optionsvalue);
    }

    /**
     * Test add_filter() method.
     * @covers \local_wunderbyte_table\filters\types\hourlist::render_mandatory_fields
     */
    public function test_render_mandatory_fields(): void {
        $mform = $this->createMock(MoodleQuickForm::class);
        $mform->expects($this->once())
            ->method('addElement')
            ->with('html', '<p id="no-pairs-message" class="alert alert-info">No further seetings needed</p>');
        hourlist::render_mandatory_fields($mform);
    }

    /**
     * Test add_filter() method.
     * @covers \local_wunderbyte_table\filters\types\hourlist
     */
    public function test_get_filterspecific_values(): void {
        $data = new \stdClass();
        $data->localizedname = 'Test Filter';
        $data->wbfilterclass = 'hourlist';
        $data->testcolumn_wb_checked = '1';

        [$result, $filterspecific] = hourlist::get_filterspecific_values($data, 'testcolumn');

        $this->assertEquals('Test Filter', $result['localizedname']);
        $this->assertEquals('1', $result['testcolumn_wb_checked']);
        $this->assertEquals('hourlist', $result['wbfilterclass']);
    }
}
