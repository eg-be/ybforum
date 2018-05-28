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

require_once __DIR__.'/../handlers/SearchHandler.php';

/**
 * Prints a form to handle search inputs.
 * Adds the hidden fields depending on previously searched values.
 * 
 * @author Elias Gerber
 */
class SearchForm {
    
    public function __construct($searchHandler)
    {
        $this->m_sh = $searchHandler;
    }
    
    public function RenderHtmlForm()
    {
        $html = 
           '<form method="post" action="search.php?search=1" accept-charset="utf-8">
            <table style="margin: auto;">
                <tr><td class="fbold">Suchbegriff:</td><td><input type="text" value="' . ($this->m_sh ? $this->m_sh->GetSearchString() : '') .'" name="' . SearchHandler::PARAM_SEARCH_STRING . '" size="50" maxlength="100"/></td></tr>
                <tr><td class="fbold">Stammposter:</td><td><input type="text" value="' . ($this->m_sh ? $this->m_sh->GetSearchNick() : '') .'" name="' . SearchHandler::PARAM_NICK . '" size="20" maxlength="60"/></td></tr>
                <tr><td class="fbold">Keine Antworten:</td>';
        $html.= '<td><input type="checkbox" value="' . SearchHandler::PARAM_NO_REPLIES .'" name="' . SearchHandler::PARAM_NO_REPLIES . '"';
        if($this->m_sh && $this->m_sh->GetNoReplies() === true)
        {
            $html.= ' checked';
        }
        $html.= '/></td></tr>
                <tr><td colspan="2">
                        <input type="submit" value="Suchen"/>
                    </td></tr>
            </table>
            </form>';
        return $html;
    }
    
    private $m_sh;
}
