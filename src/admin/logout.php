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

require_once __DIR__ . '/../helpers/ErrorHandler.php';

try {
    if (!session_start()) {
        throw new Exception('session_start() failed');
    }
    session_unset();
    session_destroy();
} catch (Exception $ex) {
    ErrorHandler::onException($ex);
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Logout</title>
    </head>
    <body>
        <div>Adieu, merci.</div>
        <div><a href="index.php">Zurück zum Start</a></div>
    </body>
</html>
