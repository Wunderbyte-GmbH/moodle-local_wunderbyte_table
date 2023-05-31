
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

import {callLoadData, SELECTORS} from 'local_wunderbyte_table/init';
import Templates from 'core/templates';

// These variables are specific to the filter.
var checked = {};

/**
 * Initialize Checkboxes.
 * @param {string} selector
 * @param {string} idstring
 * @param {string} encodedtable
 */
 export function initializeCheckboxes(selector, idstring, encodedtable) {

  const filterContainer = document.querySelector(selector + SELECTORS.FILTER);

  if (!filterContainer || filterContainer.dataset.initialized) {
    return;
  }
  const selects = filterContainer.querySelectorAll("select[id^='filteroperationselect']");
  const filterElements = filterContainer.querySelectorAll("input[class^='filterelement']");

  if (!filterElements) {
      return;
  }

  // We create the key for the checked items of this table.
  if (!checked.hasOwnProperty(idstring)) {
    checked[idstring] = {};
  }

  filterElements.forEach(el => {

      if (!el.dataset.idstring) {
        el.dataset.idstring = idstring;
      } else {
        el.dataset.idstring2 = idstring;
      }
      el.addEventListener("change", (e) => toggleFilterelement(e, selector, idstring, encodedtable));
  });
  selects.forEach(el => {

    if (!el.dataset.idstring) {
      el.dataset.idstring = idstring;
    } else {
      el.dataset.idstring2 = idstring;
    }
    el.addEventListener("change", (e) => toggleFilterelement(e, selector, idstring, encodedtable));
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
 export function toggleFilterelement(e, selector, idstring, encodedtable) {

  e.stopPropagation();
  e.preventDefault();

  // Check if Checkbox corresponds to datepicker
  if (e.target.dataset.dateelement == 'dateelement') {
    // eslint-disable-next-line no-console
    console.log("dateelement");
    getDates(e, selector, idstring);
  } else {
    getChecked(e.target.name, selector, idstring);
  }


  // Reload the filtered elements via ajax.

  const filterobjects = getFilterObjects(idstring);
  const searchstring = getSearchInput(idstring);
  const sort = getSortSelection(idstring);

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
 * Check if the checkbox of the filterparam is checked and if so write values from date- and timepicker into checked variable.
 * @param {*} e
 * @param {*} selector
 * @param {*} idstring
 */
export function getDates(e, selector, idstring) {

  let name = e.target.name;
  let filtercontainer = e.target.closest(".datepickerform");
  let filtername = e.target.dataset.filtername;
  let filtercheckbox = filtercontainer.querySelector('input[type="checkbox"][id^="' + filtername + '"][name="' + name + '"]');

  let dates = {};
  if (filtercheckbox.checked) {
    // Check how many date- and timepicker are there
    if (filtercheckbox.dataset.timespan === "true") {
      // Getting all the values we need for the filter.
      // Selector defined the operators.
      let select = filtercontainer.querySelector('select[id^="filteroperationselect"][name="' + name + '"]');
      let operator = select.value;

      // First Column to apply the filter to
      let startdatepicker = filtercontainer.querySelector('input[id^="startdate"]');
      let firstcolumn = startdatepicker.dataset.applytocolumn;
      let firstoperator = "";
      let firstColumnValues = {};

      // Second Column to apply the filter to
      let enddatepicker = filtercontainer.querySelector('input[id^="enddate"]');
      let secondcolumn = enddatepicker.dataset.applytocolumn;
      let secondoperator = "";
      let secondColumnValues = {};

      switch (operator) {
        case "within":
          // eslint-disable-next-line no-console
          console.log("within switch operator");
          firstoperator = "<";
          secondoperator = ">";
        break;

        default: break;
      }
      firstColumnValues[firstoperator] = getDateAndTimePickerDataAsUnix(filtercontainer, "startdate");
      secondColumnValues[secondoperator] = getDateAndTimePickerDataAsUnix(filtercontainer, "enddate");

      // eslint-disable-next-line no-console
      console.log(firstColumnValues);

      // Setting values for first columns.
      // Check if key is set in array, otherwise set new key.
      if (firstcolumn && filtername) {
        if (!checked[idstring][firstcolumn]) {
          checked[idstring][firstcolumn] = {};
        }
        checked[idstring][firstcolumn][filtername] = firstColumnValues;
      }
      if (Object.keys(checked[idstring][firstcolumn][filtername]).length < 1) {
        delete checked[idstring][firstcolumn][filtername];
      }
      if (Object.keys(checked[idstring][firstcolumn]).length < 1) {
        delete checked[idstring][firstcolumn];
      }

      // eslint-disable-next-line max-len
      // TODO: unsetting firstcolumn funktioniert noch nicht, setzen zweites, alles fälle im switch, schauen ob angewendet, frage ob labes als key überhaupt sinnvoll?

          // eslint-disable-next-line no-console
          console.log(secondcolumn);
        //checked[idstring][secondcolumn][filtername] = secondColumnValues;

    } else {

      dates[filtercheckbox.dataset.operator] = getDateAndTimePickerDataAsUnix(filtercontainer, "datefilter");

      // Check if key is set in array, otherwise set new key.
      if (name && filtername) {
        if (!checked[idstring][name]) {
          checked[idstring][name] = {};
        }
        checked[idstring][name][filtername] = dates;
      }
      if (Object.keys(checked[idstring][name][filtername]).length < 1) {
        delete checked[idstring][name][filtername];
      }
      if (Object.keys(checked[idstring][name]).length < 1) {
        delete checked[idstring][name];
      }
    }
  }
  // eslint-disable-next-line no-console
  console.log(checked);
}

/**
 * Checking Date and Timepicker for corresponding element and returning Unix Code.
 * @param {*} filtercontainer
 * @param {string} id
 * @returns {string}
 */
export function getDateAndTimePickerDataAsUnix(filtercontainer, id = '') {

  let datepicker = filtercontainer.querySelector('input[type="date"][id*="' + id + '"]');
  let date = new Date(datepicker.value);

  let timepicker = filtercontainer.querySelector('input[type="time"][id*="' + id + '"]');
  let time = timepicker.value;

  let dateTimeString = date.toISOString().split('T')[0] + 'T' + time + ':00.000Z';
  let unixTimestamp = Date.parse(dateTimeString) / 1000;

  return unixTimestamp;
}

/**
 * Generating and displaying filterparams in URL.
 * @param {string} filterobjects
 * @param {string} searchstring
 * @param {string} sort
 * @param {*} dir
 */
export function updateUrlWithFilterSearchSort(filterobjects, searchstring, sort, dir) {

  const url = new URL(window.location.href);

  url.search = "";
  history.replaceState(null, '', url);

  if (filterobjects) {
    url.searchParams.append('wbtfilter', filterobjects);
  }
  if (searchstring !== "" &&
  searchstring !== null) {
    url.searchParams.append('wbtsearch', searchstring);
  }
  if (sort !== "" &&
  sort !== null) {
    url.searchParams.append('tsort', sort);
  }
  if (dir !== null &&
    dir > 0) {
    url.searchParams.append('tdir', dir);
  }

  window.history.pushState(null, null, url.toString());
}

  /**
   * Gets an array of checkboxes for every table by idstring.
   * @param {*} name
   * @param {*} selector
   * @param {*} idstring
   */
   export function getChecked(name, selector, idstring) {

    // We might have more than one Table, therefore we first have to get all tables.

    const wbTable = document.querySelector(selector);

    checked[idstring][name] = Array.from(
      wbTable.querySelectorAll("input[name=" + name + "]")
    ).filter(function(el) {
      return el.checked;
    }).map(function(el) {
      return el.value;
    });

    // If there are no checked boxes, we unset the key alltogether.
    if (checked[idstring][name].length < 1) {
      delete checked[idstring][name];
    }
  }

  /**
 * Returns json of active filters as json.
 * @param {*} idstring
 * @returns {string}
 */
export function getFilterObjects(idstring) {

  if (!(idstring in checked)) {
    return '';
  }

  let hasvalues = false;
  // eslint-disable-next-line no-unused-vars

  for (const [, value] of Object.entries(checked[idstring])) {

    if (value.length > 0 || Object.keys(value).length > 0) {
      hasvalues = true;
    }
  }

  if (!hasvalues) {
    return '';
  }
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

    const selector = SELECTORS.CONTAINER + idstring;
    const container = document.querySelector(selector);
    const filtercontainer = container.querySelector(SELECTORS.FILTER);

    if (filtercontainer) {
      return;
    }

    Templates.renderForPromise('local_wunderbyte_table/filter', filterjson).then(({html}) => {

        container.insertAdjacentHTML('afterbegin', html);

        initializeCheckboxes(selector, idstring, encodedtable);

        return;
    }).catch(e => {
        // eslint-disable-next-line no-console
        console.log(e);
    });
};