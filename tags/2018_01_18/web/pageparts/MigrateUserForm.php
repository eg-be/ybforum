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

require_once __DIR__.'/../handlers/MigrateUserHandler.php';

/**
 * Print a form holding field to migrate a user.
 *
 * @author Elias Gerber
 */
class MigrateUserForm 
{
    /**
     * Create a form that will use the passed values as initial values for
     * the input fields and for the return address.
     * @param mixed $initialNick String or null. Initial value for nick field.
     * @param mixed $initialEmail String or null. Initial value for email field.
     * @param mixed $source String or null. URL to use as location if the user
     * chooses to cancel migration. A 'migrationended=1' parameter will be 
     * appended to the URL. If null is passed, the user will be 
     * redirected to index.php, without any additional arguments.
     */
    public function __construct($initialNick, $initialEmail, $source) 
    {
        $this->m_initialNick = $initialNick;
        $this->m_initialEmail = $initialEmail;
        $this->m_source = $source;
    }
    
    /**
     * Renders a HTML div holding the migration form. On submit calls
     * 'migrateuser.php?migrate=1'.
     * @return string
     */
    public function renderHtmlDiv()
    {
        $returnValue = 'index.php';
        if($this->m_source)
        {
            $returnValue = $this->m_source . '?migrationended=1';
        }
        $nickValue = $this->m_initialNick;
        $emailValue = $this->m_initialEmail;
        $html = 
        '<div id="migratediv" style="color: #00BFFF;">
            <div id="migratetitle" class="fbold noticecolor">Passwort und Mailadresse aktualisieren:</div>
            <div id="requestmigratediv"> Willkommen im Forum 2.0. 
                Die bisherigen Passwörter sind in einem nicht mehr zeitgemässen Format
                verschlüsselt. Bitte aktualisiere dein Passwort und bei dieser 
                Gelegenheit auch gleich deine Mailadresse. Um die Migration abzuschliessen 
                wird dir ein Link an deine Mailadresse gesendet.
                <form id="requestmigrateform" method="post" action="migrateuser.php?migrate=1" accept-charset="utf-8">
                    <table style="margin: auto; text-align: left;">
                        <tr>
                            <td><span class="fbold">Stammpostername:</span></td>
                            <td><input type="text" value="' . $nickValue . '" name="' . MigrateUserHandler::PARAM_NICK . '" size="20" maxlength="60"/></td>
                        </tr>                    
                        <tr>
                            <td><span class="fbold">Bisheriges Passwort:</span></td>
                            <td><input type="password" name="' . MigrateUserHandler::PARAM_OLDPASS . '" size="20" maxlength="60"/></td>
                        </tr>
                        <tr>
                            <td><span class="fbold">Neues Passwort (mind. 8 Zeichen):</span></td>
                            <td><input type="password" name="' . MigrateUserHandler::PARAM_NEWPASS . '" size="20" maxlength="60"/></td>
                        </tr>
                        <tr>
                            <td><span class="fbold">Neues Passwort bestätigen:</span></td>
                            <td><input type="password" name="' . MigrateUserHandler::PARAM_CONFIRMNEWPASS . '" size="20" maxlength="60"/></td>
                        </tr>
                        <tr>
                            <td><span class="fbold">Mailadresse:</span></td>
                            <td><input type="text" value="' . $emailValue . '" name="' . MigrateUserHandler::PARAM_NEWEMAIL . '" size="20" maxlength="191"/></td>
                        </tr>
                        <tr>
                            <td colspan="2" class="noticecolor">
                                <span class="fbold">Achtung: </span>Bitte überprüfe, dass die angegebene Mailadresse korrekt ist!
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <input type="submit" value="Passwort ändern und Mailadresse bestätigen"/>
                                <input type="button" value="Abbrechen" onclick="document.location = \'' . $returnValue . '\';"/>
                            </td>
                        </tr>          
                    </table>            
                </form>
            </div>
        </div>';
        return $html;
    }
    
    private $m_initialNick;
    private $m_initialEmail;
    private $m_source;
}
