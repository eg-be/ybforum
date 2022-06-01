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
require_once __DIR__.'/pageparts/SearchForm.php';
require_once __DIR__.'/pageparts/SearchResultsView.php';
require_once __DIR__.'/pageparts/StandWithUkr.php';
require_once __DIR__.'/handlers/SearchHandler.php';

include __DIR__.'/profile/profile_start.php';

try
{
    // Create a db for later use
    $db = new ForumDb();
    
    // Check what we have to do
    $searchHandler = null;
    if(filter_input(INPUT_GET, 'search', FILTER_VALIDATE_INT) > 0)
    {
        // Try to search using the posted search data
        try
        {
            $searchHandler = new SearchHandler();
            $searchHandler->HandleRequest($db);
            // searching succeeeded
        }
        catch(InvalidArgumentException $ex)
        {
            // Searching failed. Reshow the form with some error later
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
        <title>YB Forum</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="theme-color" content="#FFCC00">
    </head>
    <body>
        <div  style="max-width: 700px; margin: auto;">
            <?php include __DIR__.'/logo.php'; ?>
        </div>
        <div class="fullwidthcenter generictitle">Beitragssuche</div>    
        <hr>
        <div class="fullwidthcenter">
            [ <a href="index.php">Forum</a> ] 
            [ <a href="recent.php">Neue Beiträge</a> ] 
            [ <a href="textformatierung.php">Textformatierung</a> ] 
            [ <a href="stammposter.php">Stammposter</a> ]            
            [ <a href="register.php">Registrieren</a> ]
        </div>
        <hr>
        <?php
        // render an error from a previous search run
        if($searchHandler && $searchHandler->HasException())
        {
            $searchException = $searchHandler->GetLastException();
            echo '<div id="status" class="fullwidthcenter" style="color: red;">'
                . '<span class="fbold">Fehler: </span>'
                . $searchException->GetMessage()
                . '</div>';
        }
        ?>
        <div>
        <?php
        // Alwyas render the form to start a new search
        $searchForm = new SearchForm($searchHandler);
        echo $searchForm->RenderHtmlForm();
        ?>
        </div>
        <?php
        // If we have some pending results, render them
        if($searchHandler && $searchHandler->HasResults())
        {
            $searchResultsView = new SearchResultsView($searchHandler);
            echo $searchResultsView->RenderResultsNavigationDiv();
            echo $searchResultsView->RenderSortDiv();
            echo $searchResultsView->RenderResultsDiv();
            echo $searchResultsView->RenderResultsNavigationDiv();
        }
        ?>
        <?php
        include __DIR__.'/pageparts/StandWithUkr.php';
        ?>        
        <?php
        include __DIR__.'/profile/profile_end.php';
        ?>        
    </body>
</html>
