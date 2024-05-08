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
    
    
    const MSG_AUTH_FAIL = 'Ungültiger Stammpostername / Passwort';
    const MSG_AUTH_FAIL_PASSWORD_INVALID = 'Ungültiges Passwort';
    const MSG_AUTH_FAIL_NO_SUCH_USER = 'Unbekannter Stammposter';
    const MSG_AUTH_FAIL_USER_IS_INACTIVE = 'Stammposter ist nicht aktiv';
    const MSG_AUTH_FAIL_USER_IS_DUMMY = 'Stammposter ist ein Dummy';
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
        $this->newPostId = null;
    }
    
    protected function ReadParams() : void
    {
        $this->parentPostId = $this->ReadIntParam(self::PARAM_PARENTPOSTID);
        $this->nick = $this->ReadStringParam(self::PARAM_NICK);
        $this->password = $this->ReadStringParam(self::PARAM_PASS);
        $this->title = $this->ReadStringParam(self::PARAM_TITLE);
        $this->content = $this->ReadStringParam(self::PARAM_CONTENT);
        // Read optional values as plain-text and validate them later
        // so that we can send them back to the user on failure
        $this->email = $this->ReadStringParam(self::PARAM_EMAIL);
        $this->linkUrl = $this->ReadStringParam(self::PARAM_LINKURL);
        $this->linkText = $this->ReadStringParam(self::PARAM_LINKTEXT);
        $this->imgUrl = $this->ReadStringParam(self::PARAM_IMGURL);
    }
    
    protected function ValidateParams() : void
    {
        // validate what we cannot accept null values for:
        $this->ValidateIntParam($this->parentPostId, parent::MSG_GENERIC_INVALID);
        $this->ValidateStringParam($this->nick, self::MSG_AUTH_FAIL);
        $this->ValidateStringParam($this->password, self::MSG_AUTH_FAIL);
        $this->ValidateStringParam($this->title, self::MSG_TITLE_TOO_SHORT, YbForumConfig::MIN_TITLE_LENGTH);
        
        // If the user passed an optional value that does not meet the specs,
        // notify the user (instead of discarding silently)
        if($this->email)
        {
            self::ValidateEmailValue($this->email, 'Der Wert ' . $this->email 
                    .  ' ist keine gültige Mailadresse.');
        }
        if($this->linkUrl)
        {
            $this->ValidateHttpUrlValue($this->linkUrl, 'Der Wert ' . $this->linkUrl 
                    .  ' ist kein gültiger Link. Links müssen mit https://'
                    . ' (oder http://) beginnen.');
        }
        if(($this->linkUrl && !$this->linkText) || ($this->linkText && !$this->linkText))
        {
            throw new InvalidArgumentException('Wird ein URL Link angegeben '
                    . 'muss auch ein Linktext angegeben werden (und umgekehrt).');
        }
        if($this->imgUrl)
        {
            $this->ValidateHttpUrlValue($this->imgUrl, 'Der Wert ' . $this->imgUrl 
                    .  ' ist keine gültige Bild URL. Bild URLs müssen mit https://'
                    . ' (oder http://) beginnen und auf eine Bilddatei'
                    . ' verweisen', true);
        }
    }
    
    /**
     * Build a message containing all values of this post
     * @return string
     */
    private function GetExtendedLogMsg() : string
    {
        $extMsg = 'Title: ' . $this->title;
        if($this->content)
        {
            $extMsg.= '; Content: ' . $this->content;
        }
        if($this->email)
        {
            $extMsg.= '; Email: ' . $this->email;
        }
        if($this->linkUrl)
        {
            $extMsg.= '; LinkUrl: ' . $this->linkUrl;
        }
        if($this->linkText)
        {
            $extMsg.= '; LinkText: ' . $this->linkText;
        }
        if($this->imgUrl)
        {
            $extMsg.= '; ImgUrl: ' . $this->imgUrl;
        }
        return $extMsg;
    }


    protected function HandleRequestImpl(ForumDb $db) : void
    {
        // reset internal values
        $this->newPostId = null;
        // Authenticate
        $logger = new Logger($db);
        // note: The AuthUser of the db will do loggin in case of failure
        $authFailReason = 0;
        $user = $db->AuthUser($this->nick, $this->password, $authFailReason);
        if(!$user)
        {
            // determine a verbose reason for the auth-fail (which is 
            // used in the exception thrown)
            $authFailMsg = self::MSG_AUTH_FAIL;
            if ($authFailReason === ForumDb::AUTH_FAIL_REASON_PASSWORD_INVALID)
            {
                $authFailMsg = self::MSG_AUTH_FAIL_PASSWORD_INVALID;
            }
            else if($authFailReason === ForumDb::AUTH_FAIL_REASON_NO_SUCH_USER)
            {
                $authFailMsg  = self::MSG_AUTH_FAIL_NO_SUCH_USER;
            }
            else if($authFailReason === ForumDb::AUTH_FAIL_REASON_USER_IS_INACTIVE)
            {
                $authFailMsg = self::MSG_AUTH_FAIL_USER_IS_INACTIVE;
            }
            else if($authFailReason === ForumDb::AUTH_FAIL_REASON_USER_IS_DUMMY)
            {
                $authFailMsg = self::MSG_AUTH_FAIL_USER_IS_DUMMY;
            }
            
            // Maybe log the data of the post that has been discarded
            if(YbForumConfig::LOG_EXT_POST_DATA_ON_AUTH_FAILURE)
            {
                $logger->LogMessage(LogType::LOG_EXT_POST_DISCARDED, 
                        $authFailMsg, $this->GetExtendedLogMsg());
            }
            
            throw new InvalidArgumentException($authFailMsg, parent::MSGCODE_AUTH_FAIL);
        }
        // Check if migration is required
        if($user->NeedsMigration())
        {
            $logger->LogMessageWithUserId(LogType::LOG_OPERATION_FAILED_MIGRATION_REQUIRED, $user);
            throw new InvalidArgumentException(self::MSG_MIGRATION_REQUIRED, parent::MSGCODE_AUTH_FAIL);
        }
        if($this->parentPostId === 0)
        {
            $this->newPostId = $db->CreateThread($user, 
                    $this->title, $this->content, $this->email, 
                    $this->linkUrl, $this->linkText, $this->imgUrl, 
                    $this->clientIpAddress);
        }
        else
        {
            $this->newPostId = $db->CreateReplay($this->parentPostId, $user, 
                    $this->title, $this->content, $this->email, 
                    $this->linkUrl, $this->linkText, $this->imgUrl, 
                    $this->clientIpAddress);
        }
    }
    
    public function GetTitle() : ?string
    {
        return $this->title;
    }
    
    public function GetNick() : ?string
    {
        return $this->nick;
    }
    
    public function GetPassword() : ?string
    {
        return $this->password;
    }
    
    public function GetContent() : ?string
    {
        return $this->content;
    }
    
    public function GetEmail() : ?string
    {
        return $this->email;
    }
    
    public function GetLinkUrl() : ?string
    {
        return $this->linkUrl;
    }
    
    public function GetLinkText() : ?string
    {
        return $this->linkText;
    }
    
    public function GetImgUrl() : ?string
    {
        return $this->imgUrl;
    }
    
    public function GetParentPostId() : ?int
    {
        return $this->parentPostId;
    }
    
    public function GetNewPostId() : int
    {
        return $this->newPostId;
    }
    
    private ?int $parentPostId;
    private ?string $title;
    private ?string $nick;
    private ?string $password;
    private ?string $content;
    private ?string $email;
    private ?string $linkUrl;
    private ?string $linkText;
    private ?string $imgUrl;

    private ?int $newPostId;    ///< Set once HandleRequestImpl has executed successfully
}
