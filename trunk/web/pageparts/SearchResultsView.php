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
 * Renders the Search results
 *
 * @author Elias Gerber
 */
class SearchResultsView 
{
    public function __construct(SearchHandler $searchHandler)
    {
        $this->m_sh = $searchHandler;
    }
    
    private function GetHiddenSearchForm(string $id, int $offset,
            string $sortField, string $sortOrder)
    {
        $html= '<form id="' . $id .'" style="display: inline-block;" method="post" action="search.php?search=1" accept-charset="utf-8">';
        $html.= '<input type="hidden" name="' 
                . SearchHandler::PARAM_SEARCH_STRING . '" value="'
                . $this->m_sh->GetSearchString() . '"/>';
        $html.= '<input type="hidden" name="'
                . SearchHandler::PARAM_NICK . '" value="'
                . $this->m_sh->GetSearchNick() . '"/>';
        $html.= '<input type="hidden" name="'
                . SearchHandler::PARAM_RESULT_OFFSET . '" value="'
                . $offset
                . '"/>';
        $html.= '<input type="hidden" name="'
                . SearchHandler::PARAM_SORT_FIELD . '" value="'
                . $sortField
                . '"/>';
        $html.= '<input type="hidden" name="'
                . SearchHandler::PARAM_SORT_ORDER . '" value="'
                . $sortOrder
                . '"/>';
        $html.= '</form>';
        return $html;
    }
    
    public function RenderResultsNavigationDiv()
    {
        $html = '<div>';
        if(!$this->m_sh->IsFirstRecordBlock())
        {
            $html.= $this->GetHiddenSearchForm('form_previous_results', 
                    $this->m_sh->GetPreviousOffset(),
                    $this->m_sh->GetSortField(), 
                    $this->m_sh->GetSortOrder());
            $html.= '<a class="fbold" href="#" '
                    . 'onclick="document.getElementById(\'form_previous_results\').submit()">'
                    . '&lt;-- Vorherige ' . $this->m_sh->GetLimit() 
                    . ' Resultate &lt;--</a>';
        }
        
        
        if($this->m_sh->MoreRecordsAvailable())
        {
            $html.= $this->GetHiddenSearchForm('form_next_results', 
                    $this->m_sh->GetNextOffset(),
                    $this->m_sh->GetSortField(),
                    $this->m_sh->GetSortOrder());            
            $html.= '<a class="fbold" style="float: right;" href="#" '
                    . 'onclick="document.getElementById(\'form_next_results\').submit()">'
                    . '--&gt; Nächste ' . $this->m_sh->GetLimit() 
                    . ' Resultate --&gt;</a>';            
        }
        $html.= '</div>';
        return $html;
    }
    
    public function RenderSortDiv()
    {
        $html = '<div style="padding-bottom: 1em; padding-top: 1em;">';
        $html.= '<span class="fbold">Sortieren nach: </span>';
        $currentSortField = $this->m_sh->GetSortField();
        $validSortFields = $this->m_sh->GetValidSortFields();
        $sortFieldsWithDesc = SearchResult::SORT_FIELDS;
        foreach($validSortFields as $sortField)
        {
            $isCurrentfield = $currentSortField === $sortField;
            $id = 'form_sort_' . $sortField;
            $linkClass = '';
            $currentSortSymbol = '';
            // default to sorting DESC
            $sortOrder = SearchResult::SORT_ORDER_DESC;
            if($isCurrentfield)
            {
                $linkClass = 'class="fitalic" ';
                // determine an icon showing the current sort
                // arrow down for DESC, arrow up for ASC
                // and reverse the sort order, on click
                if($this->m_sh->GetSortOrder() == SearchResult::SORT_ORDER_DESC)
                {
                    $currentSortSymbol = '&#8595;';
                    $sortOrder = SearchResult::SORT_ORDER_ASC;
                }
                else
                {
                    $currentSortSymbol = '&#8593;';
                    $sortOrder = SearchResult::SORT_ORDER_DESC;
                }                    
            }
            $html.= $this->GetHiddenSearchForm($id, 0, $sortField,
                    $sortOrder);
            $fieldDesc = $sortFieldsWithDesc[$sortField];
            $html.= '<a href="#"' . $linkClass
                    . 'onclick="document.getElementById(\'' . $id . '\').submit()">'                    
                    . $fieldDesc . ' ' . $currentSortSymbol
                    . '</a> | ';
        }
        $html.= '</div>';
        return $html;
    }
    
    public function RenderResultsDiv()
    {
        $html = '<div>';
        $results = $this->m_sh->GetResults();
        foreach($results as $res)
        {
            $html.= '<p class="nomargin">';
            $html.= '<a href="showentry.php?idpost=' . $res->GetPostId(). '">';
            $html.= $res->GetTitle() . '</a>';
            $html.= ' - <span class="fbold">' . $res->GetNick() . '</span>';
            $html.= ' - ' . $res->GetPostTimestamp()->format('d.m.Y H:i:s');
            $html.= '</p>';
        }
        $html.= '</div>';        
        return $html;
    }
    
    private $m_sh;
}
