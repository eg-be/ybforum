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

require_once __DIR__.'/../YbForumConfig.php';

/**
 * Helper class to render the html for the logo
 *
 * @author Elias Gerber
 */
class Logo
{
    public function __construct() 
    {
    }
    
    /**
     * Render a a HTML div element holding an img element with the logo
     * @return string
     */
    public function renderHtmlDiv() : string
    {
        $html = '<img style="max-width: 100%; height: auto;" src="'
        . YbForumConfig::LOGO_FILE
        . '" alt="YB Forum"/>';
        return $html;
    }
}