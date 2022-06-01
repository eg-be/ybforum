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
require_once __DIR__.'/pageparts/PostList.php';

try
{
    if(!session_start())
    {
        throw new Exception('session_start() failed');
    }

    $db = new ForumDb();
}
catch(Exception $ex)
{
    ErrorHandler::OnException($ex);
}
?>

<html lang="de-ch">
    <head>
        <link rel="stylesheet" type="text/css" href="ybforum.css?v=r183">
        <title>YB Forum</title>
        <meta charset="UTF-8">
        <meta name="theme-color" content="#FFCC00">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">        
    </head>
    <body>
        <div  style="max-width: 700px; margin: auto;">
            <?php include __DIR__.'/logo.php'; ?>
        </div>
        <div class="fullwidthcenter generictitle">Neue Beiträge</div>    
        <hr>
        <div class="fullwidthcenter">
            [ <a href="postentry.php">Beitrag Schreiben</a> ] 
            [ <a href="index.php">Forum</a> ] 
            [ <a href="search.php">Suchen</a> ] 
            [ <a href="textformatierung.php">Textformatierung</a> ] 
            [ <a href="stammposter.php">Stammposter</a> ]            
            [ <a href="register.php">Registrieren</a> ]
        </div>
        <hr>
        <?php
        $replies = PostIndexEntry::LoadRecentPosts($db, YbForumConfig::RECENT_ENTRIES_COUNT);
        $pl = new PostList($replies);
        echo $pl->RenderListDiv();
        ?>
        <?php
        include __DIR__.'/pageparts/StandWithUkr.php';
        ?>        
    </body>
</html>
