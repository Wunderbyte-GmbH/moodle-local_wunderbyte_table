import Ajax from 'core/ajax';

/** @param {string} encodedtable */
export const init = (encodedtable) => {
    const observeDOMChanges = (callback) => {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.addedNodes) {
                    mutation.addedNodes.forEach((node) => {
                        if (node instanceof HTMLElement && node.querySelector('[name="filter_columns"]')) {
                            callback(node.querySelector('[name="filter_columns"]'));
                        }
                    });
                }
            });
        });
        observer.observe(document.body, {childList: true, subtree: true});
    };
    observeDOMChanges((dropdown) => {
        dropdown.addEventListener('change', (event) => {
            const selectedValue = event.target.value;
            Ajax.call([{
                methodname: 'local_wunderbyte_table_get_filter_column_data',
                args: {
                    filtercolumn: selectedValue,
                    encodedtable: encodedtable
                },
                done: (response) => {
                    const filteredit = document.getElementById('filter-edit-fields');
                    if (filteredit && response.filtereditfields) {
                        filteredit.innerHTML = response.filtereditfields;
                    }
                    const filteradd = document.getElementById('filter-add-field');
                    if (filteradd && response.filteraddfields) {
                        filteradd.innerHTML = response.filteraddfields;
                    }
                },
                fail: (error) => {
                    // eslint-disable-next-line no-console
                    console.error('Web service error:', error);
                }
            }]);
        });
    });
};