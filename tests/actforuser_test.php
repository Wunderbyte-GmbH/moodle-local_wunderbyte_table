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
 * Tests for booking option events.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @author 2025 Wunderbyte GmbH <info@wunderbyte.at> Georg Maißer
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table;

use advanced_testcase;
use local_wunderbyte_table\local\helper\actforuser;

/**
 * Test for actforuser class.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @author 2025 Mahdi Poustini
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class actforuser_test extends advanced_testcase {
    /**
     * Tests set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test for get_userid_from_foruserid_arg() function.
     * @dataProvider user_id_provider
     * @covers \local_wunderbyte_table\local\helper\actforuser::get_userid_from_foruserid_arg
     * @param int $provideduserid
     * @param int $expected
     * @return void
     */
    public function test_get_userid_from_foruserid_arg($provideduserid, $expected): void {
        $args = [
            'foruserid' => $provideduserid,
        ];

        $userid = actforuser::get_userid_from_foruserid_arg($args);
        $this->assertSame($expected, $userid);
    }

    /**
     * Test for get_userid_from_urlparamforuserid() function.
     * @dataProvider user_id_provider
     * @covers \local_wunderbyte_table\local\helper\actforuser::get_userid_from_urlparamforuserid
     * @param int $provideduserid
     * @param int $expected
     * @return void
     */
    public function test_get_userid_from_urlparamforuserid($provideduserid, $expected): void {
        unset($_GET['myforuserid']);
        $args = [
            'urlparamforuserid' => 'myforuserid',
        ];

        // When there is no URL parameter. It should return 0 for any $provideduserid.
        $userid = actforuser::get_userid_from_urlparamforuserid($args);
        $this->assertSame(0, $userid);

        // When there is URL parameter. It should return $expected for any $provideduserid.
        $_GET['myforuserid'] = $provideduserid;
        $userid = actforuser::get_userid_from_urlparamforuserid($args);
        $this->assertSame($expected, $userid);
    }

    /**
     * Test for get_foruserid() function.
     * @dataProvider user_id_provider
     * @covers \local_wunderbyte_table\local\helper\actforuser::get_foruserid
     * @param int $provideduserid
     * @param int $expected
     * @return void
     */
    public function test_get_foruserid($provideduserid, $expected): void {
        unset($_GET['myforuserid']);
        // Scenario 1:
        // We set both arguments, but we do not send the URL parameter.
        // Since the foruserid argument has higher priority, we expect $expected as the returned value.
        $args = [
            'foruserid' => $provideduserid,
            'urlparamforuserid' => 'myforuserid',
        ];

        // When there is no URL parameter. It should return 0 for any $provideduserid.
        $userid = actforuser::get_foruserid($args);
        $this->assertSame($expected, $userid);

        // Scenario 2:
        // We set both argument and we send the url param with a starnge value in url.
        // Since the foruserid argument has higher priority,
        // we expect $expected as the returned value when $provideduserid is valid.
        $args = [
            'foruserid' => $provideduserid,
            'urlparamforuserid' => 'myforuserid',
        ];
        $_GET['myforuserid'] = 123456;
        // When there is no URL parameter. It should return 0 for any $provideduserid.
        $userid = actforuser::get_foruserid($args);
        if (is_int($args['foruserid']) && $args['foruserid'] > 0) {
            $this->assertSame($expected, $userid);
        } else {
            $this->assertSame(123456, $userid);
        }

        // Scenario 3:
        // We set only urlparamforuserid argiment and we sent url params.
        $args = [
            'urlparamforuserid' => 'myforuserid',
        ];
        // When there is URL parameter. It should return $expected for any $provideduserid.
        $_GET['myforuserid'] = $provideduserid;
        $userid = actforuser::get_foruserid($args);
        $this->assertSame($expected, $userid);
    }

    /**
     *
     * User id provider.
     * @return array
     */
    public static function user_id_provider(): array {
        return [
            'User ID: 0' => [
                'userid' => 0,
                'expected' => 0,
            ],
            'User ID: 10' => [
                'userid' => 10,
                'expected' => 10,
            ],
            'User ID: null' => [
                'userid' => null,
                'expected' => 0,
            ],
            'User ID: abc' => [
                'userid' => 'abc',
                'expected' => 0,
            ],
        ];
    }
}
