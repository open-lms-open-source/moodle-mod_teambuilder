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

require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/lib.php');

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->js("/mod/teambuilder/js/json2.js");
$PAGE->requires->css('/mod/teambuilder/styles.css');

$id = optional_param('id', 0, PARAM_INT); // The course_module ID, or...
$a  = optional_param('a', 0, PARAM_INT);  // Teambuilder instance ID.
$preview = optional_param('preview', 0, PARAM_INT);
$action = optional_param('action', null, PARAM_TEXT);

if ($id) {
    list ($course, $cm) = get_course_and_cm_from_cmid($id, 'teambuilder');
    $teambuilder = $DB->get_record('teambuilder', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    if (!$teambuilder = $DB->get_record('teambuilder', array('id' => $a), '*', MUST_EXIST)) {
        print_error('You must specify a course_module ID or an instance ID');
    }
    list ($course, $cm) = get_course_and_cm_from_instance($teambuilder, 'teambuilder');
    $id = $cm->id;
}

require_login($course, true, $cm);

$ctxt = context_module::instance($cm->id);

$params = array(
    'context' => $ctxt,
    'objectid' => $teambuilder->id
);
$event = \mod_teambuilder\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('teambuilder', $teambuilder);
$event->trigger();

// Check out if we've got any submitted data.

if ($action == "submit-questionnaire") {
    $questions = teambuilder_get_questions($teambuilder->id, $USER->id);
    if (has_capability('mod/teambuilder:respond', $ctxt)) {
        foreach ($questions as $q) {
            if ($q->type === 'one') {
                $response = optional_param('question-'.$q->id, 0, PARAM_RAW);
            } else {
                $response = optional_param_array('question-'.$q->id, 0, PARAM_RAW);
            }
            // Delete all their old answers.
            foreach ($q->answers as $a) {
                if ($a->selected) {
                    $DB->delete_records("teambuilder_response", array("userid" => $USER->id, "answerid" => $a->id));
                }
            }
            // Now insert their new answers.
            if (is_array($response)) {
                foreach ($response as $r) {
                    $record = new stdClass();
                    $record->userid = $USER->id;
                    $record->answerid = $r;
                    $DB->insert_record("teambuilder_response", $record);
                }
            } else {
                $record = new stdClass();
                $record->userid = $USER->id;
                $record->answerid = $response;
                $DB->insert_record("teambuilder_response", $record);
            }
        }
        $feedback = "Your answers were submitted.";
    }
}

$mode = 'student';

if (has_capability('mod/teambuilder:create', $ctxt)) {
    if ($preview) {
        $mode = 'preview';
        $PAGE->requires->js("/mod/teambuilder/js/view.js");
    } else {
        $mode = 'teacher';
        $PAGE->requires->js("/mod/teambuilder/js/editview.js");
    }
} else {
    require_capability('mod/teambuilder:respond', $ctxt);
    $mode = 'student';
    $PAGE->requires->js("/mod/teambuilder/js/view.js");
}

if (($mode == 'teacher') && ($teambuilder->open < time()) && !isset($_GET['f'])) {
    redirect(new moodle_url('/mod/teambuilder/build.php', ['id' => $id]));
}

$PAGE->set_url('/mod/teambuilder/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($teambuilder->name));
$PAGE->set_heading($course->fullname);
$PAGE->set_cm($cm);
$PAGE->set_context($ctxt);
$output = $PAGE->get_renderer('mod_teambuilder');
echo $output->header();

// First things first: if it's not open, don't show it to students.

if (($mode == "student") && $teambuilder->groupid && !groups_is_member($teambuilder->groupid)) {
    echo '<div class="ui-widget" style="text-align:center;">';
    echo '<div style="display:inline-block; padding-left:10px; padding-right:10px;" class="ui-state-highlight ui-corner-all">';
    echo '<p>'.get_string('noneedtocomplete', 'mod_teambuilder').'</p>';
    echo '</div></div>';
} else if (($mode == "student") && (($teambuilder->open > time()) || $teambuilder->close < time())) {
    echo '<div class="ui-widget" style="text-align:center;">';
    echo '<div style="display:inline-block; padding-left:10px; padding-right:10px;" class="ui-state-highlight ui-corner-all">';
    echo '<p>'.get_string('notopen', 'mod_teambuilder').'</p>';
    echo '</div></div>';
} else {
    if ($mode == 'teacher') {
        // Before we start - import the questions.
        $import = optional_param('import', 0, PARAM_INT);
        if ($import) {
            $questions = teambuilder_get_questions($import);
            foreach ($questions as $q) {
                unset($q->id);
                $q->builder = $teambuilder->id;
                $newid = $DB->insert_record('teambuilder_question', $q);
                foreach ($q->answers as $a) {
                    unset($a->id);
                    $a->question = $newid;
                    $DB->insert_record('teambuilder_answer', $a);
                }
            }
        }

        echo $output->navigation_tabs($id, "questionnaire");

        if ($teambuilder->open < time()) {
                echo '<div class="ui-widget" style="text-align:center;">';
                $style = "display:inline-block; padding-left:10px; padding-right:10px;";
                echo '<div style="'.$style.'" class="ui-state-highlight ui-corner-all">';
                echo '<p>'.get_string('noeditingafteropentime', 'mod_teambuilder').'</p>';
                echo '</div></div>';
                echo '<script type="text/javascript">var interaction_disabled = true;</script>';
        }

        // Set up initial questions.
        $questions = teambuilder_get_questions($teambuilder->id);
        echo '<script type="text/javascript"> var init_questions = ' . json_encode($questions) . '</script>';

        echo '<div id="questions">';
        foreach ($questions as $q) {
            echo <<<HTML
<div class="question" id="question-{$q->id}"><table>
<tr>
    <td rowspan="2" class="handle">&nbsp;</td>
    <td><span class="questionText">$q->question</span> <span class="type">$q->type</span></td>
    <td class="edit">
        <a onclick="deleteQuestion(this)">Delete</a>
    </td>
</tr>
<tr>
    <td class="answers" colspan="2"><ul>
HTML;
            foreach ($q->answers as $a) {
                echo "<li>$a->answer</li>";
            }
            echo  '</ul></td></tr></table></div>';
        }

        echo '</div>';

        if ($teambuilder->open > time()) {

            // New question form.
            $onclick = "saveQuestionnaire('{$CFG->wwwroot}/mod/teambuilder/ajax.php', {$id})";
            echo '<div style="display:none;text-align:center;" id="savingIndicator"></div>';
            echo '<div style="text-align:center;"><button type="button" id="saveQuestionnaire" onclick="'.$onclick.'">';
            echo get_string('savequestionnaire', 'mod_teambuilder').'</button></div>';

            if (empty($questions)) {
                $otherbuilders = $DB->get_records('teambuilder', array('course' => $course->id));
                $strimportfrom = get_string('importquestionsfrom', 'mod_teambuilder');
                echo '<div style="text-align:center;margin:10px;font-weight:bold;" id="importContainer">';
                echo $strimportfrom.': <select id="importer">';
                foreach ($otherbuilders as $o) {
                    echo "<option value=\"$o->id\">$o->name</option>";
                }
                $strimport = get_string('import', 'mod_teambuilder');
                $stror = get_string('or', 'mod_teambuilder');
                echo '</select><button type="button" id="importButton">'.$strimport.'</button><br/>'.$stror.'</div>';
            }

            $straddanewquestion = get_string('addanewquestion', 'mod_teambuilder');
            $straddnewquestion = get_string('addnewquestion', 'mod_teambuilder');
            $strquestion = get_string('question');
            $stranswertype = get_string('answertype', 'mod_teambuilder');
            $stranswers = get_string('answers', 'mod_teambuilder');
            $strselectone = get_string('selectone', 'mod_teambuilder');
            $strselectany = get_string('selectany', 'mod_teambuilder');
            $strselectatleastone = get_string('selectatleastone', 'mod_teambuilder');
            echo <<<HTML
<div style="text-align:center;font-weight:bold;margin:10px;">$straddanewquestion</div>
<div style="text-align:center;">
<div id="newQuestionForm">
    <table>
        <tr>
            <th scope="row">$strquestion</th>
            <td><input name="question" type="text" class="text" /></td>
        </tr>
        <tr>
            <th scope="row">$stranswertype</th>
            <td><select>
                <option value="one">$strselectone</option>
                <option value="any">$strselectany</option>
                <option value="atleastone">$strselectatleastone</option>
            </select></td>
        </tr>
        <tr>
            <th scope="row">$stranswers</th>
            <td id="answerSection"><input type="text" name="answers[]" class="text" /><br/>
                <button onclick="addNewAnswer();" type="button">+</button>
                <button onclick="removeLastAnswer();" type="button">-</button>
            </td>
        </tr>
        <tr>
            <td></td>
            <td><button id="addNewQuestion" type="button" onclick="addNewQuestion();">$straddnewquestion</button></td>
        </tr>
    </table>
</div>
</div>
HTML;
        }
    } else if (($mode == "preview") || ($mode == "student")) {
        $questions = teambuilder_get_questions($teambuilder->id, $USER->id);
        $responses = teambuilder_get_responses($teambuilder->id, $USER->id);

        if ($mode == "preview") {
            echo $output->navigation_tabs($id, "preview");
        }

        if (($mode == "student") && empty($feedback)) {
            if ($responses !== false && !$teambuilder->allowupdate) {
                $feedback = "You have already completed this questionnaire.";
            }
        }

        if (isset($feedback) && $feedback) {
            echo '<div class="ui-widget centered">';
            $style = 'display:inline-block; padding-left:10px; padding-right:10px;';
            echo '<div style="'.$style.'" class="ui-state-highlight ui-corner-all">';
            echo '<p>'.$feedback.'</p>';
            echo '</div></div>';
        }

        if (!empty($teambuilder->intro)) {
            echo '<div class="description">' . format_module_intro('teambuilder', $teambuilder, $cm->id) . '</div>';
        }

        if (!$responses || $teambuilder->allowupdate) {
            $preview = $mode == "preview" ? "&preview=1" : "";
            echo '<form onsubmit="return validateForm(this)" action="view.php?id='.$id.$preview.'" method="POST">';

            $displaytypes = [
                "one" => "Select <strong>one</strong> of the following:",
                "any" => "Select any (or none) of the following:",
                "atleastone" => "Select <strong>at least one</strong> of the following:",
            ];
            foreach ($questions as $q) {
                echo <<<HTML
<div class="question" id="question-{$q->id}"><table>
<tr>
    <td><span class="questionText">$q->question</span> <span class="type">{$displaytypes[$q->type]}</span></td>
</tr>
<tr>
    <td class="answers" colspan="2">
        <div style="visibility:hidden;">
HTML;
                foreach ($q->answers as $a) {
                    if ($q->type == "one") {
                        $type = "radio";
                        $name = '';
                    } else {
                        $type = "checkbox";
                        $name = "[]";
                    }
                    $class = $q->type == "atleastone" ? "atleastone" : "";
                    $inputarr = ['type' => $type, 'name' => "question-{$q->id}{$name}", 'value' => $a->id, 'class' => $class];
                    if ($a->selected) {
                        $inputarr['checked'] = 'checked';
                    }
                    $input = html_writer::empty_tag('input', $inputarr);
                    echo html_writer::label($input.$a->answer, null);
                }
                echo <<<HTML
        </div>
    </td>
</tr>
</table></div>
HTML;
            }

            echo <<<HTML
    <input type="hidden" name="action" value="submit-questionnaire" />
    <div style="text-align:center;"><input type="submit" value="Submit" /></div>
</form>
HTML;

        }
    }
}
echo $OUTPUT->footer();
