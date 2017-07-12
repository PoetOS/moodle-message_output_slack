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
require_once($CFG->dirroot.'/lib/filelib.php');

/**
 * Slack helper manager class
 *
 * @author  Mike Churchward
 * @copyright  2017 onwards Mike Churchward (mike.churchward@poetgroup.org)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /**
     * @var $validated Moodle can call is_user_configured repeatedly. Use this to cash the curl result.
     */
    private $validated = null;

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
        // Get the user's incoming webhook URL. If not configured, abort.
        if (empty($webhookurl = get_user_preferences('message_processor_slack_url', '', $userid))) {
                return true;
        }

        $message = $this->slackify_message($message);

        $curl = new \curl();

        $payload = ['payload' => '{"text": "'.$message.'"}'];

        $response = $curl->post($webhookurl, $payload);

        // Check if the incoming webhook has been removed by the user. If it has, the user will need to re-establish the connection.
        if ($response == 'No service') {
            $this->clear_user_connection($userid);
        }

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
        $message = preg_replace('/<a .*?href=["\'](.*?)["\'].*?>(.*?)<\/a>/', '<slack ${1}|${2}>', $message);

        // Change <br>, <div> and <p> to \n.
        $message = preg_replace(['/<br\s*?\/?>/', '/<p.*?>/', '/<div.*?>/', '/<\/p>/', '/<\/div>/'], ["\n", "\n", "\n"], $message);

        // Add slashes to any remaining quote characters.
        $message = addcslashes($message, '"');

        // Clean any remaining tags except the ones we marked as placeholders.
        $message = preg_replace('/<(?!slack).*?>/', '', $message);

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
        $configurationurl = $preferences->slack_configuration_url;
        if (empty($configurationurl)) {
            // Need to add a 'redirect_uri' argument to this link. It will return to a script to complete the actions.
            // Create a state variable that confirms that the same user that sent this, receives it.
            $buttonurl = 'https://slack.com/oauth/authorize?scope=incoming-webhook' .
                '&client_id='.$this->config('clientid') .
                '&redirect_uri='.$this->redirect_uri() .
                '&state='.$this->state_var($userid);
            $configbutton = get_string('connectslackaccount', 'message_slack', $this->config('teamurl')) .
                '<a href="'.$buttonurl.'">' .
                '<img alt="Add to Slack" height="40" width="139" src="https://platform.slack-edge.com/img/add_to_slack.png" ' .
                'srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, ' .
                'https://platform.slack-edge.com/img/add_to_slack@2x.png 2x" /></a>';
        } else {
            $configbutton = '<a href="'.$configurationurl.'">' .
                get_string('manageslackaccount', 'message_slack', $this->config('teamurl')) . '</a>';
        }

        return $configbutton;
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

    /**
     * Handle the token issuing steps of the Slack OAuth flow (see https://api.slack.com/docs/oauth).
     * The OAuth API call returns a JSON object like:
     *     {"ok":true,
     *      "access_token":"xoxp-167842963185-169046927332-170233992628-229b1d9e56af86fac6b1319ceb97a621",
     *      "scope":"identify,incoming-webhook",
     *      "user_id":"U4Z1CT99S",
     *      "team_name":"POET",
     *      "team_id":"T4XQSUB5F",
     *      "incoming_webhook":
     *          {"channel":"@mikechurchward.ca",
     *           "channel_id":"D4ZLL6F2B",
     *           "configuration_url":"https:\/\/poetdev.slack.com\/services\/B506QFUEN",
     *           "url":"https:\/\/hooks.slack.com\/services\/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"
     *          }
     *      }
     * @param string $code The temporary authorization code.
     * @param string $state The security identifier to verify the call was made from Moodle (sesskey).
     * @param int $userid The Moodle user ID.
     * @return boolean Success of the operation.
     */
    public function issue_token($code, $state, $userid) {

        // Handle any problems first.
        if ($this->state_var($userid) != $state) {
            debugging('Error - unexpected state variable received. Possible exploit.');
            return false;
        }
        if (empty($clientid = $this->config('clientid'))) {
            print_error('noclientid', 'message_slack');
            return false;
        }
        if (empty($clientsecret = $this->config('clientsecret'))) {
            print_error('noclientsecret', 'message_slack');
            return false;
        }

        $curl = new \curl();

        $args = ['client_id' => $clientid, 'client_secret' => $clientsecret, 'code' => $code,
            'redirect_uri' => $this->redirect_uri()];
        $response = json_decode($curl->get('https://slack.com/api/oauth.access', $args));

        // Validate response data.
        if (!$response->ok) {
            print_error('invalidoauthresponse', 'message_slack');
            return false;
        }

        $slackuserid = get_user_preferences('message_processor_slack_user_id', '', $userid);
        if (!empty($slackuserid) && ($slackuserid != $response->user_id)) {
            print_error('invalidslackuser', 'message_slack');
            return false;
        }

        $this->set_user_connection($userid, $response->user_id, $response->access_token, $response->incoming_webhook->channel,
            $response->incoming_webhook->channel_id, $response->incoming_webhook->configuration_url,
            $response->incoming_webhook->url);

        $this->validated = true;

        return true;
    }

    /**
     * Set the user connection data for a slack button incoming webhook.
     * @param int $userid The Moodle user ID to set data for.
     * @param string $slackuserid The Slack user ID.
     * @param string $accesstoken The Slack access token for this user's webhook.
     * @param string $channel The Slack channel name chosen by the user.
     * @param string $channelid The ID of the previous Slack channel.
     * @param string $configurationurl The URL to manage a connected Slack webhook.
     * @param string $url The webhook URL to use for this user's messages.
     */
    public function set_user_connection($userid, $slackuserid, $accesstoken, $channel, $channelid, $configurationurl, $url) {
        set_user_preferences(['message_processor_slack_user_id' => $slackuserid,
            'message_processor_slack_access_token' => $accesstoken,
            'message_processor_slack_channel' => $channel,
            'message_processor_slack_channel_id' => $channelid,
            'message_processor_slack_configuration_url' => $configurationurl,
            'message_processor_slack_url' => $url], $userid);
    }

    /**
     * Clear the user's slack connection information.
     * @param int $userid The Moodle user to clear the info for.
     */
    public function clear_user_connection($userid) {
        unset_user_preference('message_processor_slack_user_id', $userid);
        unset_user_preference('message_processor_slack_access_token', $userid);
        unset_user_preference('message_processor_slack_channel', $userid);
        unset_user_preference('message_processor_slack_channel_id', $userid);
        unset_user_preference('message_processor_slack_configuration_url', $userid);
        unset_user_preference('message_processor_slack_url', $userid);
    }

    /**
     * Validate a configured Slack user connection.
     * @param int $userid The Moodle userid.
     * @param boolean $force Force the validation check, regardless of the cached value.
     * @return boolean
     */
    public function validate_user_connection($userid, $force=false) {
        // If previously determined in this run, return the cached validated value.
        if (!$force && ($this->validated !== null)) {
            return $this->validated;
        }

        // If no token set, no valid connection.
        if (!($token = get_user_preferences('message_processor_slack_access_token', null, $userid))) {
            $this->validated = false;
            return $this->validated;
        }

        $curl = new \curl();
        $response = json_decode($curl->get('https://slack.com/api/auth.test', ['token' => $token]));
        if ($response->ok) {
            $this->validated = true;
            return $this->validated;
        } else if (($response->error == 'token_revoked') || ($response->error == 'invalid_auth')) {
            $this->clear_user_connection($userid);
            $this->validated = false;
            return $this->validated;
        }

        // Not sure why it failed, so return true (could be a bad internet connection?).
        return true;
    }
}