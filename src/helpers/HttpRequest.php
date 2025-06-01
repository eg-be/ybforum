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

/**
 * Interface to wrap up calls to curl
 * @author Elias Gerber
 */

interface HttpRequest {

    /**
     * executes a POST to the passed url. The passed args posted as 
     * url encoded string.
     * The returned data is expected to be json.
     * @return ?array The decoded json array or null if anything fails
     */
    public function postReceiveJson(string $url, array $args) : ?array;
}