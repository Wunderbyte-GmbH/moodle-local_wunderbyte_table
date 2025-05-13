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
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Ajax from 'core/ajax';
import {showNotification} from 'local_wunderbyte_table/notifications';
import {reloadAllTables} from 'local_wunderbyte_table/reload';
import {
  get_strings as getStrings,
  get_string as getString
} from 'core/str';
import ModalForm from 'core_form/modalform';

const SELECTOR = {
  ACTIONBUTTON: '[data-type="wb_action_button"]',
  CHECKBOX: 'input.wb-checkbox',
};

/**
 * Function to add click listener to action button.
 * @param {string} selector
 * @param {string} idstring
 * @param {string} encodedtable
 * @returns {void}
 */
export function initializeActionButton(selector, idstring, encodedtable) {
  const container = document.querySelector(selector);

  if (!container) {
    return;
  }
  const actionbuttons = container.querySelectorAll(SELECTOR.ACTIONBUTTON);
  actionbuttons.forEach(button => {
    if (button.dataset.initialized) {
      return;
    }
    button.dataset.initialized = true;

    // First check if we have a valid methodname.
    if (button.dataset.methodname && button.dataset.methodname.length > 0) {
      // Second check if it's a checkbox, then we need a change listener.
      if (button.dataset.ischeckbox) {
        button.addEventListener('change', () => {

          const data = button.dataset;
          data.state = button.checked;

          transmitAction(button.dataset.id, button.dataset.methodname,
            JSON.stringify(data), idstring, encodedtable);
        });
      } else if (button.tagName == 'INPUT') {
        const debouncedInputHandler = debounce(() => {
          const data = button.dataset;
          data.value = button.value;

          transmitAction(button.dataset.id, button.dataset.methodname,
            JSON.stringify(data), idstring, encodedtable);
        }, 300);

        button.addEventListener('input', debouncedInputHandler);
      } else if (button.tagName == 'SELECT') {
        button.addEventListener('change', () => {
          const data = button.dataset;
          if (data.selectedValue != button.value) {
            data.selectedValue = button.value;
            transmitAction(
              button.dataset.id,
              button.dataset.methodname,
              JSON.stringify(data),
              idstring,
              encodedtable
            );
          }
        });
      } else {
        // Else it's a button, we attach the click listener.
        button.addEventListener('click', async() => {

          // Collect data from selection.
          // This will either return an object with the ids (as array) and labels (as string) of the selection or an empty object.
          var selectionresult = await getSelectionData(idstring, button.dataset);
          // Button Data will either return as int (1 for true) as bool, or as "true" string. We want all cases to return true.
          if (button.dataset.selectionmandatory == "1"
            || button.dataset.selectionmandatory == true
            || button.dataset.selectionmandatory == "true") {
            var selectionmandatory = true;
          }
          // eslint-disable-next-line block-scoped-var
          if (selectionmandatory && selectionresult.checkedids.length < 1) {
            showNoCheckboxNotification();
            // If selection is mandatory and there is no selection, no call will be executed.
            return;
          } else if (button.dataset.nomodal === 'true' || button.dataset.nomodal === "1") {
            // If nomodal is set true, action will be triggerd immediately.
            chooseActionToTransmit(button, idstring, encodedtable, selectionresult);
          } else {
            showConfirmationModal(button, idstring, encodedtable, selectionresult);
            // Modal will trigger Action to Transmit
          }
        });
      }
      // If it's not a methodname, we might have a form name a need to attach the right listener.
    } else if (button.dataset.formname && button.dataset.formname.length > 0) {
      button.addEventListener('click', e => {
        const target = e.target.closest(SELECTOR.ACTIONBUTTON);
        // eslint-disable-next-line no-console
        console.log('transmit data', target);
        let title = 'title';
        if (target.dataset.title !== undefined) {
          title = target.dataset.title;
        }
        let saveButtonText = 'button';
        if (target.dataset.submitbuttonstring !== undefined) {
          saveButtonText = target.dataset.submitbuttonstring;
        }
        showEditFormModal(button, title, 'body', saveButtonText, idstring, encodedtable);
      });
    }
  });
}

/**
 * Shows generic confirmation modal.
 * @param {*} button
 * @param {string} idstring
 * @param {string} encodedtable
 * @param {*} result
 */
async function showConfirmationModal(button, idstring, encodedtable, result) {
  // Checking if we have data from selection result. Otherwise generating default string for body.
  let datastring = result.labelstring ?? '';
  let strings = [];
  if (result.labelstring.length > 0) {
    strings = getStringsFromDataset(button, datastring, false);
  } else {
    strings = getStringsFromDataset(button, '', true);
  }
  const localizedstrings = await getStrings(strings);

  ModalFactory.create({type: ModalFactory.types.SAVE_CANCEL}).then(modal => {

    modal.setTitle(localizedstrings[0]);
    modal.setBody(localizedstrings[1]);
    modal.setSaveButtonText(localizedstrings[2]);
    modal.getRoot().on(ModalEvents.save, function() {
      chooseActionToTransmit(button, idstring, encodedtable, result);
    });
    modal.show();
    return modal;
  }).catch(e => {
    // eslint-disable-next-line no-console
    console.log(e);
  });
}

/**
 * Shows generic confirmation modal.
 * @param {*} func
 * @param {int} delay
 */
function debounce(func, delay) {
  let timeout;
  return (...args) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => func(...args), delay);
  };
}

/**
 * Function to collect the ids, check if selection of ids is mandatory and prepare a string of selected labels.
 * @param {*} idstring
 * @param {*} data //the dataset of the button that triggerd the action.
 * @returns {object}
 */
async function getSelectionData(idstring, data) {

  // First we collect the checked boxes.
  const result = getIds(data.id, idstring, data);
  const checkedids = result.checkedids;

  const labelarray = result.labelarray;

  const datastring = labelarray.join('<br>') ?? '';

  return {
    'checkedids': checkedids,
    'labelstring': datastring,
  };
}

/**
 *  If no boxes are checked, we will send out a notification.
 */
async function showNoCheckboxNotification() {
  const message = await getString('nocheckboxchecked', 'local_wunderbyte_table');
  showNotification(message, "danger");

}

/**
 * Ajax function to handle action buttons.
 * @param {int} id
 * @param {string} methodname
 * @param {string} datastring
 * @param {string} idstring
 * @param {string} encodedtable
 */
export function transmitAction(id, methodname, datastring, idstring, encodedtable) {

  // Show the call spinner.
  let callspinner = document.querySelector(".wunderbyte_table_container_" + idstring + " .wb-table-call-spinner");
  if (callspinner) {
    callspinner.classList.remove('hidden');
  }

  Ajax.call([{
    methodname: "local_wunderbyte_table_execute_action",
    args: {
      'id': parseInt(id),
      'methodname': methodname,
      'data': datastring,
      'encodedtable': encodedtable,
    },
    done: function(data) {
      // Hide the call spinner.
      let callspinner = document.querySelector(".wunderbyte_table_container_" + idstring + " .wb-table-call-spinner");
      if (callspinner) {
        callspinner.classList.add('hidden');
      }

      if (data.success == 1) {

        if (data.reload > 0) {
          window.location.reload();
        }
        if (data.message.length > 0) {
          showNotification(data.message, "success");
        }
      } else {
        showNotification(data.message, "danger");
      }

      reloadAllTables();

      // We check if the table is within a modal and if so, we make sure that this modal...
      // Stays scrollable by making sure that the body keeps the modal-open class.
      const container = document.querySelector('#a' + idstring);
      if (container.closest(".modal-dialog")) {
        let body = container.closest("body");
        body.classList.add("modal-open");
      }
    },
    fail: function(ex) {
      // eslint-disable-next-line no-console
      console.log("ex:" + ex);

      // Hide the call spinner.
      let callspinner = document.querySelector(".wunderbyte_table_container_" + idstring + " .wb-table-call-spinner");
      if (callspinner) {
        callspinner.classList.add('hidden');
      }

      showNotification("row " + id + " was not treated", "danger");
    },
  }]);
}

/**
 * Function to collect checked idboxes.
 * @param {*} id
 * @param {*} idstring
 * @param {*} data
 * @returns {object}
 */
function getIds(id, idstring, data) {
  var checkedids = [];

  const labelarray = [];
  const container = document.querySelector('#a' + idstring);

  // If the id is 0, we return for all checked checkboxes.
  // if not, just for the current one.

  // Make sure we treat id as int.
  id = parseInt(id);
  if (id < 1) {
    const checkboxes = container.querySelectorAll(SELECTOR.CHECKBOX);

    // Create an array of ids of the checked boxes.
    checkboxes.forEach(x => {

      if (x.checked) {

        // We don't need the id of the checkbox, but the data-id of the row.

        const id = x.closest('tr').dataset.id;

        labelarray.push(returnLabel(id, data.labelcolumn, container));
        checkedids.push(id);
      }
    });

  } else {
    labelarray.push(returnLabel(id, data.labelcolumn, container));
    checkedids.push(id);
  }
  return {
    'checkedids': checkedids,
    'labelarray': labelarray,
  };

  /**
   * Function to return label name or id if no name available.
   * @param {*} id
   * @param {*} label
   * @param {*} container
   * @returns {String}
   */
  function returnLabel(id, label, container) {
    try {
      const name = container.querySelector('[data-id="' + id + '"] [data-label="' + label + '"]').textContent;
      return name;
    } catch (e) {
      return '' + id;
    }
  }
}

/**
 *
 * @param {*} button
 * @param {*} titleText
 * @param {*} bodyText
 * @param {*} saveButtonText
 * @param {*} idstring
 * @param {*} encodedtable
 */
async function showEditFormModal(button, titleText, bodyText, saveButtonText, idstring, encodedtable) {
  // eslint-disable-next-line no-console
  console.log(button, bodyText, saveButtonText, idstring, encodedtable);

  let strings = [];
  strings = getStringsFromDataset(button, '', true);
  const localizedstrings = await getStrings(strings);
  titleText = localizedstrings[0];
  saveButtonText = localizedstrings[2];

  const formname = button.dataset.formname;
  let data = button.dataset;
  data.id = button.dataset.id; // Get all the data of the clicked button.

  if (data.id == -1) {
    const result = getIds(data.id, idstring, data);
    data.checkedids = result.checkedids;
  }
  // eslint-disable-next-line no-console
  console.log(data);
  let modalForm = new ModalForm({
    // Name of the class where form is defined (must extend \core_form\dynamic_form):
    formClass: formname,
    // Add as many arguments as you need, they will be passed to the form:
    args: data,
    // Pass any configuration settings to the modal dialogue, for example, the title:
    modalConfig: {
      title: titleText
    },
    // DOM element that should get the focus after the modal dialogue is closed:
    returnFocus: button,
  });
  if (saveButtonText != 'button') {
    modalForm = new ModalForm({
      // Name of the class where form is defined (must extend \core_form\dynamic_form):
      formClass: formname,
      // Add as many arguments as you need, they will be passed to the form:
      args: data,
      // Pass any configuration settings to the modal dialogue, for example, the title:
      modalConfig: {
        title: titleText
      },
      saveButtonText: saveButtonText,
      // DOM element that should get the focus after the modal dialogue is closed:
      returnFocus: button,
    });
  }

  // Listen to events if you want to execute something on form submit.
  // Event detail will contain everything the process() function returned:
  modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (e) => {
    const data = e.detail;
    if (data.reload) {
      // Reload whole site.
      window.location.reload();
    } else {
      reloadAllTables();
    }

    if (data.message && data.message.length > 0) {
      showNotification(data.message, data.success == 1 ? 'success' : 'danger');
    }
  });

  // Show the form.
  modalForm.show();
}

/**
 * Case decision between call without selection, single call or multiple call triggering transmit action.
 * @param {string} button
 * @param {string} idstring
 * @param {string} encodedtable
 * @param {object} selectionresult
 */
function chooseActionToTransmit(button, idstring, encodedtable, selectionresult) {
  const data = button.dataset;
  const id = parseInt(button.dataset.id);
  const methodname = button.dataset.methodname;
  const checkedids = selectionresult.checkedids ?? [];
  // Checkedids will either be ['id1', 'id2', ...] or [] if no selection was made.

  if (checkedids.length === 0) {
    // eslint-disable-next-line no-console
    console.log("no ids checked");
    transmitAction(button.dataset.id,
      button.dataset.methodname,
      JSON.stringify(button.dataset), idstring, encodedtable);
    return;
  }

  if (id != 0) { // -1 means we want single line execution.
    // eslint-disable-next-line no-console
    console.log("single call");
    transmitAction(id, methodname, JSON.stringify({...data, checkedids}), idstring, encodedtable);
  } else {
    // eslint-disable-next-line no-console
    console.log("multiple call");
    checkedids.forEach(cid => {
      transmitAction(cid, methodname, JSON.stringify(data), idstring, encodedtable);
    });
  }
}

/**
 * Helper function to get strings from dataset of first element (e.g. button).
 * @param {object} button
 * @param {string} datastring
 * @param {boolean} noselection
 * @returns {Array}
 */
function getStringsFromDataset(button, datastring, noselection) {
  let strings = [];
  strings.push({
    key: button.dataset.titlestring ?? 'generictitle',
    component: button.dataset.component ?? 'local_wunderbyte_table',
  });
  if (noselection) {
    strings.push({
      key: button.dataset.noselectionbodystring ?? 'noselectionbody',
      component: button.dataset.component ?? 'local_wunderbyte_table',
    });
  } else {
    strings.push({
      key: button.dataset.bodystring ?? 'genericbody',
      component: button.dataset.component ?? 'local_wunderbyte_table',
      param: {
        data: datastring,
      },
    });
  }
  strings.push({
    key: button.dataset.submitbuttonstring ?? 'genericsubmit',
    component: button.dataset.component ?? 'local_wunderbyte_table',
  });
  return strings;
}
