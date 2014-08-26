<?php // $Id: index.php,v 1.7.2.3 2009/08/31 22:00:00 mudrd8mz Exp $

/**
 * This page lists all the instances of teambuilder in a particular course
 *
 * @author  Your Name <your@email.address>
 * @version $Id: index.php,v 1.7.2.3 2009/08/31 22:00:00 mudrd8mz Exp $
 * @package mod/teambuilder
 */

/// Replace teambuilder with the name of your module and remove this line

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);   // course

if (! $course = $DB->get_record('course', array('id' => $id))) {
    print_error('Course ID is incorrect');
}

require_course_login($course);

$params = array(
    'context' => context_course::instance($course->id)
);
$event = \mod_teambuilder\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();


/// Get all required stringsteambuilder

$strteambuilders = get_string('modulenameplural', 'teambuilder');
$strteambuilder  = get_string('modulename', 'teambuilder');


/// Print the header

$PAGE->requires->css('/mod/teambuilder/styles.css');
$PAGE->set_url('/mod/teambuilder/index.php', array('id'=>$id));
$PAGE->set_pagelayout('incourse');
$PAGE->navbar->add($strteambuilders);
$PAGE->set_title($strteambuilders);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

/// Get all the appropriate data

if (! $teambuilders = get_all_instances_in_course('teambuilder', $course)) {
    notice('There are no instances of teambuilder', "../../course/view.php?id=$course->id");
    die;
}

/// Print the list of instances (your module will probably extend this)

$timenow  = time();
$strname  = get_string('name');
$strweek  = get_string('week');
$strtopic = get_string('topic');

if ($course->format == 'weeks') {
    $table->head  = array ($strweek, $strname);
    $table->align = array ('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ('center', 'left', 'left', 'left');
} else {
    $table->head  = array ($strname);
    $table->align = array ('left', 'left', 'left');
}

foreach ($teambuilders as $teambuilder) {
    if (!$teambuilder->visible) {
        //Show dimmed if the mod is hidden
        $link = '<a class="dimmed" href="view.php?id='.$teambuilder->coursemodule.'">'.format_string($teambuilder->name).'</a>';
    } else {
        //Show normal if the mod is visible
        $link = '<a href="view.php?id='.$teambuilder->coursemodule.'">'.format_string($teambuilder->name).'</a>';
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array ($teambuilder->section, $link);
    } else {
        $table->data[] = array ($link);
    }
}

print_heading($strteambuilders);
print_table($table);

/// Finish the page

print_footer($course);
echo $OUTPUT->footer();

?>
