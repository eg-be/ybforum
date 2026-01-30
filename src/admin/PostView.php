<?php

declare(strict_types=1);

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once __DIR__ . '/../model/ForumDb.php';

/**
 * Description of PostView
 *
 * @author eli
 */
class PostView
{
    public const PARAM_POSTID = 'postview_postid';
    public const PARAM_POSTACTION = 'postview_action';
    public const VALUE_SHOW = 'postview_show';
    public const VALUE_HIDE = 'postview_hide';

    public function __construct()
    {
        $this->m_postId = null;

        $this->m_postId = filter_input(INPUT_POST, self::PARAM_POSTID, FILTER_VALIDATE_INT);
        if (!$this->m_postId) {
            $this->m_postId = null;
        }
    }

    public function handleActionsAndGetResultDiv(ForumDb $db): string
    {
        try {
            $userActionValue = filter_input(INPUT_POST, self::PARAM_POSTACTION, FILTER_UNSAFE_RAW);
            if ($userActionValue === self::VALUE_SHOW || $userActionValue === self::VALUE_HIDE) {
                return $this->handleHideShowAction($db);
            } else {
                return '';
            }
        } catch (InvalidArgumentException $ex) {
            return '<div class="actionFailed">' . $ex->getMessage() . '</div>';
        }
    }

    private function handleHideShowAction(ForumDb $db): string
    {
        $userActionValue = filter_input(INPUT_POST, self::PARAM_POSTACTION, FILTER_UNSAFE_RAW);
        if ($userActionValue === self::VALUE_SHOW && $this->m_postId) {
            $db->setPostVisible($this->m_postId, true);
            return '<div class="actionSucceeded">Post ' . $this->m_postId . ' (und seine Antworten) werden angezeigt</div>';
        } elseif ($userActionValue === self::VALUE_HIDE && $this->m_postId) {
            $db->setPostVisible($this->m_postId, false);
            return '<div class="actionSucceeded">Post ' . $this->m_postId . ' (und seine Antworten) werden ausgeblendet</div>';
        }
        return '';
    }


    private function getToggleHiddenForm(Post $post): string
    {
        $htmlStr = '<form method="post" action="" accept-charset="utf-8">'
                . '<input type="hidden" name="' . self::PARAM_POSTID . '" value="' . $post->getId() . '"/>';
        if ($post->isHidden()) {
            $htmlStr .= '<input type="submit" value="Einblenden"/>'
                        . '<input type="hidden" name="' . self::PARAM_POSTACTION . '" value="' . self::VALUE_SHOW . '"/>';
        } else {
            $htmlStr .= '<input type="submit" value="Ausblenden"/>'
                        . '<input type="hidden" name="' . self::PARAM_POSTACTION . '" value="' . self::VALUE_HIDE . '"/>';
        }
        $htmlStr .= '</form>';
        return $htmlStr;
    }

    public function renderHtmlDiv(ForumDb $db): string
    {
        if (!$this->m_postId) {
            return '<div></div>';
        }
        $post = $db->loadPost($this->m_postId);
        if (!$post) {
            return '<div class="fitalic noTableEntries">Kein Post gefunden mit postId ' . $this->m_postId . '</div>';
        }
        $htmlStr = '<div><table class="actiontable">';
        if ($post->isHidden()) {
            $htmlStr .= '<tr><td>Id:</td><td>' . $post->getId() . '</td><td></td></tr>';
        } else {
            $htmlStr .= '<tr><td>Id:</td><td><a href="../showentry.php?idpost=' . $post->getId() . '">' . $post->getId() . '</a></td><td></td></tr>';
        }
        if ($post->hasParentPost()) {
            $parentPost = $db->loadPost($post->getParentPostId());
            if (!$parentPost->isHidden()) {
                $htmlStr .= '<tr><td>Parent:</td><td><a href="../showentry.php?idpost=' . $parentPost->getId() . '">' . $parentPost->getId() . '</a></td><td></td></tr>';
            } else {
                $htmlStr .= '<tr><td>Parent:</td><td>' . $parentPost->getId() . '</td><td></td></tr>';
            }
        }
        $htmlStr .= '<tr><td>Datum:</td><td>' . $post->getPostTimestamp()->format('d.m.Y H:i:s') . '</td><td></td></tr>';
        $htmlStr .= '<tr><td>Ausgeblendet:</td><td>' . ($post->isHidden() ? 'Ja' : 'Nein') . '</td><td>'
                . $this->getToggleHiddenForm($post)
                . '</td></tr>';
        $htmlStr .= '<tr><td>Stammpostername:</td><td>' . htmlspecialchars($post->getNick()) . ' (' . $post->getUserId() . ')</td><td></td></tr>';
        $htmlStr .= '<tr><td>Titel:</td><td>' . htmlspecialchars($post->getTitle()) . '</td><td></td></tr>';
        $htmlStr .= '<tr><td>Inhalt:</td><td style="white-space: pre-wrap;">' . ($post->hasContent() ? htmlspecialchars($post->getContent()) : '<Kein Inhalt>') . '</td><td></td></tr>';
        $htmlStr .= '<tr><td>Email:</td><td>' . ($post->hasEmail() ? htmlspecialchars($post->getEmail()) : '<null>') . '</td><td></td></tr>';
        $htmlStr .= '<tr><td>Link Url:</td><td>' . ($post->hasLinkUrl() ? htmlspecialchars($post->getLinkUrl()) : '<null>') . '</td><td></td></tr>';
        $htmlStr .= '<tr><td>Link Text:</td><td>' . ($post->hasLinkText() ? htmlspecialchars($post->getLinkText()) : '<null>') . '</td><td></td></tr>';
        $htmlStr .= '<tr><td>Bild Url:</td><td>' . ($post->hasImgUrl() ? htmlspecialchars($post->getImgUrl()) : '<null>') . '</td><td></td></tr>';
        $htmlStr .= '<tr><td>IP:</td><td>' . $post->getIpAddress() . '</td><td></td></tr>';
        $htmlStr .= '</table></div>';
        return $htmlStr;
    }

    private $m_postId;
}
