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

namespace profile;

/**
 * Configuration file to enable memory and/or timing profiling.
 * Include profile_start to start measuring and include
 * profile_end to stop measuring.
 *
 * @author Elias Gerber
 */
class ProfileConfig 
{
    /**
     * @var int Set to 1 to enable timing profiling.
     */
    const MEASURE_TIMING = 0;
    
    /**
     * @var int Set to 1 to enable memory profiling.
     */
    const MEASURE_MEMORY = 0;
    
    /**
     * @var int Set to 1 to error_log all results.
     */
    const ERRORLOG_MEASURE_RESULTS = 0;
    
    /**
     * @var int Set to 1 to echo all results as a HTML div.
     */
    const PRINT_MEASURE_RESULTS = 1;
}
