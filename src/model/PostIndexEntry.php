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
 * The fields from post_table (and user_table.nick) required to display an 
 * entry in the index table
 */
class PostIndexEntry
{
    /**
     * Constructed only from pdo, hide constructor
     */
    private function __construct()
    {
        assert($this->idpost > 0);
        assert($this->idthread > 0);
        assert(is_null($this->parent_idpost) || $this->parent_idpost > 0);
        assert(!empty($this->nick));
        assert(empty($this->title));
        assert($this->indent >= 0);
        assert(!empty($this->creation_ts));
        $this->creation_ts_dt = new DateTime($this->creation_ts);
    }
    
    private int $idpost;
    private int $idthread;
    private ?int $parent_idpost;
    private string $nick;
    private string $title;
    private int $indent;
    private string $creation_ts; // this is just the value from the corresponding field post_table creation_ts
                                    // pdo->fetchObject() injects a string-value
    private DateTime $creation_ts_dt; // the same but converted to a DateTime
    private int $has_content; // assigned from pdo
    private int $hidden;

    /**
     * @return int Field idthread
     */
    public function GetThreadId() : int
    {
        return $this->idthread;
    }

    /**
     * @return int Field idpost.
     */
    public function GetPostId() : int
    {
        return $this->idpost;
    }
    
    /**
     * @return ?int Field parent_idpost: id of parent post, or null if no parent
     */
    public function GetParentPostId() : ?int
    {
        return $this->parent_idpost;
    }

    /**
     * @return int Field indent.
     */
    public function GetIndent() : int
    {
        return $this->indent;
    }
    
    /**
     * @return string Field title.
     */
    public function GetTitle() : string
    {
        return $this->title;
    }
    
    /**
     * @return bool True if field content of this post is not null.
     */
    public function HasContent() : bool
    {
        return $this->has_content > 0;
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
    
    /**
     * @return boolean True if field hidden is > 0.
     */
    public function IsHidden() : bool
    {
        return $this->hidden > 0;
    }
}