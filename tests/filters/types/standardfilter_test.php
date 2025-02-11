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
     */
    public function test_define_sql() {
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

    public function test_add_options() {
        $filter = new standardfilter('username');
        $options = ['key1' => 'value1', 'key2' => 'value2'];

        $filter->add_options($options);

        $reflection = new \ReflectionClass($filter);
        $property = $reflection->getProperty('options');
        $property->setAccessible(true);
        $result = $property->getValue($filter);

        $this->assertEquals($options, $result);
    }

    public function test_validate_input() {
        $data = [
            'key' => ['one', ''],
            'value' => ['one', 'two'],
        ];

        $errors = standardfilter::validate_input(data: $data);

        $this->assertArrayHasKey('key', $errors);
        $this->assertArrayHasKey(1, $errors['key']);
        $this->assertArrayHasKey('value', $errors);
        $this->assertArrayHasKey(1, $errors['value']);
    }

    public function test_get_dynamic_values() {
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
}
