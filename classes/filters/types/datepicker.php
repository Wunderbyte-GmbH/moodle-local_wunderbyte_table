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
use local_wunderbyte_table\filters\base;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
class datepicker extends base {

    /**
     * Add the filter to the array.
     * @param array $filter
     * @return void
     * @throws moodle_exception
     */
    public function add_filter(array &$filter) {

        $options = [
            'localizedname' => $this->localizedstring,
            get_class($this) => true,
        ];

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
     * Adds the array for the mustache template to render the categoryobject.
     * If no special treatment is needed, it must be implemented in the filter class, but just return.
     * The standard filter will take care of it.
     * @param array $categoryobject
     * @param array $filtersettings
     * @param string $fckey
     * @param array $values
     * @return array
     */
    public static function add_to_categoryobject(array &$categoryobject, array $filtersettings, string $fckey, array $values) {

        if (!is_string($values) && $values !== get_called_class()) {
            return;
        }

        $datepickerarray = $filtersettings[$fckey];

        foreach ($datepickerarray['datepicker'] as $labelkey => $object) {

            if (!isset($object['columntimestart'])) {
                $defaulttimestamp = $datepickerarray['datepicker'][$labelkey]['defaultvalue'];

                $datepickerobject = [
                    'label' => $labelkey,
                    'operator' => $datepickerarray['datepicker'][$labelkey]['operator'],
                    'timestamp' => $defaulttimestamp,
                    'datereadable' => $defaulttimestamp === 'now' ? 'now' : date('Y-m-d', $defaulttimestamp),
                    'timereadable' => $defaulttimestamp === 'now' ? 'now' : date('H:i', $defaulttimestamp),
                    'checkboxlabel' => $datepickerarray['datepicker'][$labelkey]['checkboxlabel'],
                ];

            } else { // Inbetween Filter applied.
                // Prepare the array for output.
                if (empty($datepickerarray['datepicker'][$labelkey]['possibleoperations'])) {
                    $datepickerarray['datepicker'][$labelkey]['possibleoperations'] =
                        ['within', 'overlapboth', 'overlapstart', 'overlapend', 'before', 'after', 'flexoverlap'];
                }
                $operationsarray = array_map(fn($y) => [
                    'operator' => $y,
                    'label' => get_string($y, 'local_wunderbyte_table'),
                ], $datepickerarray['datepicker'][$labelkey]['possibleoperations']);

                $datepickerobject = [
                    'label' => $labelkey,
                    'startcolumn' => $datepickerarray['datepicker'][$labelkey]['columntimestart'],
                    'starttimestamp' => $datepickerarray['datepicker'][$labelkey]['defaultvaluestart'],
                    'startdatereadable' => $datepickerarray['datepicker'][$labelkey]['defaultvaluestart'] === 'now' ?
                        'now' : date('Y-m-d', $datepickerarray['datepicker'][$labelkey]['defaultvaluestart']),
                    'starttimereadable' => $datepickerarray['datepicker'][$labelkey]['defaultvaluestart'] === 'now' ?
                        'now' : date('H:i', $datepickerarray['datepicker'][$labelkey]['defaultvaluestart']),
                    'endcolumn' => $datepickerarray['datepicker'][$labelkey]['columntimeend'],
                    'endtimestamp' => $datepickerarray['datepicker'][$labelkey]['defaultvalueend'],
                    'enddatereadable' => $datepickerarray['datepicker'][$labelkey]['defaultvalueend'] === 'now' ?
                        'now' : date('Y-m-d', $datepickerarray['datepicker'][$labelkey]['defaultvalueend']),
                    'endtimereadable' => $datepickerarray['datepicker'][$labelkey]['defaultvalueend'] === 'now' ?
                        'now' : date('H:i', $datepickerarray['datepicker'][$labelkey]['defaultvalueend']),
                    'checkboxlabel' => $datepickerarray['datepicker'][$labelkey]['checkboxlabel'],
                    'possibleoperations' => $operationsarray, // Array.
                ];
            }
            $categoryobject['datepicker']['datepickers'] = $datepickerobject;
        }
    }
}
