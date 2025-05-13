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
 * @copyright 2023 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\filters\types;
use local_wunderbyte_table\filters\base;
use local_wunderbyte_table\wunderbyte_table;

/**
 * Wunderbyte table class is an extension of table_sql.
 */
class callback extends base {
    /**
     * Property to indicate if class has implemented a callback
     *
     * @var bool
     */
    public $hascallback = true;

    /**
     * Callable function
     *
     * @var string
     */
    private $callbackfunction = null;

    /**
     * Get standard filter options.
     * @param wunderbyte_table $table
     * @param string $key
     * @return array
     */
    public static function get_data_for_filter_options(wunderbyte_table $table, string $key) {
        $returnarray = [
            0 => (object)[
                $key => 0,
                'keycount' => true,
            ],
            1 => (object)[
                $key => 1,
                'keycount' => true,
            ],
        ];

        return $returnarray;
    }

    /**
     * Adds the specific part of SQL to the filterstring.
     *
     * @param string $filter
     * @param string $columnname
     * @param mixed $categoryvalue
     * @param wunderbyte_table $table
     *
     * @return void
     *
     */
    public function apply_filter(
        string &$filter,
        string $columnname,
        $categoryvalue,
        wunderbyte_table &$table
    ): void {
        // Here, we just want to serve the necessary syntax, but we can't add sql.
        $filter .= "1 = 1";

        $this->set_expected_value(reset($categoryvalue));
    }

    /**
     * Setter for static callbackfunctionname including namespace.
     *
     * @param string $functionname
     *
     * @return void
     *
     */
    public function define_callbackfunction(string $functionname) {
        $this->callbackfunction = $functionname;
    }

    /**
     * Function to filter std Class records by the set callback.
     * We have positiv & negativ filter.
     *
     * @param array $records
     * @param bool $not
     *
     * @return array
     *
     */
    public function filter_by_callback(array $records, $not = false) {

        if ($this->expectedvalue === null) {
            return $records;
        }

        $expectedvalue = $this->expectedvalue ?? 1;
        $returnarray = [];
        foreach ($records as $record) {
            if (!$not) {
                $methodname = $this->callbackfunction;
                if ($expectedvalue == $methodname($record)) {
                    $returnarray[] = $record;
                }
            } else {
                if ($expectedvalue != $methodname($record)) {
                    $returnarray[] = $record;
                }
            }
        }
        return $returnarray;
    }

    /**
     * This function takes a key value pair of options.
     * Only if there are actual results in the table, these options will be displayed.
     * The keys are the results, the values are the localized strings.
     * For the standard filter, it's not necessary to provide these options...
     * They will be gathered automatically.
     *
     * @param array $options
     * @return void
     */
    public function add_options(array $options = []) {
        foreach ($options as $key => $value) {
            $this->options[$key] = $value;
        }
    }

    /**
     * The expected value.
     * @param \MoodleQuickForm $mform
     * @param array $data
     * @param string $filterspecificvalue
     */
    public static function render_mandatory_fields(&$mform, $data = [], $filterspecificvalue = '') {
        $mform->addElement('html', '<p id="no-pairs-message" class="alert alert-info">No further seetings needed</p>');
    }

    /**
     * The expected value.
     * @param object $data
     * @param string $filtercolumn
     * @return array
     */
    public static function get_filterspecific_values($data, $filtercolumn) {
        $filterenablelabel = $filtercolumn . '_wb_checked';
        $filterspecificvalues = [
            'localizedname' => $data->localizedname ?? '',
            $data->wbfilterclass => true,
            $filterenablelabel => $data->$filterenablelabel ?? '0',
            'wbfilterclass' => $data->wbfilterclass ?? '',
        ];
        return [$filterspecificvalues, ''];
    }

    /**
     * The expected value.
     * @param object $data
     * @param string $filtercolumn
     * @return array
     */
    public static function get_new_filter_values($data, $filtercolumn) {
        return [];
    }
}
