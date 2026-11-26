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
 * Tests for the access declaration of tables.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table;

use advanced_testcase;
use cache_helper;
use coding_exception;
use context_system;
use local_wunderbyte_table\external\execute_action;
use local_wunderbyte_table\external\load_data;
use moodle_url;
use required_capability_exception;

/**
 * A table is cached once for everybody and loaded by its hash. Who may load it is
 * therefore declared on the table and checked for the current user on every request.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_wunderbyte_table\wunderbyte_table
 *
 * @runInSeparateProcess
 * @runTestsInSeparateProcesses
 */
final class access_declaration_test extends advanced_testcase {
    /**
     * Tests set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
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
     * Access callback used by the tests: only the user named in the arguments may load the table.
     *
     * @param wunderbyte_table $table
     * @param array $args
     * @return bool
     */
    public static function allow_only_declared_user(wunderbyte_table $table, array $args): bool {
        global $USER;
        return (int)$USER->id === (int)($args['userid'] ?? 0);
    }

    /**
     * Build a demo table on the course table. It is not cached yet.
     *
     * @return demo_table
     */
    private function build_table(): demo_table {
        $table = new demo_table('accessdeclarationtest');
        $columns = [
            'id' => 'id',
            'fullname' => 'fullname',
        ];
        $table->define_headers(array_values($columns));
        $table->define_columns(array_keys($columns));
        $table->set_filter_sql('*', '{course}', '1=1', '');
        $table->define_baseurl(new moodle_url('/local/wunderbyte_table/download.php'));
        return $table;
    }

    /**
     * Number of rows the data webservice returns for the current user.
     *
     * @param string $hash
     * @return int
     */
    private function count_loaded_rows(string $hash): int {
        $result = load_data::execute($hash, 0, '', '', '', 0, 0, '', '');
        $content = json_decode($result['content'], true);
        return count($content['table']['rows'] ?? []);
    }

    /**
     * A table with an own access check is only delivered to users who pass the check,
     * and the same check protects its actions.
     */
    public function test_access_callback_is_checked_for_the_current_user(): void {
        $this->getDataGenerator()->create_course();
        $owner = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();

        $table = $this->build_table();
        $table->declare_access_by_callback(
            [self::class, 'allow_only_declared_user'],
            ['userid' => $owner->id],
            context_system::instance()
        );
        $hash = $table->return_encoded_table();

        $this->setUser($owner);
        $this->assertGreaterThan(0, $this->count_loaded_rows($hash));
        $this->assertSame(1, execute_action::execute('deleteitem', $hash, 0, '{}')['success']);

        $this->setUser($other);
        $this->assertSame(0, $this->count_loaded_rows($hash));
        $this->expectException(required_capability_exception::class);
        execute_action::execute('deleteitem', $hash, 0, '{}');
    }

    /**
     * Tables that only differ in who may load them never share a cache entry.
     */
    public function test_access_rule_is_part_of_the_table_hash(): void {
        $default = $this->build_table();
        $defaulthash = $default->return_encoded_table();

        $public = $this->build_table();
        $public->declare_public_access();
        $publichash = $public->return_encoded_table();

        $restricted = $this->build_table();
        $restricted->declare_access_by_callback([self::class, 'allow_only_declared_user'], ['userid' => 2]);
        $restrictedhash = $restricted->return_encoded_table();

        $this->assertCount(3, array_unique([$defaulthash, $publichash, $restrictedhash]));

        // The cached objects carry their own rule.
        $this->assertFalse(wunderbyte_table::instantiate_from_tablecache_hash($publichash)->requirelogin);
        $this->assertTrue(wunderbyte_table::instantiate_from_tablecache_hash($defaulthash)->requirelogin);
    }

    /**
     * The key of a cached query result is a full length hash of the query.
     */
    public function test_query_cache_key_is_a_full_length_hash(): void {
        $table = $this->build_table();
        $table->return_encoded_table();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}_sqlquery$/', $table->create_cachekey(true));
    }

    /**
     * Only tables on the pure defaults count as undeclared.
     */
    public function test_tables_know_whether_their_access_was_declared(): void {
        $this->assertFalse($this->build_table()->has_access_declaration());

        $table = $this->build_table();
        $table->requirelogin = false;
        $this->assertTrue($table->has_access_declaration());

        $table = $this->build_table();
        $table->requirecapability = 'local/wunderbyte_table:canedittable';
        $this->assertTrue($table->has_access_declaration());

        $table = $this->build_table();
        $table->declare_access_for_logged_in_users();
        $this->assertTrue($table->has_access_declaration());
        $this->assertTrue($table->requirelogin);

        $table = $this->build_table();
        $table->declare_access_by_capability('local/wunderbyte_table:canedittable', context_system::instance());
        $this->assertTrue($table->has_access_declaration());
        $this->assertSame('local/wunderbyte_table:canedittable', $table->requirecapability);
    }

    /**
     * The access check is cached with the table, so only static methods are accepted.
     */
    public function test_access_callback_has_to_be_a_static_method(): void {
        $table = $this->build_table();

        $this->expectException(coding_exception::class);
        $table->declare_access_by_callback([$this, 'build_table']);
    }
}
