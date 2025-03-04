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

namespace local_wunderbyte_table\local\customfield\field\select;

// Important: Use the field controller for the right customfield.
use customfield_select\field_controller;
use local_wunderbyte_table\local\customfield\wbt_field_controller_base;
use local_wunderbyte_table\local\customfield\wbt_field_controller_info;
use stdClass;

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
     * Get the actual string value of the customfield by index.
     *
     * @param string $key
     * @param bool $formatstring
     * @param bool $keyisencoded
     * @return string the string value for the index
     */
    public function get_option_value_by_key(string $key, bool $formatstring = true, bool $keyisencoded = false): string {
        $index = (int) $key;
        $optionsstring = $this->get_configdata_property('options');
        $optionsarray = explode(PHP_EOL, $optionsstring);
        if (empty($optionsarray)) {
            return '';
        }
        $i = $index - 1;
        if ($i < 0 || $i >= count($optionsarray)) {
            return wbt_field_controller_info::WBTABLE_CUSTOMFIELD_VALUE_NOTFOUND;
        }
        $returnvalue = $optionsarray[$i];
        if ($formatstring) {
            $returnvalue = format_string($returnvalue);
        }
        return $returnvalue;
    }

    /**
     * Get an array containing all key value pairs for the customfield.
     * Depending on the type, these can be actually used values or possible values.
     *
     * @return array an array containing all key value pairs for the customfield
     */
    public function get_values_array(): array {
        $optionsstring = $this->get_configdata_property('options');
        $optionsarray = explode(PHP_EOL, $optionsstring);
        if (empty($optionsarray)) {
            return [];
        }
        return $optionsarray;
    }
}
