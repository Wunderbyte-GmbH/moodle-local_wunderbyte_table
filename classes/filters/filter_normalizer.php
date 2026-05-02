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
 * Normalises and validates structured filter rules for the Wunderbyte table API.
 *
 * This class is the single point of truth for what constitutes a valid structured
 * filter rule. It is used both by the external webservice (Phase 2) and by the
 * table class itself when applying filter rules to SQL (Phase 3/4).
 *
 * @package local_wunderbyte_table
 * @copyright 2025 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\filters;

use moodle_exception;

/**
 * Validates and normalises structured filter rules.
 *
 * A structured filter rule is an associative array with the following keys:
 *   - column   (string, required): the DB column to filter on.
 *   - operator (string, required): one of the allowed operator names (see OPERATOR_MAP).
 *   - value    (string, optional): scalar value for most operators.
 *   - value2   (string, optional): second scalar value for the 'between' operator.
 *   - values   (array,  optional): list of values for the 'in' operator.
 *
 * The normalizer is intentionally strict:
 *   - unknown operators → rule silently discarded.
 *   - column name containing unsafe characters → rule silently discarded.
 *   - value exceeding MAX_STRING_LENGTH → rule silently discarded.
 *   - IN list exceeding MAX_IN_LIST → moodle_exception thrown.
 *   - total rules count exceeding MAX_RULES → moodle_exception thrown.
 *
 * Column whitelisting against the actual table columns is NOT done here;
 * that check happens in wunderbyte_table::apply_structured_filter_rules().
 */
class filter_normalizer {

    /**
     * Maximum number of filter rules allowed per request.
     */
    const MAX_RULES = 50;

    /**
     * Maximum length of a single string value (column name or scalar value).
     */
    const MAX_STRING_LENGTH = 255;

    /**
     * Maximum number of values in an IN list.
     */
    const MAX_IN_LIST = 100;

    /**
     * Maps the public-facing operator names to their SQL equivalents.
     *
     * Only operators in this map are accepted. Any other value is rejected.
     */
    const OPERATOR_MAP = [
        'eq'        => '=',
        'ne'        => '<>',
        'lt'        => '<',
        'lte'       => '<=',
        'gt'        => '>',
        'gte'       => '>=',
        'like'      => 'LIKE',
        'notlike'   => 'NOT LIKE',
        'in'        => 'IN',
        'between'   => 'BETWEEN',
        'isnull'    => 'IS NULL',
        'isnotnull' => 'IS NOT NULL',
    ];

    /**
     * Validates and normalises an array of raw filter rules.
     *
     * Invalid individual rules are silently discarded. Global limit violations
     * (too many rules, IN list too large) throw a generic moodle_exception so that
     * no DB-specific detail leaks to the caller.
     *
     * @param array $filterrules Raw rule array, typically from the external API.
     * @return array  Normalised array of valid rules (may be empty).
     * @throws moodle_exception  When the total number of rules exceeds MAX_RULES.
     */
    public static function normalize_structured(array $filterrules): array {
        if (count($filterrules) > self::MAX_RULES) {
            throw new moodle_exception('filtertoomanyrulesexception', 'local_wunderbyte_table');
        }

        $normalized = [];
        foreach ($filterrules as $rule) {
            if (!is_array($rule)) {
                continue;
            }
            $cleaned = self::validate_rule($rule);
            if ($cleaned !== null) {
                $normalized[] = $cleaned;
            }
        }
        return $normalized;
    }

    /**
     * Validates a single raw filter rule and returns the cleaned version.
     *
     * Returns null when the rule is structurally invalid so the caller can
     * skip it without leaking details about why it was rejected.
     *
     * @param array $rule  Associative array with at least 'column' and 'operator'.
     * @return array|null  Cleaned rule array, or null when the rule is invalid.
     * @throws moodle_exception  When the IN list exceeds MAX_IN_LIST.
     */
    public static function validate_rule(array $rule) {
        // Column: must be non-empty and start with a letter, followed by alphanumeric chars and underscores.
        $column = trim((string)($rule['column'] ?? ''));
        if ($column === '' || !preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $column)) {
            return null;
        }
        if (strlen($column) > self::MAX_STRING_LENGTH) {
            return null;
        }

        // Operator: must be a key in OPERATOR_MAP.
        $operator = trim((string)($rule['operator'] ?? ''));
        if (!array_key_exists($operator, self::OPERATOR_MAP)) {
            return null;
        }

        $cleaned = [
            'column'   => $column,
            'operator' => $operator,
        ];

        // Validate the value(s) according to operator family.
        if (in_array($operator, ['isnull', 'isnotnull'], true)) {
            // These operators need no value at all.
            return $cleaned;
        }

        if ($operator === 'in') {
            $values = $rule['values'] ?? [];
            if (!is_array($values) || count($values) === 0) {
                return null;
            }
            if (count($values) > self::MAX_IN_LIST) {
                throw new moodle_exception('filterinlisttoolarge', 'local_wunderbyte_table');
            }
            $cleanedvalues = [];
            foreach ($values as $v) {
                $sv = (string)$v;
                if (strlen($sv) > self::MAX_STRING_LENGTH) {
                    return null;
                }
                $cleanedvalues[] = $sv;
            }
            $cleaned['values'] = $cleanedvalues;
            return $cleaned;
        }

        if ($operator === 'between') {
            $value  = $rule['value']  ?? null;
            $value2 = $rule['value2'] ?? null;
            if ($value === null || $value2 === null) {
                return null;
            }
            $sv  = (string)$value;
            $sv2 = (string)$value2;
            if (strlen($sv) > self::MAX_STRING_LENGTH || strlen($sv2) > self::MAX_STRING_LENGTH) {
                return null;
            }
            $cleaned['value']  = $sv;
            $cleaned['value2'] = $sv2;
            return $cleaned;
        }

        // All remaining scalar operators require exactly one value.
        $value = $rule['value'] ?? null;
        if ($value === null) {
            return null;
        }
        $sv = (string)$value;
        if (strlen($sv) > self::MAX_STRING_LENGTH) {
            return null;
        }
        $cleaned['value'] = $sv;
        return $cleaned;
    }
}
