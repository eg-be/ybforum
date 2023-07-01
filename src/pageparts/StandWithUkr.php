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

namespace standwithukr;


require_once(__DIR__.'/../YbForumConfig.php');

/**
 * @author Elias Gerber 
 */
class StandWithUkr {

    /**
     */
    public function __construct()
    {
    }
    
    /**
     * Render a div for a UKR flag
     */
    public function renderHtmlDiv() : string
    {
        $htmlStr = '<div class="standwithukr" title="We stand with Ukraine" id="we-stand-with-ukraine"></div>';
        return $htmlStr;
    }    
}

try
{
    if (\YbForumConfig::STAND_WITH_UKR === true )
    {
        $swu = new StandWithUkr();
        echo $swu->renderHtmlDiv();
    }
}
catch(\Exception $ex)
{
    \ErrorHandler::OnException($ex);
}

?>
