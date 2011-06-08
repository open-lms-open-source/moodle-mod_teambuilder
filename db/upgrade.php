<?php  //$Id: upgrade.php,v 1.2 2007/08/08 22:36:54 stronk7 Exp $

// This file keeps track of upgrades to
// the teambuilder module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_teambuilder_upgrade($oldversion=0) {

    global $CFG, $DB;

    $dbman = $DB->get_manager();

/// And upgrade begins here. For each one, you'll need one
/// block of code similar to the next one. Please, delete
/// this comment lines once this file start handling proper
/// upgrade code.

/// if ($result && $oldversion < YYYYMMDD00) { //New version in version.php
///     $result = result of "/lib/ddllib.php" function calls
/// }

/// Lines below (this included)  MUST BE DELETED once you get the first version
/// of your module ready to be installed. They are here only
/// for demonstrative purposes and to show how the teambuilder
/// iself has been upgraded.

/// For each upgrade block, the file teambuilder/version.php
/// needs to be updated . Such change allows Moodle to know
/// that this file has to be processed.

/// To know more about how to write correct DB upgrade scripts it's
/// highly recommended to read information available at:
///   http://docs.moodle.org/en/Development:XMLDB_Documentation
/// and to play with the XMLDB Editor (in the admin menu) and its
/// PHP generation posibilities.

/// First example, some fields were added to the module on 20070400
    if ($oldversion < 2007040100) {

    /// Define field course to be added to teambuilder
        $table = new xmldb_table('teambuilder');
        $field = new xmldb_field('course');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'id');
    /// Launch add field course
        $dbman->add_field($table, $field);

    /// Define field intro to be added to teambuilder
        $table = new xmldb_table('teambuilder');
        $field = new xmldb_field('intro');
        $field->set_attributes(XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'name');
    /// Launch add field intro
        $dbman->add_field($table, $field);

    /// Define field introformat to be added to teambuilder
        $table = new xmldb_table('teambuilder');
        $field = new xmldb_field('introformat');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'intro');
    /// Launch add field introformat
        $dbman->add_field($table, $field);

        upgrade_mod_savepoint(true, 2007040100, 'teambuilder');
    }

/// Second example, some hours later, the same day 20070401
/// two more fields and one index were added (note the increment
/// "01" in the last two digits of the version
    if ($oldversion < 2007040101) {

    /// Define field timecreated to be added to teambuilder
        $table = new xmldb_table('teambuilder');
        $field = new xmldb_field('timecreated');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'introformat');
    /// Launch add field timecreated
        $dbman->add_field($table, $field);

    /// Define field timemodified to be added to teambuilder
        $table = new xmldb_table('teambuilder');
        $field = new xmldb_field('timemodified');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'timecreated');
    /// Launch add field timemodified
        $dbman->add_field($table, $field);

    /// Define index course (not unique) to be added to teambuilder
        $table = new xmldb_table('teambuilder');
        $index = new xmldb_index('course');
        $index->set_attributes(XMLDB_INDEX_NOTUNIQUE, array('course'));
    /// Launch add index course
        $dbman->add_index($table, $index);

        upgrade_mod_savepoint(true, 2007040101, 'teambuilder');
    }

/// Third example, the next day, 20070402 (with the trailing 00), some inserts were performed, related with the module
    if ($oldversion < 2007040200) {
    /// Add some actions to get them properly displayed in the logs
        $rec = new stdClass;
        $rec->module = 'teambuilder';
        $rec->action = 'add';
        $rec->mtable = 'teambuilder';
        $rec->filed  = 'name';
    /// Insert the add action in log_display
        $DB->insert_record('log_display', $rec);
    /// Now the update action
        $rec->action = 'update';
        $DB->insert_record('log_display', $rec);
    /// Now the view action
        $rec->action = 'view';
        $DB->insert_record('log_display', $rec);

        upgrade_mod_savepoint(true, 2007040200, 'teambuilder');
    }

    if ($oldversion < 2011051702) {
        $table = new xmldb_table('teambuilder');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'intro');

        // Launch add field introformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2011051702, 'teambuilder');
    }

/// And that's all. Please, examine and understand the 3 example blocks above. Also
/// it's interesting to look how other modules are using this script. Remember that
/// the basic idea is to have "blocks" of code (each one being executed only once,
/// when the module version (version.php) is updated.

/// Lines above (this included) MUST BE DELETED once you get the first version of
/// yout module working. Each time you need to modify something in the module (DB
/// related, you'll raise the version and add one upgrade block here.

/// Final return of upgrade result (true/false) to Moodle. Must be
/// always the last line in the script
    return true;
}

?>
