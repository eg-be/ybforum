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
 * Updates the password for a user. If the user needs to migrate, 
 * the user is migrated (password set and activated, email is left
 * with the email already set). 
 * Else only the password is updated.
 *
 * @author Elias Gerber
 */
class UpdatePasswordHandler extends BaseHandler
{
    
    const PARAM_NEWPASS = 'stammposter_newpass';
    const PARAM_CONFIRMNEWPASS = 'stammposter_confirmpass';
    
    const MSG_PASSWORDS_NOT_MATCH = 'Passwort und Bestätigung stimmen nicht überein.';
    const MSG_PASSWORD_TOO_SHORT = 'Neues Passwort muss mindestens ' .
                    YbForumConfig::MIN_PASSWWORD_LENGTH . ' Zeichen enthalten.';
    const MSG_DUMMY_USER = 'Stammposter ist ein Dummy';
    const MSG_USER_INACTIVE = 'Stammposter ist deaktiviert';    
    
    public function __construct(User $user)
    {
        parent::__construct();
        
        $this->user = $user;
        
        // Set defaults explicitly
        $this->clientIpAddress = null;
        $this->newPassword = null;
        $this->confirmNewPassword = null;
    }
    
    protected function ReadParams() : void
    {
        // Read params
        $this->newPassword = $this->ReadStringParam(self::PARAM_NEWPASS);
        $this->confirmNewPassword = $this->ReadStringParam(self::PARAM_CONFIRMNEWPASS);
    }
    
    protected function ValidateParams() : void
    {
        $this->ValidateStringParam($this->newPassword, self::MSG_PASSWORD_TOO_SHORT, YbForumConfig::MIN_PASSWWORD_LENGTH);
        // passwords must match
        if($this->newPassword !== $this->confirmNewPassword)
        {
            throw new InvalidArgumentException(self::MSG_PASSWORDS_NOT_MATCH, 
                    parent::MSGCODE_BAD_PARAM);            
        }
    }
    
    protected function HandleRequestImpl(ForumDb $db) : void
    {
        $logger = new Logger($db);
        // dummy user cannot have a password set
        if($this->user->IsDummyUser())
        {
            $logger->LogMessageWithUserId(LogType::LOG_OPERATION_FAILED_USER_IS_DUMMY, $this->user->GetId());
            throw new InvalidArgumentException(self::MSG_DUMMY_USER, parent::MSGCODE_BAD_PARAM);
        }
        // inactive users cannot change their password, 
        // except they are inactive because they need to migrate
        if(!$this->user->IsActive() && !$this->user->NeedsMigration())
        {
            $logger->LogMessageWithUserId(LogType::LOG_OPERATION_FAILED_USER_IS_INACTIVE, $this->user->GetId());
            throw new InvalidArgumentException(self::MSG_USER_INACTIVE, parent::MSGCODE_BAD_PARAM);
        }
        // if we need to migrate, migrate
        if($this->user->NeedsMigration())
        {
            $hashedPassword = password_hash($this->newPassword, PASSWORD_DEFAULT);
            $db->ConfirmUser($this->user->GetId(), $hashedPassword, 
                    $this->user->GetEmail(), true, $this->clientIpAddress);
        }
        else
        {
            $db->UpdateUserPassword($this->user->GetId(), 
                    $this->newPassword, $this->clientIpAddress);
        }
    }
    
    private ?string $newPassword;
    private ?string $confirmNewPassword;
    
    private User $user;
}
