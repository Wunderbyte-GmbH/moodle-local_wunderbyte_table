<?php
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

namespace local_wunderbyte_table\local\helper;

/**
 * actforuser class.
 *
 * @package    local_wunderbyte_table
 * @copyright  2025 Wunderbyte GmbH <info@wunderbyte.at>
 * @author     2025 Mahdi Poustini
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class actforuser {
    /**
     * Number of times in this request the id of another user was taken from the URL for a caller
     * that did not name the capabilities for acting for others. Such callers have to be changed.
     * @var int
     */
    public static int $unverifiedforeignids = 0;

    /**
     * This function checks the given $args to see whether a user ID is provided
     * either through the 'foruserid' argument or via an optional URL parameter
     * whose name is defined by the 'urlparamforuserid' argument. Otherwise,
     * it returns $defaultvalue.
     *
     * The id of another user from the URL comes from the visitor of the page. Callers name the
     * capabilities that allow acting for others in $capabilities: a list of capability names
     * (checked in the system context) and/or capability => context pairs. The id is then only
     * returned when the current user holds one of them; with an empty list it is never returned.
     * Callers that pass null are not checked here and have to do the check themselves; this
     * mode exists for existing callers only and will be removed.
     *
     * @param array $args Arguments passed to the shortcode.
     * @param int $defaultvalue The default value when no user ID is found.
     * @param array|null $capabilities Capabilities that allow acting for another user, null for no check.
     * @return int The user ID.
     */
    public static function get_foruserid(array $args, int $defaultvalue = 0, ?array $capabilities = null) {
        // Step 1: Check if the foruserid argument is set.
        // This step succeeds only when a valid integer is provided via the foruserid argument.
        $userid = self::get_userid_from_foruserid_arg($args);

        // Step 2: If step 1 returns 0, check whether foruserid can be retrieved from the optional params.
        // This step succeeds when a valid integer is provided via the optional params whose name
        // matches the value passed through the urlparamforuserid argument.
        if ($userid === 0) {
            $userid = self::get_userid_from_urlparamforuserid($args);
            if ($userid !== 0 && !self::may_use_userid_from_url($userid, $capabilities)) {
                $userid = 0;
            }
        }

        // Step 3: If $userid is still 0, return the $defaultvalue.
        if ($userid === 0) {
            $userid = $defaultvalue;
        }

        return $userid;
    }

    /**
     * Decides whether a user id that was taken from the URL may be used.
     *
     * @param int $userid
     * @param array|null $capabilities see get_foruserid()
     * @return bool
     */
    private static function may_use_userid_from_url(int $userid, ?array $capabilities): bool {
        global $USER;

        // Acting for oneself needs no permission.
        if ($userid === (int)($USER->id ?? 0)) {
            return true;
        }

        if ($capabilities === null) {
            // The caller did not say who may act for others. For now the id is passed on as before.
            self::$unverifiedforeignids++;
            self::notify_about_unverified_userid();
            return true;
        }

        foreach ($capabilities as $key => $value) {
            if ($value instanceof \context) {
                $capability = (string)$key;
                $context = $value;
            } else {
                $capability = (string)$value;
                $context = \context_system::instance();
            }
            if (has_capability($capability, $context)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Tell developers about callers that take the id of another user from the URL without naming
     * the capabilities for it.
     *
     * @return void
     */
    private static function notify_about_unverified_userid(): void {
        // Only on regular page requests: debugging output would break ajax answers and automated tests.
        if (
            (defined('PHPUNIT_TEST') && PHPUNIT_TEST)
            || defined('BEHAT_SITE_RUNNING')
            || (defined('AJAX_SCRIPT') && AJAX_SCRIPT)
            || (defined('WS_SERVER') && WS_SERVER)
            || (defined('CLI_SCRIPT') && CLI_SCRIPT)
        ) {
            return;
        }
        debugging(
            'actforuser::get_foruserid() returned the id of another user from the URL without a capability check. '
            . 'Pass the capabilities that allow acting for others as third argument.',
            DEBUG_DEVELOPER
        );
    }

    /**
     * Looks for the value of 'urlparamforuserid' if it is set.
     * Then attempts to get user ID from url params.
     *
     * The 'urlparamforuserid' argument can be used to specify which optional parameter
     * in the URL provides the user ID. This prevents relying on a fixed parameter name
     * for the property that holds the user ID.
     *
     * Example usage:
     *  [allbookingoptions urlparamforuserid=userid] → expects a URL containing a query parameter like: ?userid=123456
     *  [allbookingoptions urlparamforuserid=foruserid] → expects a URL containing a query parameter like: ?foruserid=123456
     *  [allbookingoptions urlparamforuserid=id] → expects a URL containing a query parameter like: ?id=123456
     *
     * @param array $args Arguments passed to the shortcode.
     * @return int User ID.
     */
    public static function get_userid_from_urlparamforuserid(array $args): int {
        // Look for urlparamforuserid in $args.
        if (isset($args['urlparamforuserid']) && is_string($args['urlparamforuserid'])) {
            $paramforuserid = $args['urlparamforuserid'];
            if ($paramforuserid) {
                $userid = optional_param($paramforuserid, 0, PARAM_INT);
                $userid = $userid > 0 ? $userid : 0;
                return (int) $userid;
            }
        }

        // FALLBACK: If argument 'urlparamforuserid' is not set, we still try to use 'userid' parameter from URL.
        if (!isset($args['urlparamforuserid'])) {
            $userid = optional_param('userid', 0, PARAM_INT);
            $userid = $userid > 0 ? $userid : 0;
            return (int) $userid;
        }

        return 0;
    }

    /**
     * Returns the value of 'foruserid' if it is set.
     *
     * The 'foruserid' argument can be used to specify the user ID.
     *
     * Example usage:
     *   [allbookingoptions foruserid=12345]
     *
     * @param array $args Arguments passed to the shortcode.
     * @return int The user ID.
     */
    public static function get_userid_from_foruserid_arg(array $args): int {
        if (isset($args['foruserid']) && is_numeric($args['foruserid'])) {
            return (int) $args['foruserid'];
        }
        return 0;
    }
}
