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

/**
 * The fields from post_table (and user_table.nick) required to display a
 * search result entry. Provides static method(s) to create such entries.
 */

require_once __DIR__ . '/SearchDefinitions.php';

class SearchResult
{
    /**
     * Constructed only from pdo, hide constructor.
     * This constructor will assert that all members have a valid data
     * and set some internal values.
     */
    private function __construct()
    {
        assert($this->idpost > 0);
        assert(!empty($this->title));
        assert(!empty($this->nick));
        assert(!empty($this->creation_ts));
        $this->creation_ts_dt = new DateTime($this->creation_ts);
        if (isset($this->relevance) === false) {
            $this->relevance = 0.0;
        }
    }

    /**
     * @return int Field idpost.
     */
    public function GetPostId(): int
    {
        return $this->idpost;
    }

    /**
     * @return string Field title.
     */
    public function GetTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string Non empty nick (field user_table.nick) who wrote this post.
     */
    public function GetNick(): string
    {
        return $this->nick;
    }

    /**
     * @return DateTime of the field creation_ts.
     */
    public function GetPostTimestamp(): DateTime
    {
        return $this->creation_ts_dt;
    }

    private int $idpost;
    private string $title;
    private int $has_content;
    private int $iduser;
    private string $nick;
    private string $creation_ts; // this is just the value from the corresponding field post_table class="creation_ts
    // pdo->fetchObject() injects a string-value
    private DateTime $creation_ts_dt; // the same but converted to a DateTime
    private float $relevance;
}
