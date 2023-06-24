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

/**
 * Interface for all confirmation handlers.
 * The interface allows to read the required parameters after they have
 * been read by the handlers back again, so the process can be repeated.
 * Like this, we can when the user clicks on the link, or if some antivirus,
 * spam-filter or preview things examines the link, read all params and
 * check if they would still be valid, but not really execute the confirmation.
 * The confirmation can later be executed by a simple form forcing the user
 * to click some button, to avoid accidental confirmation from a tool like
 * the ones mentioned above.
 * 
 * @author Elias Gerber
 */
interface ConfirmHandler 
{
    /**
     * @var string Parameter name for confirmation type.
     */
    const PARAM_TYPE = 'type';
    
    /**
     * @var string Parameter name for confirmation code.
     */
    const PARAM_CODE = 'code';
    
    /**
     * @var string Parameter value for the confirm type of confirming
     * a user (migration / registration)
     */
    const VALUE_TYPE_CONFIRM_USER = 'confirmuser';
    
    /**
     * @var string Parameter value for the confirm type of confirming a
     * new email address for a user.
     */
    const VALUE_TYPE_UPDATEEMAIL = 'updateemail';    
    
    /**
     * @var string Parameter value for the confirmation link to reset a 
     * password.
     */    
    const VALUE_TYPE_RESETPASS = 'resetpass';
    
    /**
     * @return string Get the confirmation code.
     */
    public function GetCode() : string;
    
    /**
     * @return string Get the confirmation type.
     */
    public function GetType() : string;
    
    /**
     * @return string Get the text to display to the user, asking him to
     * trigger some button to complete the confirmation process.
     */
    public function GetConfirmText() : string;
    
    /**
     * @return string Get the text to display to the user once confirmation
     * process has completed with success.
     */
    public function GetSuccessText() : string;
}
