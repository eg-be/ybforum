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

require_once __DIR__.'/../helpers/Logger.php';

/**
 * Class providing static methods to act on an Exception.
 * 
 * @author Elias Gerber 
 */
class ErrorHandler
{
    /**
     * @var int Set to 1 if a stacktrace shall be included in the error message.
     */
    const PRINT_STACKTRACE_ON_ERROR = 0;
    
    /**
     * Error logs the exception and calls die().
     * @param Exception $e
     */
    public static function OnException(Exception $e) : void
    {
        $msg = $e->getFile() . '(' . $e->getLine() . '): ' . $e->getMessage();
        if(self::PRINT_STACKTRACE_ON_ERROR)
        {
            $msg.= '; Stacktrace: ' . $e->getTraceAsString();
        }
        try
        {
            // Try to log to log-table too
            $logger = new Logger();
            $logger->LogMessage(Logger::LOG_ERROR_EXCEPTION_THROWN, $msg);
        } 
        catch (Exception $ex) { 
            // Do nothing, $mg will be error_log'ed later anyway
        }
        error_log($msg, 0);
        die();
    }
}