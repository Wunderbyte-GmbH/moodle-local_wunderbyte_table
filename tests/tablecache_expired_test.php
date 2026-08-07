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
 * Tests for the behaviour of the webservices when the encoded table cache is purged.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table;

use advanced_testcase;
use cache_helper;
use local_wunderbyte_table\external\execute_action;
use local_wunderbyte_table\external\load_data;
use moodle_exception;
use moodle_url;

/**
 * The encodedtables cache can be purged at any time while pages embedding a
 * table hash stay open (scheduled purges, instance edits, admin cache purge).
 * The webservices must then fail with the dedicated errorcode tablecacheexpired
 * - the JS reacts to exactly this code with an automatic page reload - instead
 * of an unspecific coding error shown to the user.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_wunderbyte_table\external\load_data::execute
 * @covers \local_wunderbyte_table\external\execute_action::execute
 *
 * @runInSeparateProcess
 * @runTestsInSeparateProcesses
 */
final class tablecache_expired_test extends advanced_testcase {
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
     * Build a minimal demo table, cache it and return its cache hash.
     *
     * @return string
     */
    private function return_cached_table_hash(): string {
        $this->getDataGenerator()->create_course();

        $table = new demo_table('tablecacheexpiredtest');
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
     * After the encodedtables cache is purged (the real-world trigger, e.g. via
     * mod_booking or an admin cache purge), load_data must throw the dedicated
     * tablecacheexpired errorcode for the hash still embedded in open pages.
     */
    public function test_load_data_throws_tablecacheexpired_after_purge(): void {
        $hash = $this->return_cached_table_hash();

        // The hash resolves while the cache entry exists.
        $this->assertInstanceOf(wunderbyte_table::class, wunderbyte_table::instantiate_from_tablecache_hash($hash));

        cache_helper::purge_by_event('setbackencodedtables');

        try {
            load_data::execute($hash, 0, '', '', '', 0, 0, '', '');
            $this->fail('Expected moodle_exception was not thrown.');
        } catch (moodle_exception $e) {
            $this->assertSame('tablecacheexpired', $e->errorcode);
        }
    }

    /**
     * The action button webservice uses the same hash and must fail with the
     * same errorcode instead of a fatal on the boolean cache miss.
     */
    public function test_execute_action_throws_tablecacheexpired_after_purge(): void {
        $hash = $this->return_cached_table_hash();

        cache_helper::purge_by_event('setbackencodedtables');

        try {
            execute_action::execute($hash, 'rownumberperpage', 0, '{}');
            $this->fail('Expected moodle_exception was not thrown.');
        } catch (moodle_exception $e) {
            $this->assertSame('tablecacheexpired', $e->errorcode);
        }
    }
}
