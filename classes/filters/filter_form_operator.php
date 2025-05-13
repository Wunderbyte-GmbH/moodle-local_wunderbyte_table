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

use Exception;
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

        $columnmanager = new column_manager($formdata, $formdata['encodedtable']);
        $columnmanager->set_filter_columns($mform);

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
     * Validation.
     * @param MoodleQuickForm $mform
     * @param array $submitteddata
     */
    public static function persist_input_values(&$mform, $submitteddata) {
        $peristingvalue = 'filter_columns';
        $filtercolumn = $submitteddata[$peristingvalue];

        if (!empty($filtercolumn)) {
            if (!empty($submitteddata[$peristingvalue])) {
                $mform->getElement($peristingvalue)->setValue($submitteddata[$peristingvalue]);
            }

            $submitteddata['filtercolumn'] = $submitteddata['filter_columns'];

            $columnmanager = new column_manager($submitteddata, $submitteddata['encodedtable']);
            $filteredcolumnform = $columnmanager->get_filtered_column_form_persist_error();

            $validationmanager = new validation_manager($submitteddata, $submitteddata['encodedtable']);
            $errors = $validationmanager->get_data_validation();
            $validationmanager->set_errors($errors, $filteredcolumnform);

            self::render_forms_to_html($filteredcolumnform);
            self::set_dynamic_fields_inside_div($mform, $filteredcolumnform);
        }
    }

    /**
     * Validation.
     * @param array $filteredcolumnform
     */
    private static function render_forms_to_html(&$filteredcolumnform) {
        foreach ($filteredcolumnform as $key => $form) {
            try {
                $filteredcolumnform[$key] = $form->toHtml();
            } catch (Exception $e) {
                $filteredcolumnform[$key] = 'ERROR';
            }
        }
    }

    /**
     * Validation.
     * @param \MoodleQuickForm $mform
     * @param array $dynamicforms
     */
    private static function set_dynamic_fields_inside_div(&$mform, $dynamicforms) {
        foreach ($mform->_elements as $element) {
            if (
                $element->_type === 'html'
            ) {
                if (strpos($element->_text, 'filter-edit-fields')) {
                        $element->_text = $dynamicforms['filtereditfields'];
                } else if (strpos($element->_text, 'filter-add-field')) {
                    $element->_text = $dynamicforms['filteraddfields'];
                }
            }
        }
    }
}
