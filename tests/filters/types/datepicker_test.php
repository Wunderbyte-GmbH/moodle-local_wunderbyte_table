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


/**
 * Unit tests for hourlist_test class.
 */
final class datepicker_test extends TestCase {
    /**
     * Test get_data_for_filter_options() method.
     * @covers \local_wunderbyte_table\filters\types\datepicker::__construct
     * @covers \local_wunderbyte_table\filters\types\datepicker::add_filter
     */
    public function test_add_filter(): void {
        $filter = [];
        $columnidentifier = 'columnidentifier';
        $datepickeroperatormanager = new datepickeroperator($columnidentifier);
        $datepickeroperatormanager->add_filter($filter);
        $this->assertArrayHasKey('id', $filter);
        $this->assertArrayHasKey('columnidentifier', $filter);
        $this->assertEquals('ID', $filter['id']['localizedname']);
        $this->assertEquals(1, $filter['columnidentifier']['columnidentifier_wb_checked']);
    }

    /**
     * Test add_filter() method.
     * @covers \local_wunderbyte_table\filters\types\datepicker::add_options
     * @covers \local_wunderbyte_table\filters\types\datepicker::get_operatoroptions
     */
    public function test_add_options(): void {
        $columnidentifier = 'columnidentifier';
        $weekdaysmanager = new datepickeroperator($columnidentifier);

        $weekdaysmanager->add_options('standard', '<', 'Test Checkbox', '2025-01-01', '2025-12-31');

        $reflection = new ReflectionClass($weekdaysmanager);
        $property = $reflection->getProperty('options');
        $property->setAccessible(true);
        $optionsvalue = $property->getValue($weekdaysmanager);

        $this->assertNotEmpty($optionsvalue);
        $this->assertArrayHasKey($columnidentifier, $optionsvalue);
        $this->assertSame('<', $optionsvalue[$columnidentifier]['operator']);
        $this->assertSame('Test Checkbox', $optionsvalue[$columnidentifier]['checkboxlabel']);
        $this->assertSame('2025-01-01', $optionsvalue[$columnidentifier]['defaultvalue']);

        $weekdaysmanager->add_options('in between', '>', '', '2025-02-01', '2025-12-31', ['=', '<=']);

        $optionsvalue = $property->getValue($weekdaysmanager);
        $this->assertArrayHasKey($columnidentifier, $optionsvalue);
        $this->assertSame('2025-02-01', $optionsvalue[$columnidentifier]['defaultvaluestart']);
        $this->assertSame('2025-12-31', $optionsvalue[$columnidentifier]['defaultvalueend']);
        $this->assertSame(['=', '<='], $optionsvalue[$columnidentifier]['possibleoperations']);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage('novalidoperator');
        $weekdaysmanager->add_options('standard', 'invalid_operator');
    }

    /**
     * Test add_to_categoryobject() method.
     * @covers \local_wunderbyte_table\filters\types\datepicker::add_to_categoryobject
     */
    public function test_add_to_categoryobject(): void {
        $categoryobject = [];

        $filtersettings = [
            'testkey' => [
                "local_wunderbyte_table\\filters\\types\\datepicker" => true,
                'datepicker' => [
                    'Standard Label' => [
                        'operator' => '<',
                        'defaultvalue' => 1741824000,
                        'checkboxlabel' => 'Test Checkbox',
                    ],
                    'InBetween Label' => [
                        'columntimestart' => 'start_time_column',
                        'columntimeend' => 'end_time_column',
                        'defaultvaluestart' => 1741737600,
                        'defaultvalueend' => 1741824000,
                        'checkboxlabel' => 'Range Checkbox',
                        'possibleoperations' => ['flexoverlap'],
                    ],
                ],
            ],
        ];

        $fckey = 'testkey';
        $values = [];

        datepicker::add_to_categoryobject($categoryobject, $filtersettings, $fckey, $values);

        $this->assertArrayHasKey('datepicker', $categoryobject);
        $this->assertArrayHasKey('datepickers', $categoryobject['datepicker']);
        $this->assertCount(2, $categoryobject['datepicker']['datepickers']);

        $standardfilter = $categoryobject['datepicker']['datepickers'][0];
        $this->assertSame('Standard Label', $standardfilter['label']);
        $this->assertSame('<', $standardfilter['operator']);
        $this->assertSame(1741824000, $standardfilter['timestamp']);
        $this->assertSame('2025-03-13', $standardfilter['datereadable']);
        $this->assertSame('08:00', $standardfilter['timereadable']);
        $this->assertSame('Test Checkbox', $standardfilter['checkboxlabel']);

        $inbetweenfilter = $categoryobject['datepicker']['datepickers'][1];
        $this->assertSame('InBetween Label', $inbetweenfilter['label']);
        $this->assertSame('start_time_column', $inbetweenfilter['startcolumn']);
        $this->assertSame(1741737600, $inbetweenfilter['starttimestamp']);
        $this->assertSame(1741737600, $inbetweenfilter['startdatereadable']);
        $this->assertSame(1741824000, $inbetweenfilter['endtimestamp']);
        $this->assertSame('Range Checkbox', $inbetweenfilter['checkboxlabel']);
        $this->assertCount(1, $inbetweenfilter['possibleoperations']);
        $this->assertSame('flexoverlap', $inbetweenfilter['possibleoperations'][0]['operator']);

        $emptycategory = [];
        datepicker::add_to_categoryobject($emptycategory, $filtersettings, 'nonexistentkey', $values);
        $this->assertEmpty($emptycategory);
    }

    /**
     * Test add_filter() method.
     * @covers \local_wunderbyte_table\filters\types\datepicker::add_remove_button
     */
    public function test_apply_filter(): void {
        $mformmock = $this->getMockBuilder(\MoodleQuickForm::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addElement'])
            ->getMock();

        $filtertypename = 'testfilter';

        $mformmock->expects($this->once())
            ->method('addElement')
            ->with(
                $this->equalTo('button'),
                $this->equalTo("remove[{$filtertypename}]"),
                $this->stringContains('<i class="fa fa-trash"></i>'),
                $this->callback(function ($options) use ($filtertypename) {
                        return is_array($options) &&
                            isset($options['class']) && $options['class'] === 'btn remove-key-value' &&
                            isset($options['type']) && $options['type'] === 'button' &&
                            isset($options['data-groupid']) && $options['data-groupid'] === $filtertypename &&
                            isset($options['aria-label']) && $options['aria-label'] === "Remove key-value pair for {$filtertypename}";
                })
            );

        datepicker::add_remove_button($mformmock, $filtertypename);
    }

    /**
     * Test non_kestringy_value_pair_properties() method.
     * @covers \local_wunderbyte_table\filters\types\datepicker::non_kestringy_value_pair_properties
     * @covers \local_wunderbyte_table\filters\types\datepicker::add_remove_button
     *
     */
    public function test_non_kestringy_value_pair_properties(): void {
        $filtercolumn = 'testcolumn';

        $expected = [
            'localizedname',
            'wbfilterclass',
            'local_wunderbyte_table\filters\types\datepicker',
            'testcolumn_wb_checked',
        ];

        $result = datepicker::non_kestringy_value_pair_properties($filtercolumn);

        $this->assertIsArray($result);
        $this->assertSame($expected, $result);
    }

    /**
     * Test add_date_filter_head() method.
     * @covers \local_wunderbyte_table\filters\types\datepicker::add_date_filter_head
     */
    public function test_add_date_filter_head(): void {
        $mformmock = $this->getMockBuilder(\MoodleQuickForm::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addElement'])
            ->getMock();

        $filterlabel = 'Test Filter';
        $horizontallinecounter = 1;
        $htmlid = 'test-filter';

        $mformmock->expects($this->exactly(4))
            ->method('addElement')
            ->withConsecutive(
                [$this->equalTo('html'), $this->stringContains('<div id="test-filter">')],
                [$this->equalTo('html'), $this->stringContains('<hr>')],
                [$this->equalTo('html'), $this->stringContains('<b>Filter name: Test Filter</b>')]
            );

        datepicker::add_date_filter_head($mformmock, $filterlabel, $horizontallinecounter);
    }

    /**
     * Test get_filterspecific_values() method.
     * @covers \local_wunderbyte_table\filters\types\datepicker::get_filterspecific_values
     */
    public function test_get_filterspecific_values(): void {
        $data = [
            'datepicker' => [
                'filter1' => [],
                'filter2' => ['name' => 'Already Named'],
            ],
        ];
        $expectedresult = [
            'filter1' => ['name' => 'filter1'],
            'filter2' => ['name' => 'Already Named'],
        ];
        $result = datepicker::get_filterspecific_values($data, 'dummy_column');
        $this->assertSame($expectedresult, $result);
    }
}
