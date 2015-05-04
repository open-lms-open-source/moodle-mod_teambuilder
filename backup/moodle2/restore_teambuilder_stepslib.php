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
 * @package    mod_teambuilder
 * @copyright  2015 NetSpot Pty Ltd
 * @author     Adam Olley <adam.olley@netspot.com.au>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the restore steps that will be used by the restore_teambuilder_activity_task
 */

/**
 * Structure step to restore one teambuilder activity
 */
class restore_teambuilder_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $teambuilder = new restore_path_element('teambuilder', '/activity/teambuilder');
        $paths[] = $teambuilder;

        $question = new restore_path_element('teambuilder_question', '/activity/teambuilder/questions/question');
        $paths[] = $question;

        $answer = new restore_path_element('teambuilder_answer', '/activity/teambuilder/questions/question/answers/answer');
        $paths[] = $answer;

        if ($userinfo) {
            $response = new restore_path_element('teambuilder_response',
                '/activity/teambuilder/questions/question/answers/answer/responses/response');
            $paths[] = $response;
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_teambuilder($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->groupid = $this->get_mappingid('group', $data->groupid);

        // Insert the teambuilder record.
        $newid = $DB->insert_record('teambuilder', $data);

        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newid);
    }

    protected function process_teambuilder_response($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->answerid = $this->get_new_parentid('teambuilder_answer');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $DB->insert_record('teambuilder_response', $data);
    }

    protected function process_teambuilder_question($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->builder = $this->get_new_parentid('teambuilder');
        $newquestionid = $DB->insert_record('teambuilder_question', $data);
        $this->set_mapping('teambuilder_question', $oldid, $newquestionid);
    }

    protected function process_teambuilder_answer($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->question = $this->get_new_parentid('teambuilder_question');
        $newanswerid = $DB->insert_record('teambuilder_answer', $data);
        $this->set_mapping('teambuilder_answer', $oldid, $newanswerid, true);
    }

    protected function after_execute() {
        global $DB;

        $this->add_related_files('mod_teambuilder', 'intro', null);
    }
}
