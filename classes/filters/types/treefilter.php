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
 * Recursive, arbitrarily deep hierarchical filter.
 *
 * @package local_wunderbyte_table
 * @copyright 2026 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table\filters\types;

use local_wunderbyte_table\wunderbyte_table;
use stdClass;

/**
 * A hierarchical filter that renders and applies a tree of any depth (Location → Building → Floor → Room …).
 *
 * This is a NEW, separate filter type — it does NOT change {@see hierarchicalfilter} (which is used
 * externally and is intentionally two-level). treefilter inherits it only for the render scaffold and
 * overrides the three pieces that must become recursive/provider-driven: fetching the present values,
 * building the (nested) panel structure and applying the SQL condition.
 *
 * All domain knowledge lives behind a {@see tree_provider} referenced by class name (stored in the
 * filter settings), so this shared plugin stays free of any dependency on the consuming plugin.
 */
class treefilter extends hierarchicalfilter {
    /**
     * Sets the provider class that supplies the tree data and the SQL condition for this filter.
     *
     * Stored as a plain class-name string in the filter settings so it survives serialisation and is
     * available on every (also AJAX-reconstructed) request.
     *
     * @param string $providerclass fully qualified class name implementing {@see tree_provider}
     * @return void
     */
    public function set_treeprovider(string $providerclass): void {
        $this->options['wbtreeprovider'] = $providerclass;
    }

    /**
     * Resolves the provider class stored for a column, from a settings array (render side).
     *
     * @param array $settings the per-column filter settings (filtersettings[$fckey] or subcolumns entry)
     * @return string the provider class name, or '' if none/invalid
     */
    protected static function resolve_provider(array $settings): string {
        $providerclass = $settings['wbtreeprovider'] ?? '';
        return (is_string($providerclass) && $providerclass !== '' && class_exists($providerclass)) ? $providerclass : '';
    }

    /**
     * Fetches the values present in the current result set via the provider, shaped like the rows the
     * generic filter builder expects (one object per node carrying the column value and its count).
     *
     * @param wunderbyte_table $table
     * @param string $key
     * @return array
     */
    public static function get_data_for_filter_options(wunderbyte_table $table, string $key) {
        $providerclass = self::resolve_provider($table->subcolumns['datafields'][$key] ?? []);
        if ($providerclass === '') {
            return [];
        }

        $counts = $providerclass::get_present_counts($table, $key);
        $rows = [];
        foreach ($counts as $id => $count) {
            $row = new stdClass();
            $row->{$key} = (int)$id;
            $row->keycount = (int)$count;
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Builds the recursive tree context for the mustache template under the additive 'treehierarchy' key.
     *
     * The existing 'default'/'hierarchy' structures are untouched; a treefilter simply adds its own key,
     * rendered by the recursive filter_treenode partial.
     *
     * @param array $categoryobject
     * @param array $filtersettings
     * @param string $fckey
     * @param array $values node id => count, as collapsed by the generic filter builder
     * @return void
     */
    public static function add_to_categoryobject(array &$categoryobject, array $filtersettings, string $fckey, array $values) {
        $providerclass = self::resolve_provider($filtersettings[$fckey] ?? []);
        if ($providerclass === '') {
            return;
        }

        $nodes = $providerclass::build_tree($values);
        if (empty($nodes)) {
            // Nothing occupied → do not add the filter (mirrors the other filter types).
            return;
        }

        $identifierseen = [];
        $categoryobject['treehierarchy'] = [
            'istoplist' => true,
            'children' => self::build_mustache_nodes($nodes, $fckey, $identifierseen),
        ];
    }

    /**
     * Recursively converts provider nodes ({id, name, count, total, children[]}) into mustache node
     * contexts ({key, value, count, identifier, category, haschildren, children[]}).
     *
     * @param array $nodes provider nodes
     * @param string $fckey the filter column identifier (used as the checkbox field name/category)
     * @param array $identifierseen accumulator of used identifiers, to keep DOM ids unique
     * @return array
     */
    protected static function build_mustache_nodes(array $nodes, string $fckey, array &$identifierseen): array {
        $out = [];
        foreach ($nodes as $node) {
            $baseidentifier = preg_replace('/[^a-zA-Z0-9\-_\.]/', '-', 'id' . (int)$node->id);
            $identifier = $baseidentifier;
            $i = 1;
            while (in_array($identifier, $identifierseen, true)) {
                $identifier = $baseidentifier . $i;
                $i++;
            }
            $identifierseen[] = $identifier;

            $children = self::build_mustache_nodes($node->children ?? [], $fckey, $identifierseen);
            $out[] = [
                // Strip tags / decode &amp; so the label is clean, consistent with the other filter types.
                'key' => strip_tags(str_replace('&amp;', '&', (string)$node->name)),
                'value' => (int)$node->id,
                'count' => (int)($node->total ?? 0),
                'identifier' => $identifier,
                'category' => $fckey,
                'haschildren' => !empty($children),
                // Shadow the top-level flag: mustache resolves missing keys up the context stack, so
                // without this every nested list would inherit istoplist=true from the outermost context.
                'istoplist' => false,
                'children' => $children,
            ];
        }
        return $out;
    }

    /**
     * Applies the tree filter: delegates the SQL condition to the provider, which expands each selected
     * node to its whole subtree and returns a boolean expression (with its bind params already
     * registered on the table). Emitted inside the caller's "AND ( ... )" wrapper.
     *
     * @param string $filter
     * @param string $columnname
     * @param mixed $categoryvalue selected node ids
     * @param wunderbyte_table $table
     * @return void
     */
    public function apply_filter(
        string &$filter,
        string $columnname,
        $categoryvalue,
        wunderbyte_table &$table
    ): void {
        $providerclass = self::resolve_provider(
            $table->subcolumns['datafields'][$columnname] ?? ['wbtreeprovider' => $this->options['wbtreeprovider'] ?? '']
        );

        $ids = [];
        foreach ((array)$categoryvalue as $value) {
            $id = (int)$value;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        if ($providerclass === '' || empty($ids)) {
            // Nothing we can safely constrain on — match everything so the AND ( ... ) wrapper stays valid.
            $filter .= ' 1=1 ';
            return;
        }

        $filter .= ' ' . $providerclass::filter_sql($table, $columnname, $ids) . ' ';
    }
}
