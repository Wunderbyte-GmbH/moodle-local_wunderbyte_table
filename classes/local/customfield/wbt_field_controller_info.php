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

namespace local_wunderbyte_table\local\customfield;

use local_wunderbyte_table\local\customfield\field\text\wbt_field_controller;
use stdClass;
use local_wunderbyte_table\local\customfield\wbt_field_controller_base;

/**
 * Extension of the customfield field controller for Wunderbyte table.
 *
 * @package    local_wunderbyte_table
 * @copyright  2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @author     Bernhard Fischer-Sengseis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wbt_field_controller_info {
    /**
     * Array of instantiated customfield field controllers.
     * @var array
     */
    private static $instances = [];

    /**
     * @var string
     */
    public const WBTABLE_CUSTOMFIELD_VALUE_NOTFOUND = 'wbtable_customfield_value_notfound';

    /**
     * Create a customfield field controller for a customfield db record.
     *
     * @param stdClass $record customfield record from db table customfield_field
     * @return wbt_field_controller_base|null the field controller for the customfield record
     */
    public static function create(stdClass $record) {
        $class = "\\local_wunderbyte_table\\local\\customfield\\field\\{$record->type}\\wbt_field_controller";
        if (class_exists($class)) {
            return new $class($record->id, $record);
        }
        // By default, we return the text controller.
        return new \local_wunderbyte_table\local\customfield\field\text\wbt_field_controller($record->id, $record);
    }

    /**
     * Create instances of field controllers for all provided customfield shortnames.
     *
     * @param array $shortnames array of customfield shortnames
     * @return void
     */
    public static function instantiate_by_shortnames(array $shortnames) {
        global $DB;

        if (empty($shortnames)) {
            return;
        }

        [$inorequal, $inparams] = $DB->get_in_or_equal($shortnames, SQL_PARAMS_NAMED);

        $sql = "SELECT cf.shortname AS filtercolumn, cf.*
                FROM {customfield_field} cf
                WHERE cf.shortname $inorequal";
        $records = $DB->get_records_sql($sql, $inparams);

        foreach ($records as $record) {
            // We only add the instance, if a field controller exists.
            if ($instance = self::create($record)) {
                self::$instances[$record->shortname] = $instance;
            }
        }
    }

    /**
     * Get the field controller from the singleton $instances.
     *
     * @param string $shortname shortname of field controller customfield
     * @return wbt_field_controller_base the field controller for the customfield record
     */
    public static function get_instance_by_shortname(string $shortname) {
        if (!empty(self::$instances[$shortname])) {
            return self::$instances[$shortname];
        } else {
            global $DB;
            $sql = "SELECT cf.shortname AS filtercolumn, cf.*
                FROM {customfield_field} cf
                WHERE cf.shortname = :shortname";
            $params = ['shortname' => $shortname];
            if ($record = $DB->get_record_sql($sql, $params)) {
                // We only add the instance, if a field controller exists.
                if ($instance = self::create($record)) {
                    self::$instances[$record->shortname] = $instance;
                    return $instance;
                }
            }
        }
        // Fallback: By default, we return text controller.
        return new wbt_field_controller();
    }
}
