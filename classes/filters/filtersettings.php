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
use local_wunderbyte_table\editfilter;
use local_wunderbyte_table\filter;
use ReflectionClass;

/**
 * Handles the filter classes.
 * @package local_wunderbyte_table
 */
abstract class filtersettings {
    /**
     * Validation.
     * @param string $encodedtable
     * @return array
     */
    public static function get_filtersettings($encodedtable) {
        $table = wunderbyte_table::instantiate_from_tablecache_hash($encodedtable);
        $lang = filter::current_language();
        $key = $table->tablecachehash . $lang . '_filterjson';
        $settings = editfilter::return_filtersettings($table, $key);

        foreach ($table->columns as $index => $column) {
            if (!isset($settings[$index])) {
                $settings[$index] = [
                    'localizedname' => $table->headers[$column],
                    $index . '_wb_checked' => 0,
                ];
            }
        }
        return $settings;
    }

    /**
     * Handles form definition of filter classes.
     * @return array
     */
    public function get_all_filter_types() {
        $typesdirectory = __DIR__ . '/types';
        $filtertypes = [
            '' => get_string('setwbtablefiltertypeoption', 'local_wunderbyte_table'),
        ];
        $foundfiltertypes = [];
        foreach (scandir($typesdirectory) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $classname = __NAMESPACE__ . '\\types\\' . pathinfo($file, PATHINFO_FILENAME);
                $functionname = 'return_localized_name';
                if (self::is_static_public_function($classname, $functionname)) {
                    $foundfiltertypes[$classname] = $classname::$functionname();
                }
            }
        }
        return array_merge($filtertypes, $foundfiltertypes);
    }

    /**
     * Handles form definition of filter classes.
     * @param string $classname
     * @param string $functionname
     */
    protected function is_static_public_function($classname, $functionname) {
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
}
