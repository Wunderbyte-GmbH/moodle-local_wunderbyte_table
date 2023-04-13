
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
  ACTIONBUTTON: '.wb_action_button',
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

          // eslint-disable-next-line no-console
          console.log(data.state);

          transmitAction(button.dataset.id, button.dataset.methodname,
            JSON.stringify(data), idstring, encodedtable);
        });
      } else {
        // Else it's a button, we attach the click listener.
        button.addEventListener('click', () => {

          // We don't show the modal when we already know we treat just one row.
          // Todo: make a one row treatment modal?
          if (button.dataset.nomodal && button.dataset.id > 0) {
            transmitAction(button.dataset.id,
              button.dataset.methodname,
              JSON.stringify(button.dataset), idstring, encodedtable);
          } else {
            showConfirmationModal(button, idstring, encodedtable);
          }
        });
      }
      // If it's not a methodname, we might have a form name a need to attach the right listener.
    } else if (button.dataset.formname && button.dataset.formname.length > 0) {
      button.addEventListener('click', e => {
        const target = e.target;
        // eslint-disable-next-line no-console
        console.log('transmit data', target);
        showEditFormModal(button, 'title', 'body', 'button', idstring, encodedtable);
      });
    }
  });
}

/**
 * Shows generic confirmation modal.
 * @param {*} button
 * @param {string} idstring
 * @param {string} encodedtable
 */
async function showConfirmationModal(button, idstring, encodedtable) {

  const id = parseInt(button.dataset.id);
  const methodname = button.dataset.methodname;
  const data = button.dataset; // Get all the data of the clicked button.

  const result = getIds(id, idstring, data);

  var checkedids = result.checkedids;
  const labelarray = result.labelarray;

  if (checkedids.length < 1) {
    const message = await getString('nocheckboxchecked', 'local_wunderbyte_table');
    showNotification(message, "danger");
    return;
  }

  const datastring = labelarray.join('<br>') ?? '';

  let strings = [
    {
      key: button.dataset.titlestring ?? 'generictitle',
      component: button.dataset.component ?? 'local_wunderbyte_table',
    },
    {
      key: button.dataset.bodystring ?? 'genericbody',
      component: button.dataset.component ?? 'local_wunderbyte_table',
      param: {
        // eslint-disable-next-line block-scoped-var
        data: datastring,
      }
    },
    {
      key: button.dataset.submitbuttonstring ?? 'genericsubmit',
      component: button.dataset.component ?? 'local_wunderbyte_table',
    },
  ];

  const localizedstrings = await getStrings(strings);

  ModalFactory.create({ type: ModalFactory.types.SAVE_CANCEL }).then(modal => {

    modal.setTitle(localizedstrings[0]);
    modal.setBody(localizedstrings[1]);
    modal.setSaveButtonText(localizedstrings[2]);
    modal.getRoot().on(ModalEvents.save, function () {

      // If there is only one id, we transmit one call.
      if (id != 0) {
        transmitAction(id, methodname, JSON.stringify({...data, checkedids}), idstring, encodedtable);
      } else { // Zero means we want single line execution.
        // eslint-disable-next-line block-scoped-var
        checkedids.forEach(cid => {
          transmitAction(cid, methodname, JSON.stringify(data), idstring, encodedtable);
        });
      }
    });

    modal.show();
    return modal;
  }).catch(e => {
    // eslint-disable-next-line no-console
    console.log(e);
  });
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
  Ajax.call([{
    methodname: "local_wunderbyte_table_execute_action",
    args: {
      'id': parseInt(id),
      'methodname': methodname,
      'data': datastring,
      'encodedtable': encodedtable,
    },
    done: function (data) {

      if (data.success == 1) {
        showNotification(data.message, "success");
      } else {
        showNotification(data.message, "danger");
      }
      reloadAllTables();
    },
    fail: function (ex) {
      // eslint-disable-next-line no-console
      console.log("ex:" + ex);
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
  if (id < 1) {

    const checkboxes = container.querySelectorAll(SELECTOR.CHECKBOX);

    // Create an array of ids of the checked boxes.
    checkboxes.forEach(x => {

      if (x.checked) {
        labelarray.push(returnLabel(x.id, data.labelcolumn));
        checkedids.push(x.id);
      }
    });

  } else {
    labelarray.push(returnLabel(id, data.labelcolumn));
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
   * @returns {String}
   */
  function returnLabel(id, label) {
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
function showEditFormModal(button, titleText, bodyText, saveButtonText, idstring, encodedtable) {

  // eslint-disable-next-line no-console
  console.log(button, bodyText, saveButtonText, idstring, encodedtable);

  const formname = button.dataset.formname;
  let data = button.dataset;
  data.id = button.dataset.id; // Get all the data of the clicked button.

  // eslint-disable-next-line no-console
  console.log(data);

  let modalForm = new ModalForm({
    // Name of the class where form is defined (must extend \core_form\dynamic_form):
    formClass: formname,
    // Add as many arguments as you need, they will be passed to the form:
    args: data,
    // Pass any configuration settings to the modal dialogue, for example, the title:
    modalConfig: { title: titleText },
    // DOM element that should get the focus after the modal dialogue is closed:
    returnFocus: button,
  });

  // Listen to events if you want to execute something on form submit.
  // Event detail will contain everything the process() function returned:
  modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (e) => {

    // eslint-disable-next-line no-console
    console.log(e.detail);

    reloadAllTables();
  });

  // Show the form.
  modalForm.show();
}
