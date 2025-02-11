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
class validation_manager {
    /** @var array */
    protected $data;
    /** @var array */
    protected $errors;

    /**
     * Handles form definition of filter classes.
     * @param array $submitteddata
     */
    public function __construct($submitteddata) {
        $this->data = $submitteddata;
    }

    /**
     * Handles form definition of filter classes.
     * @param array $errors
     * @param array $forms
     */
    public function set_errors($errors, &$forms) {
        foreach ($forms as $form) {
            $testing = 10;
        }
    }

    /**
     * Handles form definition of filter classes.
     * @return array
     */
    public function get_data_validation() {
        $errors = [];
        if (isset($this->data['filter_columns'])) {
            $errors = self::checked_selected_column($this->data['filter_columns']);

            $classname = $this->data['filter_options'];
            $staticfunction = 'validate_input';
            if (self::is_static_public_function($this->data['filter_options'], $staticfunction)) {
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
    private static function checked_selected_column($filtercolumns) {
        $errros = [];
        if (empty($filtercolumns)) {
            $errros['filter_columns'] = get_string('columnemptyerror', 'local_wunderbyte_table');
        }
        return $errros;
    }
}
