define(['jquery', 'jqueryui', 'core/config', 'core/str', 'core/notification'], function($, jqui, mdlcfg, str, notification) {
    /*global init_questions:true*/

    // This may be set to true later in the page.
    var interaction_disabled = false;

    var strings = {
        error: "",
        enteratleasttwoanswers: "",
        enteraquestion: "",
        enteratleastonequestion: "",
        saving: "",
        types: {
            one: "",
            any: "",
            atleastone: "",
        }
    };

    var buildstrings = function(s) {
        strings.error = s[0];
        strings.enteratleasttwoanswers = s[1];
        strings.enteraquestion = s[2];
        strings.enteratleastonequestion = s[3];
        strings.saving = s[4];
        strings.types.one = s[5];
        strings.types.any = s[6];
        strings.types.atleastone = s[7];
    };

    var views = {
        /*jshint multistr: true */
        'question' : '<div class="question"><table> \
    	<tr> \
    		<td rowspan="2" class="handle">&nbsp;</td> \
    		<td><span class="questionText"></span> <span class="type"></span></td> \
    		<td colspan="2" class="edit"> \
    			<a class="deleteQuestion">Delete</a> \
    		</td> \
    	</tr> \
    	<tr> \
    		<td class="answers" colspan="2"></td> \
    	</tr></table></div>'
    };

    var addNewAnswer = function() {
        $('<input type="text" name="answers[]" class="text" /><br/>').insertBefore("#answerSection button:first").focus();
        return false;
    };

    var removeLastAnswer = function() {
        $('#answerSection input:last').next().remove();
        $('#answerSection input:last').remove();
    };

    var deleteQuestion = function(object) {
        $(object).closest("div.question").slideUp(300,function(){
            $(this).remove();
        });
    };

    var saveQuestionnaire = function(e) {
        var id = $(e.target).attr('data-id');
        var questions = $("#questions div.question");
        var questiondata = [];
        if (questions.length < 1) {
            notification.alert(strings.error, strings.enteratleastonequestion);
            return;
        }

        // Iterate over the UI to get question and answer order.
        questions.each(function() {
            // Reorder answers.
            var question = $(this).data("question");
            question.answers = [];
            $(this).find(".answers ul li").each(function(){
                question.answers.push($(this).html());
            });
            questiondata.push(question);
        });

        $("#savingIndicator").html(strings.saving).slideDown(300);

        $.post(
            mdlcfg.wwwroot + '/mod/teambuilder/ajax.php',
            {
                'id' : id,
                'action' : 'saveQuestionnaire',
                'input' : JSON.stringify(questiondata)
            },
            function(data) {
                for (var i in data.questionnaire) {
                    var o = data.questionnaire[i];
                    questions.eq(parseInt(o.ordinal)).data("question",o);
                }
                $("#savingIndicator").html("Saved!").wait(2000).slideUp(300);
            },
            'json'
        );
    };

    var addNewQuestion = function() {
        var question = {};
        question.question = $("#newQuestionForm input[name='question']").val();
        question.type = $("#newQuestionForm select").val();
        question.answers = [];
        $("#answerSection input[type='text']").each(function() {
            if ($.trim($(this).val()).length) {
                question.answers.push($(this).val());
            }
        });

        // Validate.
        var err = [];
        if ($.trim(question.question) == '') {
            err.push(strings.enteraquestion);
        }
        if (question.answers.length <= 1) {
            err.push(strings.enteratleasttwoanswers);
        }
        if (err.length) {
            notification.alert(strings.error, err.join("<br />"));
            return;
        }

        // Initialise the view.
        var questionView = $(views.question);
        questionView.find(".questionText").html(question.question);
        questionView.find(".type").html(strings.types[question.type]);
        questionView.find(".answers").html("<ul><li>" + question.answers.join("</li><li>") + "</li></ul>");
        $("#questions").append(questionView);
        questionView.find(".answers ul").sortable({axis : 'y'}).find("li").css("cursor","default");
        questionView.data("question", question);

        // Reset the form.
        $("#newQuestionForm input").val("");

        $('#saveQuestionnaire').click();

        return false;
    };

    var setup = function() {
        for (var i in init_questions) {
            $("#question-" + i).data("question", init_questions[i]);
        }
        if (interaction_disabled == false) {
            $("#questions").sortable({handle : '.handle', axis : 'y'});
            $("#questions .answers ul").sortable({axis : 'y'}).find("li").css("cursor","default");

            $("#answerSection input").on('keydown', function(evt) {
                if ((evt.which == 13) && !(evt.metaKey || evt.ctrlKey)) {
                    if ($(this).nextAll("input:first").length == 0) {
                        addNewAnswer();
                    } else {
                        $(this).nextAll("input:first").focus();
                    }
                } else if(evt.which == 38) {
                    $(this).prevAll("input:first").focus();
                } else if(evt.which == 40) {
                    $(this).nextAll("input:first").focus();
                }
            });

            $("#newQuestionForm input").on('keydown', function(evt) {
                if((evt.which == 13) && (evt.metaKey || evt.ctrlKey)) {
                    addNewQuestion();
                    $("#newQuestionForm input[name='question']").focus();
                }
            });
        } else {
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
            window.location.href = window.location.href + "&import=" + $(this).prevAll("select:first").val();
        });

    };

    var registerEventListeners = function() {
        $('#addNewQuestion').on('click', addNewQuestion);
        $('#saveQuestionnaire').on('click', saveQuestionnaire);
        $('#addnewanswer').on('click', addNewAnswer);
        $('#removelastanswer').on('click', removeLastAnswer);
        $('#questions').delegate('.deleteQuestion', 'click', function(e) {
            e.preventDefault();
            deleteQuestion(e.target);
        });
    };

    return {
        init: function() {
            str.get_strings([
                { key: 'error', component: 'error' },
                { key: 'enteratleasttwoanswers', component: 'mod_teambuilder' },
                { key: 'enteraquestion', component: 'mod_teambuilder' },
                { key: 'enteratleastonequestion', component: 'mod_teambuilder' },
                { key: 'saving', component: 'mod_teambuilder' },
                { key: 'selectone', component: 'mod_teambuilder' },
                { key: 'selectany', component: 'mod_teambuilder' },
                { key: 'selectatleastone', component: 'mod_teambuilder' },
            ]).done(function(s) {
                buildstrings(s);
                setup();
            });
            registerEventListeners();
        }
    };
});
