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

use coding_exception;
use local_wunderbyte_table\filters\base;
use local_wunderbyte_table\wunderbyte_table;
use moodle_exception;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
abstract class datepickerbase extends base {
    /**
     *
     * @var string
     */
    public static $newkeyvalue = [
        0 => [0],
    ];

    /**
     *
     * @var string
     */
    public static $groupname = 'datepickergroup';
    /**
     * Get standard filter options.
     * @param wunderbyte_table $table
     * @param string $key
     * @return array
     */
    public static function get_data_for_filter_options(wunderbyte_table $table, string $key) {
        return [];
    }

    /**
     * Add the filter to the array.
     * @param array $filter
     * @param bool $invisible
     * @return void
     * @throws moodle_exception
     */
    public function add_filter(array &$filter, bool $invisible = false) {

        $options = [
            'localizedname' => $this->localizedstring,
            get_class($this) => true,
            'datepicker' => $this->options,
            $this->columnidentifier . '_wb_checked' => 1,
        ];
        $options['wbfilterclass'] = get_called_class();

        // We always need to make sure that id column is present.
        if (!isset($filter['id'])) {
            $filter['id'] = [
                'localizedname' => get_string('id', 'local_wunderbyte_table'),
                'id_wb_checked' => 1,
            ];
        }

        if (!isset($filter[$this->columnidentifier])) {
            $filter[$this->columnidentifier] = $options;
        } else {
            throw new moodle_exception(
                'filteridentifierconflict',
                'local_wunderbyte_table',
                '',
                $this->columnidentifier,
                'Every column can have only one filter applied'
            );
        }
    }

    /**
     * Add options.
     * @param string $type
     * @param string $operator
     * @param string $checkboxlabel
     * @param string $defaultvaluestart
     * @param string $defaultvalueend
     * @param array $possibleoperations
     * @return void
     * @throws moodle_exception
     * @throws coding_exception
     */
    public function add_options(
        string $type = 'standard',
        string $operator = '<',
        string $checkboxlabel = '',
        string $defaultvaluestart = '',
        string $defaultvalueend = '',
        array $possibleoperations = []
    ) {
        if (empty($possibleoperations)) {
            $possibleoperations = self::get_operatoroptions();
        }

        if (!in_array($operator, ['=', '<', '>', '<=', '>='])) {
            throw new moodle_exception('novalidoperator', 'local_wunderbyte_table');
        }

        $filter = [
            'checkboxlabel' => !empty($checkboxlabel) ? $checkboxlabel : get_string('apply_filter', 'local_wunderbyte_table'),
        ];

        switch ($type) {
            case 'standard':
                $filter['operator'] = $operator;
                $filter['defaultvalue'] = !empty($defaultvaluestart) ? $defaultvaluestart : 'now';
                break;
            case 'in between':
                $filter['columntimestart'] = $this->columnidentifier;
                $filter['columntimeend'] = $this->secondcolumnidentifier;
                $filter['labelstartvalue'] = $this->localizedstring;
                $filter['labelendvalue'] = $this->secondcolumnlocalized;
                $filter['defaultvaluestart'] = $defaultvaluestart;
                $filter['defaultvalueend'] = $defaultvalueend;
                $filter['possibleoperations'] = $possibleoperations;

                break;
            default:
                throw new moodle_exception('unsupportedfiltertype', 'local_wunderbyte_table');
        }

        $this->options[$this->localizedstring] = $filter;
    }

    /**
     * Adds the array for the mustache template to render the categoryobject.
     * If no special treatment is needed, it must be implemented in the filter class, but just return.
     * The standard filter will take care of it.
     * @param array $categoryobject
     * @param array $filtersettings
     * @param string $fckey
     * @param array $values
     * @return void
     */
    public static function add_to_categoryobject(array &$categoryobject, array $filtersettings, string $fckey, array $values) {
        if (!isset($filtersettings[$fckey][get_called_class()])) {
            return;
        }

        $datepickerarray = $filtersettings[$fckey];

        foreach ($datepickerarray['datepicker'] as $labelkey => $object) {
            if (!isset($object['columntimestart'])) {
                $defaulttimestamp = $datepickerarray['datepicker'][$labelkey]['defaultvalue'];

                $datepickerobject = [
                    'label' => $labelkey,
                    'operator' => $datepickerarray['datepicker'][$labelkey]['operator'],
                    'timestamp' => $defaulttimestamp,
                    'datereadable' => $defaulttimestamp === 'now' ? 'now' : date('Y-m-d', $defaulttimestamp),
                    'timereadable' => $defaulttimestamp === 'now' ? 'now' : date('H:i', $defaulttimestamp),
                    'checkboxlabel' => $datepickerarray['datepicker'][$labelkey]['checkboxlabel'],
                ];
            } else { // Inbetween Filter applied.
                // Prepare the array for output.
                if (empty($datepickerarray['datepicker'][$labelkey]['possibleoperations'])) {
                    $datepickerarray['datepicker'][$labelkey]['possibleoperations'] = self::get_operatoroptions();
                }
                $operationsarray = array_map(fn($y) => [
                    'operator' => $y,
                    'label' => get_string($y, 'local_wunderbyte_table'),
                ], $datepickerarray['datepicker'][$labelkey]['possibleoperations']);

                $datepickerobject = [
                    'label' => $labelkey,
                    'startcolumn' => $datepickerarray['datepicker'][$labelkey]['columntimestart'],
                    'starttimestamp' => $datepickerarray['datepicker'][$labelkey]['defaultvaluestart'],
                    'startdatereadable' => $datepickerarray['datepicker'][$labelkey]['defaultvaluestart'],
                    'starttimereadable' => $datepickerarray['datepicker'][$labelkey]['defaultvaluestart'],
                    'endcolumn' => $datepickerarray['datepicker'][$labelkey]['columntimeend'],
                    'endtimestamp' => $datepickerarray['datepicker'][$labelkey]['defaultvalueend'],
                    'enddatereadable' => $datepickerarray['datepicker'][$labelkey]['defaultvalueend'],
                    'endtimereadable' => $datepickerarray['datepicker'][$labelkey]['defaultvalueend'],
                    'checkboxlabel' => $datepickerarray['datepicker'][$labelkey]['checkboxlabel'],
                    'possibleoperations' => $operationsarray, // Array.
                ];
            }
            $categoryobject['datepicker']['datepickers'][] = $datepickerobject;
        }
    }

    /**
     * Filter isn't applied here.
     *
     * @param string $filter
     * @param string $columnname
     * @param mixed $categoryvalue
     * @param wunderbyte_table $table
     *
     * @return void
     *
     */
    public function apply_filter(
        string &$filter,
        string $columnname,
        $categoryvalue,
        wunderbyte_table &$table
    ): void {
        return;
    }

    /**
     * The expected value.
     * @return array
     */
    public static function get_operatoroptions() {
        return [
            0 => "within",
            1 => "overlapboth",
            2 => "overlapstart",
            3 => "overlapend",
            4 => "before",
            5 => "after",
            6 => "flexoverlap",
        ];
    }


    /**
     * The expected value.
     * @param \MoodleQuickForm $mform
     * @param string $filtertypename
     */
    public static function add_remove_button(&$mform, $filtertypename) {
        $trashicon = '<i class="fa fa-trash"></i>';
        $mform->addElement(
            'button',
            "remove[{$filtertypename}]",
            $trashicon,
            [
                'class' => 'btn remove-key-value',
                'type' => 'button',
                'data-groupid' => $filtertypename,
                'aria-label' => "Remove key-value pair for {$filtertypename}",
            ]
        );
    }

    /**
     * Handles form definition of filter classes.
     * @param string $filtercolumn
     * @return array
     */
    public static function non_kestringy_value_pair_properties($filtercolumn) {
        return [
            'localizedname',
            'wbfilterclass',
            'local_wunderbyte_table\filters\types\datepicker',
            $filtercolumn . '_wb_checked',
        ];
    }

    /**
     * The expected value.
     * @param \MoodleQuickForm $mform
     * @param string $filterlabel
     * @param int $horizontallinecounter
     */
    public static function add_date_filter_head(&$mform, $filterlabel, $horizontallinecounter) {
        $htmlid = strtolower(str_replace(' ', '-', $filterlabel));
        $mform->addElement('html', '<div id="' . $htmlid . '">');
        if ($horizontallinecounter > 0) {
            $mform->addElement('html', '<hr>');
        }
        $mform->addElement('html', '<b>Filter name: ' . $filterlabel . '</b>');
        self::add_remove_button($mform, $htmlid);
    }

    /**
     * The expected value.
     * @param \MoodleQuickForm $mform
     * @param string $selected
     */
    public static function add_type_dropdown(&$mform, $selected) {
        $mform->registerNoSubmitButton('btn_subdatepickertype');
        $buttonargs = ['style' => 'visibility:hidden;'];
        $options = [
            'span' => 'span',
            'timestamp' => 'timestamp',
        ];
        $categoryselect = [
            $mform->createElement(
                'select',
                'subdatepicker_type',
                '',
                $options
            ),
            $mform->createElement(
                'submit',
                'btn_subdatepickertype',
                '',
                $buttonargs
            ),
        ];

        $mform->addGroup(
            $categoryselect,
            'subdatepicker_group',
            get_string('datepickertype', 'local_wunderbyte_table'),
            ' ',
            false
        );
        $mform->setType('btn_subdatepickertype', PARAM_NOTAGS);
        $mform->setDefault('subdatepicker_type', $selected);
    }

    /**
     * The expected value.
     * @param array $data
     * @param string $filtercolumn
     * @return array
     */
    public static function get_filterspecific_values($data, $filtercolumn) {
        if (!empty($data['datepicker'])) {
            foreach ($data['datepicker'] as $name => &$datepicker) {
                if (!isset($datepicker['name'])) {
                    $datepicker['name'] = $name;
                }
            }
        }
        return  [$data['datepicker'] ?? [], $data['subdatepicker_type'] ?? 'span'];
    }

    /**
     * The expected value.
     * @param object $data
     * @param string $filtercolumn
     * @return array
     */
    public static function get_new_filter_values($data, $filtercolumn) {
        return [];
    }
}
