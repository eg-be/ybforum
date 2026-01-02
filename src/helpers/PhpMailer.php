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

require_once __DIR__ . '/../YbForumConfig.php';
require_once __DIR__ . '/MailerDelegate.php';

/**
 * Send mail using phps built-in mail function
 * @author Elias Gerber
 */
class PhpMailer implements MailerDelegate
{
    public function sendMessage(string $to, string $subject, string $content, string $headers): bool
    {
        // If we do not force a sender, the reply-to: address is still set to www-data (?)
        // so we just use that -f switch
        $sent = mail($to, $subject, $content, $headers, '-f ' . YbForumConfig::MAIL_FROM);
        return $sent;
    }
}
