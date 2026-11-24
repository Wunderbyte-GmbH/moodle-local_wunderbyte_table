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
 * Tests for the moment a table download is announced to the browser, and for errors before it.
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
use dml_read_exception;
use local_wunderbyte_table\local\exception\download_query_exception;
use moodle_url;

/**
 * The file of a download is only started once the rows are queried and formatted, so an error
 * before that is shown as an error page instead of being saved by the browser as the file.
 *
 * Under PHPUnit the export class writes the file to the output instead of sending headers, and it
 * does so as soon as the document is started (a CSV begins with a byte order mark). "Nothing
 * printed" therefore means "no file announced to the browser".
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_wunderbyte_table\wunderbyte_table::export_class_instance
 * @covers \local_wunderbyte_table\wunderbyte_table::build_table
 * @covers \local_wunderbyte_table\wunderbyte_table::query_db_cached
 */
final class download_error_test extends advanced_testcase {
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
     * Defines columns, sql and baseurl of a table listing courses (without the site course).
     *
     * @param wunderbyte_table $table
     * @param string $fields
     * @param string $from
     * @return wunderbyte_table
     */
    private function define_table(wunderbyte_table $table, string $fields = '*', string $from = '{course}'): wunderbyte_table {
        $columns = [
            'id' => 'id',
            'fullname' => 'fullname',
        ];
        $table->define_headers(array_values($columns));
        $table->define_columns(array_keys($columns));
        $table->set_filter_sql($fields, $from, 'id > 1', '');
        $table->define_baseurl(new moodle_url('/local/wunderbyte_table/download.php'));

        return $table;
    }

    /**
     * Runs a CSV download up to the point where all rows are handed to the export class.
     * The last step of a real download (finish_output) is left out, because it ends the script.
     *
     * @param wunderbyte_table $table
     * @return array [string $output, \Throwable|null $exception]
     */
    private function run_download(wunderbyte_table $table): array {
        $exception = null;
        ob_start();
        try {
            $table->is_downloading('csv', 'testdownload', 'testdownload');
            $table->setup();
            $table->query_db_cached(20, true);
            $table->build_table();
        } catch (\Throwable $e) {
            $exception = $e;
        }
        $output = ob_get_clean();

        return [$output, $exception];
    }

    /**
     * Requesting the download does not start the file yet.
     */
    public function test_requesting_the_download_announces_no_file(): void {
        $this->setAdminUser();

        $table = $this->define_table(new demo_table('downloadrequest'));
        ob_start();
        $table->is_downloading('csv', 'testdownload', 'testdownload');
        $output = ob_get_clean();

        $this->assertSame('', $output);
        $this->assertSame('csv', $table->is_downloading());
        $this->assertFalse($table->export_class_instance()->document_started());
    }

    /**
     * A download with rows delivers the header row and the rows.
     */
    public function test_download_delivers_header_and_rows(): void {
        $this->setAdminUser();
        $this->getDataGenerator()->create_course(['fullname' => 'Download course']);

        [$output, $exception] = $this->run_download($this->define_table(new demo_table('downloadrows')));

        $this->assertNull($exception);
        $this->assertStringContainsString('id,fullname', $output);
        $this->assertStringContainsString('Download course', $output);
    }

    /**
     * A download without rows still delivers the header row, so the file is valid.
     */
    public function test_download_without_rows_delivers_the_header(): void {
        $this->setAdminUser();

        [$output, $exception] = $this->run_download($this->define_table(new demo_table('downloadnorows')));

        $this->assertNull($exception);
        $this->assertStringContainsString('id,fullname', $output);
    }

    /**
     * The query for the rows fails in the database (unknown column), while counting the rows
     * works. This is what a query that only fails for the full result (e.g. a statement timeout
     * on a big table) looks like to the table. The error is thrown and no file is announced.
     */
    public function test_failing_query_stops_the_download_before_the_file(): void {
        $this->setAdminUser();

        $table = $this->define_table(new demo_table('downloadqueryerror'), 'id, wbtable_missing_column');
        [$output, $exception] = $this->run_download($table);

        // The read error of the query of the table has a name of its own and keeps the original error.
        $this->assertInstanceOf(download_query_exception::class, $exception, 'The database error was swallowed.');
        $this->assertStringContainsString('wbtable_missing_column', $exception->original->error);
        $this->assertStringContainsString('wbtable_missing_column', $exception->debuginfo);
        $this->assertSame('', $output);
    }

    /**
     * Counting the rows fails (unknown table): the error is thrown and no file is announced.
     */
    public function test_failing_count_stops_the_download_before_the_file(): void {
        $this->setAdminUser();

        // The rows "can be read" (no query at all), so only the count fails.
        $table = new class ('downloadcounterror') extends demo_table {
            /**
             * Skips the query for the rows.
             *
             * @param int $pagesize
             * @param bool $useinitialsbar
             * @return void
             */
            public function query_db($pagesize, $useinitialsbar = true) {
                $this->rawdata = [];
            }
        };
        [$output, $exception] = $this->run_download($this->define_table($table, '*', '{wbtable_does_not_exist}'));

        $this->assertInstanceOf(download_query_exception::class, $exception);
        $this->assertSame('', $output);
    }

    /**
     * A read error of a query a column runs for a row is not the query of the table:
     * it keeps its type, so nobody mistakes it for a problem with the amount of data.
     */
    public function test_read_error_of_a_column_keeps_its_type(): void {
        $this->setAdminUser();
        $this->getDataGenerator()->create_course(['fullname' => 'Some course']);

        $table = new class ('downloadcolumnquery') extends demo_table {
            /**
             * Runs a failing query for the row.
             *
             * @param \stdClass $values
             * @return string
             */
            public function col_fullname($values) {
                global $DB;
                return (string)$DB->count_records_sql('SELECT COUNT(*) FROM {wbtable_does_not_exist}');
            }
        };
        [$output, $exception] = $this->run_download($this->define_table($table));

        $this->assertInstanceOf(dml_read_exception::class, $exception);
        $this->assertNotInstanceOf(download_query_exception::class, $exception);
        $this->assertSame('', $output);
    }

    /**
     * A column fails while the rows are formatted (here: for the second row).
     * The error is thrown and no file is announced, not even with the first row.
     */
    public function test_failing_column_stops_the_download_before_the_file(): void {
        $this->setAdminUser();
        $this->getDataGenerator()->create_course(['fullname' => 'Good course']);
        $this->getDataGenerator()->create_course(['fullname' => 'Broken course']);

        $table = new class ('downloadcolumnerror') extends demo_table {
            /**
             * Fails for one of the rows.
             *
             * @param \stdClass $values
             * @return string
             */
            public function col_fullname($values) {
                if ($values->fullname === 'Broken course') {
                    throw new coding_exception('This column cannot be rendered.');
                }
                return $values->fullname;
            }
        };
        [$output, $exception] = $this->run_download($this->define_table($table));

        $this->assertInstanceOf(coding_exception::class, $exception);
        $this->assertSame('', $output);
    }

    /**
     * A displayed table keeps catching the error of the query: it has no rows and an error message.
     */
    public function test_displayed_table_reports_the_database_error(): void {
        $this->setAdminUser();

        $table = $this->define_table(new demo_table('displayerror'), 'id, wbtable_missing_column');
        $table->setup();
        $table->query_db_cached(20, true);

        $this->assertSame([], $table->rawdata);
        $this->assertNotEmpty($table->errormessage);
    }
}
