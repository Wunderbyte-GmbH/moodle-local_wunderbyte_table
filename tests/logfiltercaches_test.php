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
 * Tests for the logfiltercaches debug logging.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table;

use advanced_testcase;

/**
 * Tests for the logfiltercaches debug logging.
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class logfiltercaches_test extends advanced_testcase {
    /**
     * Tests set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
        \cache_helper::purge_all();
    }

    /**
     * Build a minimal demo table over the course table.
     *
     * @param string $uniqueid
     * @return demo_table
     */
    private function build_demo_table(string $uniqueid): demo_table {
        $table = new demo_table($uniqueid);
        $columns = [
            'id' => get_string('id', 'local_wunderbyte_table'),
            'fullname' => get_string('fullname'),
        ];
        $table->define_headers(array_values($columns));
        $table->define_columns(array_keys($columns));
        $table->set_filter_sql('*', "(SELECT * FROM {course} ORDER BY id ASC LIMIT 20) as s1", 'id > 0', '');
        $table->pageable(true);
        $table->pagesize = 10;
        return $table;
    }

    /**
     * Run the given table through the real cached query pipeline.
     *
     * @param demo_table $table
     * @return void
     */
    private function run_table(demo_table $table): void {
        $table->define_baseurl(new \moodle_url('/local/wunderbyte_table/demo.php'));
        ob_start();
        $table->printtable(10, true);
        ob_end_clean();
    }

    /**
     * A cache miss logs one row per hash including the actual SQL, a cache hit
     * increments the counter on that same row, and the unique index prevents
     * duplicate rows for one hash.
     *
     * @covers \local_wunderbyte_table\wunderbyte_table::query_db_cached
     */
    public function test_logfiltercaches_writes_sql_and_counts_hits(): void {
        global $DB;

        set_config('logfiltercaches', 1, 'local_wunderbyte_table');
        $this->setAdminUser();

        for ($i = 0; $i < 3; $i++) {
            $this->getDataGenerator()->create_course();
        }

        // First run: cache miss, the log row is created.
        $this->run_table($this->build_demo_table('logfiltertable_1'));

        $records = $DB->get_records('local_wunderbyte_table');
        $this->assertCount(1, $records, 'the cache miss must create exactly one log row');
        $record = reset($records);
        $this->assertNotEmpty($record->sqlquery, 'the actual SQL must be stored - that is the purpose of the feature');
        $this->assertNotEmpty($record->jsonstring);
        $this->assertNotEmpty($record->hash);
        $this->assertSame(1, (int) $record->count);

        // Second run with a fresh table on the identical SQL: cache hit, the
        // counter increments on the SAME row.
        $this->run_table($this->build_demo_table('logfiltertable_1'));

        $records = $DB->get_records('local_wunderbyte_table');
        $this->assertCount(1, $records, 'a cache hit must not create additional log rows');
        $record = reset($records);
        $this->assertSame(2, (int) $record->count, 'the cache hit must increment the counter atomically');

        // The unique index refuses a duplicate hash on the raw DB layer - the
        // safety net for parallel inserts of the same key.
        $duplicate = (object) [
            'hash' => $record->hash,
            'timecreated' => time(),
            'timemodified' => time(),
            'usermodified' => 0,
            'count' => 1,
        ];
        $this->expectException(\dml_exception::class);
        $DB->insert_record('local_wunderbyte_table', $duplicate);
    }
}
