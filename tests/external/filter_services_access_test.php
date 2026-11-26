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
 * Tests for the access checks of the webservices behind the filter editor.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\external;

use advanced_testcase;
use cache_helper;
use local_wunderbyte_table\demo_table;
use local_wunderbyte_table\filters\types\standardfilter;
use moodle_url;
use required_capability_exception;

/**
 * The filter editor is only available to users who may edit tables. The webservices
 * it uses have to enforce the same capability, because they can be called directly.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_wunderbyte_table\external\get_filter_fields::execute
 * @covers \local_wunderbyte_table\external\get_filter_specific_fields::execute
 * @covers \local_wunderbyte_table\external\get_filter_column_data::execute
 *
 * @runInSeparateProcess
 * @runTestsInSeparateProcesses
 */
final class filter_services_access_test extends advanced_testcase {
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
     * Build a demo table with one filter, cache it and return its cache hash.
     *
     * @return string
     */
    private function return_cached_table_hash(): string {
        $this->getDataGenerator()->create_course();

        $table = new demo_table('filterservicesaccesstest');
        $columns = [
            'id' => 'id',
            'fullname' => 'fullname',
        ];
        $table->define_headers(array_values($columns));
        $table->define_columns(array_keys($columns));
        $table->add_filter(new standardfilter('fullname', 'fullname'));
        $table->set_filter_sql('*', '{course}', '1=1', '');
        $table->define_baseurl(new moodle_url('/local/wunderbyte_table/download.php'));

        return $table->return_encoded_table();
    }

    /**
     * Call one of the three services by name.
     *
     * @param string $service
     * @param string $hash
     * @return array
     */
    private function call_service(string $service, string $hash): array {
        switch ($service) {
            case 'get_filter_fields':
                return get_filter_fields::execute(standardfilter::class);
            case 'get_filter_specific_fields':
                return get_filter_specific_fields::execute('standard');
            default:
                return get_filter_column_data::execute('fullname', $hash);
        }
    }

    /**
     * A user who may use tables but not edit them is rejected by every service.
     *
     * @dataProvider service_provider
     *
     * @param string $service
     * @param array $expectedkeys not used here, the provider is shared with the positive test
     * @return void
     */
    public function test_user_without_edit_capability_is_rejected(string $service, array $expectedkeys): void {
        $this->setAdminUser();
        $hash = $this->return_cached_table_hash();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $this->expectException(required_capability_exception::class);
        $this->call_service($service, $hash);
    }

    /**
     * A user who may edit tables still gets the form fragments.
     *
     * @dataProvider service_provider
     *
     * @param string $service
     * @param array $expectedkeys
     * @return void
     */
    public function test_user_with_edit_capability_gets_the_fields(string $service, array $expectedkeys): void {
        $this->setAdminUser();
        $hash = $this->return_cached_table_hash();

        $result = $this->call_service($service, $hash);
        // Building the list of filter types reports filter types without a language string.
        // That is unrelated to the access check tested here.
        $this->resetDebugging();

        foreach ($expectedkeys as $key) {
            $this->assertArrayHasKey($key, $result);
        }
    }

    /**
     * The three services of the filter editor.
     *
     * @return array
     */
    public static function service_provider(): array {
        return [
            'fields of a filter type' => [
                'service' => 'get_filter_fields',
                'expectedkeys' => ['filteraddfields'],
            ],
            'fields of a specific filter type' => [
                'service' => 'get_filter_specific_fields',
                'expectedkeys' => ['filteraddfields'],
            ],
            'settings of a filter column' => [
                'service' => 'get_filter_column_data',
                'expectedkeys' => ['filtereditfields', 'filteraddfields'],
            ],
        ];
    }
}
