<?php

declare(strict_types=1);

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
    public function __construct(string $msgPrefix = '')
    {
        $this->m_msgPrefix = $msgPrefix;
    }

    public function measure(): void
    {
        $this->m_peakBytes = memory_get_peak_usage();
        $this->m_currentBytes = memory_get_usage();
    }

    private function getMessage(): string
    {
        $msg = '';
        if (!empty($this->m_msgPrefix)) {
            $msg = $this->m_msgPrefix . ': ';
        }
        $msg .= 'Memory peak: ' . $this->m_peakBytes . 'Bytes; Current usage: '
                . $this->m_currentBytes . 'Bytes.';
        return $msg;
    }


    public function log(): void
    {
        error_log($this->getMessage(), 0);
    }

    public function renderHtmlDiv(): string
    {
        return '<div>' . $this->getMessage() . '</div>';
    }

    private string $m_msgPrefix;
    private int $m_peakBytes;
    private int $m_currentBytes;
}
