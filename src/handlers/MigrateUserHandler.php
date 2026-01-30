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
require_once __DIR__ . '/../helpers/Mailer.php';
require_once __DIR__ . '/../helpers/Logger.php';

/**
 * Read all values required to migrate an old user. Creates the required
 * entry in the confirm_user_table and sends an email with the confirmation
 * link to the email address set as new email address.
 *
 * @author Elias Gerber
 */
class MigrateUserHandler extends BaseHandler
{
    public const PARAM_NICK = 'request_migrate_nick';
    public const PARAM_OLDPASS = 'request_migrate_oldpass';
    public const PARAM_NEWPASS = 'request_migrate_newpass';
    public const PARAM_CONFIRMNEWPASS = 'request_migrate_confirmpass';
    public const PARAM_NEWEMAIL = 'request_migrate_mailaddress';

    public const MSG_AUTH_FAIL = 'Unbekannter Stammposter / altes Passwort';
    public const MSG_EMAIL_NOT_UNIQUE = 'Angegebene Mailadresse bereits verwendet. Verwende '
        . 'Passwort zurücksetzen Funktion im Stammposterbereich falls du '
        . 'nicht mehr weisst mit welchem Account diese Mailadresse '
        . 'verknüpft ist.';
    public const MSG_ALREADY_MIGRATED = 'AlreadyMigrated';
    public const MSG_PASSWORDS_NOT_MATCH = 'Passwort und Bestätigung stimmen nicht überein.';
    public const MSG_PASSWORD_TOO_SHORT = 'Neues Passwort muss mindestens '
        . YbForumConfig::MIN_PASSWWORD_LENGTH . ' Zeichen enthalten.';
    public const MSG_SENDING_CONFIRMMAIL_FAILED = 'Die Bestätigungsmail konnnte nicht gesendet werden.';


    public function __construct()
    {
        parent::__construct();

        $this->logger = null;
        $this->mailer = null;

        // Set defaults explicitly
        $this->nick = null;
        $this->oldPassword = null;
        $this->newPassword = null;
        $this->confirmNewPassword = null;
        $this->newEmail = null;
    }

    protected function readParams(): void
    {
        // Read params
        $this->nick = self::readStringParam(self::PARAM_NICK);
        $this->oldPassword = self::readStringParam(self::PARAM_OLDPASS);
        $this->newPassword = self::readStringParam(self::PARAM_NEWPASS);
        $this->confirmNewPassword = self::readStringParam(self::PARAM_CONFIRMNEWPASS);
        $this->newEmail = self::readEmailParam(self::PARAM_NEWEMAIL);
    }

    protected function validateParams(): void
    {
        // And validate the params
        self::validateStringParam($this->nick, self::MSG_AUTH_FAIL);
        self::validateStringParam($this->oldPassword, self::MSG_AUTH_FAIL);
        self::validateStringParam($this->newPassword, self::MSG_PASSWORD_TOO_SHORT, YbForumConfig::MIN_PASSWWORD_LENGTH);
        self::validateEmailValue($this->newEmail);

        // Passwords must match
        if ($this->newPassword !== $this->confirmNewPassword) {
            throw new InvalidArgumentException(self::MSG_PASSWORDS_NOT_MATCH, parent::MSGCODE_BAD_PARAM);
        }
    }

    protected function handleRequestImpl(ForumDb $db): void
    {
        // First: Check if there is a matching (real) user:
        $user = $db->LoadUserByNick($this->nick);
        if (is_null($this->logger)) {
            $this->logger = new Logger($db);
        }
        if (!$user) {
            $this->logger->LogMessage(LogType::LOG_AUTH_FAILED_NO_SUCH_USER, 'Passed nick: ' . $this->nick);
            throw new InvalidArgumentException(self::MSG_AUTH_FAIL, parent::MSGCODE_AUTH_FAIL);
        }
        if ($user->IsDummyUser()) {
            $this->logger->LogMessageWithUserId(LogType::LOG_AUTH_FAILED_USER_IS_DUMMY, $user);
            throw new InvalidArgumentException(self::MSG_AUTH_FAIL, parent::MSGCODE_AUTH_FAIL);
        }
        // Check if user still needs to migrate:
        if (!$user->NeedsMigration()) {
            $this->logger->LogMessageWithUserId(LogType::LOG_OPERATION_FAILED_ALREADY_MIGRATED, $user);
            throw new InvalidArgumentException(self::MSG_ALREADY_MIGRATED, parent::MSGCODE_BAD_PARAM);
        }
        // Auth using old password
        if (!$user->OldAuth($this->oldPassword)) {
            $this->logger->LogMessageWithUserId(LogType::LOG_AUTH_FAILED_USING_OLD_PASSWORD, $user);
            throw new InvalidArgumentException(self::MSG_AUTH_FAIL, parent::MSGCODE_AUTH_FAIL);
        }
        // Authentication using old password succeeded
        $this->logger->LogMessageWithUserId(LogType::LOG_AUTH_USING_OLD_PASSWORD, $user);
        // The given Mailaddress must be unique:
        $userByEmail = $db->LoadUserByEmail($this->newEmail);
        if ($userByEmail && $userByEmail->GetId() !== $user->GetId()) {
            $this->logger->LogMessageWithUserId(LogType::LOG_OPERATION_FAILED_EMAIL_NOT_UNIQUE, $user, 'New Email: ' . $this->newEmail);
            throw new InvalidArgumentException(self::MSG_EMAIL_NOT_UNIQUE, parent::MSGCODE_BAD_PARAM);
        }
        // check that the new email is not blacklisted
        self::validateEmailAgainstBlacklist($this->newEmail, $db, $this->logger);
        // And prepare to migrate
        $confirmCode = $db->RequestConfirmUserCode(
            $user,
            $this->newPassword,
            $this->newEmail,
            ForumDb::CONFIRM_SOURCE_MIGRATE,
            $this->clientIpAddress
        );

        // send the email to the address requested
        if (is_null($this->mailer)) {
            $this->mailer = new Mailer();
        }
        if (!$this->mailer->SendMigrateUserConfirmMessage($this->newEmail, $this->nick, $confirmCode)) {
            throw new InvalidArgumentException(self::MSG_SENDING_CONFIRMMAIL_FAILED, parent::MSGCODE_INTERNAL_ERROR);
        }
    }

    public function getNick(): ?string
    {
        return $this->nick;
    }

    public function getNewEmail(): ?string
    {
        return $this->newEmail;
    }

    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }

    public function setMailer(Mailer $mailer): void
    {
        $this->mailer = $mailer;
    }

    private ?string $nick;
    private ?string $oldPassword;
    private ?string $newPassword;
    private ?string $confirmNewPassword;
    private ?string $newEmail;

    private ?Logger $logger;
    private ?Mailer $mailer;
}
