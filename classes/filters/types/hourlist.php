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

namespace local_wunderbyte_table\filters\types;

use core_date;
use DateTime;
use DateTimeZone;
use local_wunderbyte_table\filters\base;
use local_wunderbyte_table\filter;
use local_wunderbyte_table\wunderbyte_table;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
class hourlist extends base {

    /**
     * Set the column which should be filtered and possibly localize it.
     * @param string $columnidentifier
     * @param string $localizedstring
     * @param string $secondcolumnidentifier
     * @param string $secondcolumnlocalized
     * @return void
     */
    public function __construct(string $columnidentifier,
                                string $localizedstring = '',
                                string $secondcolumnidentifier = '',
                                string $secondcolumnlocalized = '') {

        $this->options = [
            0  => get_string('from0to1', 'local_wunderbyte_table'),
            1  => get_string('from1to2', 'local_wunderbyte_table'),
            2  => get_string('from2to3', 'local_wunderbyte_table'),
            3  => get_string('from3to4', 'local_wunderbyte_table'),
            4  => get_string('from4to5', 'local_wunderbyte_table'),
            5  => get_string('from5to6', 'local_wunderbyte_table'),
            6  => get_string('from6to7', 'local_wunderbyte_table'),
            7  => get_string('from7to8', 'local_wunderbyte_table'),
            8  => get_string('from8to9', 'local_wunderbyte_table'),
            9  => get_string('from9to10', 'local_wunderbyte_table'),
            10 => get_string('from10to11', 'local_wunderbyte_table'),
            11 => get_string('from11to12', 'local_wunderbyte_table'),
            12 => get_string('from12to13', 'local_wunderbyte_table'),
            13 => get_string('from13to14', 'local_wunderbyte_table'),
            14 => get_string('from14to15', 'local_wunderbyte_table'),
            15 => get_string('from15to16', 'local_wunderbyte_table'),
            16 => get_string('from16to17', 'local_wunderbyte_table'),
            17 => get_string('from17to18', 'local_wunderbyte_table'),
            18 => get_string('from18to19', 'local_wunderbyte_table'),
            19 => get_string('from19to20', 'local_wunderbyte_table'),
            20 => get_string('from20to21', 'local_wunderbyte_table'),
            21 => get_string('from21to22', 'local_wunderbyte_table'),
            22 => get_string('from22to23', 'local_wunderbyte_table'),
            23 => get_string('from23to24', 'local_wunderbyte_table'),
        ];

        $this->columnidentifier = $columnidentifier;
        $this->localizedstring = empty($localizedstring) ? $columnidentifier : $localizedstring;
        $this->secondcolumnidentifier = $secondcolumnidentifier;
        $this->secondcolumnlocalized = empty($secondcolumnlocalized) ? $secondcolumnidentifier : $secondcolumnlocalized;
    }

    /**
     * Add the filter to the array.
     * @param array $filter
     * @param bool $invisible
     * @return void
     * @throws moodle_exception
     */
    public function add_filter(array &$filter, bool $invisible = false) {

        $options = $this->options;

        $options['localizedname'] = $this->localizedstring;
        $options['wbfilterclass'] = get_called_class();
        $options[get_class($this)] = true;
        $options[$this->columnidentifier . '_wb_checked'] = $invisible ? 0 : 1;

        // We always need to make sure that id column is present.
        if (!isset($filter['id'])) {
            $filter['id'] = [
                'localizedname' => get_string('id', 'local_wunderbyte_table'),
                'id_wb_checked' => 1,
            ];
        }

        if (!isset($filter[$this->columnidentifier])) {
            $filter[$this->columnidentifier] = $options;
        } else {
            throw new moodle_exception(
                'filteridentifierconflict',
                'local_wunderbyte_table',
                '',
                $this->columnidentifier,
                'Every column can have only one filter applied');
        }
    }

    /**
     * This function takes a key value pair of options.
     * Only if there are actual results in the table, these options will be displayed.
     * The keys are the results, the values are the localized strings.
     * For the standard filter, it's not necessary to provide these options...
     * They will be gathered automatically.
     *
     * @param array $options
     * @return void
     */
    public function add_options(array $options = []) {

        foreach ($options as $key => $value) {
            $this->options[$key] = $value;
        }
    }

    /**
     * Get filter options for hours.
     * @param wunderbyte_table $table
     * @param string $key
     * @return array
     */
    public static function get_data_for_filter_options(wunderbyte_table $table, string $key) {

        $array = self::get_db_filter_column_hours($table, $key);
        $returnarray = [];

        foreach ($array as $hour => $value) {
            $value->$key = "$hour";
            $returnarray[$hour] = $value;
        }

        return $returnarray ?? [];
    }

    /**
     * Makes sql requests.
     * @param wunderbyte_table $table
     * @param string $key
     * @return array
     */
    protected static function get_db_filter_column_hours(wunderbyte_table $table, string $key) {

        global $DB;

        $databasetype = $DB->get_dbfamily();
        $tz = usertimezone(); // We must apply user's timezone there.

        // The $key param is the name of the table in the column, so we can safely use it directly without fear of injection.
        switch ($databasetype) {
            case 'postgres':
                $sql = "SELECT hours, COUNT(hours)
                        FROM (
                            SELECT EXTRACT(
                                HOUR FROM (TIMESTAMP 'epoch' + $key * interval '1 second') AT TIME ZONE 'UTC' AT TIME ZONE '$tz'
                            ) AS hours
                            FROM {$table->sql->from}
                            WHERE {$table->sql->where} AND $key IS NOT NULL
                        ) as hourss1
                        GROUP BY hours ";
                break;
            case 'mysql':
                $sql = "SELECT hours, COUNT(*) as count
                        FROM (
                            SELECT EXTRACT(
                                HOUR FROM CONVERT_TZ(FROM_UNIXTIME($key), 'UTC', '$tz')
                            ) AS hours
                            FROM {$table->sql->from}
                            WHERE {$table->sql->where} AND $key IS NOT NULL
                        ) as hourss1
                        GROUP BY hours";
                break;
            default:
                $sql = '';
                break;
        }

        if (empty($sql)) {
            return [];
        }

        $records = $DB->get_records_sql($sql, $table->sql->params);

        return $records;
    }

    /**
     * Apply the filter of hourlist class.
     *
     * @param string $filter
     * @param string $columnname
     * @param mixed $categoryvalue
     * @param wunderbyte_table $table
     *
     * @return void
     *
     */
    public function apply_filter(
        string &$filter,
        string $columnname,
        $categoryvalue,
        wunderbyte_table &$table
    ): void {
        global $DB;

        $databasetype = $DB->get_dbfamily();
        $tz = usertimezone(); // We must apply user's timezone there.
        $filtercounter = 1;
        $filter .= " ( ";
        foreach ($categoryvalue as $key => $value) {
            $filter .= $filtercounter == 1 ? "" : " OR ";
            $paramsvaluekey = $table->set_params((string) ($value), false);
            // The $key param is the name of the table in the column, so we can safely use it directly without fear of injection.
            switch ($databasetype) {
                case 'postgres':
                    $filter .= " EXTRACT(
                     HOUR FROM (TIMESTAMP 'epoch' + $columnname * interval '1 second') AT TIME ZONE 'UTC' AT TIME ZONE '$tz'
                     ) = :$paramsvaluekey
                     AND $columnname IS NOT NULL";
                    break;
                default:
                    $filter .= " EXTRACT(
                     HOUR FROM CONVERT_TZ(FROM_UNIXTIME($columnname), 'UTC', '$tz')
                     ) = :$paramsvaluekey
                     AND $columnname IS NOT NULL";
            }
            $filtercounter++;
        }
        $filter .= " ) ";
    }
}
