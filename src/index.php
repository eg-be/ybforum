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

require_once __DIR__ . '/YbForumConfig.php';
require_once __DIR__ . '/model/ForumDb.php';
require_once __DIR__ . '/pageparts/PageNavigationView.php';
require_once __DIR__ . '/pageparts/ThreadIndexView.php';
require_once __DIR__ . '/pageparts/TopNavigation.php';
require_once __DIR__ . '/pageparts/Logo.php';
require_once __DIR__ . '/helpers/ErrorHandler.php';
include __DIR__ . '/profile/profile_start.php';

try {
    // Read the arguments required
    $pageNr = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
    if (!$pageNr || $pageNr < 1) {
        $pageNr = 1;
    }

    // And create a db connection for later use
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
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="theme-color" content="#FFCC00">
        <script src="https://code.jquery.com/jquery-4.0.0.min.js" integrity="sha256-OaVG6prZf4v69dPg6PhVattBXkcOWQB62pdZ3ORyrao=" crossorigin="anonymous"></script>
<!--        <script src="js/pokal.js?v=r187"></script>		-->
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
        <?php
try {
    $topNav = new TopNavigation();
    echo $topNav->renderHtmlDiv();
} catch (Exception $ex) {
    ErrorHandler::onException($ex);
}
?>
        <hr>
        <div class="fullwidthcenter fbold">Seiten des Forums</div>
        <div class="fullwidthcenter">
        <?php
// Add the navigation
try {
    $pageNav = new PageNavigationView(
        $pageNr,
        YbForumConfig::MAX_THREADS_PER_PAGE,
        YbForumConfig::MAX_PAGE_NAV_ELEMENTS,
        YbForumConfig::NAV_SKIP_NR_OF_PAGES,
        $db->getThreadCount()
    );
    echo $pageNav->renderHtmlDivContent();
} catch (Exception $ex) {
    ErrorHandler::onException($ex);
}
?>
        </div>
        <hr>
        <div>
        <?php
// Add the threads of this page
try {
    $threadIndex = new ThreadIndexView(
        $db,
        YbForumConfig::MAX_THREADS_PER_PAGE,
        $pageNr
    );
    $threadIndex->renderHtmlDivPerThread(function ($htmlPerThread): void {
        // we get one callback per thread, containg a div with that thread.
        // note: threads with thousends of posts might still make the
        // memory explode
        echo $htmlPerThread;
    });
} catch (Exception $ex) {
    ErrorHandler::onException($ex);
}
?>
        </div>
        <?php
include __DIR__ . '/pageparts/StandWithUkr.php';
?>
        <?php
include __DIR__ . '/profile/profile_end.php';
?>
    </body>
</html>
