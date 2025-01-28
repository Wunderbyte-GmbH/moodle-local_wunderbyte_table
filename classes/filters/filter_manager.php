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
class filter_manager {
    /**
     * Handles form definition of filter classes.
     * @return array
     */
    public static function get_all_filter_types() {
        $typesdirectory = __DIR__ . '/types';
        $filtertypes = [
            'nothing' => 'Please select a type',
        ];
        $foundfiltertypes = [];
        foreach (scandir($typesdirectory) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $classname = __NAMESPACE__ . '\\types\\' . pathinfo($file, PATHINFO_FILENAME);
                $localizedname = self::execute_static_function($classname, 'return_localized_name');
                if ($localizedname) {
                    $foundfiltertypes[$classname] = $localizedname;
                }
            }
        }
        return array_merge($filtertypes, $foundfiltertypes);
    }

    /**
     * Handles form definition of filter classes.
     * @param string $classname
     * @return \MoodleQuickForm
     */
    public static function get_mandetory_filter_fields($classname) {
        $mandetoryfilterfields = self::execute_static_function($classname, 'render_mandatory_fields');
        return $mandetoryfilterfields;
    }

    /**
     * Handles form definition of filter classes.
     * @param array $data
     * @return array
     */
    public static function get_data_validation($data) {
        $errors = self::execute_static_function($data['filter_options'], 'validate_filter_data', $data);
        return is_array($errors) ? $errors : [];
    }

    /**
     * Handles form definition of filter classes.
     * @param \MoodleQuickForm $mandatoryfields
     * @param array $submitteddata
     * @param array $errors
     */
    public static function set_peristing_values($mandatoryfields, $submitteddata, $errors) {
        self::execute_static_function(
            $submitteddata['filter_options'],
            'get_dynamic_values',
            [
                'form' => $mandatoryfields,
                'data' => $submitteddata,
                'errors' => $errors,
            ]
        );
    }

    /**
     * Handles form definition of filter classes.
     * @param string $classname
     * @param string $staticfunction
     * @param array $data
     * @return mixed|null
     */
    private static function execute_static_function($classname, $staticfunction, $data = []) {
        if (class_exists($classname)) {
            try {
                $reflection = new ReflectionClass($classname);
                if (!$reflection->isAbstract() && $reflection->isSubclassOf(base::class)) {
                    if ($reflection->hasMethod($staticfunction)) {
                        $method = $reflection->getMethod($staticfunction);
                        if ($method->isPublic() && $method->isStatic()) {
                            return $classname::$staticfunction($data);
                        }
                    }
                }
            } catch (\ReflectionException $e) {
                debugging("Reflection error for class $classname: " . $e->getMessage());
            }
        }
        return null;
    }
}
