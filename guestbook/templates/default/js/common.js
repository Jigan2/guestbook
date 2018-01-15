// Полное удаление html тегов за исключением whitelist
function strip_tags(str, whitelist) {
    var tmp = document.createElement("DIV");
    tmp.id = "tmp";
    tmp.innerHTML = str;

    document.body.appendChild(tmp);

    //Strip tags and return the whole stripped text
    $("#tmp *").not(whitelist).each(function () {
        if ($(this).is("br")) {
            $(this).remove();
        }
        var content = $(this).contents();
        $(this).replaceWith(content);
    });

    var newText = tmp.innerHTML;
    tmp.remove();

    return newText;
}