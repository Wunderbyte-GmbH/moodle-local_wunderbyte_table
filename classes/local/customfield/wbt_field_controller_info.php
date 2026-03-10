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
        return new wbt_field_controller($record->id, $record);
    }

    /**
     * Create instances of field controllers for all provided customfield shortnames.
     *
     * @param array $shortnames array of customfield shortnames
     * @param string $component optional component to filter by (e.g. 'mod_booking')
     * @param string $area optional area to filter by (e.g. 'booking')
     * @return void
     */
    public static function instantiate_by_shortnames(array $shortnames, string $component = '', string $area = '') {
        global $DB;

        if (empty($shortnames)) {
            return;
        }

        [$inorequal, $inparams] = $DB->get_in_or_equal($shortnames, SQL_PARAMS_NAMED);

        $where = "cf.shortname $inorequal";
        if (!empty($component)) {
            $inparams['cfcomponent'] = $component;
            $where .= ' AND cc.component = :cfcomponent';
        }
        if (!empty($area)) {
            $inparams['cfarea'] = $area;
            $where .= ' AND cc.area = :cfarea';
        }

        // Order by cf.id DESC so the newest field per shortname is processed first.
        $sql = "SELECT cf.shortname AS filtercolumn, cf.*
                  FROM {customfield_field} cf
                  JOIN {customfield_category} cc ON cf.categoryid = cc.id
                 WHERE $where
              ORDER BY cf.id DESC";
        $records = $DB->get_records_sql($sql, $inparams);

        foreach ($records as $record) {
            $key = $record->shortname;
            if (!empty($area)) {
                $key = "$area-$key";
            }
            if (!empty($component)) {
                $key = "$component-$key";
            }
            // Only take the first (newest) instance per shortname.
            if (!isset(self::$instances[$key])) {
                if ($instance = self::create($record)) {
                    self::$instances[$key] = $instance;
                }
            }
        }
    }

    /**
     * Get the field controller from the singleton $instances.
     *
     * @param string $shortname shortname of field controller customfield
     * @param string $component optional component to filter by (e.g. 'mod_booking')
     * @param string $area optional area to filter by (e.g. 'booking')
     * @return wbt_field_controller_base the field controller for the customfield record
     */
    public static function get_instance_by_shortname(string $shortname, string $component = '', string $area = '') {
        // Key for static singleton.
        $key = $shortname;
        if (!empty($area)) {
            $key = "$area-$key";
        }
        if (!empty($component)) {
            $key = "$component-$key";
        }

        if (!empty(self::$instances[$key])) {
            return self::$instances[$key];
        } else {
            global $DB;

            $where = 'cf.shortname = :shortname';
            $params = ['shortname' => $shortname];

            if (!empty($component)) {
                $params['cfcomponent'] = $component;
                $where .= ' AND cc.component = :cfcomponent';
            }
            if (!empty($area)) {
                $params['cfarea'] = $area;
                $where .= ' AND cc.area = :cfarea';
            }

            // Order by cf.id DESC and take the first record (newest) when not fully scoped.
            $sql = "SELECT cf.shortname AS filtercolumn, cf.*
                      FROM {customfield_field} cf
                      JOIN {customfield_category} cc ON cf.categoryid = cc.id
                     WHERE $where
                  ORDER BY cf.id DESC";

            if ($record = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE)) {
                if ($instance = self::create($record)) {
                    self::$instances[$key] = $instance;
                    return $instance;
                }
            }
        }
        // Fallback: By default, we return the text controller.
        return new wbt_field_controller();
    }
}
