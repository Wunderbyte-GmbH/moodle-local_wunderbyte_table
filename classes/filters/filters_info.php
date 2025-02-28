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
use local_wunderbyte_table\editfilter;
use local_wunderbyte_table\filter;
use local_wunderbyte_table\local\settings\tablesettings;
use local_wunderbyte_table\wunderbyte_table;
use MoodleQuickForm;
use stdClass;

/**
 * Handles the filter classes.
 * @package local_wunderbyte_table
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

        $encodedtable = $formdata['encodedtable'];
        $mform->addElement('hidden', 'encodedtable', json_encode($encodedtable));
        $table = wunderbyte_table::instantiate_from_tablecache_hash($encodedtable);

        // We need to localize the filter for every user.
        $lang = filter::current_language();
        $key = $table->tablecachehash . $lang . '_filterjson';

        // We want the live and uncached datafields.
        $tablesettings = tablesettings::return_initial_settings($table);
        $filterobjects = $tablesettings['filtersettings'];

        foreach ($filterobjects as $key => $filter) {
            if ($key === 'id') {
                $mform->addElement(
                    'advcheckbox',
                    'id_wb_checked',
                    '',
                    get_string('showfilter', 'local_wunderbyte_table')
                );

                // We save the filterobject as we get it here.
                $mform->addElement('hidden', 'wb_jsontablesettings', json_encode($tablesettings));
            } else {
                $classname = $filter['wbfilterclass'];
                $filter['columnidentifier'] = $key;
                $classname::definition($mform, $formdata, (object)$filter);
            }
        }
    }

    /**
     * Save data from field.
     * @param stdClass $formdata
     * @param stdClass $newdata
     * @return array
     */
    public static function process_data(stdClass &$formdata, stdClass &$newdata): array {

        // This is implemented in the tablesettings class.

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
     * @param wunderbyte_table $table
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function set_data(stdClass &$data, wunderbyte_table $table) {

        // Here, we retrive the actually stored filter array.
        // And fill in the data form.

        // We need to localize the filter for every user.
        $lang = filter::current_language();
        $key = $table->tablecachehash . $lang . '_filterjson';

        $filtersettings = editfilter::return_filtersettings($table, $key);

        foreach ($filtersettings as $key => $filter) {
            if ($key === 'id') {
                $data->id_wb_checked = $filter['id_wb_checked'] ?? 0;
            } else if (isset($filter['wbfilterclass'])) {
                $classname = $filter['wbfilterclass'];
                $filter['columnidentifier'] = $key;
                $classname::set_data($data, (object)$filter);
            }
        }
    }
}
