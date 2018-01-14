<?php

/**
 * Copyright 2017 Elias Gerber
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

// Do not cache this page
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Wed, 26 Jan 1983 01:00:00 GMT');

require_once __DIR__.'/../model/ForumDb.php';
require_once __DIR__.'/../helpers/ErrorHandler.php';
require_once __DIR__.'/DeactivatedUserList.php';
require_once __DIR__.'/PendingApprovalUserList.php';
require_once __DIR__.'/PendingConfirmationUserList.php';
require_once __DIR__.'/LogEntryList.php';
require_once __DIR__.'/UserView.php';
require_once __DIR__.'/PostView.php';
require_once __DIR__.'/AdminList.php';
require_once __DIR__.'/Statistics.php';

try
{
    if(!session_start())
    {
        throw new Exception('session_start() failed');
    }
    $adminUser = null;
    // if there is no adminuserid set, exit
    if(!isset($_SESSION['adminuserid']))
    {
        header('Location: login.php');
        exit;
    }    
    else
    {
        // check that this adminuserid is still valid
        $db = new ForumDb();
        $adminUser = User::LoadUserById($db, $_SESSION['adminuserid']);
        if(!($adminUser && $adminUser->IsActive() && $adminUser->IsAdmin()))
        {
            header('Location: login.php');
            exit;
        }
    }
    // setup required views and do all actions on those views
    $userView = new UserView();
    $userViewResult = $userView->HandleActionsAndGetResultDiv($db, $_SESSION['adminuserid']);    
    $pendingActList = new PendingApprovalUserList();
    $pendingActListResult = $pendingActList->HandleActionsAndGetResultDiv($db);
    $pendingConfList = new PendingConfirmationUserList();
    $pendingConfListResult = $pendingConfList->HandleActionsAndGetResultDiv($db);
    $postView = new PostView();
    $postViewResult = $postView->HandleActionsAndGetResultDiv($db);
    
} 
catch (Exception $ex) 
{
    ErrorHandler::OnException($ex);
}
?>
<!DOCTYPE html>

<html lang="de-ch">
    <head>
        <link rel="stylesheet" type="text/css" href="admin.css">
        <title>YB Forum 2.0 Admin Bereich</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">        
    </head>
    <body>
        <div>
        <?php
        echo '<span class="fbold">Eingeloggt als:</span> <span class="fitalic">' 
                . htmlspecialchars($adminUser->GetNick())
                . '</span> (<span class="fitalic">' . htmlspecialchars($adminUser->GetEmail()) 
                . '</span>)';
        echo ' <a href="logout.php">Logout</a> | ';
        echo ' <a href="index.php">Aktualisieren</a>';
        ?>
        </div>
        <hr>
        <div>
            <div class="pageparttitle">Stammposter die auf die Freischaltung durch einen Admin warten</div>
            <?php
            try
            {
                echo $pendingActList->RenderHtmlDiv($db);
                echo $pendingActListResult;
            }
            catch (Exception $ex) { ErrorHandler::OnException($ex); }
            ?>
        </div>
        <hr>
        <div>
            <div class="pageparttitle">Stammposter die ihre Registrierung oder Migration bestätigen müssen</div>
            <?php
            try
            {
                echo $pendingConfList->RenderHtmlDiv($db);
                echo $pendingConfListResult;
            } 
            catch (Exception $ex) { ErrorHandler::OnException($ex); }
            ?>
        </div>
        <hr>
        <div>
            <div class="pageparttitle">Stammposter die von einem Admin deaktiviert wurden</div>
            <?php
            try
            {
                $deactList = new DeactivatedUserList();
                echo $deactList->RenderHtmlDiv($db);
            } 
            catch (Exception $ex) { ErrorHandler::OnException($ex); }
            ?>
        </div>
        <hr>
        <div>
            <div class="pageparttitle">Admin Liste</div>
            <?php
            try
            {
                $adminList = new AdminList();
                echo $adminList->RenderHtmlDiv($db);
            }
            catch (Exception $ex) { ErrorHandler::OnException($ex); }            
            ?>
        </div>
        <hr>
        <div>
            <div><span class="pageparttitle">Stammposter anzeigen und bearbeiten</span> | <a href="userlist.php" target="_blank">Liste aller Stammposter</a></div>
            <form method="post" action="index.php" accept-charset="utf-8">
                Nach BenutzerId: <input type="text" size="10" name="<?php echo UserView::PARAM_USERID?>"/>
                Nach Stammpostername (Gross-und Kleinschreibung beachten) oder Mailadresse: <input type="text" name="<?php echo UserView::PARAM_NICK_OR_EMAIL ?>"/>
                <input type="submit" value="Stammposter laden"/>
            </form>
            <?php
            try
            {
                echo $userView->RenderHtmlDiv($db);
                echo $userViewResult;
            }
            catch (Exception $ex) { ErrorHandler::OnException($ex); }
            ?>
        </div>
        <hr>
        <div>
            <div>
                <span class="pageparttitle">Post anzeigen und ausblenden &sol; einblenden</span> | 
                <a href="hiddenpostlist.php" target="_blank">Liste aller ausgeblendeten Posts</a> | 
                <a href="brokenhiddenlist.php" targer="_blank">Liste von Kindposts deren Eltern ausgeblendet sind</a>
            </div>
            <form method="post" action="index.php" accept-charset="utf-8">
                PostId laden: <input type="text" size="10" name="<?php echo PostView::PARAM_POSTID?>"/>
                <input type="submit" value="Post anzeigen"/>
            </form>
            <?php
                echo $postViewResult;            
                echo $postView->RenderHtmlDiv($db);
            ?>
        </div>
        <hr>
        <div>
            <div class="pageparttitle">Statistiken</div>
            <?php
            try
            {
                $stats = new Statistics();
                echo $stats->RenderHtmlDiv($db);
            }
            catch (Exception $ex) { ErrorHandler::OnException($ex); }
            ?>
        </div>
        <hr>
        <div>
            <div class="pageparttitle">Neuste Log Einträge</div>
            <?php
            try
            {
                $logList = new LogEntryList();
                echo $logList->RenderHtmlDiv($db);
            }
            catch (Exception $ex) { ErrorHandler::OnException($ex); }
            ?>
        </div>
    </body>
</html>
