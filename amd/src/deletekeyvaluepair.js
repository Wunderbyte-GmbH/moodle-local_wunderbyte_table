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
 * Removal of key/value pair groups in the edit table form.
 *
 * @module     local_wunderbyte_table/deletekeyvaluepair
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Register the click handler that removes a key/value group.
 * @returns {void}
 */
export function init() {
    document.body.addEventListener("click", function(event) {
        const wrapper = event.target.closest(".remove-key-value");
        if (!wrapper) {
            return;
        }
        const button = wrapper.querySelector("button");
        if (!button) {
            return;
        }
        const groupName = button.dataset.groupid;
        let group = document.querySelector('[data-groupname="' + groupName + '"]');

        if (group) {
            group.remove();
        } else {
            group = document.getElementById(groupName);
            if (group) {
                group.remove();
            }
            // eslint-disable-next-line no-console
            console.warn("Group not found:", groupName);
        }
    });
}
