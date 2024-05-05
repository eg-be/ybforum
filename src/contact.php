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

require_once __DIR__.'/helpers/ErrorHandler.php';
require_once __DIR__.'/handlers/ContactHandler.php';
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
  
    $contactHandler = null;
    if(filter_input(INPUT_GET, 'contact', FILTER_VALIDATE_INT) > 0)
    {
        $contactHandler = new ContactHandler();
        try
        {
            $db = new ForumDb();            
            $contactHandler->HandleRequest($db);
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
              document.getElementById("contact-form").submit();
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
        <div class="fullwidthcenter generictitle">Kontakt</div>    
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
        <div class="fullwidthcenter" style="padding-bottom: 2em;">
        Für alle administrativen oder technischen Fragen kann folgendes Kontaktformular benutzt werden.<br>
        Bitte gib eine <span class="fbold">gültige Mailadresse</span> an, ansonsten kann die Anfrage nicht beantwortet werden.
        </div>
        <?php 
        if($contactHandler && $contactHandler->HasException())
        {
            echo '<div class="failcolor fullwidthcenter">' .
                    '<span class="fbold">Fehler: </span>' .
                    $contactHandler->GetLastException()->GetMessage() .
                    '</div>';
        }
        ?>
        <div>
            <form id="contact-form" method="post" action="contact.php?contact=1" accept-charset="utf-8">
                <?php
                $emailValue = '';
                $emailValueRepeat = '';
                $msgValue = '';
                if($contactHandler)
                {
                    $emailValue = $contactHandler->GetEmail();
                    $emailValueRepeat = $contactHandler->GetEmailRepeat();
                    $msgValue = $contactHandler->GetMsg();
                }
                ?>
                <table style="margin: auto;">
                    <tr>
                        <td class="fbold">Mailadresse:</td>
                        <td><input type="text" value="<?php echo $emailValue; ?>" name="<?php echo ContactHandler::PARAM_EMAIL; ?>" size="30" maxlength="191"/></td>
                    </tr>
                    <tr>
                        <td class="fbold">Mailadresse wiederholen:</td>
                        <td><input type="text" value="<?php echo $emailValueRepeat; ?>" name="<?php echo ContactHandler::PARAM_EMAIL_REPEAT; ?>" size="30" maxlength="191"/></td>
                    </tr>
                    <tr>
                        <td colspan="2" class="fbold">Nachricht an die Forenadministration:</td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <textarea name="<?php echo ContactHandler::PARAM_MSG; ?>" cols="85" rows="10"><?php echo $msgValue; ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php
                            if(CaptchaV3Config::CAPTCHA_VERIFY)
                            {
                                echo '<button class="g-recaptcha" 
                                data-sitekey="'. CaptchaV3Config::CAPTCHA_SITE_KEY .'" 
                                data-callback=\'onSubmit\' 
                                data-action=\'' . CaptchaV3Config::CAPTCHA_CONTACT_ACTION . '\'>Senden</button>' . PHP_EOL;
                            }
                            else
                            {
                                echo '<input type="submit" value="Senden"/>' . PHP_EOL;
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
        if($contactHandler && !$contactHandler->HasException())
        {
            echo  
            '<div class="fbold fullwidthcenter successcolor">Die Nachricht wurde gesendet.
            </div>';
        }
        ?>
        <?php
        include __DIR__.'/pageparts/StandWithUkr.php';
        ?>        
    </body>
</html>