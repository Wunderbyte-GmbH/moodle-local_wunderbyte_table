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

/**
 * Gets called from mustache template.
 * @param {string} idstring
 * @param {string} encodedtable
 */
export const init = (idstring, encodedtable) => {
    respondToVisibility(idstring, encodedtable, callLoadData);
};

/**
 * React on visibility change.
 * @param {string} idstring
 * @param {string} encodedtable
 * @param {function} callback
 */
function respondToVisibility(idstring, encodedtable, callback) {
    let element = document.getElementById('a' + idstring);
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
    callback(idstring, encodedtable);
}

/**
 * Function to check visibility of element.
 * @param {*} el
 * @returns {boolean}
 */
function isHidden(el) {
    var style = window.getComputedStyle(el);
    return ((style.display === 'none') || (style.visibility === 'hidden'));
}

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
 */
export const callLoadData = (
    idstring,
    encodedtable,
    page = null,
    tsort = null,
    thide = null,
    tshow = null,
    tdir = null,
    treset = null) => {
    let table = document.getElementById('a' + idstring);
    let spinner = document.querySelector('#a' + idstring + 'spinner .spinner-border');
    spinner.classList.toggle('hidden');
    table.classList.toggle('hidden');

    Ajax.call([{
        methodname: "local_wunderbyte_table_load_data",
        args: {
            'encodedtable': encodedtable,
            'page': page,
            'tsort': tsort,
            'thide': thide,
            'tshow': tshow,
            'tdir': tdir,
            'treset': treset
        },
        done: function(res) {

            let frag = document.createRange().createContextualFragment(res.content);

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

            let table = document.getElementById('a' + idstring);

            while (table.firstChild) {
                table.removeChild(table.lastChild);
            }
            table.appendChild(frag);
            // Once the frag is appended, we have to trigger all load Events.
            const allElements = table.getElementsByClassName('wunderbyteTableDiv');
            for (var i = 0; i < allElements.length; i++) {
                const spanchild = allElements[i].querySelector('span');
                if (typeof spanchild.onclick === 'function') {
                    spanchild.click();
                }
             }

            spinner.classList.add('hidden');
            table.classList.remove('hidden');
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
        var element = item.querySelector('a');
        if (!element) {
            return;
        }
        var url = element.getAttribute("href");
        var pageNumber;
        if (url != undefined && url != '#') {
            let newurl = new URL(url);
            var urlParams = new URLSearchParams(newurl.search);
            pageNumber = urlParams.get('page');
        }
        element.setAttribute('href', '#');
        if (pageNumber) {
            element.addEventListener('click', () => {
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