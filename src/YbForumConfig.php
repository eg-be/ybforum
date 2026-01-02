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

final class YbForumConfig
{
    /**
     * @var int Maximum number of threads displayed on the index page
     */
    public const MAX_THREADS_PER_PAGE = 20;

    /**
     * @var int Maximum number of elements to navigate left or right
     * on the index page
     */
    public const MAX_PAGE_NAV_ELEMENTS = 10;

    /**
     * @var int How many pages to skip if navigating using the '>' elements
     */
    public const NAV_SKIP_NR_OF_PAGES = 25;

    /**
     * @var Minimal length of password if creating a new password
     */
    public const MIN_PASSWWORD_LENGTH = 8;

    /**
     * @var Minimal length of a new nickname
     */
    public const MIN_NICK_LENGTH = 5;

    /**
     * @var The base url, with a trailing '/' at the end
     */
    public const BASE_URL = 'https://www.1898.ch/';

    /**
    * @var Minimal length of the title of a post
    */
    public const MIN_TITLE_LENGTH = 3;

    /**
     * @var Duration in hours while a confirmation code is valid
     */
    public const CONF_CODE_VALID_PERIOD = 'PT24H';

    /**
     * @var Number of entries to show on the recent page
     */
    public const RECENT_ENTRIES_COUNT = 20;

    /**
     * @var Max number of search results to show
     */
    public const MAX_SEARCH_ENTRIES = 1000;

    /**
     * @var Minimal number of characters to include in a search query
     */
    public const MIN_SEARCH_LENGTH = 4;

    /**
     * @var boolean If set, the Mailer will not try to send a mail,
     * but just log to syslog and stderr what would be sent as a mail.
     */
    public const MAIL_DEBUG = false;

    /**
    * @var string Address to use as mail from address
    */
    public const MAIL_FROM = 'no-reply@1898.ch';

    /**
    * @var string Name to be used as mail from address
    */
    public const MAIL_FROM_NAME = 'YB-Forum Mailer';

    /**
    * @var string If set to a value that evaluates to true, all mail will
    * be sent using BCC to this address
    */
    public const MAIL_ALL_BCC = 'mail-monitor@1898.ch';

    /**
     * @var bool If true, a log entry is created if authentication fails with
     * the reason 'AuthFailedNoSuchUser' (a nick was passed, that doesnt
     * exists).
     * This should be set to false as default, as spammers create tons of
     * log-entries else.
     * note: Setting this to false, will also supress logging of the
     * post-content in case of 'AuthFailedNoSuchUser'.
     */
    public const LOG_AUTH_FAIL_NO_SUCH_USER = false;

    /**
     * @var bool If true, post content is logged as extended log if
     * posting fails because authentication has failed (user not found,
     * wrong password, user inactive or dummy). A normal log entry is
     * created, with type LOG_EXT_POST_DISCARDED. The message of that log
     * entry is the authentication failure reason. In the
     * log_extended_info table an entry is added, holding the content (and
     * subject) of the post that has been discarded. Note; This is mostly
     * funny to see what spammers want to add, or what newspapers without
     * an account are tryign to post.
     * note: If LOG_AUTH_FAIL_NO_SUCH_USER is set to false, the post content
     * will never be logged if the authentication failure is due to the
     * passed nick not beeing found.
     */
    public const LOG_EXT_POST_DATA_ON_AUTH_FAILURE = true;

    /**
     * @var int Number of random bytes to use for all confirmation codes generated
     */
    public const CONFIRMATION_CODE_LENGTH = 32;

    /**
     * @var bool If true, a stand with ukraine logo is rendered
     */
    public const STAND_WITH_UKR = true;

    /**
     * @var string Path to the logo to display on top. Relative to this file.
     */
    public const LOGO_FILE = 'logo/yb_forum.jpg';

    /**
     * @var bool If set to false, no registration is possible.
     */
    public const REGISTRATION_OPEN = true;

    /**
     * @var string Revision-string to append to the CSS to enforce reloading on update.
     */
    public const CSS_REV = 'r2025-11-22';
}
