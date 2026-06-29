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

namespace local_wunderbyte_table\local\customfield\field\dynamicformat;

// Important: Use the field controller for the right customfield.
use customfield_dynamicformat\field_controller;
use local_wunderbyte_table\local\customfield\wbt_field_controller_base;
use context_system;

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
     * Request-level memo of dynamicsql resultsets, keyed by sha1 of the SQL.
     *
     * Without this, the (potentially expensive, admin-defined) dynamicsql was executed
     * once per rendered row/cell when resolving customfield values, causing a massive
     * N+1 on large tables. The SQL is static field configuration (no bound params, no
     * per-user state), so a single execution per request serves every lookup.
     * A null value marks a query that failed, so even broken SQL is not retried per row.
     *
     * @var array<string, array|null>
     */
    protected static array $resultsetcache = [];

    /**
     * Get the actual string value of the customfield by index.
     *
     * @param string|array|int|null $key
     * @param bool $formatstring
     * @param bool $keyisencoded
     * @return string the string value for the index
     */
    public function get_option_value_by_key(
        string|array|int|null $key,
        bool $formatstring = true,
        bool $keyisencoded = false
    ): string {
        if ($key === null) {
            return '';
        }
        // Moodle's DML rewrites {word} into a prefixed table name (see moodle_database::fix_table_names),
        // which would destroy a multilang2 closing tag embedded in the query result. Swap it for a
        // placeholder the DML regex ignores, then restore the real tag in the results below so
        // filter_multilang2 can process it.
        // Only the exact '{mlang}' is mangled by the DML regex; spaced variants (e.g. '{ mlang}')
        // already pass through untouched, so a plain str_replace is all that is needed.
        $mlangplaceholder = '@@MLANG_CLOSE@@';
        $dynamicsql = $this->get_configdata_property('dynamicsql') ?? '';
        $sql = str_replace('{mlang}', $mlangplaceholder, $dynamicsql);
        $records = self::fetch_dynamic_records($sql);
        if ($records === null) {
            if (is_array($key)) {
                return implode(', ', $key);
            }
            return $key;
        }
        foreach ($records as $record) {
            if (isset($record->data) && is_string($record->data)) {
                $record->data = str_replace($mlangplaceholder, '{mlang}', $record->data);
            }
        }
        if (
            (is_string($key) || is_int($key))
            && isset($records[$key])
        ) {
            $returnvalue = $records[$key]->data ?? $key;
            if ($formatstring) {
                $returnvalue = format_string($returnvalue, true, ['context' => context_system::instance()]);
            }
            return $returnvalue;
        } else if (
            is_array($key)
        ) {
            $returnvalues = [];
            foreach ($key as $k) {
                if (isset($records[$k])) {
                    $returnvalue = $records[$k]->data ?? $k;
                    if ($formatstring) {
                        $returnvalue = format_string($returnvalue, true, ['context' => context_system::instance()]);
                    }
                    if ($returnvalue !== '') {
                        $returnvalues[] = $returnvalue;
                    }
                } else {
                    if ($k !== '') {
                        $returnvalues[] = $k;
                    }
                }
            }
            return implode(', ', $returnvalues);
        } else {
            return $key;
        }
    }

    /**
     * Get an array containing all key value pairs for the customfield.
     * Depending on the type, these can be actually used values or possible values.
     *
     * @return array an array containing all key value pairs for the customfield
     */
    public function get_values_array(): array {
        $sql = $this->get_configdata_property('dynamicsql');
        $records = self::fetch_dynamic_records($sql);
        if ($records === null) {
            return [];
        }

        return $records;
    }

    /**
     * Fetch (and memoize for the duration of the request) the resultset of a dynamicsql.
     *
     * The same SQL is executed at most once per request, no matter how many rows or cells
     * reference this customfield, eliminating the per-row N+1.
     *
     * @param string $sql the configured dynamicsql
     * @return array|null records keyed by their first column, or null if the query failed
     */
    protected static function fetch_dynamic_records(string $sql): ?array {
        global $DB;
        $cachekey = sha1($sql);
        if (!array_key_exists($cachekey, self::$resultsetcache)) {
            try {
                self::$resultsetcache[$cachekey] = $DB->get_records_sql($sql);
            } catch (\Throwable $th) {
                self::$resultsetcache[$cachekey] = null;
            }
        }
        return self::$resultsetcache[$cachekey];
    }
}
