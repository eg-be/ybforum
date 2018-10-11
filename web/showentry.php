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
require_once __DIR__.'/pageparts/PostView.php';
require_once __DIR__.'/pageparts/ThreadIndexView.php';
require_once __DIR__.'/helpers/ErrorHandler.php';

try
{
    // Read the arguments required
    $idPost = filter_input(INPUT_GET, 'idpost', FILTER_VALIDATE_INT);
    if(!$idPost)
    {
        return;
    }

    // And create a db connection for later use
    $db = new ForumDb();

    // Load the post we want to display and its parent (if one exists)
    $post = Post::LoadPost($db, $idPost);
    if(!$post || $post->IsHidden())
    {
        return;
    }
    $parentPostId = $post->GetParentPostId();
    $parentPost = null;
    if($parentPostId)
    {
        $parentPost = Post::LoadPost($db, $parentPostId);
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
        <title>YB Forum</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="theme-color" content="#FFCC00">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <script src="js/renderpost.js?v=r181"></script>
        <script type="text/javascript">
        $( document ).ready(function() {
            renderPost();
        });
        </script>
    </head>
    <body>
        <div  style="max-width: 700px; margin: auto;">
            <img style="max-width: 100%; height: auto;" src="logo.jpg" alt="YB Forum"/>
        </div>       
        <hr>
        <!-- The page header is really simple, in line that -->
        <div class="fullwidthcenter">
        <?php
        try
        {
            echo '[ <a href="postentry.php?idparentpost='
                . $idPost . '">Antworten</a> ]';
        }
        catch(Exception $ex)
        {
            ErrorHandler::OnException($ex);
        }
        ?>
            [ <a href="index.php">Forum</a> ] 
            [ <a href="recent.php">Neue Beiträge</a> ] 
            [ <a href="search.php">Suchen</a> ] 
            [ <a href="textformatierung.html">Textformatierung</a> ] 
            [ <a href="stammposter.php">Stammposter</a> ] 
            [ <a href="register.php">Registrieren</a> ]
        </div>
        <hr>
        <div>
        <?php
        try
        {
            // put the title in that div
            $postView = new PostView($db, $post, $parentPost);
            echo $postView->renderHtmlTitleDivContent();
        }
        catch(Exception $ex)
        {
            ErrorHandler::OnException($ex);
        }
        ?>
        </div>
        <hr>
        <div id="postcontainer" class="postcontainer">
        <?php
        try
        {
            // put the content in this div here
            echo $postView->renderHtmlPostContentDivContent();
        }
        catch(Exception $ex)
        {
            ErrorHandler::OnException($ex);
        }
        ?>
        </div>
        <hr>
        <div>
            <div class="replytitle">
                Antworten zu diesem Beitrag:
            </div>
            <div>
            <?php
            try
            {
                // And the threads following this post
                echo $postView->renderHtmlThreadDivContent();
            }
            catch(Exception $ex)
            {
                ErrorHandler::OnException($ex);                
            }
            ?>
            </div>
        </div>
        <hr>
        <!-- And a footer that is the same as the header -->
        <div class="fullwidthcenter">
            <?php
            try
            {
                echo '[ <a href="postentry.php?idparentpost='
                        . $idPost . '">Antworten</a> ]';
            }
            catch(Exception $ex)
            {
                ErrorHandler::OnException($ex);
            }
            ?>
            [ <a href="index.php">Forum</a> ] 
            [ <a href="recent.php">Neue Beiträge</a> ] 
            [ <a href="search.php">Suchen</a> ] 
            [ <a href="textformatierung.html">Textformatierung</a> ] 
            [ <a href="stammposter.php">Stammposter</a> ] 
            [ <a href="register.php">Registrieren</a> ] 
        </div>
        <hr>
    </body>
</html>
