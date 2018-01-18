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
 * Handle a confirmation link with a confirmation code to update the 
 * email address of a user.
 *
 * @author Elias Gerber
 */
class ConfirmUpdateEmailHandler extends BaseHandler
{

    const MSG_CODE_UNKNOWN = 'Ungültiger Bestätigungscode';
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
        // Check for the parameters required to authenticate
        $this->ValidateStringParam($this->code, self::MSG_CODE_UNKNOWN);
    }

    protected function HandleRequestImpl(ForumDb $db) 
    {
        $logger = new Logger($db);
        // Valide the code and remove if a valid entry is found
        $values = $db->VerifyUpdateEmailCode($this->code, true);
        if(!$values)
        {
            $logger->LogMessage(Logger::LOG_CONFIRM_CODE_FAILED_CODE_INVALID, 'Passed code: ' . $this->code);
            throw new InvalidArgumentException(self::MSG_CODE_UNKNOWN, parent::MSGCODE_BAD_PARAM);
        }
        // First: Check if there is a matching (real) user:
        $user = User::LoadUserById($db, $values['iduser']);
        if(!$user)
        {
            $logger->LogMessage(Logger::LOG_CONFIRM_CODE_FAILED_NO_MATCHING_USER, 'iduser not found : ' . $values['iduser']);
            throw new InvalidArgumentException(self::MSG_CODE_UNKNOWN, parent::MSGCODE_BAD_PARAM);
        }
        if($user->IsDummyUser())
        {
            $logger->LogMessageWithUserId(Logger::LOG_OPERATION_FAILED_USER_IS_DUMMY, $user->GetId());
            throw new InvalidArgumentException(self::MSG_DUMMY_USER, parent::MSGCODE_BAD_PARAM);
        }

        // And update the email
        $db->UpdateUserEmail($user->GetId(), $values['email'], 
                $this->clientIpAddress);        
    }
    
    private $code;    
}
