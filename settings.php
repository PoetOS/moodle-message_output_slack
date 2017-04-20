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
 * Slack message plugin settings.
 *
 * @package message_slack
 * @author  Mike Churchward
 * @copyright  2017 onwards Mike Churchward (mike.churchward@poetgroup.org)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('message_slack/teamurl', get_string('teamurl', 'message_slack'),
        get_string('configteamurl', 'message_slack'), '', PARAM_TEXT));
    $settings->add(new admin_setting_configtext('message_slack/clientid', get_string('clientid', 'message_slack'),
        get_string('configclientid', 'message_slack'), '', PARAM_TEXT));
    $settings->add(new admin_setting_configtext('message_slack/clientsecret', get_string('clientsecret', 'message_slack'),
        get_string('configclientsecret', 'message_slack'), '', PARAM_TEXT));
}
