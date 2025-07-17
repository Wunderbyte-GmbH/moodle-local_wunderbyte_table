# Wunderbyte Table #
Wunderbyte Table can be used instead of the table_sql class and then supports all the actions via Ajax.

This local plugin was developed to allow the use of the table_sql class within modals and tabs.
There is no special configuration required, BUT with special configuration, your table will be much more powerful.
After the installation of the local_wunderbyte_table, you can create an
extended class to wunderbyte_table (instead of table_sql).

    class booking_table extends wunderbyte_table {}

If your extended table class contains another class (eg myplugin_class), make sure you can instantiate it with only the cmid. Errors will be thrown if this is not possible.

From 1.1.1 on, wunderbyte_table is loaded only once the corresponding div and none of it's parent is hidden (display:none) and it will add a visbility listener on the next hidden parent element. Unhiding will trigger loading of the table.

The included demo.php is only meant to demonstrate the working of the table.

That's all it takes. Switching pages, sorting, hiding columns and downloading will now run via ajax.

## Security
Wunderbyte Table uses webservices to support features like ajax reload, infinite scroll, action buttons etc. As it is a generic project it can't know about the security requirements for the projects it actually is used in.
We use Wunderbyte Table in combination with shortcodes, where we can't know where in Moodle the table actually will show up and sometimes, this will be on a location without any login requirements. Therefore, the key requirelogin, which is set normally to "true" can also be set to false:

    $table->requirelogin = false;

We also provide the capability 'local/wunderbyte_table:canaccess' which normally is set to 'allow' for the archetype 'user'. This capability check is only required when requirelogin is set to true. Changing this will result in a change for all wunderbyte table on the moodle instance.

If only a single table should be protected, you can use the requirecapability key on a specific table to require special capabilities. When this key is not set, it falls back to 'local/wunderbyte_table:canaccess'.

    $table->requirecapability = 'local/myplugin:canaccess';

Methods that should be called via the actionbutton functionality (webservice execute_action) must include the action_ prefix ie "action_deleterow". While the transmitted methodname is "deleterow", the actual method which will be called via the webservice is action_deleterow. This prevents any other method to be called than a method defined for this purpose.

## Multiple custom templates
Wunderbyte Table comes with a responsive table template, but it can and should be overriden in your project. To do so, set in your instance of your extended class (eg $yourtable) the template to your proper template, like

    $yourtable->tabletemplate = 'mod_yourtemplate/yourtable'

where yourtable corresponds to yourtable.mustache in your own project.

With Version 1.2.1 Wunderbyte Table supports multiple templates in one project. The structure has to be like this:
- A yourfirsttable_container.mustache includes possible search, filter and sort components
- This container also contains yourfirsttable.mustache
- This table mustache contains yourfirsttable_row.mustache.
Only by obeing this structure in your own project, the reloading triggered by filter, search etc. will work.

## Caching
One new feature is caching. Wunderbyte_table will automatically pass every sql call to the MODE_APPLICATION cache with the key being the hashed request.

A request for page one will be cached with a different key than a request for page 2 etc.

Invalidation of the cache is being done by

    cache_helper::purge_by_event('changesinwunderbytetable');

If you don't run this every time your table changes, you won't see the changes in your DB reflected in the
output of wunderbyte table, unless you otherwise purge the cache.

If you use more than one table in your plugin or if there is a possibility that more than one
Plugin uses local_wunderbyte_table on your system, you should provide your own cache definitons
in your plugin. Use the define_cache('mod_myplugin', 'mycachename') function to set your own caches.

## JavaScript
This description is only relevant in one case: If you override a value from your table via the col_mycolumn function in the wunderbyte_table class and you use a mustache template and renderer to echo html (eg. to render a button or a modal etc.) AND if this mustache template includes javascript, then you will encounter the problem, that the JS won't be automatically included.
The reason is that the js would be added to the page footer via the renderer, but it is simply skipped in this particular usecase. Therefore, you need to add the JS instead of your column template to the table template.
Any JS which is on this labe (corresponding to table.mustache in the wunderbyte_table project), will be executed after the table is correctly rendered.
You have to make sure to write your js in a way that your can find the necessary variables (eg. the ids of your rows) without being able to pass them directly via the mustache template.

## Sticky Header, Infinite Scroll & Pagination
If you want to enable scrolling within your table, you can set the stickyheader property to true.
With infinite scroll, your table will automatically reload additional rows once you scrolled to its bottom. To enable infinite scroll, you can define the number of rows that will be loaded - first on init and later with each reload - in the infinitescroll variable. Sticky Header and Infinite Scroll can be combined.
If you don't use infinite scroll, pagination options will be displayed at the bottom of your table.


## Action buttons
You can add a number of action buttons to your table. If you combine them with "$yourtable->addcheckboxes = true", you will be able to select single lines and execute your function with it. The methods will need to be implemented in your child class of wunderbyte table and they will be called via ajax. Example:

    $mytable->addcheckboxes = true;
    $mytable->actionbuttons[] = [
        'label' => get_string('deleterow', 'mod_myproject'), // Name of your action button.
        'methodname' => 'deleterow', // The method needs to be added to your child of wunderbyte_table class including "action_" prefix ie action_deleterow.
        'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
            'id' => 'id'
        ]
    ];

You can choose if your Action Button needs element(s) to be selected in order to trigger an action.
If this is set to false, you can trigger actions with or without actions selected.

    $mytable->actionbuttons[] = [
        'selectionmandatory' => false,
    ]
You can choose if your Action Button triggers a modal with the "nomodal" param:

    $mytable->actionbuttons[] = [
        'nomodal' => false,
    ]

If your Action Button works with or without selected elements, you can choose a different bodystring for the modal it may trigger.

    $mytable->actionbuttons[] = [
    'data' => [
                    'id' => 'id',
                    'titlestring' => 'deletedatatitle', // Will be shown in modal title
                    'bodystring' => 'deletedatabody', // Will be shown in modal body in case elements are selected
                    'labelcolumn' => 'firstname', // The Labelcolumn is important because it will be picked for human verification in the modal.
                    'noselectionbodystring' => 'specialbody', // Will be displayed in modal bode in case no data is selected
                    'submitbuttonstring' => 'deletedatasubmit', // Modal Button String
                    'component' => 'local_wunderbyte_table', // Localization of strings
                ]
    ]

You can choose if your Action Button transmits a single or multiple call(s) in case multiple elements are selected.

    $mytable->actionbuttons[] = [
        'id' => -1, // This forces single call execution.
    ]
If you want your Action Button to trigger a form instead of a simple modal, hand over namespace and title.

    $mytable->actionbuttons[] = [
            'formname' => 'local_myplugin\\form\\edit_mytableentry', // To include a dynamic form to open and edit entry in modal.
            'data' => [
                'title' => get_string('title'), // Localized title to be displayed as title in dynamic form (formname). Default is "title".
                'submitbuttonstring' => get_string('send'), // Localized title to be displayed in submit button in dynamic form (formname). Default is "Save changes".
            ]
    ]

You can also use actionbuttons in a column to treat the corresponding record.

    public function col_action($values) { // Button will be added to action column.

        $data[] = [
            'label' => get_string('delete', 'core'), // Name of your action button.
            'class' => 'btn btn-danger', // Use bootstrap 4.
            'href' => '#', // You can either use the link, or JS, or both.
            'iclass' => 'fa fa-cog', // Add an icon before the label.
            'arialabel' => 'cogwheel', // Add an aria-label string to your icon.
            'title' => 'Edit', // Will be displayed when hovered over icon.
            'id' => $values->id.'-'.$this->uniqueid, // Access the data of the record.
            'name' => $this->uniqueid.'-'.$values->id,
            'methodname' => 'deleteitem', // The method needs to be added to your child of wunderbyte_table class including "action_" prefix ie action_deleterow.
            'nomodal' => true,
            'data' => [ // Will be added eg as data-id = $values->id, so values can be transmitted to the method above.
                'id' => $values->id,
            ]
        ];

## Filter, Sort and Search
WB Table provides direct filter, search and sort functionality.

    $standardfilter = new standardfilter('username', get_string('username', 'my_plugin'));
    $table->add_filter($standardfilter);

    $table->define_fulltextsearchcolumns(['username', 'firstname', 'lastname']);
    $table->define_sortablecolumns(['id', 'username', 'firstname', 'lastname']);

In order for them to work, you must obey some rules: Filter basically just add another 'where' to the sql. The column name must therefor be like the filtercolumn. If the "WHERE columnname like '%myname%'" doesn't work, because you would need to write "WHERE s1.columnname like '%myname%'", then the filter will not work. You would need to wrap your SQL so to eliminate the need for the columnname prefix.

Filter, search and sort params selection triggers URL update. These params can also be applied to table on load via URL. These functions are not available if more than one table is displayed per page (multitable display).

Important: both methods must be supplied with a complete array of columns which have to be used for filtering / sorting on each call!

Note: Human-readable labels are supported for sortable columns only:
    $table->define_sortablecolumns(['id' => 'ID', 'username' => 'Userame', 'firstname' => 'First Name']);

## Filter

As for the filter, you have these further functionalities:
- localize labels and results
- sort possible results in the filter panel (eg to have weekdays in order)

Here is an example how to set this up:
By the way: 'id' will aways be obmitted, as it is not a useful filter in any case.

    $standardfilter = new standardfilter('sport', get_string('sport', 'my_plugin'));
    $table->add_filter($standardfilter);

    // This filter works, when you have a column with english weekdays.
    // They will be localized to the users language.
    $weekdays = new weekdays('weekday', get_string('weekday', 'my_plugin'));
    $table->add_filter($weekdays);

### Sort filter values and localize them
When having eg three values in your dataset, one, two, three, and you want them to appear always in the right order in your filter, you can use $standardfilter->add_options(['one', 'two', 'three']) to achieve that result.

If you only have 0 for inactive and 1 for active, you can use

    $standardfilter->add_options([
        0 => get_string('inactive', 'myplugin'),
        1 => get_string('active', 'myplugin'),
    ]);

### Suppressing values in a filter
The filter function can also suppress given values. This is useful if you have a filter with a lot of values, but you only want to show a few of them. The other values will be hidden in the filter panel.

You can define the suppression like this.

    $standardfilter->add_options([
        0 => 'wbt_suppress',
        1 => get_string('active', 'myplugin'),
    ]);

### Filter position
By default, filters will be shown at the left of the table. If you want to show them on top of the table, you can do it like this:

    $table->showfilterontop = true;

### Hierarchical filter
The hierarchical filter will allow to order your results in a special way. Assume you have the following values: one, two, three, four, five, six in your database. They belong to three different categories. You can then add the filter like this:

    $hierarchicalfilter = new hierarchicalfilter('x', get_string('x', 'my_plugin'));
    $hierarchicalfilter->add_options([
        'one' => [
            'localizedname' => get_string('one', 'myplugin'),
            'parent' => get_string('category A'),
        ],
        'two' => [
            'localizedname' => get_string('two', 'myplugin'),
            'parent' => get_string('category B'),
        ],
        'three' => [
            'localizedname' => get_string('three', 'myplugin'),
            'parent' => get_string('category A'),
        ],
        ...,
        'other' => [
            'localizedname' => get_string('other', 'myplugin'),
            'parent' => get_string('category Other'),
        ]
    ]);
    $table->add_filter($hierarchicalfilter);

The hierarchical filter will just add a (only one!) layer for the defined values. With 'other', we have implemented a catch function which will take care of all values you didn't define. In our example above, if suddenly we find seven and eight in the DB, they would be added the the "Other" Category. To rename this category, just add it with the key "other" and your own localized string.

For columns that contain date and time values (as Unix timestamp) you can enable a datepicker to allow users to filter the values:

    $datepicker = new datepicker('enddate', get_string('enddate'));
    // For the datepicker, we need to add special options.
    $datepicker->add_options(
        'standard',
        '<',
        get_string('apply_filter', 'local_wunderbyte_table'),
        'now',
    );
    $table->add_filter($datepicker);

### Datepicker filter
A special type of datepicker filter is the timespan filter which will take the input of two date- and timepickers and apply to two columnvalues of a record. This enables comparison of two timespans. Possible operations are 'within', 'overlapboth', 'overlapstart', 'overlapend', 'before', 'after' and 'flexoverlap'.
"Overlapstart" filter will only display records with starttime before and ending within the timespan of the filter, "within" filter will display records starting after and ending before the values of the filter timespan. "Flexoverlap" will include all kinds of overlaping: overlapping the beginning, the end, both sides or within.
The possibleoperations array is containing a whitelist, if none specified, all are applied.

    $datepicker = new datepicker(
            'startdate',
            get_string('timespan', 'local_wunderbyte_table'),
            'enddate'
        );
    // For the datepicker, we need to add special options.
    $datepicker->add_options(
        'in between',
        '<',
        get_string('apply_filter', 'local_wunderbyte_table'),
        '1680130800',
        'now'
    );
    $table->add_filter($datepicker);

By default filters are displayed next to the table and can be hidden with the filterbutton. If you want them to be hidden on load, set filteronloadinactive = true in the instance of your table.

The Hourslist filter can extract full hours from a unix timestamp and thus only show results which e.g. happened at a certain time. One example would be in mod_booking to show only courses which start at a given time (like 10:00 - 11:00).

This only works with mariadb, mysql and postgres db.

Example of a filter json for this:

    $hourslistfilter = new hourlist('timemodified', get_string('hourlastmodified', 'local_wunderbyte_table'));
    $table->add_filter($hourslistfilter);

### Intrange Filter
The intrange filter takes the values of a column, filters the given integers (> 0) and allows to display records from within a range.
Values entered in "From"-field work the minium value, values entered in "To"-field will be maximum.
All characters different than 0-9 are ignored.

        $intrangefilter = new intrange('coursenumber', get_string('filterforcoursenumber', 'my_plugin'));
        $table->add_filter($intrangefilter);

### Exploding strings for columns storing multiple values
The filter function also supports columns with multiple values stored as string with a separator.

You can define the separator like this:

    $standardfilter = new standardfilter('mycolname', get_string('mystring', 'mod_myplugin'));
    $standardfilter->add_options(
        [
            'explode' => ',', // In this example, a comma is the separator, you might need another one.
        ]
    );
    $table->add_filter($standardfilter);

### Handle JSON objects
The filter function also supports columns storing one or multiple JSON objects.
You can define the attribute of the JSON object which should be used for the filter:

    $standardfilter = new standardfilter('mycolname', get_string('mystring', 'mod_myplugin'));
    $standardfilter->add_options(
        [
            'jsonattribute' => 'name', // Replace 'name' with the actual attribute name.
        ]
    );
    $table->add_filter($standardfilter);

## Sorting

If the output template you want to use doesn't support clickable headers to sort (eg because you use cards), you might want to use the sort select. Just add

    $table->cardsort = true;

to your wunderbyte_table classs (look in the _container templates to understand how this works).

Each column defined as sortable will display carets in the table header. They have the same functionality as sortcolumn select & changesortorder element displayed in top of table.

If you want to define default sorting on table load, set:

    $table->sort_default_column = 'columnname';
    $table->sort_default_order = SORT_ASC; // Or SORT_DESC.

## Search

The fulltext search is triggerd when more than 3 characters are typed into the searchfield. Fulltext search is checking values from all columns defined in $table->define_fulltextsearchcolumns(["column1", "column2"]).
If you want to look for values in a specific column, use columnname:searchterm. This can be applied for numerous queries and combined with regular fulltext search. Combinations are seperated via whitespace and/or comma. If you want to use values containing whitespaces, use double (or single) quotes ie. "Localized Column":"value including whitespace". Searchterms will be used like wildcards, while quoted values and numbers trigger exact search (no wildcard).
If the "Enter" key is pressed, search will be toggled immediately, no matter how many characters are set.

## Reordering

WB Table has implemented the javascript and most of the php to support reordering out of the box. To turn this on, just use this command:

    $table->sortablerows = true;

This will add a column with drag n drop handles. But you will need to add a method "action_reorderrows" to actually do the reordering to your wb table child class. For obvious reasons, this can't be done generically.
You'll find a non functional template for this method in your wunderbyte_table.php in the classes folder.

## Download

If your table supports download, you can activate the download button like this:

    $table->showdownloadbutton = true;

By default, the download button will be shown at the top of the table.
If you want to show it at the bottom of the table, you can use this:

    $table->showdownloadbuttonatbottom = true;

## Allow users to add their own filters

You can allow users with the capabiltiy 'local/wunderbyte_table:canedittable'
to set individual filters for their table from the user interface.
For this to work, the global setting 'allowedittable' needs to be turned on.

You can add the "add filter" button like this:

    $table->showaddfilterbutton = true;

### Display

If you want to display multiple tables on one page, tabs can be enabled in templates.

For the display of localized names in tableheaders, use the define_headers function.

    $columns = [
                'id' => get_string('id', 'local_wunderbyte_table'),
                'username' => get_string('username'),
                'firstname' => get_string('firstname'),
                'lastname' => get_string('lastname'),
                'email' => get_string('email'),
                'action' => get_string('action'),
            ];
    $table->define_headers(array_values($columns));
    $table->define_columns(array_keys($columns));

## Lazy loading vs. direct out
To lazy load wunderbyte table (eg. for loading in tabs or modals) you need to call $table->lazyout() instead of $table->out. While out will return the html to echo, lazyout echos right away. If you want the html of lazyout, use $table->lazyouthtml();

## Sortable classes
The sortable classes give you the possibility to
- Add SQL Code which is only needed when a given sorting is applied
- Use a different cache for this sorting

Example for a sortable:
    $standardsortable = new standardsortable(
        'freeplaces',
        get_string('freeplaces', 'local_musi')
    );
    $select = '(SELECT COALESCE(NULLIF(s1.maxanswers, 0), 999999) - COUNT(ba.id)
                FROM {booking_answers} ba
                WHERE ba.optionid = s1.id AND ba.waitinglist < 3) AS freeplaces';
    $from = '';
    $where = '';
    $standardsortable->define_sql($select, $from, $where);

    $standardsortable->define_cache('mod_booking', 'bookedusertable');
    $table->add_sortable($standardsortable);


## Installing via uploaded ZIP file ##
1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##
The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/local/wunderbyte_table}

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##
2023 Wunderbyte GmbH <info@wunderbyte.at>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
