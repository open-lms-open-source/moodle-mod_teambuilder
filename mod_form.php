<?php //$Id: mod_form.php,v 1.2.2.3 2009/03/19 12:23:11 mudrd8mz Exp $

/**
 * This file defines the main teambuilder configuration form
 * It uses the standard core Moodle (>1.8) formslib. For
 * more info about them, please visit:
 *
 * http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * The form must provide support for, at least these fields:
 *   - name: text element of 64cc max
 *
 * Also, it's usual to use these fields:
 *   - intro: one htmlarea element to describe the activity
 *            (will be showed in the list of activities of
 *             teambuilder type (index.php) and in the header
 *             of the teambuilder main page (view.php).
 *   - introformat: The format used to write the contents
 *             of the intro field. It automatically defaults
 *             to HTML when the htmleditor is used and can be
 *             manually selected if the htmleditor is not used
 *             (standard formats are: MOODLE, HTML, PLAIN, MARKDOWN)
 *             See lib/weblib.php Constants and the format_text()
 *             function for more info
 */

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/lib/grouplib.php');

class mod_teambuilder_mod_form extends moodleform_mod {

    function definition() {

        global $COURSE;
        $mform =& $this->_form;

//-------------------------------------------------------------------------------
    /// Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

    /// Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('name', 'teambuilder'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

    /// Adding the required "intro" field to hold the description of the instance
        $this->add_intro_editor(false, get_string('intro', 'teambuilder'));
        $mform->addHelpButton('introeditor', 'intro', 'teambuilder');

//-------------------------------------------------------------------------------
    /// Adding the rest of teambuilder settings, spreeading all them into this fieldset
    /// or adding more fieldsets ('header' elements) if needed for better logic
		
		$groups = groups_get_all_groups($COURSE->id);
		$options[0] = 'All Students';
		foreach($groups as $group)
			$options[$group->id] = $group->name;
        $mform->addElement('select', 'groupid', 'Group', $options);

		$mform->addElement('date_time_selector', 'open', 'Open Date');
		$mform->addElement('static','openInfo','','You will not be able to modify your questionnaire after this date.');
		$mform->addElement('date_time_selector', 'close', 'Close Date');
		$mform->addElement('checkbox', 'allowUpdate', 'Allow updating of answers');

//-------------------------------------------------------------------------------
        // add standard elements, common to all modules
		$features = new stdClass;
        $features->groups = false;
        $features->groupings = true;
        $features->groupmembersonly = true;
        $this->standard_coursemodule_elements($features);
//-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();

    }

	function definition_after_data()
	{
		parent::definition_after_data();
		
		$mform =& $this->_form;
		if($id = $mform->getElementValue('update'))
		{
			$dta = $mform->getElementValue('open');
			$dt = mktime($dta['hour'][0], $dta['minute'][0], 0, $dta['month'][0], $dta['day'][0], $dta['year'][0]);
			if($dt < time())
			{
				$el = $mform->createElement('static','openlabel','Open',date("D d/m/Y H:i",$dt));
				$mform->insertElementBefore($el,'open');
				$mform->removeElement('open');
				$mform->addElement('hidden','opendt',$dt);
			}
		}
	}
}

?>
