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
 * A User from the database (user_table)
 */
class User
{
    /**
     * Constructed only from pdo, hide constructor.
     * This constructor will assert that all members have a valid data
     * and set some internal values.
     */
    protected function __construct()
    {
        assert($this->iduser > 0);
        assert(!empty($this->nick));
        assert(is_null($this->email) || !empty($this->email));
        assert(!empty($this->registration_ts));
        assert(is_null($this->registration_msg) || !empty($this->registration_msg));
        assert(is_null($this->confirmation_ts) || !empty($this->confirmation_ts));
        assert(!empty($this->password));
        assert(is_null($this->old_passwd || !empty($this->old_passwd)));
        $this->registration_ts_dt = new DateTime($this->registration_ts);
        $this->confirmation_ts_dt = null;
        if ($this->confirmation_ts) {
            $this->confirmation_ts_dt = new DateTime($this->confirmation_ts);
        }
    }

    protected int $iduser;
    protected string $nick;
    protected ?string $email;
    protected int $admin;
    protected int $active;
    protected string $registration_ts;
    protected DateTime $registration_ts_dt;
    protected ?string $registration_msg;
    protected ?string $confirmation_ts;
    protected ?DateTime $confirmation_ts_dt;
    protected ?string $password; // note: dummy-users do not have a password, email or old-password
    protected ?string $old_passwd;

    /**
     * Build a string containing userId, nick, email,
     * active, confirmed and need migration info as one string.
     * Mostly used for logging.
     * @return string
     */
    public function getMinimalUserInfoAsString(): string
    {
        $userStr = 'IdUser: ' . $this->getId();
        $userStr .= '; Nick: ' . $this->getNick();
        $userStr .= '; Email: ';
        if ($this->hasEmail()) {
            $userStr .= $this->getEmail();
        } else {
            $userStr .= '<No Email set>';
        }
        $userStr .= '; Active: ' . ($this->isActive() ? 'Yes' : 'No');
        $userStr .= '; Confirmed: ' . ($this->isConfirmed() ? 'Yes' : 'No');
        $userStr .= '; Needs Migration: ' . ($this->needsMigration() ? 'Yes' : 'No');

        return $userStr;
    }

    /**
     * Get all information about this User as a string.
     * Mostly used for logging.
     * @return string
     */
    public function getFullUserInfoAsString(): string
    {
        $userStr = $this->getMinimalUserInfoAsString();
        $userStr .= '; HasPassword: ' . ($this->hasPassword() ? 'Yes' : 'No');
        $userStr .= '; HasOldPassword: ' . ($this->hasOldPassword() ? 'Yes' : 'No');
        $userStr .= '; IsAdmin: ' . ($this->isAdmin() ? 'Yes' : 'No');
        $userStr .= '; IsDummy: ' . ($this->isDummyUser() ? 'Yes' : 'No');
        // Registration ts is always set
        $userStr .= '; Registration Timestamp: '
                . $this->getRegistrationTimestamp()->format('d.m.Y H:i:s');
        $userStr .= '; Confirmation Timestamp: ';
        // Rest could be null
        if ($this->isConfirmed()) {
            $userStr .= $this->getConfirmationTimestamp()->format('d.m.Y H:i:s');
        } else {
            $userStr .= '<Not Confirmed>';
        }
        $userStr .= '; Registration Message: ';
        if ($this->hasRegistrationMsg()) {
            $userStr .= $this->getRegistrationMsg();
        } else {
            $userStr .= '<No Registration Message set>';
        }

        return $userStr;
    }

    /**
     * @return int Field iduser.
     */
    public function getId(): int
    {
        return $this->iduser;
    }

    /**
     * @return string Field nick.
     */
    public function getNick(): string
    {
        return $this->nick;
    }

    /**
     * @return bool True if field email is not null and not empty.
     */
    public function hasEmail(): bool
    {
        return !is_null($this->email) && !empty($this->email);
    }

    /**
     * @return string or null. Field email.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return bool True if field admin holds a value > 0.
     */
    public function isAdmin(): bool
    {
        return $this->admin > 0;
    }

    /**
     * @return bool True if field active holds a value > 0.
     */
    public function isActive(): bool
    {
        return $this->active > 0;
    }

    /**
     * @return DateTime Field registration_ts.
     */
    public function getRegistrationTimestamp(): DateTime
    {
        return $this->registration_ts_dt;
    }

    /**
     * @return string or null Field registration_msg.
     */
    public function getRegistrationMsg(): ?string
    {
        return $this->registration_msg;
    }

    /**
     * @return bool True if field registration_msg is not null and not empty.
     */
    public function hasRegistrationMsg(): bool
    {
        return !is_null($this->registration_msg) && !empty($this->registration_msg);
    }

    /**
     * @return boolean True if field confirmation_ts is not null
     */
    public function isConfirmed(): bool
    {
        return !is_null($this->confirmation_ts_dt);
    }

    /**
     * @return DateTime or null Field confirmation_ts.
     */
    public function getConfirmationTimestamp(): ?DateTime
    {
        return $this->confirmation_ts_dt;
    }

    /**
     * @return bool True if all three fields password, old_passwd and email
     * hold empty or null values
     */
    public function isDummyUser(): bool
    {
        return !$this->password && !$this->old_passwd && !$this->email;
    }

    /**
     * @return bool True if field old_passwd is not null and not empty.
     */
    public function needsMigration(): bool
    {
        return !is_null($this->old_passwd) && !empty($this->old_passwd);
    }

    /**
     * Authenticates using the new password field and checks that the
     * user is active.
     * @param string $password
     * @return boolean True if $password matches non-empty field password
     * and the user is active.
     */
    public function auth(string $password): bool
    {
        assert(!empty($password));
        assert($this->hasPassword());
        if (!$password || !$this->hasPassword()) {
            return false;
        }
        if (!$this->isActive()) {
            return false;
        }
        return password_verify($password, $this->password);
    }

    /**
     * Authenticate using the old password field old_passwd, ignoring any
     * active value.
     * @param string $oldPassword
     * @return boolean True if $oldPassword matches non-empty field
     * old_passwd.
     */
    public function oldauth(string $oldPassword): bool
    {
        assert(!empty($oldPassword));
        assert($this->hasOldPassword());
        if (!$oldPassword || !$this->hasOldPassword()) {
            return false;
        }
        return md5($oldPassword) === $this->old_passwd;
    }

    /**
     * @return boolean True if field password is not null and not empty.
     */
    public function hasPassword(): bool
    {
        return !is_null($this->password) && !empty($this->password);
    }

    /**
     * @return boolean True if field old_passwd is not null and not empty.
     */
    public function hasOldPassword(): bool
    {
        return !is_null($this->old_passwd) && !empty($this->old_passwd);
    }

    /**
     * True, if all values are equal
     */
    public function equals(self $other): bool
    {
        return $this->iduser === $other->iduser
            && $this->nick === $other->nick
            && $this->email === $other->email
            && $this->admin === $other->admin
            && $this->active === $other->active
            && $this->registration_ts_dt == $other->registration_ts_dt
            && $this->registration_msg == $other->registration_msg
            && $this->confirmation_ts_dt == $other->confirmation_ts_dt
            && $this->password === $other->password
            && $this->old_passwd === $other->old_passwd
        ;
    }
}
