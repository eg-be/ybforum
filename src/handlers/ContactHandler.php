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
class ContactHandler extends BaseHandler 
{
    const PARAM_EMAIL = 'contact_emailaddress';
    const PARAM_EMAIL_REPEAT = 'contact_emailaddress_repeat';
    const PARAM_MSG = 'contact_message';

    const MSG_EMPTY = 'Nachricht kann nicht leer sein.';
    const MSG_EMAIL_DO_NOT_MATCH = 'Mailadressen stimmen nicht überein.';
    const MSG_SENDING_CONTACTMAIL_FAILED = 'Die Anfrage konnnte nicht gesendet werden.';
    
    public function __construct()
    {
        parent::__construct();
        
        $this->logger = null;
        $this->mailer = null;

        // Set defaults explicitly
        $this->email = null;
        $this->emailRepeat = null;
        $this->msg = null;
        $this->m_captchaVerifier = null;
    }
    
    protected function ReadParams() : void
    {
        $this->email = self::ReadEmailParam(self::PARAM_EMAIL);
        $this->emailRepeat = self::ReadEmailParam(self::PARAM_EMAIL_REPEAT);
        $this->msg = self::ReadStringParam(self::PARAM_MSG);        
        
        if(CaptchaV3Config::CAPTCHA_VERIFY)
        {
            $this->m_captchaVerifier = new CaptchaV3Verifier(
                CaptchaV3Config::CAPTCHA_SECRET, 
                CaptchaV3Config::MIN_REQUIRED_SCORE,
                CaptchaV3Config::CAPTCHA_CONTACT_ACTION
            );
        }
    }
    
    protected function ValidateParams() : void
    { 
        // Validate where we cannot accept null values:
        self::ValidateStringParam($this->msg, self::MSG_EMPTY);
        self::ValidateEmailValue($this->email);
        self::ValidateEmailValue($this->emailRepeat);
        
        // check that mail-addresses match:
        if($this->email !== $this->emailRepeat)
        {
            throw new InvalidArgumentException(self::MSG_EMAIL_DO_NOT_MATCH, parent::MSGCODE_BAD_PARAM);
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
        // try to log what we have received
        $this->logger->LogMessage(LogType::LOG_CONTACT_FORM_SUBMITTED, 'Mail: ' . $this->email . '; Msg: ' . $this->msg);

        // Send a mail to all admins
        if(is_null($this->mailer))
        {
            $this->mailer = new Mailer();
        }
        $admins = $db->GetAdminUsers();
        foreach($admins as $admin)
        {
            if(!$this->mailer->SendAdminContactMessage($this->email, $this->msg, $admin->GetEmail()))
            {
                // Fail
                throw new InvalidArgumentException(self::MSG_SENDING_CONTACTMAIL_FAILED, parent::MSGCODE_INTERNAL_ERROR);
            }
        }
    }
   
    public function GetEmail() : ?string
    {
        return $this->email;
    }

    public function GetEmailRepeat() : ?string
    {
        return $this->emailRepeat;
    }    
    
    public function GetMsg() : ?string
    {
        return $this->msg;
    }
    
/*    private function GetLogger(ForumDb $db) : Logger
    {
        if(is_null($this->logger))
        {
            $this->logger = new Logger($db);
        }
        return $this->logger;
    }

    private function GetMailer() : GetMailer
    {
        if(is_null($this->mailer))
        {
            $this->mailer = new Mailer();
        }
        return $this->mailer;
    }*/

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

    private ?string $email;
    private ?string $emailRepeat;
    private ?string $msg;
    private ?CaptchaV3Verifier $m_captchaVerifier;
}
