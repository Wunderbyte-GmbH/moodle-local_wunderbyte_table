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
 * The Wunderbyte table class is an extension of the tablelib table_sql class.
 *
 * @package local_wunderbyte_table
 * @copyright 2023 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\filters;

use advanced_testcase;
use stdClass;

/**
 * Unit tests for standardfilter class.
 */
final class persist_filter_settings_test extends advanced_testcase {
    /**
     * Test define_sql() method.
     * @covers \local_wunderbyte_table\filters\wunderbyte_table_db_operator::persist_filter_settings
     */
    public function test_persisting_filtervalues_over_different_languages(): void {
        $hash = 'e2f7668834908f2baf4a94e4f8c458a1english_filterjson';
        $tablehash = 'e2f7668834908f2baf4a94e4f8c458a1';

        $this->insert_fake_values($tablehash);

        $mockdata = new stdClass();
        $mockdata->key = ['test_key'];
        $mockdata->value = ['test_value'];
        $mockdata->filter_columns = 'filter1';

        $mocktable = $this->getMockBuilder(\local_wunderbyte_table\wunderbyte_table::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mocktable->tablecachehash = 'test_cache_hash';

        $filteroperator = new wunderbyte_table_db_operator($mockdata, $mocktable);

        $otherlangtables = $filteroperator->get_other_lang_tables($tablehash, $hash);
        $this->assertSame(1, count($otherlangtables));

        $filteroperator->persist_filter_settings($otherlangtables, $this->get_json_data_second());
        $alllangtables = $filteroperator->get_other_lang_tables($tablehash, '');
        $firstsettings = array_shift($alllangtables);
        $firstsettings->jsonstring = json_decode($firstsettings->jsonstring);
        $secondsettings = array_shift($alllangtables);
        $secondsettings->jsonstring = json_decode($secondsettings->jsonstring);

        foreach ($firstsettings->jsonstring->filtersettings as $filtercolumn => $filtersettings) {
            $this->assertSame(
                $firstsettings->jsonstring->filtersettings->$filtercolumn->{$filtercolumn . '_wb_checked'},
                $secondsettings->jsonstring->filtersettings->$filtercolumn->{$filtercolumn . '_wb_checked'}
            );
        }
    }

    /**
     * Test define_sql() method.
     * @covers \local_wunderbyte_table\filters\wunderbyte_table_db_operator::persist_filter_settings
     * @param string $tablehash
     */
    private function insert_fake_values($tablehash): void {
        global $DB;
        $this->resetAfterTest(true);

        $tablename = 'local_wunderbyte_table';
        $records = $this->get_fake_values($tablehash);
        $DB->insert_records($tablename, $records);
    }

    /**
     * Test define_sql() method.
     * @covers \local_wunderbyte_table\filters\wunderbyte_table_db_operator::persist_filter_settings
     * @param string $tablehash
     */
    private function get_fake_values($tablehash): array {
        $record1 = new stdClass();
        $record1->hash = $tablehash . 'deutsch_filterjson';
        $record1->tablehash = $tablehash;
        $record1->jsonstring = json_encode($this->get_json_data_first(), JSON_UNESCAPED_UNICODE);

        $record2 = new stdClass();
        $record2->hash = $tablehash . 'english_filterjson';
        $record2->tablehash = $tablehash;
        $record2->jsonstring = json_encode($this->get_json_data_second(), JSON_UNESCAPED_UNICODE);

        return [$record1, $record2];
    }

    /**
     * Test define_sql() method.
     * @covers \local_wunderbyte_table\filters\wunderbyte_table_db_operator::persist_filter_settings
     */
    private function get_json_data_first(): array {
        return [
            'filtersettings' => [
                'id' => [
                    'localizedname' => 'ID',
                    'id_wb_checked' => 0,
                ],
                'fullname' => [
                    'localizedname' => 'Vollständiger Name',
                    'wbfilterclass' => 'local_wunderbyte_table\\filters\\types\\standardfilter',
                    'fullname_wb_checked' => 0,
                ],
                'shortname' => [
                    'localizedname' => 'Kurzbezeichnung',
                    'wbfilterclass' => 'local_wunderbyte_table\\filters\\types\\standardfilter',
                    'shortname_wb_checked' => 0,
                ],
                'enddate' => [
                    'localizedname' => 'Kursende',
                    'wbfilterclass' => 'local_wunderbyte_table\\filters\\types\\datepicker',
                    'local_wunderbyte_table\\filters\\types\\datepicker' => true,
                    'datepicker' => [
                        'Kursende' => [
                            'checkboxlabel' => 'Filter anwenden',
                            'operator' => '<',
                            'defaultvalue' => 'now',
                        ],
                    ],
                    'enddate_wb_checked' => 0,
                ],
                'startdate' => [
                    'localizedname' => 'Zeitspanne',
                    'wbfilterclass' => 'local_wunderbyte_table\\filters\\types\\datepicker',
                    'local_wunderbyte_table\\filters\\types\\datepicker' => true,
                    'datepicker' => [
                        'Zeitspanne' => [
                            'checkboxlabel' => 'Filter anwenden',
                            'columntimestart' => 'startdate',
                            'columntimeend' => 'enddate',
                            'labelstartvalue' => 'Zeitspanne',
                            'labelendvalue' => 'enddate',
                            'defaultvaluestart' => '1680130800',
                            'defaultvalueend' => 'now',
                            'possibleoperations' => [
                                'within',
                                'overlapboth',
                                'overlapstart',
                                'overlapend',
                                'before',
                                'after',
                                'flexoverlap',
                            ],
                        ],
                    ],
                    'startdate_wb_checked' => 0,
                ],
            ],
        ];
    }

    /**
     * Test define_sql() method.
     * @covers \local_wunderbyte_table\filters\wunderbyte_table_db_operator::persist_filter_settings
     */
    private function get_json_data_second(): array {
        return [
            'filtersettings' => [
                'id' => [
                    'localizedname' => 'ID',
                    'id_wb_checked' => 1,
                ],
                'fullname' => [
                    'localizedname' => 'Full name',
                    'wbfilterclass' => 'local_wunderbyte_table\\filters\\types\\standardfilter',
                    'fullname_wb_checked' => 1,
                ],
                'shortname' => [
                    'localizedname' => 'Short name',
                    'wbfilterclass' => 'local_wunderbyte_table\\filters\\types\\standardfilter',
                    'shortname_wb_checked' => 1,
                ],
                'enddate' => [
                    'localizedname' => 'Course end date',
                    'wbfilterclass' => 'local_wunderbyte_table\\filters\\types\\datepicker',
                    'local_wunderbyte_table\\filters\\types\\datepicker' => true,
                    'datepicker' => [
                        'Course end date' => [
                            'checkboxlabel' => 'Apply Filter',
                            'operator' => '>=',
                            'defaultvalue' => 'now',
                        ],
                    ],
                    'enddate_wb_checked' => 1,
                ],
                'startdate' => [
                    'localizedname' => 'Timespan',
                    'wbfilterclass' => 'local_wunderbyte_table\\filters\\types\\datepicker',
                    'local_wunderbyte_table\\filters\\types\\datepicker' => true,
                    'datepicker' => [
                        'Timespan' => [
                            'checkboxlabel' => 'Apply Filter',
                            'columntimestart' => 'startdate',
                            'columntimeend' => 'enddate',
                            'labelstartvalue' => 'Timespan',
                            'labelendvalue' => 'enddate',
                            'defaultvaluestart' => '1680130800',
                            'defaultvalueend' => 'now',
                            'possibleoperations' => ['0', '1', '2', '3'],
                        ],
                    ],
                    'startdate_wb_checked' => 1,
                ],
            ],
        ];
    }
}
