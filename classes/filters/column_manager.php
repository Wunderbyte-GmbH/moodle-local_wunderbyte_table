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

/**
 * Handles the filter classes.
 * @package local_wunderbyte_table
 */
class column_manager extends filtersettings {
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

    /** @var string */
    const FUNCTION_NAME = 'get_filterspecific_values';


    /**
     * Handles form definition of filter classes.
     * @param array $params
     * @param string $encodedtable
     */
    public function __construct($params, $encodedtable) {
        $this->data = $params;
        $this->filtercolumn = $params['filtercolumn'] ?? null;
        $this->mformedit = new \MoodleQuickForm('dynamicform', 'post', '');
        $this->mformadd = new \MoodleQuickForm('dynamicform', 'post', '');
        $this->filtersettings = self::get_filtersettings($encodedtable);
        $this->table = wunderbyte_table::instantiate_from_tablecache_hash($encodedtable);
    }

    /**
     * Handles form definition of filter classes.
     * @return array
     */
    public function get_filtered_column_form() {
        $filtersettings = $this->filtersettings[$this->filtercolumn];
        $classname = $filtersettings['wbfilterclass'];
        $functionname = self::FUNCTION_NAME;

        foreach ($filtersettings as $key => $value) {
            if ($this->is_static_public_function($classname, self::FUNCTION_NAME)) {
                [$existingfilterdata, $filterspecificvalue] = $classname::$functionname($filtersettings, $this->filtercolumn);
            }
        }

        $this->set_general_filter_settings($filtersettings);
        $this->set_available_filter_types(
            $existingfilterdata ?? [],
            $this->filtersettings[$this->filtercolumn]['wbfilterclass']
        );
        $this->set_add_filter_key_value(
            $existingfilterdata[0] ?? [],
            $filterspecificvalue
        );
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
        $filtersettings = $this->data;
        $classname = $this->data['wbfilterclass'];
        $functionname = self::FUNCTION_NAME;

        foreach ($filtersettings as $key => $value) {
            if ($this->is_static_public_function($classname, $functionname)) {
                [$existingfilterdata, $filterspecificvalue] = $classname::$functionname($filtersettings, $this->filtercolumn);
            }
        }

        $keyvaluepairs = $existingfilterdata['keyvaluepairs']['value'] ?? $existingfilterdata;
        $this->set_general_filter_settings($filtersettings);
        $this->set_available_filter_types($keyvaluepairs ?? [], $this->filtersettings[$this->filtercolumn]['wbfilterclass']);
        $this->set_add_filter_key_value($keyvaluepairs[0] ?? [], $filterspecificvalue);
        return [
            'filtereditfields' => $this->mformedit,
            'filteraddfields' => $this->mformadd,
        ];
    }

    /**
     * Handles form definition of filter classes.
     * @param array $filtercolumn
     */
    private function set_general_filter_settings($filtercolumn) {
        $this->mformedit->addElement(
            'header',
            'filter_enabled_header',
            get_string('filterenabledheader', 'local_wunderbyte_table')
        );
        $this->mformedit->addElement('text', 'localizedname', 'Filtername');
        $this->mformedit->setDefault('localizedname', $filtercolumn['localizedname']);
        $this->mformedit->addElement(
            'advcheckbox',
            $this->filtercolumn . '_wb_checked',
            get_string('enablefilter', 'local_wunderbyte_table'),
            get_string('enablefilterdesc', 'local_wunderbyte_table')
        );
        $this->mformedit->setDefault($this->filtercolumn . '_wb_checked', $filtercolumn[$this->filtercolumn . '_wb_checked']);

        $options = self::get_all_filter_types();
        $this->mformedit->addElement(
            'select',
            'wbfilterclass',
            get_string('setwbtablefiltertype', 'local_wunderbyte_table'),
            $options
        );
        $this->mformedit->setType('wbfilterclass', PARAM_INT);
        if (isset($filtercolumn['wbfilterclass'])) {
            $this->mformedit->setDefault('wbfilterclass', $filtercolumn['wbfilterclass']);
        }
    }

    /**
     * Handles form definition of filter classes.
     * @param array $existingfilterdata
     * @param string $filterclass
     */
    private function set_available_filter_types($existingfilterdata, $filterclass) {
        $this->mformedit->addElement(
            'header',
            'existing_pairs',
            get_string('filterexistingkeyvaluepairs', 'local_wunderbyte_table')
        );
        $staticfunction = 'render_mandatory_fields';
        if (
            self::has_existingfilterdata($existingfilterdata)  &&
            self::is_static_public_function($filterclass, $staticfunction)
        ) {
            $filterclass::$staticfunction($this->mformedit, $existingfilterdata);
        } else {
            $this->mformedit->addElement('html', '<p id="no-pairs-message" class="alert alert-info">'
                . get_string('filternopairsexist', 'local_wunderbyte_table') . '</p>');
        }
    }

    /**
     * Handles form definition of filter classes.
     * @param array $existingfilterdata
     * @return bool
     */
    private function has_existingfilterdata($existingfilterdata) {
        $filtersite = count($existingfilterdata);
        if (
            $filtersite > 1
        ) {
            return true;
        } else if (
            $filtersite == 1 &&
            !array_key_exists(0, $existingfilterdata)
        ) {
            return true;
        }
        return false;
    }


    /**
     * Handles form definition of filter classes.
     * @param array $newkeyvaluepair
     * @param string $filterspecificvalue
     */
    private function set_add_filter_key_value($newkeyvaluepair, $filterspecificvalue = '') {
        $this->mformadd->addElement('html', '<div id="filter-add-field">');
        $this->mformadd->addElement('header', 'add_pair', get_string('filteraddnewkeyvaluepair', 'local_wunderbyte_table'));

        $classname = $this->data['wbfilterclass'] ?? $this->filtersettings[$this->data['filtercolumn']]['wbfilterclass'];
        self::render_mandatory_fields(
            $classname,
            [$newkeyvaluepair],
            $filterspecificvalue
        );

        $this->mformadd->addElement('html', '</div>');
    }

    /**
     * Handles form definition of filter classes.
     * @param string $classname
     * @param array $newkeyvaluepair
     * @param string $filterspecificvalue
     */
    private function render_mandatory_fields($classname, $newkeyvaluepair, $filterspecificvalue = '') {
        $staticfunction = 'render_mandatory_fields';
        if (self::is_static_public_function($classname, $staticfunction)) {
            $classname::$staticfunction(
                $this->mformadd,
                $newkeyvaluepair ?? [],
                $filterspecificvalue
            );
            $parts = explode("\\", $classname);
            $elementname = array_pop($parts) . 'group';
        }
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
     */
    public function set_filter_columns(\MoodleQuickForm &$mform) {
        $options = $this->get_all_filter_columns($this->filtersettings);
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
    public function get_all_filter_columns($filterablecolumns) {
        $options = [
            '' => get_string('setwbtablefiltercolumnoption', 'local_wunderbyte_table'),
        ];
        // We only allow filters for columns that are present in SQL.
        // We do this because we want to avoid errors with custom columns created by col_functions.
        $allowedcolumns = $this->table->get_sql_column_names();
        foreach ($filterablecolumns as $key => $filterablecolumn) {
            if (!in_array($key, $allowedcolumns)) {
                continue;
            }
            if ($key != 'id') {
                $options[$key] = "{$filterablecolumn['localizedname']} ({$key})";
            }
        }
        return $options;
    }
}
