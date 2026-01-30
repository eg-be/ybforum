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
require_once __DIR__ . '/../model/ForumDb.php';
require_once __DIR__ . '/../helpers/Logger.php';

/**
 * Updates the password for a user. If the user needs to migrate,
 * the user is migrated (password set and activated, email is left
 * with the email already set).
 * Else only the password is updated.
 *
 * @author Elias Gerber
 */
class UpdatePasswordHandler extends BaseHandler
{
    public const PARAM_NEWPASS = 'stammposter_newpass';
    public const PARAM_CONFIRMNEWPASS = 'stammposter_confirmpass';

    public const MSG_PASSWORDS_NOT_MATCH = 'Passwort und Bestätigung stimmen nicht überein.';
    public const MSG_PASSWORD_TOO_SHORT = 'Neues Passwort muss mindestens '
        . YbForumConfig::MIN_PASSWWORD_LENGTH . ' Zeichen enthalten.';
    public const MSG_DUMMY_USER = 'Stammposter ist ein Dummy';
    public const MSG_USER_INACTIVE = 'Stammposter ist deaktiviert';

    public function __construct(User $user)
    {
        parent::__construct();

        $this->user = $user;
        $this->logger = null;

        // Set defaults explicitly
        $this->clientIpAddress = null;
        $this->newPassword = null;
        $this->confirmNewPassword = null;
    }

    protected function readParams(): void
    {
        // Read params
        $this->newPassword = self::readStringParam(self::PARAM_NEWPASS);
        $this->confirmNewPassword = self::readStringParam(self::PARAM_CONFIRMNEWPASS);
    }

    protected function validateParams(): void
    {
        self::validateStringParam($this->newPassword, self::MSG_PASSWORD_TOO_SHORT, YbForumConfig::MIN_PASSWWORD_LENGTH);
        // passwords must match
        if ($this->newPassword !== $this->confirmNewPassword) {
            throw new InvalidArgumentException(
                self::MSG_PASSWORDS_NOT_MATCH,
                parent::MSGCODE_BAD_PARAM
            );
        }
    }

    protected function handleRequestImpl(ForumDb $db): void
    {
        if (is_null($this->logger)) {
            $this->logger = new Logger($db);
        }
        // dummy user cannot have a password set
        if ($this->user->IsDummyUser()) {
            $this->logger->LogMessageWithUserId(LogType::LOG_OPERATION_FAILED_USER_IS_DUMMY, $this->user);
            throw new InvalidArgumentException(self::MSG_DUMMY_USER, parent::MSGCODE_BAD_PARAM);
        }
        // inactive users cannot change their password,
        // except they are inactive because they need to migrate
        if (!$this->user->IsActive() && !$this->user->NeedsMigration()) {
            $this->logger->LogMessageWithUserId(LogType::LOG_OPERATION_FAILED_USER_IS_INACTIVE, $this->user);
            throw new InvalidArgumentException(self::MSG_USER_INACTIVE, parent::MSGCODE_BAD_PARAM);
        }
        // if we need to migrate, migrate
        if ($this->user->NeedsMigration()) {
            $hashedPassword = password_hash($this->newPassword, PASSWORD_DEFAULT);
            $db->ConfirmUser(
                $this->user,
                $hashedPassword,
                $this->user->getEmail(),
                true,
                $this->clientIpAddress
            );
        } else {
            $db->UpdateUserPassword(
                $this->user,
                $this->newPassword,
                $this->clientIpAddress
            );
        }
    }

    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }

    private ?string $newPassword;
    private ?string $confirmNewPassword;

    private User $user;
    private ?Logger $logger;
}
