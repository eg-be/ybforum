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
 * The configuration to connect to the database.
 */
final class DbConfig 
{
    const SERVERNAME = '127.0.0.1';
    const RW_USERNAME = 'dbybforum_rw';
    const RW_PASSWORD = 'rw-password';
	
    const RO_USERNAME = 'dbybforum_ro';
    const RO_PASSWORD = 'ro-password';
	
    const DEFAULT_DB = 'dbybforum';
    const CHARSET = 'utf8mb4';
}
