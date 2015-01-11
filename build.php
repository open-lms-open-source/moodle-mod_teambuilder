<?php  // $Id: view.php,v 1.6.2.3 2009/04/17 22:06:25 skodak Exp $

/**
 * This page prints a particular instance of teambuilder
 *
 * @author  Your Name <your@email.address>
 * @version $Id: view.php,v 1.6.2.3 2009/04/17 22:06:25 skodak Exp $
 * @package mod/teambuilder
 */

/// (Replace teambuilder with the name of your module and remove this line)

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->dirroot.'/group/lib.php');

$PAGE->requires->js("/mod/teambuilder/js/jquery.js");
$PAGE->requires->js("/mod/teambuilder/js/jquery.ui.js");
$PAGE->requires->js("/mod/teambuilder/js/json2.js");
$PAGE->requires->js("/mod/teambuilder/js/build.js");
$PAGE->requires->css('/mod/teambuilder/styles.css');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$a  = optional_param('a', 0, PARAM_INT);  // teambuilder instance ID
$preview = optional_param('preview', 0, PARAM_INT);
$action = optional_param('action', null, PARAM_TEXT);
$grouping_id = optional_param('groupingID', 0, PARAM_INT);
$grouping_name = trim(optional_param('groupingName', null, PARAM_TEXT));
$inherit_grouping_name = optional_param('inheritGroupingName', 0, PARAM_INT);
$nogrouping = optional_param('nogrouping', 0, PARAM_INT);

$teams = optional_param_array('teams', array(), PARAM_RAW);
$team_names = optional_param_array('teamnames', array(), PARAM_TEXT);

if ($id) {
    if (! $cm = get_coursemodule_from_id('teambuilder', $id)) {
        print_error('Course Module ID was incorrect');
    }

    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('Course is misconfigured');
    }

    if (! $teambuilder = $DB->get_record('teambuilder', array('id' => $cm->instance))) {
        print_error('Course module is incorrect');
    }

} else if ($a) {
    if (! $teambuilder = $DB->get_record('teambuilder', array('id' => $a))) {
        print_error('Course module is incorrect');
    }
    if (! $course = $DB->get_record('course', array('id' => $teambuilder->course))) {
        print_error('Course is misconfigured');
    }
    if (! $cm = get_coursemodule_from_instance('teambuilder', $teambuilder->id, $course->id)) {
        print_error('Course Module ID was incorrect');
    }

} else {
    print_error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$ctxt = context_module::instance($cm->id);
require_capability("mod/teambuilder:build",$ctxt);

$strteambuilders = get_string('modulenameplural', 'teambuilder');
$strteambuilder  = get_string('modulename', 'teambuilder');

$PAGE->navbar->add($strteambuilders);
$PAGE->set_url('/mod/teambuilder/build.php', array('id' => $cm->id));
$PAGE->set_cm($cm);
$PAGE->set_context($ctxt);
$PAGE->set_title($teambuilder->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_button($OUTPUT->update_module_button($cm->id, 'teambuilder'));
$PAGE->requires->css('/mod/teambuilder/css/custom-theme/jquery.ui.css');
echo $OUTPUT->header();

if(!is_null($action) && $action == "create-groups")
{
    if (!$nogrouping) {
        if (strlen($grouping_name) > 0) {
            $data = new stdClass();
            $data->courseid = $course->id;
            $data->name = $grouping_name;
            $grouping = groups_create_grouping($data);
        } else {
            $grouping = groups_get_grouping($grouping_id);
            $grouping_name = $grouping->name;
            $grouping = $grouping->id;
        }
    }

    foreach($teams as $k => $teamstr)
    {
		$name = $team_names[$k];
		$team = explode(",",$teamstr);
        $oname = !$nogrouping && $inherit_grouping_name ? "$grouping_name $name" : $name;
        $groupdata = new stdClass();
        $groupdata->courseid = $course->id;
        $groupdata->name = $oname;
        $group = groups_create_group($groupdata);
        foreach($team as $user)
        {
            groups_add_member($group,$user);
        }
        if (!$nogrouping) {
            groups_assign_grouping($grouping,$group);
        }
    }

    $feedback = "Your groups were successfully created.";
}
else
{
    if($teambuilder->groupid) {
        $group = $teambuilder->groupid;
    } else {
        $group = '';
    }
    $students = get_enrolled_users($ctxt, 'mod/teambuilder:respond', $group, 'u.id,u.firstname,u.lastname');
    $responses = teambuilder_get_responses($teambuilder->id);
    $questions = teambuilder_get_questions($teambuilder->id);

    echo '<script type="text/javascript">';
    echo 'var students = ' . json_encode($students) . ';';
    echo 'var responses = ' . json_encode($responses) . ';';
    echo 'var questions = ' . json_encode($questions) . ';';
    echo '</script>';
}

$tabs = array();
$tabs[] = new tabobject("questionnaire","view.php?f=1&id=$id",get_string('questionnaire','teambuilder'));
$tabs[] = new tabobject("preview","view.php?id=$id&preview=1",get_string('preview','teambuilder'));
$tabs[] = new tabobject("build","build.php?id=$id",get_string('buildteams','teambuilder'));
print_tabs(array($tabs), "build");

if(!empty($feedback)):
    echo '<div class="ui-widget" style="text-align:center;"><div style="display:inline-block; padding-left:10px; padding-right:10px;" class="ui-state-highlight ui-corner-all"><p>'.$feedback.'</p></div></div>';
else:

echo <<<HTML
<div id="predicate">
</div>
<div style="text-align:center;margin:10px;"><button type="button" onclick="addNewCriterion();">Add New Criterion</button>&nbsp;<button type="button" onclick="buildTeams();"><strong>Build Teams</strong></button>&nbsp;<button type="button" onclick="resetTeams();">Reset Teams</button></div>
<div style="text-align:center;margin:10px;">Number of teams: <span class="stepper">2</span></div>
<div style="text-align:center;">Prioritize: <select id="prioritise"><option value="numbers" selected="selected">equal team numbers</option><option value="criteria">most criteria met</option></select></div>
<div id="unassigned"><h2>Unassigned to teams</h2><button type="button" onclick="assignRandomly();">Assign Randomly</button><div class="sortable">
HTML;

foreach($students as $s)
{
    $answeredstate = !isset($responses[$s->id]) || empty($responses[$s->id]) ? 'unanswered' : 'answered';
    echo "<div id=\"student-$s->id\" class=\"student ui-state-default $answeredstate\">$s->firstname&nbsp;$s->lastname</div>";
}

$groupings = "";
foreach(groups_get_all_groupings($course->id) as $grping)
{
    $groupings .= "<option value=\"$grping->id\">$grping->name</option>";
}

echo <<<HTML
</div></div><div id="teams"></div>
<div style="text-align:center;margin:15px 50px 0px;border-top:1px solid black;padding-top:15px;">
    <button type="button" onclick="$('#createGroupsForm').slideDown(300);" style="font-size:1.5em;font-weight:bold;">Create Groups</button>
    <div style="display:none" id="createGroupsForm"><p>Are you sure you want to create your groups now? This action cannot be undone.</p>
        <table style="margin:auto;">
            <tr><th scope="row"><label for="groupingName">Grouping Name</label></th><td><input type="text" id="groupingName"></td></tr>
            <tr><td colspan="2" style="text-align:center;font-size:0.8em">or...</td></tr>
            <tr><th scope="row"><label for="groupingSelect">Add To Grouping</label></th><td><select id="groupingSelect">$groupings</select></td></tr>
            <tr><th scope="row"><label for="inheritGroupingName">Prefix Team Names with Grouping Name</label></th><td style="text-align:left;"><input type="checkbox" checked="checked" name="inheritGroupingName" id="inheritGroupingName" value="1" /></td></tr>
            <tr><td colspan="2" style="text-align:center;font-size:0.8em">or...</td></tr>
            <tr><th scope="row"><label for="nogrouping">Don't assign groups to a Grouping</label></th><td style="text-align:left;"><input type="checkbox" name="nogrouping" id="nogrouping" value="1" /></td></tr>
        </table>
        <button type="button" onclick="$('#createGroupsForm').slideUp(300);">Cancel</button>&nbsp;<button type="button" onclick="createGroups();">OK</button>
    </div>
</div>
<div id="debug"></div>
HTML;

endif; //if($feedback)

echo $OUTPUT->footer();
