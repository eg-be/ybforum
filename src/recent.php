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
require_once __DIR__ . '/helpers/ErrorHandler.php';
require_once __DIR__ . '/pageparts/PostList.php';
require_once __DIR__ . '/pageparts/TopNavigation.php';
require_once __DIR__ . '/pageparts/Logo.php';

try {
    if (!session_start()) {
        throw new Exception('session_start() failed');
    }

    $db = new ForumDb();
} catch (Exception $ex) {
    ErrorHandler::onException($ex);
}
?>

<html lang="de-ch">
    <head>
        <link rel="stylesheet" type="text/css" href="ybforum.css?v=<?php echo YbForumConfig::CSS_REV ?>">
        <link rel="icon" type="image/x-icon" href="favicon.ico">
        <title>YB Forum</title>
        <meta charset="UTF-8">
        <meta name="theme-color" content="#FFCC00">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        <div class="fullwidthcenter generictitle">Neue Beiträge</div>
        <hr>
        <?php
try {
    $topNav = new TopNavigation();
    echo $topNav->renderHtmlDiv();
} catch (Exception $ex) {
    ErrorHandler::onException($ex);
}
?>
        <hr>
        <?php
try {
    $replies = $db->loadRecentPosts(YbForumConfig::RECENT_ENTRIES_COUNT);
    $pl = new PostList($replies);
    echo $pl->renderListDiv();
} catch (Exception $ex) {
    ErrorHandler::onException($ex);
}
?>
        <?php
include __DIR__ . '/pageparts/StandWithUkr.php';
?>
    </body>
</html>
