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
require_once __DIR__.'/../helpers/CaptchaV3Verifier.php';
require_once __DIR__.'/../helpers/CaptchaV3Config.php';
require_once __DIR__.'/../YbForumConfig.php';

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
    const MSG_SENDING_CONFIRMMAIL_FAILED = 'Die Bestätigungsmail konnnte nicht gesendet werden.';
    
    public function __construct()
    {
        parent::__construct();
        
        $this->logger = null;
        $this->mailer = null;

        // Set defaults explicitly
        $this->nick = null;
        $this->password = null;
        $this->confirmpassword = null;
        $this->email = null;
        $this->regMsg = null;
        $this->m_captchaVerifier = null;
    }
    
    protected function ReadParams() : void
    {
        $this->nick = self::ReadStringParam(self::PARAM_NICK);
        $this->email = self::ReadEmailParam(self::PARAM_EMAIL);
        $this->password = self::ReadStringParam(self::PARAM_PASS);
        $this->confirmpassword = self::ReadStringParam(self::PARAM_CONFIRMPASS);
        $this->regMsg = self::ReadStringParam(self::PARAM_REGMSG);        
        
        if(CaptchaV3Config::CAPTCHA_VERIFY)
        {
            $this->m_captchaVerifier = new CaptchaV3Verifier(
                CaptchaV3Config::CAPTCHA_SECRET, 
                CaptchaV3Config::MIN_REQUIRED_SCORE,
                CaptchaV3Config::CAPTCHA_REGISTER_USER_ACTION
            );
        }
    }
    
    protected function ValidateParams() : void
    { 
        // Validate where we cannot accept null values:
        self::ValidateStringParam($this->nick, self::MSG_NICK_TOO_SHORT, YbForumConfig::MIN_NICK_LENGTH);
        self::ValidateEmailValue($this->email);
        self::ValidateStringParam($this->password, self::MSG_PASSWORD_TOO_SHORT, YbForumConfig::MIN_PASSWWORD_LENGTH);
        
        // passwords must match
        if($this->confirmpassword !== $this->password)
        {
            throw new InvalidArgumentException(self::MSG_PASSWORDS_NOT_MATCH, 
                    parent::MSGCODE_BAD_PARAM);            
        }

        // Verify captcha
        if(CaptchaV3Config::CAPTCHA_VERIFY)
        {
            $this->m_captchaVerifier->VerifyResponse();
        }        
    }

    protected function HandleRequestImpl(ForumDb $db) : void
    {
        if(is_null($this->logger))
        {
            $this->logger = new Logger($db);
        }
        // Check that nick and email are unique
        $userByNick = $db->LoadUserByNick($this->nick);
        if($userByNick)
        {
            $this->logger->LogMessage(LogType::LOG_OPERATION_FAILED_NICK_NOT_UNIQUE, 
                    'Requested Nick: ' . $this->nick . ' already used in: ' . $userByNick->GetNick() . ' (' . $userByNick->GetId() . ')');
            throw new InvalidArgumentException(self::MSG_NICK_NOT_UNIQUE, 
                    parent::MSGCODE_BAD_PARAM);
        }
        $userByEmail = $db->LoadUserByEmail($this->email);
        if($userByEmail)
        {
            $this->logger->LogMessage(LogType::LOG_OPERATION_FAILED_EMAIL_NOT_UNIQUE, 'Passed Email: ' . $this->email);
            throw new InvalidArgumentException(self::MSG_EMAIL_NOT_UNIQUE, 
                    parent::MSGCODE_BAD_PARAM);
        }
        // Check that email is not blacklisted
        self::ValidateEmailAgainstBlacklist($this->email, $db, $this->logger);
        
        // Create the user and request a confirmation code 
        $user = $db->CreateNewUser($this->nick, $this->email, 
                $this->regMsg, $this->clientIpAddress);
        $confirmCode = $db->RequestConfirmUserCode($user, $this->password, 
                $this->email, ForumDb::CONFIRM_SOURCE_NEWUSER, 
                $this->clientIpAddress);

        // Send a mail with the confirmation link
        if(is_null($this->mailer))
        {
            $this->mailer = new Mailer();
        }
        if(!$this->mailer->SendRegisterUserConfirmMessage($this->email, $this->nick, $confirmCode))
        {
            // Remove the just created user
            $db->RemoveConfirmUserCode($user);
            $db->DeleteUser($user);
            // And fail
            throw new InvalidArgumentException(self::MSG_SENDING_CONFIRMMAIL_FAILED, parent::MSGCODE_INTERNAL_ERROR);
        }
    }
    
    public function GetNick() : ?string
    {
        return $this->nick;
    }
    
    public function GetEmail() : ?string
    {
        return $this->email;
    }
    
    public function GetRegMsg() : ?string
    {
        return $this->regMsg;
    }

    public function GetPassword() : ?string
    {
        return $this->password;
    }

    public function GetConfirmPassword() : ?string
    {
        return $this->confirmpassword;
    }

    public function SetMailer(Mailer $mailer) : void
    {
        $this->mailer = $mailer;
    }

    public function SetLogger(Logger $logger) : void
    {
        $this->logger = $logger;
    }
    
    private ?Logger $logger;
    private ?Mailer $mailer;

    private ?string $nick;
    private ?string $password;
    private ?string $confirmpassword;
    private ?string $email;
    private ?string $regMsg;
    private ?CaptchaV3Verifier $m_captchaVerifier;
}
