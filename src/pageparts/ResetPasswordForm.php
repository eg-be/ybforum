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
require_once __DIR__ . '/../handlers/ConfirmResetPasswordHandler.php';

/**
 * Renders a form with input fields to reset a password
 * The form has hidden fields for PARAM_TYPE and PARAM_CODE values.
 *
 * @author Elias Gerber
 */
class ResetPasswordForm
{
    public function __construct(ConfirmResetPasswordHandler $confirmHandler)
    {
        $this->m_confirmHandler = $confirmHandler;
    }

    public function RenderHtmlDiv(): string
    {
        $html = '<div class="fullwidthcenter">';
        $html .= '<span class="fbold">';
        $html .= $this->m_confirmHandler->GetConfirmText();
        $html .= '</span>';
        $html .= '<form method="post" action="resetpassword.php" accept-charset="utf-8">';
        $html .= '<table style="margin:auto">';
        $html .= '<tr>';
        $html .= '<td>Neues Passwort:</td><td><input type="password" name="'
                . UpdatePasswordHandler::PARAM_NEWPASS
                . '" required="required"/></td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td>Passwort bestätigen:</td><td><input type="password" name="'
                . UpdatePasswordHandler::PARAM_CONFIRMNEWPASS
                . '" required="required"/></td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td><input type="hidden" name="'
                . ConfirmHandler::PARAM_TYPE
                . '" value="' . $this->m_confirmHandler->GetType() . '"/></td>';
        $html .= '<td><input type="hidden" name="'
                . ConfirmHandler::PARAM_CODE . '" value="'
                . $this->m_confirmHandler->GetCode() . '"/></td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td colspan="2"><input type="submit" value="Passwort setzen"/></td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</form>';
        $html .= '</form>';
        $html .= '</div>';
        return $html;
    }

    private ConfirmResetPasswordHandler $m_confirmHandler;
}
