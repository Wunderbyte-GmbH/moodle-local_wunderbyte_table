## Version 2.0.58 (2025080400)
* Improvement: Add support of unix timestamp fields for intrange filter.

## Version 2.0.57 (2025073100)
* Improvement: Optional Scroll to container

## Version 2.0.56 (2025072400)
* Bugfix: fix undefined property, fallback for gotopage
* Improvement: Fokus for elements in filter

## Version 2.0.55 (2025071800)
* New Feature: Go to page added
* Improvement: mobile view styling
* Improvement: Possibility to disable scrolling at tablereload

## Version 2.0.54 (2025070800)
* Bugfix: More specific selector for hierarchychild-checkbox

## Version 2.0.53 (2025070700)
* Improvement: Hierarchy filter select behaviour
* Bugfix: No filtercount for hierarchy filter
* Tests: New behat scenario: WB_table settings: control presence of strings on all settings page
* Tests: fix inclusion of behat_base class
* Tests: linting: fix phpunit coverage warnings

## Version 2.0.52 (2025061700)
* Improvement: Accessibility - Sort Table on Enter
* Improvement: Accessibility - Scroll into view with Navbar
* Improvement: Add selectable 5 rows to pagecounter
* Tests: Validate settings page

## Version 2.0.51 (2025060600)
* Improvement: Switch to reusable moodle-plugin-ci.yml workflow.
* Improvement: Accesibility - Focus on Filters.
* Bugfix: Fix strings for AMOS.

## Version 2.0.50 (2025052700)
* Improvement: Enable table container to be reloaded entirely if needed

## Version 2.0.49 (2025052000)
* Improvement: Make sure adding filters does not destroy table settings and includes various small improvements
* Improvement: Hierarchy filter checkbox layout changes
* Improvement: Add checkbox for hierarchical category
* Improvement: Add hierarchical filter to demo
* Improvement: Datepicker merging
* Bugfix: Rename group to subdatepicker_group.
* Bugfix: Data adjustment
* Bugfix: Better placement for allowed column check (SQL columns only)
* Bugfix: Fixes for custom filters – missing empty checks, etc.
* Bugfix: Add custom filter button to template for cards container
* Tests: Add new PHPUnit test method test_require_access()
* Tests: Fix timezone issues in weekdays/hourlist test
* Tests: Scenario for WB_Table navigation – switch view templates

## Version 2.0.48 (2025043000)
* New feature: Button to add individual filters can now be added in table settings for each table.
* Improvement: Make sure adding filters is only possible for allowed SQL columns.
* Improvement: All filter implemented and tested.
* Improvement: Remove legacy code.
* Improvement: Split datepicker class.
* Improvement: Hierarchy filter implementation.
* Improvement: Filter form.
* Improvement: Refactoring class structure.
* Improvement: Validation manager setup.
* Improvement: Separate Moodle divs.
* Improvement: Edit and add key value pair.
* Improvement: Edit filter key value pair.
* Improvement: Restructure to column manager.
* Improvement: Add filter form.
* Improvement: Allow table to be scrolled horizontally and add more space for right-sided components.
* Improvement: Better filter sizes and reduce gaps.
* Improvement: Add encodedtable in template for tests.
* Bugfix: Fix weekdays strings.
* Bugfix: Fix type error in unit tests.
* Bugfix: Fix unit tests for filter types.

## Version 2.0.47 (2025040800)
* Bugfix: Make sure switcher remembers selected template for table is encoded.

## Version 2.0.46 (2025040300)
* Improvement: Add classes to template switcher mustache.
* Bugfix: The hourlist and weekdays filters displays available intervals/weekdays now correctly.
* Bugfix: Wrong type for showpagination.
* Tests: Improved phpunit test.
* Tests: Improved BEHAT tests.

## Version 2.0.45 (2025040100)
* New feature: Switch between table templates (individually for each user).
* Bugfix: Display demp form instead in demo.php
* Bugfix: use format_text instead of format_string on textarea.

## Version 2.0.44 (2025030400)
* Improvement: unify field controller
* Bugfix: correctly apply format string to values from customfields and return values.
* Bugfix: Correctly return value for wbt_field_controller base fields

## Version 2.0.43 (2025022600)
* Improvement: New get_values_array() function for customfields.
* New feature: Add possibility to show download button at bottom of table.
* Bugfix: Field property of field controller can be accessed directly without getter.
* Test: Add test to verify sortorder in infinite scroll environment

## Version 2.0.42 (2025021800)
* New feature: Callbackfilter
* New feature: Introduce sortable classes to allow sort specific sql and caches
* Improvement: Possibility to turn off caching
* Tests: Add automatic tests for callback filtering and new standard sortable class
* Tests: Add phpunit test for testing standard filter

## Version 2.0.41 (2025021200)
* Set minlength to 1 (to also consider input of "AI" in fulltextsearch).

## Version 2.0.40 (2025012900)
* Bugfix: Avoid empty get_in_or_equal error in function instantiate_by_shortnames of wbt_field_controller_info.

## Version 2.0.39 (2025012200)
* Bugfix: Hierarchical filter search in menu (#75).

## Version 2.0.38 (2025011602)
* Bugfix: Make sure that field controller is only applied if the string is not yet localized and sorted with sortedarray.
* Bugfix: Make sure we use the correct language prefix for storing filter & such
* Improvement: Apply timezone offset for hourlist filter.

## Version 2.0.37 (2025011601)
* Bugfix: Fixes for filters using wbt_field_controller.

## Version 2.0.36 (2025011600)
* Bugfix: Fix README - table setting is called "addcheckboxes".
* Bugfix: Add fallback for field controller, so we always have at least the text controller.

## Version 2.0.35 (2025010900)
* Improvement: Implement generic classes for customfields in wunderbyte table and get the string values to be shown in filter from field controller.
* Improvement: Do not show rows for empty or not found values.
* Improvement: Add field controllers for text and textarea and return text controller as fallback.
* Bugfix: Add missing import of wbt_field_controller_info.
* Bugfix: Correct searchfield SQL for elder moodle with mysql #68.
* Bugfix: Custom field dynamic needs its own field controller as customfield dynamic might not be installed.

## Version 2.0.34 (2024121200)
* Bugfix: Datepicker filter correctly applying enddate & unset Datepicker correctly

## Version 2.0.33 (2024120500)
* Improvement: change filterview template.
* Bugfix: hourlist filter.

## Version 2.0.31 (2024112800)
* New feature: Add possibility to pass any template data.
* Bugfix: Correct id for filter checkbox & label.
* Bugfix: "Cannot read properties of null (reading 'childNodes')" JS error.
* Bugfix: Grunt JS.

## Version 2.0.30 (2024112500)
* Improvement: Add support for Moodle 4.5.
* Bugfix: Correctly update URL from search (#67).
* Bugfix: Correctly apply filtercount to hierarchical filter Wunderbyte-GmbH/moodle-moodle-local_wunderbyte_table#243.
* Bugfix: Do not apply intrange filter when there is no string to explode. Wunderbyte-GmbH/Wunderbyte-GmbH#243.

## Version 2.0.29 (2024112000)
* Bugfix: Fix grunt "file is stale" errors.

## Version 2.0.28 (2024111900)
* Bugfix: Fix typo (dot instead of comma) breaking sortable columns and tests.
* Bugfix: Add missing data-type for wb_action_button.

## Version 2.0.27 (2024111100)
* Improvement: Filterview flip arrows on collapse.

## Version 2.0.26 (2024102900)
* Bugfix: Fix action button selector.
* Improvement: Change PNG to SVG in filterview template.
* Tests: Adjust behat scenarios.

## Version 2.0.24 (2024102100)
* Improvement: Extend tests for filters
* Improvement: Scroll to upper margin on page switch
* Bugfix: Apply hierarchical filter
* Bugfix: Fix standardfilter, especially for explode
* Bugfix: Correctly count values for filters

## Version 2.0.23 (2024101600)
* Bugfix: Strip leading zeros from intrange filter input
* Bugfix: standardfilter for ints (ids etc)
* Bugfix: Correctly count values for explode filter
* Bugfix: display filtercount label for standardfilter correctly

## Version 2.0.22 (2024101101)
* Improvement: Added support for filterview mobile changes
* Bugfix: Fix wrong calculation of morethanonetable
* Bugfix: Fallback for empty variables in timespan filter

## Version 2.0.21 (2024101100)
* Bugfix: Filter for json dataattributes
* Bugfix: Fallback empty calendar

## Version 2.0.20 (2024100900)
* Bugfix: Avoid pipe if no filter/searchtext is selected
* Bugfix: Fallback for empty data in event

## Version 2.0.19 (2024100701)
* Bugfix for filters addressing two columns
* Bugfix: fetch other data from event correctly

## Version 2.0.18 (2024100700)
* Bugfix: Don't throw an error just because a column is not supported by the datepicker.

## Version 2.0.17 (2024100400)
* Improvement: Support shownopagination
* Improvement: Add suppress functionality
* Bugfix: Avoid accessing missing key in array

## Version 2.0.16 (2024100100)
* Improvement: filterview with two columns
* Bugfix: Apply filter to url for tables with search
* Bugfix: write searchtext into search field on reload via url

## Version 2.0.15 (2024092600)
* New Feature: filtertype intrange

## Version 2.0.14 (2024092300)
* Improvement: possibility to allow searchbutton in template

## Version 2.0.13 (2024091000)
* Improvement: make filters able to break line

## Version 2.0.12 (2024090300)
* Bugfix: Make sure that current filter state is also noticed by js. Support for hierarchical filters on url output
* Bugfix: Delete last href call to anchor, which led to jump in page

## Version 2.0.11 (2024081900)
* New Feature: Show counter for checked filters directly on the label
* Improvement: Sort strings alphabetically.

## Version 2.0.10 (2024071900)
* Bugfix: Revert changes for node replacement in init.js.

## Version 2.0.9 (2024071800)
* Improvement: Add possibility to define message on submitbutton for dynamicform
* Improvement: Avoid scrolling when using pagination links.

## Version 2.0.8 (2024070700)
* Improvement: Always explode multi entry strings, even when they are int values
* Improvement: Add possibility to return message on Form (Add message AND success value!)
* Improvement: Add show filter button functionality

## Version 2.0.7 (2024061200)
* Improvement: New filterdesign.
* Improvement: Name attribute added to toggle filter button (accessibility).

## Version 2.0.6 (2024042600)
* Bugfix: Fix hourlist filter for timezone (bugs with summer time / winter time remain).
* Bugfix: Fix MariaDB SQL errors with reserved keywords.

## Version 2.0.5 (2024042200)
* Bugfix: Fix strings for AMOS.
* Bugfix: MariaDB does not allow alias in DELETE statement.

## Version 2.0.3 (2024041000)
* Improvement: Update callback to purge caches and delete filter and sql hashes
from DB when allowedittable is turned off or on.

## Version 2.0.2 (2024040200)
* Improvement: Triggering reload on all tables will now reload all rows of an infinite table.

## Version 2.0.1 (2024032700)
* Improvement: Show spinner when we trigger ajax call.
* Improvement: Add "apply filtersetting" to download table.
* Bugfix: Remove console logs that had null pointer problems.

## Version 2.0.0 (2024032500)
* New feature: Now you can individually configure filters and settings for each Wunderbyte Table.
(Setting has to be turned on in config settings of Wunderbyte Table.)
* Improvement: Lots of little improvements that were necessary to get new settings to work correctly.

## Version 1.9.18 (2024031800)
* Improvement: New filter classes for better handling of filters
* Improvement: First steps to edit filter on the fly (experimental)
* Improvement: Add generic reordering of list items (experimental)

## Version 1.9.17 (2024031400)
* Improvement: More explanation on how to integrate a form.
* Improvement: Add demo how to order by default for more than one columns.
* Bugfix: Fix string confusion (English, German) - closes #51.
* Bugfix: Namespace.

## Version 1.9.16 (2024022900)
* Improvement: Show cards right next to each other (CSS fix).
* Bugfix: Add missing cache definitions.

## Version 1.9.15 (2024022600)
* Improvement: Better layout (margins) for filter search.

## Version 1.9.14 (2024022300)
* Improvement: Add correct context handling for table.
* Improvement: Make search in columns optional.
* Improvement: Make no context compatible with existing automated tests.

## Version 1.9.13 (2024022200)
* Bugfix: Use SORT_ASC as default sort order when return_current_sortorder() returns null.

## Version 1.9.12 (2024021900)
* Improvement: Better handling of pagination cache.
* Improvement: More efficient cache handling.
* Bugfix: Fix a behat scenario.
* Bugfix: Fix JSON for template.
* Bugfix: Revert "Improvement: Constructor may not be overriden".

## Version 1.9.11 (2024021600)
* Improvement: Constructur can not be overriden anymore to prevent unwanted caching.
* Improvement: Filter default values can be set via strtotime function
* Improvement: Add debugging option via db

## Version 1.9.10 (2024012900)
* Improvement: Add more fields to WB table for better debugging.
* Bugfix: Fix switch statement in edit filter.

## Version 1.9.9 (2024012405)
* Improvement: New filter handling and new setting to store filter settings to DB. (#47)

## Version 1.9.8 (2024012400)
* Bugfix: Correct display of filtered records on pages.

## Version 1.9.7 (2024011600)
* Bugfix: Avoid error for not supported dbfamilies.
* Bugfix: Totalcount could throw error because of ambigious columnname.
* Bugfix: Set pages to correct values when infinite scroll is activated.
* Bugfix: Fix behat test.

## Version 1.9.6 (2024011201)
* Bugfix: Automatic tests are running correctly

## Version 1.9.5 (2024011200)
* Improvement: Filter generation in large tables is much faster and more efficient
* Improvement: Filters now show a count of hits
* Improvement: Hoursfilter: Full hours can be extracted from a unix timestamp in postgres, mysql & mariadb DBs

## Version 1.9.4 (2024010600)
* Bugfix: Minlength implementation broke changing sites with textinput.

## Version 1.9.3 (2023122000)
* Improvement: Add class wbtablefilter-columnname to each filter-div, so we can hide them with CSS.
* Bugfix: Fix some Github actions (mustache templates).

## Version 1.9.2 (2023121400)
* Bugfix: Localize Demo strings in German #38
* Bugfix: Delete unneeded and conflicting CSS #34

## Version 1.9.1 (2023121100)
* Improvement: Styling of search and sort icons.
* Bugfix: Normal size of sorting icon (A->Z).

## Version 1.9.0 (2023120700)
* Improvement: Add Documentation about additional security "action_" methods.
* Improvement: Toggle search immediately after enter key pressed.
* Improvement: Update readme about search function enter key.
* Improvement: Large A->Z sort icon, hamburger icon for filters.

## Version 1.8.9 (2023120600)
* Bugfix: Revert wrong "fixes" that actually broke the new security with "action_"-prefix.
* Bugfix: Add missing capability strings.
* Bugfix: Add "action_" to rownumberperpage function.

## Version 1.8.8 (2023120501)
* Bugfix: Check "real" method names including "_action".
* Bugfix: Fix behat.

## Version 1.8.7 (2023120500)
* Improvement: Additional security via "action_" prefix for methodnames that can be called via webservice actions.

## Version 1.8.6 (2023120400)
* Bugfix: Notifications and debug error if uniqueid contains any symbols other than ASCII alphanumeric characters, underlines and spaces.

## Version 1.8.5 (2023113001)
* Improvement: Better tests, some smaller improvement of UI.

## Version 1.8.4 (2023113000)
* Improvement: Add privacy class
* Improvement: Add view and actionbutton events for logging
* Improvement: Add requirelogin and requirecapability for more security
* Improvement: Additional automatic tests

## Version 1.8.3 (2023112700)
* Improvement: Added support for Moodle 4.3 and PHP 8.2.
* Improvement: Nicer strings for already set filters and language fix.
* Improvement: No unsanitized params in sql requests (#39).
* Bugfix: Restore possibility to show chosen filters and reset all filters.
* Bugfix: Fix search icon.
* Bugfix: Forget filter after reset.
* Bugfix: Fix click on reset button.
* Bugfix: Fix stale file.
* Bugfix: Fix for unscrollable page (and 4 failed behat test) under Moodle 4.3.
* Bugfix: More specific names in styles.css to avoid confusion (#34).

## Version 1.8.2 (2023112200)
* Improvement: Actionbutton now transmitting data from table and title for modal.
* Improvement: Add readme about formname and title.
* Bugfix: Fix string in demo.php.

## Version 1.8.1 (2023111300)
* Bugfix: Fix param definition in external services.
* Bugfix: Fix some namespaces.

## Version 1.8.0 (2023103100)
* Bugfix: Remove "zoom" selector from styles.css because of unintended consequences in mod_zoom plugin.

## Version 1.7.9 (2023101000)
* Bugfix: Do not show "&amp;" in filters but show normal "&".

## Version 1.7.8 (2023100900)
* Improvement: Re-enable filtering for "users" tab on demo page.
* Improvement: Use primary color for filter button.
* Improvement: Only show success notifications if there is a message.
* Improvement: Better alignment of search box.
* Improvement: Fix spaces between up and down sorting arrows.
* Bugfix: Fix sorting pseudo elements for older versions.
* Bugfix: Fix filter for escaped chars.
* Bugfix: Fix FontAwesome6 issues.
* Bugfix: Show search icon again and use primary text color for both search and sort icons.

## Version 1.7.7 (2023092101)
* Bugfix: Fix timefilters to work with count labels

## Version 1.7.6 (2023092100)
* New feature: GH-26 & GH-27 display current filter settings and delete on button click.
* Improvement: Hide search fields in 3/4 demo tables.
* Bugfix: GH-25 Bugfix: Sorting field only displayed when sorting columns selectable.
* Bugfix: GH-24 Fix for Moodle 4.2 FontAwesome sorting pseudo elements.
* Bugfix: Keep tables in modals scrollable after actionbutton execution.
* Bugfix: Prevent console error because of missing container element.

## Version 1.7.5 (2023091900)
* Bugfix: Allow for multiple values, comma separated values via filter.
* Bugfix: Sorting not working without sort component.

## Version 1.7.4 (2023091400)
* Bugfix: Fix infinitescroll.
* Bugfix: Fix bug where we didn't attach scroll to window.
* Bugfix: Fix PHPDocs.

## Version 1.7.3 (2023091300)
* Improvement: Code quality improvements and linting.
* Bugfix: Fixes for github actions (behat, mustache etc.).
* Bugfix: Fix search icon for Moodle 4.2.

## Version 1.7.2 (2023090800)
* New feature: Flexoverlap filter for timespan taking into account all kind of overlapping timespans.
* New feature: Search function within filters.
* Improvement: Display norecords message, when no records found in table.

## Version 1.7.1 (2023090600)
* Improvement: Github actions green again.
* Improvement: Add aria label to actionbutton icon.
* Improvement: Correction in readme.
* Improvement: Add title to icon in column.

## Version 1.7.0 (2023090100)
* Bugfix: str_replace causes exception if $match[0] not found.

## Version 1.6.9 (2023081100)
* Improvement: Move buttons, pagination, rowcount to top of table.
* Bugfix: Missing cache definitions.

## Version 1.6.6 (2023070500)
* Bugfix: Default sortorder on tableload now applying.
* Bugfix: Keep sortorder on change of sortcolumn.

## Version 1.6.5 (2023062900)
* New feature: Fulltext search in specific column using column:value (or "Column one":"value set" etc.) in searchfield.
* Bugfix: Applying URL search also for lazy load table.
* Bugfix: Display timespan filter only if records contain values in column of filter.
* Bugfix: Infinitescroll triggered at the bottom of the table.


## Version 1.6.4 (2023061600)
* Improvement: Behat test - adjust scenarios to support hidden by default filter panel on the "course" and "Infinite scroll"  tabs.
* Improvement: GitHub Code Checker: fix linting.

## Version 1.6.3 (2023061500)
* New feature: Possibility to hide filter buttons on initial load (filteronloadinactive).
* New feature: Displaying action buttons on top of table in case of infinite scroll.
* Bugfix: Infinitescroll now working on lazyout table.
* Bugfix: Datefilter query (Bug in 1.6.2.).

## Version 1.6.2 (2023060900)
* Bugfix: Fix bugs regarding URL searchParams on page which uses filter.

## Version 1.6.1 (2023060801)
* Bugfix: Fix bugs regarding labels for modals

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