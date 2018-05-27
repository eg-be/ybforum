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
require_once __DIR__.'/../model/ForumDb.php';
require_once __DIR__.'/../helpers/Logger.php';

/**
 * Read all values required to post a new entry, either as an answer or 
 * as a new thread.
 *
 * @author Elias Gerber
 */
class PostEntryHandler extends BaseHandler
{
    const PARAM_PARENTPOSTID = 'post_parentpostid';
    const PARAM_TITLE = 'post_title';
    const PARAM_NICK = 'post_nick';
    const PARAM_PASS = 'post_pass';
    const PARAM_EMAIL = 'post_email';
    const PARAM_CONTENT = 'post_content';
    const PARAM_LINKURL = 'post_linkurl';
    const PARAM_LINKTEXT = 'post_linktext';
    const PARAM_IMGURL = 'post_imgurl';
    
    const MSG_AUTH_FAIL = 'Es dürfen nur Stammposter schreiben';
    const MSG_MIGRATION_REQUIRED = 'MigrationRequired';
    const MSG_TITLE_TOO_SHORT = 'Betreff muss mindestens ' .
                    YbForumConfig::MIN_TITLE_LENGTH . ' Zeichen enthalten';
    
    public function __construct()
    {
        parent::__construct();
        
        // Set defaults explicitly
        $this->parentPostId = null;
        $this->nick = null;
        $this->password = null;
        $this->title = null;
        $this->content = null;
        $this->email = null;
        $this->linkUrl = null;
        $this->linkText = null;
        $this->imgUrl = null;
    }
    
    protected function ReadParams()
    {
        $this->parentPostId = $this->ReadIntParam(self::PARAM_PARENTPOSTID);
        $this->nick = $this->ReadStringParam(self::PARAM_NICK);
        $this->password = $this->ReadStringParam(self::PARAM_PASS);
        $this->title = $this->ReadStringParam(self::PARAM_TITLE);
        $this->content = $this->ReadStringParam(self::PARAM_CONTENT);
        $this->email = $this->ReadEmailParam(self::PARAM_EMAIL);
        $this->linkUrl = $this->ReadUrlParam(self::PARAM_LINKURL);
        $this->linkText = $this->ReadStringParam(self::PARAM_LINKTEXT);
        $this->imgUrl = $this->ReadUrlParam(self::PARAM_IMGURL);
    }
    
    protected function ValidateParams()
    {
        // validate what we cannot accept null values for:
        $this->ValidateIntParam($this->parentPostId, parent::MSG_GENERIC_INVALID);
        $this->ValidateStringParam($this->nick, self::MSG_AUTH_FAIL);
        $this->ValidateStringParam($this->password, self::MSG_AUTH_FAIL);
        $this->ValidateStringParam($this->title, self::MSG_TITLE_TOO_SHORT, YbForumConfig::MIN_TITLE_LENGTH);
    }
    
    protected function HandleRequestImpl(ForumDb $db)
    {        
        // Authenticate
        // note: The AuthUser of the db will do loggin in case of failure
        $user = $db->AuthUser($this->nick, $this->password);
        if(!$user)
        {
            throw new InvalidArgumentException(self::MSG_AUTH_FAIL, parent::MSGCODE_AUTH_FAIL);
        }
        // Check if migration is required
        if($user->NeedsMigration())
        {
            $logger = new Logger($db);
            $logger->LogMessageWithUserId(Logger::LOG_OPERATION_FAILED_MIGRATION_REQUIRED, $user->GetId());
            throw new InvalidArgumentException(self::MSG_MIGRATION_REQUIRED, parent::MSGCODE_AUTH_FAIL);
        }
        $newPostId = null;
        if($this->parentPostId === 0)
        {
            $newPostId = $db->CreateThread($user, 
                    $this->title, $this->content, $this->email, 
                    $this->linkUrl, $this->linkText, $this->imgUrl, 
                    $this->clientIpAddress);
        }
        else
        {
            $newPostId = $db->CreateReplay($this->parentPostId, $user, 
                    $this->title, $this->content, $this->email, 
                    $this->linkUrl, $this->linkText, $this->imgUrl, 
                    $this->clientIpAddress);
        }

        return $newPostId;

    }
    
    public function GetTitle()
    {
        return $this->title;
    }
    
    public function GetNick()
    {
        return $this->nick;
    }
    
    public function GetPassword()
    {
        return $this->password;
    }
    
    public function GetContent()
    {
        return $this->content;
    }
    
    public function GetEmail()
    {
        return $this->email;
    }
    
    public function GetLinkUrl()
    {
        return $this->linkUrl;
    }
    
    public function GetLinkText()
    {
        return $this->linkText;
    }
    
    public function GetImgUrl()
    {
        return $this->imgUrl;
    }
    
    public function GetParentPostId()
    {
        return $this->parentPostId;
    }
    
    private $parentPostId;
    private $title;
    private $nick;
    private $password;
    private $content;
    private $email;
    private $linkUrl;
    private $linkText;
    private $imgUrl;
}