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

// import {renderFilter, initializeCheckboxes} from 'local_wunderbyte_table/search';
import {initializeCheckboxes, getFilterOjects} from 'local_wunderbyte_table/search';

var loading = false;
var scrollpage = 0;

var tablejs = null;

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

    // If we find the table element AND if it hasn't yet the encoded table set, we abort this.
    // Hereby we avoid to run JS multiple times.
    if (element && !element.dataset.encodedtable) {
        element.dataset.encodedtable = encodedtable;
    } else {
        // If we don't find an element, we abort.
        return;
    }

    // We only make this callback during init if there is the spinner running.
    // We don't want to run all of this if we don't use lazyloading.
    let spinner = document.getElementById(identifier + 'spinner');

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
        // This is what we do when we didn't lazyload.
        replaceLinksInFrag(idstring,encodedtable, element, null);

        const selector = ".wunderbyte_table_container_" + idstring;
        initializeCheckboxes(selector, idstring, encodedtable);
    }

    // Check to see if scrolling near bottom of page; load more photos
    // This shoiuld only be added once.

    // As this can only be here once per table, we mark the table.
    if (element.dataset.scrollinitialized) {
        return;
    }
    element.dataset.scrollinitialized = true;

    // eslint-disable-next-line no-console
    console.log('initialize scroll', element.dataset.scrollinitialized);

    const scrollableelement = document.querySelector("#page");

    // eslint-disable-next-line no-console
    console.log(scrollableelement);

    scrollableelement.addEventListener('scroll', () => {

        if (!loading && scrollpage >= 0) {
            if (element.scrollHeight < scrollableelement.scrollTop + document.body.scrollHeight) {
                // eslint-disable-next-line no-console
                console.log('load more data', window.scrollY, window.scrollY + window.innerHeight,
                    document.body.scrollHeight);
                scrollpage++;
                // eslint-disable-next-line no-console
                console.log('call load for scroll', scrollpage);
                callLoadData(idstring,
                        encodedtable,
                        scrollpage,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null);
            }
        }

    });
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

    if (loading) {
        return;
    }

    // We reset scrollpage with 0 when we come from the filter.
    if (page !== null) {
        scrollpage = page;
    }

    // We always have to see if we need to apply a filter. Reload might come from scroll, but filter has to be applied nevertheless.
    if (filterobjects === null) {
        filterobjects = getFilterOjects();
    }

    let table = document.getElementById('a' + idstring);

    // This is now the individual spinner from the wunderbyte table template.
    let spinner = document.querySelector('#a' + idstring + 'spinner .spinner-border');

    // eslint-disable-next-line no-console
    console.log(table);

    // If we replace the whole table, we show the spinner. If we only add rows in infinite scroll, we don't.
    if (scrollpage == 0) {
        if (spinner) {
            spinner.classList.remove('hidden');
        }
        if (table) {
            table.classList.add('hidden');
        }
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

            // eslint-disable-next-line no-console
            console.log(res);

            let jsonobject = JSON.parse(res.content);
            let rendertemplate = res.template;
            let rendercontainer = true;

            // We can always expect a wunderbyte table container at this point.
            // The container will hold wunderbyteTableClass, wunderbyteTableFilter, wunderbyteTableSearch classes.
            let container = document.querySelector(".wunderbyte_table_container_" + idstring);
            const filtercontainer = container.querySelector(".wunderbyteTableFilter");

            // If there is a container, we don't want to render everything again.
            if (scrollpage > 0) {
                // Also, we want to use the table instead of the container template.
                // This is not perfect, but necessary at the moment.
                const i = rendertemplate.lastIndexOf('/');
                let rowtemplate = rendertemplate.substring(0, i);
                rowtemplate += '/row';

                // eslint-disable-next-line no-console
                console.log(rowtemplate, rendertemplate);

                if (!jsonobject.table.hasOwnProperty('rows')) {
                    // We set the scrollpage to -1 which means that we don't reload anymore.
                    scrollpage = -1;
                    loading = false;
                    return;
                }
                let rows = jsonobject.table.rows;

                // eslint-disable-next-line no-console
                console.log(rendertemplate, rows);

                // We create an array of promises where every line is rendered individually.
                const promises = rows.map(row => {
                    Templates.renderForPromise(rowtemplate, row).then(({html, js}) => {
                        // Here we add the rendered content to the table div.
                        Templates.appendNodeContents('#a' + idstring + " div.rows", html, js);
                        return true;
                    }).catch(e => {
                        // eslint-disable-next-line no-console
                        console.log(e);
                    });
                });

                if (tablejs === null) {
                    // eslint-disable-next-line no-unused-vars
                    const promise = Templates.renderForPromise(rendertemplate, jsonobject).then(({html, js}) => {

                        tablejs = js;
                        return true;
                    }).catch(e => {
                        // eslint-disable-next-line no-console
                        console.log(e);
                    });

                    promises.push(promise);
                }

                // Once all the promises are fullfilled, we set loading to false.
                Promise.all(promises).then(() => {

                    // eslint-disable-next-line no-console
                    console.log(promises.length);

                    setTimeout(() => {
                        // We only added rows, but they might need some js from the table, so we add the table js again.
                        Templates.appendNodeContents('#a' + idstring, '', tablejs);

                        // eslint-disable-next-line no-console
                        console.log('just added js to page ' + page + " " + rows);
                    }, 100);

                    loading = false;
                    return;
                }).catch(e => {
                    // eslint-disable-next-line no-console
                    console.log(e);
                });

                return;

            } else if (filtercontainer) { // If there is a container, we don't want to render everything again.
                // Also, we want to use the table instead of the container template.
                // This is not perfect, but necessary at the moment.
                const i = rendertemplate.lastIndexOf('/');
                rendertemplate = rendertemplate.substring(0, i);
                rendertemplate += '/table';

                rendercontainer = false;
            }

            let frag = container.querySelector(".wunderbyteTableClass");

            // We render the html with the right template.
            Templates.renderForPromise(rendertemplate, jsonobject).then(({html, js}) => {

                if (!rendercontainer) {
                    // Now we clean the existing table.
                    while (frag.firstChild) {
                        frag.removeChild(frag.lastChild);
                    }

                    // Here we add the rendered content to the table div.
                    Templates.appendNodeContents('#a' + idstring, html, js);
                } else {
                    // Here we try to render the whole
                    const parent = container.parentElement;
                    container.remove();
                    Templates.appendNodeContents(parent, html, js);

                    container = document.querySelector(".wunderbyte_table_container_" + idstring);
                }

                replaceLinksInFrag(idstring,encodedtable, container, page);

                // When everything is done, we loaded fine.
                loading = false;

                if (spinner) {
                    spinner.classList.add('hidden');
                }
                if (table) {
                    table.classList.remove('hidden');
                }

                return true;
            }).catch(ex => {
                loading = false;
                Notification.addNotification({
                    message: 'failed rendering ' + ex,
                    type: "danger"
                });
            });
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
 * @param {*} frag
 * @param {*} page
 */
 function replaceLinksInFrag(idstring, encodedtable, frag, page = null) {

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
