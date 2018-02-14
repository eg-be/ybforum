function renderSpans($content)
{
    var spanRules = {
        'i': 'fitalic',
        'b': 'fbold',
        'u': 'funderline'
    };
    htmlContent = $content.html();
    var inTagsStack = {
        'i': 0,
        'b': 0,
        'u': 0
    };
    var rCode = /\[(\/?[ibu])\]/;
    var match = rCode.exec(htmlContent);
    while(match !== null)
    {
        if(match[0].indexOf('[/') === 0) 
        {
            // and end-tag
            var tag = match[1].substr(1);
            // decrement the count for that tag
            inTagsStack[tag]--;
            // if this is the last occurance of that tag, close it
            if(inTagsStack[tag] <= 0)
            {
                htmlContent = htmlContent.replace(match[0], '</span>');
            }
            else
            {
                // just ignore (remove) that tag
                htmlContent = htmlContent.replace(match[0], '');
            }
        }
        else 
        {
            // a start tag
            // if not already in that tag, open it
            if(inTagsStack[match[1]] === 0)
            {
                htmlContent = htmlContent.replace(match[0], 
                    '<span class="' + spanRules[match[1]] + '">');
            }
            else
            {
                // just ignore (remove) that tag
                htmlContent = htmlContent.replace(match[0], '');
            }
            // and remember that we are once more in that tag now
            inTagsStack[match[1]]++;
        }
            
        match = rCode.exec(htmlContent);
    }
    
    $content.html(htmlContent);
}

function renderObjects($content)
{
    var objectRules = [
        {
            reg: /\[\[yb\]\]/g,
            img: '<img src="img/yb.gif"/>'
        },
        {
            reg: /\[\[gelb\]\]/g,
            img: '<img src="img/gelb.gif"/>'
        },
        {
            reg: /\[\[rot\]\]/g,
            img: '<img src="img/rote.gif"/>'
        }
        ,
        {
            reg: /\[\[bier\]\]/g,
            img: '<img src="img/bier.gif"/>'
        },
        {
            reg: /\[\[wurst\]\]/g,
            img: '<img src="img/wurst.gif"/>'
        },
        {
            reg: /\[\[kopf\]\]/g,
            img: '<img src="img/kopf.gif"/>'
        },
        {
            reg: /\[\[gbrille\]\]/g,
            img: '<img src="img/gsbrille.gif"/>'
        },
        {
            reg: /\[\[rbrille\]\]/g,
            img: '<img src="img/rosabrille.gif"/>'
        },
        {
            reg: /\[\[tja\]\]/g,
            img: '<img src="img/sachverstand.gif"/>'
        },
        {
            reg: /\[\[hundi\]\]/g,
            img: '<img src="img/hundi.gif"/>'
        },
        {
            reg: /\[\[pm\]\]/g,
            img: '<img src="img/pm.gif"/>'
        },
        {
            reg: /\[\[!!!\]\]/g,
            img: '<img src="img/!!!.gif"/>'
        }        
    ];
    
    htmlContent = $content.html();
    for(var i = 0; i < objectRules.length; i++)
    {
        var rule = objectRules[i];
        htmlContent = htmlContent.replace(rule.reg, rule.img);
    }
    $content.html(htmlContent);
}

function getLastPartWithoutExtension(url)
{
    var lastPartIndex = url.lastIndexOf('/');
    if(lastPartIndex > 0 && url.length > lastPartIndex)
    {
        var lastPart = url.substring(lastPartIndex + 1);
        var fileExtIndex = lastPart.lastIndexOf('.', lastPart);
        if(fileExtIndex > 0 && lastPart.length > fileExtIndex)
        {
            lastPart = lastPart.substring(0, fileExtIndex);
        }
        return lastPart;
    }
    return url;
}

function getHrefElement(link, text)
{
    if (link.indexOf('//') === -1)
    {
        // this is not a valid url scheme, assume its https
        link = 'https://' + link;
    }
    var hrefElement = '<a href="' + link + '" target="_blank">' + text + '</a>';
    return hrefElement;
}

function renderHtmlTags($content)
{
    htmlContent = $content.html();
    // there are (historically) two variants of url tags:
    // [url]http://bla.com[/url] -> renders into a href where the text 
    // is the url
    // and [url=http://bla.com]mylink[/url] -> renders into a href where the
    // text is the content of the tag
    var rCodeA = /\[url\](.+?)\[\/url\]/;
    var rCodeB = /\[url=(.+?)\](.+?)\[\/url\]/;
    var rYoutube = /youtube\.com\/watch\?v=([^&]+)/;
    var rYoutubeShort = /youtu\.be\/([^&]+)/;
    var match = rCodeA.exec(htmlContent);
    while(match !== null)
    {
        var link = match[1];
        var youtubeMatch = rYoutube.exec(link);
        var youtubeShortMatch = rYoutubeShort.exec(link);
        if(youtubeMatch || youtubeShortMatch)
        {
            // we might have multiple youtube videos on one page
            // handle actual video embedding later, once we have all divs set up
            var videoUrl;
            if(youtubeMatch)
            {
                videoUrl = youtubeMatch[1];
            }
            else
            {
                videoUrl = youtubeShortMatch[1];
            }
            htmlContent = htmlContent.replace(match[0], '<div class="video" data-url="' + videoUrl + '"></div>');
        }
        else
        {
            htmlContent = htmlContent.replace(match[0], getHrefElement(link, link));
        }
        match = rCodeA.exec(htmlContent);
    }
    
    match = rCodeB.exec(htmlContent);
    while(match !== null)
    {
        var link = match[1];
        var youtubeMatch = rYoutube.exec(link);
        var youtubeShortMatch = rYoutubeShort.exec(link);
        if(youtubeMatch || youtubeShortMatch)
        {
            // we might have multiple youtube videos on one page
            // handle actual video embedding later, once we have all divs set up
            var videoUrl;
            if(youtubeMatch)
            {
                videoUrl = youtubeMatch[1];
            }
            else
            {
                videoUrl = youtubeShortMatch[1];
            }
            htmlContent = htmlContent.replace(match[0], '<div class="video" data-url="' + videoUrl + '"></div>');
        }
        else
        {
            var text = match[2];
            htmlContent = htmlContent.replace(match[0], getHrefElement(link, text));
        }
        match = rCodeB.exec(htmlContent);
    }    
    
    $content.html(htmlContent);
}

function renderEmailTags($content)
{
    // there are two variants of email tags:
    // [email]foo@bla.com[/email] -> renders into <a href="mailto:foo@bla.com">foo@bla.com</a>
    // and
    // [email=foomail]foo@bla.com[/email] -> renders into <a href="mailto:foo@bla.com">foomail</a>
    htmlContent = $content.html();
    var rCodeA = /\[email\](.+)\[\/email\]/;
    var rCodeB = /\[email=(.+)\](.+)\[\/email\]/;
    var match = rCodeA.exec(htmlContent);
    while(match !== null)
    {
      var mail = match[1];
        // only keep valid mails
      if (mail.indexOf('@') > 0)
      {
        htmlContent = htmlContent.replace(match[0], '<a href="mailto:' + mail + '">' + mail + '</a>');          
      }
      match = rCodeA.exec(htmlContent);
    }
    
    match = rCodeB.exec(htmlContent);
    while(match !== null)
    {
      var mail = match[1];
      var text = match[2];
        // only keep valid mails
      if (mail.indexOf('@') > 0)
      {
        htmlContent = htmlContent.replace(match[0], '<a href=mailto:"' + mail + '>' + text + '</a>');
      } 
      match = rCodeB.exec(htmlContent);
    }    
    
    $content.html(htmlContent);    
}

function renderImgTags($content)
{
    htmlContent = $content.html();
    // an img tags looks like [img]foo.iimg[/img]
    var rCodeImg = /\[img\](.+)\[\/img\]/;
    var match = rCodeImg.exec(htmlContent);
    while(match !== null)
    {
      var imgUrl = match[1];
      if (imgUrl.indexOf('//') === -1)
      {
        // this is not a valid url scheme, assume its https
        imgUrl = 'https://' + imgUrl;
      }
      // also create some alt hint, consisting of the filename
      var alt = getLastPartWithoutExtension(imgUrl);           
      htmlContent = htmlContent.replace(match[0], '<img src="' + imgUrl + '" alt="' + alt + '"/>');
      match = rCodeImg.exec(htmlContent);
    }
    $content.html(htmlContent);
}

function renderColors($content)
{
    var colorCodes = ["black", "maroon",
        "green", "olive",
        "navy", "purple",
        "teal", "silver",
        "gray", "red",
        "lime", "yellow",
        "blue", "fuchsia",
        "aqua", "white"];
    
    htmlContent = $content.html();    
    for(var i = 0; i < colorCodes.length; i++)
    {
        var color = colorCodes[i];
        var expression = '\\[' + color + '\\](.+)\\[\\/' + color + '\\]';
        var rx = new RegExp(expression);
        var match = rx.exec(htmlContent);
        while(match !== null)
        {
          var text = match[1];
          htmlContent = htmlContent.replace(match[0], '<span style="color: ' + color + '">' + text + '</span>');
          match = rx.exec(htmlContent);
        }
    }
    
    $content.html(htmlContent);    
}

function insertPostImage($content)
{
    var imgUrl = $content.attr('data-imgurl');
    if(imgUrl)
    {
        $('<div class="fullwidthcenter"><img src="' 
                + imgUrl + '" alt="' + imgUrl + '"/></div>')
                .insertBefore($content);
    }
}

function insertPostLink($content)
{
    var linkUrl = $content.attr('data-linkurl');
    if(linkUrl)
    {
        var linkText = $content.attr('data-linktext');
        var elem = '<div class="fullwidthcenter"><a target="_blank" href="'
            + linkUrl + '">';
        if(linkText)
        {
            elem+= linkText;
        }
        else
        {
            elem+= linkUrl;
        }
        elem+= '</a></div>';
        $(elem).insertAfter($content);
    }
}

function insertMailAddress($content)
{
    var email = $content.attr('data-email');
    if(email)
    {
        // update the nickname-field
        $postnick = $('#postnick');
        var nick = $postnick.html();
        var link = '<a href="mailto:' + email + '">'
            + nick + '</a>';
        $postnick.html(link);
    }
}

function insertYoutubePlayers($content)
{
    // select all divs with class 'video'
    // and give them unique ids
    nextId = 1;    
    $videos = $content.find('.video');
    $videos.each(function()
    {
        $(this).attr('id', 'video' + nextId);
        nextId++;
    });
    if($videos.length > 0)
    {
        // Check if youtube is already loaded
        var len = $('script').filter(function () {
            return ($(this).attr('src') == 'https://www.youtube.com/iframe_api');
        }).length;
        if(len === 0)
        {
            // load the youtube api async
            var tag = document.createElement('script');
            tag.src = "https://www.youtube.com/iframe_api";
            var firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        }
        else
        {
            onYouTubeIframeAPIReady();
        }
    }
}

function onYouTubeIframeAPIReady() 
{
    // called as soon as youtube api is loaded
    // iterate again all video classes
    $videos = $('.video');
    $videos.each(function()
    {
        // and create a player on them
        var id = $(this).attr('id');
        var videoUrl = $(this).attr('data-url');
        player = new YT.Player(id, 
        {
            height: '270',
            width: '480',
            videoId: videoUrl,
        });
    });
}

function renderPost()
{
    $content = $('#postcontent');
    renderSpans($content);
    renderObjects($content);
    renderHtmlTags($content);
    renderImgTags($content);
    renderEmailTags($content);
    renderColors($content);
    insertPostImage($content);
    insertPostLink($content);
    insertMailAddress($content);
    insertYoutubePlayers($content);
}

