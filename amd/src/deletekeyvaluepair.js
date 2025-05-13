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

/*
 * @package    local_shopping_cart
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function() {
    return {
        init: function() {
            document.body.addEventListener("click", function(event) {
                var wrapper = event.target.closest(".remove-key-value");
                if (!wrapper) {
                    return;
                }
                var button = wrapper.querySelector("button");
                if (!button) {
                    return;
                }
                var groupName = button.dataset.groupid;
                var group = document.querySelector('[data-groupname="' + groupName + '"]');

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
    };
});
