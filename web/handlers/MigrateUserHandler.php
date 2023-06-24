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
 * Read all values required to migrate an old user. Creates the required
 * entry in the confirm_user_table and sends an email with the confirmation
 * link to the email address set as new email address.
 *
 * @author Elias Gerber
 */
class MigrateUserHandler extends BaseHandler
{
    const PARAM_NICK = 'request_migrate_nick';
    const PARAM_OLDPASS = 'request_migrate_oldpass';
    const PARAM_NEWPASS = 'request_migrate_newpass';
    const PARAM_CONFIRMNEWPASS = 'request_migrate_confirmpass';
    const PARAM_NEWEMAIL = 'request_migrate_mailaddress';
    
    const MSG_AUTH_FAIL = 'Unbekannter Stammposter / altes Passwort';
    const MSG_EMAIL_NOT_UNIQUE = 'Angegebene Mailadresse bereits verwendet. Verwende '
            . 'Passwort zurücksetzen Funktion im Stammposterbereich falls du '
            . 'nicht mehr weisst mit welchem Account diese Mailadresse '
            . 'verknüpft ist.';
    const MSG_ALREADY_MIGRATED = 'AlreadyMigrated';
    const MSG_PASSWORDS_NOT_MATCH = 'Passwort und Bestätigung stimmen nicht überein.';
    const MSG_PASSWORD_TOO_SHORT = 'Neues Passwort muss mindestens ' .
                    YbForumConfig::MIN_PASSWWORD_LENGTH . ' Zeichen enthalten.';
    const MSG_SENDING_CONFIRMMAIL_FAILED = 'Die Bestätigungsmail konnnte nicht gesendet werden.';

    
    public function __construct()
    {
        parent::__construct();
        
        // Set defaults explicitly
        $this->nick = null;
        $this->oldPassword = null;
        $this->newPassword = null;
        $this->confirmNewPassword = null;
        $this->newEmail = null;
    }
    
    protected function ReadParams() : void
    {
        // Read params
        $this->nick = $this->ReadStringParam(self::PARAM_NICK);
        $this->oldPassword = $this->ReadStringParam(self::PARAM_OLDPASS);
        $this->newPassword = $this->ReadStringParam(self::PARAM_NEWPASS);
        $this->confirmNewPassword = $this->ReadStringParam(self::PARAM_CONFIRMNEWPASS);
        $this->newEmail = $this->ReadEmailParam(self::PARAM_NEWEMAIL);
    }
    
    protected function ValidateParams() : void
    {
        // And validate the params
        $this->ValidateStringParam($this->nick, self::MSG_AUTH_FAIL);
        $this->ValidateStringParam($this->oldPassword, self::MSG_AUTH_FAIL);
        $this->ValidateStringParam($this->newPassword, self::MSG_PASSWORD_TOO_SHORT, YbForumConfig::MIN_PASSWWORD_LENGTH);
        $this->ValidateEmailValue($this->newEmail);
        
        // Passwords must match
        if($this->newPassword !== $this->confirmNewPassword)
        {
            throw new InvalidArgumentException(self::MSG_PASSWORDS_NOT_MATCH, parent::MSGCODE_BAD_PARAM);
        }
    }
    
    protected function HandleRequestImpl(ForumDb $db) : string 
    {
        // First: Check if there is a matching (real) user:
        $user = User::LoadUserByNick($db, $this->nick);
        $logger = new Logger($db);
        if(!$user)
        {
            $logger->LogMessage(Logger::LOG_AUTH_FAILED_NO_SUCH_USER, 'Passed nick: ' . $this->nick);
            throw new InvalidArgumentException(self::MSG_AUTH_FAIL, parent::MSGCODE_AUTH_FAIL);
        }
        if($user->IsDummyUser())
        {
            $logger->LogMessageWithUserId(Logger::LOG_AUTH_FAILED_USER_IS_DUMMY, $user->GetId());
            throw new InvalidArgumentException(self::MSG_AUTH_FAIL, parent::MSGCODE_AUTH_FAIL);            
        }
        // Check if user still needs to migrate:
        if(!$user->NeedsMigration())
        {
            $logger->LogMessageWithUserId(Logger::LOG_OPERATION_FAILED_ALREADY_MIGRATED, $user->GetId());
            throw new InvalidArgumentException(self::MSG_ALREADY_MIGRATED, parent::MSGCODE_BAD_PARAM);
        }
        // Auth using old password
        if(!$user->OldAuth($this->oldPassword))
        {
            $logger->LogMessageWithUserId(Logger::LOG_AUTH_FAILED_USING_OLD_PASSWORD, $user->GetId());
            throw new InvalidArgumentException(self::MSG_AUTH_FAIL, parent::MSGCODE_AUTH_FAIL);
        }
        // Authentication using old password succeeded
        $logger->LogMessageWithUserId(Logger::LOG_AUTH_USING_OLD_PASSWORD, $user->GetId());
        // The given Mailaddress must be unique:
        $userByEmail = User::LoadUserByEmail($db, $this->newEmail);
        if($userByEmail && $userByEmail->GetId() !== $user->GetId())
        {
            $logger->LogMessageWithUserId(Logger::LOG_OPERATION_FAILED_EMAIL_NOT_UNIQUE, $user->GetId(), 'New Email: ' . $this->newEmail);
            throw new InvalidArgumentException(self::MSG_EMAIL_NOT_UNIQUE, parent::MSGCODE_BAD_PARAM);
        }
        // check that the new email is not blacklisted
        $this->ValidateEmailAgainstBlacklist($this->newEmail, $db, $logger);
        // And prepare to migrate
        $confirmCode = $db->RequestConfirmUserCode($user->GetId(), 
                $this->newPassword, 
                $this->newEmail, 
                ForumDb::CONFIRM_SOURCE_MIGRATE,
                $this->clientIpAddress);

        // send the email to the address requested
        $mailer = new Mailer();
        if(!$mailer->SendMigrateUserConfirmMessage($this->newEmail, $this->nick, $confirmCode))
        {
            throw new InvalidArgumentException(self::MSG_SENDING_CONFIRMMAIL_FAILED, parent::MSGCODE_INTERNAL_ERROR);
        }
        // and return the address we have sent the mail to:
        return $this->newEmail;
    }
        
    public function GetNick() : string
    {
        return $this->nick;
    }
    
    public function GetNewEmail() : string
    {
        return $this->newEmail;
    }
        
    private $nick;
    private $oldPassword;
    private $newPassword;
    private $confirmNewPassword;
    private $newEmail;    
}
