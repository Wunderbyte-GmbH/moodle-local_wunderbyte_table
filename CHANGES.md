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