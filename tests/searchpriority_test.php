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
 * Tests for the priority of the search results (define_priority_columns).
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_wunderbyte_table;

use advanced_testcase;
use cache_helper;
use local_wunderbyte_table\external\load_data;
use local_wunderbyte_table\local\customfield\wbt_field_controller_info;
use moodle_url;

/**
 * Tests for the priority of the search results (define_priority_columns).
 *
 * @package local_wunderbyte_table
 * @category test
 * @copyright 2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \local_wunderbyte_table\wunderbyte_table::define_priority_columns
 * @covers \local_wunderbyte_table\wunderbyte_table::apply_searchtext
 * @covers \local_wunderbyte_table\wunderbyte_table::get_sql_sort
 */
final class searchpriority_test extends advanced_testcase {
    /**
     * Tests set up.
     */
    public function setUp(): void {
        global $PAGE;
        parent::setUp();
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $PAGE->set_url(new moodle_url('/local/wunderbyte_table/tests/searchpriority_test.php'));
        wbt_field_controller_info::purge_static_caches();
        wbt_field_controller_info::purge_resolved_value_mapping_cache();
    }

    /**
     * Mandatory clean-up after each test.
     */
    public function tearDown(): void {
        parent::tearDown();
        cache_helper::purge_by_event('changesinwunderbytetable');
        $_POST = [];
    }

    /**
     * The results are ordered by the groups of the priority columns, the normal sorting applies inside a group.
     *
     * @dataProvider priority_provider
     * @param array $prioritycolumns
     * @param string $searchtext
     * @param array $expectedfullnames fullnames of the found courses in the expected order
     */
    public function test_priority_order(array $prioritycolumns, string $searchtext, array $expectedfullnames): void {
        $this->create_courses();

        $table = $this->create_table('searchpriority' . md5(json_encode([$prioritycolumns, $searchtext])));
        $table->define_priority_columns($prioritycolumns);

        $this->assertSame($expectedfullnames, $this->search_fullnames($table, $searchtext));
    }

    /**
     * Data provider for test_priority_order.
     *
     * @return array
     */
    public static function priority_provider(): array {
        return [
            'without priority columns the order does not change' => [
                [],
                '424',
                ['424', 'Kurs 424 Yoga', 'Pilates', 'Tanz', 'Yoga für Anfänger'],
            ],
            'one word: equals and contains, column by column, then the rest' => [
                ['shortname', 'fullname'],
                '424',
                // Shortname equals, shortname contains, fullname equals, fullname contains, only in the summary.
                ['Pilates', 'Tanz', '424', 'Kurs 424 Yoga', 'Yoga für Anfänger'],
            ],
            'the order of the priority columns counts' => [
                ['fullname', 'shortname'],
                '424',
                ['424', 'Kurs 424 Yoga', 'Pilates', 'Tanz', 'Yoga für Anfänger'],
            ],
            'several words: equals ignores the case, contains needs every word in any order' => [
                ['shortname', 'fullname'],
                'Yoga Anfänger',
                // Equals, contains both words (sorted by fullname), words only in the summary.
                ['yoga anfänger', 'Anfänger Yoga', 'Yoga für Anfänger', 'Zumba'],
            ],
            'several spaces between the words' => [
                ['shortname', 'fullname'],
                'Yoga   Anfänger',
                ['yoga anfänger', 'Anfänger Yoga', 'Yoga für Anfänger', 'Zumba'],
            ],
            'wildcards in the search text are no wildcards for the priority' => [
                ['shortname'],
                '4_4',
                // The search itself treats "_" as a wildcard and finds "424", the priority does not:
                // no shortname equals or contains "4_4", so the order does not change.
                ['424', 'Kurs 424 Yoga', 'Pilates', 'Tanz', 'Yoga für Anfänger'],
            ],
        ];
    }

    /**
     * Select customfields compare the label of the option, not the stored index.
     */
    public function test_priority_of_select_customfield(): void {
        global $DB;

        $generator = $this->getDataGenerator();
        $category = $generator->create_custom_field_category([
            'component' => 'core_course',
            'area' => 'course',
            'name' => 'WBT priority category',
        ]);
        $field = $generator->create_custom_field([
            'categoryid' => $category->get('id'),
            'type' => 'select',
            'shortname' => 'wbtprioritysport',
            'name' => 'Sport',
            'configdata' => ['options' => "Yoga\nTennis", 'defaultvalue' => ''],
        ]);

        // The select field stores the index of the option: 1 => Yoga, 2 => Tennis.
        $generator->create_course(['fullname' => 'Kurs B', 'shortname' => 'KB', 'customfield_wbtprioritysport' => 1]);
        $generator->create_course(['fullname' => 'Kurs A', 'shortname' => 'KA', 'summary' => 'Yoga',
            'customfield_wbtprioritysport' => 2]);
        $this->assertEquals('1', $DB->get_field('customfield_data', 'value', ['fieldid' => $field->get('id'),
            'instanceid' => $DB->get_field('course', 'id', ['shortname' => 'KB'])]));

        $from = "(SELECT c.id, c.fullname, c.shortname, c.summary, cfd.value AS wbtprioritysport
                    FROM {course} c
               LEFT JOIN {customfield_data} cfd ON cfd.instanceid = c.id AND cfd.fieldid = :wbtfieldid
                   WHERE c.id > 1) s1";
        $params = ['wbtfieldid' => $field->get('id')];

        // Without priority: sorted by fullname. "Kurs B" is found by the label of the select field.
        $table = $this->create_table('searchpriorityselect1', $from, $params);
        $table->define_fulltextsearchcolumns(['fullname', 'summary', 'wbtprioritysport']);
        $this->assertSame(['Kurs A', 'Kurs B'], $this->search_fullnames($table, 'Yoga'));

        // With priority: the label "Yoga" equals the search text, so "Kurs B" comes first.
        $table = $this->create_table('searchpriorityselect2', $from, $params);
        $table->define_fulltextsearchcolumns(['fullname', 'summary', 'wbtprioritysport']);
        $table->define_priority_columns(['wbtprioritysport']);
        $this->assertSame(['Kurs B', 'Kurs A'], $this->search_fullnames($table, 'Yoga'));

        // The stored index is not compared: "1" is not the label of any option.
        $table = $this->create_table('searchpriorityselect3', $from, $params);
        $table->define_fulltextsearchcolumns(['fullname', 'summary', 'wbtprioritysport']);
        $table->define_priority_columns(['wbtprioritysport']);
        $this->assertSame(['Kurs A', 'Kurs B'], $this->search_fullnames($table, 'Kurs'));
    }

    /**
     * The priority also applies when the search comes from the webservice, and a later
     * request without search text uses the normal order again.
     *
     * @runInSeparateProcess
     */
    public function test_priority_via_webservice(): void {
        $this->create_courses();

        $table = $this->create_table('searchpriorityws');
        $table->define_priority_columns(['shortname', 'fullname']);
        $encodedtable = $table->return_encoded_table();

        $result = load_data::execute($encodedtable, 0, null, null, null, null, null, null, '424');
        $this->assertSame(
            ['Pilates', 'Tanz', '424', 'Kurs 424 Yoga', 'Yoga für Anfänger'],
            $this->get_fullnames_from_result($result)
        );

        $result = load_data::execute($encodedtable, 0, null, null, null, null, null, null, '');
        $fullnames = $this->get_fullnames_from_result($result);
        $this->assertSame('424', reset($fullnames));
        $this->assertCount(8, $fullnames);
    }

    /**
     * Creates the courses of the tests.
     *
     * @return void
     */
    private function create_courses(): void {
        $courses = [
            ['shortname' => '424', 'fullname' => 'Pilates'],
            ['shortname' => '4240', 'fullname' => 'Tanz'],
            ['shortname' => 'S3', 'fullname' => '424'],
            ['shortname' => 'S4', 'fullname' => 'Kurs 424 Yoga'],
            ['shortname' => 'S5', 'fullname' => 'Yoga für Anfänger', 'summary' => 'Trainer 424'],
            ['shortname' => 'S6', 'fullname' => 'Zumba', 'summary' => 'Yoga Anfänger'],
            ['shortname' => 'S7', 'fullname' => 'Anfänger Yoga'],
            ['shortname' => 'S8', 'fullname' => 'yoga anfänger'],
        ];
        foreach ($courses as $course) {
            $course['summary'] = $course['summary'] ?? '';
            $this->getDataGenerator()->create_course($course);
        }
    }

    /**
     * Creates a table of the courses, searched in shortname, fullname and summary and sorted by fullname.
     *
     * @param string $uniqueid
     * @param string $from
     * @param array $params
     * @return demo_table
     */
    private function create_table(string $uniqueid, string $from = '', array $params = []): demo_table {
        $table = new demo_table($uniqueid);
        $table->define_baseurl(new moodle_url('/local/wunderbyte_table/tests/searchpriority_test.php'));
        $table->define_columns(['id', 'fullname', 'shortname']);
        $table->define_headers(['id', 'fullname', 'shortname']);
        $table->define_fulltextsearchcolumns(['shortname', 'fullname', 'summary']);
        $table->sortable(true, 'fullname', SORT_ASC);
        if (empty($from)) {
            $from = "(SELECT id, fullname, shortname, summary FROM {course} WHERE id > 1) s1";
        }
        $table->set_filter_sql('*', $from, '1=1', '', $params);
        $table->pageable(true);
        return $table;
    }

    /**
     * Searches the table and returns the fullnames of the results in their order.
     *
     * @param wunderbyte_table $table
     * @param string $searchtext
     * @return array
     */
    private function search_fullnames(wunderbyte_table $table, string $searchtext): array {
        $table->apply_searchtext($searchtext);
        $table->printtable(100, false);
        return array_values(array_map(fn($row) => $row->fullname, $table->rawdata));
    }

    /**
     * Returns the fullnames of the rows returned by the webservice load_data.
     *
     * @param array $result
     * @return array
     */
    private function get_fullnames_from_result(array $result): array {
        $content = json_decode($result['content']);
        $fullnames = [];
        foreach ($content->table->rows ?? [] as $row) {
            // The demo table renders cards, so the columns are in the card body.
            foreach ($row->cardbody ?? [] as $field) {
                if ($field->key === 'fullname') {
                    $fullnames[] = $field->value;
                }
            }
        }
        return $fullnames;
    }
}
