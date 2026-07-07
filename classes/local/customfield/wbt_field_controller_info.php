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

    /**
     * Returns the request cache used by get_resolved_value_mapping. As a MUC request cache it
     * has the lifetime of a static array but is automatically purged e.g. between unit tests.
     *
     * @return \cache
     */
    private static function get_resolved_value_mapping_cache(): \cache {
        return \cache::make_from_params(
            \cache_store::MODE_REQUEST,
            'local_wunderbyte_table',
            'resolvedvaluemappings'
        );
    }

    /**
     * Purges the request cache of get_resolved_value_mapping.
     *
     * @return void
     */
    public static function purge_resolved_value_mapping_cache(): void {
        self::get_resolved_value_mapping_cache()->purge();
    }

    /**
     * Returns a mapping of stored customfield values to the display values resolved by the
     * matching wbt_field_controller (e.g. select indexes to labels or dynamicformat keys to
     * the data returned by the configured SQL). Only values actually in use for the field and
     * only entries where the resolved value differs from the stored value are returned, so
     * text-like fields (identity resolution) yield an empty mapping.
     * The values are resolved without format_string, so they stay language-neutral (multilang
     * tags are kept) and can be used in cached queries shared by users of different languages.
     *
     * @param string $shortname shortname of a customfield
     * @param string $component optional component to filter by (e.g. 'mod_booking')
     * @param string $area optional area to filter by (e.g. 'booking')
     * @return array [storedvalue => resolvedvalue], empty if nothing resolves differently
     */
    public static function get_resolved_value_mapping(string $shortname, string $component = '', string $area = ''): array {
        global $DB;

        $cache = self::get_resolved_value_mapping_cache();
        $cachekey = "{$component}-{$area}-{$shortname}";
        $cached = $cache->get($cachekey);
        if ($cached !== false) {
            return $cached;
        }

        $fieldcontroller = self::get_instance_by_shortname($shortname, $component, $area);
        // The fallback text controller is created without a field record and has no id.
        $fieldid = $fieldcontroller instanceof \core_customfield\field_controller
            ? (int)$fieldcontroller->get('id')
            : 0;
        if (empty($fieldid)) {
            // Also cache columns without a matching customfield, so repeated lookups are free.
            $cache->set($cachekey, []);
            return [];
        }

        $mapping = [];
        $usedvalues = $DB->get_fieldset_sql(
            "SELECT DISTINCT value FROM {customfield_data} WHERE fieldid = :fieldid",
            ['fieldid' => $fieldid]
        );
        foreach ($usedvalues as $storedvalue) {
            if ($storedvalue === null || $storedvalue === '') {
                continue;
            }
            // Multiselect values are stored as json arrays or comma separated lists
            // (e.g. dynamicformat, see its data_controller::get_value): resolve each key.
            $key = $storedvalue;
            $decoded = json_decode($storedvalue);
            if (is_array($decoded)) {
                $key = $decoded;
            } else if (strpos($storedvalue, ',') !== false) {
                $key = explode(',', $storedvalue);
            }
            $resolvedvalue = $fieldcontroller->get_option_value_by_key($key, false);
            if ($resolvedvalue !== '' && $resolvedvalue !== $storedvalue) {
                $mapping[$storedvalue] = $resolvedvalue;
            }
        }
        // Safety cap so admin-defined lookups with huge resultsets cannot blow up the query params.
        if (count($mapping) > 500) {
            debugging(
                "Too many distinct values for customfield '$shortname', resolved values are not available.",
                DEBUG_DEVELOPER
            );
            $mapping = [];
        }
        $cache->set($cachekey, $mapping);
        return $mapping;
    }

    /**
     * Returns select part, join and params to add a column containing the display values
     * resolved by the matching wbt_field_controller to a query which already selects the
     * stored customfield value. The mapping is joined as a derived table instead of using
     * a CASE expression in the select, so the select stays free of params (it might be
     * reused e.g. in a group by) and the resolved column can be used like a plain column,
     * e.g. as fulltext search column of a wunderbyte table.
     *
     * @param string $shortname shortname of a customfield
     * @param string $valuecolumn sql expression of the column holding the stored value (e.g. "cfd1.value")
     * @param string $columnalias alias for the resolved column (e.g. "sport_resolved")
     * @param string $tablealias unique alias for the joined mapping table, also used as param prefix (e.g. "cfr1")
     * @param string $component optional component to filter by (e.g. 'mod_booking')
     * @param string $area optional area to filter by (e.g. 'booking')
     * @return array [$select, $join, $params] - empty strings and array if there is nothing to resolve
     */
    public static function return_sql_for_resolved_value(
        string $shortname,
        string $valuecolumn,
        string $columnalias,
        string $tablealias,
        string $component = '',
        string $area = ''
    ): array {
        global $DB;

        $mapping = self::get_resolved_value_mapping($shortname, $component, $area);
        if (empty($mapping)) {
            return ['', '', []];
        }

        $params = [];
        $unionparts = [];
        $i = 0;
        foreach ($mapping as $storedvalue => $resolvedvalue) {
            $castkey = $DB->sql_cast_to_char(":{$tablealias}k{$i}");
            $castvalue = $DB->sql_cast_to_char(":{$tablealias}v{$i}");
            $unionparts[] = "SELECT $castkey AS k, $castvalue AS v";
            $params["{$tablealias}k{$i}"] = $storedvalue;
            $params["{$tablealias}v{$i}"] = $resolvedvalue;
            $i++;
        }
        $select = "$tablealias.v as $columnalias";
        $join = " LEFT JOIN (" . implode(" UNION ALL ", $unionparts) . ") $tablealias
                ON $tablealias.k = " . $DB->sql_compare_text($valuecolumn, 1333) . " ";

        return [$select, $join, $params];
    }
}
