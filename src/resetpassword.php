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
require_once __DIR__.'/handlers/ConfirmHandlerFactory.php';
require_once __DIR__.'/handlers/ConfirmResetPasswordHandler.php';
require_once __DIR__.'/handlers/UpdatePasswordHandler.php';
require_once __DIR__.'/pageparts/ResetPasswordForm.php';
?>

<html lang="de-ch">
    <head>
        <link rel="stylesheet" type="text/css" href="ybforum.css?v=r183">
        <link rel="icon" type="image/x-icon" href="favicon.ico">
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="theme-color" content="#FFCC00">
        <title>YB Forum</title>
    </head>
    <body>
        <div class="fullwidthcenter">
        <?php
        // evaluate the type
        try
        {
            // Get the correct handler, it must be a ConfirmResetPasswordHandler
            $handler = ConfirmHandlerFactory::CreateHandler();
            if($handler->GetType() != ConfirmHandler::VALUE_TYPE_RESETPASS)
            {
                throw new InvalidArgumentException(
                        ConfirmResetPasswordHandler::MSG_CODE_UNKNOWN, 
                        BaseHandler::MSGCODE_BAD_PARAM);
            }
            
            // let the handler validate the code
            $db = new ForumDb(false);
            $handler->HandleRequest($db);

            // If this is a POST request, we might have data to update 
            // the password, else simply display the form
            // also display the form if changing the password failed, 
            // it was probably a not matching password or such
            // (if code is invalid we would have failed earlier)
            $showForm = filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'GET';
            if(filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'POST')
            {
                // try to execute the change
                try
                {
                    $user = $handler->GetUser();
                    $updatePasswordHandler = new UpdatePasswordHandler($user);
                    $updatePasswordHandler->HandleRequest($db);
                    // changing succeeded, show the success state
                    // and remove the code from the database
                    $db->RemoveResetPasswordCode($user->GetId());
                    echo '<div class="fbold successcolor">';
                    echo $handler->GetSuccessText();
                    echo ' Dieses Fenster kann jetzt geschlossen werden.';
                    echo '</div>';
                }
                catch(InvalidArgumentException $ex)
                {
                    // display the error and show the form again
                    echo '<div class="failcolor">'
                            . '<span class="fbold">Fehler: </span>'
                            . $ex->GetMessage() . '</span></div>';                    
                    $showForm = true;
                }
            }
            if($showForm)
            {
                $resetPassForm = new ResetPasswordForm($handler);
                echo $resetPassForm->RenderHtmlDiv();
            }
        }
        catch(InvalidArgumentException $ex)
        {
            echo '<div class="failcolor">'
                    . '<span class="fbold">Fehler: </span>'
                    . $ex->GetMessage() . '</span></div>';
        }
        catch(Exception $ex)
        {
            ErrorHandler::OnException($ex);
        }
        ?>
        </div>
        <?php
        include __DIR__.'/pageparts/StandWithUkr.php';
        ?>
    </body>
</html>
