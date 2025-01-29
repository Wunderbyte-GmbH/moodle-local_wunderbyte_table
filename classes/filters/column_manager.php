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

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

use ReflectionClass;

/**
 * Handles the filter classes.
 * @package local_wunderbyte_table
 */
class column_manager {
    /** @var string */
    protected $filtercolumn;

    /** @var string */
    protected $table;

    /** @var \MoodleQuickForm */
    protected $mform;

    /**
     * Handles form definition of filter classes.
     * @param array $params
     */
    public function __construct($params) {
        $this->filtercolumn = $params['filtercolumn'];
        $this->table = wunderbyte_table::instantiate_from_tablecache_hash($params['encodedtable']);
        $this->mform = new \MoodleQuickForm('dynamicform', 'post', '');
    }

    /**
     * Handles form definition of filter classes.
     * @return \MoodleQuickForm
     */
    public function get_filtered_column_form() {
        // Get the filter type dropdown.
        filter_form_operator::set_filter_types($this->mform);
        // Get the already inserted key values.
        $this->set_available_filter_types();
        // Get the add new key value field.
        return $this->mform;
    }

    /**
     * Handles form definition of filter classes.
     */
    private function set_available_filter_types() {
        $columndata = $this->table->subcolumns['datafields'][$this->filtercolumn] ?? [];
        foreach ($columndata as $key => $value) {
            if (!in_array($key, $this->non_kestringy_value_pair_properties())) {
                self::execute_static_function($columndata['wbfilterclass'], 'generate_mandatory_fields', [$key => $value]);
            }
        }
    }

    /**
     * Handles form definition of filter classes.
     * @return array
     */
    private function non_kestringy_value_pair_properties() {
        return [
            'localizedname',
            'wbfilterclass',
            $this->filtercolumn . '_wb_checked',
        ];
    }

    /**
     * Handles form definition of filter classes.
     * @param string $classname
     * @param string $staticfunction
     * @param array $data
     * @return mixed|null
     */
    private function execute_static_function($classname, $staticfunction, $data = []) {
        if (class_exists($classname)) {
            try {
                $reflection = new ReflectionClass($classname);
                if (!$reflection->isAbstract() && $reflection->isSubclassOf(base::class)) {
                    if ($reflection->hasMethod($staticfunction)) {
                        $method = $reflection->getMethod($staticfunction);
                        if ($method->isPublic() && $method->isStatic()) {
                            return $classname::$staticfunction($this->mform, $data);
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
