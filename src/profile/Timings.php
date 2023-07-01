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

namespace profile;

/**
 * Measures timings. Starts measuring upon construction and methods to stop
 * measuring and output the result to HTML and/or error-log.
 *
 * @author Elias Gerber
 */
class Timings 
{
    
    public function __construct(string $msgPrefix = '') 
    {
        $this->m_msgPrefix = $msgPrefix;
        $this->m_start = microtime(true);
    }
    
    public function Stop() : void
    {
        $this->m_time_elapsed_secs = microtime(true) - $this->m_start;
    }
    
    private function GetMessage() : string
    {
        $msg = ($this->m_time_elapsed_secs * 1000) . 'ms elapsed.';
        if(!empty($this->m_msgPrefix))
        {
            return $this->m_msgPrefix . ': ' . $msg;
        }
        return $msg;
    }
    
    public function Log() : void
    {
        error_log($this->GetMessage(), 0);
    }
    
    public function renderHtmlDiv() : string
    {
        return '<div>'. $this->GetMessage() . '</div>';
    }
    
    private float $m_start;
    private float $m_time_elapsed_secs;
    private string $m_msgPrefix;
}
