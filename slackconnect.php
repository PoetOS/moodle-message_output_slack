<?php

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/lib/filelib.php');

$slackmanager = new message_slack\manager();
print_object($USER);

$code = required_param('code', PARAM_TEXT);
$state = required_param('state', PARAM_TEXT);

print_object($code);
print_object($state);

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
$response = $curl->get('https://slack.com/api/oauth.access', $args);
print_object($response);


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
*/