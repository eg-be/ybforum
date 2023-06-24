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
 * Handle a confirmation link with a confirmation code to reset the password
 * of a user
 * Regardless of the REQUEST_METHOD, this handler will try to validate 
 * a value PARAM_CODE to update a password. If the code is valid, the 
 * corresponding User is returned from the handler implementation.
 * This handler does not modify any data, but will fail with the same
 * InvalidArgumentException if one of the parameters fails validation.
 *
 * @author Elias Gerber
 */
class ConfirmResetPasswordHandler extends BaseHandler implements ConfirmHandler
{
    const MSG_CODE_UNKNOWN = 'Ungültiger Bestätigungscode';
    
    public function __construct()
    {
        parent::__construct();
        
        // Set defaults explicitly
        $this->code = null;
        $this->user = null;
    }
    
    protected function ReadParams() : void
    {
        // Read params - depending on the invocation using GET or through base-handler
        if(filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'GET')
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
        // Check for the parameters required to authenticate
        $this->ValidateStringParam($this->code, self::MSG_CODE_UNKNOWN);
    }
    
    protected function HandleRequestImpl(ForumDb $db) : User
    {
        // reset the internal values first
        $this->user = null;
        
        // Check if the code matches an existing entry
        $userId = $db->VerifyPasswordResetCode($this->code, false);
        if($userId <= 0)
        {
            throw new InvalidArgumentException(self::MSG_CODE_UNKNOWN, parent::MSGCODE_BAD_PARAM);
        }
        
        // Check if a user exists for that code
        $this->user = User::LoadUserById($db, $userId);
        if(!$this->user)
        {
            throw new InvalidArgumentException(self::MSG_CODE_UNKNOWN, parent::MSGCODE_BAD_PARAM);
        }
        
        // fine, return that user
        return $this->user;   
    }
    
    public function GetCode() : string
    {
        return $this->code;
    }
    
    public function GetType() : string
    {
        return ConfirmHandler::VALUE_TYPE_RESETPASS;
    }
    
    public function GetConfirmText() : string
    {
        return 'Wähle ein neues Passwort. Das Passwort muss mindestens '
                . YbForumConfig::MIN_PASSWWORD_LENGTH 
                . ' Zeichen enthalten:';
    }
    
    public function GetSuccessText() : string
    {
        return 'Passwort erfolgreich aktualisiert';
    }
    
    private $code;
    private $user;
}
