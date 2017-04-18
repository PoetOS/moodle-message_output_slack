This plugin experiments with providing a Moodle message provider plugin for Slack.
It requires an Incoming Webhook setup in the Slack Team to be used (see https://api.slack.com/incoming-webhooks).

To set up an incoming WebHook in your Slack...
    https://[yourteam].slack.com/apps/A0F7XDUAZ-incoming-webhooks

Each user will also need to add their Slack username from the team site to their notification preferences.

*** This is an ALPHA release, and is not intended to be used in any production environment ***

If you wish to contribute in any way, message me on github or to mike.churchward@poetgroup.org.


-----

A better way to do this might be by Slack button... https://api.slack.com/docs/slack-button
It does put the message in the user's private area.
You configure a button, and then add the client_id and client_secret to the Moodle config.
Then each user uses the button to configure their specific instance. The button generates a specific URL for each user.
I need to figure out how to limit the choice of channel to just the user.
