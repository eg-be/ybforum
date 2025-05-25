<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__.'/../model/ForumDb.php';

/**
 * Description of PostView
 *
 * @author eli
 */
class PostView {
    
    const PARAM_POSTID = 'postview_postid';
    const PARAM_POSTACTION = 'postview_action';
    const VALUE_SHOW = 'postview_show';
    const VALUE_HIDE = 'postview_hide';    
    
    public function __construct()
    {
        $this->m_postId = null;
        
        $this->m_postId = filter_input(INPUT_POST, self::PARAM_POSTID, FILTER_VALIDATE_INT);
        if(!$this->m_postId)
        {
            $this->m_postId = null;
        }
    }    
    
    public function HandleActionsAndGetResultDiv(ForumDb $db) : string
    {
        try
        {
            $userActionValue = filter_input(INPUT_POST, self::PARAM_POSTACTION, FILTER_UNSAFE_RAW);
            if($userActionValue === self::VALUE_SHOW || $userActionValue === self::VALUE_HIDE)
            {
                return $this->HandleHideShowAction($db);
            }
            else
            {
                return '';
            }
        }
        catch(InvalidArgumentException $ex)
        {
            return '<div class="actionFailed">' . $ex->getMessage() . '</div>';
        }
    }
    
    private function HandleHideShowAction(ForumDb $db) : string
    {
        $userActionValue = filter_input(INPUT_POST, self::PARAM_POSTACTION, FILTER_UNSAFE_RAW);
        if($userActionValue === self::VALUE_SHOW && $this->m_postId)
        {
            $db->SetPostVisible($this->m_postId, true);
            return '<div class="actionSucceeded">Post ' . $this->m_postId . ' (und seine Antworten) werden angezeigt</div>';
        }
        else if($userActionValue === self::VALUE_HIDE && $this->m_postId)
        {
            $db->SetPostVisible($this->m_postId, false);
            return '<div class="actionSucceeded">Post ' . $this->m_postId . ' (und seine Antworten) werden ausgeblendet</div>';
        }
        return '';
    }
    
    
    private function GetToggleHiddenForm(Post $post) : string
    {
        $htmlStr = '<form method="post" action="" accept-charset="utf-8">'
                . '<input type="hidden" name="' . self::PARAM_POSTID . '" value="' . $post->GetId() . '"/>';
        if($post->IsHidden())
        {
            $htmlStr.= '<input type="submit" value="Einblenden"/>'
                        . '<input type="hidden" name="' . self::PARAM_POSTACTION . '" value="' . self::VALUE_SHOW . '"/>';
        }
        else
        {
            $htmlStr.= '<input type="submit" value="Ausblenden"/>'
                        . '<input type="hidden" name="' . self::PARAM_POSTACTION . '" value="' . self::VALUE_HIDE . '"/>';
        }
        $htmlStr.= '</form>';
        return $htmlStr;
    }    
    
    public function RenderHtmlDiv(ForumDb $db) : string
    {
        if(!$this->m_postId)
        {
            return '<div></div>';
        }
        $post = $db->LoadPost($this->m_postId);
        if(!$post)
        {
            return '<div class="fitalic noTableEntries">Kein Post gefunden mit postId ' . $this->m_postId . '</div>';
        }
        $htmlStr = '<div><table class="actiontable">';
        if($post->IsHidden())
        {
            $htmlStr.= '<tr><td>Id:</td><td>' . $post->GetId() . '</td><td></td></tr>';
        }
        else
        {
            $htmlStr.= '<tr><td>Id:</td><td><a href="../showentry.php?idpost=' . $post->GetId() . '">' . $post->GetId() . '</a></td><td></td></tr>';            
        }
        if($post->HasParentPost())
        {
            $parentPost = $db->LoadPost($post->GetParentPostId());
            if(!$parentPost->IsHidden())
            {
                $htmlStr.= '<tr><td>Parent:</td><td><a href="../showentry.php?idpost=' . $parentPost->GetId() . '">' . $parentPost->GetId() . '</a></td><td></td></tr>';
            }
            else
            {
                $htmlStr.= '<tr><td>Parent:</td><td>' . $parentPost->GetId() . '</td><td></td></tr>';                
            }
        }
        $htmlStr.= '<tr><td>Datum:</td><td>' . $post->GetPostTimestamp()->format('d.m.Y H:i:s') . '</td><td></td></tr>';
        $htmlStr.= '<tr><td>Ausgeblendet:</td><td>' . ($post->IsHidden() ? 'Ja' : 'Nein') . '</td><td>'
                . $this->GetToggleHiddenForm($post)
                . '</td></tr>';
        $htmlStr.= '<tr><td>Stammpostername:</td><td>' . htmlspecialchars($post->GetNick()) . ' (' . $post->GetUserId() . ')</td><td></td></tr>';
        $htmlStr.= '<tr><td>Titel:</td><td>' . htmlspecialchars($post->GetTitle()) . '</td><td></td></tr>';
        $htmlStr.= '<tr><td>Inhalt:</td><td style="white-space: pre-wrap;">' . ( $post->HasContent() ? htmlspecialchars($post->GetContent()) : '<Kein Inhalt>' ) . '</td><td></td></tr>';
        $htmlStr.= '<tr><td>Email:</td><td>' . ( $post->HasEmail() ? htmlspecialchars($post->GetEmail()) : '<null>') . '</td><td></td></tr>';
        $htmlStr.= '<tr><td>Link Url:</td><td>' . ( $post->HasLinkUrl() ? htmlspecialchars($post->GetLinkUrl()) : '<null>') . '</td><td></td></tr>';
        $htmlStr.= '<tr><td>Link Text:</td><td>' . ( $post->HasLinkText() ? htmlspecialchars($post->GetLinkText()) : '<null>') . '</td><td></td></tr>';
        $htmlStr.= '<tr><td>Bild Url:</td><td>' . ( $post->HasImgUrl() ? htmlspecialchars($post->GetImgUrl()) : '<null>') . '</td><td></td></tr>';
        $htmlStr.= '<tr><td>IP:</td><td>' . $post->GetIpAddress() . '</td><td></td></tr>';
        $htmlStr.= '</table></div>';
        return $htmlStr;
    }
    
    private $m_postId;
}
