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
require_once __DIR__ . '/pageparts/PostView.php';
require_once __DIR__ . '/pageparts/ThreadIndexView.php';
require_once __DIR__ . '/pageparts/TopNavigation.php';
require_once __DIR__ . '/pageparts/Logo.php';
require_once __DIR__ . '/helpers/ErrorHandler.php';

try {
    // Read the arguments required
    $idPost = filter_input(INPUT_GET, 'idpost', FILTER_VALIDATE_INT);
    if (!$idPost) {
        return;
    }

    // And create a db connection for later use
    $db = new ForumDb();

    // Load the post we want to display and its parent (if one exists)
    $post = $db->loadPost($idPost);
    if (!$post || $post->isHidden()) {
        return;
    }
    $parentPostId = $post->getParentPostId();
    $parentPost = null;
    if ($parentPostId) {
        $parentPost = $db->loadPost($parentPostId);
    }
} catch (Exception $ex) {
    ErrorHandler::onException($ex);
}
?>

<html lang="de-ch">
    <head>
        <link rel="stylesheet" type="text/css" href="ybforum.css?v=r183">
        <link rel="icon" type="image/x-icon" href="favicon.ico">
        <title>YB Forum</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="theme-color" content="#FFCC00">
        <script src="https://code.jquery.com/jquery-4.0.0.js"></script>
<script src="https://code.jquery.com/jquery-migrate-4.0.2.js"></script>
        <script src="js/renderpost.js?v=r181"></script>
        <script type="text/javascript">
        $( document ).ready(function() {
            renderPost();
        });
        </script>
    </head>
    <body>
        <?php
        try {
            $logo = new Logo();
            echo $logo->renderHtmlDiv();
        } catch (Exception $ex) {
            ErrorHandler::onException($ex);
        }
?>
        <hr>
        <!-- The page header is really simple, in line that -->
        <?php
try {
    $topNav = new TopNavigation($post->getId());
    echo $topNav->renderHtmlDiv();
} catch (Exception $ex) {
    ErrorHandler::onException($ex);
}
?>
        <div>
        <?php
try {
    // put the title in that div
    $postView = new PostView($db, $post, $parentPost);
    echo $postView->renderHtmlTitleDivContent();
} catch (Exception $ex) {
    ErrorHandler::onException($ex);
}
?>
        </div>
        <hr>
        <div id="postcontainer" class="postcontainer">
        <?php
try {
    // put the content in this div here
    echo $postView->renderHtmlPostContentDivContent();
} catch (Exception $ex) {
    ErrorHandler::onException($ex);
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
    try {
        // And the threads following this post
        echo $postView->renderHtmlThreadDivContent();
    } catch (Exception $ex) {
        ErrorHandler::onException($ex);
    }
?>
            </div>
        </div>
        <hr>
        <?php
        include __DIR__ . '/pageparts/StandWithUkr.php';
?>
    </body>
</html>
