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
use moodle_exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;


/**
 * Unit tests for hourlist_test class.
 */
final class datepickeroperator_test extends TestCase {
    /**
     * Test get_operators() method.
     * @covers \local_wunderbyte_table\filters\types\datepickeroperator::get_operators
     */
    public function test_get_operators(): void {
        $expected = [
            '='  => '=',
            '<'  => '<',
            '>'  => '>',
            '<=' => '<=',
            '>=' => '>=',
        ];

        $result = datepickeroperator::get_operators();
        $this->assertSame($expected, $result);
    }

    /**
     * Test validate_input() method.
     * @covers \local_wunderbyte_table\filters\types\datepickeroperator::validate_input
     *
     */
    public function test_validate_input(): void {
        $validdata = [
            'datepicker' => [
                'filter1' => ['name' => 'Valid Filter'],
            ],
        ];
        $result = datepickeroperator::validate_input($validdata);
        $this->assertEmpty($result);

        $invaliddata = [
            'datepicker' => [
                'filter1' => ['name' => '', 'checkboxlabel' => 'Some label'],
            ],
        ];
        $result = datepickeroperator::validate_input($invaliddata);
        $this->assertArrayHasKey('filter1_group', $result);
        $this->assertSame(get_string('datepickererrormandatory', 'local_wunderbyte_table'), $result['filter1_group']);
    }

    /**
     * Test set_date_default_value_input() method.
     * @covers \local_wunderbyte_table\filters\types\datepickeroperator::get_new_filter_values
     */
    public function test_get_new_filter_values(): void {
        $data = new stdClass();
        $data->datepicker = [
            'filter1' => [
                'name' => 'Filter One',
                'checkboxlabel' => 'Check One',
                'operator' => '>',
                'defaultvalue' => '2025-03-10',
            ],
            'filter2' => [
                'name' => 'Filter Two',
                'checkboxlabel' => 'Check Two',
                'operator' => '<',
                'defaultvalue' => '2025-03-11',
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
                    'operator' => '>',
                    'defaultvalue' => '2025-03-10',
                ],
                'Filter Two' => (object) [
                    'checkboxlabel' => 'Check Two',
                    'operator' => '<',
                    'defaultvalue' => '2025-03-11',
                ],
            ],
            'testcolumn_wb_checked' => '1',
            'wbfilterclass' => 'custom_filter_class',
        ];

        $result = datepickeroperator::get_new_filter_values($data, 'testcolumn');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test render_mandatory_fields() method.
     * @covers \local_wunderbyte_table\filters\types\datepickeroperator::render_mandatory_fields
     * @covers \local_wunderbyte_table\filters\types\datepickeroperator::set_date_filter_input
     * @covers \local_wunderbyte_table\filters\types\datepickeroperator::set_date_default_value_input
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

        $mformmock->expects($this->exactly(2))
            ->method('addGroup');

        datepickeroperator::render_mandatory_fields($mformmock, $data);
    }
}
