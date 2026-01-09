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

/**
 * A class to render a list of PostIndexEntry objects.
 *
 * @author Elias Gerber
 */
class PostList
{
    /**
     * Create a new instance, holding the passed list
     * @param array $postIndexEntries An array of PostIndexEntry objects.
     */
    public function __construct(array $postIndexEntries)
    {
        $this->m_postIndexEntries = $postIndexEntries;
    }

    /**
     * Returns a HTML div that holds every entry of the array set during
     * construction as p element.
     * @return string
     */
    public function RenderListDiv(): string
    {
        $htmlStr = '<div class="fullwidth">';
        foreach ($this->m_postIndexEntries as $indexEntry) {
            $htmlStr .= '<p class="nomargin"><a ';
            $htmlStr .= 'href="showentry.php?idpost='
                . $indexEntry->GetPostId() . '">';
            $htmlStr .= $indexEntry->GetTitle();
            if (!$indexEntry->HasContent()) {
                $htmlStr .= ' (o.T.)';
            }
            $htmlStr .= '</a> - <span class="fbold">';
            $htmlStr .= $indexEntry->GetNick();
            $htmlStr .= '</span> - ';
            $htmlStr .= $indexEntry->GetPostTimestamp()->format('d.m.Y H:i:s');
            $htmlStr .= '</p>';
        }

        $htmlStr .= '</div>';
        return $htmlStr;
    }

    private array $m_postIndexEntries;
}
