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

require_once __DIR__ . '/model/ForumDb.php';
require_once __DIR__ . '/handlers/ConfirmUserHandler.php';
require_once __DIR__ . '/handlers/ConfirmUpdateEmailHandler.php';
require_once __DIR__ . '/handlers/ConfirmHandlerFactory.php';
require_once __DIR__ . '/helpers/ErrorHandler.php';
require_once __DIR__ . '/helpers/Mailer.php';
require_once __DIR__ . '/helpers/Logger.php';
require_once __DIR__ . '/pageparts/ConfirmForm.php';
?>

<html lang="de-ch">
    <head>
        <link rel="stylesheet" type="text/css" href="ybforum.css?v=<?php echo YbForumConfig::CSS_REV ?>">
        <link rel="icon" type="image/x-icon" href="favicon.ico">
        <title>YB Forum</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="theme-color" content="#FFCC00">
    </head>
    <body>
        <div class="fullwidthcenter">
        <?php
        try {
            // Get the correct handler
            $handler = ConfirmHandlerFactory::createHandler();

            // let the handler handle the request
            $db = new ForumDb(false);
            // If this is GET request, the handler will only simulate
            // but fail with a correct exception if something is wrong
            $handler->handleRequest($db);
            if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'GET') {
                // A GET request is probably a click on a link in a mail
                // output a form telling the user click the confirm button
                // to avoid getting confirmed by evil bots
                $confirmForm = new ConfirmForm($handler);
                echo $confirmForm->renderHtmlDiv();
            } else {
                // A POST request is something that was triggered by the form
                echo '<div class="fbold successcolor">';
                echo $handler->getSuccessText();
                echo ' Dieses Fenster kann jetzt geschlossen werden.';
                echo '</div>';
            }
        } catch (InvalidArgumentException $ex) {
            echo '<span class="failcolor">'
                    . '<span class="fbold">Fehler: </span>'
                    . $ex->GetMessage() . '</span>';
        } catch (Exception $ex) {
            ErrorHandler::OnException($ex);
        }
?>
        </div>
    </body>
</html>
