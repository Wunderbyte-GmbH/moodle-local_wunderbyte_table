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

namespace local_wunderbyte_table\local\customfield\field\text;

// Important: Use the field controller for the right customfield.
use customfield_text\field_controller;
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
     * @param string $key
     * @param bool $formatstring
     * @param bool $keyisencoded
     * @return string the string value
     */
    public function get_option_value_by_key(string $key, bool $formatstring = true, bool $keyisencoded = false): string {
        if (!empty($key)) {
            if ($keyisencoded) {
                $returnvalue = base64_decode($key);
            } else {
                $returnvalue = $key;
            }

            // For normal text fields we might need format_string.
            if ($formatstring) {
                $returnvalue = format_string($returnvalue);
            }
            return $returnvalue;
        }
        return '';
    }

    /**
     * Get an array containing all key value pairs for the customfield.
     * Depending on the type, these can be actually used values or possible values.
     *
     * @return array an array containing all key value pairs for the customfield
     */
    public function get_values_array(): array {
        global $DB;

        switch ($DB->get_dbfamily()) {
            case 'mysql':
                $sql = "SELECT DISTINCT TO_BASE64(value) AS id, value AS data
                          FROM {customfield_data} cd
                         WHERE fieldid = :fieldid";
                break;
            default:
                $sql = "SELECT DISTINCT encode(value::bytea, 'base64') AS id, value AS data
                          FROM {customfield_data} cd
                         WHERE fieldid = :fieldid";
                break;
        }
        // We need to be able to get back to data, for textbased entries, therefore we use the value as id and data.
        $params = [
            'fieldid' => $this->field->get('id'),
        ];
        try {
            $records = $DB->get_records_sql($sql, $params);
        } catch (\Throwable $th) {
            return [];
        }

        return $records;
    }
}
