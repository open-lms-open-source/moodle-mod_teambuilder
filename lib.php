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
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $teambuilder An object from the form in mod_form.php
 * @return int The id of the newly inserted teambuilder record
 */
function teambuilder_add_instance($teambuilder) {
    global $DB;
    return $DB->insert_record('teambuilder', $teambuilder);
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $teambuilder An object from the form in mod_form.php
 * @return boolean Success/Fail
 */
function teambuilder_update_instance($teambuilder) {
    global $DB;
    $teambuilder->timemodified = time();
    $teambuilder->id = $teambuilder->instance;

    if (isset($teambuilder->opendt)) {
        $teambuilder->open = $teambuilder->opendt;
    }
    if (!isset($teambuilder->allowupdate)) {
        $teambuilder->allowupdate = 0;
    }

    return $DB->update_record('teambuilder', $teambuilder);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function teambuilder_delete_instance($id) {
    global $DB;

    if (!$teambuilder = $DB->get_record('teambuilder', array('id' => $id))) {
        return false;
    }

    $result = true;

    if (!$DB->delete_records('teambuilder', array('id' => $teambuilder->id))) {
        $result = false;
    }

    return $result;
}

function teambuilder_user_outline($course, $user, $mod, $teambuilder) {
    return false;
}

function teambuilder_user_complete($course, $user, $mod, $teambuilder) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in teambuilder activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 * @todo Finish documenting this function
 */
function teambuilder_print_recent_activity($course, $isteacher, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of teambuilder. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $teambuilderid ID of an instance of this module
 * @return mixed boolean/array of students
 */
function teambuilder_get_participants($teambuilderid) {
    return false;
}

/**
 * This function returns if a scale is being used by one teambuilder
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $teambuilderid ID of an instance of this module
 * @return mixed
 */
function teambuilder_scale_used($teambuilderid, $scaleid) {
    return false;
}

/**
 * Checks if scale is being used by any instance of teambuilder.
 * This function was added in 1.9
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any teambuilder
 */
function teambuilder_scale_used_anywhere($scaleid) {
    return false;
}


function teambuilder_get_questions($id,$userid = null) {
    global $DB;
    if ($questions = $DB->get_records("teambuilder_question", array("builder" => $id), "ordinal ASC")) {
        foreach($questions as &$q)
        {
            $q->answers = $DB->get_records("teambuilder_answer", array("question" => $q->id), "ordinal ASC");
            if($userid)
            {
                foreach($q->answers as &$a)
                {
                    if($DB->get_record("teambuilder_response", array("userid" => $userid, "answerid" => $a->id))) {
                        $a->selected = true;
                    } else {
                        $a->selected = false;
                    }
                }
            }
        }
    }

    return $questions;
}

/**
 * Get responses for a particular team builder questionnaire.
 *
 * @param int $id Team Builder id
 * @return An array of student ids => array of answers they selected
 * @author Morgan Harris
 */
function teambuilder_get_responses($id, $student = null) {
    global $CFG, $DB;
    $teambuilder = $DB->get_record("teambuilder", array("id" => $id));
    if($student == null) {
        if($teambuilder->groupid) {
            $students = groups_get_members($teambuilder->groupid,"u.id");
        } else {
            $ctxt = context_course::instance($teambuilder->course);
            $students = get_users_by_capability($ctxt, 'mod/teambuilder:respond', 'u.id');
        }
        $responses = array();
        foreach($students as $s) {
            $responses[$s->id] = teambuilder_get_responses($id,$s->id);
        }
        return $responses;
    }
    $sql = "SELECT answerid
            FROM {teambuilder}_response
            WHERE userid = :userid AND answerid IN (
                SELECT id FROM {teambuilder_answer}
                WHERE question IN (
                    SELECT id FROM {teambuilder_question}
                    WHERE builder = :builder
                )
            )";

    $params = array('userid' => $student, 'builder' => $id);
    $rslt = $DB->get_records_sql($sql, $params);
    $ret = false;
    if(!empty($rslt)) {
        $ret = array_keys($rslt);
    }
    return $ret;
}

function teambuilder_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}
