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
use local_wunderbyte_table\filters\base;
use local_wunderbyte_table\wunderbyte_table;
use local_wunderbyte_table\filter;
use moodle_exception;

/**
 *
 * Class customfieldfilter
 *
 * This class provides functionality to filter Moodle data tables
 * based on values stored in custom fields. It supports both standard
 * filters and custom SQL subqueries for more complex data conditions.
 *
 * The class is typically used within the {@see wunderbyte_table} framework
 * to dynamically inject WHERE clauses into SQL queries based on user-selected
 * filter criteria.
 *
 * @package local_wunderbyte_table
 * @copyright 2023 Wunderbyte Gmbh <info@wunderbyte.at>
 * @author Mahdi Poustini
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class customfieldfilter extends base {
    /**
     *
     * @var string
     */
    public static $groupname = 'customfieldfiltergroup';
    /**
     * Property to indicate if class has implemented a callback
     *
     * @var bool
     */
    public $hascallback = true;

    /**
     * The SQL fragment or subquery string used in filtering.
     * Must contain one placeholder (e.g. ':where') that will be replaced
     * with dynamically generated conditions.
     *
     * @var string
     */
    protected string $sqlwithsubquery = '';

    /**
     * The column inside the subquery that will be used for filtering
     * (e.g., 'cfd.value' in a customfield_data subquery).
     *
     * @var string
     */
    protected string $subquerycolumn;

    /**
     * The custom field ID used for generating a default subquery when none is provided.
     *
     * @var int
     */
    protected int $fieldid;

    /**
     * Indicates whether the user has provided a custom SQL query.
     *
     * @var bool
     */
    protected bool $iscustomsql = false;

    /**
     * By default we count keys, but if false we return the options with no count.
     *
     * You need to call add_options() and pass your own options when you set this property to false.
     *
     * @var bool
     */
    protected bool $countkeys = true;

    /**
     * By default, this filter uses the ILIKE operator to filter results in the WHERE condition.
     * However, you can use the '=' operator instead. To use the '=' operator, you must call `use_operator_equal()`.
     *
     * @var string Can be 'ilike' or '='.
     */
    protected string $inuseoperator = 'ilike';

    /**
     * Applies the filter to a wunderbyte_table instance using either a custom SQL
     * subquery or a default one based on field ID.
     *
     * @param string $filter
     * @param string $columnname
     * @param mixed $categoryvalue
     * @param wunderbyte_table $table
     * @throws moodle_exception If no SQL subquery or field ID is provided.
     * @return void
     *
     */
    public function apply_filter(
        string &$filter,
        string $columnname,
        $categoryvalue,
        wunderbyte_table &$table
    ): void {
        global $DB;

        if (!$this->iscustomsql) {
            // If the user did not provide an SQL query, use the default one.
            // However, the user must provide a custom field ID.
            if (empty($this->fieldid)) {
                throw new moodle_exception(
                    'missing_subquery_and_fieldid',
                    'local_wunderbyte_table',
                    '',
                    null,
                    'Customfieldfilter: No SQL subquery or no fieldid provided.
                    You must call set_sql() or set_sql_for_fieldid before applying the filter,
                    otherwise the filter cannot be applied.'
                );
            }

            $defaultsql =
                    "id IN (SELECT instanceid
                            FROM {customfield_data} cfd
                            WHERE cfd.fieldid = {$this->fieldid}
                            AND :where)";
            $this->sqlwithsubquery = $defaultsql;
            $this->subquerycolumn = 'cfd.value';
        }

        switch ($this->inuseoperator) {
            case '=':
                $generatedwhere = $this->generate_where_condition_using_equal($filter, $columnname, $categoryvalue, $table);
                break;
            case 'ilike':
            default:
                $generatedwhere = $this->generate_where_condition_using_ilike($filter, $columnname, $categoryvalue, $table);
                break;
        }

        // Replaces placeholder with double dots (:) with the generated where condition.
        $filter .= $this->adjust_sql_condition($generatedwhere);
    }

    /**
     * Generated where condition using ILKE operator to filter out the results.
     *
     * @param string $filter
     * @param string $columnname
     * @param mixed $categoryvalue
     * @param wunderbyte_table $table
     * @return string
     *
     */
    protected function generate_where_condition_using_ilike(
        string &$filter,
        string $columnname,
        $categoryvalue,
        wunderbyte_table &$table
    ): string {
        global $DB;
        $filtercounter = 1;
        $generatedwhere = '(';
        foreach ($categoryvalue as $key => $value) {
            $generatedwhere .= $filtercounter == 1 ? "" : " OR ";
            // Apply special filter here.
            if (isset($table->subcolumns['datafields'][$columnname]['jsonattribute'])) {
                    $paramsvaluekey = $table->set_params("%" . $value . "%");
                    $generatedwhere .= $DB->sql_like("$this->subquerycolumn", ":$paramsvaluekey", false);
            } else {
                // We want to find the value in an array of values.
                // Therefore, we have to use or as well.
                // First, make sure we have enough params we can use..
                $separator = $table->subcolumns['datafields'][$columnname]['explode'] ?? ",";
                $paramsvaluekey = $table->set_params('%' . $separator . $value . $separator . '%', true);
                $escapecharacter = wunderbyte_table::return_escape_character($value);
                $concatvalue = $DB->sql_concat("'$separator'", $this->subquerycolumn, "'$separator'");
                $generatedwhere .= $DB->sql_like("$concatvalue", ":$paramsvaluekey", false, false, false, $escapecharacter);
            }
            $filtercounter++;
        }
        $generatedwhere .= ')';

        return $generatedwhere;
    }

    /**
     * Generated where condition using '=' operator to filter out the results.
     *
     * @param string $filter
     * @param string $columnname
     * @param mixed $categoryvalue
     * @param wunderbyte_table $table
     * @return string
     *
     */
    protected function generate_where_condition_using_equal(
        string &$filter,
        string $columnname,
        $categoryvalue,
        wunderbyte_table &$table
    ): string {
        global $DB;
        $filtercounter = 1;
        $generatedwhere = '(';
        foreach ($categoryvalue as $key => $value) {
            $generatedwhere .= $filtercounter == 1 ? "" : " OR ";
            $paramsvaluekey = $table->set_params($value, true);
            $generatedwhere .= $this->subquerycolumn . '=' . ":$paramsvaluekey";
            $filtercounter++;
        }
        $generatedwhere .= ')';

        return $generatedwhere;
    }

    /**
     * Sets the SQL subquery used by this filter.
     *
     * This method must be called before {@see apply_filter()} to define the SQL condition
     * that will be injected into the final query. The provided SQL string should represent
     * a valid subquery or condition fragment that can be appended to the main query.
     *
     * Example usage:
     * ```php
     * $filter->set_sql("category IN (
     *     SELECT cc.id
     *     FROM m_course_categories cc
     *     WHERE :WHERE
     * )");
     * ```
     *
     * @param string $sqlwithsubquery The SQL subquery or condition fragment to apply.
     * @param string $columnname The column within the subquery that the filter condition applies to (e.g. 'cfd.value').
     *
     * @return void
     * @throws \InvalidArgumentException If required SQL components (SELECT, FROM, WHERE, etc.) are missing.
     */
    public function set_sql(string $sqlwithsubquery, string $columnname) {
        $this->iscustomsql = true;

        if ($this->sql_contains_required_patterns($sqlwithsubquery)) {
            $this->sqlwithsubquery = $sqlwithsubquery;
            $this->subquerycolumn = $columnname;
        }
    }

    /**
     * Sets $fieldid.
     * @param int $fieldid
     * @return void
     */
    public function set_sql_for_fieldid(int $fieldid) {
        $this->fieldid = $fieldid;
    }

    /**
     * Validates that an SQL fragment contains required structural patterns.
     *
     * @param string $sql The SQL string to check.
     * @throws \InvalidArgumentException if any required pattern is missing.
     * @return bool True if all patterns are found.
     */
    protected function sql_contains_required_patterns(string $sql): bool {
        // Normalize case for consistent regex matching.
        $normalized = strtoupper($sql);

        // Define required patterns with human-readable labels.
        $patterns = [
            'IN keyword' => '/\bIN\b/',
            'SELECT keyword' => '/\bSELECT\b/',
            'FROM phrase' => '/\bFROM\b/',
            'table name inside curly braces' => '/\{.*\}/',
            'WHERE keyword' => '/\bWHERE\b/',
        ];

        // Check each pattern and throw exception if any is missing.
        foreach ($patterns as $label => $pattern) {
            if (!preg_match($pattern, $normalized)) {
                throw new \InvalidArgumentException("Your provided SQL must requires the term: {$label}");
            }
        }

        return true;
    }

    /**
     * Replaces the placeholder in the subquery with the generated WHERE condition.
     *
     * @param string $generatedwhere The dynamically generated WHERE condition string.
     * @throws \InvalidArgumentException If the subquery does not contain exactly one placeholder.
     * @return string The completed SQL query ready for execution.
     *
     * @return string
     */
    protected function adjust_sql_condition(string $generatedwhere): string {
        // Find all placeholders like :param1, :table etc.
        preg_match_all('/:([a-zA-Z0-9_]+)/', $this->sqlwithsubquery, $matches);

        $placeholders = $matches[1] ?? [];

        // Check if counts match.
        if (count($placeholders) !== 1) {
            throw new \InvalidArgumentException(sprintf(
                'The placeholder is not found.',
            ));
        }

        // Replace each placeholder with its corresponding value.
        $key = current($placeholders);
        $sql = str_replace(
            ':' . $key,
            $generatedwhere,
            $this->sqlwithsubquery
        );

        return $sql;
    }

    /**
     * Retrieves data for populating filter dropdowns in the UI.
     *
     * If the custom field contains data, returns a count of distinct values.
     * Otherwise, falls back to a generic database column count.
     *
     * @param wunderbyte_table $table The table instance.
     * @param string $key The column or field key to aggregate values for.
     * @return array An associative array of filter options and their counts.
     */
    public static function get_data_for_filter_options(wunderbyte_table $table, string $key) {
        global $DB;

        /** @var customfieldfilter $filter */
        $filter = $table->filters[$key];
        $customfieldid = $filter->fieldid ?? null;
        $iscustomsql = $filter->iscustomsql ?? false;

        // If we dont need count key, we don't run the query to count it but we need the options.
        // So we create it manulally based on the options we passed to the filter.
        if (!$filter->countkeys) {
            $records = [];
            foreach ($filter->options as $k => $v) {
                $option[$key] = $k;
                $option['keycount'] = false;
                $records[$k] = (object) $option;
            }
            return $records;
        }

        // If $iscustomsql is set,
        // so we look inside the query to count the number of records for each value of the given key.
        if ($iscustomsql) {
            $records = filter::get_db_filter_column($table, $key);
        } else {
            // It is not possbile to count the number of records with get_db_filter_column function
            // as it needs the column to be included in the selected fields and we have not this custom
            // filed inside the selected fields when it is not a custom field.
            // The $key param is the name of the column in the table, so we can safely use it directly without fear of injection.
            // As this filter is made specifically for custom fields, we count the number of records for each value of
            // the given $key in the custom field data table.
            $records = self::get_db_filter_column_for_custom_field($table, $key);
        }

        return $records;
    }

    /**
     * Returns the data for the filter if it is a custom field.
     *
     * It is not possbile to count the number of records with get_db_filter_column function
     * as it needs the column to be included in the selected fields and we have not this custom filed inside the selected fields
     * when it is not a custom field.
     * The $key param is the name of the column in the table, so we can safely use it directly without fear of injection.
     * As this filter is made specifically for custom fields, we count the number of records for each value of
     * the given $key in the custom field data table.
     *
     * @param wunderbyte_table $table The table instance.
     * @param string $key The column or field key to aggregate values for.
     * @return array An associative array of filter options and their counts.
     */
    protected static function get_db_filter_column_for_custom_field(wunderbyte_table $table, string $key): array {
        global $DB;

        /** @var customfieldfilter $filter */
        $filter = $table->filters[$key];
        $customfieldid = $filter->fieldid ?? null;

        $sql = "
            SELECT cfd.value as $key, COUNT('$key') as keycount
            FROM {customfield_data} cfd
            WHERE cfd.fieldid = :fieldid
            GROUP BY cfd.value
            ORDER BY $key ASC
        ";
        $params = ['fieldid' => $customfieldid];

        $records = $DB->get_records_sql($sql, $params);

        // Check if there minimum one valid key.
        $novalidkey = true;
        foreach ($records as $k => $v) {
            if (!empty($k) && !empty($v->{$key})) {
                $novalidkey = false;
                break;
            }
        }

        // If there are only empty strings, we don't want the filter to show.
        if (!$records || $novalidkey) {
            return [
                'continue' => true,
            ];
        } else {
            return $records;
        }
    }

    /**
     * Set $countkeys to false.
     *
     * You need to call add_options() and pass your own options when you call this function.
     *
     * @return void
     */
    public function dont_count_keys() {
        $this->countkeys = false;
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
     * Sets `$inuseoperator` to 'ilike'.
     * This makes the generated query use the following format to filter the results.
     *
     *   ('' || ',' || COLUMNVALUE || ',' ILIKE '%desiredvalue%' ESCAPE '\')
     *
     * Note that this may cause the query to perform slower.
     *
     * @return void
     */
    public function use_operator_ilike(): void {
        $this->inuseoperator = 'ilike';
    }

    /**
     * Sets `$inuseoperator` to '='.
     * This makes the generated query use the following format to filter the results.
     *
     * COLUMNVALUE = 'desiredvalue'
     *
     * Using '=' may make the query perform faster than 'ilike', but it only filters results that match the exact value.
     *
     * @return void
     */
    public function use_operator_equal(): void {
        $this->inuseoperator = '=';
    }
}
