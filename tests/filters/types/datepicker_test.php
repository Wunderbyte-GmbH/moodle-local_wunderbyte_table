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

use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Unit tests for hourlist_test class.
 */
final class datepicker_test extends TestCase {
    /**
     * Test get_operatoroptions_name() method.
     * @covers \local_wunderbyte_table\filters\types\datepicker::get_operatoroptions_name
     *
     */
    public function test_get_operatoroptions_name(): void {
        $selectedoptions = ['0', '2'];
        $expected = ['within', 'overlapstart'];

        $result = datepicker::get_operatoroptions_name($selectedoptions);
        $this->assertSame($expected, $result);
    }

    /**
     * Test get_operatoroptions_index() method.
     * @covers \local_wunderbyte_table\filters\types\datepicker::get_operatoroptions_index
     */
    public function test_get_operatoroptions_index(): void {
        $selectedoptions = ['within', 'overlapstart'];
        $expected = [0, 2];

        $result = datepicker::get_operatoroptions_index($selectedoptions);
        $this->assertSame($expected, $result);
    }

    /**
     * Test get_timestamp() method.
     * @covers \local_wunderbyte_table\filters\types\datepicker::get_timestamp
     */
    public function test_get_timestamp(): void {
        $input = ['day' => 10, 'month' => 3, 'year' => 2025];
        $expected = mktime(0, 0, 0, 3, 10, 2025);

        $result = datepicker::get_timestamp($input);
        $this->assertSame($expected, $result);
    }

    /**
     * Test get_moodle_form_date() method.
     * @covers \local_wunderbyte_table\filters\types\datepicker::get_moodle_form_date
     */
    public function test_get_moodle_form_date(): void {
        $timestamp = mktime(0, 0, 0, 3, 10, 2025);
        $expected = ['day' => 10, 'month' => 3, 'year' => 2025];

        $result = datepicker::get_moodle_form_date($timestamp);
        $this->assertSame($expected, $result);
    }

    /**
     * Test validate_input() method.
     * @covers \local_wunderbyte_table\filters\types\datepicker::validate_input
     */
    public function test_validate_input(): void {
        $validdata = [
            'datepicker' => [
                'filter1' => ['name' => 'Valid Filter', 'operator' => '='],
            ],
        ];
        $result = datepicker::validate_input($validdata);
        $this->assertEmpty($result);

        $invaliddata = [
            'datepicker' => [
                'filter1' => ['name' => '', 'checkboxlabel' => 'Some label', 'operator' => '='],
            ],
        ];
        $result = datepicker::validate_input($invaliddata);
        $this->assertArrayHasKey('filter1_group', $result);
        $this->assertSame(get_string('datepickererrormandatory', 'local_wunderbyte_table'), $result['filter1_group']);
    }

    /**
     * Test get_new_filter_values() method.
     * @covers \local_wunderbyte_table\filters\types\datepicker::get_new_filter_values
     */
    public function test_get_new_filter_values(): void {
        $data = new stdClass();
        $data->datepicker = [
            'filter1' => [
                'name' => 'Filter One',
                'checkboxlabel' => 'Check One',
                'defaultvaluestart' => ['day' => 10, 'month' => 3, 'year' => 2025],
                'defaultvalueend' => ['day' => 20, 'month' => 3, 'year' => 2025],
                'possibleoperations' => [0, 2],
            ],
        ];
        $data->localizedname = 'Localized Filter';
        $data->wbfilterclass = 'custom_filter_class';
        $data->testcolumn_wb_checked = '1';

        $expected = [
            'localizedname' => 'Localized Filter',
            'custom_filter_class' => true,
            'datepicker' => (object) [
                'Filter One' => (object) [
                    'checkboxlabel' => 'Check One',
                    'columntimestart' => 'startdate',
                    'columntimeend' => 'enddate',
                    'labelstartvalue' => 'Timespan',
                    'labelendvalue' => 'enddate',
                    'defaultvaluestart' => mktime(0, 0, 0, 3, 10, 2025),
                    'defaultvalueend' => mktime(0, 0, 0, 3, 20, 2025),
                    'possibleoperations' => ['within', 'overlapstart'],
                ],
            ],
            'testcolumn_wb_checked' => '1',
            'wbfilterclass' => 'custom_filter_class',
        ];

        $result = datepicker::get_new_filter_values($data, 'testcolumn');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test render_mandatory_fields() method.
     * @covers \local_wunderbyte_table\filters\types\datepicker
     */
    public function test_render_mandatory_fields(): void {
        $mformmock = $this->getMockBuilder(\MoodleQuickForm::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addElement', 'addGroup'])
            ->getMock();

        $data = [
            'Filter1' => ['name' => 'Filter1'],
            'Filter2' => ['name' => 'Filter2'],
        ];

        $mformmock->expects($this->atLeastOnce())
            ->method('addElement');

        if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
            // Code for PHP 8.0.0 or greater.
            $mformmock->expects($this->exactly(2))
                ->method('addGroup');
        } else {
            // Code for lower versions.
            $mformmock->expects($this->atLeastOnce())
                ->method('addGroup');
        }
        datepicker::render_mandatory_fields($mformmock, $data);
    }

    /**
     * Adding a datepicker switches the filter panel on, like every other filter type: the table
     * creates the id entry of the filter definition unchecked, which hides the whole filter.
     * A table that only has datepicker filters did not show them.
     *
     * @covers \local_wunderbyte_table\filters\types\datepickerbase::add_filter
     */
    public function test_add_filter_shows_the_filter_panel(): void {
        // The filter definition of a table without filters.
        $filter = [
            'id' => [
                'localizedname' => 'ID',
                'id_wb_checked' => 0,
            ],
        ];

        $datepicker = new datepicker('timecreated', 'Time created', 'timecreated', 'Time created');
        $datepicker->add_options('in between', '<', 'Apply filter', 'now - 1 month', 'now', ['within']);
        $datepicker->add_filter($filter);

        $this->assertSame(1, $filter['id']['id_wb_checked']);
        $this->assertSame(1, $filter['timecreated']['timecreated_wb_checked']);
        // Both ends of the period apply to the same column.
        $options = $filter['timecreated']['datepicker']['Time created'];
        $this->assertSame('timecreated', $options['columntimestart']);
        $this->assertSame('timecreated', $options['columntimeend']);
        $this->assertSame(['within'], $options['possibleoperations']);

        // Without any definition, the id entry is created checked, as before.
        $empty = [];
        $datepicker->add_filter($empty);
        $this->assertSame(1, $empty['id']['id_wb_checked']);
    }
}
