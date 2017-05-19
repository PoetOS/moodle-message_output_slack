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
 * Unit tests.
 *
 * @package message_slack
 * @author  Mike Churchward
 * @copyright 2017 onwards Mike Churchward (mike.churchward@poetgroup.org)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use message_slack\manager;

/**
 * Tests for manager class
 * @package message_slack
 * @author  Mike Churchward
 * @copyright 2017 onwards Mike Churchward (mike.churchward@poetgroup.org)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group message_slack
 */
class message_slack_manager_testcase extends advanced_testcase {

    public function test_slackify_message() {
        $slackmanager = new message_slack\manager();

        // Verify links are rewritten correctly.
        $message1 = 'Test link - <a href="http://somelink.com/somelink.html" attr="otherattr">Some link text</a>';
        $expected1 = 'Test link - <http://somelink.com/somelink.html|Some link text>';
        $output = $slackmanager->slackify_message($message1);
        $this->assertEquals($expected1, $output);

        // Verify <br>, <p> and <div> tags are rewritten to '\n' line breaks.
        $message2 = 'First line<br>Second line<br /><p>Third line</p><div>Fourth line</div>';
        $expected2 = "First line\nSecond line\n\nThird line\nFourth line";
        $output = $slackmanager->slackify_message($message2);
        $this->assertEquals($expected2, $output);

        // Verify double quotes are escaped with backslashes.
        $message3 = 'Single quote - \'. Double quote - ".';
        $expected3 = 'Single quote - \'. Double quote - \\".';
        $output = $slackmanager->slackify_message($message3);
        $this->assertEquals($expected3, $output);

        // Verify any other tags are removed completely.
        $message4 = '<span>Some text</span>. Some <b>other</b> text.';
        $expected4 = 'Some text. Some other text.';
        $output = $slackmanager->slackify_message($message4);
        $this->assertEquals($expected4, $output);

        // Verify it all together, just in case.
        $output = $slackmanager->slackify_message($message1.$message2.$message3.$message4);
        $this->assertEquals($expected1.$expected2.$expected3.$expected4, $output);
    }
}