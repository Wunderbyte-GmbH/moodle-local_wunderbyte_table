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
 * @copyright 2024 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\filters\types;

use coding_exception;
use local_wunderbyte_table\filters\base;
use local_wunderbyte_table\wunderbyte_table;
use moodle_exception;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
class intrange extends base {
    /**
     * Get standard filter options.
     * @param wunderbyte_table $table
     * @param string $key
     * @return array
     */
    public static function get_data_for_filter_options(wunderbyte_table $table, string $key) {

        return [];
    }

    /**
     * Add the filter to the array.
     * @param array $filter
     * @param bool $invisible
     * @return void
     * @throws moodle_exception
     */
    public function add_filter(array &$filter, bool $invisible = false) {

        $options = [
            'localizedname' => $this->localizedstring,
            get_class($this) => true,
            'intrange' => $this->options,
            $this->columnidentifier . '_wb_checked' => 1,
        ];
        $options['wbfilterclass'] = get_called_class();

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
     * Add options.
     *
     * @param string $checkboxlabel
     * @param int $defaultvaluestart
     * @param int $defaultvalueend
     *
     * @return void
     *
     */
    public function add_options(
        string $checkboxlabel = '',
        int $defaultvaluestart = 0,
        int $defaultvalueend = 0
    ) {

        $filter = [
            'checkboxlabel' => !empty($checkboxlabel) ? $checkboxlabel : get_string('apply_filter', 'local_wunderbyte_table'),
            'defaultvaluestart' => $defaultvaluestart,
            'defaultvalueend' => $defaultvalueend,
        ];

        $this->options[$this->localizedstring] = $filter;
    }

    /**
     * Adds the array for the mustache template to render the categoryobject.
     * If no special treatment is needed, it must be implemented in the filter class, but just return.
     * The standard filter will take care of it.
     * @param array $categoryobject
     * @param array $filtersettings
     * @param string $fckey
     * @param array $values
     * @return void
     */
    public static function add_to_categoryobject(array &$categoryobject, array $filtersettings, string $fckey, array $values) {

        if (!isset($filtersettings[$fckey][get_called_class()])) {
            return;
        }

        $intrangearray = $filtersettings[$fckey];

        foreach ($intrangearray['intrange'] as $labelkey => $object) {
            // Prepare the array for output.
            $intrangeobject = [
                'label' => $labelkey ?? '',
                'column' => $fckey,
                'startvalue' => $intrangearray['intrange'][$labelkey]['defaultvaluestart'] ?? '',
                'endvalue' => $intrangearray['intrange'][$labelkey]['columntimeend'] ?? '',
                'checkboxlabel' => $intrangearray['intrange'][$labelkey]['checkboxlabel'] ?? '',
            ];
            $categoryobject['intrange']['intranges'][] = $intrangeobject;
        }
        if (empty($intrangearray['intrange'])) {
            $categoryobject['intrange']['intranges'][] = [
                'label' => '',
                'column' => $fckey,
                'startvalue' => '',
                'endvalue' => '',
                'checkboxlabel' => '',
            ];
        }
    }

    /**
     * Apply the filter of intrange class.
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

        // Bugfix: Do not apply this filter, if categoryvalue is of another type than string.
        if (!is_string($categoryvalue)) {
            return;
        }
        $dates = explode(",", $categoryvalue);
        if (empty($dates)) {
            return;
        }

        $from = self::return_int_values($dates[0]);
        $to = self::return_int_values($dates[1]);
        $onlymingiven = false;
        if (empty($from) && empty($to)) {
            $filter .= "1 = 1";
            return;
        } else if (empty($to)) {
            $onlymingiven = true;
        }
        $filter .= " ( ";
        // Set the params correctly.
        $params = [$from, $to];
        $paramswithkeys = [];
        foreach ($params as $paramvalue) {
            $key = $table->set_params($paramvalue);
            $paramswithkeys[$key] = $paramvalue;
        }
        $keys = array_keys($paramswithkeys);

        // Will treat positive ints only, since everything except 0-9 is escaped.
        if ($DB->get_dbfamily() === 'postgres') {
            // PostgreSQL: Extract numbers from the string and cast to integer for comparison.
            $filter .= "
            REGEXP_REPLACE(CAST($columnname AS TEXT), '[^0-9]', '', 'g') IS NOT NULL
            AND REGEXP_REPLACE(CAST($columnname AS TEXT), '[^0-9]', '', 'g') != ''
            AND CAST(REGEXP_REPLACE(CAST($columnname AS TEXT), '[^0-9]', '', 'g') AS INTEGER)";
        } else {
            // MariaDB/MySQL.
            // phpcs:ignore moodle.Commenting.TodoComment.MissingInfoInline
            // TODO: Test if this works!!
            $filter .= "
            REGEXP_REPLACE(CAST($columnname AS CHAR), '[^0-9]', '') IS NOT NULL
            AND REGEXP_REPLACE(CAST($columnname AS CHAR), '[^0-9]', '') != ''
            AND CAST(REGEXP_REPLACE(CAST($columnname AS CHAR), '[^0-9]', '') AS SIGNED)";
        }

        // This part is identical for both db families.
        if ($onlymingiven) {
            $filter .= " > :" . $keys[0];
        } else {
            $filter .= "BETWEEN :" . $keys[0] . " AND :" . $keys[1];
        }
        $filter .= " ) ";
    }

    /**
     * Make sure, only ints are handed over to query.
     *
     * @param mixed $value
     *
     * @return int
     *
     */
    private static function return_int_values($value): int {
        $matches = [];
        if (preg_match_all('/\d+/', $value, $matches)) {
            // If matches are found, join them and return as an integer.
            return (int)implode('', $matches[0]);
        } else {
            // If no matches, return 0.
            return 0;
        }
    }

    /**
     * Add keys and values for applied filters. This will only be applied if filter is active.
     *
     * @param mixed $tableobject
     * @param array $filterarray
     * @param int $key
     *
     * @return void
     *
     */
    public static function prepare_filter_for_rendering(&$tableobject, array $filterarray, int $key) {

        // Expand the filter area. TODO: Fix this for all filters.
        $tableobject[$key]['show'] = 'show';
        $tableobject[$key]['collapsed'] = '';
        $tableobject[$key]['expanded'] = 'true';

        // Set the checkbox checked.
        $tableobject[$key]['intrange']['intranges'][0]['checked'] = 'checked';

        // Apply values.
        $filterstring = array_values($filterarray)[0];
        if (!is_string($filterstring)) {
            return;
        }
        $values = explode(",", $filterstring);
        $tableobject[$key]['intrange']['intranges'][0]['startvalue'] = $values[0];
        $tableobject[$key]['intrange']['intranges'][0]['endvalue'] = $values[1];

        return;
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
    public static function get_new_filter_values($data, $filtercolumn) {
        return [];
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
            'intrange' => [],
            $filterenablelabel => $data->$filterenablelabel ?? '0',
            'wbfilterclass' => $data->wbfilterclass ?? '',
        ];
        return [$filterspecificvalues, ''];
    }
}
