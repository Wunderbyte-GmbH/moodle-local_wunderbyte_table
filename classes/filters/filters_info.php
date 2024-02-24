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
 * @copyright 2024 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\filters;

use coding_exception;
use dml_exception;
use local_wunderbyte_table\filter;
use local_wunderbyte_table\wunderbyte_table;
use MoodleQuickForm;
use stdClass;

/**
 * Handles the filter classes.
 * @package local_wunderbyte_table\filters
 */
class filters_info {

    /**
     * Handles form definition of filter classes.
     * @param MoodleQuickForm $mform
     * @param array $formdata
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function defintion(MoodleQuickForm &$mform, array &$formdata) {

        // Here, we retrieve tha filters array with all the possible filters.

        $filterobject = self::return_filter_object($formdata);

        $mform->addElement('advcheckbox',
                           'filterinactive',
                           '',
                           get_string('filterinactive', 'local_wunderbyte_table'));

        foreach ($filterobject->categories as $filter) {
            if (isset($filter->wbfilterclass)) {
                $classname = $filter->wbfilterclass;
                $classname::definition($mform, $formdata, $filter);
            }
        }
    }

    /**
     * This function runs through all installed field classes and executes the prepare save function.
     * Returns an array of warnings as string.
     * @param stdClass $formdata
     * @param stdClass $newdata
     * @param int $updateparam
     * @return array
     */
    public static function process_data(stdClass &$formdata, stdClass &$newdata): array {

        return [];
    }

    /**
     * Validation.
     * @param array $data
     * @param array $files
     * @param array $errors
     * @return void
     */
    public static function validation(array $data, array $files, array &$errors) {

    }

    /**
     * Set data for all filters.
     * @param stdClass $data
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function set_data(stdClass &$data) {

        // Here, we retrive the actually stored filter array.
        // And fill in the data form.
        $filterobject = self::return_filter_object((array)$data);

        $data->filterinactive = $filterobject->filterinactive;

        foreach ($filterobject->categories as $filter) {
            if (isset($filter->wbfilterclass)) {
                $classname = $filter->wbfilterclass;
                $classname::set_data($data, $filter);
            }
        }

    }

    private static function return_filter_object(array $formdata) {
        $encodedtable = $formdata['encodedtable'];

        $table = wunderbyte_table::instantiate_from_tablecache_hash($encodedtable);
        filter::create_filter($table);

        return json_decode($table->filterjson);
    }
}
