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
require_once __DIR__.'/ConfirmHandler.php';
require_once __DIR__.'/../model/ForumDb.php';
require_once __DIR__.'/../helpers/Mailer.php';
require_once __DIR__.'/../helpers/Logger.php';

/**
 * Handle a confirmation link with a confirmation code to either
 * finish the registration process of a user, or the complete the migration
 * of a user.
 * If the REQUEST_METHOD associated with this ConfirmHandler is GET,
 * this handler does not modify any data, but will return as soon as
 * all parameters have been verified (but will fail with the same
 * InvalidArgumentException if one of the parameters fails validation.).
 * 
 * Note: Simulate used internally here is set if the request method
 * is GET and means that the process is not * finished (confirm user, etc.). 
 * Callers should display a button to  trigger a POST action with the
 * same confirm-values again. This makes it a little bit harder for
 * dumb bots.
 * 
 * @author Elias Gerber 
 */
class ConfirmUserHandler extends BaseHandler implements ConfirmHandler
{    
    const MSG_CODE_UNKNOWN = 'Ungültiger Bestätigungscode';
    const MSG_ALREADY_CONFIRMED = 'AlreadyConfirmed';
    const MSG_ALREADY_MIGRATED = 'AlreadyMigrated';
    const MSG_DUMMY_USER = 'Stammposter ist ein Dummy';
    
    public function __construct()
    {
        parent::__construct();
        
        // Set defaults explicitly
        $this->code = null;
        $this->user = null;
        $this->confirmSource = null;
        $this->simulate = false;
    }
    
    protected function ReadParams() : void
    {
        // Read params - depending on the invocation using GET or through base-handler
        $this->simulate = (filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'GET');
        if($this->simulate)
        {
            $this->code = trim(filter_input(INPUT_GET, ConfirmHandler::PARAM_CODE, FILTER_UNSAFE_RAW));
            if(!$this->code)
            {
                $this->code = null;
            }
        }
        else
        {
            $this->code = $this->ReadStringParam(ConfirmHandler::PARAM_CODE);
        }
    }
    
    protected function ValidateParams() : void
    {
        // Check for the parameters required
        $this->ValidateStringParam($this->code, self::MSG_CODE_UNKNOWN);
    }
    
    protected function HandleRequestImpl(ForumDb $db) : void
    {
        // reset internal values first
        $this->user = null;
        $this->confirmSource = null;
        $logger = new Logger($db);
        // Valide the code, but only remove it if we are not simulating
        $values = $db->VerifyConfirmUserCode($this->code, !$this->simulate);
        if(!$values)
        {
            $logger->LogMessage(Logger::LOG_CONFIRM_CODE_FAILED_CODE_INVALID, 'Passed code: ' . $this->code);
            throw new InvalidArgumentException(self::MSG_CODE_UNKNOWN, parent::MSGCODE_BAD_PARAM);
        }
        // First: Check if there is a matching user who actually needs 
        // a confirmation to be migrated / registered:
        $this->user = User::LoadUserById($db, $values['iduser']);
        if(!$this->user)
        {
            $logger->LogMessage(Logger::LOG_CONFIRM_CODE_FAILED_NO_MATCHING_USER, 'iduser not found : ' . $values['iduser']);
            throw new InvalidArgumentException(self::MSG_CODE_UNKNOWN, parent::MSGCODE_BAD_PARAM);
        }
        $this->confirmSource = $values['confirm_source'];
        if(!($this->user->NeedsConfirmation() || $this->user->NeedsMigration()))
        {
            if($this->confirmSource === ForumDb::CONFIRM_SOURCE_NEWUSER)
            {
                $logger->LogMessageWithUserId(Logger::LOG_OPERATION_FAILED_ALREADY_CONFIRMED, $this->user->GetId());
                throw new InvalidArgumentException(self::MSG_ALREADY_CONFIRMED, parent::MSGCODE_BAD_PARAM);
            }
            if($this->confirmSource === ForumDb::CONFIRM_SOURCE_MIGRATE)
            {
                $logger->LogMessageWithUserId(Logger::LOG_OPERATION_FAILED_ALREADY_MIGRATED, $this->user->GetId());
                throw new InvalidArgumentException(self::MSG_ALREADY_MIGRATED, parent::MSGCODE_BAD_PARAM);
            }        
        }
        if($this->simulate)
        {
            // okay, return in simulation mode now
            return;
        }
        $activate = ($this->confirmSource === ForumDb::CONFIRM_SOURCE_MIGRATE);
        // And migrate that user:
        $db->ConfirmUser($this->user->GetId(), $values['password'],
                $values['email'], $activate);
        // Notify the admins if a user is awaiting to get freed
        if($this->confirmSource === ForumDb::CONFIRM_SOURCE_NEWUSER)
        {
            $mailer = new Mailer();
            $query = 'SELECT email FROM user_table '
                    . 'WHERE admin > 0 AND active > 0';
            $stmt = $db->prepare($query);
            $stmt->execute();
            while($row = $stmt->fetch())
            {
                $adminEmail = $row['email'];
                if($mailer->NotifyAdminUserConfirmedRegistration($this->user->GetNick(), 
                        $adminEmail, $this->user->GetRegistrationMsg()))
                {
                    $logger->LogMessageWithUserId(Logger::LOG_NOTIFIED_ADMIN_USER_REGISTRATION_CONFIRMED, $this->user->GetId(), 'Mail sent to: ' . $adminEmail);
                }
            }
        }
    }
    
    public function GetCode() : ?string
    {
        return $this->code;
    }
    
    public function GetType() : string
    {
        return ConfirmHandler::VALUE_TYPE_CONFIRM_USER;
    }
    
    public function GetConfirmText() : string
    {
        $txt = '';
        if($this->confirmSource === ForumDb::CONFIRM_SOURCE_NEWUSER)
        {
            $txt = 'Klicke auf Bestätigen um die Registrierung für den Stampposter ' . $this->user->GetNick() . ' zu bestätigen: ';
        }
        else if($this->confirmSource === ForumDb::CONFIRM_SOURCE_MIGRATE)
        {
            $txt = 'Klicke auf Bestätigen um die Migration für den Stammposter ' . $this->user->GetNick() . ' abzuschliessen: ';
        }
        return $txt;
    }
    
    public function GetSuccessText() : string
    {
        $txt = '';
        if($this->confirmSource === ForumDb::CONFIRM_SOURCE_NEWUSER)
        {
            $txt = 'Registrierung erfolgreich abgeschlossen. '
                            . 'Ein Administrator wird deinen Antrag begutachten '
                            . 'und dein Account bei Gelegenheit eventuell '
                            . 'freischalten. Du erhältst eine Email sobald '
                            . 'dein Account freigschaltet wurde.';
        }
        else if($this->confirmSource === ForumDb::CONFIRM_SOURCE_MIGRATE)
        {
            $txt = 'Migration erfolgreich abgeschlossen, dein neues '
                            . 'Passwort ist ab sofort gültig';
        }
        return $txt;
    }    

    private ?string $code;
    private ?string $confirmSource;
    private ?User $user;
    private bool $simulate;
}