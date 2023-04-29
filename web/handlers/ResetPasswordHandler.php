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
require_once __DIR__.'/../helpers/Mailer.php';
require_once __DIR__.'/../helpers/Logger.php';

/**
 * Looks up a user by email or nick and sends a link to reset the password
 * by mail to that user.
 *
 * @author Elias Gerber
 */
class ResetPasswordHandler extends BaseHandler
{
        
    const PARAM_EMAIL_OR_NICK = 'resetpassword_email_or_nick';
    
    const MSG_UNKNOWN_EMAIL_OR_NICK = 'Unbekannter Stammposter / Mailadresse';
    const MSG_DUMMY_USER = 'Stammposter ist ein Dummy';
    const MSG_USER_HAS_NO_EMAIL = 'Stammposter hat keine Mailadresse hinterlegt';
    const MSG_USER_INACTIVE = 'Stammposter ist deaktiviert';
    const MSG_SENDING_CONFIRMMAIL_FAILED = 'Die Bestätigungsmail konnnte nicht gesendet werden.';
    
    public function __construct()
    {
        parent::__construct();
        
        // Set defaults explicitly
        $this->email = null;
        $this->nick = null;
    }
    
    protected function ReadParams()
    {
        // Try to read email or nick param as email first
        $this->email = $this->ReadEmailParam(self::PARAM_EMAIL_OR_NICK);
        if(!$this->email)
        {
            // try to read as nick
            $this->nick = $this->ReadStringParam(self::PARAM_EMAIL_OR_NICK);
        }
    }
    
    protected function ValidateParams()
    {
        // need either email or password
        if(!$this->email && !$this->nick)
        {
            throw new InvalidArgumentException(self::MSG_UNKNOWN_EMAIL_OR_NICK, parent::MSGCODE_BAD_PARAM);
        }
    }
    
    protected function HandleRequestImpl(ForumDb $db) 
    {
        $logger = new Logger($db);
        // First: Check if there is a matching user:
        $user = null;
        if($this->nick)
        {
            $user = User::LoadUserByNick($db, $this->nick);
        }
        if($this->email)
        {
            $user = User::LoadUserByEmail($db, $this->email);
        }
        if(!$user)
        {
            $passedValue = '';
            if($this->nick)
            {
                $passedValue = $this->nick;
            }
            if($this->email)
            {
                $passedValue = $this->email;
            }
            $logger->LogMessage(Logger::LOG_OPERATION_FAILED_NO_MATCHING_NICK_OR_EMAIL, 'Passed nick or email: ' . $passedValue);
            throw new InvalidArgumentException(self::MSG_UNKNOWN_EMAIL_OR_NICK, parent::MSGCODE_BAD_PARAM);
        }
        // we only need an email
        if(!$user->HasEmail())
        {
            $logger->LogMessageWithUserId(Logger::LOG_OPERATION_FAILED_USER_HAS_NO_EMAIL, $user->GetId());
            throw new InvalidArgumentException(self::MSG_USER_HAS_NO_EMAIL, parent::MSGCODE_BAD_PARAM);
        }
        // A dummy never has an email, but check anyway
        if($user->IsDummyUser())
        {
            $logger->LogMessageWithUserId(Logger::LOG_OPERATION_FAILED_USER_IS_DUMMY, $user->GetId());
            throw new InvalidArgumentException(self::MSG_DUMMY_USER, parent::MSGCODE_BAD_PARAM);            
        }
        // Do not allow requesting a password for an inactive user, exept this
        // is a user who needs to migrate:
        if(!$user->IsActive() && !$user->NeedsMigration())
        {
            $logger->LogMessageWithUserId(Logger::LOG_OPERATION_FAILED_USER_IS_INACTIVE, $user->GetId());
            throw new InvalidArgumentException(self::MSG_USER_INACTIVE, parent::MSGCODE_BAD_PARAM);
        }
        // okay, init the request to change the password
        $confirmationCode = $db->RequestPasswordResetCode($user, $this->clientIpAddress);

        // send the email to the address requested
        $mailer = new Mailer();
        if(!$mailer->SendResetPasswordMessage($user->GetEmail(), 
                $user->GetNick(), $confirmationCode))
        {
            throw new InvalidArgumentException(self::MSG_SENDING_CONFIRMMAIL_FAILED, parent::MSGCODE_INTERNAL_ERROR);
        }
    }
    
    private $nick;
    private $email;
}
