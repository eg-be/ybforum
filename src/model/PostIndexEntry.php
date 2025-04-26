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
     * Loads thread structures and invokes callback with an array
     * of PostIndexEntry objects: Search for a number of $maxThreads, where
     * the last thread is the thread with $maxThreadId. For every thread, an
     * array is created, holding the thread index entries in form of 
     * PostIndexEntry objects. 
     * As soon as all PostIndexEntry objects
     * for one thread have been placed in the array, the $threadIndexCallback
     * is invoked with the array f PostIndexEntry objects for that thread.
     * PostIndexEntry objects inside an array are sorted by the rank 
     * value (ascending). 
     * Threads are iterated by idthread descending.
     * Hidden posts and their children are not added to the array of 
     * PostIndexEntry objects.
     * @param ForumDb $db Database
     * @param int $maxThreads Maximum number of threads to load index entries
     * for.
     * @param int $maxThreadId Maximum thread id to load index entries for, 
     * the callback will start with the index entries for this thread.
     * @param callable $threadIndexCallback Callback to invoke with an array of
     * ThreadIndexEntry objects.
     * @throws Exception If a database operation fails.
     */  
    public static function LoadThreadIndexEntries(ForumDb $db, 
        int $maxThreads, int $maxThreadId, 
        callable $threadIndexCallback) : void
    {
        $db->LoadThreadIndexEntries($maxThreads, $maxThreadId, $threadIndexCallback);
    }
  
  
    /**
     * Load the replies of a post as PostIndexEntry objects. Returned is
     * an array, ordered by rank. If a hidden post is encountered, its whole
     * subtree (and the post itself) is skipped, and not included in the
     * returned array, except $includeHidden is set to true.
     * 
     * @param ForumDb $db The database
     * @param Post $post A post to load children for.
     * @return array Holding PostIndexEntry objects.
     */
    public static function LoadPostReplies(ForumDb $db, Post $post, bool $includeHidden = false) : array
    {
        return $db->LoadPostReplies($post, $includeHidden);
    }
    
    /**
     * Loads a list of the newest posts.
     * @param ForumDb $db The database.
     * @param int $maxEntries Maximum number of newest entries to load.
     * @return array An array of PostIndexEntry objects. Hold max. 
     * $maxEntries of PostIndexEntry objects, sorted by idpost descending.
     */
    public static function LoadRecentPosts(ForumDb $db, int $maxEntries) : array
    {
        return $db->LoadRecentPosts($maxEntries);
    }
        
    /**
     * Create an instance using one of the static methods. This constructor
     * will assert that the objects holds valid values when it is invoked.
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
    private string $creation_ts; // this is just the value from the corresponding field post_table class="creation_ts
                                    // pdo->fetchObject() injects a string-value
    private DateTime $creation_ts_dt; // the same but converted to a DateTime
    private int $has_content;
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