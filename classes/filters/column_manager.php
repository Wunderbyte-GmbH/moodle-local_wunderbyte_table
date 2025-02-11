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

    /** @var \local_wunderbyte_table\wunderbyte_table */
    protected $table;

    /** @var \MoodleQuickForm */
    protected $mformedit;
    /** @var \MoodleQuickForm */
    protected $mformadd;
    /** @var array */
    protected $filtersettings;
    /** @var array */
    protected $data;

    /**
     * Handles form definition of filter classes.
     * @param array $params
     */
    public function __construct($params) {
        $this->data = $params;
        $this->filtercolumn = $params['filtercolumn'];
        $this->table = wunderbyte_table::instantiate_from_tablecache_hash($params['encodedtable']);
        $this->mformedit = new \MoodleQuickForm('dynamicform', 'post', '');
        $this->mformadd = new \MoodleQuickForm('dynamicform', 'post', '');
        $lang = filter::current_language();
        $key = $this->table->tablecachehash . $lang . '_filterjson';
        $this->filtersettings = editfilter::return_filtersettings($this->table, $key);
    }

    /**
     * Handles form definition of filter classes.
     * @return array
     */
    public function get_filtered_column_form() {
        $existingfilterdata = [];
        foreach ($this->filtersettings[$this->filtercolumn] as $key => $value) {
            if (!in_array($key, $this->non_kestringy_value_pair_properties())) {
                $existingfilterdata[$key] = $value;
            }
        }
        $this->set_available_filter_types($existingfilterdata, $this->filtersettings[$this->filtercolumn]['wbfilterclass']);
        $this->set_add_filter_key_value();
        return [
            'filtereditfields' => $this->mformedit->toHtml(),
            'filteraddfields' => $this->mformadd->toHtml(),
        ];
    }

    /**
     * Handles form definition of filter classes.
     * @return array
     */
    public function get_filtered_column_form_persist_error() {
        $existingfilterdata = [];
        foreach ($this->data['value'] as $key => $keyvalue) {
            if ($key !== 0) {
                $existingfilterdata[$key] = $this->data['value'][$key];
            }
        }
        $this->set_available_filter_types($existingfilterdata, $this->filtersettings[$this->filtercolumn]['wbfilterclass']);
        $this->set_add_filter_key_value();
        return [
            'filtereditfields' => $this->mformedit,
            'filteraddfields' => $this->mformadd,
        ];
    }

    /**
     * Handles form definition of filter classes.
     * @param array $existingfilterdata
     * @param string $filterclass
     */
    private function set_available_filter_types($existingfilterdata, $filterclass) {
        $this->mformedit->addElement('header', 'existing_pairs', 'Existing key value pairs');
        $staticfunction = 'render_mandatory_fields';
        if ($existingfilterdata  && self::is_static_public_function($filterclass, $staticfunction)) {
            $filterclass::$staticfunction($this->mformedit, $existingfilterdata);
        } else {
            $this->mformedit->addElement('html', '<p id="no-pairs-message" class="alert alert-info">No pairs exist</p>');
        }
    }

    /**
     * Handles form definition of filter classes.
     * @param string $classname
     * @param string $functionname
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
     * Handles form definition of filter classes.
     */
    private function set_add_filter_key_value() {
        $this->mformadd->addElement('html', '<div id="filter-add-field">');
        $this->mformadd->addElement('header', 'add_pair', 'Add new key value pair');

        $classname = $this->data['filter_options'];
        filter_manager::set_filter_types($this->mformadd, $classname);
        self::render_mandatory_fields($classname);

        $this->mformadd->addElement('html', '</div>');
    }

    /**
     * Handles form definition of filter classes.
     * @param string $classname
     */
    private function render_mandatory_fields($classname) {
        if (isset($this->data['key'][0])) {
            $newvalue = [
                $this->data['key'][0] => $this->data['value'][0],
            ];
            $staticfunction = 'render_mandatory_fields';
            if (self::is_static_public_function($classname, $staticfunction)) {
                $classname::$staticfunction($this->mformadd, $newvalue);
                $parts = explode("\\", $classname);
                $elementname = array_pop($parts) . 'group';
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
     * The expected value.
     * @param string $filtercolumn
     * @return array
     */
    public function get_filter_settings_of_column($filtercolumn) {
        return $this->filtersettings[$filtercolumn] ?? [];
    }

    /**
     * Handles form definition of filter classes.
     * @param \MoodleQuickForm $mform
     * @param array $formdata
     */
    public static function set_filter_columns(\MoodleQuickForm &$mform, $formdata) {
        $encodedtable = $formdata['encodedtable'];
        $table = wunderbyte_table::instantiate_from_tablecache_hash($encodedtable);
        $filterablecolumns = $table->subcolumns['datafields'];
        $options = self::get_all_filter_columns($filterablecolumns);
        if ($options) {
            $mform->addElement(
                'select',
                'filter_columns',
                get_string('setwbtablefiltercolumn', 'local_wunderbyte_table'),
                $options
            );
            $mform->setType('filter_columns', PARAM_INT);
        }
    }

    /**
     * Handles form definition of filter classes.
     * @param array $filterablecolumns
     * @return array
     */
    public static function get_all_filter_columns($filterablecolumns) {
        $options = [
            '' => get_string('setwbtablefiltercolumnoption', 'local_wunderbyte_table'),
        ];
        foreach ($filterablecolumns as $key => $filterablecolumn) {
            if (isset($filterablecolumn['wbfilterclass'])) {
                $options[$key] = $filterablecolumn['localizedname'];
            }
        }
        return $options;
    }
}
