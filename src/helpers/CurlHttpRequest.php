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

require_once __DIR__.'/HttpRequest.php';

/**
 * Uses curl for the underlying http transport
 * @author Elias Gerber
 */
class CurlHttpRequest implements HttpRequest
{
    public function postReceiveJson(string $url, array $args) : ?array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 
                    http_build_query($args));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $serverResponse = curl_exec ($ch);
        curl_close ($ch);
        if(!$serverResponse)
        {
            return null;
        }
        $decodedResp = json_decode($serverResponse, true);
        if(!$decodedResp)
        {
            return null;
        }
        return $decodedResp;
    }
}