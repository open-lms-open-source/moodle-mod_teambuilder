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

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/lib/grouplib.php');

class mod_teambuilder_mod_form extends moodleform_mod {

    public function definition() {

        global $COURSE;
        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('name', 'teambuilder'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Adding the required "intro" field to hold the description of the instance.
        $this->add_intro_editor(false, get_string('intro', 'teambuilder'));
        $mform->addHelpButton('introeditor', 'intro', 'teambuilder');

        // Adding the rest of teambuilder settings, spreeading all them into this fieldset
        // or adding more fieldsets ('header' elements) if needed for better logic.

        $groups = groups_get_all_groups($COURSE->id);
        $options[0] = 'All Students';
        foreach($groups as $group)
            $options[$group->id] = $group->name;
        $mform->addElement('select', 'groupid', 'Group', $options);

        $mform->addElement('date_time_selector', 'open', 'Open Date');
        $mform->addElement('static','openInfo','','You will not be able to modify your questionnaire after this date.');
        $mform->addElement('date_time_selector', 'close', 'Close Date');
        $mform->addElement('checkbox', 'allowupdate', 'Allow updating of answers');

        // Add standard elements, common to all modules.
        $features = new stdClass;
        $features->groups = false;
        $features->groupings = true;
        $features->groupmembersonly = true;
        $this->standard_coursemodule_elements($features);

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();

    }

    public function definition_after_data() {
        parent::definition_after_data();

        $mform = $this->_form;
        if ($id = $mform->getElementValue('update')) {
            $dta = $mform->getElementValue('open');
            $dt = mktime($dta['hour'][0], $dta['minute'][0], 0, $dta['month'][0], $dta['day'][0], $dta['year'][0]);
            if ($dt < time()) {
                $el = $mform->createElement('static','openlabel','Open',date("D d/m/Y H:i",$dt));
                $mform->insertElementBefore($el,'open');
                $mform->removeElement('open');
                $mform->addElement('hidden','opendt',$dt);
            }
        }
    }
}
