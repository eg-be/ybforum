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
 * A wrapper around static config-values, for easier testing using
 * dependency injection
 * @author Elias Gerber
 */
class ConfigWrapper {
    
    private static ?ConfigWrapper $instance = null;

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): ConfigWrapper
    {
        if (self::$instance === null) {
            self::$instance = new self();

        }
        return self::$instance;
    }

    public function getLogExtendedPostDataOnAuthFailure() : bool
    {
        return YbForumConfig::LOG_EXT_POST_DATA_ON_AUTH_FAILURE;
    }

    public function getLogAuthFailNoSuchUser() : bool
    {
        return YbForumConfig::LOG_AUTH_FAIL_NO_SUCH_USER;
    }

    private function __construct() {}

    private function __clone() {}

    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }    
}
