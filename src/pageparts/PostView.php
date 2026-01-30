<?php

declare(strict_types=1);

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

require_once __DIR__ . '/../model/ForumDb.php';

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
        assert($forumDb->isConnected());
        assert(is_null($parentPost) || $parentPost->getId() === $post->getParentPostId());
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
    public function renderHtmlTitleDivContent(): string
    {
        $htmlStr = '<div class="fullwidthcenter generictitle">'
            . htmlspecialchars($this->m_post->getTitle());
        if (!$this->m_post->hasContent()) {
            $htmlStr .= ' (o.T.)';
        }
        $htmlStr .= '</div>';
        $htmlStr .=  '<div>geschrieben von <span id="postnick">'
                . htmlspecialchars($this->m_post->getNick())
                . '</span> am ';

        $timestampStr = $this->m_post->getPostTimestamp()->format(
            'd.m.Y \u\m H:i:s'
        );
        $htmlStr .= $timestampStr;
        if (!is_null($this->m_parentPost)) {
            $htmlStr .= ' - als Antwort auf: <a class="fbold" '
                . 'href="showentry.php?idpost='
                . $this->m_parentPost->getId() . '">'
                . htmlspecialchars($this->m_parentPost->getTitle()) . '</a> '
                . 'von ' . htmlspecialchars($this->m_parentPost->getNick());
        }
        $htmlStr .= '</div>';
        return $htmlStr;
    }

    /**
     * Renders the content of this post as a HTML div. Additional content
     * (like a link, an image-url, etc.) are added as data- attributes on
     * the div itself. If the post has no text-content, some text stating
     * this post is empty is set as content.
     * @return string
     */
    public function renderHtmlPostContentDivContent(): string
    {
        $extraData = '';
        // Add all extra data as data-tags
        if ($this->m_post->isOldPost()) {
            $extraData .= 'data-oldno="' . $this->m_post->getOldPostNo() . '" ';
        }
        if ($this->m_post->hasImgUrl()) {
            $extraData .= 'data-imgurl="'
                    . htmlspecialchars($this->m_post->getImgUrl()) . '" ';
        }
        if ($this->m_post->hasLinkUrl()) {
            $extraData .= 'data-linkurl="'
                    . htmlspecialchars($this->m_post->getLinkUrl()) . '" ';
        }
        if ($this->m_post->hasLinkText()) {
            $extraData .= 'data-linktext="'
                    . htmlspecialchars($this->m_post->getLinkText()) . '" ';
        }
        if ($this->m_post->hasEmail()) {
            $extraData .= 'data-email="'
                    . htmlspecialchars($this->m_post->getEmail()) . '" ';
        }
        $html = '<div id="postcontent" ';
        if (!empty($extraData)) {
            $html .= $extraData;
        }
        if (!$this->m_post->hasContent()) {
            $html .= 'class="nocontent fullwidthcenter">'
                . 'Dieser Eintrag hat keinen Text!'
                . '</div>';
        } else {
            $html .= 'class="postcontent">'
                . htmlspecialchars($this->m_post->getContent())
                . '</div>';
        }
        return $html;
    }

    /**
     * Renders the answers of this post, as threaded view. Returned is a
     * a list of HTML p elements.
     * @return string
     */
    public function renderHtmlThreadDivContent(): string
    {
        // if this post is already hidden, do not display any children at all
        if ($this->m_post->isHidden()) {
            return '';
        }
        $htmlStr = '';
        $threadIndexes = $this->m_forumDb->loadPostReplies($this->m_post);
        $ourPostIndent = $this->m_post->getIndent();
        foreach ($threadIndexes as $ti) {
            $htmlStr .= '<p class="nomargin" ';
            $htmlStr .= 'style="text-indent: ';
            $htmlStr .= ($ti->getIndent() - $ourPostIndent - 1) . 'em"><a ';
            $htmlStr .= 'href="showentry.php?idpost='
                . $ti->getPostId() . '">';
            $htmlStr .= $ti->getTitle();
            if (!$ti->hasContent()) {
                $htmlStr .= ' (o.T.)';
            }
            $htmlStr .= '</a> - <span class="fbold">';
            $htmlStr .= $ti->getNick();
            $htmlStr .= '</span> - ';
            $htmlStr .= $ti->getPostTimestamp()->format('d.m.Y H:i:s');
            $htmlStr .= '</p>';
        }
        return $htmlStr;
    }

    private ForumDb $m_forumDb;
    private Post $m_post;
    private ?Post $m_parentPost;

}
