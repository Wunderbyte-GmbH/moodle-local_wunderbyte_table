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
 * @module    local_wunderbyte_table
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import SortableList from 'core/sortable_list';
import jQuery from 'jquery';
import {transmitAction} from 'local_wunderbyte_table/actionbutton';

const SELECTOR = {
    ROWS: '.rows-container',
};

var activeinfo = null;
/**
 * Sets up sortable list in the column sort order page.
 * @param {Element} listRoot
 * @param {String} identifier
 * @param {String} idstring
 * @param {String} encodedtable
 */
const initSortableLists = (listRoot, identifier, idstring, encodedtable) => {
    new SortableList(listRoot);

    jQuery(identifier + ' > *').on(SortableList.EVENTS.DROP, function(event, info) {
        // eslint-disable-next-line no-console
        console.log('drop', event.currentTarget);

        if (info.positionChanged && activeinfo != info) {

            activeinfo = info;
            const data = {
                ids: getIdOrder(listRoot),
            };

            const datastring = JSON.stringify(data);

            transmitAction(-1, 'reorderrows', datastring, idstring, encodedtable);
            listRoot.querySelectorAll('tr').forEach(item => item.classList.remove('wb-table-sortable-drag'));
            listRoot.querySelectorAll('tr').forEach(item => item.classList.remove('wb-table-sortable-active'));
        }
    });

    jQuery(identifier + ' > *').on(SortableList.EVENTS.DRAGSTART, (event) => {
        // eslint-disable-next-line no-console
        console.log('dragstart', event.currentTarget);
        event.currentTarget.classList.add('wb-table-sortable-active');
    });

    jQuery(identifier + ' > *').on(SortableList.EVENTS.DRAG, (event) => {
        // eslint-disable-next-line no-console
        console.log('drag', event.currentTarget);
        event.currentTarget.classList.add('wb-table-sortable-drag');
    });

    jQuery(identifier + ' > *').on(SortableList.EVENTS.DAGEND, (event) => {
        // eslint-disable-next-line no-console
        console.log('dragend', event.currentTarget);
        listRoot.querySelectorAll('tr').forEach(item => item.classList.remove('wb-table-sortable-drag'));
        listRoot.querySelectorAll('tr').forEach(item => item.classList.remove('wb-table-sortable-active'));
    });
};

/**
 * Gets the newly reordered columns to display in the question bank view.
 * @param {Element} listRoot
 * @returns {Array}
 */
const getIdOrder = listRoot => {
    const columns = Array.from(listRoot.querySelectorAll('tr[data-id]'))
        .map(column => column.dataset.id);
    columns.pop();
    return columns;
};

/**
 * Function to initialize the search after rendering the searchbox.
 * @param {*} listContainer
 * @param {*} idstring
 * @param {*} encodedtable
 * @returns {void}
 */
export function initializeReordering(listContainer, idstring, encodedtable) {

    const container = document.querySelector(listContainer);

    if (!container) {
        return;
    }

    const rowscontainer = container.querySelector(`${SELECTOR.ROWS}`);

    if (!rowscontainer || rowscontainer.dataset.sortinitialized) {
        return;
    }
    rowscontainer.dataset.sortinitialized = true;

    initSortableLists(rowscontainer, SELECTOR.ROWS, idstring, encodedtable);
}
