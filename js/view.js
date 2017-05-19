$(function() {
    var maxwidth = 80;
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

});

function validateForm(form) {

    var valid = true;
    $(form).find(".atleastone, input[type='radio']").closest(".answers").each(function() {
        if ($(this).find(".atleastone:checked").length > 0)
        {
            $(this).closest("div.question").removeClass("ui-state-error");
            return true; // Equiv. to continue in each() loop.
        }
        if ($(this).find("input[type='radio']:checked").length > 0)
        {
            $(this).closest("div.question").removeClass("ui-state-error");
            return true; // Equiv. to continue in each() loop.
        }
        valid = false;
        $(this).closest("div.question").addClass("ui-state-error");
    });

    return valid;

}