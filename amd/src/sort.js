
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
 * @package    local_wunderbyte_table
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {callLoadData} from 'local_wunderbyte_table/init';
import {getFilterOjects} from 'local_wunderbyte_table/filter';
import {getSearchInput} from 'local_wunderbyte_table/search';

const SELECTOR = {
  SORTCOLUMN: 'select.sortcolumn',
  CHANGESORTORDER: 'a.changesortorder',
  TABLECOLUMN: 'th.wb-table-column',
  WBCONTAINER: ".wunderbyte_table_container_",
  CHECKBOXES: 'input.wb-checkbox',
};

/**
 * Function to initialize the search after rendering the searchbox.
 * @param {*} listContainer
 * @param {*} idstring
 * @param {*} encodedtable
 * @returns {void}
 */
 export function initializeSort(listContainer, idstring, encodedtable) {

    const container = document.querySelector(listContainer);

    const sortColumnElement = container.querySelector(SELECTOR.SORTCOLUMN);
    const sortOrderElement = container.querySelector(SELECTOR.CHANGESORTORDER);

    initializeSortColumns(listContainer, idstring, encodedtable);

    if (!sortColumnElement || !sortOrderElement) {

        return;
    }

    if (!sortColumnElement.dataset.initialized || !sortOrderElement.dataset.initialized) {

      sortColumnElement.dataset.initialized = true;
      sortOrderElement.dataset.initialized = true;

      // We add to listener, on on the select, one on the sortorder button.

      sortColumnElement.addEventListener('change', (e) => {
        callSortAjax(e, idstring, encodedtable);
      });
      sortOrderElement.addEventListener('click', () => {
        callSortAjax(null, idstring, encodedtable);
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

      let checked = e.target.dataset.checked !== "true" ? true : false;

      switch (columnname) {
        case 'wbcheckbox':
          selectAllCheckboxes(idstring, checked);
          e.target.dataset.checked = checked;
          break;
        default:
          callSortAjax(columnname, idstring, encodedtable);
      }
    });
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

  if (typeof event === 'string') {
    // We are sure that we clicked on a column header.
    sortOrderElement = container.querySelector(SELECTOR.TABLECOLUMN + '.' + event);
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

    // Get the sortorder by the icon and apply it.
    if (className.includes('asc')) {

      sortorder = 4;
    } else {

      sortorder = 3;
    }

  } else {
    // Else, we are in the sortorder mode.
    // 3 is ASC, 4 is DESC. We have to find out which is the current mode.

    // Get the sortorder by the icon and change it.
    if (className.includes('asc')) {

      sortorder = 3;
      sortOrderElement.className = className.replace('asc', 'desc');
    } else {

      sortorder = 4;
      sortOrderElement.className = className.replace('desc', 'asc');
    }

    // We also need the sortcolumn name to effectuate the change.
    sortcolumn = sortColumnElement.selectedOptions[0].value;
  }

   const filterobjects = getFilterOjects(idstring);
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
 * Function to read the searchstring from the input leement.
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
 *
 * @param {string} idstring
 * @param {bool} checked
 */
function selectAllCheckboxes(idstring, checked) {

  const container = document.querySelector('#a' + idstring);

  const checkboxes = container.querySelectorAll(SELECTOR.CHECKBOXES);

  // eslint-disable-next-line no-console
  console.log(idstring, checked);

  checkboxes.forEach(x => {
    x.checked = checked;
  });
}