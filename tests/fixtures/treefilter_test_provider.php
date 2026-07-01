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

use local_wunderbyte_table\wunderbyte_table;

/**
 * A fixed, DB-free tree_provider used to test the generic treefilter in isolation.
 *
 * Tree shape (4 levels): Root(1) → Building(2) → Floor(3) → Room(4); plus Root(1) → BuildingB(5).
 *
 * @package local_wunderbyte_table
 * @copyright 2026 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class treefilter_test_provider implements tree_provider {

    /**
     * Fixed present counts: two options on Room(4), one on BuildingB(5).
     *
     * @param wunderbyte_table $table
     * @param string $columnidentifier
     * @return array<int,int>
     */
    public static function get_present_counts(wunderbyte_table $table, string $columnidentifier): array {
        return [4 => 2, 5 => 1];
    }

    /**
     * Fixed nested tree with subtree totals.
     *
     * @param array<int,int> $presentcounts
     * @return array<int,object>
     */
    public static function build_tree(array $presentcounts): array {
        return [
            (object)[
                'id' => 1, 'name' => 'Root', 'count' => 0, 'total' => 3,
                'children' => [
                    (object)[
                        'id' => 2, 'name' => 'Building', 'count' => 0, 'total' => 2,
                        'children' => [
                            (object)[
                                'id' => 3, 'name' => 'Floor', 'count' => 0, 'total' => 2,
                                'children' => [
                                    (object)['id' => 4, 'name' => 'Room', 'count' => 2, 'total' => 2, 'children' => []],
                                ],
                            ],
                        ],
                    ],
                    (object)['id' => 5, 'name' => 'BuildingB', 'count' => 1, 'total' => 1, 'children' => []],
                ],
            ],
        ];
    }

    /**
     * Builds a simple "column IN (...)" predicate, registering each id as a bind param on the table.
     *
     * @param wunderbyte_table $table
     * @param string $columnidentifier
     * @param int[] $selectedids
     * @return string
     */
    public static function filter_sql(wunderbyte_table $table, string $columnidentifier, array $selectedids): string {
        $names = [];
        foreach ($selectedids as $id) {
            $names[] = ':' . $table->set_params((string)$id);
        }
        return $columnidentifier . ' IN (' . implode(',', $names) . ')';
    }
}
