define(['jquery'], function($) {
    var maxwidth = 80;

    var setup = function() {
        $(".answers").find("label").each(function() {
            if ($(this).width() > maxwidth) {
                maxwidth = $(this).width();
            }
        });

        $(".answers").each(function() {
            var table = $("<div />");
            $(this).find("label").each(function() {
                var div = $("<div />");
                div.addClass("response");
                div.width(maxwidth);
                div.append($(this));
                table.append(div);
            });
            $(this).empty();
            $(this).append(table);
            $(this).css("visbility", "visible");
        });
    };

    var validateForm = function(e) {
        var valid = true;

        $(e.target).find(".atleastone, input[type='radio']").closest(".answers").each(function() {
            if ($(this).find(".atleastone:checked").length > 0) {
                $(this).closest("div.question").removeClass("ui-state-error");
                return true; // Equiv. to continue in each() loop.
            }
            if ($(this).find("input[type='radio']:checked").length > 0) {
                $(this).closest("div.question").removeClass("ui-state-error");
                return true; // Equiv. to continue in each() loop.
            }
            valid = false;
            $(this).closest("div.question").addClass("ui-state-error");
        });

        return valid;
    };

    var registerEventListeners = function() {
        $('#questionnaireform').on('submit', validateForm);
    };

    return {
        init: function() {
            setup();
            registerEventListeners();
        }
    };
});
