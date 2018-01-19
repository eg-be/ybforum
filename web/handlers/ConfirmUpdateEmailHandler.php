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
require_once __DIR__.'/../helpers/Logger.php';

/**
 * Handle a confirmation link with a confirmation code to update the 
 * email address of a user.
 * If the REQUEST_METHOD associated with this ConfirmHandler is GET,
 * this handler does not modify any data, but will return as soon as
 * all parameters have been verified (but will fail with the same
 * IllegalArgumentException if one of the parameters fails validation.).
 *
 * @author Elias Gerber
 */
class ConfirmUpdateEmailHandler extends BaseHandler implements ConfirmHandler
{

    const MSG_CODE_UNKNOWN = 'Ungültiger Bestätigungscode';
    const MSG_DUMMY_USER = 'Stammposter ist ein Dummy';    
    
    public function __construct()
    {
        parent::__construct();
        
        // Set defaults explicitly
        $this->code = null;
        $this->simulate = null;
        $this->user = null;
        $this->newEmail = null;
    }
    
    protected function ReadParams()
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
    
    protected function ValidateParams()
    {
        // Check for the parameters required to authenticate
        $this->ValidateStringParam($this->code, self::MSG_CODE_UNKNOWN);
    }

    protected function HandleRequestImpl(ForumDb $db) 
    {
        // reset the internal values first
        $this->user = null;
        $this->newEmail = null;
        $logger = new Logger($db);
        // Valide the code and remove it if we are not simulating
        $values = $db->VerifyUpdateEmailCode($this->code, !$this->simulate);
        if(!$values)
        {
            $logger->LogMessage(Logger::LOG_CONFIRM_CODE_FAILED_CODE_INVALID, 'Passed code: ' . $this->code);
            throw new InvalidArgumentException(self::MSG_CODE_UNKNOWN, parent::MSGCODE_BAD_PARAM);
        }
        // First: Check if there is a matching (real) user:
        $this->user = User::LoadUserById($db, $values['iduser']);
        if(!$this->user)
        {
            $logger->LogMessage(Logger::LOG_CONFIRM_CODE_FAILED_NO_MATCHING_USER, 'iduser not found : ' . $values['iduser']);
            throw new InvalidArgumentException(self::MSG_CODE_UNKNOWN, parent::MSGCODE_BAD_PARAM);
        }
        if($this->user->IsDummyUser())
        {
            $logger->LogMessageWithUserId(Logger::LOG_OPERATION_FAILED_USER_IS_DUMMY, $this->user->GetId());
            throw new InvalidArgumentException(self::MSG_DUMMY_USER, parent::MSGCODE_BAD_PARAM);
        }
        
        $this->newEmail = $values['email'];
        
        if($this->simulate)
        {
            // abort here in simulation mode
            return;
        }
        
        // And update the email
        $db->UpdateUserEmail($this->user->GetId(), $this->newEmail, 
                $this->clientIpAddress);        
    }
    
    public function GetCode()
    {
        return $this->code;
    }
    
    public function GetType()
    {
        return ConfirmHandler::VALUE_TYPE_UPDATEEMAIL;
    }
    
    public function GetConfirmText() 
    {
        return 'Klicke auf Bestätigen um die Mailadresse '
                . $this->newEmail . ' für den Benutzer ' 
                . $this->user->GetNick() . ' zu bestätigen:';
    }
    
    public function GetSuccessText()
    {
        return 'Emailadresse erfolgreich aktualisiert';
    }
    
    private $code;
    private $simulate;
    private $user;
    private $newEmail;
}
