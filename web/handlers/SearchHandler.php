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

require_once __DIR__.'/BaseHandler.php';
require_once __DIR__.'/../model/SearchResult.php';

/**
 * Handle a Search request
 *
 * @author Elias Gerber
 */
class SearchHandler extends BaseHandler
{
    const PARAM_SEARCH_STRING = 'search_string';
    const PARAM_NICK = 'search_nick';
    const PARAM_RESULT_OFFSET = 'search_result_offset';
    const PARAM_SORT_FIELD = 'search_sort_field';
    const PARAM_SORT_ORDER = 'search_sort_order';
    
    const MSG_NO_SEARCH_PARAMS_GIVEN = 'Es muss ein Suchbegriff und/oder ein '
            . 'Stammpostername angegeben werden';
    const MSG_INVALID_SEARCH_STRING = 'Ungültiger Suchstring';
    const MSG_SEARCH_STRING_TOO_SHORT = 'Der Suchbegriff muss mindestens '
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
    }
    
    protected function ReadParams()
    {
        $this->m_searchString = $this->ReadStringParam(self::PARAM_SEARCH_STRING);
        $this->m_searchNick = $this->ReadStringParam(self::PARAM_NICK);
        $this->m_resultOffset = $this->ReadIntParam(self::PARAM_RESULT_OFFSET);
        $this->m_sortField = $this->ReadStringParam(self::PARAM_SORT_FIELD);
        $this->m_sortOrder = $this->ReadStringParam(self::PARAM_SORT_ORDER);
    }
    
    protected function ValidateParams()
    {
        // Either a nick or a search string is required
        if(!$this->m_searchString && !$this->m_searchNick)
        {
            throw new InvalidArgumentException(self::MSG_NO_SEARCH_PARAMS_GIVEN, parent::MSGCODE_BAD_PARAM);
        }
        // and turn both values into strings, if needed empty string
        if(is_null($this->m_searchNick))
        {
            $this->m_searchNick = '';
        }
        if(is_null($this->m_searchString))
        {
            $this->m_searchString = '';
        }
        if(!empty($this->m_searchString) && mb_strlen($this->m_searchString, 'UTF-8') < YbForumConfig::MIN_SEARCH_LENGTH)
        {
            throw new InvalidArgumentException(self::MSG_SEARCH_STRING_TOO_SHORT, parent::MSGCODE_BAD_PARAM);
        }
        // If no offset is given, default to 0 as offset
        if(!$this->m_resultOffset)
        {
            $this->m_resultOffset = 0;
        }
        // If no sort field / order or an invalid sort order is given, default
        // to the first one that is valid
        $validSortFields = $this->GetValidSortFields();
        if(!$this->m_sortField || !in_array($this->m_sortField, $validSortFields))
        {
            $this->m_sortField = $validSortFields[0];
        }
        if(!$this->m_sortOrder || 
                !($this->m_sortOrder === 'ASC' || $this->m_sortOrder === 'DESC'))
        {
            $this->m_sortOrder = 'DESC';
        }
    }
    
    
    public function GetValidSortFields()
    {
        $sortFields = array(
            SearchResult::SORT_FIELD_DATE,
            SearchResult::SORT_FIELD_TITLE,
            SearchResult::SORT_FIELD_NICK
        );
        if($this->m_searchString)
        {
            array_unshift($sortFields, SearchResult::SORT_FIELD_RELEVANCE);
        }
        return $sortFields;
    }
    
    protected function HandleRequestImpl(ForumDb $db) 
    {
        // clear any pending results
        $this->m_results = null;
        $this->m_moreRecordsAvailable = false;
        // and fetch new ones:
        // we fetch one more than the limit, to check if there would
        // be more results available
        try
        {
            $this->m_results = SearchResult::SearchPosts($db, 
                    $this->m_searchString, 
                    $this->m_searchNick, 
                    $this->GetLimit() + 1, 
                    $this->m_resultOffset,
                    $this->m_sortField, 
                    $this->m_sortOrder);
        }
        catch(PDOException $ex)
        {
            if($ex->getCode() === '42000')
            {
                // Syntax error or access violation
                throw new InvalidArgumentException(self::MSG_INVALID_SEARCH_STRING, parent::MSGCODE_BAD_PARAM);                
            }
        }
        if(sizeof($this->m_results) > $this->GetLimit())
        {
            // remove last result and indicate we have more
            array_pop($this->m_results);
            $this->m_moreRecordsAvailable = true;
        }
        else
        {
            $this->m_moreRecordsAvailable = false;
        }
    }
    
    public function GetSearchNick()
    {
        return $this->m_searchNick;
    }
    
    public function GetSearchString()
    {
        return $this->m_searchString;
    }
    
    public function HasResults()
    {
        return !is_null($this->m_results);
    }
    
    public function GetResults()
    {
        return $this->m_results;
    }
    
    public function GetResultOffset()
    {
        return $this->m_resultOffset;
    }
    
    public function MoreRecordsAvailable()
    {
        return $this->m_moreRecordsAvailable;
    }
    
    public function GetNextOffset()
    {
        $nextOffset = $this->m_resultOffset + $this->GetLimit();
        return $nextOffset;
    }
    
    public function GetPreviousOffset()
    {
        $prevOffset = $this->m_resultOffset - $this->GetLimit();
        if($prevOffset < 0)
        {
            $prevOffset = 0;
        }
        return $prevOffset;
    }
    
    public function IsFirstRecordBlock()
    {
        return $this->m_resultOffset == 0;
    }
    
    public function GetLimit()
    {
        return YbForumConfig::MAX_SEARCH_ENTRIES;
    }
    
    public function GetSortField()
    {
        return $this->m_sortField;        
    }
    
    public function GetSortOrder()
    {
        return $this->m_sortOrder;
    }
    
    private $m_searchNick;
    private $m_searchString;
    private $m_resultOffset;
    private $m_sortField;
    private $m_sortOrder;
    
    private $m_results;
    private $m_moreRecordsAvailable;
}
