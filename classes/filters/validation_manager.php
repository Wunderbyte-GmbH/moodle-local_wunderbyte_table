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

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

use ReflectionClass;

/**
 * Handles the filter classes.
 * @package local_wunderbyte_table
 */
class validation_manager extends filtersettings {
    /** @var array */
    protected $data;
    /** @var array */
    protected $filtersettings;
    /** @var array */
    protected $errors;

    /**
     * Handles form definition of filter classes.
     * @param array $submitteddata
     * @param string $encodedtable
     */
    public function __construct($submitteddata, $encodedtable) {
        $this->data = $submitteddata;
        $this->filtersettings = self::get_filtersettings($encodedtable);
    }

    /**
     * Handles form definition of filter classes.
     * @param array $errors
     * @param array $forms
     */
    public function set_errors($errors, &$forms) {
        foreach ($forms as $formkey => $form) {
            if (!isset($form->_elements) || !is_array($form->_elements)) {
                continue;
            }
            foreach ($form->_elements as $element) {
                if (isset($element->_type) && in_array($element->_type, $this->valid_element_types())) {
                    if (isset($errors['0_group']) && $this->is_error_on_new_pair($formkey, $errors['0_group'])) {
                        $form->setElementError($element->getName(), $errors['0_group']);
                    } else if (isset($errors[$element->getName()]) && $formkey != 'filteraddfields') {
                        $form->setElementError($element->getName(), $errors[$element->getName()]);
                    }
                }
            }
        }
    }

    /**
     * Handles form definition of filter classes.
     * @return array
     */
    private function valid_element_types() {
        return [
            'group',
            'text',
            'select',
        ];
    }

    /**
     * Handles form definition of filter classes.
     * @param array $formkey
     * @param array $errormsg
     * @return bool
     */
    private function is_error_on_new_pair($formkey, $errormsg) {
        return $formkey == 'filteraddfields' && $errormsg;
    }

    /**
     * Handles form definition of filter classes.
     * @param array $formkey
     * @param array $errors
     * @return mixed
     */
    private function is_error_on_existing_pair($formkey, $errors) {
        if ($formkey == 'filtereditfields') {
            return self::get_existing_pair_errors($errors);
        }
        return false;
    }

    /**
     * Handles form definition of filter classes.
     * @param array $errors
     * @return string
     */
    public function get_existing_pair_errors($errors) {
        $uniqueerrors = [];
        unset($errors[0]);
        foreach ($errors as $error) {
            if (!in_array($error, $uniqueerrors)) {
                $uniqueerrors[] = $error;
            }
        }
        return implode(', ', $uniqueerrors);
    }

    /**
     * Handles form definition of filter classes.
     * @return array
     */
    public function get_data_validation() {
        $errors = [];
        if (isset($this->data['filter_columns'])) {
            $errors = $this->checked_general_filter_settings($this->data);

            $classname = $this->data['wbfilterclass'];
            $staticfunction = 'validate_input';
            if (self::is_static_public_function($classname, $staticfunction)) {
                $fitertypeerrors = $classname::$staticfunction($this->data);
                $errors = array_merge($errors, $fitertypeerrors);
            }
        }
        return $errors;
    }

    /**
     * The expected value.
     * @param array $filterdata
     * @return array
     */
    private function checked_general_filter_settings($filterdata) {
        $errros = [];
        if (empty($filterdata['filter_columns'])) {
            $errros['filter_columns'] = get_string('columnemptyerror', 'local_wunderbyte_table');
        }
        if (empty($filterdata['localizedname'])) {
            $errros['localizedname'] = get_string('localizednameemptyerror', 'local_wunderbyte_table');
        }
        if (empty($filterdata['wbfilterclass'])) {
            $errros['wbfilterclass'] = get_string('wbfilterclassemptyerror', 'local_wunderbyte_table');
        }
        return $errros;
    }
}
