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

class YbForumConfig
{
    /**
     * @var int Maximum number of threads displayed on the index page
     */
    const MAX_THREADS_PER_PAGE = 20;
    
    /**
     * @var int Maximum number of elements to navigate left or right
     * on the index page
     */
    const MAX_PAGE_NAV_ELEMENTS = 10;
    
    /**
     * @var int How many pages to skip if navigating using the '>' elements
     */
    const NAV_SKIP_NR_OF_PAGES = 25;
    
    /**
     * @var Minimal length of password if creating a new password
     */
    const MIN_PASSWWORD_LENGTH = 8;
    
    /**
     * @var Minimal length of a new nickname
     */
    const MIN_NICK_LENGTH = 5;
    
    /**
     * @var The base url, with a trailing '/' at the end
     */
    const BASE_URL = 'https://www.1898.ch/';
    
    /**
    * @var Minimal length of the title of a post
    */
    const MIN_TITLE_LENGTH = 3;
    
    /**
     * @var Duration in hours while a confirmation code is valid
     */
    const CONF_CODE_VALID_PERIOD = 'PT24H';
    
    /**
     * @var Number of entries to show on the recent page
     */
    const RECENT_ENTRIES_COUNT = 20;
    
    /**
     * @var Max number of search results to show
     */
    const MAX_SEARCH_ENTRIES = 1000;
    
    /**
     * @var Minimal number of characters to include in a search query
     */
    const MIN_SEARCH_LENGTH = 4;
    
    /**
     * @var boolean If set, the Mailer will not try to send a mail,
     * but just log to syslog and stderr what would be sent as a mail.
     */
    const MAIL_DEBUG = true;

    /**
    * @var string Address to use as mail from address
    */
    const MAIL_FROM = 'no-reply@1898.ch';

    /**
    * @var string Name to be used as mail from address
    */
    const MAIL_FROM_NAME = 'YB-Forum Mailer';
 
    /**
    * @var string If set to a value that evaluates to true, all mail will 
    * be sent using BCC to this address
    */
    const MAIL_ALL_BCC = 'mail-monitor@1898.ch';
 
    /**
     * @var bool Use captcha verification while registering new users.
     */
    const CAPTCHA_VERIFY = false;
    
    /**
     * @var string Captcha secret code
     */
    const CAPTCHA_SECRET = '6Lc3HksUAAAAAPRM08dQUlqZaekbxr47GMzW4m1Y';
    
    /**
     * @var bool If true, post content is logged as extended log if
     * posting fails because authentication has failed (user not found,
     * wrong password, user inactive or dummy). A normal log entry is 
     * created, with type LOG_EXT_POST_DISCARDED. The message of that log
     * entry is the authentication failure reason. In the 
     * log_extended_info table an entry is added, holding the content (and 
     * subject) of the post that has been discarded. Note; This is mostly
     * funny to see what spammers want to add, or what stupid newspapers
     * like 20min are trying to post..
     */
    const LOG_EXT_POST_DATA_ON_AUTH_FAILURE = true;

    /**
     * @var bool If true, a stand with ukraine logo is rendered
     */
    const STAND_WITH_UKR = true;
}
