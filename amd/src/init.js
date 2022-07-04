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

import Ajax from 'core/ajax';
import Templates from 'core/templates';
import Notification from 'core/notification';

import {renderFilter, renderSearchbox} from 'local_wunderbyte_table/search';

var loading = false;

/**
 * Gets called from mustache template.
 * @param {string} idstring
 * @param {string} encodedtable
 */
export const init = (idstring, encodedtable) => {

    if (idstring && encodedtable) {
        respondToVisibility(idstring, encodedtable, callLoadData);
    }
};

/**
 * React on visibility change.
 * @param {string} idstring
 * @param {string} encodedtable
 * @param {function} callback
 */
function respondToVisibility(idstring, encodedtable, callback) {

    const identifier = 'a' + idstring;
    let element = document.getElementById(identifier);

    if (element) {
        element.dataset.encodedtable = encodedtable;
    } else {
        // If we don't find an element, we abort.
        return;
    }

    // We only make this callback during init if there is the spinner running.
    // We don't want to run all of this if we don't use lazyloading.
    let spinner = document.getElementById(identifier + 'spinner');

    // eslint-disable-next-line no-console
    console.log(spinner);

    if ((spinner !== null) && !isHidden(spinner)) {
        callback(idstring, encodedtable);

        var observer = new MutationObserver(function() {
            if (!isHidden(element)) {
                this.disconnect();
                callback(idstring, encodedtable);
            }
        });

        // We look if we find a hidden parent. If not, we load right away.
        while (element !== null) {
            if (!isHidden(element)) {
                element = element.parentElement;
            } else {
                observer.observe(element, {attributes: true});
                return;
            }
        }
    } else {
        replaceLinksInFrag(idstring,encodedtable, null, element);
    }
}

/**
 * Function to reload a wunderbyte table from js.
 * Here we trim the idstring before we pass it to the calldatafunction.
 * @param {*} idstring
 * @param {*} encodedtable
 */
export function wbTableReload(idstring, encodedtable = null) {


    // We need to trim the first character. We use the a to make sure no number is in first place due to random generation.
    idstring = idstring.substring(1);

    let element = document.getElementById('a' + idstring);

    if (!element) {

        return;
    }

    if (!encodedtable) {
        encodedtable = element.dataset.encodedtable;

        if (!encodedtable) {
            return;
        }
    }

    callLoadData(idstring, encodedtable);
}

/**
 * Function to check visibility of element.
 * @param {*} el
 * @returns {boolean}
 */
 export const isHidden = (el) => {
    var style = window.getComputedStyle(el);
    return ((style.display === 'none') || (style.visibility === 'hidden'));
};

/**
 * Reloads the rendered table and sets it to the div with the right identifier.
 * @param {string} idstring
 * @param {string} encodedtable
 * @param {null|int} page
 * @param {null|string} tsort
 * @param {null|string} thide
 * @param {null|string} tshow
 * @param {null|int} tdir
 * @param {null|int} treset
 * @param {null|string} filterobjects
 * @param {null|string} searchtext
 */
export const callLoadData = (
    idstring,
    encodedtable,
    page = null,
    tsort = null,
    thide = null,
    tshow = null,
    tdir = null,
    treset = null,
    filterobjects = null,
    searchtext = null) => {

    let table = document.getElementById('a' + idstring);

    // This is now the individual spinner from the wunderbyte table template.
    let spinner = document.querySelector('#a' + idstring + 'spinner .spinner-border');

    if (spinner) {
        spinner.classList.remove('hidden');
    }
    if (table) {
        table.classList.add('hidden');
    }

    loading = true;

    Ajax.call([{
        methodname: "local_wunderbyte_table_load_data",
        args: {
            'encodedtable': encodedtable,
            'page': page,
            'tsort': tsort,
            'thide': thide,
            'tshow': tshow,
            'tdir': tdir,
            'treset': treset,
            'filterobjects': filterobjects,
            'searchtext': searchtext
        },
        done: function(res) {

            const jsonobject = JSON.parse(res.content);

            // eslint-disable-next-line no-console
            console.log(jsonobject);

            Templates.renderForPromise(res.template, jsonobject).then(({html, js}) => {

                const frag = document.querySelector('#a' + idstring);

                while (frag.firstChild) {
                    frag.removeChild(table.lastChild);
                }

                Templates.appendNodeContents('#a' + idstring, html, js);

                replaceLinksInFrag(idstring,encodedtable, frag, page);

                // When everything is done, we loaded fine.
                loading = false;
                // eslint-disable-next-line no-console
                console.log(loading);

                return true;
            }).catch(ex => {
                loading = false;
                Notification.addNotification({
                    message: 'failed rendering ' + ex,
                    type: "danger"
                });
            });

            renderSearchbox(idstring);

            if (res.filterjson) {
                const filterjson = JSON.parse(res.filterjson);
                renderFilter(filterjson, idstring);
            }

            if (spinner) {
                spinner.classList.add('hidden');
            }
            if (table) {
                table.classList.remove('hidden');
            }
            return true;
        },
        fail: function(err) {
            // If we have an error, resetting the table might be enough. we do that.
            // To avoid a loop, we only do this in special cases.
            if ((treset != 1)) {
                callLoadData(idstring, encodedtable, page, null, null, null, null, 1);
            } else {
                let node = document.createElement('DIV');
                let textnode = document.createTextNode(err.message);
                node.appendChild(textnode);
                table.appendChild(node);
                spinner.classList.add('hidden');
                table.classList.remove('hidden');
            }
        }
    }]);
};

/**
 * The rendered table has links we can't use. We replace them with eventlisteners and use the callLoadData function.
 * @param {string} idstring
 * @param {string} encodedtable
 * @param {DocumentFragment} frag
 * @param {int} page
 */
function replaceSortColumnLinks(idstring, encodedtable, frag, page) {

    var arrayOfItems = frag.querySelectorAll("th.header a");

    arrayOfItems.forEach(item => {
        var sortid = item.getAttribute('data-sortby');
        var sortorder = item.getAttribute('data-sortorder');
        var thide = item.getAttribute('data-action') == 'hide' ? item.getAttribute('data-column') : null;
        var tshow = item.getAttribute('data-action') == 'show' ? item.getAttribute('data-column') : null;

        item.setAttribute('href', '#');
        item.addEventListener('click', () => {
            callLoadData(idstring, encodedtable, page, sortid, thide, tshow, sortorder);
        });
    });
}

/**
 * The rendered table has links we can't use. We replace them with eventlisteners and use the callLoadData function.
 * @param {string} idstring
 * @param {string} encodedtable
 * @param {DocumentFragment} frag
 * @param {int} page
 */
function replaceResetTableLink(idstring, encodedtable, frag, page) {
    var arrayOfItems = frag.querySelectorAll("div.resettable");

    if (!arrayOfItems || arrayOfItems.length == 0) {
        return;
    }
    arrayOfItems.forEach(item => {
        var classofelement = item.getAttribute('class');
        if (classofelement.indexOf('resettable') >= 0) {
            let listOfChildren = item.querySelectorAll('a');
            listOfChildren.forEach(subitem => {
                subitem.setAttribute('href', '#');
                subitem.addEventListener('click', () => {
                    callLoadData(idstring, encodedtable, page, null, null, null, null, 1);
                });
            });
        }
    });
}

/**
 * The rendered table has links we can't use. We replace them with eventlisteners and use the callLoadData function.
 * @param {string} idstring
 * @param {string} encodedtable
 * @param {DocumentFragment} frag
 */
function replacePaginationLinks(idstring, encodedtable, frag) {
    var arrayOfPageItems = frag.querySelectorAll(".page-item");

    if (!arrayOfPageItems || arrayOfPageItems.length == 0) {
        return;
    }
    arrayOfPageItems.forEach(item => {

        let pageNumber = item.dataset.pagenumber;

        if (pageNumber) {
            --pageNumber;
            item.addEventListener('click', () => {
                callLoadData(idstring, encodedtable, pageNumber);
            });
        }
    });
}

/**
 * The rendered table has links we can't use. We replace them with eventlisteners and use the callLoadData function.
 * @param {string} idstring
 * @param {string} encodedtable
 * @param {DocumentFragment} frag
 */
function replaceDownloadLink(idstring, encodedtable, frag) {

    var arrayOfItems = frag.querySelectorAll("form");

    arrayOfItems.forEach(item => {
        if (item.tagName == 'FORM') {
            item.setAttribute('method', 'POST');
            let newnode = document.createElement('input');
            newnode.setAttribute('type', 'hidden');
            newnode.setAttribute('name', 'encodedtable');
            newnode.setAttribute('value', encodedtable);
            item.appendChild(newnode);
        }
    });
}

/**
 *
 * @param {*} idstring
 * @param {*} encodedtable
 * @param {*} page
 */
 function replaceLinksInFrag(idstring, encodedtable, frag, page = null) {

    // eslint-disable-next-line no-console
    console.log(frag);

    if (!page) {
        const activepage = frag.querySelector('li.page-item active');
        if (activepage) {
            page = activepage.getAttribute('data-page-number');
        }
    }

    replaceDownloadLink(idstring, encodedtable, frag);
    replaceResetTableLink(idstring, encodedtable, frag, page);
    replacePaginationLinks(idstring, encodedtable, frag);
    replaceSortColumnLinks(idstring, encodedtable, frag, page);
}
