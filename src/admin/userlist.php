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
        $adminUser = $db->loadUserById($_SESSION['adminuserid']);
        if (!($adminUser && $adminUser->isActive() && $adminUser->isAdmin())) {
            header('Location: login.php');
            exit;
        }
    }
} catch (Exception $ex) {
    ErrorHandler::onException($ex);
}
?>
<!DOCTYPE html>

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
$sortField = 'iduser';
$sortFieldValue = filter_input(INPUT_GET, 'sort', FILTER_UNSAFE_RAW);
if ($sortFieldValue === 'id' || $sortFieldValue === 'nick'
        || $sortFieldValue === 'email' || $sortFieldValue === 'registration_ts'
        || $sortFieldValue === 'registration_msg' || $sortFieldValue === 'confirmation_ts'
        || $sortFieldValue === 'active' || $sortFieldValue === 'admin'
        || $sortFieldValue === 'is_dummy' || $sortFieldValue === 'has_password'
        || $sortFieldValue === 'has_old_passwd') {
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
                    <th><a href="userlist.php?sort=iduser&order=<?php echo $sortOrderReverse; ?>">Id</a></th>
                    <th><a href="userlist.php?sort=nick&order=<?php echo $sortOrderReverse; ?>">Stammpostername</a></th>
                    <th><a href="userlist.php?sort=email&order=<?php echo $sortOrderReverse; ?>">Email<a/></th>
                    <th><a href="userlist.php?sort=registration_ts&order=<?php echo $sortOrderReverse; ?>">Registriert seit</a></th>
                    <th><a href="userlist.php?sort=registration_msg&order=<?php echo $sortOrderReverse; ?>">Registrierungsnachricht</a></th>
                    <th><a href="userlist.php?sort=confirmation_ts&order=<?php echo $sortOrderReverse; ?>">Email bestätigt am</a></th>
                    <th><a href="userlist.php?sort=active&order=<?php echo $sortOrderReverse; ?>">Aktiv</a></th>
                    <th><a href="userlist.php?sort=admin&order=<?php echo $sortOrderReverse; ?>">Admin</a></th>
                    <th><a href="userlist.php?sort=is_dummy&order=<?php echo $sortOrderReverse; ?>">Dummy</a></th>
                    <th><a href="userlist.php?sort=has_password&order=<?php echo $sortOrderReverse; ?>">Hat neues Passwort</a></th>
                    <th><a href="userlist.php?sort=has_old_passwd&order=<?php echo $sortOrderReverse; ?>">Hat altes Passwort</a></th>
                </tr>
<?php
try {
    $query = 'SELECT iduser, nick, email, admin, active, '
            . 'registration_ts, registration_msg, '
            . 'confirmation_ts, '
            . '(password IS NOT NULL) AS has_password, '
            . '(old_passwd IS NOT NULL) AS has_old_passwd, '
            . '(ISNULL(email) AND ISNULL(password) '
            . 'AND ISNULL(old_passwd)) AS is_dummy '
            . 'FROM user_table';
    if ($sortField && $sortOrder) {
        $query .= ' ORDER BY ' . $sortField . ' ' . $sortOrder;
    }
    $stmt = $db->prepare($query);
    $stmt->execute();
    while ($row = $stmt->fetch()) {
        $rowStr = '<tr>';
        $rowStr .= '<td>' . $row['iduser'] . '</td>';
        $rowStr .= '<td>' . htmlspecialchars($row['nick']) . '</td>';
        $rowStr .= '<td>' . htmlspecialchars($row['email']) . '</td>';
        $rowStr .= '<td>' . new DateTime($row['registration_ts'])->format('d.m.Y H:i:s') . '</td>';
        $rowStr .= '<td>' . $row['registration_msg'] . '</td>';
        $rowStr .= '<td>' . ($row['confirmation_ts'] ? new DateTime($row['confirmation_ts'])->format('d.m.Y H:i:s') : '') . '</td>';
        $rowStr .= '<td>' . $row['active'] . '</td>';
        $rowStr .= '<td>' . $row['admin'] . '</td>';
        $rowStr .= '<td>' . ($row['is_dummy'] ? 'Ja' : 'Nein') . '</td>';
        $rowStr .= '<td>' . ($row['has_password'] ? 'Ja' : 'Nein') . '</td>';
        $rowStr .= '<td>' . ($row['has_old_passwd'] ? 'Ja' : 'Nein') . '</td>';
        $rowStr .= '<tr>';
        echo $rowStr;
    }
} catch (Exception $ex) {
    ErrorHandler::onException($ex);
}
?>
            </table>
        </div>
    </body>
</html>
