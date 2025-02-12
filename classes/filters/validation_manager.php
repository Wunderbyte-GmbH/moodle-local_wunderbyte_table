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
                if (isset($element->_type) && $element->_type === 'group') {
                    if ($this->is_error_on_new_pair($formkey, $errors['key'][0])) {
                        $form->setElementError($element->_name, $errors['key'][0]);
                    } else if ($existingpairerrors = $this->is_error_on_existing_pair($formkey, $errors)) {
                        $form->setElementError($element->_name, $existingpairerrors);
                    }
                }
            }
        }
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
        unset($errors['key'][0]);
        foreach ($errors['key'] as $error) {
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
            $errors = $this->checked_selected_column($this->data['filter_columns']);

            $classname = $this->filtersettings[$this->data['filter_columns']]['wbfilterclass'];
            $staticfunction = 'validate_input';
            if (self::is_static_public_function($classname, $staticfunction)) {
                $fitertypeerrors = $classname::$staticfunction($this->data);
                $errors = array_merge($errors, $fitertypeerrors);
            }
        }
        return $errors;
    }

    /**
     * Handles form definition of filter classes.
     * @param string $classname
     * @param string $functionname
     * @return bool
     */
    private function is_static_public_function($classname, $functionname) {
        if (class_exists($classname)) {
            try {
                $reflection = new ReflectionClass($classname);
                if (!$reflection->isAbstract() && $reflection->isSubclassOf(base::class)) {
                    if ($reflection->hasMethod($functionname)) {
                        $method = $reflection->getMethod($functionname);
                        if ($method->isPublic() && $method->isStatic()) {
                            return true;
                        }
                    }
                }
            } catch (\ReflectionException $e) {
                debugging("Reflection error for class $classname: " . $e->getMessage());
            }
        }
        return false;
    }

    /**
     * The expected value.
     * @param string $filtercolumns
     * @return array
     */
    private function checked_selected_column($filtercolumns) {
        $errros = [];
        if (empty($filtercolumns)) {
            $errros['filter_columns'] = get_string('columnemptyerror', 'local_wunderbyte_table');
        }
        return $errros;
    }
}
