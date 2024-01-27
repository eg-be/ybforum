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
    const PARAM_MSG = 'contact_message';

    const MSG_EMPTY = 'Nachricht kann nicht leer sein.';
    const MSG_SENDING_CONTACTMAIL_FAILED = 'Die Anfrage konnnte nicht gesendet werden.';
    
    public function __construct()
    {
        parent::__construct();
        
        // Set defaults explicitly
        $this->email = null;
        $this->msg = null;
        $this->m_captchaVerifier = null;
    }
    
    protected function ReadParams() : void
    {
        $this->email = $this->ReadEmailParam(self::PARAM_EMAIL);
        $this->msg = $this->ReadStringParam(self::PARAM_MSG);        
        
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
        $this->ValidateStringParam($this->msg, self::MSG_EMPTY);
        $this->ValidateEmailValue($this->email);
        
        // Verify captcha
        if(CaptchaV3Config::CAPTCHA_VERIFY)
        {
            $this->m_captchaVerifier->VerifyResponse();
        }        
    }

    protected function HandleRequestImpl(ForumDb $db) : void
    {
        $logger = new Logger($db);

        // Send a mail to all admins
        $mailer = new Mailer();
        $admins = $db->GetAdminUsers();
        foreach($admins as $admin)
        {
            if(!$mailer->SendAdminContactMessage($this->email, $this->msg, $admin->GetEmail()))
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
    
    public function GetMsg() : ?string
    {
        return $this->msg;
    }
    
    private ?string $email;
    private ?string $msg;
    private ?CaptchaV3Verifier $m_captchaVerifier;
}
