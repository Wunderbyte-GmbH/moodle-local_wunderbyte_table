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
 * @author Mahdi Poustini
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\filters\types;
use local_wunderbyte_table\filters\base;
use local_wunderbyte_table\wunderbyte_table;
use moodle_exception;

/**
 * Wunderbyte table class is an extension of table_sql.
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
     * Subquery string
     * @var string
     */
    protected string $sqlwithsubquery = '';

    /**
     * Sub query params.
     * @var string
     */
    protected string $subquerycolumn;

    /**
     * SQL query that fetchs the data from the source. It should return 2 mandatroy elements and 1 optional column.
     * SELECT x as id, y as name, z as description from {table}.
     * @var string
     */
    protected static string $filteroptionsquery;

    /**
     * Apply the filter of hierachical class. Logic of Standardfilter can be applied here.
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
        if (empty($this->sqlwithsubquery)) {
            throw new moodle_exception(
                'missing_subquery',
                'local_wunderbyte_table',
                '',
                null,
                'Customfieldfilter: No SQL subquery provided.
                You must call set_sql() before applying the filter, otherwise the filter cannot be applied.'
            );
        }

        global $DB;
        $filtercounter = 1;
        $generatedwhere = '';
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

        // Replaces placeholder with double dots (:) with the generated where condition.
        $filter .= $this->adjust_sql_condition($generatedwhere);
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
     * @param string $columnname
     *
     * @return void
     */
    public function set_sql(string $sqlwithsubquery, string $columnname) {
        if ($this->sql_contains_required_patterns($sqlwithsubquery)) {
            $this->sqlwithsubquery = $sqlwithsubquery;
            $this->subquerycolumn = $columnname;
        }
    }

    /**
     * Sets $filedid.
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
     * Replace placeholders in an SQL-like string with parameter values.
     *
     * @param string $generatedwhere
     * @throws \InvalidArgumentException
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
     * Get standard filter options.
     * @param wunderbyte_table $table
     * @param string $key
     * @return array
     */
    // public static function get_data_for_filter_options(wunderbyte_table $table, string $key) {
    //     global $DB;

    //     if (empty(self::$filteroptionsquery)) {
    //         return \local_wunderbyte_table\filter::get_db_filter_column($table, $key);
    //     }

    //     $records = $DB->get_records_sql(self::$filteroptionsquery, []);

    //     $returnarray = [];

    //     foreach ($records as $record) {
    //         $item = new \stdClass();
    //         $item->$key = "{$record->name}";
    //         $returnarray[$record->id] = $item;
    //     }
    //     return $returnarray ?? [];
    // }

    /**
     *
     */
    public function set_filter_options_query(string $query) {
        self::$filteroptionsquery = $query;
    }
}
