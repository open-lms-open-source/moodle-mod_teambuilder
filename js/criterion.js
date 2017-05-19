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

var criterion = function(name) {
    this.name = name;
    criterion.prototype.create = function () {
        var creator = $('<div> \
			At least one person answered: \
			<div><select id="question-dropdown"></select></div> \
			with <select id="cardinality"><option value="0">one</option><option value="1">all</option></select> \
			of the following answers: \
			<div id="answers"></div>');
        creator.dialog({})

    }
};