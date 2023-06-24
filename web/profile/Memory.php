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
 * Measures Memory usage. Output the result to HTML and/or error-log.
 *
 * @author Elias Gerber
 */
class Memory 
{

    public function __construct($msgPrefix = '') 
    {
        $this->m_msgPrefix = $msgPrefix;
    }
    
    public function Measure() : void
    {
        $this->m_peakBytes = memory_get_peak_usage();
        $this->m_currentBytes = memory_get_usage();
    }
    
    private function GetMessage() : string
    {
        $msg = '';
        if(!empty($this->m_msgPrefix))
        {
            $msg = $this->m_msgPrefix . ': ';
        }
        $msg.= 'Memory peak: ' . $this->m_peakBytes . 'Bytes; Current usage: '
                . $this->m_currentBytes . 'Bytes.';
        return $msg;
    }    
    
    
    public function Log() : void
    {
        error_log($this->GetMessage(), 0);
    }
    
    public function renderHtmlDiv() : string
    {
        return '<div>' . $this->GetMessage() . '</div>';
    }    

    private $m_msgPrefix;
    private $m_peakBytes;
    private $m_currentBytes;
}
