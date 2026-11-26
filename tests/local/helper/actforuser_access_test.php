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

namespace local_wunderbyte_table\local\helper;

use advanced_testcase;
use context_system;
use stdClass;

/**
 * Tests for the authorisation of acting for another user.
 *
 * The id of another user can come from the URL. A caller that names the capabilities for acting
 * for others gets the id only when the current user holds one of them; everybody else acts for
 * themselves. Callers that name nothing keep the old behaviour for now and are counted.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_wunderbyte_table\local\helper\actforuser::get_foruserid
 */
final class actforuser_access_test extends advanced_testcase {
    /** @var string A capability regular users do not have. */
    private const CAPABILITY = 'local/wunderbyte_table:canedittable';

    /** @var string Another capability regular users do not have. */
    private const OTHERCAPABILITY = 'moodle/site:config';

    /** @var stdClass The user whose id is put into the URL. */
    private stdClass $otheruser;

    /**
     * Tests set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->otheruser = $this->getDataGenerator()->create_user();
        unset($_GET['userid'], $_GET['myforuserid']);
        actforuser::$unverifiedforeignids = 0;
    }

    /**
     * Mandatory clean-up after each test.
     */
    public function tearDown(): void {
        unset($_GET['userid'], $_GET['myforuserid']);
        parent::tearDown();
    }

    /**
     * Log in as a new user, optionally with the test capability on system level.
     *
     * @param bool $withcapability
     * @return stdClass
     */
    private function login(bool $withcapability): stdClass {
        $user = $this->getDataGenerator()->create_user();
        if ($withcapability) {
            $roleid = $this->getDataGenerator()->create_role();
            assign_capability(self::CAPABILITY, CAP_ALLOW, $roleid, context_system::instance());
            role_assign($roleid, $user->id, context_system::instance());
        }
        $this->setUser($user);
        return $user;
    }

    /**
     * The two ways a user id can come from the URL: a parameter named by the caller,
     * and the parameter userid that is used when the caller names none.
     *
     * @return array
     */
    public static function url_provider(): array {
        return [
            'named url parameter' => [
                'args' => ['urlparamforuserid' => 'myforuserid'],
                'urlparam' => 'myforuserid',
            ],
            'implicit url parameter userid' => [
                'args' => [],
                'urlparam' => 'userid',
            ],
        ];
    }

    /**
     * Without the capability the id of another user is ignored and the default applies.
     *
     * @dataProvider url_provider
     *
     * @param array $args
     * @param string $urlparam
     * @return void
     */
    public function test_other_user_from_url_is_ignored_without_capability(array $args, string $urlparam): void {
        $user = $this->login(false);
        $_GET[$urlparam] = $this->otheruser->id;

        $this->assertSame((int)$user->id, actforuser::get_foruserid($args, (int)$user->id, [self::CAPABILITY]));
        $this->assertSame(0, actforuser::get_foruserid($args, 0, [self::CAPABILITY]));
        $this->assertSame(0, actforuser::$unverifiedforeignids);
    }

    /**
     * A caller that names an empty list of capabilities does not support acting for others at all.
     *
     * @dataProvider url_provider
     *
     * @param array $args
     * @param string $urlparam
     * @return void
     */
    public function test_other_user_from_url_is_ignored_with_an_empty_capability_list(array $args, string $urlparam): void {
        $this->setAdminUser();
        $_GET[$urlparam] = $this->otheruser->id;

        $this->assertSame(0, actforuser::get_foruserid($args, 0, []));
        $this->assertSame(0, actforuser::$unverifiedforeignids);
    }

    /**
     * A caller that names no capabilities at all still gets the id, as before. Every such case
     * is counted, so the callers that have to be changed can be found.
     *
     * @dataProvider url_provider
     *
     * @param array $args
     * @param string $urlparam
     * @return void
     */
    public function test_caller_without_capabilities_keeps_the_old_behaviour_and_is_counted(
        array $args,
        string $urlparam
    ): void {
        $user = $this->login(false);
        $_GET[$urlparam] = $this->otheruser->id;

        $this->assertSame((int)$this->otheruser->id, actforuser::get_foruserid($args));
        $this->assertSame(1, actforuser::$unverifiedforeignids);

        // The own id is no acting for somebody else and is not counted.
        $_GET[$urlparam] = $user->id;
        $this->assertSame((int)$user->id, actforuser::get_foruserid($args));
        $this->assertSame(1, actforuser::$unverifiedforeignids);
    }

    /**
     * With the capability the id of another user is accepted.
     *
     * @dataProvider url_provider
     *
     * @param array $args
     * @param string $urlparam
     * @return void
     */
    public function test_other_user_from_url_is_accepted_with_capability(array $args, string $urlparam): void {
        $user = $this->login(true);
        $_GET[$urlparam] = $this->otheruser->id;

        $this->assertSame(
            (int)$this->otheruser->id,
            actforuser::get_foruserid($args, (int)$user->id, [self::CAPABILITY])
        );
        // One of several capabilities is enough.
        $this->assertSame(
            (int)$this->otheruser->id,
            actforuser::get_foruserid($args, (int)$user->id, [self::OTHERCAPABILITY, self::CAPABILITY])
        );
        // A capability the user does not hold does not help.
        $this->assertSame(
            (int)$user->id,
            actforuser::get_foruserid($args, (int)$user->id, [self::OTHERCAPABILITY])
        );
        $this->assertSame(0, actforuser::$unverifiedforeignids);
    }

    /**
     * A capability can be named together with the context it is checked in.
     */
    public function test_capability_is_checked_in_the_named_context(): void {
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);
        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability(self::CAPABILITY, CAP_ALLOW, $roleid, context_system::instance());
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $roleid);
        $this->setUser($user);
        $_GET['userid'] = $this->otheruser->id;

        // The user holds the capability in the course only.
        $this->assertSame((int)$user->id, actforuser::get_foruserid([], (int)$user->id, [self::CAPABILITY]));
        $this->assertSame(
            (int)$this->otheruser->id,
            actforuser::get_foruserid([], (int)$user->id, [self::CAPABILITY => $coursecontext])
        );
    }

    /**
     * The own id in the URL needs no capability.
     *
     * @dataProvider url_provider
     *
     * @param array $args
     * @param string $urlparam
     * @return void
     */
    public function test_own_id_from_url_is_always_accepted(array $args, string $urlparam): void {
        $user = $this->login(false);
        $_GET[$urlparam] = $user->id;

        $this->assertSame((int)$user->id, actforuser::get_foruserid($args, 0, [self::CAPABILITY]));
        $this->assertSame((int)$user->id, actforuser::get_foruserid($args, 0, []));
    }

    /**
     * The id the author of a page writes into the shortcode itself is not taken from the request
     * and is returned as before.
     */
    public function test_user_from_shortcode_argument_is_returned(): void {
        $this->login(false);

        $this->assertSame(
            (int)$this->otheruser->id,
            actforuser::get_foruserid(['foruserid' => $this->otheruser->id])
        );
    }
}
