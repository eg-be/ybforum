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

require_once __DIR__.'/../handlers/PostEntryHandler.php';

/**
 * Prints a form to create a new post and some usage hints.
 * 
 * @author Elias Gerber
 */
class PostEntryForm 
{
    /**
     * Create a form that will optionally use values from the passed 
     * PostEntryHandler instance.
     * @param type $parentPost Null or a Post, holding the parent post of
     * this post.
     * @param type $postEntryHandler Null or a PostEntryHandler.
     */
    public function __construct($parentPost, $postEntryHandler) 
    {
        $this->m_parentPost = $parentPost;
        $this->m_peh = $postEntryHandler;
    }
    
    /**
     * Render a a HTML form element holding all input fields required.
     * On submit, calls 'postentry.php?post=1'. Needs a javascript
     * function 'preview()' that can create a preview of the post.
     * @return string
     */
    public function renderHtmlForm()
    {
        $html =
           '<form id="postform" method="post" action="postentry.php?post=1" accept-charset="utf-8">
            <table style="margin: auto;">
                <tr><td><span class="fbold">Name</span> (Stammposterregistrierung):</td><td><input type="text" value="' . ($this->m_peh ? $this->m_peh->GetNick() : '') .'" name="' . PostEntryHandler::PARAM_NICK . '" size="20" maxlength="60"/></td></tr>
                <tr><td><span class="fbold">Stammposterpasswort:</span></td><td><input type="password" value="' . ($this->m_peh ? $this->m_peh->GetPassword() : '') . '" name="' . PostEntryHandler::PARAM_PASS . '" size="20" maxlength="60"/></td></tr>
                <tr><td><span class="fbold">Mailadresse</span> (freiwillig):</td><td><input type="text" value="' . ($this->m_peh ? $this->m_peh->GetEmail() : '') . '" name="' . PostEntryHandler::PARAM_EMAIL . '" size="30" maxlength="254"/></td></tr>
                <tr><td>Betreff:</td><td>' . $this->renderHtmlFormTitleInput() . '</td></tr>
                <tr><td colspan="2">Textformattierung: 
                        <img class="addtextstyle" src="img/bold.gif" alt="bold" onclick="formatText(\'b\')"/>
                        <img class="addtextstyle" src="img/italic.gif" alt="italic" onclick="formatText(\'i\')"/>
                        <img class="addtextstyle" src="img/underline.gif" alt="underline" onclick="formatText(\'u\')"/>
                    </td></tr>
                <tr><td colspan="2">' . $this->renderHtmlFormContentTextArea() . '</td></tr>                
                <tr><td colspan="2">' . $this->renderHtmlFormParentPostIdInput() . '</td></tr>
                <tr><td><span class="fbold">URL Link</span> (freiwillig):</td><td><input type="text" value="' . ($this->m_peh ? $this->m_peh->GetLinkUrl() : '') . '" id="post_linkurl" name="' . PostEntryHandler::PARAM_LINKURL . '" size="50" maxlength="250"/></td></tr>
                <tr><td><span class="fbold">URL Link Text</span> (freiwillig):</td><td><input type="text" value="' . ($this->m_peh ? $this->m_peh->GetLinkText() : '') . '" id="post_linktext" name="' . PostEntryHandler::PARAM_LINKTEXT . '" size="20" maxlength="100"/></td></tr>
                <tr><td><span class="fbold">URL eines Bildes</span> (freiwillig):</td><td><input type="text" value="' . ($this->m_peh ? $this->m_peh->GetImgUrl() : '') . '" id="post_imgurl" name="' . PostEntryHandler::PARAM_IMGURL . '" size="50" maxlength="100"/></td></tr>
                <tr><td colspan="2">
                        <input type="submit" value="Eintrag senden"/>
                        <input type="button" value="Vorschau" onclick="preview();"/>
                        <input type="reset" value="Eintrag löschen"/>                    
                    </td></tr>
            </table>
            </form>';
        return $html;
    }
    
    /**
     * Renders a HTML table element holding some hints how to format a post.
     * Relies on a javascript function 'addObject()' to directly add format 
     * objects on to the post.
     * @return string
     */
    public function renderUsageTable()
    {
        $html = 
           '<table style="margin: auto">
                <tr><td colspan="2" class="fbold">Tags für Smilies</td></tr>
                <tr><td colspan="2">
                        <table>
                            <tr>
                                <td><img src="img/yb.gif" alt="YB"/></td><td onclick="addObject(\'[[yb]]\')" class="addtag">[[yb]]</td>
                                <td><img src="img/gelb.gif" alt="Gelbe Karte"/></td><td onclick="addObject(\'[[gelb]]\')" class="addtag">[[gelb]]</td>
                                <td><img src="img/rote.gif" alt="Rote Karte"/></td><td onclick="addObject(\'[[rot]]\')" class="addtag">[[rot]]</td>
                            </tr>
                            <tr>
                                <td><img src="img/bier.gif" alt="Bier"/></td><td onclick="addObject(\'[[bier]]\')" class="addtag">[[bier]]</td>
                                <td><img src="img/wurst.gif" alt="YB-Wurst"/></td><td onclick="addObject(\'[[wurst]]\')" class="addtag">[[wurst]]</td>
                                <td><img src="img/kopf.gif" alt="Kopfschüttel"/></td><td onclick="addObject(\'[[kopf]]\')" class="addtag">[[kopf]]</td>
                            </tr>
                            <tr>
                                <td><img src="img/gsbrille.gif" alt="Gelbe Brille"/></td><td onclick="addObject(\'[[gbrille]]\')" class="addtag">[[gbrille]]</td>
                                <td><img src="img/rosabrille.gif" alt="Rosa Brille"/></td><td onclick="addObject(\'[[rbrille]]\')" class="addtag">[[rbrille]]</td>
                                <td><img src="img/sachverstand.gif" alt="Sachverstand"/></td><td onclick="addObject(\'[[tja]]\')" class="addtag">[[tja]]</td>
                            </tr>
                            <tr>
                                <td><img src="img/hundi.gif" alt="Hundi"/></td><td onclick="addObject(\'[[hundi]]\')" class="addtag">[[hundi]]</td>
                                <td><img src="img/pm.gif" alt="PM"/></td><td onclick="addObject(\'[[pm]]\')" class="addtag">[[pm]]</td>
                                <td><img src="img/!!!.gif" alt="!!!"/></td><td onclick="addObject(\'[[!!!]]\')" class="addtag">[[!!!]]</td>
                            </tr>                            
                        </table>
                </td></tr>
            </table>';
        return $html;
    }
    
    /**
     * Returns the HTML input element for the title. If a PostEntryHandler
     * has been passed upon construction, the title value of that
     * PostEntryHandler will be used as default value. 
     * Else, if a parant post has been passed
     * on construction, a title holding 'Re: <parant-title>' will be used as
     * default value. Else the default value is empty.
     * @return string
     */
    private function renderHtmlFormTitleInput()
    {
        // If a title was already set, use that one
        $title = '';
        if($this->m_peh)
        {
            $title = $this->m_peh->GetTitle();
        }
        if(!$title && $this->m_parentPost)
        {
            $title = $this->m_parentPost->GetTitle();
            if(substr($title, 0, 3) !== 'Re:')
            {
                $title = 'Re: ' . $title;
            }
        }
        $htmlString = '<input type="text" '
            . 'name="' . PostEntryHandler::PARAM_TITLE . '" size="50" '
            . 'maxlength="100" value="'. $title
            . '"/>';
        return $htmlString;
    }
    
    /**
     * Returns a HTML textarea element to hold the actual content of the post.
     * If a PostEntryHandler has been passed upon construction, the content
     * value of that PostEntryHandler will be used as default value.
     * Else, if a parent post has been passed on construction, and that
     * parent post has some content, the content of that post will be
     * framed with [i] .. [/i] what makes the default values.
     * Else, the default value is empty.
     * @return string
     */
    private function renderHtmlFormContentTextArea()
    {
        // Reuse an old content sent before
        $content = '';
        if($this->m_peh)
        {
            $content = $this->m_peh->GetContent();
        }
        if(!$content && $this->m_parentPost && $this->m_parentPost->HasContent())
        {
            $content = '[i]' . $this->m_parentPost->GetContent() . '[/i]';
        }
        $htmlString = '<textarea name="' . PostEntryHandler::PARAM_CONTENT . '" '
            . 'id="post_content" cols="85" rows="10">'
            . $content . '</textarea>';
        return $htmlString;
    }
    
    /**
     * Renders a HTML input that is hidden, holding the idpost value of the
     * parent post (if set during construction), or 0 otherwise.
     * @return string
     */
    private function renderHtmlFormParentPostIdInput()
    {
        $parentPostId = 0;
        if($this->m_parentPost)
        {
            $parentPostId = $this->m_parentPost->GetId();
        }
        $htmlString = '<input type="hidden" '
            . 'name="' . PostEntryHandler::PARAM_PARENTPOSTID . '" value="' . $parentPostId . '"/>';
        return $htmlString;
    }
    
    private $m_parentPost;
    private $m_peh;
}
