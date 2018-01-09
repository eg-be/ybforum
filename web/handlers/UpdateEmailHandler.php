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
require_once __DIR__.'/../model/ForumDb.php';

/**
 * Handles a request to update the email address of a user. Sends an email
 * with a confirmation link to the newly set email address.
 *
 * @author Elias Gerber
 */
class UpdateEmailHandler extends BaseHandler
{
    
    const PARAM_NEWEMAIL = 'stammposter_updateemail';
    
    const MSG_EMAIL_NOT_DIFFERENT = 'Angegebene Mailadresse ist dieselbe wie '
            . 'die bereits hinterlegte.';
    const MSG_EMAIL_NOT_UNIQUE = 'Angegebene Mailadresse bereits verwendet. Verwende '
            . 'Passwort zurücksetzen Funktion im Stammposterbereich falls du '
            . 'nicht mehr weisst mit welchem Account diese Mailadresse '
            . 'verknüpft ist.';
    
    public function __construct(User $user)
    {
        parent::__construct();
        
        $this->user = $user;
        
        // Set defaults explicitly
        $this->newEmail = null;
    }
    
    protected function ReadParams()
    {
        // Read params
        $this->newEmail = $this->ReadEmailParam(self::PARAM_NEWEMAIL);
    }
    
    protected function ValidateParams()
    {
        $this->ValidateEmailValue($this->newEmail);
        // Email must be different from current email
        if($this->user->GetEmail() === $this->newEmail)
        {
            throw new InvalidArgumentException(self::MSG_EMAIL_NOT_DIFFERENT, parent::MSGCODE_BAD_PARAM);
        }
    }
    
    protected function HandleRequestImpl(ForumDb $db) 
    {
        // Check that this email address is not already used within some other 
        // account
        $user = User::LoadUserByEmail($db, $this->newEmail);
        if($user)
        {
            throw new InvalidArgumentException(self::MSG_EMAIL_NOT_UNIQUE, parent::MSGCODE_BAD_PARAM);
        }
        
        // Create a confirmation link to update the email
        $confirmCode = $db->RequestUpdateEmailCode($this->user->GetId(), 
                $this->newEmail, $this->clientIpAddress);            

        // send the email to the address requested
        $mailer = new Mailer();
        if(!$mailer->SendUpdateEmailConfirmMessage($this->newEmail, $confirmCode))
        {
            throw new Exception('Sending mail to ' . $this->newEmail . ' failed!');
        }
        
        $logger = new Logger($db);
        $logger->LogMessage(Logger::LOG_CONFIRM_EMAIL_CODE_CREATED, 'Mail sent to: ' . $this->newEmail);

        // and return the address we have sent the mail to:
        return $this->newEmail;
    }
        
    public function GetNewEmail()
    {
        return $this->newEmail;
    }
        
    private $newEmail;

    private $user;
}
