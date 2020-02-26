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
require_once __DIR__.'/pageparts/PostEntryForm.php';
require_once __DIR__.'/pageparts/MigrateUserForm.php';
require_once __DIR__.'/helpers/ErrorHandler.php';
require_once __DIR__.'/handlers/PostEntryHandler.php';

try
{
    if(!session_start())
    {
        throw new Exception('start_session() failed');
    }
    
    // Determine what we have to do
    $postEntryHandler = null;
    $parentPost = null;
    $db = new ForumDb(false);
    if(filter_input(INPUT_GET, 'post', FILTER_VALIDATE_INT) > 0)
    {
        // Try to submit passed post data
        try
        {
            $postEntryHandler = new PostEntryHandler();
            $newPostId = $postEntryHandler->HandleRequest($db);
            // posting succeeded
            session_destroy();
            header('Location: showentry.php?idpost=' . $newPostId);
            exit;
        }
        catch(InvalidArgumentException $ex)
        {
            // Posting failed. Reshow the form or if we are
            // requested to migrate move on to migrate user page
            if($ex->GetMessage() === PostEntryHandler::MSG_MIGRATION_REQUIRED)
            {
                // If we know that we need to migrate, we can also pass in some values for email and nick
                $user = User::LoadUserByNick($db, $postEntryHandler->GetNick());
                // Remember the current post data
                // But clear the last set exception, as that will hold
                // a stacktrace with some pdo object 
                $postEntryHandler->ClearLastException();                
                $_SESSION['posthandler'] = $postEntryHandler;
                header('Location: migrateuser.php?source=postentry.php&nick=' . urlencode($user->GetNick()) . '&email=' . urlencode($user->GetEmail()));
                exit;
            }
        }
    }
    else if(filter_input(INPUT_GET, 'migrationended', FILTER_VALIDATE_INT) > 0)
    {
        // Try to load an eventually set old post data
        if(isset($_SESSION['posthandler']))
        {
            $postEntryHandler = $_SESSION['posthandler'];
            unset($_SESSION['posthandler']);
            $parentPostId = $postEntryHandler->GetParentPostId();
            if($parentPostId > 0)
            {
                $parentPost = Post::LoadPost($db, $parentPostId);
            }
        }
        else
        {
            // stupid waited too long. make her return to index.php
            session_destroy();
            header('Location: index.php');
            exit;
        }
    }
    else
    {
        // Someone arrived here from the index page: If a new thread we have no 
        // idparentpost value set, for an answer the value is set.
        // Or someone arrived here as a completion of a migration
        $parentPostId = filter_input(INPUT_GET, 'idparentpost', FILTER_VALIDATE_INT);
        if($parentPostId > 0)
        {
            $parentPost = Post::LoadPost($db, $parentPostId);
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
        <link rel="stylesheet" type="text/css" href="ybforum.css?v=r181">
        <title>Beitrag schreiben</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="theme-color" content="#FFCC00">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <script src="js/formatentry.js?v=r148"></script>
        <script src="js/renderpost.js?v=r181"></script>
        <script src="js/preview.js"></script>
    </head>
    <body>
        <div  style="max-width: 700px; margin: auto;">
            <?php include __DIR__.'/logo.php'; ?>
        </div>    
        <div class="fullwidthcenter generictitle">Beitrag schreiben</div>
        <hr>
        <div class="fullwidthcenter">
            [ <a href="index.php">Forum</a> ] 
            [ <a href="recent.php">Neue Beiträge</a> ] 
            [ <a href="search.php">Suchen</a> ] 
            [ <a href="textformatierung.php">Textformatierung</a> ] 
            [ <a href="stammposter.php">Stammposter</a> ] 
            [ <a href="register.php">Registrieren</a> ]             
        </div>
        <hr>
        <?php
        try
        {
            if($postEntryHandler && $postEntryHandler->HasException())
            {
                $postException = $postEntryHandler->GetLastException();
                echo '<div id="status" class="fullwidthcenter" style="color: red;">'
                    . '<span class="fbold">Fehler: </span>'
                    . $postException->GetMessage()
                    . '</div>';
            }
        }
        catch(Exception $ex)
        {
            ErrorHandler::OnException($ex);
        }
        ?>
        <div id="postformcontainer" class="fullwidth">
        <?php 
        try
        {
            $pef = new PostEntryForm($parentPost, $postEntryHandler);
            echo $pef->renderHtmlForm();
            echo $pef->renderUsageTable();
        }
        catch(Exception $ex)
        {
            ErrorHandler::OnException($ex);
        }
        ?>
        </div>
    </body>
</html>
