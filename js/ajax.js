
$(document).on("click", "a.ajax", function (event) {
    event.preventDefault();
    $.get(this.href);
});

/* AJAXové odeslání formulářů */
$(document).on("submit","form.ajax", function () {
    $(this).ajaxSubmit();
    return false;
});

$(document).on("click", "form.ajax :submit", function () {
    $(this).ajaxSubmit();
    return false;
});