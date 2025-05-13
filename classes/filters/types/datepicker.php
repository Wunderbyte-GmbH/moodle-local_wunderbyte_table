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

use local_wunderbyte_table\filters\types\datepickers\datepicker_factory;
use stdClass;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
class datepicker extends datepickerbase {
    /**
     * The expected value.
     * @param \MoodleQuickForm $mform
     * @param array $data
     * @param string $filterspecificvalue
     */
    public static function render_mandatory_fields(&$mform, $data = null, $filterspecificvalue = '') {
        if ($data === null) {
            $data = self::$newkeyvalue;
        }

        $horizontallinecounter = 0;
        foreach ($data as $filterlabel => $filtertype) {
            if (empty($filterlabel) && count($data) != 1) {
                continue;
            }
            if (!empty($filterlabel)) {
                self::add_date_filter_head($mform, $filterlabel, $horizontallinecounter);
            }

            $valuelabel = 'datepicker[' . $filterlabel . ']';

            if ($filterlabel == 0) {
                self::add_type_dropdown($mform, $filtertype['datepicker_type'] ?? $filterspecificvalue);
                $inputs = datepicker_factory::set_input(
                    $mform,
                    $valuelabel,
                    $filterspecificvalue
                );
            } else {
                $inputs = datepicker_factory::get_and_set_input($mform, $valuelabel, $filtertype);
            }

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
    public static function set_span_filter_input(&$mform, $valuelabel) {
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

        $possibleoperationsinput = $mform->createElement(
            'select',
            $valuelabel . '[possibleoperations]',
            '',
            self::get_operatoroptions(),
            ['multiple' => 'multiple']
        );

        $defaultvaluestartinput = $mform->createElement(
            'date_selector',
            $valuelabel  . '[defaultvaluestart]',
            'from'
        );

        $defaultvalueendinput = $mform->createElement(
            'date_selector',
            $valuelabel  . '[defaultvalueend]',
            0
        );

        return [
            $mform->createElement(
                'static',
                'datepickerheadingname',
                '',
                '<br><label>' . get_string('datepickerheadingname', 'local_wunderbyte_table') . '</label>'
            ),
            $nameinput,
            $mform->createElement(
                'static',
                'datepickerheadingcheckboxlabel',
                '',
                '<br><label>' . get_string('datepickerheadingcheckboxlabel', 'local_wunderbyte_table') . '</label>'
            ),
            $checkboxlabelinput,
            $mform->createElement(
                'static',
                'datepickerheadingpossibleoperations',
                '',
                '<br><label>' . get_string('datepickerheadingpossibleoperations', 'local_wunderbyte_table') . '</label>'
            ),
            $possibleoperationsinput,
            $mform->createElement(
                'static',
                'datepickerheadingdefaultvaluestart',
                '',
                '<br><label>' . get_string('datepickerheadingdefaultvaluestart', 'local_wunderbyte_table') . '</label>'
            ),
            $defaultvaluestartinput,
            $mform->createElement(
                'static',
                'datepickerheadingdefaultvalueend',
                '',
                '<br><label>' . get_string('datepickerheadingdefaultvalueend', 'local_wunderbyte_table') . '</label>'
            ),
            $defaultvalueendinput,
        ];
    }

    /**
     * The expected value.
     * @param \MoodleQuickForm $mform
     * @param string $valuelabel
     * @param array $filtertype
     */
    public static function set_span_default_value_input(&$mform, $valuelabel, $filtertype) {
        $mform->setDefault(
            $valuelabel . '[name]',
            $filtertype['name'] ?? ''
        );
        $mform->setDefault(
            $valuelabel . '[checkboxlabel]',
            $filtertype['checkboxlabel'] ?? ''
        );
        $mform->setDefault(
            $valuelabel . '[possibleoperations]',
            self::get_operatoroptions_index($filtertype['possibleoperations'] ?? [])
        );
        $mform->setDefault(
            $valuelabel . '[defaultvaluestart]',
            $filtertype['defaultvaluestart'] ?? time()
        );
        $mform->setDefault(
            $valuelabel . '[defaultvalueend]',
            $filtertype['defaultvalueend'] ?? time()
        );
    }

    /**
     * The expected value.
     * @param array $data
     * @return array
     */
    public static function validate_input($data) {
        $errors = [];
        foreach ($data['datepicker'] as $datepickername => $datepickerinput) {
            $errormsg = '';
            if (
                empty($datepickerinput['name'])  &&
                (
                    !empty($datepickername) ||
                    !empty($datepickerinput['checkboxlabel'])
                )
            ) {
                $errormsg .= get_string('datepickererrormandatory', 'local_wunderbyte_table');
            }
            if (
                isset($datepickerinput['operator']) &&
                $datepickerinput['operator'] == '"_qf__force_multiselect_submission"'
            ) {
                $errormsg .= get_string('datepickererroroperations', 'local_wunderbyte_table');
            }
            if (!empty($errormsg)) {
                $errors[$datepickername . '_group'] = $errormsg;
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
                if (isset($keyvaluepair['operator'])) {
                    $datepickerfilter->$name = (object) [
                        'checkboxlabel' => $keyvaluepair['checkboxlabel'],
                        'operator' => $keyvaluepair['operator'],
                        'defaultvalue' => self::get_timestamp($keyvaluepair['defaultvalue']),
                    ];
                } else {
                    $datepickerfilter->$name = (object) [
                        'checkboxlabel' => $keyvaluepair['checkboxlabel'],
                        'columntimestart' => 'startdate',
                        'columntimeend' => 'enddate',
                        'labelstartvalue' => 'Timespan',
                        'labelendvalue' => 'enddate',
                        'defaultvaluestart' => self::get_timestamp($keyvaluepair['defaultvaluestart']),
                        'defaultvalueend' => self::get_timestamp($keyvaluepair['defaultvalueend']),
                        'possibleoperations' => self::get_operatoroptions_name($keyvaluepair['possibleoperations']),
                    ];
                }
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

    /**
     * The expected value.
     * @param array $moodleformdate
     * @return int
     */
    public static function get_timestamp($moodleformdate) {
        return mktime(
            0,
            0,
            0,
            $moodleformdate['month'],
            $moodleformdate['day'],
            $moodleformdate['year']
        );
    }

    /**
     * The expected value.
     * @param string $timestamp
     * @return array
     */
    public static function get_moodle_form_date($timestamp) {
        $moodleformdate = date_parse_from_format('Y-m-d', date('Y-m-d', $timestamp));

        return [
            'day' => $moodleformdate['day'],
            'month' => $moodleformdate['month'],
            'year' => $moodleformdate['year'],
        ];
    }

    /**
     * The expected value.
     * @param array $selectedoptions
     * @return array
     */
    public static function get_operatoroptions_name($selectedoptions) {
        $possibleoperations = self::get_operatoroptions();
        $possibleoperationsname = [];

        foreach ($selectedoptions as $value) {
            $possibleoperationsname[] = $possibleoperations[$value];
        }
        return $possibleoperationsname;
    }

    /**
     * The expected value.
     * @param array $selectedoptions
     * @return array
     */
    public static function get_operatoroptions_index($selectedoptions) {
        if (is_null($selectedoptions)) {
            $selectedoptions = [];
        }
        $possibleoperations = self::get_operatoroptions();
        $possibleoperationsindex = [];

        foreach ($possibleoperations as $index => $value) {
            if (in_array($value, $selectedoptions)) {
                $possibleoperationsindex[] = $index;
            }
        }
        return $possibleoperationsindex;
    }
}
