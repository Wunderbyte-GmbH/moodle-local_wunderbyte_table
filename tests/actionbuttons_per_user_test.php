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
 * Tests that the action buttons of a table survive ajax reloads per user.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table;

use advanced_testcase;
use cache_helper;
use local_wunderbyte_table\external\load_data;
use moodle_url;

/**
 * The cached table (encodedtables) is shared by all users who see a table with the
 * same sql, but plugins add the action buttons depending on the capabilities of the
 * current user. An ajax reload (paging, rows per page, sorting) must render the
 * buttons of the user who reloads, not those of the user who cached the table first.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_wunderbyte_table\wunderbyte_table::return_encoded_table
 * @covers \local_wunderbyte_table\wunderbyte_table::instantiate_from_tablecache_hash
 *
 * @runInSeparateProcess
 * @runTestsInSeparateProcesses
 */
final class actionbuttons_per_user_test extends advanced_testcase {
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
        cache_helper::purge_by_event('setbackencodedtables');
        $_POST = [];
    }

    /**
     * Build the same table (same sql, same context) for the current user and cache it.
     *
     * @param bool $withdeletebutton what a plugin decides with has_capability()
     * @return string the hash the page hands to the JS
     */
    private function render_table_for_current_user(bool $withdeletebutton): string {
        $table = new demo_table('actionbuttonsperusertest');
        $columns = [
            'id' => 'id',
            'fullname' => 'fullname',
        ];
        $table->define_headers(array_values($columns));
        $table->define_columns(array_keys($columns));
        $table->set_filter_sql('*', '{course}', '1=1', '');
        $table->define_baseurl(new moodle_url('/local/wunderbyte_table/download.php'));

        if ($withdeletebutton) {
            $table->actionbuttons[] = [
                'label' => 'Unsubscribe',
                'class' => 'btn btn-danger',
                'href' => '#',
                'methodname' => 'deleteitem',
                'id' => -1,
                'data' => [
                    'id' => 'id',
                    'component' => 'local_wunderbyte_table',
                ],
            ];
        }

        return $table->return_encoded_table();
    }

    /**
     * A user without the button opens the table first, then a user with the button.
     * The reload of the second user must keep the button, the reload of the first
     * user must not gain it.
     */
    public function test_reload_keeps_actionbuttons_of_current_user(): void {
        $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();

        // The teacher (no delete button) caches the table first.
        $this->setUser($teacher);
        $teacherhash = $this->render_table_for_current_user(false);

        // The manager renders the same table with a delete button.
        $this->setUser($manager);
        $managerhash = $this->render_table_for_current_user(true);

        // Same sql and context: both share one cached table.
        $this->assertSame($teacherhash, $managerhash);

        // Reload as manager (what paging and "show x rows" do).
        $table = wunderbyte_table::instantiate_from_tablecache_hash($managerhash);
        $this->assertCount(1, $table->actionbuttons);
        $this->assertSame('Unsubscribe', $table->actionbuttons[0]['label']);

        // The webservice used by the JS delivers the button, too.
        $this->setAdminUser();
        $this->render_table_for_current_user(true);
        $result = load_data::execute($managerhash, 0, '', '', '', 0, 0, '', '');
        $content = json_decode($result['content']);
        $this->assertSame('Unsubscribe', $content->showactionbuttons[0]->label);

        // A second reload must still work (the buttons are transformed for output on every render).
        $result = load_data::execute($managerhash, 0, '', '', '', 0, 0, '', '');
        $content = json_decode($result['content']);
        $this->assertSame('Unsubscribe', $content->showactionbuttons[0]->label);
        $this->assertSame('id', $content->showactionbuttons[0]->data[0]->key);

        // The teacher still gets no button on reload.
        $this->setUser($teacher);
        $table = wunderbyte_table::instantiate_from_tablecache_hash($teacherhash);
        $this->assertEmpty($table->actionbuttons);
    }

    /**
     * The other direction: the user with the button caches the table first. The user
     * without the button must not get it on reload.
     */
    public function test_reload_does_not_leak_actionbuttons_to_other_users(): void {
        $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $manager = $this->getDataGenerator()->create_user();

        $this->setUser($manager);
        $hash = $this->render_table_for_current_user(true);

        $this->setUser($teacher);
        $this->render_table_for_current_user(false);

        $table = wunderbyte_table::instantiate_from_tablecache_hash($hash);
        $this->assertEmpty($table->actionbuttons);

        $this->setUser($manager);
        $table = wunderbyte_table::instantiate_from_tablecache_hash($hash);
        $this->assertCount(1, $table->actionbuttons);
    }
}
