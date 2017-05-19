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
 * Controller for building a teambuilder.
 *
 * @package    mod_teambuilder
 * @copyright  UNSW
 * @author     UNSW
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->dirroot.'/group/lib.php');

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->js("/mod/teambuilder/js/json2.js");
$PAGE->requires->js("/mod/teambuilder/js/build.js");
$PAGE->requires->css('/mod/teambuilder/styles.css');

$id = optional_param('id', 0, PARAM_INT); // The course_module ID, or...
$a  = optional_param('a', 0, PARAM_INT);  // Teambuilder instance ID.
$preview = optional_param('preview', 0, PARAM_INT);
$action = optional_param('action', null, PARAM_TEXT);
$groupingid = optional_param('groupingID', 0, PARAM_INT);
$groupingname = trim(optional_param('groupingName', null, PARAM_TEXT));
$inheritgroupingname = optional_param('inheritGroupingName', 0, PARAM_INT);
$nogrouping = optional_param('nogrouping', 0, PARAM_INT);
if (!$nogrouping) {
    $nogrouping = empty($groupingid) && empty($groupingname);
}

$teams = optional_param_array('teams', array(), PARAM_RAW);
$teamnames = optional_param_array('teamnames', array(), PARAM_TEXT);

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
require_capability("mod/teambuilder:build", $ctxt);

$strteambuilders = get_string('modulenameplural', 'teambuilder');
$strteambuilder  = get_string('modulename', 'teambuilder');

$PAGE->navbar->add($strteambuilders);
$PAGE->set_url('/mod/teambuilder/build.php', array('id' => $cm->id));
$PAGE->set_cm($cm);
$PAGE->set_context($ctxt);
$PAGE->set_title($teambuilder->name);
$PAGE->set_heading($course->fullname);

$output = $PAGE->get_renderer('mod_teambuilder');
echo $output->header();

if (!is_null($action) && $action == "create-groups") {
    if (!$nogrouping) {
        if (strlen($groupingname) > 0) {
            $data = new stdClass();
            $data->courseid = $course->id;
            $data->name = $groupingname;
            $grouping = groups_create_grouping($data);
        } else {
            $grouping = groups_get_grouping($groupingid);
            $groupingname = $grouping->name;
            $grouping = $grouping->id;
        }
    }

    foreach ($teams as $k => $teamstr) {
        $name = $teamnames[$k];
        $team = explode(",", $teamstr);
        $oname = !$nogrouping && $inheritgroupingname ? "$groupingname $name" : $name;
        $groupdata = new stdClass();
        $groupdata->courseid = $course->id;
        $groupdata->name = $oname;
        $group = groups_create_group($groupdata);
        foreach ($team as $user) {
            if (!empty($user)) {
                groups_add_member($group, $user);
            }
        }
        if (!$nogrouping) {
            groups_assign_grouping($grouping, $group);
        }
    }

    $feedback = "Your groups were successfully created.";
} else {
    $group = '';
    if ($teambuilder->groupid) {
        $group = $teambuilder->groupid;
    }
    $students = get_enrolled_users($ctxt, 'mod/teambuilder:respond', $group, 'u.id,u.firstname,u.lastname', null, 0, 0, true);
    $responses = teambuilder_get_responses($teambuilder->id);
    $questions = teambuilder_get_questions($teambuilder->id);

    echo '<script type="text/javascript">';
    echo 'var students = ' . json_encode($students) . ';';
    echo 'var responses = ' . json_encode($responses) . ';';
    echo 'var questions = ' . json_encode($questions) . ';';
    echo '</script>';
}

echo $output->navigation_tabs($id, "build");

if (!empty($feedback)) {
    echo '<div class="ui-widget" style="text-align:center;">';
    echo '<div style="display:inline-block; padding-left:10px; padding-right:10px;" class="ui-state-highlight ui-corner-all">';
    echo '<p>'.$feedback.'</p>';
    echo '</div></div>';
} else {
    echo html_writer::div(null, '', ['id' => 'predicate']);

    $buttons = [
        html_writer::tag('button',
            get_string('addnewcriterion', 'mod_teambuilder'), ['type' => 'button', 'onclick' => 'addNewCriterion();']),
        html_writer::tag('button',
            html_writer::tag('strong', get_string('buildteams', 'mod_teambuilder')),
            ['type' => 'button', 'onclick' => 'buildTeams();']),
        html_writer::tag('button',
            get_string('resetteams', 'mod_teambuilder'), ['type' => 'button', 'onclick' => 'resetTeams();']),
    ];
    echo html_writer::div(implode('&nbsp;', $buttons), 'centered padded');

    $stepper = html_writer::span(2, 'stepper');
    echo html_writer::div(get_string('numberofteams', 'mod_teambuilder').': '.$stepper, 'centered padded');

    $options = [
        'numbers' => get_string('prioritizeequal', 'mod_teambuilder'),
        'criteria' => get_string('prioritizemostcriteria', 'mod_teambuilder'),
    ];
    $select = html_writer::select($options, null, '', null, ['id' => 'prioritise']);
    echo html_writer::div(get_string('prioritize', 'mod_teambuilder').': '.$select, 'centered');

    $unassignedheading = html_writer::tag('h2', get_string('unassignedtoteams', 'mod_teambuilder'));
    $unassignedbutton = html_writer::tag('button',
        get_string('assignrandomly', 'mod_teambuilder'), ['type' => 'button', 'onclick' => 'assignRandomly();']);
    echo html_writer::start_div('', ['id' => 'unassigned']);
    echo $unassignedheading.$unassignedbutton;
    echo html_writer::start_div('sortable');

    foreach ($students as $s) {
        $answeredstate = !isset($responses[$s->id]) || empty($responses[$s->id]) ? 'unanswered' : 'answered';
        echo "<div id=\"student-$s->id\" class=\"student ui-state-default $answeredstate\">$s->firstname&nbsp;$s->lastname</div>";
    }

    $groupings = "";
    foreach (groups_get_all_groupings($course->id) as $grping) {
        $groupings .= "<option value=\"$grping->id\">$grping->name</option>";
    }

    $strcreategroups = get_string('creategroups', 'mod_teambuilder');
    $strgroupingname = get_string('groupingname', 'group');
    $straddtogrouping = get_string('addtogrouping', 'mod_teambuilder');
    $strconfirmgroupbuilding = get_string('confirmgroupbuilding', 'mod_teambuilder');
    $strprefixteamnames = get_string('prefixteamnames', 'mod_teambuilder');
    $strdontassigngrouptogrouping = get_string('dontassigngrouptogrouping', 'mod_teambuilder');
    $strcancel = get_string('cancel');
    $strok = get_string('ok');

    echo <<<HTML
</div></div><div id="teams"></div>
<div style="text-align:center;margin:15px 50px 0px;border-top:1px solid black;padding-top:15px;">
    <button type="button" onclick="$('#createGroupsForm').slideDown(300);" class="creategroups">$strcreategroups</button>
    <div style="display:none" id="createGroupsForm"><p>$strconfirmgroupbuilding</p>
        <table style="margin:auto;">
            <tr>
                <th scope="row"><label for="groupingName">$strgroupingname</label></th>
                <td><input type="text" id="groupingName"></td>
            </tr>
            <tr><td colspan="2" style="text-align:center;font-size:0.8em">or...</td></tr>
            <tr>
                <th scope="row"><label for="groupingSelect">$straddtogrouping</label></th>
                <td><select id="groupingSelect">$groupings</select></td>
            </tr>
            <tr>
                <th scope="row"><label for="inheritGroupingName">$strprefixteamnames</label></th>
                <td style="text-align:left;">
                    <input type="checkbox" checked="checked" name="inheritGroupingName" id="inheritGroupingName" value="1" />
                </td>
            </tr>
            <tr><td colspan="2" style="text-align:center;font-size:0.8em">or...</td></tr>
            <tr>
                <th scope="row"><label for="nogrouping">$strdontassigngrouptogrouping</label></th>
                <td style="text-align:left;"><input type="checkbox" name="nogrouping" id="nogrouping" value="1" /></td>
            </tr>
        </table>
        <button type="button" onclick="$('#createGroupsForm').slideUp(300);">$strcancel</button>&nbsp
        <button type="button" onclick="createGroups();">$strok</button>
    </div>
</div>
<div id="debug"></div>
HTML;
}

echo $OUTPUT->footer();