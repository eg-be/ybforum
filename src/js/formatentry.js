function formatText(tag)
{
    var $textArea = $('#post_content');
    var cursorStart = $textArea.prop("selectionStart");
    var cursorEnd = $textArea.prop("selectionEnd");
    if(cursorStart >= 0 && cursorEnd >= 0)
    {
        var txt = $textArea.val();
        var newTxt = txt.substr(0, cursorStart);
        newTxt+= '[' + tag + ']';
        newTxt+= txt.substr(cursorStart, cursorEnd - cursorStart);
        newTxt+= '[/' + tag + ']';
        newTxt+= txt.substr(cursorEnd);
        $textArea.val(newTxt);
        $textArea.trigger("focus");
    }
}

function addObject(object)
{
    var $textArea = $('#post_content');
    var cursorStart = $textArea.prop("selectionStart");
    var cursorEnd = $textArea.prop("selectionEnd");
    if(cursorStart >= 0 && cursorEnd >= 0)
    {
        var txt = $textArea.val();
        var newTxt = txt.substr(0, cursorStart);
        newTxt+= object;
        newTxt+= txt.substr(cursorStart);
        $textArea.val(newTxt);
        $textArea.trigger("focus");
        $textArea.prop("selectionEnd", cursorStart + object.length);
    }
}