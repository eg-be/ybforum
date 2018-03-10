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
require_once __DIR__.'/helpers/ErrorHandler.php';
require_once __DIR__.'/handlers/RegisterUserHandler.php';
require_once __DIR__.'/YbForumConfig.php';

try
{
    if(!session_start())
    {
        throw new Exception('session_start() failed');
    }

    $db = new ForumDb();
    
    $registerUserHandler = null;
    if(filter_input(INPUT_GET, 'register', FILTER_VALIDATE_INT) > 0)
    {
        $registerUserHandler = new RegisterUserHandler();
        try
        {
            $registerUserHandler->HandleRequest($db);
        }
        catch(InvalidArgumentException $ex)
        {
            // do some output of the error later
        }
    }
}
catch(Exception $ex)
{
    ErrorHandler::OnException($ex);
}
?>

<html lang="de-ch">
    <head>
        <link rel="stylesheet" type="text/css" href="ybforum.css">
        <title>YB Forum</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <script src='https://www.google.com/recaptcha/api.js'></script>        
        <script type='text/javascript'>
            function foo()
            {
                alert("foo");
            }
        </script>
    </head>
    <body>
        <div  style="max-width: 700px; margin: auto;">
            <img style="max-width: 100%; height: auto;" src="logo.jpg" alt="YB Forum"/>
        </div>
        <div class="fullwidthcenter generictitle">Stammposter Registrierungsantrag</div>    
        <hr>
        <div class="fullwidthcenter">
            [ <a href="index.php">Forum</a> ] 
            [ <a href="recent.php">Neue Beiträge</a> ] 
            [ <a href="search.php">Suchen</a> ] 
            [ <a href="textformatierung.html">Textformatierung</a> ] 
            [ <a href="stammposter.php">Stammposter</a> ]            
        </div>
        <hr>
        <div class="fullwidthcenter" style="padding-bottom: 2em;">
            Ihre hier angebenen Emailadresse wird nicht im Forum gezeigt. Sie 
            dient lediglich dem Forenadministrator dazu einen Anhaltspunkt zu 
            haben, wer seine Stammposter eigentlich sind. Bevor Ihr Antrag vom 
            Forenadministrator überhaupt betrachtet wird, wird Ihre Emailadresse verifiziert. 
            An Ihre Emailadresse sendet das System Ihnen einen Link, den Sie 
            besuchen müssen. Damit wird die Korrektheit Ihrer Emailadresse 
            bestätigt.
        </div>
        <div class="fullwidthcenter"><span class="fbold">Die Kriterien ob eine Registrierung akzeptiert wird oder nicht sind absolut willkürlich. Rückfragen werden nicht beantwortet.</span> 
        Mit einer netten Nachricht an die Administratoren, Referenzen auf existierende Stammposter und einem Nickname der kein Spielername und nicht allzu primitiv ist, kann die Chance aber erhöht werden.        
        </div>
        <?php 
        if($registerUserHandler && $registerUserHandler->HasException())
        {
            echo '<div class="failcolor fullwidthcenter">' .
                    '<span class="fbold">Fehler: </span>' .
                    $registerUserHandler->GetLastException()->GetMessage() .
                    '</div>';
        }
        ?>
        <div>
            <form method="post" action="register.php?register=1" accept-charset="utf-8">
                <?php
                $nickValue = '';
                $emailValue = '';
                $regMsgValue = '';
                if($registerUserHandler)
                {
                    $nickValue = $registerUserHandler->GetNick();
                    $emailValue = $registerUserHandler->GetEmail();
                    $regMsgValue = $registerUserHandler->GetRegMsg();
                }
                ?>
                <table style="margin: auto;">
                    <tr>
                        <td class="fbold">Nickname</td>
                        <td><input type="text" value="<?php echo $nickValue; ?>" name="<?php echo RegisterUserHandler::PARAM_NICK; ?>" size="20" maxlength="60"/></td>
                    </tr>
                    <tr>
                        <td class="fbold">Passwort (mind. 8 Zeichen):</td>
                        <td><input type="password" name="<?php echo RegisterUserHandler::PARAM_PASS; ?>" size="20" maxlength="60"/></td>
                    </tr>
                    <tr>
                        <td class="fbold">Passwortwiederholung:</td>
                        <td><input type="password" name="<?php echo RegisterUserHandler::PARAM_CONFIRMPASS; ?>" size="20" maxlength="60"/></td>
                    </tr>
                    <tr>
                        <td class="fbold">Mailadresse</td>
                        <td><input type="text" value="<?php echo $emailValue; ?>" name="<?php echo RegisterUserHandler::PARAM_EMAIL; ?>" size="30" maxlength="191"/></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="fbold">Nachricht an die Forenadministration</td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <textarea name="<?php echo RegisterUserHandler::PARAM_REGMSG; ?>" cols="85" rows="10"><?php echo $regMsgValue; ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" class="failcolor fbold">Die Registrierung wurd nächstens überarbeitet und ist momentan geschlossen.</td>
                    </tr>
                    <?php
                    if(YbForumConfig::CAPTCHA_VERIFY)
                    {
                        echo 
                        '<tr>
                            <td colspan="2">
                                <div class="g-recaptcha" data-sitekey="6Lc3HksUAAAAACle5q5Mi8qKlpJQVZJ5roh-bIVw"></div>                            
                            </td>
                        </tr>';
                    }
                    ?>
                    <tr>
                        <td>
                            <input type="submit" value="Registrieren"/>
                        </td>
                        <td>
                            <input type="reset" value="Eingaben löschen"/>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <?php
        if($registerUserHandler && !$registerUserHandler->HasException())
        {
            echo  
            '<div class="fbold fullwidthcenter successcolor">Ein Bestätigungslink wurde dir an die Mailadresse 
            <span class="fitalic">' . $registerUserHandler->GetEmail() . '</span> gesendet. 
            Bitte besuche den Link um die angegebene Mailadresse zu bestätigen.
            </div>';
        }
        ?>
    </body>
</html>