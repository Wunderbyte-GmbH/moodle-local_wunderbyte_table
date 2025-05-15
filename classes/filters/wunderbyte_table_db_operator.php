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
    protected $tablesettings;

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
        // Last param true gets full tablesettings.
        $this->tablesettings = editfilter::return_filtersettings($table, $this->key, true);
    }

    /**
     * Set the key value pairs
     */
    public function set_existing_key_value_pairs() {
        $this->tablesettings['filtersettings'][$this->data->filter_columns] = $this->data->wbfilterclass::get_new_filter_values(
            $this->data,
            $this->data->filter_columns
        );
    }

    /**
     * Set the key value pairs
     */
    public function save_new_filter_options() {
        global $DB;
        $result = $DB->get_record($this->tablename, ['hash' => $this->key], 'id');
        if ($result) {
            $result->jsonstring = json_encode($this->tablesettings);
            $DB->update_record($this->tablename, $result);
            \core\notification::add(
                get_string('successaddedfilternotification', 'local_wunderbyte_table'),
                \core\output\notification::NOTIFY_SUCCESS
            );
            $otherlangtables = $this->get_other_lang_tables($result->tablehash ?? '', $this->key);
            $this->persist_filter_settings($otherlangtables, $this->tablesettings);
        }
    }

    /**
     * Set the key value pairs
     * @param string $tablehash
     * @param string $hash
     */
    public function get_other_lang_tables($tablehash, $hash) {
        global $DB;
        $sql = "SELECT * FROM {" . $this->tablename . "}
            WHERE tablehash = :tablehash
            AND hash <> :hash";

        $params = [
            'tablehash' => $tablehash,
            'hash' => $hash,
        ];
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Set the key value pairs
     * @param array $tables
     * @param array $newtablesettings
     */
    public function persist_filter_settings($tables, $newtablesettings) {
        global $DB;
        foreach ($tables as $table) {
            $tablesettings = json_decode($table->jsonstring);

            if ($tablesettings === null || !isset($tablesettings->filtersettings)) {
                continue;
            }

            foreach ($tablesettings->filtersettings as $filtercolumn => $filtercolumnsettings) {
                if (isset($newtablesettings['filtersettings'][$filtercolumn][$filtercolumn . '_wb_checked'])) {
                    $tablesettings->filtersettings->$filtercolumn->{$filtercolumn . '_wb_checked'} =
                        $newtablesettings['filtersettings'][$filtercolumn][$filtercolumn . '_wb_checked'];
                }
            }

            $DB->update_record($this->tablename, [
                'id' => $table->id,
                'jsonstring' => json_encode($tablesettings),
            ]);
        }
    }
}
