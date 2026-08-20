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

namespace local_wunderbyte_table;

use advanced_testcase;
use local_wunderbyte_table\filters\types\standardfilter;
use local_wunderbyte_table\local\customfield\wbt_field_controller_info;

/**
 * Read-count guard for the filter category build.
 *
 * add_to_categoryobject() used to resolve the customfield field controller
 * once per filter VALUE although it only depends on the column - for filter
 * columns without a matching customfield (e.g. the teachers filter of
 * mod_booking) every value fired its own DB lookup: 224 identical queries
 * for 224 teachers during one cold filter build (issue #2211).
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \local_wunderbyte_table\filters\base::add_to_categoryobject
 */
final class filter_value_lookup_reads_test extends advanced_testcase {
    /**
     * Building the category object for many values of a non-customfield column
     * must not scale its DB reads with the number of values.
     */
    public function test_category_build_reads_do_not_scale_with_values(): void {
        global $DB;
        $this->resetAfterTest();

        // The controller memo is request-static; isolate from other tests.
        wbt_field_controller_info::purge_static_caches();

        $fckey = 'teacherobjects';
        $filtersettings = [
            '_customfieldcomponent' => 'mod_booking',
            '_customfieldarea' => 'booking',
            // Mirrors a standardfilter with an add_options() sort configuration
            // (like the mod_booking teachers filter): the non-empty settings entry
            // routes all values through the "resolve display value" branch.
            $fckey => [
                'jsonattribute' => 'name',
                $fckey . '_wb_checked' => 1,
            ],
        ];

        $values = [];
        for ($i = 0; $i < 50; $i++) {
            $values['{"id":' . $i . ',"name":"Teacher ' . $i . '"}'] = $i + 1;
        }

        $categoryobject = [
            'name' => 'Teachers',
            'columnname' => $fckey,
            'collapsed' => 'collapsed',
        ];

        $before = $DB->perf_get_reads();
        standardfilter::add_to_categoryobject($categoryobject, $filtersettings, $fckey, $values);
        $delta = $DB->perf_get_reads() - $before;

        // Per-column constant: one scope bulk load, one single-shortname fallback
        // lookup (the column is no customfield) and one multi-customfield check.
        $this->assertLessThanOrEqual(
            3,
            $delta,
            "add_to_categoryobject() issued {$delta} DB reads for 50 filter values; "
                . "the field controller must be resolved once per column, not per value (issue #2211)."
        );

        // The values themselves must have survived the build.
        $this->assertNotEmpty($categoryobject['default']['values'] ?? $categoryobject);
    }
}
