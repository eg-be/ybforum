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
     * Load a post from the post_table. Searches for a row matching passed
     * 
     * $idPost. Creates a Post object if such a row is found and returns that
     * Post object. Returns NULL if no matching row is found.
     * @param int $idPost
     * @return \Post
     * @throws Exception If a database operation fails.
     */  
    public static function LoadPost(ForumDb $db, int $idPost)
    {
        assert($idPost > 0);
        $query = 'SELECT idpost, idthread, parent_idpost, nick, '
                . 'post_table.iduser AS iduser, '
                . 'title, content, `rank`, indent, '
                . 'creation_ts, '
                . 'post_table.email AS email, '
                . 'link_url, link_text, img_url, old_no, '
                . 'hidden, ip_address '
                . 'FROM post_table '
                . 'LEFT JOIN user_table '
                . 'ON post_table.iduser = user_table.iduser '
                . 'WHERE idpost = :idpost';
        $stmt = $db->prepare($query);
        $stmt->execute(array('idpost' => $idPost));
        $post = $stmt->fetchObject('Post');
        if($post === false)
        {
            $post = null;
        }
        return $post;
    }
  
    /**
     * Create an instance using the static LoadPost method. This constructor
     * will assert that all members have a valid data.
     */
    private function __construct()
    {
        assert(is_int($this->idthread) && $this->idthread > 0);
        assert(is_null($this->parent_idpost) || (is_int($this->parent_idpost) && $this->parent_idpost > 0));
        assert(is_string($this->nick) && !empty($this->nick));
        assert(is_int($this->iduser) && $this->iduser > 0);
        assert(is_string($this->title) && !empty($this->title));
        assert(is_null($this->content) ||(is_string($this->content) && !empty($this->content)));
        assert(is_int($this->rank) && $this->rank >= 0);
        assert(is_int($this->indent) && $this->indent >= 0);
        assert(is_string($this->creation_ts) && !empty($this->creation_ts));
        assert(is_null($this->email) ||(is_string($this->email) && !empty($this->email)));
        assert(is_null($this->link_url) ||(is_string($this->link_url) && !empty($this->link_url)));
        assert(is_null($this->link_text) ||(is_string($this->link_text) && !empty($this->link_text)));
        assert(is_null($this->img_url) ||(is_string($this->img_url) && !empty($this->img_url)));
        assert(is_null($this->old_no) ||(is_int($this->old_no) && $this->old_no > 0));
        assert(is_int($this->hidden));
        assert(is_string($this->ip_address) && !empty($this->ip_address));
        $this->creation_ts = new DateTime($this->creation_ts);
    }
  
    private $idpost;
    private $idthread;
    private $parent_idpost;
    private $nick;
    private $iduser;
    private $title;
    private $content;
    private $rank;
    private $indent;
    private $creation_ts;
    private $email;
    private $link_url;
    private $link_text;
    private $img_url;
    private $old_no;
    private $hidden;
    private $ip_address;
  
    /**
     * @return int Field idpost
     */
    public function GetId()
    {
        return $this->idpost;
    }

    /**
     * @return int Field idthread
     */
    public function GetThreadId()
    {
        return $this->idthread;
    }
    
    /**
     * @return bool True if field hidden has a value > 0.
     */
    public function IsHidden()
    {
        return $this->hidden > 0;
    }
  
    /**
     * @return null or int. field parent_idpost.
     */
    public function GetParentPostId()
    {
        return $this->parent_idpost;
    }
    
    /**
     * @return boolean True if field parent_idpost has a value > 0
     */
    public function HasParentPost()
    {
        return !is_null($this->parent_idpost) && $this->parent_idpost > 0;
    }
  
    /**
     * @return string Non empty field title.
     */
    public function GetTitle()
    {
        return $this->title;
    }
  
    /**
     * @return string Non empty nick (field user_table.nick) who wrote this post. 
     */
    public function GetNick()
    {
        return $this->nick;
    }
    
    /**
     * @return int User who wrote this post (field post_table.iduser)
     */
    public function GetUserId()
    {
        return $this->iduser;
    }
  
    /**
     * @return DateTime of the field creation_ts.
     */
    public function GetPostTimestamp()
    {
        return $this->creation_ts;
    }
  
    /**
     * @return bool True if field content is not null.
     */
    public function HasContent()
    {
        return !is_null($this->content);
    }
  
    /**
     * @return string or null. Field content.
     */
    public function GetContent()
    {
        assert($this->HasContent());
        return $this->content;
    }  
  
    /**
     * @return int or null. Field old_no.
     */
    public function GetOldPostNo()
    {
        assert($this->IsOldPost());
        return $this->old_no;
    }
  
    /**
     * @return bool True if field old_no is not null.
     */
    public function IsOldPost()
    {
        return !is_null($this->old_no);
    }
  
    /**
     * @return bool True if field link_url is not null.
     */
    public function HasLinkUrl()
    {
        return !is_null($this->link_url);
    }
  
    /**
     * @return string or null. Field link_url.
     */
    public function GetLinkUrl()
    {
        assert($this->HasLinkUrl());
        return $this->link_url;
    }
  
    /**
     * @return bool True if field link_text is not null.
     */
    public function HasLinkText()
    {
        return !is_null($this->link_text);
    }
  
    /**
     * @return string or null. Field link_text.
     */
    public function GetLinkText()
    {
        assert($this->HasLinkText());
        return $this->link_text;
    }
  
    /**
     * @return bool True if field img_url is not null.
     */
    public function HasImgUrl()
    {
        return !is_null($this->img_url);
    }
  
    /**
     * @return string or null. Field img_url.
     */
    public function GetImgUrl()
    {
        assert($this->HasImgUrl());
        return $this->img_url;
    }
  
    /**
     * @return bool True if field email is not null.
     */
    public function HasEmail()
    {
        return !is_null($this->email);
    }
  
    /**
     * @return string or email Field email.
     */
    public function GetEmail()
    {
        assert($this->HasEmail());
        return $this->email;
    }
  
    /**
     * @return int Field rank.
     */
    public function GetRank()
    {
        return $this->rank;
    }
  
    /**
     * @return int Field indent.
     */
    public function GetIndent()
    {
        return $this->indent;
    }

    /**
     * @return string Field ip_address.
     */    
    public function GetIpAddress()
    {
       return $this->ip_address; 
    }
}