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

require_once __DIR__ . '/../model/ForumDb.php';
require_once __DIR__ . '/../handlers/ConfirmHandler.php';

/**
 * Renders a form with a button to complete the confirmation process.
 * The form has hidden fields for PARAM_TYPE and PARAM_CODE values.
 *
 * @author Elias Gerber
 */
class ConfirmForm
{
    public function __construct(ConfirmHandler $confirmHandler)
    {
        $this->m_confirmHandler = $confirmHandler;
    }

    public function RenderHtmlDiv(): string
    {
        $html = '<div>';
        $html .= '<span class="fbold">';
        $html .= $this->m_confirmHandler->GetConfirmText();
        $html .= '</span>';
        $html .= '<form method="post" action="confirm.php?confirm=1" accept-charset="utf-8">';
        $html .= '<input type="hidden" name="' . ConfirmHandler::PARAM_TYPE . '" value="' . $this->m_confirmHandler->GetType() . '"/>';
        $html .= '<input type="hidden" name="' . ConfirmHandler::PARAM_CODE . '" value="' . $this->m_confirmHandler->GetCode() . '"/>';
        $html .= '<input type="submit" value="Bestätigen"/>';
        $html .= '</form>';
        $html .= '</div>';
        return $html;
    }

    private ConfirmHandler $m_confirmHandler;
}
