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

use local_wunderbyte_table\wunderbyte_table;
use MoodleQuickForm;

/**
 * Handles the filter classes.
 * @package local_wunderbyte_table
 */
class filter_form_operator {
    /**
     * Handles form definition of filter classes.
     * @param MoodleQuickForm $mform
     * @param array $formdata
     * @return void
     */
    public static function generate_form(MoodleQuickForm &$mform, array &$formdata) {
        $encodedtable = $formdata['encodedtable'];
        $mform->addElement('hidden', 'encodedtable', json_encode($encodedtable));
        self::set_filter_columns($mform, $formdata);

        $mform->addElement(
            'html',
            '<div id="filter-edit-fields"></div>'
        );
        $mform->addElement(
            'html',
            '<div id="filter-add-field"></div>'
        );
    }

    /**
     * Handles form definition of filter classes.
     * @param MoodleQuickForm $mform
     * @param array $formdata
     */
    public static function set_filter_columns(MoodleQuickForm &$mform, $formdata) {
        $encodedtable = $formdata['encodedtable'];
        $table = wunderbyte_table::instantiate_from_tablecache_hash($encodedtable);
        $filterablecolumns = $table->subcolumns['datafields'];
        $options = filter_manager::get_all_filter_columns($filterablecolumns);
        if ($options) {
            $mform->addElement(
                'select',
                'filter_columns',
                get_string('setwbtablefiltercolumn', 'local_wunderbyte_table'),
                $options
            );
            $mform->setType('filter_columns', PARAM_INT);
        }
    }

    /**
     * Handles form definition of filter classes.
     * @param MoodleQuickForm $mform
     * @param string $default
     */
    public static function set_filter_types(MoodleQuickForm &$mform, $default = '') {
        $options = filter_manager::get_all_filter_types();
        if ($options) {
            $mform->addElement(
                'select',
                'filter_options',
                get_string('setwbtablefiltertype', 'local_wunderbyte_table'),
                $options
            );
            $mform->setType('filter_options', PARAM_INT);
            if ($default !== '' && array_key_exists($default, $options)) {
                $mform->setDefault('filter_options', $default);
            }
        }
    }

    /**
     * Validation.
     * @param MoodleQuickForm $mform
     * @param array $submitteddata
     */
    public static function persist_input_values($mform, $submitteddata) {
        $peristingvalue = 'filter_columns';
        $filtercolumn = $submitteddata[$peristingvalue];

        if (!empty($filtercolumn)) {
            if (!empty($submitteddata[$peristingvalue])) {
                $mform->getElement($peristingvalue)->setValue($submitteddata[$peristingvalue]);
            }

            $submitteddata['filtercolumn'] = $submitteddata['filter_columns'];
            $columnmanager = new column_manager($submitteddata);
            $filteredcolumnform = $columnmanager->get_filtered_column_form();
            $errors = $columnmanager->get_data_validation($submitteddata);

            foreach ($filteredcolumnform->_elements as $element) {
                foreach ($element->_elements as $groupelement) {
                    $mform->addElement($element);
                }
            }

            $filtertype = $columnmanager->get_filter_settings_of_column($filtercolumn);
            if ($filtertype) {
                $mandatoryfields = filter_manager::get_mandetory_filter_fields($filtertype['wbfilterclass']);
                foreach ($mandatoryfields->_elements as $element) {
                    $mform->addElement($element);
                }
            }
        }
    }

    /**
     * Validation.
     * @param \MoodleQuickForm $mform
     * @param string $dynamichtml
     */
    private static function set_dynamic_fields_inside_div(&$mform, $dynamichtml, $divid) {
        foreach ($mform->_elements as $element) {
            if (
                $element->_type === 'html' &&
                isset($element->_text) &&
                strpos($element->_text, $divid) !== false
            ) {
                if ($divid == 'filter-add-field') {
                    $element->_text .= '<div id="' . $divid . '"> ' . $dynamichtml . '</div>';
                } else {
                    $element->_text = '<div id="' . $divid . '"> ' . $dynamichtml . '</div>';
                }
                break;
            }
        }
    }
}
