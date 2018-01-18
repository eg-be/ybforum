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
     * Looks up a row in user_table matching passed $userId in field iduser.
     * If such an entry is found, a User object is created and returned. NULL
     * is returned if no matching entry is found.
     * @param int $userId Must match field iduser.
     * @return User or null.
     * @throws Exception If a database operation fails.
     */        
    public static function LoadUserById(ForumDb $db, int $userId)
    {
        assert($userId > 0);
        
        $query = 'SELECT iduser, nick, email, admin, active, '
                . 'registration_ts, registration_msg, '
                . 'confirmation_ts, '
                . 'password, old_passwd '
                . 'FROM user_table '
                . 'WHERE iduser = :iduser';
        $stmt = $db->prepare($query);
        $stmt->execute(array(':iduser' => $userId));
        $user = $stmt->fetchObject('User');
        if($user === false)
        {
            $user = null;
        }
        return $user;        
    }
    
    
    /**
     * Looks up a row in user_table matching passed $nick in field nick.
     * If such an entry is found, a User object is created and returned. NULL
     * is returned if no matching entry is found.
     * @param string $nick Must match field nick.
     * @return User or null.
     * @throws Exception If a database operation fails.
     */    
    public static function LoadUserByNick(ForumDb $db, string $nick)
    {
        assert(!empty($nick));

        $query = 'SELECT iduser '
                . 'FROM user_table '
                . 'WHERE nick = :nick';
        $stmt = $db->prepare($query);
        $stmt->execute(array(':nick' => $nick));
        $result = $stmt->fetch();
        if($result === false)
        {
            return null;
        }
        return User::LoadUserById($db, $result['iduser']);
    }
    
    /**
     * Looks up a row in user_table matching passed $email in field email.
     * If such an entry is found, a User object is created and returned. NULL
     * is returned if no matching entry is found.
     * @param string $email Must match field email.
     * @return User or null.
     * @throws Exception If a database operation fails.
     */    
    public static function LoadUserByEmail(ForumDb $db, string $email)
    {
        assert(!empty($email));

        $query = 'SELECT iduser '
                . 'FROM user_table '
                . 'WHERE email = :email';
        $stmt = $db->prepare($query);
        $stmt->execute(array(':email' => $email));
        $result = $stmt->fetch();
        if($result === false)
        {
            return null;
        }
        return User::LoadUserById($db, $result['iduser']);
    }
    
    /**
     * Create an instance using one of the static methods. This constructor
     * will assert that valid values are set when it is invoked.
     */
    private function __construct()
    {
        assert(is_int($this->iduser) && $this->iduser > 0);
        assert(is_string($this->nick) && !empty($this->nick));
        assert(is_null($this->email) || (is_string($this->email) && !empty($this->email)));
        assert(is_int($this->admin));
        assert(is_int($this->active));
        assert(is_string($this->registration_ts) && !empty($this->registration_ts));
        assert(is_null($this->registration_msg) || (is_string($this->registration_msg) && !empty($this->registration_msg)));
        assert(is_null($this->confirmation_ts) || (is_string($this->confirmation_ts) && !empty($this->confirmation_ts)));
        $this->registration_ts = new DateTime($this->registration_ts);
        if($this->confirmation_ts)
        {
            $this->confirmation_ts = new DateTime($this->confirmation_ts);
        }
    }

    private $iduser;
    private $nick;
    private $email;
    private $admin;
    private $active;
    private $registration_ts;
    private $registration_msg;
    private $confirmation_ts;
    private $password;
    private $old_passwd;
    
    /**
     * @return int Field iduser.
     */
    public function GetId()
    {
        return $this->iduser;
    }
    
    /**
     * @return string Field nick.
     */
    public function GetNick()
    {
        return $this->nick;
    }
    
    /**
     * @return bool True if field email is not null and not empty.
     */
    public function HasEmail()
    {
        return !is_null($this->email) && !empty($this->email);
    }
    
    /**
     * @return string or null. Field email.
     */
    public function GetEmail()
    {
        return $this->email;
    }
    
    /**
     * @return bool True if field admin holds a value > 0.
     */
    public function IsAdmin()
    {
        return $this->admin;
    }
    
    /**
     * @return bool True if field active holds a value > 0.
     */
    public function IsActive()
    {
        return $this->active;
    }
    
    /**
     * @return DateTime Field registration_ts.
     */
    public function GetRegistrationTimestamp()
    {
        return $this->registration_ts;
    }
    
    /**
     * @return string or null Field registration_msg.
     */
    public function GetRegistrationMsg()
    {
        return $this->registration_msg;
    }
    
    /**
     * @return boolean True if field confirmation_ts is not null
     */
    public function IsConfirmed()
    {
        return !is_null($this->confirmation_ts);
    }
    
    /**
     * @return DateTime or null Field confirmation_ts.
     */    
    public function GetConfirmationTimestamp()
    {
        return $this->confirmation_ts;
    }
    
    /**
     * @return bool True if all three fields password, old_passwd and email 
     * hold empty or null values
     */
    public function IsDummyUser()
    {
        return !$this->password && !$this->old_passwd && !$this->email;
    }
    
    /**
     * @return bool True if field old_passwd is not null and not empty.
     */
    public function NeedsMigration()
    {
        return !is_null($this->old_passwd) && !empty($this->old_passwd);
    }
    
    /**
     * @return bool True if field confirmation_ts is set to null.
     */
    public function NeedsConfirmation()
    {
        return is_null($this->confirmation_ts);
    }
    
    /**
     * Authenticates using the new password field and checks that the
     * user is active.
     * @param string $password
     * @return boolean True if $password matches non-empty field password 
     * and the user is active.
     */
    public function Auth(string $password)
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
    public function OldAuth(string $oldPassword)
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
    public function HasPassword()
    {
        return !is_null($this->password) && !empty($this->password);
    }
    
    /**
     * @return boolean True if field old_passwd is not null and not empty.
     */
    public function HasOldPassword()
    {
        return !is_null($this->old_passwd) && !empty($this->old_passwd);
    }
}
