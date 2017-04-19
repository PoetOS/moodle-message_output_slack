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
 * Slack user connection handler.
 *
 * @package message_slack
 * @author  Mike Churchward
 * @copyright  2017 onwards Mike Churchward (mike.churchward@poetgroup.org)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/lib/filelib.php');

$code = required_param('code', PARAM_TEXT);
// State is used to confirm the operation is genuine. We expect the Moodle sesskey to be in this variable.
$state = required_param('state', PARAM_TEXT);

$PAGE->set_url(new moodle_url('/message/output/slack/slackconnect.php'));
$PAGE->set_context(context_system::instance());

require_login();
confirm_sesskey($state);

$slackmanager = new message_slack\manager();


// This is handled above by confirm_sesskey.
if ($slackmanager->state_var($USER->id) != $state) {
    echo 'Error - unexpected state variable.';
}

if (empty($redirecturi = $slackmanager->redirect_uri())) {
    echo 'Error - no defined redirect_uri';
}
if (empty($clientid = $slackmanager->config('clientid'))) {
    echo 'Error - no defined client_id';
}
if (empty($clientsecret = $slackmanager->config('clientsecret'))) {
    echo 'Error - no defined client_secret';
}

$curl = new curl();

$args = ['client_id' => $clientid, 'client_secret' => $clientsecret, 'code' => $code, 'redirect_uri' => $redirecturi];
//$args = ['client_id' => '167842963185.169586326049', 'client_secret' => '44de929a6237ef8ed421f711b35f7246',
//         'code' => $code, 'redirect_uri' => $redirect_uri];
$response = json_decode($curl->get('https://slack.com/api/oauth.access', $args));

// Validate response data.
if (!$response->ok) {
    echo 'Error - invalid Oauth response.';
}

$slackuserid = get_user_preferences('message_processor_slack_user_id', '', $USER->id);
if (empty($slackuserid)) {
    set_user_preference('message_processor_slack_user_id', $response->user_id, $USER->id);
} else if ($slackuserid != $response->user_id) {
    echo 'Error - incorrect Slack user id returned.';
}

set_user_preferences(['message_processor_slack_access_token' => $response->access_token,
                      'message_processor_slack_channel' => $response->incoming_webhook->channel,
                      'message_processor_slack_channel_id' => $response->incoming_webhook->channel_id,
                      'message_processor_slack_configuration_url' => $response->incoming_webhook->configuration_url,
                      'message_processor_slack_url' => $response->incoming_webhook->url], $USER->id);

redirect($CFG->wwwroot.'/message/notificationpreferences.php?userid='.$USER->id);

/// Returns:
// $code: 167842963185.169572845409.77276c85be
/* $response:
    {"ok":true,
     "access_token":"xoxp-167842963185-169046927332-170233992628-229b1d9e56af86fac6b1319ceb97a621",
     "scope":"identify,incoming-webhook",
     "user_id":"U4Z1CT99S",
     "team_name":"POET",
     "team_id":"T4XQSUB5F",
     "incoming_webhook":
        {"channel":"@mikechurchward.ca",
         "channel_id":"D4ZLL6F2B",
         "configuration_url":"https:\/\/poetdev.slack.com\/services\/B506QFUEN",
         "url":"https:\/\/hooks.slack.com\/services\/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"
        }
    }

{"ok":true,
 "access_token":"xoxp-167842963185-169202062870-170336025509-dcce0d1e51f5e6d31d51e367718d49eb",
 "scope":"identify,incoming-webhook",
 "user_id":"U4Z5Y1URL",
 "team_name":"POET",
 "team_id":"T4XQSUB5F",
 "incoming_webhook":
    {"channel":"@mike",
     "channel_id":"D4XQSUW81",
     "configuration_url":"https:\/\/poetdev.slack.com\/services\/B50QMBSSD",
     "url":"https:\/\/hooks.slack.com\/services\/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"
    }
}
*/