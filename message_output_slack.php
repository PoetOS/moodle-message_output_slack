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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/message/output/lib.php');

/**
 * The slack message processor
 *
 * @package   message_slack
 * @copyright  2017 onwards Mike Churchward (mike.churchward@poetgroup.org)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class message_output_slack extends message_output {

    /**
     * Processes the message and sends a notification via slack
     *
     * @param stdClass $eventdata the event data submitted by the message sender plus $eventdata->savedmessageid
     * @return true if ok, false if error
     */
    public function send_message($eventdata) {
        global $CFG;

        // Skip any messaging of suspended and deleted users.
        if (($eventdata->userto->auth === 'nologin') || $eventdata->userto->suspended || $eventdata->userto->deleted) {
            return true;
        }

        if (!empty($CFG->noemailever)) {
            // Hidden setting for development sites, set in config.php if needed.
            debugging('$CFG->noemailever is active, no slack message sent.', DEBUG_MINIMAL);
            return true;
        }

        // If no slack URL congigured, skip.
        if (empty($webhookurl = get_config('message_slack', 'webhookurl'))) {
            return true;
        }

        // If user doesn't have a slack username, skip.
        if (empty($channelname = get_user_preferences( 'message_processor_slack_slackusername', '', $eventdata->userto->id))) {
            return true;
        }

        $slackmanager = new message_slack\manager();
        $message = $slackmanager->slackify_message($eventdata->fullmessagehtml);

        $curl = new curl();

        $username = get_config('message_slack', 'botname');
        $username = !empty($username) ? '"username": "'.$username.'", ' : '';

        $curl->post($webhookurl, ['payload' => '{"channel": "'.$channelname.'", '.$username.'"text": "'.$message.'"}']);

        return true;
    }

    /**
     * Creates necessary fields in the messaging config form.
     *
     * @param array $preferences An array of user preferences
     */
    public function config_form($preferences) {
        if (!$this->is_system_configured()) {
            return get_string('notconfigured', 'message_slack');
        } else {
            return get_string('slackusername', 'message_slack').': <input size="30" name="slack_slackusername" value="' .
                s($preferences->slack_slackusername).'" />';
        }
    }

    /**
     * Parses the submitted form data and saves it into preferences array.
     *
     * @param stdClass $form preferences form class
     * @param array $preferences preferences array
     */
    public function process_form($form, &$preferences) {
        if (isset($form->slack_slackusername) && !empty($form->slack_slackusername)) {
            // Put the @ sign at the beginning of the username.
            if (substr($form->slack_slackusername, 0, 1) != '@') {
                $form->slack_slackusername = '@' . $form->slack_slackusername;
            }
            $preferences['message_processor_slack_slackusername'] = $form->slack_slackusername;
        }
    }

    /**
     * Loads the config data from database to put on the form during initial form display
     *
     * @param array $preferences preferences array
     * @param int $userid the user id
     */
    public function load_data(&$preferences, $userid) {
        $preferences->slack_slackusername = get_user_preferences( 'message_processor_slack_slackusername', '', $userid);
    }

    /**
     * Tests whether the Slack settings have been configured
     * @return boolean true if Slack is configured
     */
    public function is_system_configured() {
        return !empty(get_config('message_slack', 'webhookurl'));
    }

    /**
     * Tests whether the Slack settings have been configured on user level
     * @param  object $user the user object, defaults to $USER.
     * @return bool has the user made all the necessary settings
     * in their profile to allow this plugin to be used.
     */
    public function is_user_configured($user = null) {
        global $USER;

        if ($user === null) {
            $user = $USER;
        }
        return !empty(get_user_preferences('message_processor_slack_slackusername', null, $user->id));
    }
}