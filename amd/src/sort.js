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
import {callLoadData} from 'local_wunderbyte_table/init';
import {getFilterObjects} from 'local_wunderbyte_table/filter';
import {getSearchInput} from 'local_wunderbyte_table/search';

const SELECTOR = {
    SORTCOLUMN: 'select.sortcolumn',
    CHANGESORTORDER: 'a.changesortorder',
    TABLECOLUMN: 'th.wb-table-column',
    WBCONTAINER: ".wunderbyte_table_container_",
    CHECKBOXES: 'input.wb-checkbox',
    TABLEHEADERCHECKBOX: 'input.tableheadercheckbox',
};

const SORT_ASC = 4;
const SORT_DESC = 3;

/**
 * Function to initialize the search after rendering the searchbox.
 * @param {*} listContainer
 * @param {*} idstring
 * @param {*} encodedtable
 * @returns {void}
 */
export function initializeSort(listContainer, idstring, encodedtable) {

    const container = document.querySelector(listContainer);

    if (!container) {
        return;
    }

    const sortColumnElement = container.querySelector(SELECTOR.SORTCOLUMN);

    const sortOrderElement = container.querySelector(SELECTOR.CHANGESORTORDER);

    initializeSortColumns(listContainer, idstring, encodedtable);

    if (!sortColumnElement || !sortOrderElement) {

        return;
    }

    if (!sortColumnElement.dataset.initialized || !sortOrderElement.dataset.initialized) {

        sortColumnElement.dataset.initialized = true;
        sortOrderElement.dataset.initialized = true;

        // We add two listener, one on the select, one on the sortorder button.

        sortColumnElement.addEventListener('change', (e) => {
            callSortAjax(e, idstring, encodedtable);
        });
        sortOrderElement.addEventListener('click', () => {
            callSortAjax(null, idstring, encodedtable);
        });
        sortOrderElement.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                callSortAjax(null, idstring, encodedtable);
        }
});
    }
}

/**
 * Initialize Sort Columns in list table.
 * @param {*} listContainer
 * @param {*} idstring
 * @param {*} encodedtable
 */
export function initializeSortColumns(listContainer, idstring, encodedtable) {

    const container = document.querySelector(listContainer);

    const sortColumnHeaders = container.querySelectorAll(SELECTOR.TABLECOLUMN);

    // Add the listeners to column headers.
    sortColumnHeaders.forEach(element => {
        if (element.dataset.initialized) {
            return;
        }
        element.dataset.initialized = true;

        element.addEventListener('click', e => {

            let columnname = element.dataset.columnname;

            switch (columnname) {
                // In case we are in the checkboxes column...
                case 'wbcheckbox':
                    // Checking if checkbox in table header is checked.
                    var checked = container.querySelector(SELECTOR.TABLEHEADERCHECKBOX).checked;

                    // Applying state of header checkbox to checkboxes in table.
                    selectAllCheckboxes(idstring, checked);
                    e.target.dataset.checked = checked;
                    break;
                default:
                    if (element.dataset.sortable) {
                        callSortAjax(columnname, idstring, encodedtable);
                    }
            }
        }
        );
    });
}

/**
 * Execture the two possible sort Ajax calls as reaction on the triggered event.
 * @param {*} event
 * @param {*} idstring
 * @param {*} encodedtable
 */
function callSortAjax(event, idstring, encodedtable) {

    let sortcolumn = null;
    let sortorder = null;
    let reset = null;

    const container = document.querySelector(SELECTOR.WBCONTAINER + idstring);
    const sortColumnElement = container.querySelector(SELECTOR.SORTCOLUMN);

    let sortOrderElement = container.querySelector("a.changesortorder i");
    let className = null;
    let columnheaderIsTrigger = false;

    if (typeof event === 'string') {
        // We are sure that we clicked on a column header.
        sortOrderElement = container.querySelector(SELECTOR.TABLECOLUMN + '.' + event);
        columnheaderIsTrigger = true;
    }

    className = sortOrderElement.className;

    // If we get an event, we are in the sortcolum mode.
    if (event !== null) {

        if (typeof event === 'string') {
            // We have gotten the column directly as string.
            sortcolumn = event;
        } else {
            sortcolumn = event.target.value;
        }

        // We reset only on changed sortcolumn, not on order.
        reset = 1;

        if (!sortcolumn) {
            return;
        }

        // eslint-disable-next-line no-console
        console.log("Classname ", className);
        // Get the sortorder by the icon and apply it.
        if ((className.includes('desc') && columnheaderIsTrigger == false)
            || (className.includes('asc') && columnheaderIsTrigger == true)) {
            sortorder = SORT_DESC;
        } else {
            sortorder = SORT_ASC;
        }

    } else {
        // Else, we are in the sortorder mode.
        // Get the sortorder by the icon and change it.
        if (className.includes('asc')) {

            sortorder = SORT_DESC;
            sortOrderElement.className = className.replace('asc', 'desc');
        } else {

            sortorder = SORT_ASC;
            sortOrderElement.className = className.replace('desc', 'asc');
        }

        // We also need the sortcolumn name to effectuate the change.
        sortcolumn = sortColumnElement.selectedOptions[0].value;
    }

    const filterobjects = getFilterObjects(idstring);
    const searchstring = getSearchInput(idstring);

    callLoadData(idstring,
        encodedtable,
        0, // We set page to 0 because we need to start the container anew.
        sortcolumn,
        null,
        null,
        sortorder,
        reset,
        filterobjects,
        searchstring);
}

/**
 * Function to read the searchstring from the input element.
 * @param {*} idstring
 * @returns {null|string}
 */
export function getSortSelection(idstring) {

    const inputElement = document.querySelector(SELECTOR.WBCONTAINER + idstring + ' select.sort');

    if (!inputElement) {
        return null;
    }

    return '';
}

/**
 * Selection all checkboxes in tablerows with table id.
 * @param {string} idstring
 * @param {bool} checked
 */
function selectAllCheckboxes(idstring, checked) {

    const container = document.querySelector('#a' + idstring);

    const checkboxes = container.querySelectorAll(SELECTOR.CHECKBOXES);

    checkboxes.forEach(x => {
        // Applying status of tableheadercheckbox to all checkboxes.
        if (x.dataset.tableid == idstring) {
            x.checked = checked;
        }
    });
}