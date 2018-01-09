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
 * Renders thread topics as tree.
 *
 * @author Elias Gerber 
 */
class ThreadIndexView {

    /**
     * Constructor
     * @param ForumDb $forumDb An instance of the database.
     * @param int $nrOfThreadsPerPage Maximum number of threads to display on
     *  one page.
     * @param int $pageNr Number of the page we are on
     */
    public function __construct(ForumDb $forumDb, int $nrOfThreadsPerPage, 
            int $pageNr)
    {
        assert($forumDb->IsConnected());
        assert($nrOfThreadsPerPage > 0);
        assert($pageNr > 0);
        // the max thread is the latest (newest) thread
        $dbLastThreadId = $forumDb->GetLastThreadId();
        // depending on our pagenr and the number of threads per page
        // calculate the last thread id for this page view
        $this->m_lastThreadId = $dbLastThreadId - 
                (($pageNr - 1) * $nrOfThreadsPerPage);
        $this->m_nrOfThreads = $nrOfThreadsPerPage;
        $this->m_forumDb = $forumDb;
    }
    
    /**
     * Renders a HTML string with the content of the thread. The HTML content
     * for every thread is rendered as a  single HTML div element, 
     * holding multiple p elements for the thread. 
     * After every thread div has been fully created, the 
     * $htmlPerThreadCallback is invoked, with a single string argument
     * holding the HTML div code for that thread.
     * @param callable $htmlPerThreadCallback Callable that accepts as
     * argument the HTML string content to display one single thread.
     */
    public function renderHtmlDivPerThread(callable $htmlPerThreadCallback)
    {
        PostIndexEntry::LoadThreadIndexEntries($this->m_forumDb,
            $this->m_nrOfThreads, 
                $this->m_lastThreadId, 
                function($threadIndexes) 
                use ($htmlPerThreadCallback)
        {
            $htmlStr = '<div class="threadmargin">';
            foreach($threadIndexes as $ti)
            {
                $indent = $ti->GetIndent();
                $htmlStr.= '<p class="nomargin ';
                if($indent === 0)
                {
                    $htmlStr.= 'fbold';
                }
                $htmlStr.= '" style="text-indent: ';
                $htmlStr.= $indent . 'em"><a ';
                $htmlStr.= 'href="showentry.php?idpost=' 
                    . $ti->GetPostId() . '">';
                $htmlStr.= $ti->GetTitle();
                if(!$ti->HasContent())
                {
                    $htmlStr.= ' (o.T.)';
                }
                $htmlStr.= '</a> - <span class="fbold">';
                $htmlStr.= $ti->GetNick();
                $htmlStr.= '</span> - ';
                $htmlStr.= $ti->GetPostTimestamp()->format('d.m.Y H:i:s');
                $htmlStr.= '</p>';
            }
            $htmlStr.= '</div>';
            call_user_func($htmlPerThreadCallback, $htmlStr);
        });
    }
    
    private $m_forumDb;
    private $m_nrOfThreads;
    private $m_lastThreadId;
}
