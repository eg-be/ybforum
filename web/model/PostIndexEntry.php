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
        callable $threadIndexCallback)
    {
        assert($maxThreads > 0);
        assert($maxThreadId > 0);
        assert($db->IsConnected());
        $minThreadId = $maxThreadId - $maxThreads;        
        $query = 'SELECT idpost, idthread, parent_idpost, nick, '
                . 'title, indent, creation_ts, '
                . 'content IS NOT NULL AS has_content,'
                . 'hidden '
                . 'FROM post_table LEFT JOIN '
                . 'user_table ON post_table.iduser = user_table.iduser '
                . 'WHERE idthread <= :maxThreadId AND idthread > :minThreadId '
                . 'ORDER BY idthread DESC, `rank`';
        $stmt = $db->prepare($query);
        $stmt->execute(array(':maxThreadId' => $maxThreadId, 
            ':minThreadId' => $minThreadId));
        $threadIndexEntries = array();
        $lastThreadId = 0;
        $inHiddenPath = false;
        $hiddenStartedAtIndent = 0;        
        while($indexEntry = $stmt->fetchObject('PostIndexEntry'))
        {
            // whenever a new thread starts, notify the user about the 
            // previous entries.
            if($indexEntry->idthread !== $lastThreadId && $lastThreadId !== 0)
            {
                if(!empty($threadIndexEntries))
                {
                    call_user_func($threadIndexCallback, $threadIndexEntries);
                }
                $threadIndexEntries = array();
            }
            $lastThreadId = $indexEntry->idthread;
            if($inHiddenPath && $indexEntry->indent <= $hiddenStartedAtIndent)
            {
                // might be leaving the hidden path
                $inHiddenPath = false;
            }
            if($indexEntry->hidden > 0 && $inHiddenPath === false)
            {
                // entering a hidden path, discard until we are out of it
                $inHiddenPath = true;
                $hiddenStartedAtIndent = $indexEntry->indent;
            }
            if(!$inHiddenPath)
            {
                array_push($threadIndexEntries, $indexEntry);
            }          
        }
        // dont forget the rest
        if(!empty($threadIndexEntries))
        {
            call_user_func($threadIndexCallback, $threadIndexEntries);
        }
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
    public static function LoadPostReplies(ForumDb $db, Post $post, bool $includeHidden = false)
    {
        $query = 'SELECT idpost, idthread, parent_idpost, nick, '
                . 'title, indent, creation_ts, '
                . 'content IS NOT NULL AS has_content,'
                . 'hidden '
                . 'FROM post_table LEFT JOIN '
                . 'user_table ON post_table.iduser = user_table.iduser '
                . 'WHERE idthread = :idthread AND indent > :indent AND `rank` > :rank '
                . 'ORDER BY idthread DESC, `rank`';
        $stmt = $db->prepare($query);
        $stmt->execute(array(':idthread' => $post->GetThreadId(), 
                ':indent' => $post->GetIndent(),
                ':rank' => $post->GetRank()));
        $replies = array();
        $childOfOurPost = true;
        $ourPostIndent = $post->GetIndent();
        $ourPostId = $post->GetId();
        $inHiddenPath = false;
        $hiddenStartedAtIndent = 0;
        while($indexEntry = $stmt->fetchObject('PostIndexEntry'))
        {
            // check if entry with indent + 1 are direct ancestors of our post:
            if($indexEntry->indent === $ourPostIndent + 1)
            {
                $childOfOurPost = ($indexEntry->parent_idpost === $ourPostId);
            }
            if($childOfOurPost)
            {
                // we are leaving if we have reached the same indent again
                // as we have entered the hidden path                
                if($inHiddenPath && $indexEntry->indent <= $hiddenStartedAtIndent)
                {
                    $inHiddenPath = false;
                }                  
                // Check if we are entering a hidden path part (and not ready in)
                if($indexEntry->hidden > 0 && $inHiddenPath === false)
                {
                    $inHiddenPath = true;
                    $hiddenStartedAtIndent = $indexEntry->indent;
                }
                if(!$inHiddenPath || $includeHidden)
                {
                    array_push($replies, $indexEntry);
                }
            }
        }
        return $replies;
    }  
    
    /**
     * Loads a list of the newest posts.
     * @param ForumDb $db The database.
     * @param int $maxEntries Maximum number of newest entries to load.
     * @return array An array of PostIndexEntry objects. Hold max. 
     * $maxEntries of PostIndexEntry objects, sorted by idpost descending.
     */
    public static function LoadRecentPosts(ForumDb $db, int $maxEntries)
    {
        $query = 'SELECT idpost, idthread, parent_idpost, nick, '
                . 'title, indent, creation_ts, '
                . 'content IS NOT NULL AS has_content,'
                . 'hidden '
                . 'FROM post_table LEFT JOIN '
                . 'user_table ON post_table.iduser = user_table.iduser '
                . 'WHERE hidden = 0 '
                . 'ORDER BY idpost DESC LIMIT :maxEntries';
        $stmt = $db->prepare($query);
        $stmt->execute(array( ':maxEntries' => $maxEntries));
        $replies = array();
        while($indexEntry = $stmt->fetchObject('PostIndexEntry'))
        {
            array_push($replies, $indexEntry);
        }
        return $replies;
    }
        
    /**
     * Create an instance using one of the static methods. This constructor
     * will assert that the objects holds valid values when it is invoked.
     */
    private function __construct()
    {
        assert(is_int($this->idpost) && $this->idpost > 0);
        assert(is_int($this->idthread) && $this->idthread > 0);
        assert(is_null($this->parent_idpost) ||(is_int($this->parent_idpost) && $this->parent_idpost > 0));
        assert(is_string($this->nick) && !empty($this->nick));
        assert(is_string($this->title) && !empty($this->title));
        assert(is_int($this->indent) && $this->indent >= 0);
        assert(is_string($this->creation_ts) && !empty($this->creation_ts));
        assert(is_int($this->has_content));
        assert(is_int($this->hidden));    
        $this->creation_ts = new DateTime($this->creation_ts);
    }
    
    private $idpost;
    private $idthread;
    private $parent_idpost;
    private $nick;
    private $title;
    private $indent;
    private $creation_ts;
    private $has_content;
    private $hidden;

    /**
     * @return int Field idpost.
     */
    public function GetPostId()
    {
        return $this->idpost;
    }
    
    /**
     * @return int Field indent.
     */
    public function GetIndent()
    {
        return $this->indent;
    }
    
    /**
     * @return string Field title.
     */
    public function GetTitle()
    {
        return $this->title;
    }
    
    /**
     * @return bool True if field content of this post is not null.
     */
    public function HasContent()
    {
        return $this->has_content > 0;
    }
    
    /**
     * @return string Non empty nick (field user_table.nick) who wrote this post. 
     */
    public function GetNick()
    {
        return $this->nick;
    }

    /**
     * @return DateTime of the field creation_ts.
     */
    public function GetPostTimestamp()
    {
        return $this->creation_ts;
    }
    
    /**
     * @return boolean True if field hidden is > 0.
     */
    public function IsHidden()
    {
        return $this->hidden > 0;
    }
}