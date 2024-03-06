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
use MoodleQuickForm;

define('LOCAL_WUNDERBYTE_TABLE_FILTERCACHEKEY', 'wbfilter_');

define('LOCAL_WUNDERBYTE_TABLE_FILTERNAME', 'wbfiltername_');
define('LOCAL_WUNDERBYTE_TABLE_FILTERISACTIVE', 'wbfilterisactive_');

/**
 * Wunderbyte table class is an extension of table_sql.
 */
class editfilter {

    /**
     * Adds the elements to the form.
     * @param MoodleQuickForm $mform
     * @return void
     */
    public static function definition(MoodleQuickForm &$mform) {

        // Get ID.
        $mform->addElement('hidden', 'id');

    }

    /**
     * Adds the correct data to the form.
     * @param local_wunderbyte_table\stdClass $data
     * @return void
     * @throws dml_exception
     */
    public static function set_data(stdClass &$data) {

        $id = $data->id;
        $userid = $data->userid;

        $filterjson = self::return_filterjson($id, $userid);

        $filterobject = json_decode($filterjson);

        foreach ($filterobject->categories as $category) {

            $key = LOCAL_WUNDERBYTE_TABLE_FILTERNAME . $category['columnname'];
            $data->{$key} = $category['name'];

        }

    }

    /**
     * Adds the correct mform elements after data is set.
     * @param MoodleQuickForm $mform
     * @param array $formdata
     * @return void
     * @throws coding_exception
     */
    public static function definition_after_data(MoodleQuickForm &$mform, array $formdata) {

        $keys = preg_grep('/^' . LOCAL_WUNDERBYTE_TABLE_FILTERNAME . '/', array_keys($formdata));

        foreach ($keys as $key) {

            list($identifier, $index) = explode('_', $key);

            $isactivekey = LOCAL_WUNDERBYTE_TABLE_FILTERISACTIVE . $index;
            $mform->addElement('checkbox', $isactivekey, get_string('filterisactive', 'local_wunderbyte_table'));
            $mform->addElement('text', $key, get_string('filtername', 'local_wunderbyte_table'));
        }
    }

    /**
     * Find filterjson for user. This is the cached function.
     * If a userid is transmitted...
     * ... we first look for an individual setting for the user.
     * If there is no individual setting for the specific user, we fall back to the general one.
     * If the userid is precisely 0, we always get the general one.
     * @param int $id
     * @param int $userid
     * @return mixed
     * @throws dml_exception
     */
    public static function return_filterjson(int $id, int $userid = -1) {

        return self::return_filterjson_from_db($id, $userid);
    }

    /**
     * Find filterjson for user. If a userid is transmitted...
     * ... we first look for an individual setting for the user.
     * If there is no individual setting for the specific user, we fall back to the general one.
     * If the userid is precisely 0, we always get the general one.
     * @param int $id
     * @param string $hash
     * @param int $userid
     * @return string
     * @throws dml_exception
     */
    public static function return_filterjson_from_db(int $id = 0, string $hash = '', int $userid = -1) {

        global $DB;

        if (empty(get_config('local_wunderbyte_table', 'savesettingstodb'))) {
            return '{}';
        }

        // When the userid is 0, this is the general setting.
        $searcharray = [0];
        if ($userid > 0) {
            $searcharray[] = $userid;

            $orderby = "ORDER BY userid DESC
                        LIMIT 1";
        } else {
            $orderby = '';
        }

        list($inorequal, $params) = $DB->get_in_or_equal($searcharray, SQL_PARAMS_NAMED);

        if (!empty($hash)) {
            $params['hash'] = $hash;
            $where = "hash = :hash";
        } else if (!empty($id)) {
            $params['id'] = $id;
            $where = "id = :id";
        }

        $sql = "SELECT jsonstring
                FROM {local_wunderbyte_table}
                WHERE userid $inorequal AND $where
                $orderby ";

        $json = $DB->get_field_sql($sql, $params);

        return $json ?? '{}';
    }

    /**
     * This function will check for filter settings for the current user.
     * If there are non, it will return general settings for this table and if there are none...
     * ... it will take the standard settings from the class.
     * If there is no setting in the DB (should only happen once for every table), setting is written.
     * @param wunderbyte_table $table
     * @param string $cachekey
     * @return array
     */
    public static function return_filtersettings(wunderbyte_table $table, string $cachekey) {

        global $USER, $DB, $PAGE;

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
            $jsonstring = self::return_filterjson_from_db(0, $table->idstring, $USER->id);

            $filtersettings = json_decode($jsonstring);
        } else {
            // At this point, we know that there is no user specific filter available.
            // There might be a general one in the DB.
            if ((get_config('local_wunderbyte_table', 'savesettingstodb'))
                && $DB->record_exists('local_wunderbyte_table', ['hash' => $cachekey, 'userid' => "0"])) {
                $jsonstring = self::return_filterjson_from_db(0, $cachekey, 0);
                $filtersettings = json_decode($jsonstring, true);
            } else {
                // We have stored the columns to filter in the subcolumn "datafields".
                if (!isset($table->subcolumns['datafields'])) {
                    return '';
                }
                $filtersettings = $table->subcolumns['datafields'];

                // We return the filtersettings right away.
                if (empty(get_config('local_wunderbyte_table', 'savesettingstodb'))) {
                    return $filtersettings;
                }

                filter::save_settings($table,
                                      $cachekey,
                                      $filtersettings);
            }
        }

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
        $cache = \cache::make($table->cachecomponent, $table->rawcachename);

        // First we see if the user has a user specific cache.

        $filterjson = $cache->get($userspecifickey);

        switch ($filterjson) {
            case false:
                // If user specific key did not exist, we still need to look in the DB.
                if (get_config('local_wunderbyte_table', 'savesettingstodb')
                    && $DB->record_exists('local_wunderbyte_table', ['hash' => $cachekey, 'userid' => $USER->id])) {
                    // If the key doesn't exist, it returns false. If only the key exists...
                    // ... it returns null.
                    $cache->set($userspecifickey, null);
                    return false;
                }

                $filterjson = $cache->get($cachekey);
                break;
            // TODO: There will be an additional case with null for user-specific keys.
        }

        return $filterjson;

    }
}
