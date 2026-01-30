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
require_once __DIR__ . '/helpers/Logger.php';
require_once __DIR__ . '/handlers/ResetPasswordHandler.php';
require_once __DIR__ . '/pageparts/TopNavigation.php';
require_once __DIR__ . '/pageparts/Logo.php';

try {
    if (!session_start()) {
        throw new Exception('session_start() failed');
    }

    $loginValue = filter_input(INPUT_GET, 'login', FILTER_VALIDATE_INT);
    $loginFailed = false;
    $authFailReason = 0;
    $resetPasswordHandler = null;
    if ($loginValue && $loginValue > 0) {
        // for the login, a read-only db is enough
        $db = new ForumDb();
        // do the login, reset first
        unset($_SESSION['userid']);
        $nick = trim(filter_input(INPUT_POST, 'login_nick'));
        $pass = trim(filter_input(INPUT_POST, 'login_pass'));

        if ($nick && $pass) {
            // Note: AuthUser will take care of logging
            $user = $db->authUser($nick, $pass, $authFailReason);
            if ($user) {
                $logger = new Logger($db);
                if ($user->needsMigration()) {
                    $logger->logMessageWithUserId(LogType::LOG_OPERATION_FAILED_MIGRATION_REQUIRED, $user);
                    header('Location: migrateuser.php?source=stammposter.php&nick=' . urlencode($user->getNick()) . '&email=' . urlencode($user->getEmail()));
                    exit;
                }
                $logger->logMessageWithUserId(LogType::LOG_STAMMPOSTER_LOGIN, $user);
                $_SESSION['userid'] = $user->getId();
                header('Location: user/index.php');
            }
        }
        if (!(isset($_SESSION['userid']) && $_SESSION['userid'] > 0)) {
            $loginFailed = true;
        }
    } elseif (filter_input(INPUT_GET, 'resetpassword', FILTER_VALIDATE_INT) > 0) {
        try {
            // Requires a writeable db
            $db = new ForumDb(false);
            $resetPasswordHandler = new ResetPasswordHandler();
            $resetPasswordHandler->handleRequest($db);
        } catch (InvalidArgumentException $ex) {
            // show some error later
        }
    }
} catch (Exception $ex) {
    ErrorHandler::onException($ex);
}
?>

<html lang="de-ch">
    <head>
        <link rel="stylesheet" type="text/css" href="ybforum.css?v=r183">
        <link rel="icon" type="image/x-icon" href="favicon.ico">
        <title>YB Forum</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="theme-color" content="#FFCC00">
        <script src="https://code.jquery.com/jquery-4.0.0.min.js" integrity="sha256-OaVG6prZf4v69dPg6PhVattBXkcOWQB62pdZ3ORyrao=" crossorigin="anonymous"></script>
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
        <div class="fullwidthcenter generictitle">Stammposter-Bereich</div>
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
        <div class="fullwidthcenter">Als Stammposter kannst du hier deine
        Einstellungen ändern oder dir einen Link zum Setzen eines neuen
        Passwortes an deine hinterlegte Mailadresse zusenden lassen.
        </div>
        <div class="fullwidthcenter">
            <form id="loginform" method="post" action="stammposter.php?login=1" accept-charset="utf-8">
                <table style="margin: auto; text-align: left; padding-top: 2em;">
                    <tr><td colspan="2" class="genericsmalltitle">Login</td></tr>
                    <tr><td class="fbold">Stammpostername:</td><td><input type="text" id="login_nick" name="login_nick" size="20" maxlength="60"/></td></tr>
                    <tr><td class="fbold">Passwort:</td><td><input type="password" id="login_pass" name="login_pass" size="20" maxlength="60"/></td></tr>
                    <tr><td colspan="2"><input type="submit" value="Login"/></td></tr>
                </table>
            </form>
            <?php
    if ($loginFailed) {
        $authFailMsg = null;
        if ($authFailReason === ForumDb::AUTH_FAIL_REASON_PASSWORD_INVALID) {
            $authFailMsg = 'Ungültiges Passwort';
        } elseif ($authFailReason === ForumDb::AUTH_FAIL_REASON_NO_SUCH_USER) {
            $authFailMsg  = 'Unbekannter Stammposter';
        }
        echo '<div class="fullwidthcenter" style="color: red">Login fehlgeschlagen';
        if ($authFailMsg) {
            echo ': ' . $authFailMsg;
        }
        echo '</div>';
    }
?>
            <form id="resetpasswordform" method="post" action="stammposter.php?resetpassword=1" accept-charset="utf-8">
                <table style="margin: auto; text-align: left; padding-top: 2em;">
                    <tr><td colspan="2" class="genericsmalltitle" style="padding-top: 2em">Neues Passwort anfordern</td></tr>
                    <tr>
                        <td class="fbold">Stammpostername<br>oder Mailadresse:</td>
                        <td><input type="text" id="resetpassword_email" name="resetpassword_email_or_nick" size="30" maxlength="254"/></td>
                    </tr>
                    <tr><td colspan="2"><input type="submit" value="Neues Passwort"/></td></tr>
                </table>
            </form>
            <?php
if ($resetPasswordHandler) {
    if ($resetPasswordHandler->hasException()) {
        $ex = $resetPasswordHandler->getLastException();
        echo '<div class="fullwidthcenter" style="color: red"><span style="font-weight: bold;">Fehler: </span>' . $ex->getMessage() . '</div>';
    } else {
        echo '<div class="fullwidthcenter" style="color: #33cc33">Eine Mail mit einem Link zum zurücksetzen des Passwortes wurde an die hinterlegte Adresse gesendet.</div>';
    }
}
?>
        </div>
        <?php
        include __DIR__ . '/pageparts/StandWithUkr.php';
?>
    </body>
</html>
