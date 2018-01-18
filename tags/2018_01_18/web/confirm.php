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
require_once __DIR__.'/handlers/ConfirmUserHandler.php';
require_once __DIR__.'/handlers/ConfirmUpdateEmailHandler.php';
require_once __DIR__.'/helpers/ErrorHandler.php';
require_once __DIR__.'/helpers/Mailer.php';
require_once __DIR__.'/helpers/Logger.php';
?>

<html lang="de-ch">
    <head>
        <link rel="stylesheet" type="text/css" href="ybforum.css">
        <title>YB Forum 2.0</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">  
    </head>
    <body>
        <div class="fullwidthcenter">
        <?php
        try
        {
            $successText = '';
            if(Mailer::IsPreviewRequest())
            {
                $logger = new Logger();
                $logger->LogMessage(Logger::LOG_CONFIRM_REQUEST_IGNORED_IS_PREVIEW, 'HTTP_USER_AGENT: ' . filter_input(INPUT_SERVER, 'HTTP_USER_AGENT', FILTER_UNSAFE_RAW));
                throw new InvalidArgumentException('Preview not supported for confirmation links', 400);
            }
            $db = new ForumDb();
            $type = urldecode(filter_input(INPUT_GET, Mailer::PARAM_TYPE, FILTER_UNSAFE_RAW));
            if($type === Mailer::VALUE_TYPE_CONFIRM_USER)
            {
                // complete migration / registration of user
                $cuh = new ConfirmUserHandler();
                $confirmSource = $cuh->HandleRequest($db);
                if($confirmSource === ForumDb::CONFIRM_SOURCE_MIGRATE)
                {
                    $successText = 'Migration erfolgreich abgeschlossen, dein neues '
                            . 'Passwort ist ab sofort gültig';
                }
                else
                {
                    $successText = 'Registrierung erfolgreich abgeschlossen. '
                            . 'Ein Administrator wird deinen Antrag begutachten '
                            . 'und dein Account bei Gelegenheit eventuell '
                            . 'freischalten. Du erhältst eine Email sobald '
                            . 'dein Account freigschaltet wurde.';
                }
            }
            else if($type === Mailer::VALUE_TYPE_UPDATEEMAIL)
            {
                // complete updating email address
                $cueh = new ConfirmUpdateEmailHandler();
                $cueh->HandleRequest($db);
                $successText = 'Mailadresse bestätigt, '
                        . 'dein neue Mailadresse ist ab sofort gültig.';
            }
            else
            {
                throw new InvalidArgumentException('Unbekannte Aktion');
            }
            if($successText)
            {
                echo '<span class="fbold successcolor">' .
                        $successText . '</span>';                
            }
        }
        catch(InvalidArgumentException $ex)
        {
            echo '<span class="failcolor">'
                    . '<span class="fbold">Fehler: </span>'
                    . $ex->GetMessage() . '</span>';
        }
        catch(Exception $ex)
        {
            ErrorHandler::OnException($ex);
        }
        ?>
        </div>
    </body>
</html>
