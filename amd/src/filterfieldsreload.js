import Ajax from 'core/ajax';

/**
 * Initialise it all.
 */
export const init = () => {
    const observeDOMChanges = (callback) => {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.addedNodes) {
                    mutation.addedNodes.forEach((node) => {
                        if (node instanceof HTMLElement && node.querySelector('[name="wbfilterclass"]')) {
                            callback(node.querySelector('[name="wbfilterclass"]'));
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
                methodname: 'local_wunderbyte_table_get_filter_fields',
                args: {filtertype: selectedValue},
                done: (response) => {
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