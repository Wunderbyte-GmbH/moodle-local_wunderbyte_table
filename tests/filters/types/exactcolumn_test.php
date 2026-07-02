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
 * @copyright 2026 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\filters\types;

use local_wunderbyte_table\wunderbyte_table;
use moodle_exception;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for exactcolumn class.
 */
final class exactcolumn_test extends TestCase {
    /**
     * Test add_filter() method.
     * @covers \local_wunderbyte_table\filters\types\exactcolumn::add_filter
     */
    public function test_add_filter(): void {
        $filter = [];
        $exactcolumn = new exactcolumn('testcolumn', 'Test Label');
        $exactcolumn->add_filter($filter);

        $this->assertArrayHasKey('testcolumn', $filter);
        $this->assertSame('Test Label', $filter['testcolumn']['localizedname']);
        $this->assertTrue($filter['testcolumn'][exactcolumn::class]);
        $this->assertSame(1, $filter['testcolumn']['testcolumn_wb_checked']);
        $this->assertSame(exactcolumn::class, $filter['testcolumn']['wbfilterclass']);
    }

    /**
     * Test that add_filter() throws when the column already has a filter.
     * @covers \local_wunderbyte_table\filters\types\exactcolumn::add_filter
     */
    public function test_add_filter_throws_on_identifier_conflict(): void {
        $filter = [];
        $exactcolumn = new exactcolumn('testcolumn', 'Test Label');
        $exactcolumn->add_filter($filter);

        $this->expectException(moodle_exception::class);
        $exactcolumn->add_filter($filter);
    }

    /**
     * Test add_to_categoryobject() method.
     * @covers \local_wunderbyte_table\filters\types\exactcolumn::add_to_categoryobject
     */
    public function test_add_to_categoryobject(): void {
        $categoryobject = [];
        $filtersettings = [
            'testkey' => [
                exactcolumn::class => true,
            ],
        ];

        exactcolumn::add_to_categoryobject($categoryobject, $filtersettings, 'testkey', []);

        $this->assertArrayHasKey('exactcolumn', $categoryobject);
        $this->assertTrue($categoryobject['exactcolumn']);
    }

    /**
     * Test add_to_categoryobject() method without matching filtersettings.
     * @covers \local_wunderbyte_table\filters\types\exactcolumn::add_to_categoryobject
     */
    public function test_add_to_categoryobject_without_settings(): void {
        $categoryobject = [];
        $filtersettings = [
            'testkey' => [],
        ];

        exactcolumn::add_to_categoryobject($categoryobject, $filtersettings, 'testkey', []);

        $this->assertArrayNotHasKey('exactcolumn', $categoryobject);
    }

    /**
     * Test apply_filter() method with a valid categoryvalue.
     * @covers \local_wunderbyte_table\filters\types\exactcolumn::apply_filter
     */
    public function test_apply_filter_with_valid_categoryvalue(): void {
        $filter = '';
        $passedvalues = [];
        $table = $this->createMock(wunderbyte_table::class);
        $table->expects($this->once())
            ->method('set_params')
            ->willReturnCallback(function ($value) use (&$passedvalues) {
                $passedvalues[] = $value;
                return 'param1';
            });

        $exactcolumn = new exactcolumn('testcolumn', 'Test Label');
        $exactcolumn->apply_filter($filter, 'testcolumn', 'MyValue', $table);

        $this->assertSame('testcolumn = :param1', $filter);
        // The categoryvalue is lowercased before it is bound as sql param.
        $this->assertSame(['myvalue'], $passedvalues);
    }

    /**
     * Test apply_filter() method with an empty categoryvalue.
     * @covers \local_wunderbyte_table\filters\types\exactcolumn::apply_filter
     */
    public function test_apply_filter_with_empty_categoryvalue(): void {
        $filter = '';
        $table = $this->createMock(wunderbyte_table::class);
        $table->expects($this->never())
            ->method('set_params');

        $exactcolumn = new exactcolumn('testcolumn', 'Test Label');
        $exactcolumn->apply_filter($filter, 'testcolumn', '', $table);

        $this->assertSame('', $filter);
    }

    /**
     * Test apply_filter() method with a non-string categoryvalue.
     * @covers \local_wunderbyte_table\filters\types\exactcolumn::apply_filter
     */
    public function test_apply_filter_with_nonstring_categoryvalue(): void {
        $filter = '';
        $table = $this->createMock(wunderbyte_table::class);
        $table->expects($this->never())
            ->method('set_params');

        $exactcolumn = new exactcolumn('testcolumn', 'Test Label');
        $exactcolumn->apply_filter($filter, 'testcolumn', 123, $table);
        $exactcolumn->apply_filter($filter, 'testcolumn', ['MyValue'], $table);

        $this->assertSame('', $filter);
    }
}
