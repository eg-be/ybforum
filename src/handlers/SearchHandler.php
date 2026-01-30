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

require_once __DIR__ . '/BaseHandler.php';
require_once __DIR__ . '/../model/SearchResult.php';
require_once __DIR__ . '/../model/SearchDefinitions.php';

/**
 * Handle a Search request
 *
 * @author Elias Gerber
 */
class SearchHandler extends BaseHandler
{
    public const PARAM_SEARCH_STRING = 'search_string';
    public const PARAM_NICK = 'search_nick';
    public const PARAM_RESULT_OFFSET = 'search_result_offset';
    public const PARAM_SORT_FIELD = 'search_sort_field';
    public const PARAM_SORT_ORDER = 'search_sort_order';
    public const PARAM_NO_REPLIES = 'search_no_replies';

    public const MSG_NO_SEARCH_PARAMS_GIVEN = 'Es muss ein Suchbegriff und/oder ein '
        . 'Stammpostername angegeben werden';
    public const MSG_INVALID_SEARCH_STRING = 'Ungültiger Suchstring';
    public const MSG_SEARCH_STRING_TOO_SHORT = 'Der Suchbegriff muss mindestens '
        . YbForumConfig::MIN_SEARCH_LENGTH . ' beinhalten';

    public function __construct()
    {
        parent::__construct();

        // Set defaults explicitly
        $this->m_searchNick = null;
        $this->m_searchString = null;
        $this->m_resultOffset = null;
        $this->m_results = null;
        $this->m_moreRecordsAvailable = false;
        $this->m_sortField = null;
        $this->m_sortOrder = null;
        $this->m_noReplies = false;
    }

    protected function readParams(): void
    {
        $this->m_searchString = self::readStringParam(self::PARAM_SEARCH_STRING);
        $this->m_searchNick = self::readStringParam(self::PARAM_NICK);
        $this->m_resultOffset = self::readIntParam(self::PARAM_RESULT_OFFSET);
        $sortField = self::readStringParam(self::PARAM_SORT_FIELD);
        if ($sortField) {
            $this->m_sortField = SortField::tryFrom($sortField);
        }
        $sortOrder = self::readStringParam(self::PARAM_SORT_ORDER);
        if ($sortOrder) {
            $this->m_sortOrder = SortOrder::tryFrom($sortOrder);
        }
        $noRepliesValue = self::readStringParam(self::PARAM_NO_REPLIES);
        if ($noRepliesValue && $noRepliesValue === self::PARAM_NO_REPLIES) {
            $this->m_noReplies = true;
        } else {
            $this->m_noReplies = false;
        }
    }

    protected function validateParams(): void
    {
        // Either a nick or a search string is required
        if (!$this->m_searchString && !$this->m_searchNick) {
            throw new InvalidArgumentException(self::MSG_NO_SEARCH_PARAMS_GIVEN, parent::MSGCODE_BAD_PARAM);
        }
        // and turn both values into strings, if needed empty string
        if (is_null($this->m_searchNick)) {
            $this->m_searchNick = '';
        }
        if (is_null($this->m_searchString)) {
            $this->m_searchString = '';
        }
        if (!empty($this->m_searchString) && mb_strlen($this->m_searchString, 'UTF-8') < YbForumConfig::MIN_SEARCH_LENGTH) {
            throw new InvalidArgumentException(self::MSG_SEARCH_STRING_TOO_SHORT, parent::MSGCODE_BAD_PARAM);
        }
        // If no offset is given, default to 0 as offset
        if (!$this->m_resultOffset) {
            $this->m_resultOffset = 0;
        }
        // If no sort field / order or an invalid sort order is given, default
        // to the first one that is valid
        $validSortFields = $this->getValidSortFields();
        if (!$this->m_sortField || !in_array($this->m_sortField, $validSortFields)) {
            $this->m_sortField = $validSortFields[0];
        }
        if (!$this->m_sortOrder) {
            $this->m_sortOrder = SortOrder::ORDER_DESC;
        }
    }


    public function getValidSortFields(): array
    {
        $sortFields = [
            SortField::FIELD_DATE,
            SortField::FIELD_TITLE,
            SortField::FIELD_NICK,
        ];
        if ($this->m_searchString) {
            array_unshift($sortFields, SortField::FIELD_RELEVANCE);
        }
        return $sortFields;
    }

    protected function handleRequestImpl(ForumDb $db): void
    {
        // clear any pending results
        $this->m_results = null;
        $this->m_moreRecordsAvailable = false;
        // and fetch new ones:
        // we fetch one more than the limit, to check if there would
        // be more results available
        try {
            $this->m_results = $db->searchPosts(
                $this->m_searchString,
                $this->m_searchNick,
                $this->getLimit() + 1,
                $this->m_resultOffset,
                $this->m_sortField,
                $this->m_sortOrder,
                $this->m_noReplies
            );
        } catch (PDOException $ex) {
            if ($ex->getCode() === '42000') {
                // Syntax error or access violation
                throw new InvalidArgumentException(self::MSG_INVALID_SEARCH_STRING, parent::MSGCODE_BAD_PARAM);
            }
        }
        if (count($this->m_results) > $this->getLimit()) {
            // remove last result and indicate we have more
            array_pop($this->m_results);
            $this->m_moreRecordsAvailable = true;
        } else {
            $this->m_moreRecordsAvailable = false;
        }
    }

    public function getSearchNick(): ?string
    {
        return $this->m_searchNick;
    }

    public function getSearchString(): ?string
    {
        return $this->m_searchString;
    }

    public function hasResults(): bool
    {
        return !is_null($this->m_results);
    }

    public function getResults(): array
    {
        return $this->m_results;
    }

    public function getResultOffset(): int
    {
        return $this->m_resultOffset;
    }

    public function moreRecordsAvailable(): bool
    {
        return $this->m_moreRecordsAvailable;
    }

    public function getNextOffset(): int
    {
        $nextOffset = $this->m_resultOffset + $this->getLimit();
        return $nextOffset;
    }

    public function getPreviousOffset(): int
    {
        $prevOffset = $this->m_resultOffset - $this->getLimit();
        if ($prevOffset < 0) {
            $prevOffset = 0;
        }
        return $prevOffset;
    }

    public function isFirstRecordBlock(): bool
    {
        return $this->m_resultOffset == 0;
    }

    public function getLimit(): int
    {
        return YbForumConfig::MAX_SEARCH_ENTRIES;
    }

    public function getSortField(): SortField
    {
        return $this->m_sortField;
    }

    public function getSortOrder(): SortOrder
    {
        return $this->m_sortOrder;
    }

    public function getNoReplies(): bool
    {
        return $this->m_noReplies;
    }

    private ?string $m_searchNick;
    private ?string $m_searchString;
    private ?int $m_resultOffset;
    private ?SortField $m_sortField;
    private ?SortOrder $m_sortOrder;
    private bool $m_noReplies;

    private ?array $m_results;
    private bool $m_moreRecordsAvailable;
}
