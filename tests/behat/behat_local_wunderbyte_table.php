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
 * Defines message providers (types of messages being sent)
 *
 * @package local_wunderbyte_table
 * @copyright 2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @author Andrii Semenets
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * To create wunderbyte_table specific behat scearios.
 */
class behat_local_wunderbyte_table extends behat_base {
    /**
     * Clean wbtable cache
     * @Given /^I clean wbtable cache$/
     * @return void
     */
    public function i_clean_wbtable_cache() {
        global $DB;
        // Mandatory clean-up.
        cache_helper::purge_by_event('changesinwunderbytetable');
        cache_helper::purge_by_event('setbackencodedtables');
        cache_helper::purge_by_event('setbackfilters');
        $sql = "DELETE FROM {local_wunderbyte_table}
                      WHERE hash LIKE '%_filterjson'
                         OR hash LIKE '%_sqlquery'";
        $DB->execute($sql);
        $_POST = [];
    }

    /**
     * Fire one of core/sortable_list's (jQuery) events on the first row of a sortable wunderbyte table.
     *
     * Real mouse dragging is too flaky in behat, so the events are triggered the same way
     * core/sortable_list triggers them. The event name is taken from SortableList.EVENTS,
     * so a wrong constant name in the plugin's js is caught here.
     *
     * @Given /^I trigger the sortable list event "(?P<event_string>DRAGSTART|DRAG|DROP|DRAGEND)" on the first row of the wunderbyte table$/
     * @param string $event Key of SortableList.EVENTS.
     * @return void
     */
    public function i_trigger_sortable_list_event(string $event) {
        $script = "
            M.util.js_pending('wbsortablelistevent');
            require(['core/sortable_list', 'jquery'], function(SortableList, jQuery) {
                var row = jQuery('.rows-container > *').first();
                row.trigger(SortableList.EVENTS.{$event}, {positionChanged: false});
                M.util.js_complete('wbsortablelistevent');
            });
        ";
        $this->getSession()->executeScript($script);
        $this->wait_for_pending_js();
    }
}
