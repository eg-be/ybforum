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

enum Page
{
    case INDEX;
    case POST_ENTRY;
    case RECENT_ENTRIES;
    case SEARCH;
    case FORMATING;
    case STAMMPOSTER;
    case REGISTER;
    case SHOW_ENTRY;
    case CONTACT;
};

/**
 * Renders the navigation-menu at the top of the page
 */
class TopNavigation
{
    /**
     * list all known pages
     */
    public const PAGES = [
        [Page::INDEX, 'Forum', 'index.php'],
        [Page::POST_ENTRY, 'Beitrag Schreiben', 'postentry.php'],
        [Page::RECENT_ENTRIES, 'Neue Beiträge', 'recent.php'],
        [Page::SEARCH, 'Suchen', 'search.php'],
        [Page::FORMATING, 'Textformatierung', 'textformatierung.php'],
        [Page::STAMMPOSTER, 'Stammposter', 'stammposter.php'],
        [Page::REGISTER, 'Registrieren', 'register.php'],
        [Page::SHOW_ENTRY, '', 'showentry.php'],    // empty title, shall never appear as entry in the top-navigation
        [Page::CONTACT, 'Kontakt', 'contact.php'],
    ];

    public function __construct(?int $postId = null)
    {
        $uri = $_SERVER['REQUEST_URI'];
        // just try to get the pure page-name
        // remove any eventually set parameters
        $paramsIndex = strpos($uri, '?');
        if ($paramsIndex !== false) {
            $uri = substr($uri, 0, $paramsIndex);
        }
        // just take everything after the last slash
        $slashIndex = strrpos($uri, '/');
        if ($slashIndex === false) {
            throw new InvalidArgumentException('Cant parse REQUEST_URI: ' . $uri);
        }
        $pageUri = substr($uri, $slashIndex + 1);
        if (empty($pageUri)) {
            // assume we are on the default-page
            $this->m_page = Page::INDEX;
        } elseif (str_ends_with($pageUri, '.php') !== true) {
            // we are sometimes getting request like
            // recent.php/favicon.ico, recent.php/ybforum.css, recent.php/logo/yb_forum.jpg, etc.
            // sees some browser are reading the received header from 'page.php' and then just
            // append things to the end (?)
            // lets just ignore such cases, there is no reason why any of this php-scripts should
            // be executed from such an url, not?
            http_response_code(404);
            exit;
        } else {
            $pageKnown = false;
            foreach (self::PAGES as $page) {
                if ($page[2] === $pageUri) {
                    $this->m_page = $page[0];
                    $pageKnown = true;
                    break;
                }
            }
            if ($pageKnown === false) {
                throw new InvalidArgumentException('Unknown REQUEST_URI: ' . $uri);
            }
        }
        if ($this->m_page === Page::SHOW_ENTRY) {
            if (is_null($postId)) {
                throw new InvalidArgumentException('$postId must be set for Page::SHOW_ENTRY');
            }
            $this->m_postId = $postId;
        }
    }

    public function renderHtmlDiv(): string
    {
        $htmlStr = '<div class="fullwidthcenter">' . PHP_EOL;
        if ($this->m_page == Page::SHOW_ENTRY) {
            // add a reply option as first entry
            foreach (self::PAGES as $page) {
                if ($page[0] === Page::POST_ENTRY) {
                    $htmlStr .= ' [ <a href="' . $page[2] . '?idparentpost=' . $this->m_postId . '">Antworten</a> ]' . PHP_EOL;
                }
            }
        }
        foreach (self::PAGES as $page) {
            if ($this->m_page != $page[0] // dont link to ourself
                && empty($page[1]) === false // dont show empty entries
                && !($this->m_page == Page::SHOW_ENTRY && $page[0] == Page::POST_ENTRY)) { // dont add 'Beitrag schreiben', we already provided 'Antworten'
                $htmlStr .= ' [ <a href="' . $page[2] . '">' . $page[1] . '</a> ]' . PHP_EOL;
            }
        }
        $htmlStr .= '</div>';
        return $htmlStr;
    }

    public function getPage(): Page
    {
        return $this->m_page;
    }

    private Page $m_page;
    private ?int $m_postId;
}
