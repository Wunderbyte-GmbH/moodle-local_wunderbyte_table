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
        self::set_filter_name_field($mform);
        self::set_filter_types($mform);
        $mform->addElement(
            'html',
            '<div id="filter-mandatory-fields"></div>'
        );
    }

    /**
     * Handles form definition of filter classes.
     * @param MoodleQuickForm $mform
     */
    public static function set_filter_name_field(MoodleQuickForm &$mform) {
        $mform->addElement(
            'text',
            'new_filter_name',
            get_string('newwbtablefiltername', 'local_wunderbyte_table'),
            ['size' => '60'],
        );
        $mform->setType('new_filter_name', PARAM_TEXT);
    }

    /**
     * Handles form definition of filter classes.
     * @param MoodleQuickForm $mform
     */
    public static function set_filter_types(MoodleQuickForm &$mform) {
        $options = filter_manager::get_all_filter_types();
        $mform->addElement(
            'select',
            'filter_options',
            get_string('setwbtablefiltertype', 'local_wunderbyte_table'),
            $options
        );
        $mform->setType('filter_options', PARAM_INT);
    }

    /**
     * Validation.
     * @param array $data
     * @return array
     */
    public static function validation(array $data) {
        return filter_manager::get_data_validation($data);
    }

    /**
     * Validation.
     * @param array $data
     */
    public static function persist_input_values($mform, $submitteddata) {
        $peristingvalues = [
            'new_filter_name',
            'filter_options',
        ];
        $filtertype = $submitteddata['filter_options'];
        if (!empty($filtertype)) {
            foreach ($peristingvalues as $peristingvalue) {
                if (!empty($submitteddata[$peristingvalue])) {
                    $mform->getElement($peristingvalue)->setValue($submitteddata[$peristingvalue]);
                }
            }

            $mandatoryfields = filter_manager::get_mandetory_filter_fields($filtertype);
            $errors = self::validation($submitteddata);
            filter_manager::set_peristing_values($mandatoryfields, $submitteddata, $errors);

            $dynamichtml = $mandatoryfields->toHtml();
            self::set_dynamic_fields_inside_div($mform, $dynamichtml);
        }
    }

    /**
     * Validation.
     * @param \MoodleQuickForm $mform
     */
    private static function set_dynamic_fields_inside_div(&$mform, $dynamichtml) {
        foreach ($mform->_elements as $element) {
            if (
                $element->_type === 'html' &&
                isset($element->_text) &&
                strpos($element->_text, 'filter-mandatory-fields') !== false
            ) {
                $element->_text = '<div id="filter-mandatory-fields"> ' . $dynamichtml . '</div>';
                break;
            }
        }
    }
}
