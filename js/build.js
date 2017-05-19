var maxwidth = 85; // Maximum width of a studentbox in px. initialised as the minimum possible width.
var initstate; // Initial state of unassigned box. used to restore when syncing between model/view.

var TWO_JOBS_PRIORITY = 1;
var MISSED_OUT_PRIORITY = 2;

// Teams are identified by their index in these arrays.
// Contains arrays of students assigned to that team.
var teamAssignments = [];
// Contains the names of teams.
var teamNames = [];
// Contains information for rendering students.
var selectedStudents = [];

var preventStudentClick = 0;

$(function() {
    $(".stepper").each(function() {
        var val = $(this).html();
        var buttons = $("<span><div class='ui-stepper-down'></div><span>" + val + "</span><div class='ui-stepper-up'></div></span>");
        buttons.find(".ui-stepper-up").click(function() {
            var x = parseInt(buttons.find("span").html());
            x++;
            buttons.find("span").html(x);
            updateTeams(x);
        });
        buttons.find(".ui-stepper-down").click(function() {
            var x = parseInt(buttons.find("span").html());
            x--;
            if (x < 1) {
                x = 1;
            }
            buttons.find("span").html(x);
            updateTeams(x);
        });
        $(this).empty();
        $(this).append(buttons);
    });

    $(".student").each(function() {
        if ($(this).width() > maxwidth) {
            maxwidth = $(this).width();
        }
    });

    $(".student").each(function() {
        $(this).width(maxwidth);
    });

    $(".student").on("mouseup", function(evt) {
        if (preventStudentClick) {
            preventStudentClick = false;
            return;
        }

        var details = $('<div class="studentResponse ui-corner-all"></div>');

        var studentID = /student-(\d+)/.exec($(this).attr("id"));
        var myResponses = responses[studentID[1]];

        if (myResponses) {
            var detailsTable = $('<table></table>');
            details.append(detailsTable);
            for(i in questions)
            {
                q = questions[i];
                qr = []; // Question responses.
                for(j in q.answers)
                {
                    a = q.answers[j];
                    if ($.inArray(parseInt(a.id),myResponses) != -1)
                    {
                        qr.push(a.answer);
                    }
                }
                var row = $('<tr><th scope="row">' + q.question + '</th><td>' + qr.join("<br/>") + '</td></tr>');
                detailsTable.append(row);
            }
        } else {
            details.html("This student has not responded.");
        }

        $(document.body).append(details);
        details.css("left",evt.pageX);
        details.css("top",evt.pageY);

        var mdevent = function(evt){
            if (evt.target != details.get(0)) {
                details.remove();
                $(document).unbind('mousedown',mdevent);
            }
        }
        $(document).mousedown(mdevent);

        var moto; // Mouseover timeout.

        var moevent = function(evt){
            stresponse = $(evt.target).closest(".studentResponse");
            if(evt.target != this && (stresponse.length == 0 || (stresponse.length > 0 && details.get(0) != stresponse.get(0))))
            {
                if (moto == undefined)
                {
                    moto = setTimeout(function(){
                        details.remove();
                    },500);
                }
            }
            else
            {
                if(moto) {
                    clearTimeout(moto);
                }
            }
        }
        $(document).mouseover(moevent);

    });

    $(".team > h2").on("dblclick", function(evt)
    {
        var teamHeader = $(evt.target);
        var teamName = teamHeader.html();
        var teamTextBox = $('<input type="text" value="' + teamName + '" />');
        teamTextBox.css('font-size',teamHeader.css('font-size'));
        teamTextBox.width(teamHeader.width());
        teamTextBox.height(teamHeader.height());
        teamTextBox.css('border-width','0px');

        function textBoxDone()
        {
            var teamHeader = $("<h2>" + teamTextBox.val() + "</h2>");
            teamHeader.width(teamTextBox.width());
            teamHeader.height(teamTextBox.height());
            teamTextBox.replaceWith(teamHeader);
            teamNames[teamHeader.parent().index()] = teamHeader.html();
        }

        // Conditionally attach the textBoxDone event if you click outside the textbox.
        var mdevent = function(evt){
            if(evt.target != teamTextBox.get(0))
            {
                textBoxDone();
                $(document).unbind('mousedown',mdevent);
            }
        }
        $(document).mousedown(mdevent);
        // If you press return.
        teamTextBox.keypress(function(evt){
            if(evt.keyCode == 13) // Return character.
            {
                textBoxDone();
                $(document).unbind('mousedown',mdevent);
            }
        });

        teamHeader.replaceWith(teamTextBox);
        teamTextBox.focus();
        teamTextBox.select();
    })

    initstate = $("#unassigned").html();

    updateTeams(2);

    addNewCriterion();

});


function updateTeams(numTeams) {

    synchroniseViewToModel();

    // Slice off the end of the teams array if needed.
    if(teamAssignments.length > numTeams)
    {
        teamAssignments = teamAssignments.slice(0,numTeams);
    }

    // Slice off the end of the names array if needed.
    if(teamNames.length > numTeams)
    {
        teamNames = teamNames.slice(0,numTeams);
    }
    else if(teamNames.length < numTeams)
    {
        for(i = teamNames.length - 1; i < numTeams; i++)
        {
            teamNames[i] = "Team " + (i + 1);
        }
    }
    synchroniseModelToView();

}

function resetTeams() {
    selectedStudents = [];
    for(i = 0; i < teamAssignments.length; i++)
    {
        teamAssignments[i] = [];
    }
    synchroniseModelToView();
    $("#debug").html("");
}

function updateRunningCounter(criterion) {
    var c = getCriterionObjectFromView(criterion);
    ctr = 0;
    for (i in students)
    {
        if (responses[i] === false)
        { // Don't count students with no response.
            continue;
        }
        if (studentMeetsCriterion(i, c))
        {
            ctr++;
        }
    }
    criterion.find(".runningCounter").html(ctr);
    if (ctr < teamNames.length) {
        criterion.find(".runningCounter").css("color", "#E9AAAA");
    } else {
        criterion.find(".runningCounter").css("color", "");
    }
}

function getResponsesForQuestion(questionID,responseContainer)
{
    q = questions[questionID];
    var ul = $("<ul></ul>");
    for(a in q.answers)
    {
        answer = q.answers[a];
        ul.append('<li><input type="checkbox" value="' + answer.id + '">' + answer.answer + '</input></li>');
    }

    responseContainer.closest(".criterionWrapper").find(".runningCounter").empty();
    responseContainer.empty().append(ul);

    responseContainer.closest(".criterionWrapper").find("ul input,select.oper").change(function(){
        updateRunningCounter($(this).closest(".criterionWrapper").children(".criterion"));
    });
}

function addNewCriterion() {
    if (!$("#predicate").length) {
        return;
    }
    var criterion = $(criterionHTML);

    // Insert the question data.
    // Questions is defined in the document (by PHP).
    for(var i in questions)
    {
        q = questions[i];
        criterion.find(".questions").append('<option value="' + q.id + '">' + q.question + '</option>');
    }

    // Add our behaviours.
    // Question select behaviour.
    criterion.find(".questions").change(function(){
        getResponsesForQuestion(this.value,$(this).nextAll(".answers:first"));
        if (questions[this.value]['type'] == 'one') {
            $(this).next(".oper").children("[value='all']").remove();
        } else {
            $(this).nextAll(".oper:first").children("[value='none']").before('<option>all</option>');
        }
    });
    criterion.find(".questions *:selected").change();

    // Delete button behaviours.
    criterion.find(".criterion").hover(function(){
        $(this).children('.criterionDelete').fadeIn(100);
    },function(){
        $(this).children('.criterionDelete').fadeOut(100);
    });

    criterion.find('.criterionDelete').click(function(){
        $(this).hide();
        $(this).closest('.criterionWrapper').prev('.criterionWrapper').find('.boolOper').slideUp(300);
        $(this).closest('.criterionWrapper').slideUp(300,function()
        {
            $(this).remove();
        });
    });

    // Bool oper behaviours.
    criterion.find(".boolOper").click(function(){
        var oper = $(this).html();
        if (oper == 'AND') {
            oper = 'OR';
        } else {
            oper = 'AND';
        }
        $(this).html(oper);
    });
    criterion.find(".boolOper").hide();

    // Show the previously hidden boolOper.
    $("#predicate .boolOper").slideDown(100);
    $("#predicate").append(criterion);
    criterion.slideDown(300);
}

function addSubcriterion(obj) {

    var criterion = $(obj).closest(".criterionWrapper");
    var subcriterion = $(subcriterionHTML);

    criterion.find(".subcriteria").append(subcriterion);

    for (var i in questions)
    {
        q = questions[i];
        subcriterion.find(".questions").append('<option value="' + q.id + '">' + q.question + '</option>');
    }

    subcriterion.find(".questions").change(function() {
        getResponsesForQuestion(this.value, $(this).nextAll(".answers:first"));
        if (questions[this.value]['type'] == 'one') {
            $(this).next(".oper").children("[value='all']").remove();
        } else {
            $(this).nextAll(".oper:first").children("[value='none']").before('<option>all</option>');
        }
    });
    subcriterion.find(".questions *:selected").change();

    subcriterion.hover(function(){
        $(this).find('.criterionDelete').fadeIn(100);
    },function(){
        $(this).find('.criterionDelete').fadeOut(100);
    });

    subcriterion.find('.criterionDelete').click(function(){
        $(this).hide();
        $(this).closest('.subcriterionWrapper').slideUp(300,function()
        {
            var c = $(this).closest(".criterionWrapper").children(".criterion");
            $(this).remove();
            updateRunningCounter(c);
        });
    });

}

function synchroniseViewToModel() {
    // First clear out our model.
    teamAssignments = [];
    $(".team").each(function() {
        var teamDiv = $(this);
        var teamIndex = $(this).index();
        var assignments = [];
        teamDiv.find(".student").each(function() {
            var studentDiv = $(this);
            var studentID = /student-(\d+)/.exec($(this).attr("id"))
            assignments.push(studentID[1]); // Slot 1 contains the group we want.
        });
        teamAssignments[teamIndex] = assignments;
    });
}

function synchroniseModelToView(numTeams) {
    // Reset our view.
    $("#unassigned").html(initstate);
    $("#teams").empty();

    // Create our team views.
    for(var i = 0; i < teamNames.length; i++)
    {
        var teamDiv = $('<div class="team" id="team-' + i + '" />');
        teamDiv.append("<h2>" + teamNames[i] + "</h2>");
        teamDiv.width(maxwidth + 30);
        teamDiv.append('<div class="sortable"></div>');
        $("#teams").append(teamDiv);
    }

    // Get our sortable states happening.
    sortdict = {
        connectWith:".sortable",
        start: function(evt) { preventStudentClick = true; },
    };
    $("#teams .sortable").sortable(sortdict)
    $("#unassigned .sortable").sortable(sortdict);

    // Now to move our students to our teams.
    for(i in teamAssignments)
    {
        var team = teamAssignments[i];
        var teamDiv = $("#team-" + i + " > div.sortable");
        for(j in team)
        {
            var studentID = team[j];
            var studentDiv = $("#student-" + studentID);
            studentDiv.detach();
            teamDiv.append(studentDiv);
            if ($.inArray(studentID,selectedStudents) != -1)
            {
                studentDiv.css("color","green");
            }
            else
            {
                studentDiv.css("color","");
            }
        }
    }

}

function getCriterionObjectFromView(view) {
    var criterion = {};

    criterion.question = $(view).children(".questions").find("*:selected").val();
    criterion.answers = [];
    criterion.oper = $(view).children(".oper").find("*:selected").val();
    $(view).children(".answers").find("input:checked").each(function(){
        criterion.answers.push(this.value);
    });

    // Subcriteria.

    criterion.subcriteria = [];
    subcriteria = $(view).children(".subcriteria");
    subcriteria.find(".subcriterionWrapper").each(function(){
        var subcriterion = {};
        subcriterion.question = $(this).children(".questions").find("*:selected").val();
        subcriterion.answers = [];
        subcriterion.oper = $(this).children(".oper").find("*:selected").val();
        $(this).children(".answers").find("input:checked").each(function(){
            subcriterion.answers.push(this.value);
        });
        criterion.subcriteria.push(subcriterion);
    });

    return criterion;
}

/*

 Assignment psuedocode:

 for each criterion:
 if (number of candidates who meet the criterion who are not on any teams) >= (minimum number of teams):
 for each candidate who meets the criterion who is not on a team:
 assign candidate to a random team
 else if (number of candidates who meet the criterion) >= (minimum number of teams):
 get a list of teams with members who already meet this criterion
 for each candidate who meets the criterion who is not on a team:
 assign candidate to a random team not on that list
 else:
 get a list of teams with no members who meet this criterion
 for each candidate who meets the criterion who is not on a team:
 assign candidate to a random team on that list
 increase 'priority' of teams who did not get a member for this criterion
 -- when getting lists, teams are sorted first randomly, then by priority

 */

function buildTeams()
{
    resetTeams();

    synchroniseViewToModel();

    selectedStudents = [];

    // Build our predicate based on the UI.

    var predicate = [];
    var criterionGroup = []; // Temp var for running criterion group.
    $("#predicate .criterionWrapper").each(function(){
        var criterion = getCriterionObjectFromView($(this).children(".criterion"));
        criterionGroup.push(criterion);
        if ($(this).find(".boolOper").html() == "AND")
        {
            predicate.push(criterionGroup);
            criterionGroup = [];
        }
    });

    // Options.
    var prioritise = $("#prioritise").val();

    unassignedStudents = {};
    assignedStudents = {};
    $("#unassigned .student").each(function() {
        rslt = /student-(\d+)/.exec(this.id);
        unassignedStudents[rslt[1]] = students[rslt[1]];
    });
    $(".team .student").each(function() {
        rslt = /student-(\d+)/.exec(this.id);
        assignedStudents[rslt[1]] = students[rslt[1]];
    });

    // Get rid of students with no responses.
    $.each(responses, function(k, v) {
        if (v === false) {
            delete assignedStudents[k];
            delete unassignedStudents[k];
        }
    });

    // Initialise teamsPriority to all zeroes.
    teamsPriority = [];
    initTeamsList = [];
    for(i = 0; i < teamNames.length; i++)
    {
        teamsPriority.push(0);
        initTeamsList.push(i);
    }

    for(i in predicate)
    {
        __debug("Criterion " + i);
        cg = predicate[i]; // Criterion group.
        ucandidates = []; // Unassigned candidates.
        acandidates = []; // Assigned candidates.

        // Get candidates matching criterion group.
        ucandidates = studentsMeetingCriterionGroup(unassignedStudents,cg);
        acandidates = studentsMeetingCriterionGroup(assignedStudents,cg);
        studentPriority = {};
        // Initialise studentPriority.
        for(j in unassignedStudents)
        {
            studentPriority[j] = 0;
        }

        // This is done to optimise the selection process
        // in this case priority will be an index of how much a student should NOT be selected for this criterion,
        // because they will be more useful in future selections.
        for (j = predicate.length - 1; j > i; j--)
        {
            // Count backwards from the end, reducing student priority as it becomes more likely that they will be needed again
            // note that this is only relevant for unassigned students.
            icandidates = studentsMeetingCriterionGroup(unassignedStudents,predicate[j]);
            for(k in icandidates)
            {
                s = icandidates[k];
                studentPriority[s]++;
            }
        }

        __debug(studentPriority);

        teamsList = initTeamsList.slice(0);
        teamsList = randomiseArray(teamsList);

        // This function will be comparing team indices in teamsList, so it needs to compare their priorities.
        function teamsSortingFunction(a,b)
        {
            return teamsPriority[a] - teamsPriority[b];
        }
        teamsList = teamsList.sort(teamsSortingFunction);

        if (prioritise == "numbers")
        {
            function teamsSortingFunction2(a,b)
            {
                return teamAssignments[a].length - teamAssignments[b].length;
            }
            teamsList = teamsList.sort(teamsSortingFunction2);
            __debug("teams list");
            __debug(teamAssignments);
            __debug(teamsList)

            TWO_JOBS_PRIORITY = 2;
        } else {
            TWO_JOBS_PRIORITY = 1;
        }

        ucandidates = randomiseArray(ucandidates);
        acandidates = randomiseArray(acandidates);

        // This function sorts like the above function, only it compares student priorities.
        function studentsSortingFunction(a,b)
        {
            return studentPriority[a] - studentPriority[b];
        }
        ucandidates = ucandidates.sort(studentsSortingFunction);

        if(ucandidates.length >= teamNames.length)
        {
            // Best case scenario - assign at random.
            __debug("Best case");
            __debug(ucandidates);
            unassignedStudents = ucandidates;

            while(unassignedStudents.length > 0)
            {
                // Get the team(s) with the lowest numbers.
                var lowestTeam = 0; var lowestTeams = [];

                // Skip the 0th team since otherwise we compare it to itself.
                for (i = 1; i < teamAssignments.length; i++)
                {
                    t = teamAssignments[i];
                    lt = teamAssignments[lowestTeam];

                    if (t.length < lt.length) {
                        lowestTeam = i;
                        lowestTeams = [];
                    } else if (t.length == lt.length) {
                        lowestTeams.push(i);
                    }
                }
                lowestTeams.push(lowestTeam);

                // Pick a random team from the list of lowest teams.
                do {
                    randomTeam = Math.floor(Math.random() * lowestTeams.length);
                    // On the OFF CHANCE that Math.random() produces 1, loop.
                } while (randomTeam >= lowestTeams.length);
                teamAssignments[lowestTeams[randomTeam]].push(unassignedStudents.pop());
            }
        }
        else
        {
            if (ucandidates.length + acandidates.length < teamNames.length) {
                __debug("Worst case")
            } else {
                __debug("Worse case");
            }
            __debug(ucandidates);
            __debug(acandidates);
            // Get a list of teams with members who already meet this criterion.
            var priorityTeams = []; // Teams with no members who meet this criterion.
            var otherTeams = []; // Teams with members who meet this criterion.
            for(j in teamsList)
            {
                t = teamsList[j];
                var teamQualifies = true; // Rendered false if team already has a member who meets this criterion.

                for(k in acandidates)
                {
                    c = acandidates[k];
                    ta = teamAssignments[t];
                    if($.inArray(c,ta) != -1) { // If student k is already on team t.
                        teamQualifies = false;
                    }
                }

                if(teamQualifies)
                {
                    priorityTeams.push(t);
                    // Cover our worst case - not enough people to go around.
                    teamsPriority[t] += MISSED_OUT_PRIORITY;
                }
                else
                {
                    otherTeams.push(t);
                    teamsPriority[t] += TWO_JOBS_PRIORITY;
                }
            }

            // Now we have our list of teams split in two, first take the priority teams and assign to them.
            for(j in ucandidates)
            {
                if(j < priorityTeams.length)
                {
                    t = priorityTeams[j];
                    s = ucandidates[j];
                    teamAssignments[t].push(s);
                    teamsPriority[t] -= MISSED_OUT_PRIORITY;
                }
                else
                {
                    x = j - priorityTeams.length;
                    t = otherTeams[x];
                    s = ucandidates[j];
                    teamAssignments[t].push(s);
                    teamsPriority[t] -= TWO_JOBS_PRIORITY; // Looks like this team didn't miss out after all!
                }
            }

        }

        // First refresh our assignedStudents and unassignedStudents arrays from teamAssignments.
        for(j in teamAssignments)
        {
            t = teamAssignments[j];
            for(k in unassignedStudents)
            {
                s = unassignedStudents[k];
                if($.inArray(k,t) != -1)
                {
                    // Move from unassigned to assigned.
                    assignedStudents[k] = s;
                    delete unassignedStudents[k];
                    selectedStudents.push(k);
                }
            }
        }

        __debug(teamAssignments);
        __debug(teamsPriority);

        __debug("<br/>");

    }

    synchroniseModelToView();

}

function studentsMeetingCriterionGroup(students,criterionGroup)
{
    candidates = [];
    for(c in criterionGroup)
    {
        criterion = criterionGroup[c];

        // Get the candidates who that meet the criterion.
        for(s in students)
        {
            if(studentMeetsCriterion(s,criterion))
            {
                if ($.inArray(s, candidates) == -1) {
                    candidates.push(s);
                }
            }
        }
    }
    return candidates;
}

function studentMeetsCriterion(student,criterion)
{
    sr = responses[student]; // Student responses.
    if(sr === false) {
        return false; // Students without a response cannot meet a criterion.
    }

    var ret; // Return value.

    if(criterion.oper == "any")
    {
        ret = false;
        for(a in criterion.answers)
        {
            ans = parseInt(criterion.answers[a]);
            if ($.inArray(ans,sr) != -1)
            {
                ret = true;
                break;
            }
        }
    }
    else if (criterion.oper == "all")
    {
        ret = true;
        for (a in criterion.answers)
        {
            ans = parseInt(criterion.answers[a]);
            if ($.inArray(ans,sr) == -1)
            {
                ret = false;
                break;
            }
        }
    }
    else if (criterion.oper == "none")
    {
        ret = true;
        for (a in criterion.answers)
        {
            ans = parseInt(criterion.answers[a]);
            if ($.inArray(ans,sr) != -1)
            {
                ret = false;
            }
        }
    }

    if (ret && criterion.subcriteria)
    {
        for (i = 0; i < criterion.subcriteria.length; i++)
        {
            sc = criterion.subcriteria[i];
            ret = studentMeetsCriterion(student, sc);
            if (ret == false) {
                break;
            }
        }
    }
    return ret;
}

function assignRandomly()
{
    synchroniseViewToModel();
    var unassignedStudents = [];
    $("#unassigned .student").each(function(){
        rslt = /student-(\d+)/.exec(this.id);
        unassignedStudents.push(rslt[1]);
    });
    unassignedStudents = randomiseArray(unassignedStudents);

    while(unassignedStudents.length > 0)
    {
        // Get the team(s) with the lowest numbers.
        var lowestTeam = 0; var lowestTeams = [];

        // Skip the 0th team since otherwise we compare it to itself.
        for(i = 1; i < teamAssignments.length; i++)
        {
            t = teamAssignments[i];
            lt = teamAssignments[lowestTeam];

            if(t.length < lt.length) {
                lowestTeam = i;
                lowestTeams = [];
            } else if(t.length == lt.length) {
                lowestTeams.push(i);
            }
        }
        lowestTeams.push(lowestTeam);

        // Pick a random team from the list of lowest teams.
        do {
            randomTeam = Math.floor(Math.random() * lowestTeams.length);
            // On the OFF CHANCE that Math.random() produces 1, loop.
        } while (randomTeam >= lowestTeams.length);
        teamAssignments[lowestTeams[randomTeam]].push(unassignedStudents.pop());
    }

    synchroniseModelToView();
}

function createGroups() {
    // How this works is, we're going to create an invisible form and submit it
    // acutally i don't know if we can do that but we'll try.

    synchroniseViewToModel();

    var form = $('<form action="' + window.location + '" method="POST"></form>');

    for (i = 0; i < teamNames.length; i++)
    {
        var tn = teamNames[i];
        var assign = teamAssignments[i];
        var tnInput = $('<input type="hidden" name="teamnames[' + i + ']" value="' + tn + '" />');
        form.append(tnInput)
        var input = $('<input type="hidden" name="teams[' + i + ']" value="' + assign.join(",") + '" />')
        form.append(input);
    }

    var action = $('<input type="hidden" name="action" value="create-groups" />');
    var name = $('<input type="hidden" name="groupingName" value="' + $('#groupingName').val() + '" />');
    var grpid = $('<input type="hidden" name="groupingID" value="' + $('#groupingSelect').val() + '" />');
    var inherit = $('<input type="hidden" name="inheritGroupingName" value="' + ( $('#inheritGroupingName').is(":checked") ? 1 : 0 ) + '" />');
    var nogrouping = $('<input type="hidden" name="nogrouping" value="' + ( $('#nogrouping').is(":checked") ? 1 : 0 ) + '" />');
    form.append(action);
    form.append(name);
    form.append(grpid);
    form.append(inherit);
    form.append(nogrouping);

    $("#createGroupsForm").append(form);
    form.submit();
}

var criterionHTML = '<div class="criterionWrapper"><div class="criterion ui-corner-all"> \
<div class="criterionDelete"></div> \
At least one student who answered <select class="questions"></select> with <select class="oper"><option>any</option><option>all</option><option>none</option></select> of the following: \
<div class="answers"></div> \
<div class="add_sub"><a href="#" onclick="addSubcriterion(this);" title="Add new subcriterion"><img src="css/add_sub.png" /></a></div><div class="subcriteria"></div> \
<div class="runningCounter"></div> \
</div><div class="boolOper">AND</div></div>';

var subcriterionHTML = '<div class="subcriterionWrapper"><div class="criterionDelete"></div> \
...and answered <select class="questions"></select> with <select class="oper"><option>any</option><option>all</option><option>none</option></select> of the following: \
<div class="answers"></div></div>';


function addSlashes(str) {
    str = str.replace(/\\/g,'\\\\');
    str = str.replace(/\'/g,'\\\'');
    str = str.replace(/\"/g,'\\"');
    str = str.replace(/\0/g,'\\0');
    return str;
}

function stripSlashes(str) {
    str = str.replace(/\\'/g,'\'');
    str = str.replace(/\\"/g,'"');
    str = str.replace(/\\0/g,'\0');
    str = str.replace(/\\\\/g,'\\');
    return str;
}

function __debug(val) {
    if (false) { // Set to true for debugging.
        $("#debug").append("<div>" + JSON.stringify(val) + "</div>");
    }
}

function randomiseArray(inArray) {
    // Much more random than sort().
    var ret = [];
    var array = inArray.slice(0);
    for (i = array.length; i > 0; i--) {
        index = Math.floor(Math.random() * i);
        ret.push(array[index]);
        array.splice(index, 1);
    }
    return ret;
}
