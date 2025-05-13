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


/**
 * Unit tests for intrange_test class.
 */
final class intrange_test extends TestCase {
    /**
     * Test get_data_for_filter_options() method.
     * @covers \local_wunderbyte_table\filters\types\intrange::get_data_for_filter_options
     */
    public function test_get_data_for_filter_options(): void {
        $table = $this->createMock(wunderbyte_table::class);
        $key = 'default_key';

        $result = intrange::get_data_for_filter_options($table, $key);
        $this->assertSame([], $result);
    }

    /**
     * Test add_filter() method.
     * @covers \local_wunderbyte_table\filters\types\intrange::add_filter
     */
    public function test_add_filter(): void {
        $filter = [];
        $intrange = new intrange('testcolumn', 'Test Label');
        $intrange->add_filter($filter);

        $this->assertArrayHasKey('id', $filter);
        $this->assertArrayHasKey('testcolumn', $filter);
        $this->assertArrayHasKey('wbfilterclass', $filter['testcolumn']);
    }

    /**
     * Test add_filter() method.
     * @covers \local_wunderbyte_table\filters\types\intrange::add_options
     */
    public function test_add_options(): void {
        $intrange = new intrange('testcolumn', 'Test Label');
        $intrange->add_options('Test Checkbox', 10, 50);

        $reflection = new \ReflectionClass($intrange);
        $property = $reflection->getProperty('options');
        $property->setAccessible(true);
        $options = $property->getValue($intrange)['Test Label'];

        $this->assertEquals('Test Checkbox', $options['checkboxlabel']);
        $this->assertEquals(10, $options['defaultvaluestart']);
        $this->assertEquals(50, $options['defaultvalueend']);
    }

    /**
     * Test add_filter() method.
     * @covers \local_wunderbyte_table\filters\types\intrange::add_to_categoryobject
     */
    public function test_add_to_categoryobject(): void {
        $categoryobject = [];
        $filtersettings = [
            'testkey' => [
                'local_wunderbyte_table\\filters\\types\\intrange' => true,
                'intrange' => [
                    'label1' => [
                        'defaultvaluestart' => 5,
                        'columntimeend' => 10,
                        'checkboxlabel' => 'Test Checkbox',
                    ],
                ],
            ],
        ];

        intrange::add_to_categoryobject($categoryobject, $filtersettings, 'testkey', []);

        $this->assertArrayHasKey('intrange', $categoryobject);
        $this->assertArrayHasKey('intranges', $categoryobject['intrange']);
        $this->assertCount(1, $categoryobject['intrange']['intranges']);
        $this->assertEquals('label1', $categoryobject['intrange']['intranges'][0]['label']);
        $this->assertEquals('testkey', $categoryobject['intrange']['intranges'][0]['column']);
        $this->assertEquals(5, $categoryobject['intrange']['intranges'][0]['startvalue']);
        $this->assertEquals(10, $categoryobject['intrange']['intranges'][0]['endvalue']);
        $this->assertEquals('Test Checkbox', $categoryobject['intrange']['intranges'][0]['checkboxlabel']);
    }

    /**
     * Test add_filter() method.
     * @covers \local_wunderbyte_table\filters\types\intrange::apply_filter
     */
    public function test_apply_filter_with_invalid_categoryvalue(): void {
        $filter = '';
        $table = $this->createMock(wunderbyte_table::class);
        $intrange = new intrange('testcolumn', 'Test Label');

        $intrange->apply_filter($filter, 'testcolumn', 123, $table);

        $this->assertEquals('', $filter);
    }

    /**
     * Test add_filter() method.
     * @covers \local_wunderbyte_table\filters\types\intrange::apply_filter
     * @covers \local_wunderbyte_table\filters\types\intrange::return_int_values
     */
    public function test_apply_filter_with_valid_categoryvalue(): void {
        $filter = '';
        $table = $this->createMock(wunderbyte_table::class);
        $table->expects($this->any())
            ->method('set_params')
            ->willReturnCallback(function ($param) {
                static $counter = 0;
                $counter++;
                return 'param' . $counter;
            });

        $intrange = new intrange('testcolumn', 'Test Label');
        $intrange->apply_filter($filter, 'testcolumn', '10,20', $table);

        $this->assertStringContainsString('BETWEEN :param1 AND :param2', $filter);
    }

    /**
     * Test add_filter() method.
     * @covers \local_wunderbyte_table\filters\types\intrange::prepare_filter_for_rendering
     */
    public function test_prepare_filter_for_rendering(): void {
        $tableobject = [];
        $filterarray = ['testkey' => '10,20'];

        intrange::prepare_filter_for_rendering($tableobject, $filterarray, 0);

        $this->assertArrayHasKey('show', $tableobject[0]);
        $this->assertEquals('show', $tableobject[0]['show']);
        $this->assertEquals('', $tableobject[0]['collapsed']);
        $this->assertEquals('true', $tableobject[0]['expanded']);
        $this->assertEquals('checked', $tableobject[0]['intrange']['intranges'][0]['checked']);
        $this->assertEquals('10', $tableobject[0]['intrange']['intranges'][0]['startvalue']);
        $this->assertEquals('20', $tableobject[0]['intrange']['intranges'][0]['endvalue']);
    }

    /**
     * Test add_filter() method.
     * @covers \local_wunderbyte_table\filters\types\intrange::render_mandatory_fields
     */
    public function test_render_mandatory_fields(): void {
        $mform = $this->createMock(MoodleQuickForm::class);
        $mform->expects($this->once())
            ->method('addElement')
            ->with('html', '<p id="no-pairs-message" class="alert alert-info">No further seetings needed</p>');

        intrange::render_mandatory_fields($mform);
    }

    /**
     * Test add_filter() method.
     * @covers \local_wunderbyte_table\filters\types\intrange::get_filterspecific_values
     */
    public function test_get_filterspecific_values(): void {
        $data = new \stdClass();
        $data->localizedname = 'Test Filter';
        $data->wbfilterclass = 'intrange';
        $data->testcolumn_wb_checked = '1';

        [$result, $filterspecific] = intrange::get_filterspecific_values($data, 'testcolumn');

        $this->assertEquals('Test Filter', $result['localizedname']);
        $this->assertEquals([], $result['intrange']);
        $this->assertEquals('1', $result['testcolumn_wb_checked']);
        $this->assertEquals('intrange', $result['wbfilterclass']);
    }
}
