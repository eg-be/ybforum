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
require_once __DIR__.'/pageparts/MigrateUserForm.php';
require_once __DIR__.'/helpers/ErrorHandler.php';
require_once __DIR__.'/handlers/MigrateUserHandler.php';

try
{
    if(!session_start())
    {
        throw new Exception('start_session() failed');
    }
    
    // Determine what we have to do
    $migrateUserHandler = null;
    $db = new ForumDb();
    $migrationSucceeded = false;
    $alreadyMigrated = false;
    if(filter_input(INPUT_GET, 'migrate', FILTER_VALIDATE_INT) > 0)
    {
        // Try to submit passed migration data
        try
        {
            $migrateUserHandler = new MigrateUserHandler();
            $newPostId = $migrateUserHandler->HandleRequest($db);
            $migrationSucceeded = true;
           // migration succeeded, show some info
        }
        catch(InvalidArgumentException $ex)
        {
            // Migration failed, show error latee
            $alreadyMigrated = ($ex->GetMessage() == MigrateUserHandler::MSG_ALREADY_MIGRATED);
        }
    }
    else
    {
        // Someone arrived here from a page that requested to migrate
        // Rember where from if the value is set:
        $source = trim(filter_input(INPUT_GET, 'source', FILTER_UNSAFE_RAW));
        if($source)
        {      
            $_SESSION['source'] = urldecode($source);
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
        <title>Benutzer migrieren</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">                
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    </head>
    <body>
        <div  style="max-width: 700px; margin: auto;">
            <img style="max-width: 100%; height: auto;" src="logo.jpg" alt="YB Forum"/>
        </div>    
        <div class="fullwidthcenter generictitle">Benutzer migrieren</div>
        <hr>
        <div class="fullwidthcenter">
            [ <a href="index.php">Forum</a> ]
        </div>
        <hr>
        <?php
            if($migrateUserHandler && $migrateUserHandler->HasException() && !$alreadyMigrated)
            {
                $migrationException = $migrateUserHandler->GetLastException();
                echo '<div id="status" class="fullwidthcenter failcolor">'
                    . '<span class="fbold">Fehler: </span>'
                    . $migrationException->GetMessage()
                    . '</div>';
            }
        ?>
        <div id="migrationformcontainer" class="fullwidthcenter">
            <?php 
            // If we have a return-path, read that
            $source = null;
            if(isset($_SESSION['source']))
            {
                $source = $_SESSION['source'];
            }            
            if(!($migrationSucceeded || $alreadyMigrated))
            {
                // If we do not have a MigrateUserHandler, try to read values from passed argument nick
                $initialNick = null;
                $initialEmail = null;
                if(!$migrateUserHandler)
                {
                    $initialNickGetValue = trim(filter_input(INPUT_GET, 'nick', FILTER_UNSAFE_RAW));
                    $initialEmailGetValue = trim(filter_input(INPUT_GET, 'email', FILTER_UNSAFE_RAW));
                    if($initialNickGetValue)
                    {
                        $initialNick = urldecode($initialNickGetValue);
                    }
                    if($initialEmailGetValue)
                    {
                        $initialEmail = urldecode($initialEmailGetValue);
                    }
                }
                else
                {
                    $initialNick = $migrateUserHandler->GetNick();
                    $initialEmail = $migrateUserHandler->GetNewEmail();
                }
                $muf = new MigrateUserForm($initialNick, $initialEmail, $source);
                echo $muf->renderHtmlDiv();
            }
            else
            {
                if($migrationSucceeded)
                {
                    echo  
                    '<div class="fbold successcolor">Ein Bestätigungslink wurde dir an die Mailadresse 
                    <span class="fbold fitalic" id="confirm_mailaddress">' . $migrateUserHandler->GetNewEmail() . '</span> gesendet. 
                    Bitte besuche den Link um die Migration abzuschliessen und dein neues Passwort zu aktivieren.
                    </div>';
                }
                else if($alreadyMigrated)
                {
                    echo 
                    '<div class="fbold successcolor">
                    Benutzer bereits migriert, neues Passwort kann verwendet werden.
                    </div>';
                }
                if($source)
                {
                    echo '<div><a href="'. urlencode($source) .'?migrationended=1">Zurück</a></div>';
                }
            }
            ?>
        </div>
    </body>
</html>
