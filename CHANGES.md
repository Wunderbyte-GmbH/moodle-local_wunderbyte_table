## Version 1.6.0 (2023060800)
* Bugfix: Fix problem with fulltextsearch when adding bigint columns.

## Version 1.5.9 (2023060500)
* New feature: Filter for timespan, comparing two selected values (timespan) to two values of a record (i.e. startdate, enddate).

## Version 1.5.8 (2023052200)
* New feature: Localized Filter

## Version 1.5.7 (2023051700)
* New feature: Filter for columns with Unix timestamp.
* New feature: Applying filter, search and sort params via URL.
* New feature: Improved caching, especially for filters.
* Bugfix: Tableheadercheckbox selecting all checkboxes in multiple table.
* Bugfix: Infinitescroll.
* Bugfix: Checkbox in card display.

## Version 1.5.6 (2023042400)
* New feature: Keeping last (current) sorting on reload.
* New feature: Sortorder button and sorting select synchronized with tableheader sorting.
* New feature: Keeping value for number of rows displayed in select on table reload.
* New feature: Tablehash-cash unique for each user and tableid.

## Version 1.5.5 (2023042000)
* New feature: Displaying multiple tables in tabs on demo page.
* New feature: Added selection-mandatory property and function to actionbuttons to define if an actionbutton triggers action without elements selected.
* New feature: Actionbutton not triggering modal can treat data of selected elements and transmit multiple calls.
* Bugfix: Tableheader row display with z-index displaying on top level of table.
* Bugfix: Tableheader icons for sorting changed descending and ascending order.
* Bugfix: Tableheader highlighting default sortcolumn and sortorder.
* Bugfix: Tableheader click on columnname triggers sorting only for columnnames defined as sortable.

## Version 1.5.4 (2023041300)
* New feature: change number of rows in pagination mode.

## Version 1.5.3 (2023040600)
* New feature: Added new form functionality.
* New feature: Added new checkbox functionality.
* Bugfix: Fixed aria-label strings.
* Bugfix: Use a dot to concatenate (plus sign is JS notation).
* Bugfix: Fixed action buttons logic (added nomodal flag).
* Improvement: Use cache instead of encoded table to improve security and performance.
* Improvement: Renamed function to instantiate_from_tablecache_hash.

## Version 1.5.2 (2023032100)
* Bugfix: Pagination fixed.

## Version 1.5.1 (2023031300)
* Improvement: Allow value "0" as filter option (== null isstead of "empty" check).

## Version 1.5.0 (2023030900)
* Bugfix: Fix JS error on reloadAllTables.
* Improvement: Better error message on sql error.

## Version 1.4.9 (2023030100)
* Improvement: Show error when sql fails.

## Version 1.4.8 (2023022800)
* Improvement: Icon in front of action button label via template.
* Improvement: Define baseurl only in test.php (not in download.php).
* Improvement: No automatic checkboxes during downloading.
* Bugfix: Fix warning on download if $jsonobject is empty.

## Version 1.4.7 (2023022000)
* Improvement: Fix countlabel string.
* Improvement: add reloadAllTables function.
* Improvement: filter js less verbose.
* Improvement: Add special treatment for JSON objects (e.g. for teachers).
* Improvement: If there is nothing to filter, we don't show the filter.
* Improvement: Documentation for explode and JSON features for filters.
* Improvement: Make sure data-id is present in dom. We need it for a couple of operations.
* Improvement: Layout - smaller filter column.
* Improvement: Add a few identifiers for table & rows.
* Improvement: make actionbutton more robust.
* Improvement: add cardsort property to hide or unhide special sort element.
* Bugfix: fix filter with multiple tables on one page.
* Bugfix: fix row enumerations being correct as well as row ID.
* Bugfix: small fix to avoid overlapping footer.
* Bugfix: Fixed and improved sorting.
* Bugfix: fix filter for int & postgres.

## Version 1.4.6 (2023012800)
* New feature: Add the "addcheckboxes functionality" with configurable action.

## Version 1.4.5 (2023012600)
* Improvement: Improved layout and styling for filter.
* Improvement: Better CSS for cards view.
* Improvement: New export param 'shoppingcartisavailable' for templates - so shopping cart plugin is optional.
* Bugfix: Fixed bug with component of renderer (must be 'local_wunderbyte_table').

## Version 1.4.4 (2023011200)
* Improvement: Improved layout and styling.
* Improvement: Code quality.

## Version 1.4.3 (2022121500)
* Bugfix: Some changes due to deprecation waringing.

## Version 1.4.2 (2022120500)
* Improvement: Some layout and design changes.

## Version 1.4.1
* Improvement: Improved styling of sorting buttons and layout.
* Bugfix: Fixed baseurl handling to fix download which didn't work in modal.
* Bugfix: Fixed lazyout on test.php.

## Version 1.4.0
* New feature: New possibility to turn infinite scroll on and off.
* New feature: New possibility to turn labels and code buttons on and off.
* Improvement: Better export (print) functionality.
* Improvement: Don't hide table on reload.
* Improvement: Sticky header and new sorting icons, new toggler.
* Improvement: Improved code quality (linting).
* Bugfix: Many minor bugfixes.

## Version 1.3.2 (2022101000)
* New feature: New export (print) functionality.

## Version 1.3.1 (2022092200)
* Bugfix: Sorting of non sql column caught now.

## Version 1.3.0 (2022091900)
* Bugfix: Fix scrolling bug.

## Version 1.2.6 (2022091500)
* Bugfix: Fix incorrectly implemented optional values, move to required.
* Bugfix: Fix issue #1, missing cached table string.

## Version 1.2.5 (2022082901)
* Fix infinite scroll on some themes

## Version 1.2.4 (2022082900)
* Work on templates and consistency
* Fix localized column headers
* Add possibility to show reload button
* Numeric filters can't have wildcards anymore

## Version 1.2.1 (2022081800)
* Clarify container structure for generalical ajax reload (pages, infinite scroll, filtering) and support for multiple table formats in one project.

## Version 1.2.0 (2022080900)
* Introduce fulltext search, filter and sort

## Version 1.1.8 (2022050400)
* Improvement: Improved code quality

## Version 1.1.7 (2022042100)
* Bugfix: Fix nolazyout function

## Version 1.1.6 (2022041800)
* Improvement: No login required for using lazyloading of Wunderbyte Table