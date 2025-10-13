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
class customfieldfilter extends standardfilter {
    /**
     * Subquery string
     * @var string
     */
    protected string $sqlwithsubquery = '';

    /**
     * Sub query params.
     * @var array
     */
    protected array $subqueryparams;

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
        if (empty($this->subquery)) {
            throw new moodle_exception(
                'missing_subquery',
                'local_wunderbyte_table',
                '',
                null,
                'Customfieldfilter: No SQL subquery provided.
                You must call set_sql() before applying the filter, otherwise the filter cannot be applied.'
            );
        }
        // $filter .= ' ' . $this->sqlwithsubquery . ' ';

        global $DB;
        $filtercounter = 1;

        foreach ($categoryvalue as $key => $value) {
            $generatedwhere .= $filtercounter == 1 ? "" : " OR ";
            // Apply special filter here.
            if (
                isset($table->subcolumns['datafields'][$columnname]['jsonattribute'])
            ) {
                    $paramsvaluekey = $table->set_params("%" . $value . "%");
                    $generatedwhere .= $DB->sql_like("$columnname", ":$paramsvaluekey", false);
            } else {
                // We want to find the value in an array of values.
                // Therefore, we have to use or as well.
                // First, make sure we have enough params we can use..
                $separator = $table->subcolumns['datafields'][$columnname]['explode'] ?? ",";
                $paramsvaluekey = $table->set_params('%' . $separator . $value . $separator . '%', true);
                $escapecharacter = wunderbyte_table::return_escape_character($value);
                $concatvalue = $DB->sql_concat("'$separator'", $columnname, "'$separator'");
                $generatedwhere .= $DB->sql_like("$concatvalue", ":$paramsvaluekey", false, false, false, $escapecharacter);
            }
            $filtercounter++;
        }

        $filter .= $this->replace_sql_params(
            $this->sqlwithsubquery,
            $this->subqueryparams,
            $generatedwhere
        );
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
     *     WHERE cc.name LIKE '%Math%'
     *         OR cc.name ILIKE '%Computer%'
     * )");
     * ```
     *
     * @param string $sqlwithsubquery The SQL subquery or condition fragment to apply.
     *
     * @return void
     */
    public function set_sql(string $sqlwithsubquery) {
        if ($this->sql_contains_required_patterns($sqlwithsubquery)) {
            $this->sqlwithsubquery = $sqlwithsubquery;
        }
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
            'id FROM phrase' => '/\bID\s+FROM\b/',
            'table name inside curly braces' => '/\{[A-Z0-9_]+\}/',
            'WHERE keyword' => '/\bWHERE\b/',
        ];

        // Check each pattern and throw exception if any is missing.
        foreach ($patterns as $label => $pattern) {
            if (!preg_match($pattern, $normalized)) {
                throw new \InvalidArgumentException("Missing required SQL term: {$label}");
            }
        }

        return true;
    }

    /**
     * Sets replacements in the subquery.
     * @param array $params
     * @return void
     */
    public function set_subquery_params(array $params = []) {
        $this->subqueryparams = $params;
    }

    /**
     * Replace placeholders in an SQL-like string with parameter values.
     *
     * @param string $sql     The SQL string containing placeholders (e.g. :table, :param1).
     * @param array  $params  Associative array of parameters ['table' => 'categories'].
     * @throws \InvalidArgumentException If number of placeholders does not match $params,
     *                                  or a placeholder has no corresponding key.
     * @return string The string with placeholders replaced.
     */
    protected function replace_sql_params(string $sql, array $params, string $generatedwhere): string {
        // Find all placeholders like :param1, :table etc.
        preg_match_all('/:([a-zA-Z0-9_]+)/', $sql, $matches);

        $placeholders = $matches[1] ?? [];

        // Check if counts match.
        if (count($placeholders) !== count($params)) {
            throw new \InvalidArgumentException(sprintf(
                'Parameter count mismatch: found %d placeholders in string but %d params provided.',
                count($placeholders),
                count($params)
            ));
        }

        // Replace each placeholder with its corresponding value.
        foreach ($placeholders as $key) {
            if (!array_key_exists($key, $params)) {
                throw new \InvalidArgumentException("Missing parameter value for placeholder :{$key}");
            }

            // Escape value safely (you can adapt escaping rules as needed).
            $value = $params[$key];
            if (empty($value)) {
                $value = $generatedwhere;
            }
            $sql = str_replace(':' . $key, $value, $sql);
        }

        return $sql;
    }
}
