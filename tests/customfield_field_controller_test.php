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

namespace local_wunderbyte_table;

use advanced_testcase;
use local_wunderbyte_table\local\customfield\field\text\wbt_field_controller as text_controller;
use local_wunderbyte_table\local\customfield\field\textarea\wbt_field_controller as textarea_controller;

/**
 * Tests for the customfield field controllers.
 *
 * @package    local_wunderbyte_table
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_wunderbyte_table\local\customfield\field\text\wbt_field_controller
 * @covers     \local_wunderbyte_table\local\customfield\field\textarea\wbt_field_controller
 */
final class customfield_field_controller_test extends advanced_testcase {
    /**
     * A multiselect customfield resolves to an array of keys. The text controller must render
     * each element joined by commas instead of passing the array into format_string(), which
     * would throw a TypeError (the crash seen on the booking options table in Behat).
     *
     * @return void
     */
    public function test_text_controller_handles_array_value(): void {
        $this->resetAfterTest();

        $controller = new text_controller();

        // The regression: an array value used to throw a TypeError from format_string().
        $this->assertSame('foo, bar', $controller->get_option_value_by_key(['foo', 'bar']));

        // Empty members are dropped, not rendered as stray commas.
        $this->assertSame('foo', $controller->get_option_value_by_key(['foo', '']));

        // An empty array yields an empty string, not a TypeError.
        $this->assertSame('', $controller->get_option_value_by_key([]));

        // Scalar behaviour is unchanged.
        $this->assertSame('foo', $controller->get_option_value_by_key('foo'));
    }

    /**
     * The textarea controller shares the same array guard (it formats via format_text()).
     *
     * @return void
     */
    public function test_textarea_controller_handles_array_value(): void {
        $this->resetAfterTest();

        $controller = new textarea_controller();

        // The format_text() call wraps content; assert each element survived and the join happened.
        $rendered = $controller->get_option_value_by_key(['foo', 'bar']);
        $this->assertStringContainsString('foo', $rendered);
        $this->assertStringContainsString('bar', $rendered);
        $this->assertStringContainsString(', ', $rendered);

        $this->assertSame('', $controller->get_option_value_by_key([]));
    }
}
