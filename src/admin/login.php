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
require_once __DIR__ . '/../helpers/Logger.php';

try {
    if (!session_start()) {
        throw new Exception('session_start() failed');
    }
    $db = new ForumDb();
    $logger = new Logger($db);
    $loginFailed = false;
    if (filter_input(INPUT_GET, 'login', FILTER_VALIDATE_INT)) {
        // do the login, reset first
        unset($_SESSION['adminuserid']);
        $nick = trim(filter_input(INPUT_POST, 'nick'));
        $pass = trim(filter_input(INPUT_POST, 'pass'));

        if ($nick && $pass) {
            $user = $db->AuthUser($nick, $pass);
            if ($user) {
                if ($user->IsAdmin()) {
                    // Lgin succeeded, move on toe index page
                    $logger->LogMessageWithUserId(LogType::LOG_ADMIN_LOGIN, $user);
                    $_SESSION['adminuserid'] = $user->GetId();
                    header('Location: index.php');
                    exit;
                } else {
                    $logger->LogMessageWithUserId(LogType::LOG_ADMIN_LOGIN_FAILED_USER_IS_NOT_ADMIN, $user);
                }
            }
        }
        // login failed
        $loginFailed = true;
    }
} catch (Exception $ex) {
    ErrorHandler::OnException($ex);
}

?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Login</title>
    </head>
    <body>
        <form action="?login=1" method="post">
            <table>
                <tr><td>Name: </td><td><input type="text" name="nick" id="in_nick" size="20" maxlength="60"></td></tr>
                <tr><td>Passwort: </td><td><input type="password" name="pass" id="in_pass" size="20" maxlength="60"/></td></tr>
                <tr><td colspan="2"><input type="submit" value="Login"></td></tr>
            </table>
        </form>
        <?php
        if ($loginFailed) {
            echo '<div>Login failed</div>';
        }
?>
    </body>
</html>
