
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

  const allCheckboxes = filterContainer.querySelectorAll("input[type=checkbox]");

  const filterElements = filterContainer.querySelectorAll("input[class^='filterelement']");

  if (!filterElements) {
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

  filterElements.forEach(el => {

      if (!el.dataset.idstring) {
        el.dataset.idstring = idstring;
      } else {
        el.dataset.idstring2 = idstring;
      }
      // eslint-disable-next-line no-console
      console.log("filterelementToggle");
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
 * Get the data from datepicker and timepicker
 * @param {*} e
 * @param {*} selector
 * @param {*} idstring
 */
export function getDates(e, selector, idstring) {

      // We might have more than one Table, therefore we first have to get all tables.
      const wbTable = document.querySelector(selector);

      // Check if element is a checkbox

      if (e.target.type == "checkbox") {
        // eslint-disable-next-line no-console
        console.log ("its a checkbox");
      } else {
        // eslint-disable-next-line no-console
        console.log ("NOT a checkbox");
      }

      let name = e.target.name;
      let dates = Array.from(
        wbTable.querySelectorAll("input[name=" + name + "]")
      ).filter(function(el) {
        return el.checked;
      }).map(function(el) {
        let unixcode = getDateAndTimePickerDataAsUnix(el, idstring);
        return {[el.dataset.operator]: unixcode};
      });

    checked[idstring][name] = dates;


    //checked[idstring].push(dates);
      // eslint-disable-next-line no-console
      //console.log(checked);


  //schauen welche column
  //schauen welcher elemente angewählt sind
  //von den angewählten: auslesen date und time -> konvertieren in unix
  //in array schreiben: dates[colname][operator][unixtime]


}

/**
 * Checking Date and Timepicker for corresponding element and returning Unix Code.
 * @param {*} el
 * @param {string} idstring
 * @returns {string}
 */
export function getDateAndTimePickerDataAsUnix(el, idstring) {

  if (!el) {
    return '';
  }

  let selector = SELECTORS.CONTAINER + idstring;

  //filtercontainer sollte schon mal nur der coloumname sein
  //alle elemente (checkbox, picker) mit selben attribut ansprechen zB class
  //dann nur eine foreach

  //change listenere checkbox auch über picker drüber
  //wenn change und datepicker element (checkbox, picker..) -> check if checkbox is true, schreiben in array

  let filterContainer = document.querySelector(selector + SELECTORS.FILTER);


  const allDatepicker = filterContainer.querySelectorAll("input[type=date]");
  const allTimepicker = filterContainer.querySelectorAll("input[type=time]");

  let filtername = el.dataset.filtername;
  var dates = {};

  allDatepicker.forEach(node => {
    if (node.name == filtername) {
      dates['date'] = new Date(node.value);
    }
  });

  allTimepicker.forEach(node => {
    if (node.name == filtername) {
    dates['time'] = node.value;
    }
  });

  // eslint-disable-next-line no-undef
  let dateTimeString = dates['date'].toISOString().split('T')[0] + 'T' + dates['time'] + ':00.000Z';
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

    // eslint-disable-next-line no-console
    console.log("checked[idstring][name] " + checked[idstring][name]);

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
  for (const [key, value] of Object.entries(checked[idstring])) {

    if (value.length > 0) {
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