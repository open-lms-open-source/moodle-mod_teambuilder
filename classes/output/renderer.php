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
 * Teambuilder activity renderer
 *
 * @package    block_poll
 * @copyright  2017 Adam Olley <adam.olley@blackboard.com>
 * @copyright  2017 Blackboard Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_teambuilder\output;
defined('MOODLE_INTERNAL') || die;

use html_writer;
use moodle_url;
use plugin_renderer_base;
use tabobject;

/**
 * Teambuilder activity renderer
 *
 * @copyright  2017 Adam Olley <adam.olley@blackboard.com>
 * @copyright  2017 Blackboard Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    public function navigation_tabs($id, $selected = 'build') {
        $questionnaireurl = new moodle_url('/mod/teambuilder/view.php', ['f' => 1, 'id' => $id]);
        $previewurl = new moodle_url('/mod/teambuilder/view.php', ['preview' => 1, 'id' => $id]);
        $buildurl = new moodle_url('/mod/teambuilder/build.php', ['id' => $id]);
        $tabs = [
            new tabobject("questionnaire", $questionnaireurl, get_string('questionnaire', 'teambuilder')),
            new tabobject("preview", $previewurl, get_string('preview', 'teambuilder')),
            new tabobject("build", $buildurl, get_string('buildteams', 'teambuilder')),
        ];
        return print_tabs([$tabs], $selected, null, null, true);
    }
}