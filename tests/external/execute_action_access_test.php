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
 * Tests for the access checks of the webservice that executes table actions.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\external;

use advanced_testcase;
use cache_helper;
use context;
use context_course;
use context_system;
use local_wunderbyte_table\demo_table;
use moodle_url;
use required_capability_exception;

/**
 * Table actions are authorised like the data of the table: with the capability of the
 * table in the context of the table. Tables that are available without login only offer
 * their public actions to visitors who are not logged in.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_wunderbyte_table\external\execute_action::execute
 *
 * @runInSeparateProcess
 * @runTestsInSeparateProcesses
 */
final class execute_action_access_test extends advanced_testcase {
    /** @var string A capability that regular users do not have. */
    private const CAPABILITY = 'local/wunderbyte_table:canedittable';

    /**
     * Tests set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Mandatory clean-up after each test.
     */
    public function tearDown(): void {
        parent::tearDown();
        cache_helper::purge_by_event('changesinwunderbytetable');
        $_POST = [];
    }

    /**
     * Build a demo table in the given context, cache it and return its cache hash.
     *
     * @param context $context
     * @param bool $requirelogin
     * @param string $capability
     * @return string
     */
    private function return_cached_table_hash(context $context, bool $requirelogin, string $capability = ''): string {
        $table = new demo_table('executeactionaccesstest');
        $table->context = $context;
        $table->requirelogin = $requirelogin;
        if ($capability !== '') {
            $table->requirecapability = $capability;
        }
        $columns = [
            'id' => 'id',
            'fullname' => 'fullname',
        ];
        $table->define_headers(array_values($columns));
        $table->define_columns(array_keys($columns));
        $table->set_filter_sql('*', '{course}', '1=1', '');
        $table->define_baseurl(new moodle_url('/local/wunderbyte_table/download.php'));

        return $table->return_encoded_table();
    }

    /**
     * Create a user who holds the test capability in the given course only.
     *
     * @param \stdClass $course
     * @return \stdClass
     */
    private function create_user_with_capability_in_course(\stdClass $course): \stdClass {
        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability(self::CAPABILITY, CAP_ALLOW, $roleid, context_system::instance());
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $roleid);
        return $user;
    }

    /**
     * A user who holds the capability of the table in the context of the table can execute
     * its actions, also when the capability is missing on system level.
     */
    public function test_capability_in_table_context_is_sufficient(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $hash = $this->return_cached_table_hash(context_course::instance($course->id), true, self::CAPABILITY);

        $user = $this->create_user_with_capability_in_course($course);
        $this->setUser($user);
        $this->assertFalse(has_capability(self::CAPABILITY, context_system::instance()));

        $result = execute_action::execute('deleteitem', $hash, 0, '{}');

        $this->assertSame(1, $result['success']);
    }

    /**
     * A user without the capability of the table in the context of the table is rejected.
     */
    public function test_missing_capability_in_table_context_is_rejected(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();
        $hash = $this->return_cached_table_hash(context_course::instance($course->id), true, self::CAPABILITY);

        // The capability in another course does not help.
        $user = $this->create_user_with_capability_in_course($othercourse);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $this->setUser($user);

        $this->assertFalse(has_capability(self::CAPABILITY, context_course::instance($course->id)));

        $this->expectException(required_capability_exception::class);
        execute_action::execute('deleteitem', $hash, 0, '{}');
    }

    /**
     * A table that is available without login offers only its public actions to visitors
     * who are not logged in.
     *
     * @dataProvider public_table_provider
     *
     * @param bool $loggedin
     * @param string $methodname
     * @param string $data
     * @param int $expectedsuccess
     * @return void
     */
    public function test_actions_of_a_table_without_login(
        bool $loggedin,
        string $methodname,
        string $data,
        int $expectedsuccess
    ): void {
        $this->setAdminUser();
        $hash = $this->return_cached_table_hash(context_system::instance(), false);

        if ($loggedin) {
            $this->setUser($this->getDataGenerator()->create_user());
        } else {
            $this->setUser(null);
        }

        $result = execute_action::execute($methodname, $hash, 0, $data);

        $this->assertSame($expectedsuccess, $result['success']);
    }

    /**
     * Visitors and users on a table without login.
     *
     * @return array
     */
    public static function public_table_provider(): array {
        return [
            'visitor may use a public action' => [
                'loggedin' => false,
                'methodname' => 'rownumberperpage',
                'data' => '{"numberofrowsselect":20}',
                'expectedsuccess' => 1,
            ],
            'visitor may not use another action' => [
                'loggedin' => false,
                'methodname' => 'deleteitem',
                'data' => '{}',
                'expectedsuccess' => 0,
            ],
            'logged in user may still use another action' => [
                'loggedin' => true,
                'methodname' => 'deleteitem',
                'data' => '{}',
                'expectedsuccess' => 1,
            ],
        ];
    }
}
