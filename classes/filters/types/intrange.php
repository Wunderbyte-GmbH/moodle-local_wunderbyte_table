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
                'Every column can have only one filter applied');
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
                'label' => $labelkey,
                'column' => $intrangearray['intrange'][$labelkey]['column'],
                'startvalue' => $intrangearray['intrange'][$labelkey]['defaultvaluestart'],
                'endvalue' => $intrangearray['intrange'][$labelkey]['columntimeend'],
                'checkboxlabel' => $intrangearray['intrange'][$labelkey]['checkboxlabel'],
            ];
        }
        $categoryobject['intrange']['intranges'][] = $intrangeobject;
    }
}