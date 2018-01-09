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
 * Handle a confirmation link with a confirmation code to either
 * finish the registration process of a user, or the complete the migration
 * of a user.
 * 
 * @author Elias Gerber 
 */
class ConfirmUserHandler extends BaseHandler
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
    }
    
    protected function ReadParams()
    {
        // Read params (using get, not through BaseHandler)        
        $this->code = trim(filter_input(INPUT_GET, Mailer::PARAM_CODE, FILTER_UNSAFE_RAW));
    }
    
    protected function ValidateParams()
    {
        // Check for the parameters required
        $this->ValidateStringParam($this->code, self::MSG_CODE_UNKNOWN);
    }
    
    protected function HandleRequestImpl(ForumDb $db) 
    {
        $logger = new Logger($db);
        // Valide the code
        $values = $db->VerifyConfirmUserCode($this->code);
        if(!$values)
        {
            $logger->LogMessage(Logger::LOG_CONFIRM_CODE_FAILED_CODE_INVALID, 'Passed code: ' . $this->code);
            throw new InvalidArgumentException(self::MSG_CODE_UNKNOWN, parent::MSGCODE_BAD_PARAM);
        }
        // First: Check if there is a matching user who actually needs 
        // a confirmation to be migrated / registered:
        $user = User::LoadUserById($db, $values['iduser']);
        if(!$user)
        {
            $logger->LogMessage(Logger::LOG_CONFIRM_CODE_FAILED_NO_MATCHING_USER, 'iduser not found : ' . $values['iduser']);
            throw new InvalidArgumentException(self::MSG_CODE_UNKNOWN, parent::MSGCODE_BAD_PARAM);
        }
        $confirmSource = $values['confirm_source'];
        if(!($user->NeedsConfirmation() || $user->NeedsMigration()))
        {
            if($confirmSource === ForumDb::CONFIRM_SOURCE_NEWUSER)
            {
                $logger->LogMessageWithUserId(Logger::LOG_OPERATION_FAILED_ALREADY_CONFIRMED, $user->GetId());
                throw new InvalidArgumentException(self::MSG_ALREADY_CONFIRMED, parent::MSGCODE_BAD_PARAM);
            }
            if($confirmSource === ForumDb::CONFIRM_SOURCE_MIGRATE)
            {
                $logger->LogMessageWithUserId(Logger::LOG_OPERATION_FAILED_ALREADY_MIGRATED, $user->GetId());
                throw new InvalidArgumentException(self::MSG_ALREADY_MIGRATED, parent::MSGCODE_BAD_PARAM);
            }        
        }
        $activate = ($confirmSource === ForumDb::CONFIRM_SOURCE_MIGRATE);
        // And migrate that user:
        $db->ConfirmUser($user->GetId(), $values['password'],
                $values['email'], $activate,  $this->clientIpAddress);
        // Notify the admins if a user is awaiting to get freed
        if($confirmSource === ForumDb::CONFIRM_SOURCE_NEWUSER)
        {
            $mailer = new Mailer();
            $query = 'SELECT email FROM user_table '
                    . 'WHERE admin > 0 AND active > 0';
            $stmt = $db->prepare($query);
            $stmt->execute();
            while($row = $stmt->fetch())
            {
                $adminEmail = $row['email'];
                if(!$mailer->NotifyAdminUserConfirmedRegistraion($user->GetNick(), $adminEmail))
                {
                    throw new Exception('Sending mail to ' . $adminEmail . ' failed!');
                }
                $logger->LogMessageWithUserId(Logger::LOG_NOTIFIED_ADMIN_USER_REGISTRATION_CONFIRMED, $user->GetId(), 'Mail sent to: ' . $adminEmail);
            }
        }

        // return the type of confirmation executed
        return $confirmSource;
    }

    private $code;
}