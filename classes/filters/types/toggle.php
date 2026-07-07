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

namespace local_wunderbyte_table\filters\types;

use coding_exception;
use local_wunderbyte_table\wunderbyte_table;

/**
 * Class toggle
 *
 * A toggle filter is a customfieldfilter which filters on exactly one single value.
 * It renders as a single on/off switch instead of a list of checkboxes.
 *
 * - Switch on: the records are filtered on the defined toggle value.
 * - Switch off: no filtering is applied for this column at all.
 *
 * Like the customfieldfilter, it can filter on a booking option customfield either
 * via set_sql_for_fieldid() or via a custom SQL subquery using set_sql().
 * In addition, a second column can be included in the filter condition via
 * set_second_column(), e.g. to filter on a date column with an upper bound like
 * strtotime('today + 4 weeks').
 *
 * @package local_wunderbyte_table
 * @copyright 2026 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class toggle extends customfieldfilter {
    /**
     *
     * @var string
     */
    public static $groupname = 'togglefiltergroup';

    /**
     * Allowed operators for the second column condition.
     * '!=' is accepted in set_second_column() and normalized to '<>'.
     *
     * @var array
     */
    const ALLOWEDOPERATORS = ['=', '<', '<=', '>', '>=', '<>'];

    /**
     * The column used for the additional second condition (e.g. 'coursestarttime').
     *
     * @var string
     */
    protected string $secondsubquerycolumn = '';

    /**
     * The operator used for the second column condition.
     *
     * @var string
     */
    protected string $secondcolumnoperator = '=';

    /**
     * The value the second column is compared against.
     *
     * @var string
     */
    protected string $secondcolumnvalue = '';

    /**
     * Set the column which should be filtered and possibly localize it.
     *
     * @param string $columnidentifier
     * @param string $localizedstring
     * @param string $secondcolumnidentifier
     * @param string $secondcolumnlocalized
     */
    public function __construct(
        string $columnidentifier,
        string $localizedstring = '',
        string $secondcolumnidentifier = '',
        string $secondcolumnlocalized = ''
    ) {
        parent::__construct($columnidentifier, $localizedstring, $secondcolumnidentifier, $secondcolumnlocalized);

        // A toggle filters on exactly one static value, so there is nothing to count...
        $this->countkeys = false;
        // ... and we always compare with exact match.
        $this->inuseoperator = '=';
        // Default toggle value, can be overridden via set_toggle_value().
        $this->set_toggle_value('1');
    }

    /**
     * Define the single value this toggle filters on when switched on.
     *
     * @param string $value the value stored in the filtered column
     * @param string $label the label displayed next to the switch, defaults to the localized filter name
     * @return void
     */
    public function set_toggle_value(string $value, string $label = '') {
        // A toggle has exactly one value, so we replace any previously set option.
        $this->options = [
            $value => $label !== '' ? $label : $this->localizedstring,
        ];
    }

    /**
     * This function takes a key value pair of options.
     * As a toggle filters on exactly one single value, only the first pair is used.
     *
     * @param array $options
     * @return void
     */
    public function add_options(array $options = []) {
        if (empty($options)) {
            return;
        }
        $key = array_key_first($options);
        $this->set_toggle_value((string)$key, (string)$options[$key]);
    }

    /**
     * Define an additional condition on a second column which is applied together
     * with the toggle value condition (combined via AND) when the toggle is on.
     *
     * Example for "taking place within the next four weeks":
     * ```php
     * $toggle->set_second_column('coursestarttime', strtotime('today + 4 weeks'), '<=');
     * ```
     *
     * @param string $subquerycolumn the column inside the subquery (or the table column) to compare
     * @param string|int $value the value to compare against, e.g. a timestamp
     * @param string $operator one of '=', '<', '<=', '>', '>=', '!=' (default '=')
     * @return void
     * @throws coding_exception on an invalid column name or operator
     */
    public function set_second_column(string $subquerycolumn, $value, string $operator = '=') {
        if (!preg_match('/^[a-zA-Z0-9_\.]+$/', $subquerycolumn)) {
            throw new coding_exception('Invalid column name for second column of toggle filter: ' . $subquerycolumn);
        }

        $operator = trim($operator);
        // Normalize to the SQL standard inequality operator.
        if ($operator === '!=') {
            $operator = '<>';
        }
        if (!in_array($operator, self::ALLOWEDOPERATORS, true)) {
            throw new coding_exception('Invalid operator for second column of toggle filter: ' . $operator);
        }

        $this->secondsubquerycolumn = $subquerycolumn;
        $this->secondcolumnoperator = $operator;
        $this->secondcolumnvalue = (string)$value;
    }

    /**
     * Applies the filter to a wunderbyte_table instance.
     *
     * When a custom SQL subquery or a fieldid is set, the customfieldfilter logic is used.
     * Otherwise the toggle works on a plain table column with an exact match, so it can
     * also be used when it was configured via the filter edit form.
     *
     * @param string $filter
     * @param string $columnname
     * @param mixed $categoryvalue
     * @param wunderbyte_table $table
     * @return void
     */
    public function apply_filter(
        string &$filter,
        string $columnname,
        $categoryvalue,
        wunderbyte_table &$table
    ): void {
        if ($this->iscustomsql || !empty($this->fieldid)) {
            // Custom SQL subquery or customfield mode. The parent generates the condition via
            // the generate_where_condition_* methods, which we override to append the second column.
            parent::apply_filter($filter, $columnname, $categoryvalue, $table);
            return;
        }

        // Plain table column: filter directly on the column with an exact match.
        $this->subquerycolumn = $columnname;
        $filter .= $this->generate_where_condition_using_equal($filter, $columnname, $categoryvalue, $table);
    }

    /**
     * Generated where condition using '=' operator, extended by the second column condition.
     *
     * @param string $filter
     * @param string $columnname
     * @param mixed $categoryvalue
     * @param wunderbyte_table $table
     * @return string
     */
    protected function generate_where_condition_using_equal(
        string &$filter,
        string $columnname,
        $categoryvalue,
        wunderbyte_table &$table
    ): string {
        $generatedwhere = parent::generate_where_condition_using_equal($filter, $columnname, $categoryvalue, $table);
        return $this->append_second_column_condition($generatedwhere, $categoryvalue, $table);
    }

    /**
     * Generated where condition using ILIKE operator, extended by the second column condition.
     *
     * @param string $filter
     * @param string $columnname
     * @param mixed $categoryvalue
     * @param wunderbyte_table $table
     * @return string
     */
    protected function generate_where_condition_using_ilike(
        string &$filter,
        string $columnname,
        $categoryvalue,
        wunderbyte_table &$table
    ): string {
        $generatedwhere = parent::generate_where_condition_using_ilike($filter, $columnname, $categoryvalue, $table);
        return $this->append_second_column_condition($generatedwhere, $categoryvalue, $table);
    }

    /**
     * Appends the condition on the second column (if any) to the generated where condition.
     *
     * If set_second_column() was not called but a second column identifier was passed to the
     * constructor, the second column is filtered on the toggle value as well.
     *
     * @param string $generatedwhere
     * @param mixed $categoryvalue
     * @param wunderbyte_table $table
     * @return string
     */
    protected function append_second_column_condition(
        string $generatedwhere,
        $categoryvalue,
        wunderbyte_table &$table
    ): string {
        $column = $this->secondsubquerycolumn;
        $operator = $this->secondcolumnoperator;
        $value = $this->secondcolumnvalue;

        if ($column === '' && !empty($this->secondcolumnidentifier)) {
            // Fallback: filter the second column on the toggle value as well.
            $column = $this->secondcolumnidentifier;
            $operator = '=';
            $firstvalue = is_array($categoryvalue) ? reset($categoryvalue) : $categoryvalue;
            $value = (string)$firstvalue;
        }

        if ($column === '') {
            return $generatedwhere;
        }

        $paramsvaluekey = $table->set_params($value, true);
        return "($generatedwhere AND $column $operator :$paramsvaluekey)";
    }

    /**
     * Retrieves the single option of this toggle for the filter UI.
     *
     * Other than the customfieldfilter, the toggle also works without a live filter
     * instance on the table (e.g. when it was configured via the filter edit form).
     *
     * @param wunderbyte_table $table The table instance.
     * @param string $key The column or field key.
     * @return array An associative array with the single toggle option.
     */
    public static function get_data_for_filter_options(wunderbyte_table $table, string $key) {
        if (($table->filters[$key] ?? null) instanceof self) {
            return parent::get_data_for_filter_options($table, $key);
        }

        // No live instance: build the single option from the stored filter settings.
        $settings = $table->subcolumns['datafields'][$key] ?? [];
        foreach ($settings as $optionkey => $optionvalue) {
            if (self::is_reserved_settings_key((string)$optionkey, $key) || !is_string($optionvalue)) {
                continue;
            }
            // A toggle has exactly one value, so we return the first option found.
            return [
                $optionkey => (object)[
                    $key => (string)$optionkey,
                    'keycount' => false,
                ],
            ];
        }

        // Fall back to the default toggle value.
        return [
            '1' => (object)[
                $key => '1',
                'keycount' => false,
            ],
        ];
    }

    /**
     * Adds the array for the mustache template to render the categoryobject.
     * A toggle renders as one single switch, not as a list of checkbox values.
     *
     * @param array $categoryobject
     * @param array $filtersettings
     * @param string $fckey
     * @param array $values
     * @return void
     */
    public static function add_to_categoryobject(array &$categoryobject, array $filtersettings, string $fckey, array $values) {
        // The single toggle value with its label as stored via add_options()/set_toggle_value().
        $valuekey = null;
        $label = '';
        foreach (($filtersettings[$fckey] ?? []) as $optionkey => $optionvalue) {
            if (self::is_reserved_settings_key((string)$optionkey, $fckey) || !is_string($optionvalue)) {
                continue;
            }
            $valuekey = (string)$optionkey;
            $label = $optionvalue;
            break;
        }

        if ($valuekey === null) {
            // Fall back to the values gathered for this filter.
            if (!is_array($values) || empty($values)) {
                return;
            }
            $valuekey = (string)array_key_first($values);
        }

        $categoryobject['toggle'] = [
            'value' => $valuekey,
            // We do not want to show HTML tags or HTML entities, so replace &amp; with &.
            'label' => $label !== '' ? strip_tags(str_replace('&amp;', '&', $label)) : $valuekey,
            'columnname' => $fckey,
        ];
    }

    /**
     * Add keys and values for applied filters. This will only be applied if filter is active.
     *
     * @param mixed $tableobject
     * @param array $filterarray
     * @param int $key
     * @return void
     */
    public static function prepare_filter_for_rendering(&$tableobject, array $filterarray, int $key) {
        // Expand the filter area.
        $tableobject[$key]['show'] = 'show';
        $tableobject[$key]['collapsed'] = '';
        $tableobject[$key]['expanded'] = 'true';

        // Set the switch to checked.
        if (isset($tableobject[$key]['toggle'])) {
            $tableobject[$key]['toggle']['checked'] = 'checked';
        }
    }

    /**
     * Checks if a filter settings key is a reserved metadata key rather than an option.
     *
     * @param string $optionkey
     * @param string $columnname
     * @return bool
     */
    protected static function is_reserved_settings_key(string $optionkey, string $columnname): bool {
        return in_array($optionkey, [
            'localizedname',
            'wbfilterclass',
            'wbbypasscache',
            'showalloptions',
            'explode',
            'jsonattribute',
            'json',
            $columnname . '_wb_checked',
        ], true);
    }

    /**
     * The expected value.
     * @param \MoodleQuickForm $mform
     * @param array $data
     * @param string $filterspecificvalue
     */
    public static function render_mandatory_fields(&$mform, $data = [[]], $filterspecificvalue = '') {
        // A toggle has exactly one key value pair: the stored value (key) and the switch label (value).
        $key = '';
        $keyvaluepair = [];
        foreach ($data as $datakey => $datapair) {
            if (count($data) > 1 && empty($datakey)) {
                continue;
            }
            $key = $datakey;
            $keyvaluepair = is_array($datapair) ? $datapair : [];
            break;
        }

        $elements = [];
        $elements[] = $mform->createElement('text', 'keyvaluepairs[' . $key . '][key]', '', ['placeholder' => 'Key']);
        if (!empty($keyvaluepair['key'])) {
            $mform->setDefault('keyvaluepairs[' . $key . '][key]', $keyvaluepair['key']);
        }
        $elements[] = $mform->createElement('text', 'keyvaluepairs[' . $key . '][value]', '', ['placeholder' => 'Value']);
        if (!empty($keyvaluepair['value'])) {
            $mform->setDefault('keyvaluepairs[' . $key . '][value]', $keyvaluepair['value']);
        }
        $grouplabelname = empty($key) ? 'New' : $key;
        $mform->addGroup($elements, $key . '_group', $grouplabelname . ' values', '<br>', false);
    }

    /**
     * The expected value.
     * @param array $data
     * @return array
     */
    public static function validate_input($data) {
        $errors = [];
        $filledpairs = 0;
        foreach ($data['keyvaluepairs'] as $key => $keyvaluepair) {
            if (empty($keyvaluepair['key']) !== empty($keyvaluepair['value'])) {
                $errors[$key . '_group'] = get_string('standardfiltervaluekeyerror', 'local_wunderbyte_table');
            }
            if (!empty($keyvaluepair['key'])) {
                $filledpairs++;
            }
        }
        if ($filledpairs > 1) {
            foreach ($data['keyvaluepairs'] as $key => $keyvaluepair) {
                $errors[$key . '_group'] = get_string('togglefiltersinglevalueerror', 'local_wunderbyte_table');
            }
        }
        return $errors;
    }

    /**
     * The expected value.
     * @param array $data
     * @param string $filtercolumn
     * @return array
     */
    public static function get_filterspecific_values($data, $filtercolumn) {
        $filterenablelabel = $filtercolumn . '_wb_checked';
        $filterunspecificvalues = [
            'localizedname',
            'wbfilterclass',
            'showalloptions',
            $filterenablelabel,
        ];
        $filterspecificvalues = [];
        foreach ($data as $key => $value) {
            if (!in_array($key, $filterunspecificvalues)) {
                $filterspecificvalues[$key] = [
                    'key' => $key,
                    'value' => $value,
                ];
            }
        }
        return [$filterspecificvalues, ''];
    }

    /**
     * The expected value.
     * @param object $data
     * @param string $filtercolumn
     * @return array
     */
    public static function get_new_filter_values($data, $filtercolumn) {
        $filterenablelabel = $filtercolumn . '_wb_checked';
        $filterspecificvalues = [
            'localizedname' => $data->localizedname ?? '',
            'wbfilterclass' => $data->wbfilterclass ?? '',
            $filterenablelabel => $data->$filterenablelabel ?? '0',
        ];
        foreach ($data->keyvaluepairs as $key => $keyvaluepair) {
            if (!empty($keyvaluepair['key']) && !empty($keyvaluepair['value'])) {
                $filterspecificvalues[$keyvaluepair['key']] = $keyvaluepair['value'];
                // A toggle filters on exactly one single value.
                break;
            }
        }
        return $filterspecificvalues;
    }
}
