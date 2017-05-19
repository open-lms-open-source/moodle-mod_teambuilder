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
 * @copyright  2017 Blackboard Inc
 * @author     2017 Adam Olley <adam.olley@blackboard.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete teambuilder structure for backup, with file and id annotations
 */
class backup_teambuilder_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $teambuilder = new backup_nested_element('teambuilder', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'open', 'close', 'groupid', 'allowupdate',
        ));

        $questions = new backup_nested_element('questions');
        $question = new backup_nested_element('question', array('id'), array(
            'builder', 'question', 'type', 'display', 'ordinal',
        ));

        $answers = new backup_nested_element('answers');
        $answer = new backup_nested_element('answer', array('id'), array(
            'question', 'answer', 'ordinal',
        ));

        $responses = new backup_nested_element('responses');
        $response = new backup_nested_element('response', array('id'), array(
            'userid', 'answerid',
        ));

        // Build the tree.

        $teambuilder->add_child($questions);
        $questions->add_child($question);
        $question->add_child($answers);
        $answers->add_child($answer);
        $answer->add_child($responses);
        $responses->add_child($response);

        // Define sources.
        $teambuilder->set_source_table('teambuilder', array('id' => backup::VAR_ACTIVITYID));
        $question->set_source_table('teambuilder_question', array('builder' => backup::VAR_PARENTID));
        $answer->set_source_table('teambuilder_answer', array('question' => backup::VAR_PARENTID));

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $response->set_source_table('teambuilder_response', array('answerid' => backup::VAR_PARENTID));
        }

        $teambuilder->annotate_ids('group', 'groupid');
        $response->annotate_ids('user', 'userid');

        // Define file annotations.
        $teambuilder->annotate_files('mod_teambuilder', 'intro', null);

        // Return the root element (teambuilder), wrapped into standard activity structure.
        return $this->prepare_activity_structure($teambuilder);
    }
}
