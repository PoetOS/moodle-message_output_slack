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
     * Constructor to add needed properties to the Slack app.
     */
    public function __construct() {
        $this->slackmanager = new message_slack\manager();
    }

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
        if (empty($this->slackmanager->config('webhookurl'))) {
            return true;
        }

        $message = !empty($eventdata->fullmessagehtml) ? $eventdata->fullmessagehtml : $eventdata->fullmessage;

        return $this->slackmanager->send_message($message, $eventdata->userto->id);
    }

    /**
     * Creates necessary fields in the messaging config form.
     *
     * @param array $preferences An object of user preferences
     */
    public function config_form($preferences) {
        global $USER;
        if (!$this->is_system_configured()) {
            return get_string('notconfigured', 'message_slack');
        } else {
            return $this->slackmanager->config_form($preferences, $USER->id);
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
     * @param object $preferences preferences object
     * @param int $userid the user id
     */
    public function load_data(&$preferences, $userid) {
        $preferences->slack_slackusername = get_user_preferences('message_processor_slack_slackusername', '', $userid);
        $preferences->slack_configuration_url = get_user_preferences('message_processor_slack_configuration_url', '', $userid);
    }

    /**
     * Tests whether the Slack settings have been configured
     * @return boolean true if Slack is configured
     */
    public function is_system_configured() {
        return !empty($this->slackmanager->config('webhookurl'));
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
        return $this->slackmanager->is_user_configured($user->id);
    }
}