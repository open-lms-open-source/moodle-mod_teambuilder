//this may be set to true later in the page
var interaction_disabled = false;

$(function() {
	
	for(var i in init_questions)
	{
		$("#question-"+i).data("question",init_questions[i])
		var x = $("#question-"+i+" .type");
		x.html(typesDisplay[x.html()]);
	}
	
	if(interaction_disabled==false)
	{
		$("#questions").sortable({handle : '.handle', axis : 'y'});
		$("#questions .answers ul").sortable({axis : 'y'}).find("li").css("cursor","default");
	
		$("#answerSection input").live('keydown',function(evt) {
			if((evt.which==13) && !(evt.metaKey || evt.ctrlKey))
			{
				if($(this).nextAll("input:first").length==0)
					addNewAnswer();
				else
					$(this).nextAll("input:first").focus();
			} else if(evt.which==38) {
				$(this).prevAll("input:first").focus();
			} else if(evt.which==40) {
				$(this).nextAll("input:first").focus();
			}
		});
	
		$("#newQuestionForm input").live('keydown',function(evt) {
			if((evt.which==13) && (evt.metaKey || evt.ctrlKey))
			{
				$("#addNewQuestion").click();
				$("#newQuestionForm input[name='question']").focus();
			}
		});
	}
	else
	{
		$("td.edit a").remove();
	}
	
	$.fn.wait = function(time, type) {
	        time = time || 1000;
	        type = type || "fx";
	        return this.queue(type, function() {
	            var self = this;
	            setTimeout(function() {
	                $(self).dequeue();
	            }, time);
	        });
	    };
	
	$("#importButton").click(function() {
		window.location.href = window.location.href + "&import="+$(this).prevAll("select:first").val();
	});
	
	
});

function addNewAnswer()
{
	$('<input type="text" name="answers[]" class="text" /><br/>').insertBefore("#answerSection button:first").focus();
	return false;
}

function removeLastAnswer()
{
	$('#answerSection input:last').next().remove();
	$('#answerSection input:last').remove();
}

function addNewQuestion()
{	
	var question = {};
	question['question'] = $("#newQuestionForm input[name='question']").val();
	question['type'] = $("#newQuestionForm select").val();
	question['answers'] = [];
	$("#answerSection input[type='text']").each(function()
	{
		if($.trim($(this).val()).length)
		question['answers'].push($(this).val());
	})
	
	//validate
	err = [];
	if($.trim(question.question)=='')
		err.push('Please enter a question');
	if(question.answers.length<=1)
		err.push('Please enter at least two answers');
	if(err.length)
	{
		alert(err.join("\n"));
		return;
	}
	
	//initialise the view
	questionView = $(views.question);
	questionView.find(".questionText").html(question['question']);
	questionView.find(".type").html(typesDisplay[question['type']]);
	questionView.find(".answers").html("<ul><li>" + question['answers'].join("</li><li>") + "</li></ul>");
	$("#questions").append(questionView);
	questionView.find(".answers ul").sortable({axis : 'y'}).find("li").css("cursor","default");
	questionView.data("question",question);
	
	//reset the form
	$("#newQuestionForm input").val("");
	
	$("#saveQuestionnaire").click();
	
	return false;
}

function deleteQuestion(object)
{
	$(object).closest("div.question").slideUp(300,function(){
		$(this).remove();
	});
}

function saveQuestionnaire(url, id)
{	
	var questions = $("#questions div.question");
	var questiondata = [];
	if(questions.length<1)
	{
		alert("Please enter at least one question.");
		return;
	}
	
	//iterate over the UI to get question and answer order
	questions.each(function()
	{
		//reorder answers
		var question = $(this).data("question");
		question.answers = [];
		$(this).find(".answers ul li").each(function(){
			question.answers.push($(this).html());
		})
		questiondata.push(question);
	});
	
	$("#savingIndicator").html("Saving...").slideDown(300);
	
	$.post(url,{'id' : id, 'action' : 'saveQuestionnaire', 'input' : JSON.stringify(questiondata)},function(data) {
		for(i in data.questionnaire)
		{
			o = data.questionnaire[i];
			questions.eq(parseInt(o.ordinal)).data("question",o);
		}
		$("#savingIndicator").html("Saved!").wait(2000).slideUp(300);
	},'json')
}

var views = {
	'question' : '<div class="question"><table> \
	<tr> \
		<td rowspan="2" class="handle">&nbsp;</td> \
		<td><span class="questionText"></span> <span class="type"></span></td> \
		<td colspan="2" class="edit"> \
			<a onclick="deleteQuestion(this)">Delete</a> \
		</td> \
	</tr> \
	<tr> \
		<td class="answers" colspan="2"></td> \
	</tr></table></div>'
};

var typesDisplay = {
	'one' : 'Select one',
	'any' : 'Select any (or none)',
	'atleastone' : 'Select at least one'
};
