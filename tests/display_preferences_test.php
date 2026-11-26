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
 * Tests for the display choices of a user: template and number of rows.
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
use moodle_url;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/template_layout_table.php');

/**
 * The definition of a table is cached once and shared by everybody who loads the table.
 * The template and the number of rows a user chooses are personal: they apply to this
 * user on every request and never change what other users get.
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
final class display_preferences_test extends advanced_testcase {
    /** @var int Number of courses the tests create, the site course comes on top. */
    private const COURSES = 12;

    /**
     * Tests set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
        for ($i = 0; $i < self::COURSES; $i++) {
            $this->getDataGenerator()->create_course();
        }
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
     * Build the test table with a list and a cards template. It is not cached yet.
     *
     * @return template_layout_table
     */
    private function build_table(): template_layout_table {
        $table = new template_layout_table('displaypreferencestest');
        $columns = [
            'id' => 'id',
            'fullname' => 'fullname',
        ];
        $table->define_headers(array_values($columns));
        $table->define_columns(array_keys($columns));
        $table->set_filter_sql('*', '{course}', '1=1', '');
        $table->define_baseurl(new moodle_url('/local/wunderbyte_table/download.php'));
        $table->add_template_to_switcher(template_layout_table::LIST, 'List', true);
        $table->add_template_to_switcher(template_layout_table::CARDS, 'Cards');
        $table->tabletemplate = template_layout_table::LIST;
        $table->showrowcountselect = true;
        $table->use_pages = true;
        return $table;
    }

    /**
     * Load the table through the data webservice as the current user.
     *
     * @param string $hash
     * @return array with the keys template and rows
     */
    private function load(string $hash): array {
        $result = load_data::execute($hash, 0, '', '', '', 0, 0, '', '');
        $content = json_decode($result['content'], true);
        return [
            'template' => $result['template'],
            'rows' => $content['table']['rows'] ?? [],
        ];
    }

    /**
     * Switch the template as the current user.
     *
     * @param string $hash
     * @param string $template
     * @return array
     */
    private function switch_template(string $hash, string $template): array {
        return execute_action::execute('switchtemplates', $hash, 0, json_encode(['selectedValue' => $template . ' 0']));
    }

    /**
     * The template one user chooses applies to this user only, with its own column layout.
     */
    public function test_template_choice_of_a_user_does_not_change_the_table_of_others(): void {
        $usera = $this->getDataGenerator()->create_user();
        $userb = $this->getDataGenerator()->create_user();
        $hash = $this->build_table()->return_encoded_table();

        $this->setUser($usera);
        $this->assertSame(1, $this->switch_template($hash, template_layout_table::CARDS)['success']);

        // The choice holds for every later request of this user.
        $loaded = $this->load($hash);
        $this->assertSame(template_layout_table::CARDS, $loaded['template']);
        $this->assertArrayHasKey('cardbody', $loaded['rows'][0]);
        $this->assertArrayNotHasKey('leftside', $loaded['rows'][0]);

        // Another user with the same table keeps the default template and its layout.
        $this->setUser($userb);
        $loaded = $this->load($hash);
        $this->assertSame(template_layout_table::LIST, $loaded['template']);
        $this->assertArrayHasKey('leftside', $loaded['rows'][0]);
        $this->assertArrayNotHasKey('cardbody', $loaded['rows'][0]);

        // And the first user still gets the cards afterwards.
        $this->setUser($usera);
        $this->assertSame(template_layout_table::CARDS, $this->load($hash)['template']);
    }

    /**
     * Only the templates the table offers in its switcher can be chosen.
     */
    public function test_template_that_is_not_offered_is_rejected(): void {
        $user = $this->getDataGenerator()->create_user();
        $hash = $this->build_table()->return_encoded_table();

        $this->setUser($user);
        // The template exists in the plugin, but this table does not offer it.
        $result = $this->switch_template($hash, 'local_wunderbyte_table/demo');

        $this->assertSame(0, $result['success']);
        $this->assertSame(template_layout_table::LIST, $this->load($hash)['template']);
    }

    /**
     * The number of rows one user chooses applies to this user only.
     */
    public function test_row_number_choice_of_a_user_does_not_change_the_table_of_others(): void {
        $usera = $this->getDataGenerator()->create_user();
        $userb = $this->getDataGenerator()->create_user();
        $hash = $this->build_table()->return_encoded_table();

        $this->setUser($userb);
        $rowsbefore = count($this->load($hash)['rows']);
        $this->assertGreaterThan(5, $rowsbefore);

        $this->setUser($usera);
        $result = execute_action::execute('rownumberperpage', $hash, 0, json_encode(['numberofrowsselect' => 5]));
        $this->assertSame(1, $result['success']);
        $this->assertCount(5, $this->load($hash)['rows']);

        $this->setUser($userb);
        $this->assertCount($rowsbefore, $this->load($hash)['rows']);
    }

    /**
     * The number of rows a user chose is still applied when the page builds the table again.
     */
    public function test_row_number_choice_survives_a_new_page_request(): void {
        global $PAGE;

        $user = $this->getDataGenerator()->create_user();
        $hash = $this->build_table()->return_encoded_table();

        $this->setUser($user);
        execute_action::execute('rownumberperpage', $hash, 0, json_encode(['numberofrowsselect' => 5]));

        // A page request builds the table from scratch and asks for its usual number of rows.
        $table = $this->build_table();
        $tableobject = $table->printtable(20, true);
        $exported = $tableobject->export_for_template($PAGE->get_renderer('local_wunderbyte_table'));
        $rows = $exported['table']['rows'] ?? [];

        $this->assertCount(5, $rows);
    }
}
