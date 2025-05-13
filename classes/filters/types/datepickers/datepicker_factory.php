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

 namespace local_wunderbyte_table\filters\types\datepickers;

/**
 * Class unit
 * @author Jacob Viertel
 * @copyright 2025 Wunderbyte GmbH
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class datepicker_factory {
    /**
     * Factory for the organisational units
     * @param \MoodleQuickForm $mform
     * @param string $valuelabel
     * @param array $filtertype
     * @return array
     */
    public static function get_and_set_input(&$mform, $valuelabel, $filtertype) {
        $type = self::get_datepicker_type($filtertype['operator'] ?? $filtertype['possibleoperations'] ?? '');
        $datepickersclass = 'local_wunderbyte_table\\filters\\types\\datepickers\\' . $type;
        if (class_exists($datepickersclass)) {
            $datepickerinstance = new $datepickersclass($mform, $valuelabel, $filtertype);
            $inputs = $datepickerinstance->get_inputs();
            $datepickerinstance->set_inputs();
            return $inputs;
        }
        return [];
    }

    /**
     * Factory for the organisational units
     * @param \MoodleQuickForm $mform
     * @param string $valuelabel
     * @param string $type
     * @return array
     */
    public static function set_input(&$mform, $valuelabel, $type) {
        $datepickersclass = 'local_wunderbyte_table\\filters\\types\\datepickers\\' . $type;
        if (class_exists($datepickersclass)) {
            $datepickerinstance = new $datepickersclass($mform, $valuelabel, []);
            $inputs = $datepickerinstance->get_inputs();
            return $inputs;
        }
        return [];
    }

    /**
     * The expected value.
     * @param string|array $operator
     * @return string
     */
    public static function get_datepicker_type($operator) {
        if (is_array($operator)) {
            return 'span';
        }
        return 'timestamp';
    }
}
