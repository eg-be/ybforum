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

require_once __DIR__ . '/Translation.php';

/**
 * Maps db-fields to a translation
 */
enum SortField: string implements Translation
{
    case FIELD_RELEVANCE = 'relevance';
    case FIELD_TITLE = 'title';
    case FIELD_NICK = 'nick';
    case FIELD_DATE = 'creation_ts';

    public function getTranslation(): string
    {
        return match ($this) {
            SortField::FIELD_RELEVANCE => 'Relevanz',
            SortField::FIELD_TITLE => 'Titel',
            SortField::FIELD_NICK => 'Stammposter',
            SortField::FIELD_DATE => 'Datum'
        };
    }
}

/**
 * Maps db-sorting to a translation
 */
enum SortOrder: string implements Translation
{
    case ORDER_ASC = 'ASC';
    case ORDER_DESC = 'DESC';

    public function getTranslation(): string
    {
        return match ($this) {
            SortOrder::ORDER_ASC => 'Aufsteigend',
            SortOrder::ORDER_DESC => 'Absteigend',
        };
    }
}
