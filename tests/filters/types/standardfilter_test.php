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
use local_wunderbyte_table\filters\types\standardfilter;


/**
 * Unit tests for standardfilter class.
 */
final class standardfilter_test extends TestCase {
    /**
     * Test define_sql() method.
     * @covers \local_wunderbyte_table\filters\types\standardfilter::define_sql
     */
    public function test_define_sql(): void {
        $filter = new standardfilter('username');
        $filter->define_sql('test_field', 'test_table', 'test_condition');

        $reflection = new \ReflectionClass($filter);
        $property = $reflection->getProperty('sql');
        $property->setAccessible(true);
        $sql = $property->getValue($filter);

        $this->assertSame('test_field', $sql['field']);
        $this->assertSame('test_table', $sql['from']);
        $this->assertSame('test_condition', $sql['where']);
    }

    /**
     * Test define_sql() method.
     * @covers \local_wunderbyte_table\filters\types\standardfilter::add_options
     */
    public function test_add_options(): void {
        $filter = new standardfilter('username');
        $options = ['key1' => 'value1', 'key2' => 'value2'];

        $filter->add_options($options);

        $reflection = new \ReflectionClass($filter);
        $property = $reflection->getProperty('options');
        $property->setAccessible(true);
        $result = $property->getValue($filter);

        $this->assertEquals($options, $result);
    }

    /**
     * Test define_sql() method.
     * @covers \local_wunderbyte_table\filters\types\standardfilter::validate_input
     * @covers \local_wunderbyte_table\filters\types\standardfilter::only_partial_submitted
     */
    public function test_validate_input(): void {
        $data = [
            'keyvaluepairs' => [
                'one' => [
                    'key' => 'one',
                    'value' => 'one',
                ],
                'two' => [
                    'key' => 'two',
                    'value' => null,
                ],
            ],
        ];

        $errors = standardfilter::validate_input($data);
        $this->assertArrayHasKey('two_group', $errors);
    }

    /**
     * Test define_sql() method.
     * @covers \local_wunderbyte_table\filters\types\standardfilter::get_dynamic_values
     */
    public function test_get_dynamic_values(): void {
        $mformmock = $this->createMock(\MoodleQuickForm::class);

        $mformmock->expects($this->any())
            ->method('getElement')
            ->willReturn((object)['_elements' => [(object)['_attributes' => ['name' => 'key', 'value' => '']]]]);

        $fieldsandsubmitteddata = [
            'form' => $mformmock,
            'data' => ['key' => 'testvalue'],
            'errors' => ['key' => 'Error message'],
        ];

        $result = standardfilter::get_dynamic_values($fieldsandsubmitteddata);
        $this->assertInstanceOf(\MoodleQuickForm::class, $result);
    }

    /**
     * Test define_sql() method.
     * @covers \local_wunderbyte_table\filters\types\standardfilter::non_kestringy_value_pair_properties
     */
    public function test_non_kestringy_value_pair_properties(): void {
        $result = standardfilter::non_kestringy_value_pair_properties('username');
        $this->assertCount(3, $result);
    }

    /**
     * Test define_sql() method.
     * @covers \local_wunderbyte_table\filters\types\standardfilter::get_new_filter_values
     */
    public function test_get_new_filter_values(): void {
        $data = (object) [
            'localizedname' => 'datalocalizedname',
            'username_wb_checked' => 'datafilterenablelabel',
            'keyvaluepairs' => [
                'one' => [
                    'key' => 'one',
                    'value' => 'value',
                ],
            ],

        ];
        $filtercolumn = 'username';

        $result = standardfilter::get_new_filter_values($data, $filtercolumn);
        $this->assertCount(4, $result);
        $this->assertArrayHasKey('one', $result);
    }

    /**
     * Test define_sql() method.
     * @covers \local_wunderbyte_table\filters\types\standardfilter::get_filterspecific_values
     */
    public function test_get_filterspecific_values(): void {
        $data = [
            'localizedname' => 'datalocalizedname',
            'username_wb_checked' => 'datafilterenablelabel',
            'one' => 'onevalue',
            'two' => 'twovalue',
        ];
        $filtercolumn = 'username';

        [$result, $filterspecific] = standardfilter::get_filterspecific_values($data, $filtercolumn);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('one', $result);
    }


    /**
     * Test define_sql() method.
     * @covers \local_wunderbyte_table\filters\types\standardfilter::render_mandatory_fields
     * @covers \local_wunderbyte_table\filters\types\standardfilter::generate_delete_button
     */
    public function test_render_mandatory_fields(): void {
        $mformmock = $this->createMock(\MoodleQuickForm::class);
        $mformmock->expects($this->exactly(6))
            ->method('createElement')
            ->withConsecutive(
                ['text', 'keyvaluepairs[one][key]', '', ['placeholder' => 'Key']],
                ['text', 'keyvaluepairs[one][value]', '', ['placeholder' => 'Value']],
                ['button', 'remove[one_group]', '<i class="fa fa-trash"></i>', [
                    'class' => 'btn remove-key-value',
                    'type' => 'button',
                    'data-groupid' => 'one_group',
                    'aria-label' => 'Remove key-value pair for one',
                ]],
                ['text', 'keyvaluepairs[two][key]', '', ['placeholder' => 'Key']],
                ['text', 'keyvaluepairs[two][value]', '', ['placeholder' => 'Value']],
                ['button', 'remove[two_group]', '<i class="fa fa-trash"></i>', [
                    'class' => 'btn remove-key-value',
                    'type' => 'button',
                    'data-groupid' => 'two_group',
                    'aria-label' => 'Remove key-value pair for two',
                ]]
            );

        $mformmock->expects($this->exactly(4))
            ->method('setDefault')
            ->withConsecutive(
                ['keyvaluepairs[one][key]', 'one'],
                ['keyvaluepairs[one][value]', 'one_value'],
                ['keyvaluepairs[two][key]', 'two'],
                ['keyvaluepairs[two][value]', 'two_value']
            );

        $data = [
            'one' => [
                'key' => 'one',
                'value' => 'one_value',
            ],
            'two' => [
                'key' => 'two',
                'value' => 'two_value',
            ],
            '0' => [
                'key' => 'new value',
                'value' => 'new_value',
            ],
        ];

        standardfilter::render_mandatory_fields($mformmock, $data);
    }
}
