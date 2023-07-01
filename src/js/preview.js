function clearPreview()
{
    $('#previewcontainer').remove();
}

function preview()
{
    // remove any existing preview first
    clearPreview();
    // insert a div with the preview before the actual form to create an entry
    var prev = '<div id="previewcontainer">' + 
            '<div class="fullwidthcenter generictitle">Vorschau</div>' + 
            '<div id="previewcontent" style="white-space: pre-wrap"></div>' +
            '<hr>' + 
            '</div>';
    
    $(prev).insertBefore($('#postformcontainer'));
    
    // and add all properties of the input form
    // do not add email, there is no place to display it
    var linkUrl = $('#post_linkurl').val();
    var linkText = $('#post_linktext').val();
    var imgUrl = $('#post_imgurl').val();
    var content = $('#post_content').val();
    
    $previewcontent = $('#previewcontent');
    $previewcontent.attr('data-linkurl', linkUrl);
    $previewcontent.attr('data-linktext', linkText);
    $previewcontent.attr('data-imgurl', imgUrl);
    $previewcontent.text(content);

    // and render the content
    renderSpans($previewcontent);
    renderObjects($previewcontent);
    renderHtmlTags($previewcontent);
    renderImgTags($previewcontent);
    renderEmailTags($previewcontent);    
    renderColors($previewcontent);
    insertPostImage($previewcontent);
    insertPostLink($previewcontent);
    
    // also the embedded videos
    insertYoutubePlayers($previewcontent);
}