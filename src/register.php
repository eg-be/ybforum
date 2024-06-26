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
require_once __DIR__.'/helpers/CaptchaV3Config.php';
require_once __DIR__.'/pageparts/TopNavigation.php';
require_once __DIR__.'/pageparts/Logo.php';

try
{
    if(!session_start())
    {
        throw new Exception('session_start() failed');
    }

    $db = new ForumDb(false);
    
    $registerUserHandler = null;
    if(YbForumConfig::REGISTRATION_OPEN && filter_input(INPUT_GET, 'register', FILTER_VALIDATE_INT) > 0)
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
        <link rel="stylesheet" type="text/css" href="ybforum.css?v=<?php echo YbForumConfig::CSS_REV ?>">
        <link rel="icon" type="image/x-icon" href="favicon.ico">
        <title>YB Forum</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="theme-color" content="#FFCC00">
        <?php
        if(CaptchaV3Config::CAPTCHA_VERIFY)
        {
            echo '<script src=\'https://www.google.com/recaptcha/api.js\'></script>' . PHP_EOL;
            echo '<script>
            function onSubmit(token) {
              document.getElementById("register-form").submit();
            }
          </script>' . PHP_EOL;
        }
        ?>
    </head>
    <body>
        <?php
        try
        {
            $logo = new Logo();
            echo $logo->renderHtmlDiv();
        }
        catch(Exception $ex)
        {
            ErrorHandler::OnException($ex);
        }
        ?>
        <div class="fullwidthcenter generictitle">Stammposter Registrierungsantrag</div>    
        <hr>
        <?php
        try
        {
            $topNav = new TopNavigation();
            echo $topNav->renderHtmlDiv();
        }
        catch(Exception $ex)
        {
            ErrorHandler::OnException($ex);
        }
        ?>
        <hr>
        <div class="fullwidthcenter" style="padding-top:1em">
			<div><span class="fbold">Weitergabe von Tickets</span></div>		
			<div>
            Tickets können über die <a href="https://www.ostkurve.be/abo-boerse/">ABO-BÖRSE</a> 
			der Ostkurve weitergeben werden. Bitte kein Tickethandel im Forum.
			</div>
		</div>
        <div class="fullwidthcenter" style="padding-top:1em">
			<div><span class="fbold">Urheberrecht</span></div>		
			<div>
            Bitte postet keine Inhalte, insbesondere Artikel aus Zeitungen und Zeitschriften, welche
			das Urheberrecht verletzen. Entsprechende Posts werden gelöscht und der Benutzer wird gesperrt.
			</div>
		</div>
        <div class="fullwidthcenter" style="padding-top: 1em">
			<div><span class="fbold" >Bitte verwende eine gültige Emailadresse</span></div>		
			<div>
            Die hier angebenen Emailadresse wird nicht im Forum gezeigt. Sie 
            dient lediglich als Kontaktadresse. 
			Um die Registrierung abzuschliessen, muss die Emailadresse bestätigt werden.
			</div>
		</div>
        <div class="fullwidthcenter" style="padding-bottom: 2em; padding-top: 1em">
			<div><span class="fbold">Die Freischaltung kann einige Zeit dauern.</span></div>
			<div >Bitte hinterlasse uns im Feld Nachricht eine kurze Nachricht.</div>
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
            <form id="register-form" method="post" action="register.php?register=1" accept-charset="utf-8">
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
                        <td>
                            <?php
                            if(YbForumConfig::REGISTRATION_OPEN)
                            {
                                if(CaptchaV3Config::CAPTCHA_VERIFY)
                                {
                                    echo '<button class="g-recaptcha" 
                                    data-sitekey="'. CaptchaV3Config::CAPTCHA_SITE_KEY .'" 
                                    data-callback=\'onSubmit\' 
                                    data-action=\'' . CaptchaV3Config::CAPTCHA_REGISTER_USER_ACTION . '\'>Registrieren</button>' . PHP_EOL;
                                }
                                else
                                {
                                    echo '<input type="submit" value="Registrieren"/>' . PHP_EOL;
                                }
                            }
                            else
                            {
                                echo '<input type="submit" value="Registrieren" disabled/><span class="fbold failcolor">Registrierung zurzeit geschlossen</span>' . PHP_EOL;
                            }
                            ?>
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
        <?php
        include __DIR__.'/pageparts/StandWithUkr.php';
        ?>        
    </body>
</html>