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

use moodle_exception;
use core_date;
use local_wunderbyte_table\filters\base;
use local_wunderbyte_table\wunderbyte_table;

/**
 * Filter class with automatically supports the english weekdays as filter options.
 * @package local_wunderbyte_table
 */
class weekdays extends base {
    /**
     * Set the column which should be filtered and possibly localize it.
     * @param string $columnidentifier
     * @param string $localizedstring
     * @param string $secondcolumnidentifier
     * @param string $secondcolumnlocalized
     * @return void
     */
    public function __construct(
        string $columnidentifier,
        string $localizedstring = '',
        string $secondcolumnidentifier = '',
        string $secondcolumnlocalized = ''
    ) {

        $this->options = self::get_possible_weekdays_options();
        $this->columnidentifier = $columnidentifier;
        $this->localizedstring = empty($localizedstring) ? $columnidentifier : $localizedstring;
        $this->secondcolumnidentifier = $secondcolumnidentifier;
        $this->secondcolumnlocalized = empty($secondcolumnlocalized) ? $secondcolumnidentifier : $secondcolumnlocalized;
    }

    /**
     * Add the filter to the array.
     * @return array
     */
    public static function get_possible_weekdays_options() {
        return [
            'monday' => get_string('monday', 'calendar'),
            'tuesday' => get_string('tuesday', 'calendar'),
            'wednesday' => get_string('wednesday', 'calendar'),
            'thursday' => get_string('thursday', 'calendar'),
            'friday' => get_string('friday', 'calendar'),
            'saturday' => get_string('saturday', 'calendar'),
            'sunday' => get_string('sunday', 'calendar'),
        ];
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
                'Every column can have only one filter applied'
            );
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
     * The expected value.
     * @param \MoodleQuickForm $mform
     * @param array $data
     * @param string $filterspecificvalue
     */
    public static function render_mandatory_fields(&$mform, $data = [], $filterspecificvalue = '') {
        $mform->addElement('html', '<p id="no-pairs-message" class="alert alert-info">No further seetings needed</p>');
    }

    /**
     * The expected value.
     * @param object $data
     * @param string $filtercolumn
     * @return array
     */
    public static function get_filterspecific_values($data, $filtercolumn) {
        $filterenablelabel = $filtercolumn . '_wb_checked';
        $filterspecificvalues = [
            'localizedname' => $data->localizedname ?? '',
            $data->wbfilterclass => true,
            $filterenablelabel => $data->$filterenablelabel ?? '0',
            'wbfilterclass' => $data->wbfilterclass ?? '',
        ];
        return [$filterspecificvalues, ''];
    }

    /**
     * The expected value.
     * @param object $data
     * @param string $filtercolumn
     * @return array
     */
    public static function get_new_filter_values($data, $filtercolumn) {
        $filterenablelabel = $filtercolumn . '_wb_checked';
        $filterspecificvalues = [
            'localizedname' => $data->localizedname ?? '',
            $data->wbfilterclass => true,
            $filterenablelabel => $data->$filterenablelabel ?? '0',
            'wbfilterclass' => $data->wbfilterclass ?? '',
        ];
        $filterspecificvalues = array_merge($filterspecificvalues, self::get_possible_weekdays_options());
        return $filterspecificvalues;
    }

    /**
     * Get filter options for weekdays.
     * @param wunderbyte_table $table
     * @param string $key
     * @return array
     */
    public static function get_data_for_filter_options(wunderbyte_table $table, string $key) {

        $array = self::get_db_filter_column_weekdays($table, $key);

        $returnarray = [];
        // We get back the GMT timestamps. We need to translate them.
        foreach ($array as $day => $value) {
            $value->$key = "$day";
            $returnarray[$day] = $value;
        }

        return $returnarray ?? [];
    }

    /**
     * Makes sql request for weekdays .
     * @param wunderbyte_table $table
     * @param string $key
     * @return array
     */
    protected static function get_db_filter_column_weekdays(wunderbyte_table $table, string $key) {
        global $DB, $USER;

        $databasetype = $DB->get_dbfamily();
        $tz = core_date::get_user_timezone($USER); // We must apply user's timezone there.

        // The $key param is the name of the table in the column, so we can safely use it directly without fear of injection.
        switch ($databasetype) {
            case 'postgres':
                $sql = "SELECT weekday, COUNT(weekday)
                        FROM (
                            SELECT TRIM(TO_CHAR(
                                (TIMESTAMP 'epoch' + $key * INTERVAL '1 second') AT TIME ZONE 'UTC' AT TIME ZONE '$tz', 'day'
                            )) AS weekday
                            FROM {$table->sql->from}
                            WHERE {$table->sql->where} AND $key IS NOT NULL
                        ) as weekdayss1
                        GROUP BY weekday ";
                break;
            case 'mysql':
                $sql = "SELECT weekday, COUNT(*) as count
                        FROM (
                            SELECT LOWER(DATE_FORMAT(
                                CONVERT_TZ(FROM_UNIXTIME($key), 'UTC', '$tz'), '%W'
                            )) AS weekday
                            FROM {$table->sql->from}
                            WHERE {$table->sql->where} AND $key IS NOT NULL
                        ) as weekdayss1
                        GROUP BY weekday";
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
     * Apply the filter of weekday class.
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
        global $DB, $USER;

        $databasetype = $DB->get_dbfamily();
        $tz = core_date::get_user_timezone($USER); // We must apply user's timezone there.
        $filtercounter = 1;
        $filter .= " ( ";
        foreach ($categoryvalue as $key => $value) {
            $filter .= $filtercounter == 1 ? "" : " OR ";
            $paramsvaluekey = $table->set_params((string) ($value), false);
            // The $key param is the name of the table in the column, so we can safely use it directly without fear of injection.
            switch ($databasetype) {
                case 'postgres':
                    $filter .= " TRIM(TO_CHAR(
                    (TIMESTAMP 'epoch' + $columnname * INTERVAL '1 second') AT TIME ZONE 'UTC' AT TIME ZONE '$tz', 'day'
                    )) = :$paramsvaluekey
                    AND $columnname IS NOT NULL";
                    break;
                default:
                    $filter .= " LOWER(DATE_FORMAT(
                    CONVERT_TZ(FROM_UNIXTIME($columnname), 'UTC', '$tz'), '%W'
                    )) = :$paramsvaluekey
                    AND $columnname IS NOT NULL";
            }
            $filtercounter++;
        }
        $filter .= " ) ";
    }
}
