
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

import {getSearchInput} from 'local_wunderbyte_table/search';
import {getSortSelection} from 'local_wunderbyte_table/sort';

import {callLoadData} from 'local_wunderbyte_table/init';
import Templates from 'core/templates';

// These variables are specific to the filter.
var checked = {};
// var categories = {};

/**
 * Initialize Checkboxes.
 * @param {string} selector
 * @param {string} idstring
 * @param {string} encodedtable
 */
 export function initializeCheckboxes(selector, idstring, encodedtable) {

  const filterContainer = document.querySelector(selector + " .wunderbyteTableFilter");

  if (!filterContainer || filterContainer.dataset.initialized) {
    return;
  }

  const allCheckboxes = filterContainer.querySelectorAll("input[type=checkbox]");

  if (!allCheckboxes) {
      return;
  }

  // We create the key for the checked items of this table.
  if (!checked.hasOwnProperty(idstring)) {
    checked[idstring] = {};
  }

  // filterContainer.querySelectorAll(".form-group").forEach(e => {

  //     if (!categories || !categories.hasOwnProperty(idstring)) {
  //       categories[idstring] = [];
  //     }

  //     categories[idstring].push(e.getAttribute("name"));
  //     getChecked(e.getAttribute("name"), selector, idstring);
  // });

  allCheckboxes.forEach(el => {
      el.addEventListener("change", (e) => toggleCheckbox(e, selector, idstring, encodedtable));
  });

  filterContainer.dataset.initialized = true;
}


/**
 * Eventhandler
 * @param {*} e
 * @param {*} selector
 * @param {*} idstring
* @param {*} encodedtable
 */
 export function toggleCheckbox(e, selector, idstring, encodedtable) {

  // eslint-disable-next-line no-console
  console.log('checked', e.target.name);
  getChecked(e.target.name, selector, idstring);

  // eslint-disable-next-line no-console
  console.log('checked', checked);

  // Reload the filtered elements via ajax.

  const filterobjects = getFilterOjects(idstring);
  const searchstring = getSearchInput(idstring);
  const sort = getSortSelection(idstring);

  // eslint-disable-next-line no-console
  console.log('reload from filter ', filterobjects);

  // The filter reloads data from the Server.
  // Because of pages and infinite scroll we don't have the data to do without.
  callLoadData(idstring,
    encodedtable,
    0, // Pagenumber is always rest to 0.
    null,
    sort,
    null,
    null,
    null,
    filterobjects,
    searchstring);
}

  /**
   * Gets an array of checkboxes for every table by idstring.
   * @param {*} name
   * @param {*} selector
   * @param {*} idstring
   * @returns {array}
   */
   export function getChecked(name, selector, idstring) {

    // We might have more than one Table, therefore we first have to get all tables.

    const wbTable = document.querySelector(selector);

    checked[idstring][name] = Array.from(
      wbTable.querySelectorAll("input[name=" + name + "]:checked")
    ).map(function(el) {
      return el.value;
    });

    // eslint-disable-next-line no-console
    console.log("after every step ", checked[idstring]);
  }

  /**
 * Returns json of active filters as json.
 * @param {*} idstring
 * @returns {string}
 */
export function getFilterOjects(idstring) {

  // eslint-disable-next-line no-console
  console.log(checked[idstring]);

  const test = {
    'sport': [
      'eins', 'zwei', 'drei']
  };

  // eslint-disable-next-line no-console
  console.log(test, JSON.stringify(test));

  const testzwei = JSON.stringify(checked[idstring]);

  // eslint-disable-next-line no-console
  console.log(testzwei);

  return JSON.stringify(checked[idstring]);
}

    /**
   * Render the checkboxes for the filer.
   * @param {string} filterjson
   * @param {string} idstring
   * @param {string} encodedtable
   */
     export const renderFilter = (filterjson, idstring, encodedtable) => {

      // We render the filter only once, so if we find it already, we don't render it.

      // eslint-disable-next-line no-console
      console.log(idstring);

      const selector = ".wunderbyte_table_container_" + idstring;
      const container = document.querySelector(selector);
      const filtercontainer = container.querySelector(".wunderbyteTableFilter");

      if (filtercontainer) {
        return;
      }

      Templates.renderForPromise('local_wunderbyte_table/filter', filterjson).then(({html}) => {

          // eslint-disable-next-line no-console
          console.log("encodedtable: ", encodedtable);

          container.insertAdjacentHTML('afterbegin', html);

          initializeCheckboxes(selector, idstring, encodedtable);

          return;
      }).catch(e => {
          // eslint-disable-next-line no-console
          console.log(e);
      });
  };