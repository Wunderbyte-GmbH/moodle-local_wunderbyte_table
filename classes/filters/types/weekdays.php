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
use local_wunderbyte_table\filter;
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

        $this->options = [
            'monday' => get_string('monday', 'local_wunderbyte_table'),
            'tuesday' => get_string('tuesday', 'local_wunderbyte_table'),
            'wednesday' => get_string('wednesday', 'local_wunderbyte_table'),
            'thursday' => get_string('thursday', 'local_wunderbyte_table'),
            'friday' => get_string('friday', 'local_wunderbyte_table'),
            'saturday' => get_string('saturday', 'local_wunderbyte_table'),
            'sunday' => get_string('sunday', 'local_wunderbyte_table'),
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
     * Get filter options for weekdays.
     * @param wunderbyte_table $table
     * @param string $key
     * @return array
     */
    public static function get_data_for_filter_options(wunderbyte_table $table, string $key) {

        $array = filter::get_db_filter_column_weekdays($table, $key);

        $returnarray = [];
        // We get back the GMT timestamps. We need to translate them.
        foreach ($array as $day => $value) {
            $value->$key = "$day";
            $returnarray[$day] = $value;
        }

        return $returnarray ?? [];
    }
}
