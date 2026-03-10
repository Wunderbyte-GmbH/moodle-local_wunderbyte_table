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
 * Extension of the customfield field controller for Wunderbyte table.
 *
 * @package    local_wunderbyte_table
 * @copyright  2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author     Bernhard Fischer-Sengseis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\local\customfield\field\date;

// Important: Use the field controller for the right customfield.
use customfield_date\field_controller;
use local_wunderbyte_table\local\customfield\wbt_field_controller_base;

/**
 * Extension of the customfield field controller for Wunderbyte table.
 *
 * @package    local_wunderbyte_table
 * @copyright  2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author     Bernhard Fischer-Sengseis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wbt_field_controller extends field_controller implements wbt_field_controller_base {
    /**
     * Get the actual string value of the customfield.
     *
     * @param string|array $key
     * @param bool $formatstring
     * @param bool $keyisencoded
     * @return string the string value
     */
    public function get_option_value_by_key(string|array $key, bool $formatstring = true, bool $keyisencoded = false): string {
        if (empty($key) || $key == 0) {
            return '';
        }
        $includetime = $this->get_configdata_property('includetime');
        if ($includetime == "1") {
            $format = get_string('strftimedatetime');
        } else {
            $format = get_string('strftimedate');
        }
        return userdate($key, $format);
    }

    /**
     * Get an array containing all key value pairs for the customfield.
     * Depending on the type, these can be actually used values or possible values.
     *
     * @return array an array containing all key value pairs for the customfield
     */
    public function get_values_array(): array {
        return [];
    }
}
