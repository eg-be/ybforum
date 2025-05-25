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
 * A Post entry from the database (post_table). All text must be utf-8 encoded 
 * and will hold the values as stored in database (without any htmlspecialchars
 * or similar. Apply those functions later)
 */
class Post
{ 
    /**
     * Constructed only from pdo, hide constructor.
     * This constructor will assert that all members have a valid data
     * and set some internal values.
     */
    protected function __construct()
    {
        assert($this->idpost > 0);
        assert($this->idthread > 0);
        assert(is_null($this->parent_idpost) || $this->parent_idpost > 0);
        assert(!empty($this->nick));
        assert($this->iduser > 0);
        assert(!empty($this->title));
        assert(is_null($this->content) || !empty($this->content));
        assert($this->rank >= 0);
        assert($this->indent >= 0);
        assert(is_string($this->creation_ts) && !empty($this->creation_ts));
        assert(is_null($this->email) ||!empty($this->email));
        assert(is_null($this->link_url) || !empty($this->link_url));
        assert(is_null($this->link_text) || !empty($this->link_text));
        assert(is_null($this->img_url) || !empty($this->img_url));
        assert(is_null($this->old_no) || $this->old_no > 0);
        assert(!empty($this->ip_address));
        $this->creation_ts_dt = new DateTime($this->creation_ts);
    }
  
    protected int $idpost;
    protected int $idthread;
    protected ?int $parent_idpost;
    protected string $nick;
    protected int $iduser;
    protected string $title;
    protected ?string $content;
    protected int $rank;
    protected int $indent;
    protected string $creation_ts; // this is just the value from the corresponding field post_table class="creation_ts
                                    // pdo->fetchObject() injects a string-value
    protected DateTime $creation_ts_dt; // the same but converted to a DateTime
    protected ?string $email;
    protected ?string $link_url;
    protected ?string $link_text;
    protected ?string $img_url;
    protected ?int $old_no;
    protected int $hidden;
    protected string $ip_address;
  
    /**
     * @return int Field idpost
     */
    public function GetId() : int
    {
        return $this->idpost;
    }

    /**
     * @return int Field idthread
     */
    public function GetThreadId() : int
    {
        return $this->idthread;
    }
    
    /**
     * @return bool True if field hidden has a value > 0.
     */
    public function IsHidden() : bool
    {
        return $this->hidden > 0;
    }
  
    /**
     * @return null or int. field parent_idpost.
     */
    public function GetParentPostId() : ?int
    {
        return $this->parent_idpost;
    }
    
    /**
     * @return boolean True if field parent_idpost has a value > 0
     */
    public function HasParentPost() : bool
    {
        return !is_null($this->parent_idpost) && $this->parent_idpost > 0;
    }
  
    /**
     * @return string Non empty field title.
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
     * @return int User who wrote this post (field post_table.iduser)
     */
    public function GetUserId() : int
    {
        return $this->iduser;
    }
  
    /**
     * @return DateTime of the field creation_ts.
     */
    public function GetPostTimestamp() : DateTime
    {
        return $this->creation_ts_dt;
    }
  
    /**
     * @return bool True if field content is not null.
     */
    public function HasContent() : bool
    {
        return !is_null($this->content);
    }
  
    /**
     * @return string or null. Field content.
     */
    public function GetContent() : ?string
    {
        assert($this->HasContent());
        return $this->content;
    }  
  
    /**
     * @return int or null. Field old_no.
     */
    public function GetOldPostNo() : ?int
    {
        assert($this->IsOldPost());
        return $this->old_no;
    }
  
    /**
     * @return bool True if field old_no is not null.
     */
    public function IsOldPost() : bool
    {
        return !is_null($this->old_no);
    }
  
    /**
     * @return bool True if field link_url is not null.
     */
    public function HasLinkUrl() : bool
    {
        return !is_null($this->link_url);
    }
  
    /**
     * @return string or null. Field link_url.
     */
    public function GetLinkUrl() : ?string
    {
        assert($this->HasLinkUrl());
        return $this->link_url;
    }
  
    /**
     * @return bool True if field link_text is not null.
     */
    public function HasLinkText() : bool
    {
        return !is_null($this->link_text);
    }
  
    /**
     * @return string or null. Field link_text.
     */
    public function GetLinkText() : ?string
    {
        assert($this->HasLinkText());
        return $this->link_text;
    }
  
    /**
     * @return bool True if field img_url is not null.
     */
    public function HasImgUrl() : bool
    {
        return !is_null($this->img_url);
    }
  
    /**
     * @return string or null. Field img_url.
     */
    public function GetImgUrl() : ?string
    {
        assert($this->HasImgUrl());
        return $this->img_url;
    }
  
    /**
     * @return bool True if field email is not null.
     */
    public function HasEmail() : bool
    {
        return !is_null($this->email);
    }
  
    /**
     * @return string or email Field email.
     */
    public function GetEmail() : ?string
    {
        assert($this->HasEmail());
        return $this->email;
    }
  
    /**
     * @return int Field rank.
     */
    public function GetRank() : int
    {
        return $this->rank;
    }
  
    /**
     * @return int Field indent.
     */
    public function GetIndent() : int
    {
        return $this->indent;
    }

    /**
     * @return string Field ip_address.
     */    
    public function GetIpAddress() : string
    {
       return $this->ip_address; 
    }

    /**
     * True, if all values are equal
     */
    public function equals(self $other) : bool
    {
        return $this->idpost === $other->idpost
            && $this->idthread === $other->idthread
            && $this->parent_idpost === $other->parent_idpost
            && $this->nick === $other->nick
            && $this->iduser === $other->iduser
            && $this->title === $other->title
            && $this->content === $other->content
            && $this->rank === $other->rank
            && $this->indent === $other->indent
            && $this->creation_ts_dt == $other->creation_ts_dt
            && $this->email === $other->email
            && $this->link_url === $other->link_url
            && $this->link_text === $other->link_text
            && $this->img_url === $other->img_url
            && $this->old_no === $other->old_no
            && $this->hidden === $other->hidden
            && $this->ip_address === $other->ip_address;
            ;
    }    
}