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

require_once __DIR__ . '/BaseHandler.php';
require_once __DIR__ . '/../helpers/Mailer.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../model/ForumDb.php';

/**
 * Handles a request to update the email address of a user. Sends an email
 * with a confirmation link to the newly set email address.
 *
 * @author Elias Gerber
 */
class UpdateEmailHandler extends BaseHandler
{
    public const PARAM_NEWEMAIL = 'stammposter_updateemail';

    public const MSG_EMAIL_NOT_DIFFERENT = 'Angegebene Mailadresse ist dieselbe wie '
        . 'die bereits hinterlegte.';
    public const MSG_EMAIL_NOT_UNIQUE = 'Angegebene Mailadresse bereits verwendet. Verwende '
        . 'Passwort zurücksetzen Funktion im Stammposterbereich falls du '
        . 'nicht mehr weisst mit welchem Account diese Mailadresse '
        . 'verknüpft ist.';
    public const MSG_SENDING_CONFIRMMAIL_FAILED = 'Die Bestätigungsmail konnnte nicht gesendet werden.';

    public function __construct(User $user)
    {
        parent::__construct();

        $this->user = $user;
        $this->mailer = null;

        // Set defaults explicitly
        $this->newEmail = null;
    }

    protected function readParams(): void
    {
        // Read params
        $this->newEmail = self::readEmailParam(self::PARAM_NEWEMAIL);
    }

    protected function validateParams(): void
    {
        self::validateEmailValue($this->newEmail);
        // Email must be different from current email
        if ($this->user->getEmail() === $this->newEmail) {
            throw new InvalidArgumentException(self::MSG_EMAIL_NOT_DIFFERENT, parent::MSGCODE_BAD_PARAM);
        }
    }

    protected function handleRequestImpl(ForumDb $db): void
    {
        // Check that this email address is not already used within some other
        // account
        $user = $db->LoadUserByEmail($this->newEmail);
        if ($user) {
            throw new InvalidArgumentException(self::MSG_EMAIL_NOT_UNIQUE, parent::MSGCODE_BAD_PARAM);
        }

        // Create a confirmation link to update the email
        $confirmCode = $db->RequestUpdateEmailCode(
            $this->user,
            $this->newEmail,
            $this->clientIpAddress
        );

        // send the email to the address requested
        if (is_null($this->mailer)) {
            $this->mailer = new Mailer();
        }
        if (!$this->mailer->SendUpdateEmailConfirmMessage($this->newEmail, $this->user->getNick(), $confirmCode)) {
            $db->RemoveUpdateEmailCode($this->user);
            throw new InvalidArgumentException(self::MSG_SENDING_CONFIRMMAIL_FAILED, parent::MSGCODE_INTERNAL_ERROR);
        }
    }

    public function getNewEmail(): ?string
    {
        return $this->newEmail;
    }

    public function setMailer(Mailer $mailer): void
    {
        $this->mailer = $mailer;
    }

    private ?Mailer $mailer;

    private ?string $newEmail;

    private User $user;
}
