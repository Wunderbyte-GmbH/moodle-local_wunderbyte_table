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
 * Provider contract for the recursive {@see treefilter}.
 *
 * @package local_wunderbyte_table
 * @copyright 2026 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\filters\types;

use local_wunderbyte_table\wunderbyte_table;

/**
 * A treefilter is generic: it renders and applies an arbitrarily deep hierarchy without knowing what
 * the nodes mean. All domain knowledge (what the values are, how they nest, how a selected node maps
 * to a SQL condition) lives behind this provider, so the shared wunderbyte_table plugin never depends
 * on the consuming plugin (e.g. local_entities / mod_booking).
 *
 * A provider is referenced by class name (stored in the filter settings), so it must be stateless and
 * reconstructable from that string alone — do not rely on instance state surviving a request.
 */
interface tree_provider {
    /**
     * The values present in the current result set, as a map of node id => number of rows on that node.
     *
     * Called while the filter panel is built, with the table already carrying its base SQL, so an
     * implementation can join whatever relation holds the values to the current result set. The counts
     * drive the per-node labels; the ids drive which branches of the tree are shown.
     *
     * @param wunderbyte_table $table the table whose current result set defines "present"
     * @param string $columnidentifier the filter column identifier (e.g. 'entityid')
     * @return array<int,int> node id => count
     */
    public static function get_present_counts(wunderbyte_table $table, string $columnidentifier): array;

    /**
     * Builds the nested tree of nodes to display, from the present counts.
     *
     * Returns only the nodes that should be shown (typically the occupied nodes plus the ancestors that
     * connect them), each as an object with: id (int), name (string), count (int, rows exactly on this
     * node), total (int, rows in this node's whole subtree) and children (array of the same shape).
     *
     * @param array $presentcounts node id => count, as returned by {@see self::get_present_counts()}
     * @return array<int,object> ordered root nodes (recursive)
     */
    public static function build_tree(array $presentcounts): array;

    /**
     * Returns the SQL WHERE fragment that keeps only rows matching the selected node(s).
     *
     * The fragment is appended inside an "AND ( ... )" wrapper by the caller. Implementations MUST
     * register their bind values on the passed table via {@see wunderbyte_table::set_params()} and
     * reference the returned placeholder names in the fragment (so params stay consistent with the rest
     * of the query). Selecting a node is expected to include its whole subtree. Must return a valid,
     * non-empty boolean expression (return '1=1' to match everything when there is nothing to filter).
     *
     * @param wunderbyte_table $table the table (used to register bind params)
     * @param string $columnidentifier the filter column identifier
     * @param int[] $selectedids the node ids the user selected
     * @return string a boolean SQL expression
     */
    public static function filter_sql(wunderbyte_table $table, string $columnidentifier, array $selectedids): string;
}
