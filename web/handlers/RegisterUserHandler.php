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
 * Creates a new user by adding an entry in user_table and creating the 
 * required entry in confirm_user_table and sending an email with a confirmation
 * link.
 *
 * @author Elias Gerber
 */
class RegisterUserHandler extends BaseHandler 
{
    
    const PARAM_NICK = 'register_nick';
    const PARAM_PASS = 'register_pass';
    const PARAM_CONFIRMPASS = 'register_confirmpass';
    const PARAM_EMAIL = 'register_emailaddress';
    const PARAM_REGMSG = 'register_message';
    
    const MSG_NICK_NOT_UNIQUE = 'Angegebener Nickname bereits verwendet.';
    const MSG_NICK_TOO_SHORT = 'Nickname muss mindestens ' .
            YbForumConfig::MIN_NICK_LENGTH . ' Zeichen enthalten.';
    const MSG_EMAIL_NOT_UNIQUE = 'Angegebene Mailadresse bereits verwendet.';
    const MSG_PASSWORDS_NOT_MATCH = 'Passwort und Bestätigung stimmen nicht überein.';
    const MSG_PASSWORD_TOO_SHORT = 'Neues Passwort muss mindestens ' .
                    YbForumConfig::MIN_PASSWWORD_LENGTH . ' Zeichen enthalten.';
        
    
    public function __construct()
    {
        parent::__construct();
        
        // Set defaults explicitly
        $this->nick = null;
        $this->password = null;
        $this->confirmpassword = null;
        $this->email = null;
    }
    
    protected function ReadParams()
    {
        $this->nick = $this->ReadStringParam(self::PARAM_NICK);
        $this->email = $this->ReadEmailParam(self::PARAM_EMAIL);
        $this->password = $this->ReadStringParam(self::PARAM_PASS);
        $this->confirmpassword = $this->ReadStringParam(self::PARAM_CONFIRMPASS);
        $this->regMsg = $this->ReadStringParam(self::PARAM_REGMSG);
    }
    
    protected function ValidateParams()
    {
        // Validate where we cannot accept null values:
        $this->ValidateStringParam($this->nick, self::MSG_NICK_TOO_SHORT, YbForumConfig::MIN_NICK_LENGTH);
        $this->ValidateEmailValue($this->email);
        $this->ValidateStringParam($this->password, self::MSG_PASSWORD_TOO_SHORT, YbForumConfig::MIN_PASSWWORD_LENGTH);
        
        // passwords must match
        if($this->confirmpassword !== $this->password)
        {
            throw new InvalidArgumentException(self::MSG_PASSWORDS_NOT_MATCH, 
                    parent::MSGCODE_BAD_PARAM);            
        }
    }

    protected function HandleRequestImpl(ForumDb $db) 
    {
        $logger = new Logger($db);
        // Check that nick and email are unique
        $userByNick = User::LoadUserByNick($db, $this->nick);
        if($userByNick)
        {
            $logger->LogMessage(Logger::LOG_OPERATION_FAILED_NICK_NOT_UNIQUE, 
                    'Requested Nick: ' . $this->nick . ' already used in: ' . $userByNick->GetNick() . ' (' . $userByNick->GetId() . ')');
            throw new InvalidArgumentException(self::MSG_NICK_NOT_UNIQUE, 
                    parent::MSGCODE_BAD_PARAM);
        }
        $userByEmail = User::LoadUserByEmail($db, $this->email);
        if($userByEmail)
        {
            $logger->LogMessage(Logger::LOG_OPERATION_FAILED_EMAIL_NOT_UNIQUE, 'Passed Email: ' . $this->email);
            throw new InvalidArgumentException(self::MSG_EMAIL_NOT_UNIQUE, 
                    parent::MSGCODE_BAD_PARAM);
        }
        // Create the user and request a confirmation code 
        $userId = $db->CreateNewUser($this->nick, $this->email, 
                $this->regMsg, $this->clientIpAddress);
        $confirmCode = $db->RequestConfirmUserCode($userId, $this->password, 
                $this->email, ForumDb::CONFIRM_SOURCE_NEWUSER, 
                $this->clientIpAddress);
        // Send a mail with the confirmation link
        $mailer = new Mailer();
        if(!$mailer->SendRegisterUserConfirmMessage($this->email, $confirmCode))
        {
            throw new Exception('Sending mail to ' . $this->email . ' failed!');
        }
        $logger->LogMessageWithUserId(Logger::LOG_CONFIRM_REGISTRATION_CODE_CREATED, $userId, 'Mail sent to: ' . $this->email);
        // and return the address we have sent the mail to:
        return $this->email;
    }
    
    public function GetNick()
    {
        return $this->nick;
    }
    
    public function GetEmail()
    {
        return $this->email;
    }
    
    public function GetRegMsg()
    {
        return $this->regMsg;
    }
    
    private $nick;
    private $password;
    private $confirmpassword;
    private $email;
    private $regMsg;
}
