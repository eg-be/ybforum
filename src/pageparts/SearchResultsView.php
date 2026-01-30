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

require_once __DIR__ . '/../handlers/SearchHandler.php';

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

    private function getHiddenSearchForm(
        string $id,
        int $offset,
        SortField $sortField,
        SortOrder $sortOrder
    ): string {
        $searchString = null;
        $searchNick = null;
        if ($this->m_sh) {
            $searchString = $this->m_sh->getSearchString();
            $searchNick = $this->m_sh->getSearchNick();
        }
        $html = '<form id="' . $id . '" style="display: inline-block;" method="post" action="search.php?search=1" accept-charset="utf-8">';
        $html .= '<input type="hidden" name="'
                . SearchHandler::PARAM_SEARCH_STRING . '" value="'
                . (is_null($searchString) ? '' : htmlspecialchars($searchString)) . '"/>';
        $html .= '<input type="hidden" name="'
                . SearchHandler::PARAM_NICK . '" value="'
                . (is_null($searchNick) ? '' : htmlspecialchars($searchNick)) . '"/>';
        $html .= '<input type="hidden" name="'
                . SearchHandler::PARAM_RESULT_OFFSET . '" value="'
                . $offset
                . '"/>';
        $html .= '<input type="hidden" name="'
                . SearchHandler::PARAM_SORT_FIELD . '" value="'
                . $sortField->value
                . '"/>';
        $html .= '<input type="hidden" name="'
                . SearchHandler::PARAM_SORT_ORDER . '" value="'
                . $sortOrder->value
                . '"/>';
        if ($this->m_sh->getNoReplies()) {
            $html .= '<input type="hidden" name="'
                    . SearchHandler::PARAM_NO_REPLIES . '" value="'
                    . SearchHandler::PARAM_NO_REPLIES
                    . '"/>';
        }
        $html .= '</form>';
        return $html;
    }

    public function renderResultsNavigationDiv(): string
    {
        $html = '<div>';
        if (!$this->m_sh->isFirstRecordBlock()) {
            $html .= $this->getHiddenSearchForm(
                'form_previous_results',
                $this->m_sh->getPreviousOffset(),
                $this->m_sh->getSortField(),
                $this->m_sh->getSortOrder()
            );
            $html .= '<a class="fbold" href="#" '
                    . 'onclick="document.getElementById(\'form_previous_results\').submit()">'
                    . '&lt;-- Vorherige ' . $this->m_sh->getLimit()
                    . ' Resultate &lt;--</a>';
        }


        if ($this->m_sh->moreRecordsAvailable()) {
            $html .= $this->getHiddenSearchForm(
                'form_next_results',
                $this->m_sh->getNextOffset(),
                $this->m_sh->getSortField(),
                $this->m_sh->getSortOrder()
            );
            $html .= '<a class="fbold" style="float: right;" href="#" '
                    . 'onclick="document.getElementById(\'form_next_results\').submit()">'
                    . '--&gt; Nächste ' . $this->m_sh->getLimit()
                    . ' Resultate --&gt;</a>';
        }
        $html .= '</div>';
        return $html;
    }

    public function renderSortDiv(): string
    {
        $html = '<div style="padding-bottom: 1em; padding-top: 1em;">';
        $html .= '<span class="fbold">Sortieren nach: </span>';
        $currentSortField = $this->m_sh->getSortField();
        $validSortFields = $this->m_sh->getValidSortFields();
        foreach ($validSortFields as $sortField) {
            $isCurrentfield = $currentSortField === $sortField;
            $id = 'form_sort_' . $sortField->value;
            $linkClass = '';
            $currentSortSymbol = '';
            // default to sorting DESC
            $sortOrder = SortOrder::ORDER_DESC;
            if ($isCurrentfield) {
                $linkClass = 'class="fitalic" ';
                // determine an icon showing the current sort
                // arrow down for DESC, arrow up for ASC
                // and reverse the sort order, on click
                if ($this->m_sh->getSortOrder() == SortOrder::ORDER_DESC) {
                    $currentSortSymbol = '&#8595;';
                    $sortOrder = SortOrder::ORDER_ASC;
                } else {
                    $currentSortSymbol = '&#8593;';
                    $sortOrder = SortOrder::ORDER_DESC;
                }
            }
            $html .= $this->getHiddenSearchForm(
                $id,
                0,
                $sortField,
                $sortOrder
            );
            $fieldDesc = $sortField->getTranslation();
            $html .= '<a href="#" ' . $linkClass
                    . 'onclick="document.getElementById(\'' . $id . '\').submit()">'
                    . $fieldDesc . ' ' . $currentSortSymbol
                    . '</a> | ';
        }
        $html .= '</div>';
        return $html;
    }

    public function renderResultsDiv(): string
    {
        $html = '<div>';
        $results = $this->m_sh->getResults();
        foreach ($results as $res) {
            $html .= '<p class="nomargin">';
            $html .= '<a href="showentry.php?idpost=' . $res->getPostId() . '">';
            $html .= htmlspecialchars($res->getTitle()) . '</a>';
            $html .= ' - <span class="fbold">' . htmlspecialchars($res->getNick()) . '</span>';
            $html .= ' - ' . $res->getPostTimestamp()->format('d.m.Y H:i:s');
            $html .= '</p>';
        }
        $html .= '</div>';
        return $html;
    }

    private SearchHandler $m_sh;
}
