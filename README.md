# Wunderbyte Table #

Wunderbyte Table can be used instead of the table_sql class and then supports all the actions via Ajax.

This local plugin was developed to allow the use of the table_sql class within modals and tabs.
There is no special configuration required. After the installation of the local_wunderbyte_table, you can create an
extended class to wunderbyte_table (instead of table_sql).

    class booking_table extends wunderbyte_table {}

That's all it takes. Switching pages, sorting, hiding columns and downloading will now run via ajax.

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

2021 Wunderbyte GmbH <info@wunderbyte.at>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
