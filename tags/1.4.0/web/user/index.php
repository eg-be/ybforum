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

require_once __DIR__.'/../model/ForumDb.php';
require_once __DIR__.'/../helpers/ErrorHandler.php';
require_once __DIR__.'/../handlers/UpdatePasswordHandler.php';
require_once __DIR__.'/../handlers/UpdateEmailHandler.php';

try
{
    if(!session_start())
    {
        throw new Exception('session_start() failed');
    }
    
    // Do not cache this page
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Wed, 26 Jan 1983 01:00:00 GMT');

    if(!isset($_SESSION['userid']))
    {
        header('Location: ../stammposter.php');
        exit;
    }

    // Handle Logout
    $logoutValue = filter_input(INPUT_GET, 'logout', FILTER_VALIDATE_INT);
    if($logoutValue)
    {
        session_unset();
        session_destroy();
        header('Location: ../stammposter.php');
        exit;
    }

    // Get and verify user
    $db = new ForumDb(false);
    $user = User::LoadUserById($db, $_SESSION['userid']);
    // If user is a dummy or inactive, get out
    if($user->IsDummyUser() || (!$user->IsActive() && !$user->NeedsMigration()))
    {
        session_unset();
        session_destroy();
        header('Location: ../stammposter.php');
        exit;
    }
    
    // Check what action we shall do and what handlers are required
    $updatePasswordHandler = null;
    if(filter_input(INPUT_GET, 'updatepassword', FILTER_VALIDATE_INT) > 0)
    {
        $updatePasswordHandler = new UpdatePasswordHandler($user);
        try
        {
            $updatePasswordHandler->HandleRequest($db);
        } catch (InvalidArgumentException $ex) {
            // show some error later
        }
    }
    
    $updateEmailHandler = null;
    if(filter_input(INPUT_GET, 'updateemail', FILTER_VALIDATE_INT) > 0)
    {
        $updateEmailHandler = new UpdateEmailHandler($user);
        try
        {
            $updateEmailHandler->HandleRequest($db);
        } catch (InvalidArgumentException $ex) {
            // show some error later
        }
    }
}
catch(Exception $ex)
{
    ErrorHandler::OnException($ex);
}
?>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="../ybforum.css">
        <title>YB Forum</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">        
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    </head>
    <body>
        <div  style="max-width: 700px; margin: auto;">
            <img style="max-width: 100%; height: auto;" src="../logo.jpg" alt="YB Forum"/>
        </div>
        <div class="fullwidthcenter generictitle">Stammposter-Bereich von <span class="fitalic"><?php echo $user->GetNick(); ?></span></div>    
        <hr>
        <div class="fullwidthcenter">
            [ <a href="index.php?logout=1">Logout</a> ] 
        </div>
        <hr>
        <div>
            <form method="post" action="index.php?updatepassword=1" accept-charset="utf-8">            
                <table style="margin: auto; text-align: left; padding-top: 2em;">
                    <tr><td colspan="2" class="genericsmalltitle">Passwort ändern</td></tr>
                    <tr><td class="fbold">Neues Passwort:</td><td><input type="password" name="<?php echo UpdatePasswordHandler::PARAM_NEWPASS; ?>" size="20" maxlength="60"/></td></tr>
                    <tr><td class="fbold">Passwort bestätigen</td><td><input type="password" name="<?php echo UpdatePasswordHandler::PARAM_CONFIRMNEWPASS; ?>" size="20" maxlength="60"/></td></tr>
                    <tr><td colspan="2"><input type="submit" value="Passwort ändern"/></td></tr>
                </table>
            </form>
            <?php 
            if($updatePasswordHandler)
            {
                if($updatePasswordHandler->HasException())
                {
                    $ex = $updatePasswordHandler->GetLastException();
                    echo '<div class="fullwidthcenter failcolor"><span class="fbold">Fehler: </span>' . $ex->getMessage() . '</div>';
                }
                else
                {
                    echo '<div class="fullwidthcenter successcolor fbold">Passwort aktualisiert</div>';
                }
            }
            ?>
            <form method="post" action="index.php?updateemail=1" accept-charset="utf-8">
                <table style="margin: auto; text-align: left; padding-top: 2em;">
                    <tr><td colspan="2" class="genericsmalltitle" style="padding-top: 2em">Mailadresse aktualisieren</td></tr>
                    <tr>
                        <td class="fbold">Aktuelle Mailadresse:</td>
                        <td class="fitalic"><?php echo $user->GetEmail(); ?></td>
                    </tr>
                    <tr>
                        <td class="fbold">Neue Mailadresse:</td>
                        <td><input type="text" value="" name="<?php echo UpdateEmailHandler::PARAM_NEWEMAIL; ?>" size="30" maxlength="191"/></td>
                    </tr>
                    <tr><td colspan="2"><input type="submit" value="Mailadresse ändern"/></td></tr>
                </table>
            </form>
            <div class="fullwidthcenter">Um deine Mailadresse zu ändern wird dir ein Bestätigungslink geschickt welchen du innert 
                    <?php 
                    $validFor = new DateInterval(YbForumConfig::CONF_CODE_VALID_PERIOD);
                    echo $validFor->format('%h Stunden'); ?> 
                    besuchen musst. Ansonsten bleibt deine alte Mailadresse hinterlegt.
            </div>            
            <?php 
            if($updateEmailHandler)
            {
                if($updateEmailHandler->HasException())
                {
                    $ex = $updateEmailHandler->GetLastException();
                    echo '<div class="fullwidthcenter failcolor"><span class="fbold">Fehler: </span>' . $ex->getMessage() . '</div>';
                }
                else
                {
                    echo  
                    '<div class="fbold successcolor fullwidthcenter">Ein Bestätigungslink wurde dir an die Mailadresse 
                    <span class="fbold fitalic" id="confirm_mailaddress">' . $updateEmailHandler->GetNewEmail() . '</span> gesendet. 
                    Bitte besuche den Link um diese Mailadresse zu aktivieren.
                    </div>';
                }
            }
            ?>
        </div>
    </body>
</html>
