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

/**
 * The fields from post_table (and user_table.nick) required to display a
 * search result entry. Provides static method(s) to create such entries.
 */
class SearchResult 
{
    const SORT_FIELD_RELEVANCE = 'relevance';
    const SORT_FIELD_TITLE = 'title';
    const SORT_FIELD_NICK = 'nick';
    const SORT_FIELD_DATE = 'creation_ts';
    
    const SORT_ORDER_ASC = 'ASC';
    const SORT_ORDER_DESC = 'DESC';
    
    const SORT_FIELDS = array(
        self::SORT_FIELD_RELEVANCE => 'Relevanz',
        self::SORT_FIELD_TITLE => 'Titel',
        self::SORT_FIELD_NICK => 'Stammposter',
        self::SORT_FIELD_DATE => 'Datum'
    );
    
    const SORT_ORDERS = array(
        self::SORT_ORDER_ASC => 'Aufsteigend',
        self::SORT_ORDER_DESC => 'Absteigend'
    );
    
    public static function SearchPosts(ForumDb $db, 
            string $searchString, string $nick, 
            int $limit, int $offset, 
            string $sortField, string $sortOrder,
            bool $noReplies) : array
    {
        // check that we have a valid sort field
        if(!array_key_exists($sortField, self::SORT_FIELDS))
        {
            throw new InvalidArgumentException('Invalid sortField: ' . $sortField);
        }
        // and a valid sortorder
        if(!array_key_exists($sortOrder, self::SORT_ORDERS))
        {
            throw new InvalidArgumentException('Invalid sortOrder: ' . $sortOrder);            
        }
        $query = '';
        $params = array();
        if($searchString)
        {
            $mode = 'IN NATURAL LANGUAGE MODE';
            // if we have symbols from a binary search, switch to that mode
            if(preg_match('/\+|\-|<|>|\(.+\)|\*|~/', $searchString))
            {
                $mode = 'IN BOOLEAN MODE';
            }            
            // full-text search if we have a searchString
            $query = 'SELECT p.idpost AS idpost, p.iduser AS iduser, '
                    . 'p.title AS title, p.creation_ts AS creation_ts, '
                    . 'u.nick AS nick, '
                    . 'p.content IS NOT NULL AS has_content, '
                    . 'MATCH (title, content) '
                    . 'AGAINST (:search_string1 ' . $mode . ') AS relevance '
                    . 'FROM post_table p LEFT JOIN user_table u '
                    . 'ON p.iduser = u.iduser '
                    . 'WHERE p.hidden = 0 '
                    . 'AND MATCH (title, content) '
                    . 'AGAINST (:search_string2 ' . $mode . ') ';
            $params[':search_string1'] = $searchString;
            $params[':search_string2'] = $searchString;
            // add an optional nick clause
            if($nick)
            {
                $query.= 'AND u.nick = :nick ';
                $params[':nick'] = $nick;
            }
        }
        else if($nick)
        {
            // user-search only
            $query = 'SELECT p.idpost AS idpost, p.iduser AS iduser, '
                    . 'p.title AS title, p.creation_ts AS creation_ts, '
                    . 'u.nick AS nick, '
                    . 'p.content IS NOT NULL AS has_content '
                    . 'FROM post_table p LEFT JOIN user_table u '
                    . 'ON p.iduser = u.iduser '
                    . 'WHERE p.hidden = 0 '
                    . 'AND u.nick = :nick ';
            $params[':nick'] = $nick;
            // for a user-search only, we have no relevance. Fall back to date
            if($sortField === self::SORT_FIELD_RELEVANCE)
            {
                $sortField = self::SORT_FIELD_DATE;
            }
        }
        // add an optinal no replies clause
        if($noReplies === true)
        {
            $query.= 'AND p.parent_idpost IS NULL ';
        }
        // add the order by clause
        $query.= 'ORDER BY ' . $sortField . ' ' .$sortOrder;
        // and the limit with an offset
        $query.= ' LIMIT :offset, :limit';
        $params[':offset'] = $offset;
        $params[':limit'] = $limit;
        // ready to query
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $results = array();
        while($searchResult = $stmt->fetchObject('SearchResult'))
        {
            array_push($results, $searchResult);
        }
        return $results;
    }
    
    /**
     * Create an instance using one of the static methods. This constructor
     * will assert that the objects holds valid values when it is invoked.
     */    
    private function __construct()
    {
        assert($this->idpost > 0);
        assert(!empty($this->title));
        assert(!empty($this->nick));
        assert(!empty($this->creation_ts));
        $this->creation_ts_dt = new DateTime($this->creation_ts);
    }
    
    /**
     * @return int Field idpost.
     */
    public function GetPostId() : int
    {
        return $this->idpost;
    }
    
    /**
     * @return string Field title.
     */
    public function GetTitle() : string
    {
        return $this->title;
    }
    
    /**
     * @return string Non empty nick (field user_table.nick) who wrote this post. 
     */
    public function GetNick() : string
    {
        return $this->nick;
    }
    
    /**
     * @return DateTime of the field creation_ts.
     */
    public function GetPostTimestamp() : DateTime
    {
        return $this->creation_ts_dt;
    }    
    
    private int $idpost;
    private string $title;
    private int $has_content;
    private int $iduser;
    private string $nick;
    private string $creation_ts; // this is just the value from the corresponding field post_table class="creation_ts
                                    // pdo->fetchObject() injects a string-value
    private DateTime $creation_ts_dt; // the same but converted to a DateTime    
}
