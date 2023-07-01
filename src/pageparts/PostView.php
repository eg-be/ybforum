<?php

/**
 * Copyright 2017 Elias Gerber <eg@zame.ch>
 * 
 * This file is part of YbForum1898.
 *
 * YbForum1898 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * YbForum1898 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with YbForum1898.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Renders the parts of a single post.
 *
 * @author Elias Gerber
 */
class PostView
{
    /**
     * Display a single post.
     * @param ForumDb $forumDb A database instance
     * @param Post $post The post to display.
     * @param ?Post $parentPost The parent post of this post, or null if this
     * post has no parent post.
     */
    public function __construct(ForumDb $forumDb, Post $post, ?Post $parentPost)
    {
        assert($forumDb->IsConnected());
        assert(is_null($parentPost) || $parentPost->GetId() === $post->GetParentPostId());
        $this->m_forumDb = $forumDb;
        $this->m_post = $post;
        $this->m_parentPost = $parentPost;
    }
    
    /**
     * Renders a HTML div holding the title of this post. If a parent post 
     * has been set during construction, a reference to that post is 
     * included in the title.
     * @return string
     */
    public function renderHtmlTitleDivContent() : string
    {
        $htmlStr = '<div class="fullwidthcenter generictitle">'
            . htmlspecialchars($this->m_post->GetTitle());
        if(!$this->m_post->HasContent())
        {
            $htmlStr.= ' (o.T.)';
        }
        $htmlStr.= '</div>';
        $htmlStr.=  '<div>geschrieben von <span id="postnick">' 
                . htmlspecialchars($this->m_post->GetNick())
                . '</span> am ';
        
        $timestampStr = $this->m_post->GetPostTimestamp()->format(
                'd.m.Y \u\m H:i:s');
        $htmlStr.= $timestampStr;
        if(!is_null($this->m_parentPost))
        {
            $htmlStr.= ' - als Antwort auf: <a class="fbold" '
                . 'href="showentry.php?idpost='
                . $this->m_parentPost->GetId() . '">'
                . htmlspecialchars($this->m_parentPost->GetTitle()) . '</a> '
                . 'von ' . htmlspecialchars($this->m_parentPost->GetNick());
        }
        $htmlStr.= '</div>';
        return $htmlStr;        
    }
    
    /**
     * Renders the content of this post as a HTML div. Additional content 
     * (like a link, an image-url, etc.) are added as data- attributes on
     * the div itself. If the post has no text-content, some text stating
     * this post is empty is set as content.
     * @return string
     */
    public function renderHtmlPostContentDivContent() : string
    {
        $extraData = '';
        // Add all extra data as data-tags
        if($this->m_post->IsOldPost())
        {
            $extraData.= 'data-oldno="' . $this->m_post->GetOldPostNo() . '" ';
        }        
        if($this->m_post->HasImgUrl())
        {
            $extraData.= 'data-imgurl="' 
                    . htmlspecialchars($this->m_post->GetImgUrl()) . '" ';
        }        
        if($this->m_post->HasLinkUrl())
        {
            $extraData.= 'data-linkurl="'
                    . htmlspecialchars($this->m_post->GetLinkUrl()) . '" ';
        }
        if($this->m_post->HasLinkText())
        {
            $extraData.= 'data-linktext="'
                    . htmlspecialchars($this->m_post->GetLinkText()) . '" ';
        }
        if($this->m_post->HasEmail())
        {
            $extraData.= 'data-email="'
                    . htmlspecialchars($this->m_post->GetEmail()) . '" ';
        }
        $html = '<div id="postcontent" ';
        if(!empty($extraData))
        {
            $html.= $extraData;
        }
        if(!$this->m_post->HasContent())
        {
            $html.= 'class="nocontent fullwidthcenter">'
                . 'Dieser Eintrag hat keinen Text!'
                . '</div>';
        }
        else
        {
            $html.= 'class="postcontent">' 
                . htmlspecialchars($this->m_post->GetContent()) 
                . '</div>';
        }
        return $html;
    }
    
    /**
     * Renders the answers of this post, as threaded view. Returned is a
     * a list of HTML p elements.
     * @return string
     */
    public function renderHtmlThreadDivContent() : string
    {
        // if this post is already hidden, do not display any children at all
        if($this->m_post->IsHidden())
        {
            return '';
        }
        $htmlStr = '';
        $threadIndexes = PostIndexEntry::LoadPostReplies($this->m_forumDb, $this->m_post);
        $ourPostIndent = $this->m_post->GetIndent();
        foreach($threadIndexes as $ti)
        {
            $htmlStr.= '<p class="nomargin ';
            $htmlStr.= '" style="text-indent: ';
            $htmlStr.= ($ti->GetIndent() - $ourPostIndent - 1) . 'em"><a ';
            $htmlStr.= 'href="showentry.php?idpost=' 
                . $ti->GetPostId() . '">';
            $htmlStr.= $ti->GetTitle();
            if(!$ti->HasContent())
            {
                $htmlStr.= ' (o.T.)';
            }
            $htmlStr.= '</a> - <span class="fbold">';
            $htmlStr.= $ti->GetNick();
            $htmlStr.= '</span> - ';
            $htmlStr.= $ti->GetPostTimestamp()->format('d.m.Y H:i:s');
            $htmlStr.= '</p>';
        }
        return $htmlStr;
    }
    
    private ForumDb $m_forumDb;
    private Post $m_post;
    private ?Post $m_parentPost;
    
}
