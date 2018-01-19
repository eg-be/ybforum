<!DOCTYPE html>
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

require_once __DIR__.'/model/ForumDb.php';
require_once __DIR__.'/handlers/UpdatePasswordHandler.php';
require_once __DIR__.'/handlers/ConfirmHandler.php';

try
{
    if(!session_start())
    {
        throw new Exception('session_start() failed');
    }
} catch (Exception $ex) 
{
    ErrorHandler::OnException($ex);
}

?>

<html lang="de-ch">
    <head>
        <link rel="stylesheet" type="text/css" href="ybforum.css">        
        <meta charset="UTF-8">
        <title>YB Forum</title>
    </head>
    <body>
        <?php
        
        function writeChangePasswordForm()
        {
            echo '<div class="fullwidthcenter">'
                    . '<form method="post" action="resetpassword.php" accept-charset="utf-8">'
                    . '<table style="margin:auto">'
                    . '<tr>'
                    . '<td>Neues Passwort:</td><td><input type="password" name="' . UpdatePasswordHandler::PARAM_NEWPASS . '" required="required"/></td>'
                    . '</tr>'
                    . '<tr>'
                    . '<td>Passwort bestätigen:</td><td><input type="password" name="' . UpdatePasswordHandler::PARAM_CONFIRMNEWPASS .  '" required="required"/></td>'
                    . '</tr>'
                    . '<tr>'
                    . '<td colspan="2"><input type="submit" value="Passwort setzen"/></td>'
                    . '</tr>'
                    . '</table>'
                    . '</form>'
                    . '</div>';
        }
        
        function writeFailure(string $msg)
        {
            echo '<div class="fullwidthcenter fbold" style="color: red">'
                    . $msg
                    . '</div>';
        }
        
        function writeSuccess(string $msg)
        {
            echo '<div class="fullwidthcenter fbold" style="color: #33cc33">'
                    . $msg
                    . '</div>';
        }       
        
        function abort($msg = 'Bestätigungscode abgelaufen, unbekannt oder bereits verwendet')
        {
            if(isset($_SESSION['updatepasswordtoken'])
                && is_string($_SESSION['updatepasswordtoken'])
                && !empty($_SESSION['updatepasswordtoken']))
            {
                // remove any code, if there is one in the session
                $code = $_SESSION['updatepasswordtoken'];
                $db = new ForumDb();
                $db->VerifyPasswortResetCode($code, true);
            }            
            session_unset();
            session_destroy();
            if(!empty($msg))
            {
                echo '<div style="color:red" class="fullwidthcenter">' . $msg . '</div>';
            }
            exit;
        }
                
        // Check if we have a valid updatepasswordtoken in our session
        // If we have a code as GET param, always take that code
        $paramCode = filter_input(INPUT_GET, ConfirmHandler::PARAM_CODE, FILTER_UNSAFE_RAW);
        if($paramCode)
        {
            $code = urldecode($paramCode);
            if(!$code)
            {
                abort();
            }

            // Test if token still valid. Do not remove it yet
            $db = new ForumDb();
            $userId = $db->VerifyPasswortResetCode($code, false);
            if($userId > 0)
            {
                // ok, token is valid, remember token for this seesion
                $_SESSION['updatepasswordtoken'] = $code;
                writeChangePasswordForm();
            }
            else
            {
                abort();
            }
        }
        else if(isset($_SESSION['updatepasswordtoken']) 
            && is_string($_SESSION['updatepasswordtoken'])
            && !empty($_SESSION['updatepasswordtoken']))
        {
            // we have a code in our session
            $code = $_SESSION['updatepasswordtoken'];
            // The code was found, but is it still valid?
            $db = new ForumDb();
            $userId = $db->VerifyPasswortResetCode($code, false);
            if($userId > 0)
            {
                // ok, token is still valid, check the user behind the token:
                $user = User::LoadUserById($db, $userId);
                if(!$user)
                {
                    abort('User nicht mehr vorhanden');                
                }
                try
                {
                    $updatePasswordHandler = new UpdatePasswordHandler($user);
                    $updatePasswordHandler->HandleRequest($db);
                    // Done. Remove the session and notify user that we are ready
                    writeSuccess('Dein neues Passwort ist ab sofort gültig. Dieses Fenster kann nun geschlossen werden.');
                    abort('');                
                }
                catch(InvalidArgumentException $ex)
                {
                    if($ex->getMessage() == UpdatePasswordHandler::MSG_USER_INACTIVE
                            || $ex->getMessage() === UpdatePasswordHandler::MSG_DUMMY_USER)
                    {
                        // If the user is inactive or deactivted, do not allow
                        // the user to try a second time
                        abort('Inaktive und Dummyuser können nicht aktiviert werden');
                    }
                    else
                    {
                        // failed, but probably due to too short password, wrong
                        // confirmatin or similar. Inform the user and let her
                        // try again
                        writeFailure($ex->getMessage());
                        writeChangePasswordForm();
                    }
                }
            }
            else
            {
                // token is no longer valid, abort
                abort();
            } 
        }
        else
        {        
            // if we have no code in our session, or set as get param, just fail
            abort();
        }
        ?>
    </body>
</html>
