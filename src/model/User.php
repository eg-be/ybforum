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

/**
 * A User from the database (user_table)
 */
class User
{
    /**
     * Create an instance from the passed values. Comes in handy for testing without database
     */
    public static function CreateUser(int $iduser, string $nick, ?string $email, int $admin, int $active, 
        string $registration_ts, ?string $registration_msg, ?string $confirmation_ts, 
        ?string $password, ?string $old_passwd) : User
    {
        $ref = new ReflectionClass(User::class);
        $ctor = $ref->getConstructor();
        $ctor->setAccessible(true);
        $user = $ref->newInstanceWithoutConstructor();
        $ref->getProperty('iduser')->setValue($user, $iduser);
        $ref->getProperty('nick')->setValue($user, $nick);
        $ref->getProperty('email')->setValue($user, $email);
        $ref->getProperty('admin')->setValue($user, $admin);
        $ref->getProperty('active')->setValue($user, $active);
        $ref->getProperty('registration_ts')->setValue($user, $registration_ts);
        $ref->getProperty('registration_msg')->setValue($user, $registration_msg);
        $ref->getProperty('confirmation_ts')->setValue($user, $confirmation_ts);
        $ref->getProperty('password')->setValue($user, $password);
        $ref->getProperty('old_passwd')->setValue($user, $old_passwd);
        $ctor->invoke($user);
        return $user;
    }

    /**
     * Looks up a row in user_table matching passed $userId in field iduser.
     * If such an entry is found, a User object is created and returned. NULL
     * is returned if no matching entry is found.
     * @param int $userId Must match field iduser.
     * @return User or null.
     * @throws Exception If a database operation fails.
     */        
    public static function LoadUserById(ForumDb $db, int $userId) :?User
    {
        return $db->LoadUserById($userId);
    }
    
    
    /**
     * Looks up a row in user_table matching passed $nick in field nick.
     * If such an entry is found, a User object is created and returned. NULL
     * is returned if no matching entry is found.
     * @param string $nick Must match field nick.
     * @return User or null.
     * @throws Exception If a database operation fails.
     */    
    public static function LoadUserByNick(ForumDb $db, string $nick) :?User
    {
        return $db->LoadUserByNick($nick);
    }
    
    /**
     * Looks up a row in user_table matching passed $email in field email.
     * If such an entry is found, a User object is created and returned. NULL
     * is returned if no matching entry is found.
     * @param string $email Must match field email.
     * @return User or null.
     * @throws Exception If a database operation fails.
     */    
    public static function LoadUserByEmail(ForumDb $db, string $email) :?User
    {
        return $db->LoadUserByEmail($email);
    }
    
    /**
     * Create an instance using one of the static methods. This constructor
     * will assert that valid values are set when it is invoked.
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
        if($this->confirmation_ts)
        {
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
    public function GetMinimalUserInfoAsString() : string
    {
        $userStr = 'IdUser: ' . $this->GetId();
        $userStr.= '; Nick: ' . $this->GetNick();            
        $userStr.= '; Email: ';
        if($this->HasEmail())
        {
            $userStr.= $this->GetEmail();
        }
        else
        {
            $userStr.= '<No Email set>';
        }
        $userStr.= '; Active: ' . ($this->IsActive() ? 'Yes' : 'No');
        $userStr.= '; Confirmed: ' . ($this->IsConfirmed() ? 'Yes' : 'No');
        $userStr.= '; Needs Migration: ' . ($this->NeedsMigration() ? 'Yes' : 'No');

        return $userStr;        
    }
    
    /**
     * Get all information about this User as a string.
     * Mostly used for logging.
     * @return string
     */
    public function GetFullUserInfoAsString() : string
    {
        $userStr = $this->GetMinimalUserInfoAsString();
        $userStr.= '; HasPassword: ' . ($this->HasPassword() ? 'Yes' : 'No');
        $userStr.= '; HasOldPassword: ' . ($this->HasOldPassword() ? 'Yes' : 'No');
        $userStr.= '; IsAdmin: ' . ($this->IsAdmin() ? 'Yes' : 'No');
        $userStr.= '; IsDummy: ' . ($this->IsDummyUser() ? 'Yes' : 'No');
        // Registration ts is always set
        $userStr.= '; Registration Timestamp: '
                . $this->GetRegistrationTimestamp()->format('d.m.Y H:i:s');
        $userStr.= '; Confirmation Timestamp: ';
        // Rest could be null
        if($this->IsConfirmed())
        {
            $userStr.= $this->GetConfirmationTimestamp()->format('d.m.Y H:i:s');
        }
        else
        {
            $userStr.= '<Not Confirmed>';
        }
        $userStr.= '; Registration Message: ';
        if($this->HasRegistrationMsg())
        {
            $userStr.= $this->GetRegistrationMsg();
        }
        else
        {
            $userStr.= '<No Registration Message set>';
        }
        
        return $userStr;        
    }
    
    /**
     * @return int Field iduser.
     */
    public function GetId() : int
    {
        return $this->iduser;
    }
    
    /**
     * @return string Field nick.
     */
    public function GetNick() : string
    {
        return $this->nick;
    }
    
    /**
     * @return bool True if field email is not null and not empty.
     */
    public function HasEmail() : bool
    {
        return !is_null($this->email) && !empty($this->email);
    }
    
    /**
     * @return string or null. Field email.
     */
    public function GetEmail() : ?string
    {
        return $this->email;
    }
    
    /**
     * @return bool True if field admin holds a value > 0.
     */
    public function IsAdmin() : bool
    {
        return $this->admin > 0;
    }
    
    /**
     * @return bool True if field active holds a value > 0.
     */
    public function IsActive() : bool
    {
        return $this->active > 0;
    }
    
    /**
     * @return DateTime Field registration_ts.
     */
    public function GetRegistrationTimestamp() : DateTime
    {
        return $this->registration_ts_dt;
    }
    
    /**
     * @return string or null Field registration_msg.
     */
    public function GetRegistrationMsg() : ?string
    {
        return $this->registration_msg;
    }
    
    /**
     * @return bool True if field registration_msg is not null and not empty.
     */
    public function HasRegistrationMsg() : bool
    {
        return !is_null($this->registration_msg) && !empty($this->registration_msg);
    }    
    
    /**
     * @return boolean True if field confirmation_ts is not null
     */
    public function IsConfirmed() : bool
    {
        return !is_null($this->confirmation_ts_dt);
    }
    
    /**
     * @return DateTime or null Field confirmation_ts.
     */    
    public function GetConfirmationTimestamp() : ?DateTime
    {
        return $this->confirmation_ts_dt;
    }
    
    /**
     * @return bool True if all three fields password, old_passwd and email 
     * hold empty or null values
     */
    public function IsDummyUser() : bool
    {
        return !$this->password && !$this->old_passwd && !$this->email;
    }
    
    /**
     * @return bool True if field old_passwd is not null and not empty.
     */
    public function NeedsMigration() : bool
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
    public function Auth(string $password) : bool
    {
        assert(!empty($password));
        assert($this->HasPassword());
        if(!$password || !$this->HasPassword())
        {
            return false;
        }
        if(!$this->IsActive())
        {
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
    public function OldAuth(string $oldPassword) : bool
    {
        assert(!empty($oldPassword));
        assert($this->HasOldPassword());
        if(!$oldPassword || !$this->HasOldPassword())
        {
            return false;
        }
        return md5($oldPassword) === $this->old_passwd;
    }
    
    /**
     * @return boolean True if field password is not null and not empty.
     */
    public function HasPassword() : bool
    {
        return !is_null($this->password) && !empty($this->password);
    }
    
    /**
     * @return boolean True if field old_passwd is not null and not empty.
     */
    public function HasOldPassword() : bool
    {
        return !is_null($this->old_passwd) && !empty($this->old_passwd);
    }

    /**
     * True, if all values are equal
     */
    public function equals(self $other) : bool
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
