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

require_once __DIR__.'/MailerDelegate.php';
require_once __DIR__.'/../YbForumConfig.php';

/**
 * Writes a mail to LOG_DEBUG
 * @author Elias Gerber
 */
class DebugOutMailer implements MailerDelegate
{
    public function sendMessage(string $to, string $subject, string $content, string $headers) : bool
    {
        $msg = $headers . PHP_EOL 
            . 'From: ' . YbForumConfig::MAIL_FROM_NAME .'<'. YbForumConfig::MAIL_FROM . '>' . PHP_EOL
            .  'To: ' . $to . PHP_EOL
            . 'Subject: ' . $subject . PHP_EOL
            . $content;
        openlog("YbForum", LOG_PERROR, LOG_USER);
        syslog(LOG_DEBUG, $msg);
        closelog();
        return true;
    }
}