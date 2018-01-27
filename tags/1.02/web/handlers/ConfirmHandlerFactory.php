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

require_once __DIR__.'/ConfirmHandler.php';
require_once __DIR__.'/ConfirmUserHandler.php';
require_once __DIR__.'/ConfirmUpdateEmailHandler.php';
require_once __DIR__.'/ConfirmResetPasswordHandler.php';

/**
 * Factory to create an confirmation handler. Depending on the value of the
 * parameter PARAM_TYPE, a corresponding handler is created. All handlers 
 * created implement the interface ConfirmHandler.
 * 
 * @author Elias Gerber
 */
class ConfirmHandlerFactory 
{
    public static function CreateHandler()
    {
        $type = null;
        if(filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'GET')
        {
            $type = filter_input(INPUT_GET, ConfirmHandler::PARAM_TYPE, FILTER_UNSAFE_RAW);
        }
        else if(filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'POST')
        {
            $type = filter_input(INPUT_POST, ConfirmHandler::PARAM_TYPE, FILTER_UNSAFE_RAW);
        }
        
        if($type === ConfirmHandler::VALUE_TYPE_CONFIRM_USER)
        {
            return new ConfirmUserHandler();
        }
        else if($type === ConfirmHandler::VALUE_TYPE_UPDATEEMAIL)
        {
            return new ConfirmUpdateEmailHandler();
        }
        else if($type === ConfirmHandler::VALUE_TYPE_RESETPASS)
        {
            return new ConfirmResetPasswordHandler();
        }
        else
        {
            throw new InvalidArgumentException('Invalid type', 400);
        }
    }
}
