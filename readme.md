[![Build Status](https://travis-ci.org/mchurchward/moodle-message_output_slack.png?branch=MOODLE_BETA_32)](https://travis-ci.org/mchurchward/moodle-message_output_slack)

This plugin experiments with providing a Moodle message provider plugin for Slack.

It requires a Slack App be set up in the team, using https://api.slack.com/apps. Once created, the basic information screen for that
App will show the Client ID and the Client Secret required for the Moodle plugin configuration.

This is setup so that "only one" Slack Team can be set up at the site level. Anyone wishing to have a Slack notification setup
for their Moodle account must be a member of that team and use their account for that team.

The user connection is done via the Slack button, which uses the OAuth 2.0 protocol in Slack (https://api.slack.com/docs/oauth).

Full setup documentation for both users and admins is located here - https://docs.moodle.org/33/en/Slack_message_processor.

Possible Issues:
If the user logs into a different team Slack when trying to connect Moodle, they will receive the error:
"OAuth Error: invalid_team_for_non_distributed_app".
If the user is already logged into a different team Slack when trying to connect Moodle, they will receive the team login screen
for Slack until they do login to the correct Slack.
I am trying to determine if there is a way to "force" the login to the correct team.

-----
*** This is a BETA release, and is not intended to be used in any production environment ***

If you wish to contribute in any way, message me on github or to mike.churchward@poetgroup.org.

-----
Future thoughts:
- It may be possible to allow users to pick their own teams, but it would involve the same complex setup for each user as it is
for the site setup.
- It would be great if the current configuration forced the team set at the site level so that the user account connection is
simplified.
- It would also be great if the connection to Slack could use the Slack user's user name to set the channel rather than force the
user to pick one.