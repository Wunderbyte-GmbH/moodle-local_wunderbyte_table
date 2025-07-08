
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
  const hierarchcheckboxes = filterContainer.querySelectorAll('.hierarchycategory-checkbox');

  if (!filterElements) {
    return;
  }

  // We create the key for the checked items of this table.
  if (!checked.hasOwnProperty(idstring)) {
    checked[idstring] = {};
  }

  // We run through all the filter elements and make sure that we store the state we received from php.
  filterElements.forEach(element => {
    getChecked(element.name, selector, idstring);
  });

  applyChangelistener(filterElements, selector, idstring, encodedtable);
  applyChangelistener(selects, selector, idstring, encodedtable);

  if (hierarchcheckboxes) {
    handleHierarchyCategoryCheckbox(hierarchcheckboxes, filterElements, selector, idstring, encodedtable);
  }

  filterContainer.dataset.initialized = true;
}
/**
 * Apply change listener to list of nodes.
 * @param {*} nodelist
 * @param {*} selector
 * @param {*} idstring
 * @param {*} encodedtable
 */
function applyChangelistener(nodelist, selector, idstring, encodedtable) {
  if (nodelist) {
    nodelist.forEach(el => {

      if (!el.dataset.idstring) {
        el.dataset.idstring = idstring;
      } else {
        el.dataset.idstring2 = idstring;
      }
      ["change", "keyup"].forEach(event => {
        el.addEventListener(event, (e) => toggleFilterelement(e, selector, idstring, encodedtable));
      });
    });
  }
}
/**
 * Init for button to reset all filter and searchparams.
 * @param {*} selector
 * @param {*} idstring
 * @param {*} encodedtable
 */
export function initializeResetFilterButton(selector, idstring, encodedtable) {
  const container = document.querySelector(selector);
  if (!container) {
    return;
  }
  let button = container.querySelector(".reset-filter-button");

  // eslint-disable-next-line no-console
  console.log(button);

  if (!button) {
    return;
  }
  button.addEventListener('click', () => {

    if (!container) {
      return;
    }

    const componentscontainer = container.querySelector(".wunderbyte_table_components");

    if (!componentscontainer) {
      return;
    }

    componentscontainer.remove();

    resetCheckedObject(idstring);

    const sort = getSortSelection(idstring);
    callLoadData(idstring,
      encodedtable,
      0, // Pagenumber is always set to 0.
      null,
      sort,
      null,
      null,
      null,
      "",
      "");
  });
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

  setTimeout(() => {
    // Check if Checkbox corresponds to datepicker
    if (e.target.type === 'checkbox') {
            handleParentCheckbox(e.target);
    }
    if (e.target.dataset.dateelement == 'dateelement') {
      getDates(e, selector, idstring);
    } else if (e.target.dataset.intrangeelement && e.target.dataset.intrangeelement.includes('intrangeelement')) {
      getIntRange(e, selector, idstring);
      // eslint-disable-next-line no-console
      console.log("intrangeelement");
    } else {
      getChecked(e.target.name, selector, idstring);
    }

    triggerReload(idstring, encodedtable);
  }, 400);
}

/**
 * Handles the behavior of parent checkboxes in a hierarchical structure.
 * When a child checkbox is checked/unchecked, this function updates the state
 * of the parent checkbox accordingly. The parent checkbox will be checked if
 * all children are checked, and unchecked if any child is unchecked.
 *
 * @param {HTMLInputElement} checkbox - The checkbox element that triggered the event
 */
function handleParentCheckbox(checkbox) {
    const wrapper = checkbox.closest('ul.hierarchy');
    if (!wrapper) {
      return;
    }
    const spanElement = wrapper.querySelector('.d-flex');
    const parentCheckbox = spanElement.querySelector('.hierarchycategory-checkbox');
    if (!parentCheckbox) {
      return;
    }
    const siblingCheckboxes = wrapper.querySelectorAll('.hierarchychild-checkbox');
    const allChecked = Array.from(siblingCheckboxes).every(cb => cb.checked);

    if (allChecked) {
        parentCheckbox.checked = true;
    } else if (Array.from(siblingCheckboxes).some(cb => !cb.checked)) {
        parentCheckbox.checked = false;
    }
}

/**
 * Trigger the reload with filter, search, sort.
 *
 * @param {*} idstring
 * @param {*} encodedtable
 *
 */
function triggerReload(idstring, encodedtable) {
      // Reload the filtered elements via ajax.
    const filterobjects = getFilterObjects(idstring);
    const searchstring = getSearchInput(idstring);
    const sort = getSortSelection(idstring);

    // The filter reloads data from the Server.
    // Because of pages and infinite scroll we don't have the data to do without.
    callLoadData(idstring,
      encodedtable,
      0, // Pagenumber is always set to 0.
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

  let name = e.target.dataset.columnname;
  let filtercontainer = e.target.closest(".datepickerform");
  let filtername = e.target.dataset.filtername;
  let filtercheckbox = filtercontainer.querySelector('input[type="checkbox"][id^="' + filtername + '"][name="' + name + '"]');

  let dates = {};
  if (filtercheckbox.dataset.timespan === "true") {
    compareDateValues(e, filtercontainer);
  }
  if (filtercheckbox.checked) {
    // Check if we have a timespan filter or a single one.
    if (filtercheckbox.dataset.timespan === "true") {
      setTimespanFilter(filtercontainer, filtername, idstring, name);
    } else {
      dates[String(filtercheckbox.dataset.operator)] = getDateAndTimePickerDataAsUnix(filtercontainer, "datefilter") / 1000;
      // Check if key is set in array, otherwise set new key.
      if (name && filtername) {
        if (!checked[idstring][name]) {
          checked[idstring][name] = {};
        }
        checked[idstring][name][filtername] = dates;
      }
      unsetEmptyFieldsInCheckedObject(name, filtername, idstring);
    }
  } else { // If checkbox of filter is unchecked: unset values in checked object.
    resetCheckedObject(idstring, name, filtername);
    unsetEmptyFieldsInCheckedObject(name, null, idstring);
    // Vorher noch ein if exists etc.
    Object.keys(checked[idstring]).forEach(function(key) {
      Object.keys(checked[idstring][key]).forEach(function(okey) {
        if (okey == filtername) {
          resetCheckedObject(idstring, key, filtername);
          unsetEmptyFieldsInCheckedObject(key, null, idstring);
        }
      }
      );
    }
    );
  }
  updateFilterCounter(name, selector, idstring);
}

/**
 * Check if date and time value set in first timepicker is before second timepicker.
 * @param {*} e // The element that triggerd the change.
 * @param {*} filtercontainer
 */
function compareDateValues(e, filtercontainer) {
  let startdate = getDateAndTimePickerDataAsUnix(filtercontainer, "startdate");
  let enddate = getDateAndTimePickerDataAsUnix(filtercontainer, "enddate");

  // We make sure the entered enddate is after 2020, to avoid triggering change while date setting is not finished yet.
  if ((enddate / 1000 > 1577836800) && (startdate > enddate)) {
    // Apply change.
    setDateAndTimePickerDataFromUnix(filtercontainer, 'startdate', enddate);
  }
}

/**
 * Checking Date and Timepicker for corresponding element and returning Unix Code.
 * @param {*} filtercontainer
 * @param {string} id // Id of the date- and timepicker, the value should be applied to.
 * @param {integer} timestamp
 */
export function setDateAndTimePickerDataFromUnix(filtercontainer, id = '', timestamp) {

  let dateobject = new Date(timestamp);

  let datepicker = filtercontainer.querySelector('input[type="date"][id*="' + id + '"]');
  datepicker.value = dateobject.toISOString().split('T')[0];

  let timepicker = filtercontainer.querySelector('input[type="time"][id*="' + id + '"]');
  timepicker.value = dateobject.toLocaleTimeString().slice(0, 5);
}

/**
 * Applying a timespan filter.
 * @param {*} filtercontainer
 * @param {string} filtername
 * @param {string} idstring
 * @param {string} name
 */
function setTimespanFilter(filtercontainer, filtername, idstring, name) {
  // Selector defined the operators.
  let select = filtercontainer.querySelector('select[id^="filteroperationselect"][name="' + name + '"]');
  let operator = select.value;

  // First Column to apply the filter to
  let startdatepicker = filtercontainer.querySelector('input[id^="startdate"]');
  let firstcolumn = startdatepicker.dataset.applytocolumn;
  let firstoperator = "";
  let additionalFirstColumnValues = {};
  let valuefirstcolumn = getDateAndTimePickerDataAsUnix(filtercontainer, "startdate") / 1000;

  // Second Column to apply the filter to
  let enddatepicker = filtercontainer.querySelector('input[id^="enddate"]');
  let secondcolumn = enddatepicker.dataset.applytocolumn;
  let secondoperator = "";
  let additionalSecondColumnValues = {};
  let valuesecondcolumn = getDateAndTimePickerDataAsUnix(filtercontainer, "enddate") / 1000;

  if (!Number.isInteger(valuefirstcolumn) || !Number.isInteger(valuefirstcolumn)) {
    return;
  }

  // Unset the values of the span filter in checked object.
  resetCheckedObject(idstring, firstcolumn, filtername);
  resetCheckedObject(idstring, secondcolumn, filtername);

  switch (operator) {
    case "within":
      firstoperator = ">=";
      secondoperator = "<=";
      break;
    case "overlapboth":
      firstoperator = "<=";
      secondoperator = ">=";
      break;
    case "overlapstart":
      firstoperator = "<=";
      additionalSecondColumnValues[">="] = valuefirstcolumn;
      secondoperator = "<=";
      break;
    case "overlapend":
      firstoperator = ">=";
      secondoperator = ">=";
      additionalFirstColumnValues["<="] = valuesecondcolumn;
      break;
    case "before":
      firstoperator = "<";
      additionalSecondColumnValues["<="] = valuefirstcolumn;
      secondoperator = "<";
      break;
    case "after":
      secondoperator = ">=";
      additionalFirstColumnValues[">="] = valuesecondcolumn;
      firstoperator = ">";
      break;
    case "flexoverlap":
      firstoperator = "fo";
      secondoperator = "fo";
      break;
    default:
      break;
  }
  if (!secondcolumn) {
    secondcolumn = firstcolumn;
  }
  applySpanfilter(firstcolumn, valuefirstcolumn, filtername, firstoperator, additionalFirstColumnValues, idstring);
  applySpanfilter(secondcolumn, valuesecondcolumn, filtername, secondoperator, additionalSecondColumnValues, idstring);

  // Unsetting the timespan filter if empty
  if (firstcolumn && filtername) {
    unsetEmptyFieldsInCheckedObject(firstcolumn, filtername, idstring);
  }
  if (secondcolumn && filtername) {
    unsetEmptyFieldsInCheckedObject(secondcolumn, filtername, idstring);
  }
}

/**
 *  Check if filter object already exisits and unset values.
 * @param {string} idstring
 * @param {string} column
 * @param {string} filtername
 */
function resetCheckedObject(idstring, column = '', filtername = '') {

  // If no column is specified, we reset all the filters.
  if (column.length === 0) {

    Object.keys(checked[idstring]).forEach(col => {
      checked[idstring][col] = [];
    });
  } else {
    if (checked[idstring].hasOwnProperty(column)) {
      if (checked[idstring][column].hasOwnProperty(filtername)) {
        delete checked[idstring][column][filtername];
      }
      if (checked[idstring][column].hasOwnProperty(filtername + 'a')) {
        delete checked[idstring][column][filtername + 'a'];
      }
      if (
        Array.isArray(checked[idstring][column]) &&
        checked[idstring][column].length === 1 &&
        checked[idstring][column][0] === 'datecheckbox'
      ) {
        delete checked[idstring][column];
      }
    }
  }
}

/**
 *  Check if object already exisits and set values.
 * @param {string} column
 * @param {*} value
 * @param {string} filtername
 * @param {string} operator
 * @param {*} additionalvaluesObject
 * @param {string} idstring
 */
function applySpanfilter(column, value, filtername, operator, additionalvaluesObject, idstring) {
  if (operator.length >= 1) {
    if (column && filtername) {
      if (!checked[idstring][column]) {
        checked[idstring][column] = {};
      }
      if (!checked[idstring][column][filtername]) {
        checked[idstring][column][filtername] = {};
      }
      checked[idstring][column][filtername][operator] = value;
      if (Object.keys(additionalvaluesObject).length > 0) {
        checked[idstring][column][filtername + 'a'] = additionalvaluesObject;
      }
    }
  }
}

/**
 * Unsetting empty keys in checked object. If a filter param was created and deleted later on we will need this.
 * @param {*} key1
 * @param {*} key2
 * @param {string} idstring
 */
function unsetEmptyFieldsInCheckedObject(key1, key2, idstring) {
  if (checked[idstring][key1]) {
    if (checked[idstring][key1][key2]) {
      if (Object.keys(checked[idstring][key1][key2]).length < 1) {
        delete checked[idstring][key1][key2];
      }
    }
  }

  if (checked[idstring][key1]) {
    if (Object.keys(checked[idstring][key1]).length < 1) {
      delete checked[idstring][key1];
    }
  }
}

/**
 * Checking Date and Timepicker for corresponding element and returning Unix Code.
 * @param {*} filtercontainer
 * @param {string} id
 * @returns {string}
 */
export function getDateAndTimePickerDataAsUnix(filtercontainer, id = '') {

  let datepicker = filtercontainer.querySelector('input[type="date"][id*="' + id + '"]');
  let date = datepicker.value;

  let timepicker = filtercontainer.querySelector('input[type="time"][id*="' + id + '"]');
  let time = timepicker.value;

  let unixTimestamp = Date.parse(date + ' ' + time);
  let tenDigitTimestamp = unixTimestamp;

  return tenDigitTimestamp;
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

  let params = url.searchParams;

  // We don't actually want to delete all url params, only those we don't use for searching.
  params.delete('wbtfilter');
  params.delete('wbtsearch');
  params.delete('tsort');
  params.delete('tdir');

  window.history.replaceState(null, '', url);

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
 * Generating and displaying filterparams in URL.
 * @param {string} idstring
 * @param {string} filterobjects
 * @param {string} searchstring
 * @param {string} sort
 * @param {*} dir
 */
export function updateDownloadUrlWithFilterSearchSort(idstring, filterobjects, searchstring, sort, dir) {

  // The container will hold wunderbyteTableClass, wunderbyteTableFilter, wunderbyteTableSearch classes.
  let container = document.querySelector(".wunderbyte_table_container_" + idstring);
  if (!container) {
    return;
  }

  let url = '';
  let formelement = null;
  try {
    formelement = container.querySelector('form.wb-table-download-buttons');
    url = new URL(formelement.getAttribute('action'));
  } catch (e) {

    // eslint-disable-next-line no-console
    console.log(e);
    return;
  }

  let params = url.searchParams;

  // We don't actually want to delete all url params, only those we don't use for searching.
  params.delete('wbtfilter');
  params.delete('wbtsearch');
  params.delete('tsort');
  params.delete('tdir');

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

  formelement.action = url.toString();
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

  updateFilterCounter(name, selector, idstring);
}

/**
 * Gets the values of the checked intrange filter.
 * @param {*} e
 * @param {*} selector
 * @param {*} idstring
 */
export function getIntRange(e, selector, idstring) {

  // We might have more than one Table, therefore we first have to get all tables.
  let filtercontainer = e.target.closest(".intrangeform");

  let from = filtercontainer.querySelector('input[id*="intrangefilter_intrange-start"]');
  let fromvalue = from.value;
  let to = filtercontainer.querySelector('input[id*="intrangefilter_intrange-end"]');
  let tovalue = to.value;
  let colname = e.target.dataset.columnname;

  // Add the alert if not all entries are ints (or empty).
  const isInt = (str) => (!isNaN(parseInt(str)) && isFinite(str)) || (str.trim() === '') || (str === null);

  const alertelement = filtercontainer.querySelector('div[id*="intrangefilter_alert"]');
  if (!isInt(fromvalue)
    || !isInt(tovalue)) {

    alertelement.removeAttribute('hidden');
  } else {
    alertelement.setAttribute('hidden', 'true');

    // Stripping leading zeros.
    fromvalue = parseInt(fromvalue, 10);
    fromvalue = fromvalue.toString();
    tovalue = parseInt(tovalue, 10);
    tovalue = tovalue.toString();
  }

  if (fromvalue.length > 0 || tovalue.length > 0) {
    checked[idstring][colname] = fromvalue + "," + tovalue;
  }

  // If there are no checked boxes, unset the key when the checkbox is unchecked.
  if (!filtercontainer.querySelector('input[data-intrangeelement="intrangeelement-checkbox"]').checked) {
    delete checked[idstring][colname];
  }
  updateFilterCounter(colname, selector, idstring);
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

/**
 * Update Filter counter.
 *
 * @param {*} name
 * @param {*} selector
 * @param {*} idstring
 *
 *
 */
function updateFilterCounter(name, selector, idstring) {

  const wbTable = document.querySelector(selector);

  let counter = checked[idstring][name] ? checked[idstring][name].length : 0;
  if ((counter > 0 && (typeof checked[idstring][name] === 'string') ||
    (typeof checked[idstring][name] === 'object' && !Array.isArray(checked[idstring][name])))) {
    // Handle different cases of filters here (datepicker, intrange).
    // TODO: Find a better marker for difference of filters.
    counter = 1;
  }

  const labelElement = wbTable.querySelector('[data-ident=' + name + '] span.filtercounter');

  if (labelElement) {

    if (counter > 0) {
      labelElement.classList.remove('hidden');
    } else {
      labelElement.classList.add('hidden');
    }
    labelElement.textContent = counter;
  }

  const totalfiltercounter = checked[idstring] ? Object.keys(checked[idstring]).length : 0;
  const resetElement = wbTable.querySelector('.reset-filter-button');

  if (resetElement) {
    if (totalfiltercounter > 0) {
      resetElement.classList.remove('hidden');
    } else {
      resetElement.classList.add('hidden');
    }
  }
}

/**
 * Attach a click listener for these checkboxes to check all boxes in category.
 *
 * @param {*} parentCheckboxes
 * @param {*} filterElements
 * @param {*} selector
 * @param {*} idstring
 * @param {*} encodedtable
 *
 */
function handleHierarchyCategoryCheckbox(parentCheckboxes, filterElements, selector, idstring, encodedtable) {
    parentCheckboxes.forEach(parentCheckbox => {
      parentCheckbox.addEventListener('click', function() {
            // Get the closest parent <ul> element
            const wrapper = parentCheckbox.closest('ul');

            // Find all child checkboxes inside this <ul>
            const childCheckboxes = wrapper.querySelectorAll('.form-check-input');

            childCheckboxes.forEach(childCheckbox => {
                // Only click if current state doesn't match parent
                if (childCheckbox.checked !== parentCheckbox.checked) {
                    childCheckbox.checked = parentCheckbox.checked; // Triggers associated JS
                }
            });
            filterElements.forEach(element => {
              getChecked(element.name, selector, idstring);
            });
            triggerReload(idstring, encodedtable);
        });
    });
}