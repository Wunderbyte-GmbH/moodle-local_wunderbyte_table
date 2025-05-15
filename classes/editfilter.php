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

namespace local_wunderbyte_table;

use cache;
use coding_exception;
use dml_exception;
use local_wunderbyte_table\local\settings\tablesettings;
use MoodleQuickForm;

define('LOCAL_WUNDERBYTE_TABLE_FILTERCACHEKEY', 'wbfilter_');

define('LOCAL_WUNDERBYTE_TABLE_FILTERNAME', 'wbfiltername_');
define('LOCAL_WUNDERBYTE_TABLE_FILTERISACTIVE', 'wbfilterisactive_');

/**
 * Edit filter class.
 */
class editfilter {
    /**
     * This function will check for filter settings for the current user.
     * If there are non, it will return general settings for this table and if there are none...
     * ... it will take the standard settings from the class.
     * If there is no setting in the DB (should only happen once for every table), setting is written.
     * @param wunderbyte_table $table
     * @param string $cachekey
     * @param bool $fulltablesettings false by default, if true, then full tablesettings are returned.
     * @return array
     */
    public static function return_filtersettings(
        wunderbyte_table $table,
        string $cachekey,
        bool $fulltablesettings = false
    ): array {
        global $USER, $DB;

        // At this point, we know that we don't get the full filter for this user from Cache.
        // What we don't know yet is if this userfilter exists in DB. But we have checked that before.
        // And we can get the information from cache.

        $userspecifickey = $cachekey . '_' . $USER->id;
        $cache = cache::make($table->cachecomponent, $table->rawcachename);
        $filterjson = $cache->get($userspecifickey);

        $filtersettings = [];

        if ($filterjson === null) {
            // This means that a record exists and we have set the value for this key to null
            // This is distinct from a non existing key, which would return false.

            // Now we fetch the user specific filter columns from DB.
            $jsonstring = tablesettings::return_jsontablesettings_from_db(0, $cachekey, $USER->id);

            $tablesettings = json_decode($jsonstring, true);
            // For backwards compatibility, we also support only filtersettings.
            $filtersettings = $tablesettings['filtersettings'] ?? $tablesettings;
        } else {
            // At this point, we know that there is no user specific filter available.
            // There might be a general one in the DB.
            $jsonstring = tablesettings::return_jsontablesettings_from_db(0, $cachekey, 0);
            $tablesettings = json_decode($jsonstring, true);
            if (
                (get_config('local_wunderbyte_table', 'allowedittable'))
                && $DB->record_exists('local_wunderbyte_table', ['hash' => $cachekey, 'userid' => "0"])
            ) {
                // For backwards compatibility, we also support only filtersettings.
                $filtersettings = $tablesettings['filtersettings'] ?? $tablesettings;
            } else {
                // We have stored the columns to filter in the subcolumn "datafields".
                if (!isset($table->subcolumns['datafields'])) {
                    if ($fulltablesettings) {
                        $tablesettings['filtersettings'] = [];
                        return $tablesettings;
                    }
                    return [];
                }
                $filtersettings = $table->subcolumns['datafields'];

                // We return the filtersettings right away.
                if (!get_config('local_wunderbyte_table', 'allowedittable')) {
                    // If param $fulltablesettings is set to true, we return the full tablesettings.
                    if ($fulltablesettings) {
                        return $tablesettings;
                    }
                    // By default, we return the filter settings only.
                    return $filtersettings;
                }

                $tablesettings['filtersettings'] = $filtersettings;
                filter::save_settings(
                    $table,
                    $cachekey,
                    $tablesettings
                );
            }
        }
        // If param $fulltablesettings is set to true, we return the full tablesettings.
        if ($fulltablesettings) {
            return $tablesettings;
        }
        // By default, we return the filter settings only.
        return $filtersettings;
    }

    /**
     * See if we can get the userspecific or the general filter from cache.
     * This function will, if
     * @param wunderbyte_table $table
     * @param string $cachekey
     * @return mixed
     * @throws coding_exception
     */
    public static function get_userspecific_filterjson(wunderbyte_table $table, string $cachekey) {

        global $USER, $DB;

        $userspecifickey = $cachekey . '_' . $USER->id;
        $cache = cache::make($table->cachecomponent, $table->rawcachename);

        // First we see if the user has a user specific cache.

        $filterjson = $cache->get($userspecifickey);

        switch ($filterjson) {
            case false:
                // If user specific key did not exist, we still need to look in the DB.
                if (
                    get_config('local_wunderbyte_table', 'allowedittable')
                    && $DB->record_exists('local_wunderbyte_table', ['hash' => $cachekey, 'userid' => $USER->id])
                ) {
                    // If the key doesn't exist, it returns false. If only the key exists...
                    // ... it returns null.
                    $cache->set($userspecifickey, null);
                    return false;
                }

                $filterjson = $cache->get($cachekey);
                break;
            // Todo: There will be an additional case with null for user-specific keys.
        }

        return $filterjson;
    }
}
