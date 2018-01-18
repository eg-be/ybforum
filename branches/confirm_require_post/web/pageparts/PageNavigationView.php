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
 * Renders navigation elements, pointing to previous or following pages.
 * 
 * @author Elias Gerber
 */
class PageNavigationView
{
    
    /**
     * Constructor to create $maxPageNavEntries pointing towards left from
     * the $currentPageNr, and $maxPageNavEntries pointing towards right from
     * the current $currentPageNr. If not on the first (or last) page, a 
     * navigation element to navigate to the first or last page is included.
     * @param int $currentPageNr The page number being displayed.
     * @param int $maxThreadsPerPage Maximum threads to display on one page.
     * @param int $maxPageNavEntries Maximum number of page entries to display,
     *      to navigate left or right.
     * @param int $threadCount Total number of threads stored in the database.
     */
    public function __construct(int $currentPageNr, int $maxThreadsPerPage, 
            int $maxPageNavEntries, int $threadCount)
    {
        assert($currentPageNr >= 1);
        assert($maxThreadsPerPage > 0);
        assert($maxPageNavEntries > 0);
        assert($threadCount >= 0);        
        
        $this->m_currentPage = $currentPageNr;
        $this->m_totalPages = ceil($threadCount / $maxThreadsPerPage);
        
        // Calculate leftmost page we want to display
        $this->m_leftmostPage = $currentPageNr - $maxPageNavEntries;
        if($this->m_leftmostPage <= 0)
        {
            $this->m_leftmostPage = 1;
        }
        
        // and rightmost page
        $this->m_rightmostPage = $currentPageNr + $maxPageNavEntries;
        if($this->m_rightmostPage > $this->m_totalPages)
        {
            $this->m_rightmostPage = $this->m_totalPages;
        }
    }
    
    
    private function CreatePageNavElement($pageNr)
    {
        return '<span class="navelement"><a href="index.php?page=' . $pageNr . '">'
                . $pageNr .'</a></span>';
    }
    
    private function CreateFirstPageNavElement()
    {
        return '<a href="index.php?page=1">&lt;&lt;</a>';
    }
    
    private function CreateCurrentPagElement()
    {
        return '<span class="navelement fbold">' . $this->m_currentPage 
                . '</span>';
    }
    
    private function CreateLastPageNavElement()
    {
        return '<a href="index.php?page=' . $this->m_totalPages . '">&gt;&gt;</a>';        
    }
    
    private function CreateSkipLeftNavElement()
    {
        $destinationPageNr = 
                $this->m_currentPage - YbForumConfig::NAV_SKIP_NR_OF_PAGES;
        if($destinationPageNr < 1)
        {
            $destinationPageNr = 1;
        }
        return '<span class="navelement"><a href="index.php?page=' 
                . $destinationPageNr . '">&lt;</a></span>';
    }    
    
    private function CreateSkipRightNavElement()
    {
        $destinationPageNr = 
                $this->m_currentPage + YbForumConfig::NAV_SKIP_NR_OF_PAGES;
        if($destinationPageNr > $this->m_totalPages)
        {
            $destinationPageNr = $this->m_totalPages;
        }
        return '<span class="navelement"><a href="index.php?page=' 
                . $destinationPageNr . '">&gt;</a></span>';
    }

    /**
     * Renders a HTML string with the page navigation content.
     * @return string HTML with a content like <a href="index.php?pagenr=xx">
     */
    public function renderHtmlDivContent()
    {
        $htmlStr = '';
        // Navigate towards left (only possible if we are not on newest page)
        if($this->m_currentPage > 1)
        {
            $htmlStr.= $this->CreateFirstPageNavElement() . ' ';
            if($this->m_currentPage - YbForumConfig::NAV_SKIP_NR_OF_PAGES > 1)
            {
                $htmlStr.= $this->CreateSkipLeftNavElement() . ' ';
            }
            for($i = $this->m_leftmostPage; $i < $this->m_currentPage; $i++)
            {
                $htmlStr.= $this->CreatePageNavElement($i) . ' ';
            }
        }
        // Mark current page
        $htmlStr.= $this->CreateCurrentPagElement() . ' ';
        // and navigate towards right
        if($this->m_currentPage < $this->m_totalPages)
        {
            for($i = $this->m_currentPage + 1; $i <= $this->m_rightmostPage; $i++)
            {
                $htmlStr.= $this->CreatePageNavElement($i) . ' ';
            }
            if($this->m_currentPage + YbForumConfig::NAV_SKIP_NR_OF_PAGES < 
                    $this->m_totalPages)
            {
                $htmlStr.= $this->CreateSkipRightNavElement() . ' ';
            }
            $htmlStr.= $this->CreateLastPageNavElement();
        }
        return $htmlStr;
    }
    
    private $m_currentPage;
    private $m_totalPages;
    private $m_leftmostPage;
    private $m_rightmostPage;
}
