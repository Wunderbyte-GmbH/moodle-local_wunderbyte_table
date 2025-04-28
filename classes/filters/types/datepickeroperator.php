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

namespace local_wunderbyte_table\filters\types;
use stdClass;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
class datepickeroperator extends datepickerbase {
    /**
     * The expected value.
     * @param \MoodleQuickForm $mform
     * @param array $data
     */
    public static function render_mandatory_fields(&$mform, $data = null) {
        if ($data === null) {
            $data = self::$newkeyvalue;
        }
        $horizontallinecounter = 0;
        foreach ($data as $filterlabel => $filtertype) {
            if (empty($filterlabel) && !strpos($mform->_elements[0]->_text, 'filter-add-field')) {
                continue;
            }
            if (!empty($filterlabel)) {
                self::add_date_filter_head($mform, $filterlabel, $horizontallinecounter);
            } else {
                continue;
            }

            $valuelabel = 'datepicker[' . $filterlabel . ']';
            $inputs = self::set_date_filter_input($mform, $valuelabel);
            self::set_date_default_value_input($mform, $valuelabel, $filtertype);

            $mform->addGroup(
                $inputs,
                $filterlabel . '_group',
                '',
                ' ',
                false
            );
            $horizontallinecounter++;
            $mform->addElement('html', '</div>');
        }
    }

    /**
     * The expected value.
     * @param \MoodleQuickForm $mform
     * @param string $valuelabel
     * @return array
     */
    public static function set_date_filter_input(&$mform, $valuelabel) {
        $nameinput = $mform->createElement(
            'text',
            $valuelabel  . '[name]',
            '',
            ['placeholder' => get_string('datepickerplaceholdername', 'local_wunderbyte_table')]
        );

        $checkboxlabelinput = $mform->createElement(
            'text',
            $valuelabel  . '[checkboxlabel]',
            '',
            ['placeholder' => get_string('datepickerplaceholdercheckboxlabel', 'local_wunderbyte_table')]
        );

        $operatorinput = $mform->createElement(
            'select',
            $valuelabel . '[operator]',
            '',
            self::get_operators()
        );

        $defaultvalueinput = $mform->createElement(
            'date_selector',
            $valuelabel  . '[defaultvalue]',
            '',
        );

        return [
            $mform->createElement(
                'static',
                '',
                '',
                '<br><label>' . get_string('datepickerheadingname', 'local_wunderbyte_table') . '</label>'
            ),
            $nameinput,
            $mform->createElement(
                'static',
                '',
                '',
                '<br><label>' . get_string('datepickerheadingcheckboxlabel', 'local_wunderbyte_table') . '</label>'
            ),
            $checkboxlabelinput,
            $mform->createElement(
                'static',
                '',
                '',
                '<br><label>' . get_string('datepickerheadingoperation', 'local_wunderbyte_table') . '</label>'
            ),
            $operatorinput,
            $mform->createElement(
                'static',
                '',
                '',
                '<br><label>' . get_string('datepickerheadingdefaultvalue', 'local_wunderbyte_table') . '</label>'
            ),
            $defaultvalueinput,
        ];
    }

    /**
     * The expected value.
     * @param \MoodleQuickForm $mform
     * @param string $valuelabel
     * @param array $filtertype
     */
    public static function set_date_default_value_input(&$mform, $valuelabel, $filtertype) {
        $mform->setDefault(
            $valuelabel . '[name]',
            $filtertype['name'] ?? ''
        );
        $mform->setDefault(
            $valuelabel . '[checkboxlabel]',
            $filtertype['checkboxlabel'] ?? ''
        );
        $mform->setDefault(
            $valuelabel . '[operator]',
            $filtertype['operator'] ?? ''
        );
        $mform->setDefault(
            $valuelabel . '[defaultvalue]',
            $filtertype['defaultvalue'] ?? ''
        );
    }

    /**
     * The expected value.
     * @return array
     */
    public static function get_operators() {
        return [
            '=' => '=',
            '<' => '<',
            '>' => '>',
            '<=' => '<=',
            '>=' => '>=',
        ];
    }

    /**
     * The expected value.
     * @param array $data
     * @return array
     */
    public static function validate_input($data) {
        $errors = [];
        if (!empty($data['datepicker'])) {
            foreach ($data['datepicker'] as $datepickername => $datepickerinput) {
                if (
                    empty($datepickerinput['name'])  &&
                    (
                        !empty($datepickername) ||
                        !empty($datepickerinput['checkboxlabel'])
                    )
                ) {
                    $errors[$datepickername . '_group'] = get_string('datepickererrormandatory', 'local_wunderbyte_table');
                }
            }
        }
        return $errors;
    }

    /**
     * The expected value.
     * @param object $data
     * @param string $filtercolumn
     * @return array
     */
    public static function get_new_filter_values($data, $filtercolumn) {
        $datepickerfilter = new stdClass();
        foreach ($data->datepicker as $key => $keyvaluepair) {
            if (
                !empty($keyvaluepair['name'])
            ) {
                $name = $keyvaluepair['name'];
                $datepickerfilter->$name = (object) [
                    'checkboxlabel' => $keyvaluepair['checkboxlabel'],
                    'operator' => $keyvaluepair['operator'],
                    'defaultvalue' => $keyvaluepair['defaultvalue'],
                ];
            }
        }
        $filterenablelabel = $filtercolumn . '_wb_checked';
        $filterspecificvalues = [
            'localizedname' => $data->localizedname ?? '',
            $data->wbfilterclass => true,
            'datepicker' => $datepickerfilter,
            $filterenablelabel => $data->$filterenablelabel ?? '0',
            'wbfilterclass' => $data->wbfilterclass ?? '',
        ];
        return $filterspecificvalues;
    }
}
