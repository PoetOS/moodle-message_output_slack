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

/**
 * Slack message plugin version information.
 *
 * @package message_slack
 * @author  Mike Churchward
 * @copyright  2017 onwards Mike Churchward (mike.churchward@poetgroup.org)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace message_slack;

defined('MOODLE_INTERNAL') || die();

/**
 * Slack helper manager class
 *
 * @author  Mike Churchward
 * @copyright  2017 onwards Mike Churchward (mike.churchward@poetgroup.org)
 */
class manager {

    public function __construct($message = '') {
        $this->message = $message;
    }

    /**
     * Function to filter content and return to slack's API requirements.
     * @param string $message Optional message to slackify.
     * @return string The slackified string.
     */
    public function slackify_message($message = '') {
        // Slack needs to escape quotes, move links into angle brackets, and use \n for line breaks.

        // Change linked text to Slack style with a placeholder for adding back at the end.
        $message = preg_replace('/<a href="(.*?)".*?>(.*?)<\/a>/', '<slack ${1}|${2}>', $message);

        // Change <br>, <div> and <p> to \n.
        $message = preg_replace(['/<br\s*\/?>/', '/<p.*?>/', '/<div.*?>/', '/<\/p>/', '/<\/div>/'], ['\n', '\n', '\n'], $message);

        // Add slashes to any remaining quote characters.
        $message = addcslashes($message, '\'"');

        // Clean any remaining tags except the ones we marked as placeholders.
        $message = strip_tags($message, '<slack>');

        // Finally, restore marked slack tags.
        $message = str_replace('<slack ', '<', $message);

        return $message;
    }
}
