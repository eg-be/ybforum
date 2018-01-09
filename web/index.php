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

require_once __DIR__.'/YbForumConfig.php';
require_once __DIR__.'/model/ForumDb.php';
require_once __DIR__.'/pageparts/PageNavigationView.php';
require_once __DIR__.'/pageparts/ThreadIndexView.php';
require_once __DIR__.'/helpers/ErrorHandler.php';

include __DIR__.'/profile/profile_start.php';

try
{
    // Read the arguments required
    $pageNr = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
    if(!$pageNr || $pageNr < 1)
    {
        $pageNr = 1;
    }

    // And create a db connection for later use
    $db = new ForumDb();
}
catch(Exception $ex)
{
    ErrorHandler::OnException($ex);
}
?>

<html lang="de-ch">
    <head>
        <link rel="stylesheet" type="text/css" href="ybforum.css">
        <title>YB Forum 2.0</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">        
    </head>
    <body>
        <div  style="max-width: 700px; margin: auto;">
            <img style="max-width: 100%; height: auto;" src="logo.jpg" alt="YB Forum"/>
        </div>
        <hr>
        <div class="fullwidthcenter">
            [ <a href="postentry.php">Beitrag Schreiben</a> ] 
            [ <a href="recent.php">Neue Beiträge</a> ] 
            [ <a href="search.php">Suchen</a> ] 
            [ <a href="textformatierung.html">Textformatierung</a> ] 
            [ <a href="stammposter.php">Stammposter</a> ]
            [ <a href="register.php">Registrieren</a> ]
        </div>
        <hr>
        <div class="fullwidthcenter fbold">Seiten des Forums</div>
        <div class="fullwidthcenter">
        <?php
        // Add the navigation
        try
        {
            $pageNav = new PageNavigationView($pageNr, 
                YbForumConfig::MAX_THREADS_PER_PAGE,
                YbForumConfig::MAX_PAGE_NAV_ELEMENTS, $db->GetThreadCount());
            echo $pageNav->renderHtmlDivContent();
        }
        catch(Exception $ex)
        {
            ErrorHandler::OnException($ex);
        }
        ?>
        </div>
        <hr>
        <div>
        <?php
        // Add the threads of this page
        try
        {
            $threadIndex = new ThreadIndexView($db, 
                YbForumConfig::MAX_THREADS_PER_PAGE,  $pageNr);
            $threadIndex->renderHtmlDivPerThread(function($htmlPerThread)
            {
                // we get one callback per thread, containg a div with that thread.
                // note: threads with thousends of posts might still make the
                // memory explode
                echo $htmlPerThread;
            });
        }
        catch(Exception $ex)
        {
            ErrorHandler::OnException($ex);
        }
        ?>
        </div>
        <?php
        include __DIR__.'/profile/profile_end.php';
        ?>
    </body>
</html>
