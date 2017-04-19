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

    /**
     * Constructor. Loads all needed data.
     */
    public function __construct() {
        $this->config = get_config('message_slack');
    }

    /**
     * Send the message to Slack. If the user has a message_processor_slack_url set, then the Slack button OAuth process if being
     * used. If not, then there needs to be an incoming webhook URL set.
     * @param string $message The message contect to send to Slack.
     * @param int $userid The Moodle user id that is being sent to.
     */
    public function send_message($message, $userid) {
        // Figure out which webhook URL to use. If neither set, abort.
        if ($this->is_using_slackbutton()) {
            if (empty($webhookurl = get_user_preferences('message_processor_slack_url', '', $userid))) {
                return true;
            }
        } else if (empty($webhookurl = $this->slackmanager->config('webhookurl')) ||
            empty($channelname = get_user_preferences( 'message_processor_slack_slackusername', '', $userid))) {
            return true;
        }

        $message = $this->slackify_message($message);

        $curl = new \curl();

        if ($this->is_using_slackbutton()) {
            $payload = ['payload' => '{"text": "'.$message.'"}'];
        } else {
            $username = !empty($this->config('botname')) ? '"username": "'.$this->config('botname').'", ' : '';
            $payload = ['payload' => '{"channel": "'.$channelname.'", '.$username.'"text": "'.$message.'"}'];
        }

        $curl->post($webhookurl, $payload);

        return true;
    }

    /**
     * Function to filter content and return to slack's API requirements.
     * @param string $message Message to slackify.
     * @return string The slackified string.
     */
    public static function slackify_message($message) {
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

    /**
     * Return the requested configuration item or null. Should have been loaded in the constructor.
     * @param string $configitem The requested configuration item.
     * @return mixed The requested value or null.
     */
    public function config($configitem) {
        return isset($this->config->{$configitem}) ? $this->config->{$configitem} : null;
    }

    /**
     * Return the HTML for the user preferences form.
     * @param array $preferences An array of user preferences.
     * @param int $userid Moodle id of the user in question.
     * @return string The HTML for the form.
     */
    public function config_form ($preferences, $userid) {
        if ($this->is_using_slackbutton()) {
            return $this->user_config_slackbutton($preferences, $userid);
        } else {
            return $this->user_config_slackusername($preferences);
        }
    }

    /**
     * @return boolean true if Slack is using the configure user connection slack button.
     */
    public function is_using_slackbutton() {
        return ($this->config('useslackbutton') == 1);
    }

    /**
     * Returns true if the user has their Slack configuration needed for integration.
     * @param int $userid The Moodle id of the user to check.
     * @return boolean
     */
    public function is_user_configured($userid) {
        return (!$this->is_using_slackbutton() &&
                !empty(get_user_preferences('message_processor_slack_slackusername', null, $userid))) ||
               ($this->is_using_slackbutton() &&
                !empty(get_user_preferences('message_processor_slack_configuration_url', null, $userid)));
    }
    /**
     * Return the appropriate user configuration button code.
     * @param object $preferences An object of user preferences.
     * @param int $userid Moodle id of the user in question.
     * @return string The appropriate button code.
     */
    private function user_config_slackbutton($preferences, $userid) {
        global $CFG;

        $configurationurl = $preferences->slack_configuration_url;
        if (empty($configurationurl)) {
            // Need to add a 'redirect_uri' argument to this link. It will return to a script to complete the actions.
            $redirecturi = $this->redirect_uri();
            // Create a state variable that confirms that the same user that sent this, receives it.
            $state = $this->state_var($userid);
            $configbutton = get_string('connectslackaccount', 'message_slack') .
                '<a href="'.$this->config('slackbuttonurl').'&redirect_uri='.$redirecturi.'&state='.$state.'">' .
                '<img alt="Add to Slack" height="40" width="139" src="https://platform.slack-edge.com/img/add_to_slack.png" ' .
                'srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, ' .
                'https://platform.slack-edge.com/img/add_to_slack@2x.png 2x" /></a>';
        } else {
            $configbutton = get_string('manageslackaccount', 'message_slack') .
                '<a href="'.$configurationurl.'">' .
                '<img alt="Configure Slack" height="40" width="139" src="https://platform.slack-edge.com/img/add_to_slack.png" ' .
                'srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, ' .
                'https://platform.slack-edge.com/img/add_to_slack@2x.png 2x" /></a>';
        }

        return $configbutton;
    }

    /**
     * Return the appropriate HTML code for the Slack channel to post to for incoming webhook config.
     * @param object $preferences An object of user preferences.
     * @return string The appropriate button code.
     */
    private function user_config_slackusername($preferences) {
        return get_string('slackusername', 'message_slack').': <input size="30" name="slack_slackusername" value="' .
            s($preferences->slack_slackusername).'" />';
    }

    /**
     * Construct a state variable to use with the OAuth 2 API.
     * @param int $userid The id of the Moodle user.
     * @return string A constructed state variable for this user (Moodle's sesskey).
     */
    public function state_var($userid) {
        return sesskey();
    }

    /**
     * Return the redirect URI to handle the callback for OAuth.
     * @return string The URI.
     */
    public function redirect_uri() {
        global $CFG;

        return $CFG->wwwroot.'/message/output/slack/slackconnect.php';
    }
}