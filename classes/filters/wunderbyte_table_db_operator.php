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

use local_wunderbyte_table\editfilter;
use local_wunderbyte_table\filter;

/**
 * Handles the filter classes.
 * @package local_wunderbyte_table
 */
class wunderbyte_table_db_operator {
    /** @var string */
    protected $key;

    /** @var array */
    protected $filtersettings;

    /** @var object */
    protected $data;

    /** @var string */
    protected $tablename;
    /**
     * __construct
     * @param object $data
     * @param \local_wunderbyte_table\wunderbyte_table $table
     */
    public function __construct($data, $table) {
        $this->tablename = 'local_wunderbyte_table';
        $this->data = $data;
        $lang = filter::current_language();
        $this->key = $table->tablecachehash . $lang . '_filterjson';
        $this->filtersettings = editfilter::return_filtersettings($table, $this->key);
    }

    /**
     * Set the key value pairs
     */
    public function set_existing_key_value_pairs() {
        $newfilterkeyvalues = [];
        foreach ($this->data->key as $key => $keyvalue) {
            if ($key == 0) {
                $newfilterkeyvalues[$keyvalue] = $this->data->value[0];
            } else {
                $newfilterkeyvalues[$keyvalue] = $this->data->value[$keyvalue];
            }
        }
        $this->filtersettings[$this->data->filter_columns] = $this->merge_settings_head_with_key_value_($newfilterkeyvalues);
    }

    /**
     * Set the key value pairs
     * @param array $newfilterkeyvalues
     */
    private function merge_settings_head_with_key_value_($newfilterkeyvalues) {
        $settingshead = [
            'localizedname',
            'wbfilterclass',
            'username_wb_checked',
        ];
        foreach ($this->filtersettings[$this->data->filter_columns] as $key => $filtersetting) {
            if (in_array($key, $settingshead)) {
                $newfilterkeyvalues[$key] = $filtersetting;
            }
        }
        return $newfilterkeyvalues;
    }

    /**
     * Set the key value pairs
     */
    public function save_new_filter_options() {
        global $DB;
        $result = $DB->get_record($this->tablename, ['hash' => $this->key], 'id');
        if ($result) {
            $result->jsonstring = json_encode($this->filtersettings);
            $DB->update_record($this->tablename, $result);
        }
    }
}
