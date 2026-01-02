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

require_once __DIR__ . '/../model/ForumDb.php';
require_once __DIR__ . '/../helpers/ErrorHandler.php';

try {
    if (!session_start()) {
        throw new Exception('session_start() failed');
    }
    $adminUser = null;
    // if there is no adminuserid set, exit
    if (!isset($_SESSION['adminuserid'])) {
        header('Location: login.php');
        exit;
    } else {
        // check that this adminuserid is still valid
        $db = new ForumDb();
        $adminUser = $db->LoadUserById($_SESSION['adminuserid']);
        if (!($adminUser && $adminUser->IsActive() && $adminUser->IsAdmin())) {
            header('Location: login.php');
            exit;
        }
    }
} catch (Exception $ex) {
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
<?php
$sortField = 'idpost';
$sortFieldValue = filter_input(INPUT_GET, 'sort', FILTER_UNSAFE_RAW);
if ($sortFieldValue === 'idpost' || $sortFieldValue === 'idthread'
        || $sortFieldValue === 'parent_idpost' || $sortFieldValue === 'nick'
        || $sortFieldValue === 'title' || $sortFieldValue === 'content'
        || $sortFieldValue === 'creation_ts' || $sortFieldValue === 'email'
        || $sortFieldValue === 'link_url' || $sortFieldValue === 'link_text'
        || $sortFieldValue === 'img_url' || $sortFieldValue === 'ip_address') {
    $sortField = $sortFieldValue;
}
$sortOrder = 'ASC';
$sortOrderReverse = 'DESC';
$sortOrderValue = filter_input(INPUT_GET, 'order', FILTER_UNSAFE_RAW);
if ($sortOrderValue === 'ASC') {
    $sortOrder = 'ASC';
    $sortOrderReverse = 'DESC';
} elseif ($sortOrderValue === 'DESC') {
    $sortOrder = 'DESC';
    $sortOrderReverse = 'ASC';
}
?>
                    <th><a href="hiddenpostlist.php?sort=idpost&order=<?php echo $sortOrderReverse; ?>">Id</a></th>
                    <th><a href="hiddenpostlist.php?sort=idthread&order=<?php echo $sortOrderReverse; ?>">Thread</a></th>
                    <th><a href="hiddenpostlist.php?sort=parent_idpost&order=<?php echo $sortOrderReverse; ?>">ParentPost<a/></th>
                    <th><a href="hiddenpostlist.php?sort=nick&order=<?php echo $sortOrderReverse; ?>">Stammposter (id)</a></th>
                    <th><a href="hiddenpostlist.php?sort=title&order=<?php echo $sortOrderReverse; ?>">Titel</a></th>
                    <th><a href="hiddenpostlist.php?sort=content&order=<?php echo $sortOrderReverse; ?>">Inhalt</a></th>
                    <th><a href="hiddenpostlist.php?sort=creation_ts&order=<?php echo $sortOrderReverse; ?>">Inhalt</a></th>
                    <th><a href="hiddenpostlist.php?sort=email&order=<?php echo $sortOrderReverse; ?>">Email</a></th>
                    <th><a href="hiddenpostlist.php?sort=link_url&order=<?php echo $sortOrderReverse; ?>">Link URL</a></th>
                    <th><a href="hiddenpostlist.php?sort=link_text&order=<?php echo $sortOrderReverse; ?>">Link Text</a></th>
                    <th><a href="hiddenpostlist.php?sort=img_url&order=<?php echo $sortOrderReverse; ?>">Bild URL</a></th>
                    <th><a href="hiddenpostlist.php?sort=ip_address&order=<?php echo $sortOrderReverse; ?>">IP</a></th>
                </tr>
<?php
try {
    $query = 'SELECT idpost, idthread, parent_idpost, '
            . 'post_table.iduser AS iduser, nick, title, '
            . 'content, creation_ts, '
            . 'post_table.email AS email, '
            . 'link_url, link_text, img_url, ip_address '
            . 'FROM post_table LEFT JOIN user_table '
            . 'ON post_table.iduser = user_table.iduser '
            . 'WHERE hidden > 0';
    if ($sortField && $sortOrder) {
        $query .= ' ORDER BY ' . $sortField . ' ' . $sortOrder;
    }
    $stmt = $db->prepare($query);
    $stmt->execute();
    while ($row = $stmt->fetch()) {
        $rowStr = '<tr>';
        $rowStr .= '<td>' . $row['idpost'] . '</td>';
        $rowStr .= '<td>' . $row['idthread'] . '</td>';
        $rowStr .= '<td>' . $row['parent_idpost'] . '</td>';
        $rowStr .= '<td>' . htmlspecialchars($row['nick']) . ' (' . $row['iduser'] . ')</td>';
        $rowStr .= '<td>' . htmlspecialchars($row['title']) . '</td>';
        $rowStr .= '<td style="white-space: pre-wrap;">' . htmlspecialchars($row['content']) . '</td>';
        $rowStr .= '<td>' . new DateTime($row['creation_ts'])->format('d.m.Y H:i:s') . '</td>';
        $rowStr .= '<td>' . htmlspecialchars($row['email']) . '</td>';
        $rowStr .= '<td>' . htmlspecialchars($row['link_url']) . '</td>';
        $rowStr .= '<td>' . htmlspecialchars($row['link_text']) . '</td>';
        $rowStr .= '<td>' . htmlspecialchars($row['img_url']) . '</td>';
        $rowStr .= '<td>' . $row['ip_address'] . '</td>';
        $rowStr .= '<tr>';
        echo $rowStr;
    }
} catch (Exception $ex) {
    ErrorHandler::OnException($ex);
}
?>
            </table>
        </div>
    </body>
</html>