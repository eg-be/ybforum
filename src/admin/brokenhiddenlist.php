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
} 
catch (Exception $ex) 
{
    ErrorHandler::OnException($ex);
}
?>
<html lang="de-ch">
    <head>
        <link rel="stylesheet" type="text/css" href="admin.css">
        <title>YB Forum Admin Bereich</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">   
    </head>
    <body>
        <div>
            <table>
                <tr>
                    <th>Hidden Id</th>
                    <th>Parent</th>
                    <th>Title</th>
                    <th>Number of children in subtree</th>
                    <th>Not hidden children in subtree</th>
                </tr>
                <?php
                // Find all hiddenposts
                $query = 'SELECT idpost FROM post_table WHERE hidden > 0';
                $stmt = $db->prepare($query);
                $stmt->execute();
                while($row = $stmt->fetch())
                {
                    // Get all children of that post
                    $post = $db->LoadPost($row['idpost']);
                    $children = $db->LoadPostReplies($post, true);
                    $notHiddenChildren = array();
                    $unhiddenLinkList = '';
                    foreach($children as $childPost)
                    {
                        if(!$childPost->IsHidden())
                        {
                            array_push($notHiddenChildren, $childPost);
                            $unhiddenLinkList.= '<a href="../showentry.php?idpost=' . $childPost->GetPostId() . '">' . $childPost->GetPostId() . '</a> ';
                        }
                    }
                    if(!empty($notHiddenChildren))
                    {
                        $rowStr = '<tr class="actionConfirm">';
                    }
                    else
                    {
                        $rowStr = '<tr>';
                    }
                    $rowStr.= '<td>' . $post->GetId() . '</td>';
                    $rowStr.= '<td>' . $post->GetParentPostId() . '</td>';
                    $rowStr.= '<td>' . $post->GetTitle() . '</td>';
                    $rowStr.= '<td>' . count($children) . '</td>';
                    $rowStr.= '<td>' . count($notHiddenChildren) . ': ' . $unhiddenLinkList;
                    $rowStr.= '</td>';
                    $rowStr.= '</tr>';
                    echo $rowStr;
                }
                ?>
            </table>
        </div>
    </body>
</html>