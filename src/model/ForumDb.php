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

require_once __DIR__.'/DbConfig.php';
require_once __DIR__.'/User.php';
require_once __DIR__.'/Post.php';
require_once __DIR__.'/PostIndexEntry.php';
require_once __DIR__.'/SearchDefinitions.php';
require_once __DIR__.'/SearchResult.php';
require_once __DIR__.'/../helpers/ErrorHandler.php';
require_once __DIR__.'/../helpers/Logger.php';
require_once __DIR__.'/../YbForumConfig.php';

/**
 * The database we are working with, as a PDO object.
 * This is the only place where we wan to have SQL.
 */
class ForumDb extends PDO
{
    /**
     * @var string Field confirm_user_table.confirm_source holds this value if
     * the row holds the confirmation values for a newly registered user.
     */
    const CONFIRM_SOURCE_NEWUSER = 'registernewuser';
    
    /**
     * @var string Field confirm_user_table.confirm_source hold this value if
     * the row holds the confirmation values for a user who is migrating from
     * the old dataset.
     */
    const CONFIRM_SOURCE_MIGRATE = 'migrateuser';
    
    /**
     * Create a new instance. Connects to the database using the values from
     * DbConfig. Sets m_connected to true on success.
     * Invokes ErrorHandler::OnException() if connecting fails.
     */
    public function __construct(bool $readOnly = true) 
    {
        $this->m_connected = false;
        $this->m_readOnly = $readOnly;
		
        $dsn = 'mysql:host=' . DbConfig::SERVERNAME .
                ';dbname=' . DbConfig::DEFAULT_DB .
                ';charset=' . DbConfig::CHARSET;
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false
        );
        if($this->m_readOnly)
        {
            parent::__construct($dsn, DbConfig::RO_USERNAME, DbConfig::RO_PASSWORD, $options);
        }
        else
        {
            parent::__construct($dsn, DbConfig::RW_USERNAME, DbConfig::RW_PASSWORD, $options);			
        }
        $this->m_connected = true;
    }
  
    /**
    * @return bool True if connected to db (constructor run without exception)
    */    
    public function IsConnected() : bool
    {
        return $this->m_connected;
    }
    
    /**
    * @return bool True if connected using read-only parameters
    */
    public function IsReadOnly() : bool
    {
        return $this->m_readOnly;
    }
  
    /**
    * Count number of entries in thread_table.
    * Note: This will also include threads that have no
    * visible posts (no posts, or all posts hidden). Its
    * just the count of rows of the thread_table.
    * @return int
    * @throws Exception If a database operation fails.
    */
    public function GetThreadCount() : int
    {
        $stmt = $this->query('SELECT COUNT(idthread) FROM thread_table');
        $result = $stmt->fetch(PDO::FETCH_NUM);
        if($result === false)
        {
            throw new Exception('Failed to get ThreadCount');
        }
        return $result[0];
    }
    
    /**
     * Counter number of entries in post_table.
     * Note: This will also include posts that are hidden, 
     * its just the count of rows of the post_table.
     * @throws Exception If database operation fails
     */
    public function GetPostCount() : int
    {
        $stmt = $this->query('SELECT COUNT(idpost) FROM post_table');
        $result = $stmt->fetch(PDO::FETCH_NUM);
        if($result === false)
        {
            throw new Exception('Failed to get PostCount');        
        }
        return $result[0];
    }
    
    /**
     * Count number of entries in user_table
     * @throws Exception If database operation fails
     */
    public function GetUserCount() : int
    {
        $stmt = $this->query('SELECT COUNT(iduser) FROM user_table');
        $result = $stmt->fetch(PDO::FETCH_NUM);
        if($result === false)
        {
            throw new Exception('Failed to get UserCount');        
        }
        return $result[0];
    }
    
    /**
     * Count number of entries in user_table that have the flag active set to 1
     * @throws Exception If database operation fails
     */
    public function GetActiveUserCount() : int
    {
        $stmt = $this->query('SELECT COUNT(iduser) FROM user_table '
                . 'WHERE active > 0');
        $result = $stmt->fetch(PDO::FETCH_NUM);
        if($result === false)
        {
            throw new Exception('Failed to get GetActiveUserCount');        
        }
        return $result[0];
    }
    
    /**
     * Count number of entries that have been deactivated 
     * by an admin: Those users have an entry in table
     * user_deactivated_reason_table. All rows from 
     * user_deactivated_reason_table are included where
     * the referened user has the flag user_table.active set
     * to 0.
     * @throws Exception If database operation fails
     */
    public function GetFromAdminDeactivatedUserCount()
    {
        $query = 'SELECT COUNT(*) '
                . 'FROM ((user_deactivated_reason_table d JOIN user_table u1 '
                . 'ON((u1.iduser = d.iduser))) JOIN user_table u2 '
                . 'ON((u2.iduser = d.deactivated_by_iduser))) '
                . 'WHERE (u1.active = 0)';
        $stmt = $this->query($query);
        $result = $stmt->fetch(PDO::FETCH_NUM);
        if($result === false)
        {
            throw new Exception('Failed to get GetFromAdminDeactivatedUserCount');        
        }
        return $result[0];
    }
    
    /**
     * Count number of users which are inactive, because
     * an admin needs to approve the registration. Those
     * users must have confirmed their e-mail address,
     * therefore confirmation_ts must not be NULL, their
     * active flag must be set to 0 and they are not allowed
     * to appear in user_deactivated_reason_table.
     * @throws Exception If database operation fails
     */
    public function GetPendingAdminApprovalUserCount() : int
    {
        $query = 'SELECT COUNT(*) '
                . 'FROM user_table '
                . 'WHERE ((confirmation_ts IS NOT NULL) '
                . 'AND active = 0 '
                . 'AND (NOT(user_table.iduser IN (SELECT '
                . ' iduser FROM user_deactivated_reason_table))))';
        $stmt = $this->query($query);
        $result = $stmt->fetch(PDO::FETCH_NUM);
        if($result === false)
        {
            throw new Exception('Failed to get GetPendingAdminApprovalUserCount');        
        }
        return $result[0];
    }
    
    /**
     * Count number of entries in user_table that have a non null
     * value in field old_passwd
     * @throws Exception If database operation fails
     */
    public function GetNeedMigrationUserCount() : int
    {
        $stmt = $this->query('SELECT COUNT(iduser) FROM user_table '
                . 'WHERE old_passwd IS NOT NULL');
        $result = $stmt->fetch(PDO::FETCH_NUM);
        if($result === false)
        {
            throw new Exception('Failed to get GetNeedMigrationUserCount');        
        }
        return $result[0];
    }
    
    /**
     * Count number of entries in user_table that have a null value in fields
     * old_passwd, password and email
     * @throws Exception If database operation fails
     */
    public function GetDummyUserCount() : int
    {
        $stmt = $this->query('SELECT COUNT(iduser) FROM user_table '
                . 'WHERE old_passwd IS NULL AND password IS NULL '
                . 'AND email IS NULL');
        $result = $stmt->fetch(PDO::FETCH_NUM);
        if($result === false)
        {
            throw new Exception('Failed to get GetDummyUserCount');        
        }
        return $result[0];
    }
  
    /**
    * Return the MAX(idthread) from thread_table. If no threads exists, 0
    * is returned
    * @return int
    * @throws Exception If a database operation fails.
    */
    public function GetLastThreadId() : int
    {
        $stmt = $this->query('SELECT MAX(idthread) FROM thread_table');
        $result = $stmt->fetch(PDO::FETCH_NUM);
        if($result === false)
        {
            return 0;
        }
        return $result[0];
    }

    const AUTH_FAIL_REASON_NO_SUCH_USER = 1;
    const AUTH_FAIL_REASON_USER_IS_DUMMY = 2;
    const AUTH_FAIL_REASON_USER_IS_INACTIVE = 3;
    const AUTH_FAIL_REASON_PASSWORD_INVALID = 4;
    /**
     * Authenticate against the user_table. Returns a user object if
     * a user with the passed $nick exists and:
     * - If the user has a (new) password set in field password that matches
     * the passed $password and the user is active, 
     * or:
     * - If the user has an old password set in field old_passwd that matches
     * the passed $password (ignoring active).
     * authFailReason is set to one of the constants AUTH_FAIL_REASON_xx
     * only if authentification fails and null is returned. Else its not modified
     * If a user is inactive and the password missmatches, AUTH_FAIL_REASON_USER_IS_INACTIVE
     * is set as authFailReason
     * @param string $password
     * @return User
     */
    public function AuthUser(string $nick, string $password, int &$authFailReason = null) : ?User
    {
        assert(!empty($nick));
        assert(!empty($password));
        
        // log authentication stuff
        $logger = new Logger($this);
        
        $user = $this->LoadUserByNick($nick);
        if(!$user)
        {
            $logger->LogMessage(LogType::LOG_AUTH_FAILED_NO_SUCH_USER, 'Passed nick: ' . $nick);
            if(!is_null($authFailReason))
            {
                $authFailReason = self::AUTH_FAIL_REASON_NO_SUCH_USER;
            }
            return null;
        }
        if($user->IsDummyUser())
        {
            $logger->LogMessageWithUserId(LogType::LOG_AUTH_FAILED_USER_IS_DUMMY, $user);
            if(!is_null($authFailReason))
            {
                $authFailReason = self::AUTH_FAIL_REASON_USER_IS_DUMMY;
            }
            return null;
        }
        if(!$user->IsActive() && !$user->NeedsMigration())
        {
            $logger->LogMessageWithUserId(LogType::LOG_AUTH_FAILED_USER_INACTIVE, $user);
            if(!is_null($authFailReason))
            {
                $authFailReason = self::AUTH_FAIL_REASON_USER_IS_INACTIVE;
            }            
            return null;
        }
        // First try to auth using modern auth, else using old md5 hash auth
        if($user->HasPassword() && $user->Auth($password))
        {
            return $user;
        }
        else if($user->HasOldPassword() && $user->OldAuth($password))
        {
            $logger->LogMessageWithUserId(LogType::LOG_AUTH_USING_OLD_PASSWORD, $user);
            return $user;
        }
        $logger->LogMessageWithUserId(LogType::LOG_AUTH_FAILED_PASSWORD_INVALID, $user);        
        if(!is_null($authFailReason))
        {
            $authFailReason = self::AUTH_FAIL_REASON_PASSWORD_INVALID;
        }        
        return null;
    }

    /**
     * Create a new Thread with the first post. Creates the entry in the
     * thread_table and the first entry for that thread in the post_table.
     * @param User $user User object of authenticated user writing the post.
     * @param string $title Non-empty title of the post.
     * @param ?string $content String with content of the post. Can be null.
     * @param ?string $email String with email. Can be null.
     * @param ?string $linkUrl String with an URL. Can be null.
     * @param ?string $linkText String with a text for the URL. Can be null.
     * @param ?string $imgUrl String with an URL to an image. Can be null.
     * @param string $clientIpAddress Client IP address writing the post.
     * @return int The Value of the field idpost of the post_table for the
     * post just created.
     * @throws InvalidArgumentException If passed user is not active, or
     * if passed user is a dummy.
     * @throws Exception If a database operation fails.
     */
    public function CreateThread(User $user, string $title, 
            ?string $content, ?string $email, 
            ?string $linkUrl, ?string $linkText, 
            ?string $imgUrl, string $clientIpAddress) : int
    {        
        assert(!empty($title));
        assert(is_null($content) || !empty($content));        
        assert(is_null($email) || !empty($email));
        assert(is_null($linkUrl) || !empty($linkUrl));
        assert(is_null($linkText) || !empty($linkText));
        assert(is_null($imgUrl) || !empty($imgUrl));
        assert(!empty($clientIpAddress));
        
        if(!$user->IsActive())
        {
            throw new InvalidArgumentException('User ' . $user->GetNick() . ' is not active');
        }
        if($user->IsDummyUser())
        {
            throw new InvalidArgumentException('User ' . $user->GetNick() . ' is a dummy');            
        }
        $query = 'CALL insert_thread(:iduser, '
                . ':title, :content, :ip_address, '
                . ':email, :link_url, :link_text, :img_url, '
                . '@newPostId)';
        $stmt = $this->prepare($query);
        $stmt->execute(array(
            ':iduser' => $user->GetId(), ':title' => $title,
            ':content' => $content, ':ip_address' => $clientIpAddress, 
            ':email' => $email, ':link_url' => $linkUrl, 
            ':link_text' => $linkText, ':img_url' => $imgUrl
        ));
        // reading an out-parameter is somewhat stupid with PDO + mariaDb, or I dont get it
        // close to discard any result from the stmt. If not, no new query can be executed
        // and the query holds as result the rows that have been selected for update
        $stmt->closeCursor();
        $res = $this->query("SELECT @newPostId")->fetch(PDO::FETCH_ASSOC);
        $newPostId = $res['@newPostId'];
        return $newPostId;
    }
    
    /**
     * Create a reply in post_table. Calls the stored procedure to create
     * a reply entry in post_table.
     * @param int $parentPostId Value of field idpost of parent post.
     * @param User $user User writing the post.
     * @param string $title Title of the post. Must be non-empty.
     * @param ?string $content Content of the post. Can be null.
     * @param ?string $email String with email. Can be null.
     * @param ?string $linkUrl String with an URL. Can be null.
     * @param ?string $linkText String with a text for the URL. Can be null.
     * @param ?string $imgUrl String with an URL to an image. Can be null.
     * @param string $clientIpAddress Client IP address writing the post.
     * @return int The Value of the field idpost of the post_table for the
     * post just created.
     * @throws InvalidArgumentException If passed user is not active, or
     * if passed user is a dummy, or if no post matching $parentPostId exists.
     * @throws Exception If a database operation fails.
     */
    public function CreateReplay(int $parentPostId, User $user, string $title, 
            ?string $content, ?string $email, 
            ?string $linkUrl, ?string $linkText,
            ?string $imgUrl, string $clientIpAddress) : int
    {
        assert($parentPostId > 0);
        $this->validateNonEmpty([$title, $clientIpAddress]);
        $this->validateNotWhitespaceOnly([$content, $email, $linkUrl, $linkText, $imgUrl, $clientIpAddress ]);
        if(!$user->IsActive())
        {
            throw new InvalidArgumentException('User ' . $user->GetNick() . ' is not active');
        }
        if($user->IsDummyUser())
        {
            throw new InvalidArgumentException('User ' . $user->GetNick() . ' is a dummy');
        }
        $parentPost = $this->LoadPost($parentPostId);
        if(!$parentPost)
        {
            throw new InvalidArgumentException('No post exists for passed parent postid ' . $parentPostId);
        }
        $userId = $user->GetId();
        $query = 'CALL insert_reply(:parent_idpost, :iduser, '
                . ':title, :content, :ip_address, '
                . ':email, :link_url, :link_text, :img_url, '
                . '@newPostId)';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':parent_idpost' => $parentPostId,
            ':iduser' => $user->GetId(), ':title' => $title,
            ':content' => $content, ':ip_address' => $clientIpAddress,
            ':email' => $email, ':link_url' => $linkUrl,
            ':link_text' => $linkText, ':img_url' => $imgUrl
        ));
        // reading an out-parameter is somewhat stupid with PDO + mariaDb, or I dont get it
        // close to discard any result from the stmt. If not, no new query can be executed
        // and the query holds as result the rows that have been selected for update
        $stmt->closeCursor();
        $res = $this->query("SELECT @newPostId")->fetch(PDO::FETCH_ASSOC);
        $newPostId = $res['@newPostId'];
        return $newPostId;
    }
    
    /**
     * Creates a new entry in user_table and returns the iduser of that entry.
     * @param string $nick Value for field nick.
     * @param string $email Value for field email.
     * @param ?string $registrationMsg Value for field registration_mgs.
     * @return User Newly cretad User, as read from db after inserting it.
     * @throws InvalidArgumentException If nick or email already used, or if
     * empty values are passed for nick or email
     */
    public function CreateNewUser(string $nick, string $email,
            ?string $registrationMsg) : User
    {
        $this->validateNonEmpty([$nick, $email]);
        $existingUser = $this->LoadUserByNick($nick);
        if(!is_null($existingUser))
        {
            throw new InvalidArgumentException('Nick  ' . $nick . ' already used');
        }
        $existingUser = $this->LoadUserByEmail($email);
        if(!is_null($existingUser))
        {
            throw new InvalidArgumentException('Email  ' . $email . ' already used');
        }        
        $query = 'INSERT INTO user_table (nick, email, '
                . 'registration_msg) '
                . 'VALUES(:nick, :email, '
                . ':registration_msg)';
        $stmt = $this->prepare($query);
        $stmt->execute(array(
            ':nick' => $nick,
            ':email' => $email,
            ':registration_msg' => $registrationMsg
        ));
        $userId = $this->lastInsertId();
        $user = $this->LoadUserById($userId);
        $logger = new Logger($this);
        $logger->LogMessageWithUserId(LogType::LOG_USER_CREATED, $user);
        return $user;
    }
    
    /**
     * Creates a new entry in the confirm_user_table with the
     * hashed password and email address and a newly created confirmation code.
     * Returns the confirmation code created.
     * Before creating a new entry, all entries matching the userId of the 
     * passed $user are deleted.
     * @param User $user The user that should be migrated.
     * @param string $newPasswordClearText clear-text password to be used as new password
     * @param string $newEmail email to be set for the user
     * @param string $confirmSource Must be ForumDb::CONFIRM_SOURCE_NEWUSER
     * or ForumDb::CONFIRM_SOURCE_NEWUSER
     * @param string $requestClientIpAddress address initiating the request
     * @return string The confirmation code created
     * @throws Exception If a database operation fails.
     */
    public function RequestConfirmUserCode(User $user, 
            string $newPasswordClearText, 
            string $newEmail, 
            string $confirmSource,
            string $requestClientIpAddress) : string
    {
        $this->validateNonEmpty([ $newPasswordClearText, $newEmail, $confirmSource, $requestClientIpAddress ]);
        if(!($confirmSource === self::CONFIRM_SOURCE_MIGRATE || 
                $confirmSource === self::CONFIRM_SOURCE_NEWUSER))
        {
            throw new Exception('$confirmSource must be ' .
                    self::CONFIRM_SOURCE_MIGRATE . ' or ' .
                    self::CONFIRM_SOURCE_NEWUSER);
        }
        // delete an eventually already existing entry first
        $this->RemoveConfirmUserCode($user);
        // generate some random bytes to be used as confirmation code
        $bytes = random_bytes(YbForumConfig::CONFIRMATION_CODE_LENGTH);
        $confirmCode = mb_strtoupper(bin2hex($bytes), 'UTF-8');
        // and hash the new password
        $hashedPass = password_hash($newPasswordClearText, PASSWORD_DEFAULT);
        
        // insert it into the migration table
        $insertQuery = 'INSERT INTO confirm_user_table (iduser, email, '
                . 'password, confirm_code, request_ip_address, '
                . 'confirm_source) '
                . 'VALUES(:iduser, :email, :password, '
                . ':confirm_code, :request_ip_address, :confirm_source)';
        $insertStmt = $this->prepare($insertQuery);
        $insertStmt->execute(array(':iduser' => $user->GetId(),
            ':email' => $newEmail, ':password' => $hashedPass,
            ':confirm_code' => $confirmCode, 
            ':request_ip_address' => $requestClientIpAddress,
            ':confirm_source' => $confirmSource
        ));
        
        // and log that we have created a new code
        $logger = new Logger($this);
        $logType = LogType::LOG_CONFIRM_REGISTRATION_CODE_CREATED;
        if($confirmSource === self::CONFIRM_SOURCE_MIGRATE)
        {
            $logType = LogType::LOG_CONFIRM_MIGRATION_CODE_CREATED;
        }
        $logger->LogMessageWithUserId($logType, $user,  
                'Mailaddress with entry: ' . $newEmail);

        return $confirmCode;
    }
    
    /**
     * Check that in table confirm_user_table a row exists that matches
     * passed confirmation code $code in field confirm_code.
     * A code is considered to be valid if it is not older (field 
     * request_date) than YbForumConfig::CONF_CODE_VALID_PERIOD hours.
     * If a code is found, but the code is invalid, it is always removed
     * from the table.
     * Returned is an array with the field values iduser, password, email and
     * confirm_source for that row, or null if no valid code was found
     * @param string $code Confirmation code that must match field confirm_code.
     * @param bool $remove If true, the entry will be removed from the table
     * (if a valid entry was found, invalid entries are always removed)
     * @return array holding values of fields iduser, password, email and
     * confirm_source if a matching row is found, or null if no such row 
     * exists or if the code is invalid.
     * @throws Exception If removing a used code fails (or any other database
     * operation fails). If empty value is passed for $code
     */
    public function VerifyConfirmUserCode(string $code, bool $remove) : ?array
    {
        $this->validateNonEmpty([$code]);
        // Select the matching entry in the confirm table
        $query = 'SELECT iduser, email, password, request_date, '
                . 'confirm_source '
                . 'FROM confirm_user_table '
                . 'WHERE confirm_code = BINARY :confirm_code';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':confirm_code' => $code));
        $result = $stmt->fetch();
        if(!$result)
        {
            return null;
        }
        $userId = $result['iduser'];
        $password = $result['password'];
        $email = $result['email'];
        $confirmSource = $result['confirm_source'];
        $requestDate = new DateTime($result['request_date']);
        // Check if the code is not too old:
        $codeExpired = !$this->IsDateWithinConfirmPeriod($requestDate);
        // If the code is expired, or we are requested to remove it, delete:
        if($codeExpired || $remove)
        {
            $user = $this->LoadUserById($userId);
            if($this->RemoveConfirmUserCode($user) !== 1)
            {
                throw new Exception('Not exactly one row was deleted for used '
                        . 'confirmation code .' . $code);
            }
            if($codeExpired)
            {
                return null;
            }
        }
        // okay, return the values
        $values = array(
            'iduser' => $userId, 
            'password' => $password, 
            'email' => $email,
            'confirm_source' => $confirmSource);
        return $values;
    }
    
    /**
     * Remove entries from the confirm_user_table that match the passed 
     * user
     * @param User $user
     * @return int Number of rows that have been removed
     */
    public function RemoveConfirmUserCode(User $user) : int
    {
        $delQuery = 'DELETE FROM confirm_user_table WHERE iduser = :iduser';
        $delStmt = $this->prepare($delQuery);
        $delStmt->execute(array(':iduser' => $user->GetId()));
        return $delStmt->rowCount();
    }
    
    /**
     * Searches for a entry in confirm_user_table matching the passed
     * $user, and compares the value of the field confirm_source
     * against self::CONFIRM_SOURCE_NEWUSER or 
     * self::CONFIRM_SOURCE_MIGRATE. If the value is one of that
     * defined values, the defined value is returend. Else an
     * InvalidArgumentException is thrown.
     * An InvalidArgumentException is also thrown if no such row matching
     * the passed $userId exists.
     * @param User $user
     * @throw InvalidArgumentException
     * @return self::CONFIRM_SOURCE_NEWUSER or self::CONFIRM_SOURCE_MIGRATE
     */
    public function GetConfirmReason(User $user) : string
    {
        $query = 'SELECT confirm_source '
                . 'FROM confirm_user_table '
                . 'WHERE iduser = :iduser';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':iduser' => $user->GetId()));
        $row = $stmt->fetch();
        if(!$row)
        {
            throw new InvalidArgumentException('No row matching iduser '
                    . $userId . ' was found in confirm_user_table');
        }
        $sourceValue = $row['confirm_source'];
        if($sourceValue === self::CONFIRM_SOURCE_NEWUSER)
        {
            return self::CONFIRM_SOURCE_NEWUSER;
        }
        else if($sourceValue === self::CONFIRM_SOURCE_MIGRATE)
        {
            return self::CONFIRM_SOURCE_MIGRATE;
        }
        else
        {
            throw new InvalidArgumentException('Invalid confirm_source value '
                    . $sourceValue . ' for iduser ' . $user->GetId());
        }
    }
    
    /**
     * Marks a user as confirmed, by setting the field confirmation_ts of
     * the matching row to the current timestamp and updating the row
     * with the passed values for password and email
     * This will in all cases set old_passwd to NULL.
     * @param User &$user The user that shall be confirmed. The passed reference will be
     * updated with the newly set values, if the function succeeds
     * @param string $hashedPassword Value for field password
     * @param string $email Value for field email.
     * @param bool $activate If True, field active will be set to 1, else to 0.
     * @throws InvalidArgumentException If no row was updated
     */
    public function ConfirmUser(User &$user, string $hashedPassword, 
            string $email, bool $activate) : void
    {
        $activateQuery = 'UPDATE user_table SET password = :password, '
                . 'email = :email, active = :active, old_passwd = NULL,'
                . 'confirmation_ts = CURRENT_TIMESTAMP() '
                . 'WHERE iduser = :iduser';
        $activateStmt = $this->prepare($activateQuery);
        $activateStmt->execute(array(
            ':password' => $hashedPassword, 
            ':email' => $email, 
            ':active' => ($activate ? 1 : 0),
            ':iduser' => $user->GetId()));
        if($activateStmt->rowCount() === 0)
        {
            throw new InvalidArgumentException('No row updated matching userid ' . $user->GetId());
        }
        
        $logger = new Logger($this);
        if($activate)
        {
            $logger->LogMessageWithUserId(LogType::LOG_USER_MIGRATION_CONFIRMED, $user);
            $logger->LogMessageWithUserId(LogType::LOG_USER_ACTIVED, $user);
        }
        else
        {
            $logger->LogMessageWithUserId(LogType::LOG_USER_REGISTRATION_CONFIRMED, $user);
        }
        // Update the passed User object that has been passed
        $user = $this->LoadUserById($user->GetId());
    }
    
    /**
     * Creates a new entry in reset_password_table by creating a new 
     * confirmation code. Returns the code created.
     * Before a row is inserted, any row matching passed $user is removed.
     * @param User $user To create an entry for.
     * @param string $requestClientIpAddress
     * @return string Confirmation code.
     */
    public function RequestPasswordResetCode(User $user, 
            string $requestClientIpAddress) : string
    {
        // Delete any already existing entry first
        $this->RemoveResetPasswordCode($user);
        // Create some randomness and insert as new entry
        $bytes = random_bytes(YbForumConfig::CONFIRMATION_CODE_LENGTH);
        $confirmCode = mb_strtoupper(bin2hex($bytes), 'UTF-8');
        // insert it into the reset password table
        $insertQuery = 'INSERT INTO reset_password_table '
                . '(iduser, confirm_code, request_ip_address) '
                . 'VALUES(:iduser, :confirm_code, :request_ip_address)';
        $insertStmt = $this->prepare($insertQuery);
        $insertStmt->execute(array(':iduser' => $user->GetId(),
            ':confirm_code' => $confirmCode, 
            ':request_ip_address' => $requestClientIpAddress
        ));

        // log event
        $logger = new Logger($this);
        $logger->LogMessageWithUserId(LogType::LOG_PASS_RESET_CODE_CREATED, 
                $user, 'Mailaddress of user: ' . $user->GetEmail());
        
        
        // and return the confirm-code
        return $confirmCode;
    }
    
    /**
     * Check that in table reset_password_table an entry matching the passed
     * $code in field confirm_code exists.
     * A code is considered to be valid if it is not older (field 
     * request_date) than YbForumConfig::CONF_CODE_VALID_PERIOD hours.
     * If a code is found, but the code is invalid, it is always removed
     * from the table.
     * @param string $code Confirmation code that must match field confirm_code.
     * @param bool $remove If true, the entry will be removed from the table
     * (if a valid entry was found, invalid entries are always removed)
     * @return int If a matching row is found, and the code is valid,
     * the value of the field iduser is returned. 
     * If no matching row is found, or the code is invalid, 0 is returned.
     * @throws Exception If removing a used code fails, or any other 
     * database operation fails.
     */
    public function VerifyPasswordResetCode(string $code, bool $remove) : int
    {
        // get entry in reset_password_table
        $query = 'SELECT iduser, request_date '
                . 'FROM reset_password_table '
                . 'WHERE confirm_code = BINARY :confirm_code';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':confirm_code' => $code));
        $result = $stmt->fetch();
        if(!$result)
        {
            return 0;
        }
        $userId = $result['iduser'];
        $requestDate = new DateTime($result['request_date']);
        // Check if the code is not too old:
        $codeExpired = !$this->IsDateWithinConfirmPeriod($requestDate);
        // If the code is expired, or we are requested to remove it, delete:
        if($codeExpired || $remove)
        {
            $user = $this->LoadUserById($userId);
            if($this->RemoveResetPasswordCode($user) !== 1)
            {
                throw new Exception('Not exactly one row was deleted for used '
                        . 'confirmation code .' . $code);
            }
            if($codeExpired)
            {
                return 0;
            }
        }
        // okay, return the userid of this entry
        return $userId;
    }
    
    /**
     * Removes all entries from table reset_password_table that match the
     * passed $user in field iduser.
     * @param User $user to match against field iduser
     * @return int Number of rows returned
     */
    public function RemoveResetPasswordCode(User $user) : int
    {
        $delQuery = 'DELETE FROM reset_password_table WHERE iduser = :iduser';
        $delStmt = $this->prepare($delQuery);
        $delStmt->execute(array(':iduser' => $user->GetId()));
        return $delStmt->rowCount();
    }
    
    /**
     * Update the password of a user by updating the value in field password
     * in the user_table for a row matching passed $user in field userid.
     * @param User &$user Identify the row in field userid. The passed 
     * reference will be updated with the newly set values, if the function 
     * succeeds
     * @param string $clearTextPassword New password to set (will be hashed)
     * @throws Exception If not exactly one row is updated.
     */
    public function UpdateUserPassword(User &$user, string $clearTextPassword) : void
    {
        $hashedPassword = password_hash($clearTextPassword, PASSWORD_DEFAULT);
        $query = 'UPDATE user_table SET password = :password '
                . 'WHERE iduser = :iduser';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':password' => $hashedPassword,
            ':iduser' => $user->GetId()));
        if($stmt->rowCount() !== 1)
        {
            throw new Exception('Not exactly one row was updated in table '
                    . 'user_table matching iduser ' . $user->GetId());
        }
        $logger = new Logger($this);
        $logger->LogMessageWithUserId(LogType::LOG_USER_PASSWORD_UPDATED, $user);
        // and reload the user-object
        $user = $this->LoadUserById($user->GetId());
    }
    
    /**
     * Creates a new entry in update_email_table with a confirmation code
     * to update the email address of a user. Returns the code created.
     * Before a new entry is created, all entries matching passed $user
     * are deleted from the update_email_table.
     * @param User $user To create an entry for.
     * @param string $newEmail The email address that is awaiting confirmation.
     * @param string $requestClientIpAddress
     * @return string confirmation code created.
     */
    public function RequestUpdateEmailCode(User $user, string $newEmail, 
            string $requestClientIpAddress) : string
    {        
        // delete an eventually already existing entry first
        $this->RemoveUpdateEmailCode($user);
        
        // generate some random bytes to be used as confirmation code
        $bytes = random_bytes(YbForumConfig::CONFIRMATION_CODE_LENGTH);
        $confirmCode = mb_strtoupper(bin2hex($bytes), 'UTF-8');
        
        // insert it into the update_email_table
        $insertQuery = 'INSERT INTO update_email_table (iduser, email, '
                . 'confirm_code, request_ip_address) '
                . 'VALUES(:iduser, :email, '
                . ':confirm_code, :request_ip_address)';
        $insertStmt = $this->prepare($insertQuery);
        $insertStmt->execute(array(':iduser' => $user->GetId(),
            ':email' => $newEmail, 
            ':confirm_code' => $confirmCode, 
            ':request_ip_address' => $requestClientIpAddress
        ));
        
        $logger = new Logger($this);
        $logger->LogMessage(LogType::LOG_CONFIRM_EMAIL_CODE_CREATED, 
                'Mailaddress with entry: ' . $newEmail);
        
        return $confirmCode;
    }    
  
    /**
     * Check that in table update_email_table an entry matching the passed
     * $code in field confirm_code exists.
     * A code is considered to be valid if it is not older (field 
     * request_date) than YbForumConfig::CONF_CODE_VALID_PERIOD hours.
     * If a code is found, but the code is invalid, it is always removed
     * from the table.
     * @param string $code To search for in field confirm_code
     * @param bool $remove If true, the entry will be removed from the table
     * (if a valid entry was found, invalid entries are always removed)
     * @return array Array with the values of the matching row. Array holds 
     * values for the keys 'iduser' and 'email'. null is returned if no
     * matching row is found, or the code is invalid
     */
    public function VerifyUpdateEmailCode(string $code, bool $remove) : ?array
    {
        // Select the matching entry in the update_email_table
        $query = 'SELECT iduser, email, request_date '
                . 'FROM update_email_table '
                . 'WHERE confirm_code = BINARY :confirm_code';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':confirm_code' => $code));
        $result = $stmt->fetch();
        if(!$result)
        {
            return null;
        }
        $userId = $result['iduser'];
        $email = $result['email'];
        $requestDate = new DateTime($result['request_date']);
        // Check if the code is not too old:
        $codeExpired = !$this->IsDateWithinConfirmPeriod($requestDate);
        if($codeExpired || $remove)
        {
            $user = $this->LoadUserById($userId);
            if($this->RemoveUpdateEmailCode($user) !== 1)
            {
                throw new Exception('Not exactly one row was deleted for used '
                        . 'confirmation code .' . $code);
            }
            if($codeExpired)
            {
                return null;
            }
        }
        // okay, return the values
        $values = array('iduser' => $userId, 'email' => $email);
        return $values;
    }
    
    /**
     * Removes all entries from table update_email_table that match the
     * passed $user in field iduser.
     * @param User $user to match against field iduser
     * @return int Number of rows returned
     */    
    public function RemoveUpdateEmailCode(User $user) : int
    {
        $delQuery = 'DELETE FROM update_email_table WHERE iduser = :iduser';
        $delStmt = $this->prepare($delQuery);
        $delStmt->execute(array(':iduser' => $user->GetId()));
        return $delStmt->rowCount();
    }
    
    /**
     * Update the email of a user by updating the value in field email
     * in the user_table for a row matching passed $user in field userid.
     * @param User &$user Update the email of the passed User. The passed 
     * reference will be updated with the newly set values, if the function 
     * succeeds.
     * @param string $email
     * @throws Exception If not exactly one row is updated.
     */
    public function UpdateUserEmail(User &$user, string $email) : void
    {
        $activateQuery = 'UPDATE user_table SET '
                . 'email = :email '
                . 'WHERE iduser = :iduser';
        $activateStmt = $this->prepare($activateQuery);
        $activateStmt->execute(array(
            ':email' => $email, 
            ':iduser' => $user->GetId()));
        if($activateStmt->rowCount() !== 1)
        {
            throw new Exception('Not exactly one row was updated in table '
                    . 'user_table matching iduser ' . $user->GetId());
        }
        $logger = new Logger($this);
        $logger->LogMessageWithUserId(LogType::LOG_USER_EMAIL_UPDATED, $user, 'New Email: ' . $email);

        // and reload the user
        $user = $this->LoadUserById($user->GetId());
    }

    /**
     * Activate a user if that user exists and has confirmed the email address.
     * If user is already activated, this method does nothing.
     * Also removes all entries from the user_deactivated_reason_table that
     * match the passed $userId.
     * @param User $user User to active. The passed  reference will be updated
     * with the newly set values, if the function succeeds.
     * @throws InvalidArgumentException If no user with passed $userId exists
     * or if the user has no value in the field confirmed_ts
     */
    public function ActivateUser(User &$user) : void
    {
        // Get the user first
        if(!$user->IsConfirmed())
        {
            throw new InvalidArgumentException('Cannot activate a user which '
                    . 'has not confiremd his email address');
        }
        if($user->IsActive())
        {
            return;
        }
        $this->beginTransaction();
        try {
            $query = 'UPDATE user_table SET active = 1 '
                    . 'WHERE iduser = :iduser';
            $stmt = $this->prepare($query);
            $stmt->execute(array(':iduser' => $user->GetId()));
            if($stmt->rowCount() !== 1)
            {
                throw new Exception('Not exactly one row was updated in table '
                        . 'user_table matching iduser ' . $user->GetId());
            }
            // remove entry from the deactivated reasons table
            $this->ClearDeactivationReason($user);
            // log what happened
            $logger = new Logger($this);
            $logger->LogMessageWithUserId(LogType::LOG_USER_ACTIVED, $user);  
            $this->commit();

            // and reload the passed user
            $user = $this->LoadUserById($user->GetId());
        }
        catch(Exception $e) {
            $this->rollBack();
            throw $e;
        }        
    }

    /**
     * Deactivate a user. If user is already deactivated, this method
     * does nothing.
     * @param User $user User to deactivate. The passed  reference will 
     * be updated with the newly set values, if the function succeeds.
     * @param string $reason The reason why to deactivate
     * @param int $deactivatedByAdminUser Must be an active admin 
     * @throws InvalidArgumentException If no user with passed $userId exists
     * or if $deactivatedByUserId is not an active admin
     */
    public function DeactivateUser(User &$user, string $reason,
            User $deactivatedByAdminUser) : void
    {
        if(!$user->IsActive())
        {
            return;
        }
        // Check that the user who is trying to deactivate, is an admin:
        if(!$deactivatedByAdminUser || !($deactivatedByAdminUser->IsAdmin() 
            && $deactivatedByAdminUser->IsActive())) {
            throw new InvalidArgumentException('Only active admins can deactivate');
        }
        $this->beginTransaction();
        try 
        {
            // Deactivate the user
            $query = 'UPDATE user_table SET active = 0 '
                    . 'WHERE iduser = :iduser';
            $stmt = $this->prepare($query);
            $stmt->execute(array(':iduser' => $user->GetId()));
            if($stmt->rowCount() !== 1)
            {
                throw new Exception('Not exactly one row was updated in table '
                        . 'user_table matching iduser ' . $user->GetId());
            }
            // And create the corresponding entry in deactivated_reason_table
            $this->SetDeactivationReason($user, $reason, $deactivatedByAdminUser->GetId());
            // There was a modification, create the corresponding log entry
            $logger = new Logger($this);
            $logger->LogMessageWithUserId(LogType::LOG_USER_DEACTIVATED, $user, 
                'Reason: ' . $reason);
            $this->commit();

            // and reload the passed user
            $user = $this->LoadUserById($user->GetId());
        }
        catch(Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }
    
    /**
     * Create a new entry in the user_deactivated_reason_table.
     * Any existing entry matching the passed $userId in field userid
     * is deleted first.
     * @param int $userId Value for field iduser
     * @param string $reason Value for field reason
     * @param int $deactivatedByUserId Value for the field 
     * deactivated_by_iduser
     */
    private function SetDeactivationReason(User $user, string $reason,
            int $deactivatedByUserId) : void
    {
        // delete any existing entry in the reasons-table
        $delQuery = 'DELETE FROM user_deactivated_reason_table '
                . 'WHERE iduser = :iduser';
        $delStmt = $this->prepare($delQuery);
        $delStmt->execute(array(':iduser' => $user->GetId()));
        // and insert the new reason
        $insQuery = 'INSERT INTO user_deactivated_reason_table '
                . '(iduser, deactivated_by_iduser, reason) '
                . 'VALUES(:iduser, :deactivated_by_iduser, :reason)';
        $insStmt = $this->prepare($insQuery);
        $insStmt->execute(array(':iduser' => $user->GetId(), 
            ':deactivated_by_iduser' => $deactivatedByUserId, 
            ':reason' => $reason));
    }
    
    
    /**
     * Get the deactivation-reason for a user.
     * Returns null if there is no entry user_deactivated_reason_table
     * @param User $user User to reason for
     */
    public function GetDeactivationReason(User $user) : ?string
    {
        // Select the matching entry in the table
        $query = 'SELECT reason '
                . 'FROM user_deactivated_reason_table '
                . 'WHERE iduser = :iduser';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':iduser' => $user->GetId()));
        $result = $stmt->fetch();
        if(!$result)
        {
            return null;
        }
        $reason = $result['reason'];
        return $reason;
    }    

    /**
     * Sets or unsets the admin-flag on a user that exists 
     * and has confirmed the email address.
     * If the user-flag is already set to the passed value, 
     * this method does nothing.
     * @param User $user User to modify. The passed  reference will 
     * be updated with the newly set values, if the function succeeds.
     * @param bool $admin Enable the admin-flag or remove it
     * @throws InvalidArgumentException If the user is not confirmed
     */
    public function SetAdmin(User &$user, bool $admin) : void
    {
        if(!$user->IsConfirmed())
        {
            throw new InvalidArgumentException('Cannot propagate a user '
                    . 'to an admin who '
                    . 'has not confiremd his email address');
        }
        if(($admin && $user->IsAdmin()) || (!$admin && !$user->IsAdmin()))
        {
            return; // nothing to do
        }
        $query = 'UPDATE user_table SET admin = :admin '
                . 'WHERE iduser = :iduser';
        $stmt = $this->prepare($query);
        $stmt->execute(array(
            ':iduser' => $user->GetId(),
            ':admin' => ($admin ? 1 : 0)
        ));
        if($stmt->rowCount() !== 1)
        {
            throw new Exception('Not exactly one row was updated in table '
                    . 'user_table matching iduser ' . $userId);
        }
        $logger = new Logger($this);
        $logger->LogMessageWithUserId(
            $admin ? LogType::LOG_USER_ADMIN_SET : LogType::LOG_USER_ADMIN_REMOVED,
            $user
        );

        // and reload the passed user
        $user = $this->LoadUserById($user->GetId());
    }
    
    /**
     * Remove all entries from user_deactivated_reason_table that match
     * the passed $user
     * @param User $user
     */
    private function ClearDeactivationReason(User $user) : void
    {
        $delQuery = 'DELETE FROM user_deactivated_reason_table '
                . 'WHERE iduser = :iduser';
        $delStmt = $this->prepare($delQuery);
        $delStmt->execute(array(':iduser' => $user->GetId()));
    }
    
    /**
     * Turns the passed $userId into a dummy, by setting the values for 
     * the fields email, password and old_passwd to null and 
     * confirmation_ts to null. Also sets active and admin to 0 and
     * removes all entries from the user_deactivated_reason_table
     * @param User $user User to modify. The passed  reference will 
     * be updated with the newly set values, if the function succeeds.
     */
    public function MakeDummy(User &$user) : void
    {
        $query = 'UPDATE user_table SET email = NULL, password = NULL, '
                . 'old_passwd = NULL, confirmation_ts = NULL, '
                . 'active = 0, admin = 0 '
                . 'WHERE iduser = :iduser';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':iduser' => $user->GetId()));
        $logger = new Logger($this);
        $logger->LogMessageWithUserId(LogType::LOG_USER_TURNED_INTO_DUMMY, $user, 
                'Previous values: email: ' . $user->GetEmail()
                . '; active: ' . ($user->IsActive() ? '1' : '0')
                . '; admin: ' . ($user->IsAdmin() ? '1' : '0')
                . '; confirmation_ts: ' . ($user->IsConfirmed() ? $user->GetConfirmationTimestamp()->format('d.m.Y H:i:s') : 'null') 
                . '; hadPassword: ' . ($user->HasPassword() ? 'True' : 'False')
                . '; hadOldPasswd: ' . ($user->HasOldPassword() ? 'True' : 'False'));
        $this->ClearDeactivationReason($user);

        // and reload the passed user
        $user = $this->LoadUserById($user->GetId());
    }
    
    /**
     * Deletes a user by removing it entirely from the user_table.
     * This method will fail if there are
     * already post entries from that user.
     * @param User $user
     * @throws InvalidArgumentException If the user has already posted something
     */
    public function DeleteUser(User $user) : void
    {
        if($this->GetPostByUserCount($user) > 0)
        {
            throw new InvalidArgumentException('Cannot delete user '
                    . $user->GetId() . ' there are already entries in post_table '
                    . 'by that user. Want to turn her into a dummy instead?');
        }
        // Load the user to add some logging
        $logMessage = null;
        $extendedLogMessage = null;
        $logMessage = $user->GetMinimalUserInfoAsString();
        $extendedLogMessage = $user->GetFullUserInfoAsString();
        $query = 'DELETE FROM user_table WHERE iduser = :iduser';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':iduser' => $user->GetId()));
        $logger = new Logger($this);
        $logger->LogMessage(LogType::LOG_USER_DELETED, $logMessage, $extendedLogMessage);
    }
    
    /**
     * Count the number of entries in post_table that have been created
     * using the passed $userId.
     * note: Hidden posts are also included.
     * @param User $user
     * @return int Post-count
     * @throws Exception If database operation fails
     */
    public function GetPostByUserCount(User $user) : int
    {
        $query = 'SELECT COUNT(idpost) FROM post_table '
                . 'WHERE iduser = :iduser';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':iduser' => $user->GetId()));
        $result = $stmt->fetch(PDO::FETCH_NUM);
        if($result === false)
        {
            throw new Exception('Failed to get GetPostByUserCount');        
        }
        return $result[0];
    }
    
    /**
     * Update the field hidden of an entry in post_table and of all
     * the children. If the post identified by postId, this method
     * does nothing - especially it does not alter any children.
     * @param int $postId
     * @param bool $show If true, set hidden to 0, else set hidden to 1.
     * @throws InvalidArgumentException If no post with passed $postId 
     * exists
     */
    public function SetPostVisible(int $postId, bool $show = true) : void
    {
        // The underlying implementation is recursive. Start an outer
        // transaction from up there
        $this->beginTransaction();
        try
        {
            $this->SetPostVisibleImpl($postId, $show);
            $this->commit();
        }
        catch(Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    private function SetPostVisibleImpl(int $postId, bool $show = true) : void
    {
        $post = $this->LoadPost($postId);
        if(!$post)
        {
            throw new InvalidArgumentException('No post with id ' . $postId . 
                    ' was found');
        }
        if($show && !$post->IsHidden())
        {
            return;
        }
        if(!$show && $post->IsHidden())
        {
            return;
        }
        // Hide this post
        $query = 'UPDATE post_table SET hidden = :hidden '
                . 'WHERE idpost = :idpost';
        $stmt = $this->prepare($query);
        $stmt->execute(array(
            ':hidden' => ($show ? 0 : 1),
            ':idpost' => $postId
        ));
        // And recursively hide all children
        $childrenQuery = 'SELECT idpost FROM post_table '
                . 'WHERE parent_idpost = :idpost';
        $childrenStmt = $this->prepare($childrenQuery);
        $childrenStmt->execute(array(':idpost' => $postId));
        while($childRow = $childrenStmt->fetch())
        {
            $childPostId = $childRow['idpost'];
            $this->SetPostVisibleImpl($childPostId, $show);
        }
        $logger = new Logger($this);
        $logger->LogMessage(($show ? LogType::LOG_POST_SHOW : LogType::LOG_POST_HIDDEN), 'PostId: ' . $postId);
    }
    
    /**
     * Test if passed DateTime is not older than (now - YbForumConfig::CONF_CODE_VALID_PERIOD)
     * @param DateTime $ts
     * @return bool True if DateTime is new than(now - YbForumConfig::CONF_CODE_VALID_PERIOD)
     */
    public function IsDateWithinConfirmPeriod(DateTime $ts) : bool
    {
        $now = new DateTime();
        $codeValidInterval = new DateInterval(YbForumConfig::CONF_CODE_VALID_PERIOD);
        $minDate = $now;
        $minDate->sub($codeValidInterval);
        $inbetween = ($ts > $minDate);
        return $inbetween;
    }
    
    /**
     * Test if passed email appears in blacklist_table in field email.
     * If an (exactly) matching entry is found, the description field of
     * that row is returned.
     * Else false is found.
     * @param string $email
     * @return bool|string false if no entry is found, else the value of the 
     * description field.
     */
    public function IsEmailOnBlacklistExactly(string $email) : bool|string
    {
        $query = 'SELECT description FROM blacklist_table '
                . 'WHERE email = :email';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':email' => $email));
        $result = $stmt->fetch();
        if($result)
        {
            return $result['description'];
        }
        return false;
    }
    
    /**
     * Test if passed email matches a regex from the blacklist_table.
     * If an regex of field email_regex matches the email, the 
     * description field of that row is returned.
     * Else false is found.
     * @param string $email
     * @return bool|string false if no matching entry is found, else the value of the 
     * description field.
     */
    public function IsEmailOnBlacklistRegex(string $email) : bool|string
    {
        $query = 'SELECT email_regex, description FROM blacklist_table '
                . 'WHERE email_regex IS NOT NULL';
        $stmt = $this->prepare($query);
        $stmt->execute();
        while($row = $stmt->fetch())
        {
            $regex = $row['email_regex'];
            if(preg_match($regex, $email) === 1)
            {
                return $row['description'];
            }
        }
        return false;
    }
    
    /**
     * Add a single email to the blacklist
     * @param string $email
     * @param string $reason
     */
    public function AddBlacklist(string $email, string $reason) : void
    {
        $query = 'INSERT INTO blacklist_table (email, description) '
                . 'VALUES(:email, :description)';
        $stmt = $this->prepare($query);
        $stmt->execute(array(
            ':email' => $email,
            ':description' => $reason
        ));
        $logger = new Logger($this);
        $logger->LogMessage(LogType::LOG_BLACKLIST_EMAIL_ADDED,
                'Email: ' . $email . ' Reason: ' . $reason);
    }

    /**
     * Return all Users that have the admin-flag set and are active
     */
    public function GetAdminUsers() : array
    {
        $query = 'SELECT iduser FROM user_table '
            . 'WHERE admin > 0 AND active > 0';
        $admins = array();
        $stmt = $this->prepare($query);
        $stmt->execute();
        while($row = $stmt->fetch())
        {
            $userId = $row['iduser'];
            $user = $this->LoadUserById($userId);
            array_push($admins, $user);
        }
        return $admins;
    }
    
    /**
     * Looks up a row in user_table matching passed $userId in field iduser.
     * If such an entry is found, a User object is created and returned. NULL
     * is returned if no matching entry is found.
     * @param int $userId Must match field iduser.
     * @return ?User or null.
     * @throws Exception If a database operation fails.
     */
    public function LoadUserById(int $userId) : ?User
    {
        assert($userId > 0);
        
        $query = 'SELECT iduser, nick, email, admin, active, '
                . 'registration_ts, registration_msg, '
                . 'confirmation_ts, '
                . 'password, old_passwd '
                . 'FROM user_table '
                . 'WHERE iduser = :iduser';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':iduser' => $userId));
        $user = $stmt->fetchObject(User::class);
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
    public function LoadUserByNick(string $nick) :?User
    {
        assert(!empty($nick));

        $query = 'SELECT iduser, nick, email, admin, active, '
                . 'registration_ts, registration_msg, '
                . 'confirmation_ts, '
                . 'password, old_passwd '
                . 'FROM user_table '
                . 'WHERE nick = :nick';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':nick' => $nick));
        $user = $stmt->fetchObject(User::class);
        if($user === false)
        {
            $user = null;
        }
        return $user;
    }
    
    /**
     * Looks up a row in user_table matching passed $email in field email.
     * If such an entry is found, a User object is created and returned. NULL
     * is returned if no matching entry is found.
     * @param string $email Must match field email.
     * @return User or null.
     * @throws Exception If a database operation fails.
     */    
    public function LoadUserByEmail(string $email) :?User
    {
        assert(!empty($email));

        $query = 'SELECT iduser, nick, email, admin, active, '
                . 'registration_ts, registration_msg, '
                . 'confirmation_ts, '
                . 'password, old_passwd '
                . 'FROM user_table '
                . 'WHERE email = :email';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':email' => $email));
        $user = $stmt->fetchObject(User::class);
        if($user === false)
        {
            $user = null;
        }
        return $user;
    }

    /**
     * Loads thread structures and invokes callback with an array
     * of PostIndexEntry objects: Search for a number of $maxThreads, where
     * the last thread is the thread with $maxThreadId. For every thread, an
     * array is created, holding the thread index entries in form of
     * PostIndexEntry objects.
     * As soon as all PostIndexEntry objects
     * for one thread have been placed in the array, the $threadIndexCallback
     * is invoked with the array f PostIndexEntry objects for that thread.
     * PostIndexEntry objects inside an array are sorted by the rank
     * value (ascending).
     * Threads are iterated by idthread descending.
     * Hidden posts and their children are not added to the array of
     * PostIndexEntry objects.
     * @param int $maxThreads Maximum number of threads to load index entries
     * for.
     * @param int $maxThreadId Maximum thread id to load index entries for,
     * the callback will start with the index entries for this thread.
     * @param callable $threadIndexCallback Callback to invoke with an array of
     * ThreadIndexEntry objects.
     * @throws Exception If a database operation fails.
     */
    public function LoadThreadIndexEntries(
        int $maxThreads, int $maxThreadId,
        callable $threadIndexCallback) : void
    {
        assert($maxThreads > 0);
        assert($maxThreadId > 0);
        assert($this->IsConnected());
        $minThreadId = $maxThreadId - $maxThreads;
        $query = 'SELECT idpost, idthread, parent_idpost, nick, '
                . 'title, indent, creation_ts, '
                . 'content IS NOT NULL AS has_content,'
                . 'hidden '
                . 'FROM post_table LEFT JOIN '
                . 'user_table ON post_table.iduser = user_table.iduser '
                . 'WHERE idthread <= :maxThreadId AND idthread > :minThreadId '
                . 'ORDER BY idthread DESC, `rank`';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':maxThreadId' => $maxThreadId,
            ':minThreadId' => $minThreadId));
        $threadIndexEntries = array();
        $lastThreadId = 0;
        $inHiddenPath = false;
        $hiddenStartedAtIndent = 0;
        while($indexEntry = $stmt->fetchObject(PostIndexEntry::class))
        {
            // whenever a new thread starts, notify the user about the
            // previous entries.
            if($indexEntry->GetThreadId() !== $lastThreadId && $lastThreadId !== 0)
            {
                if(!empty($threadIndexEntries))
                {
                    call_user_func($threadIndexCallback, $threadIndexEntries);
                }
                $threadIndexEntries = array();
            }
            $lastThreadId = $indexEntry->GetThreadId();
            if($inHiddenPath && $indexEntry->GetIndent() <= $hiddenStartedAtIndent)
            {
                // might be leaving the hidden path
                $inHiddenPath = false;
            }
            if($indexEntry->IsHidden() && $inHiddenPath === false)
            {
                // entering a hidden path, discard until we are out of it
                $inHiddenPath = true;
                $hiddenStartedAtIndent = $indexEntry->GetIndent();
            }
            if(!$inHiddenPath)
            {
                array_push($threadIndexEntries, $indexEntry);
            }
        }
        // dont forget the rest
        if(!empty($threadIndexEntries))
        {
            call_user_func($threadIndexCallback, $threadIndexEntries);
        }
    }

    /**
     * Load the replies of a post as PostIndexEntry objects. Returned is
     * an array, ordered by rank. If a hidden post is encountered, its whole
     * subtree (and the post itself) is skipped, and not included in the
     * returned array, except $includeHidden is set to true.
     * 
     * @param Post $post A post to load children for.
     * @return array Holding PostIndexEntry objects.
     */
    public function LoadPostReplies(Post $post, bool $includeHidden = false) : array
    {
        $query = 'SELECT idpost, idthread, parent_idpost, nick, '
                . 'title, indent, creation_ts, '
                . 'content IS NOT NULL AS has_content,'
                . 'hidden '
                . 'FROM post_table LEFT JOIN '
                . 'user_table ON post_table.iduser = user_table.iduser '
                . 'WHERE idthread = :idthread AND indent > :indent AND `rank` > :rank '
                . 'ORDER BY idthread DESC, `rank`';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':idthread' => $post->GetThreadId(), 
                ':indent' => $post->GetIndent(),
                ':rank' => $post->GetRank()));
        $replies = array();
        $childOfOurPost = true;
        $ourPostIndent = $post->GetIndent();
        $ourPostId = $post->GetId();
        $inHiddenPath = false;
        $hiddenStartedAtIndent = 0;
        while($indexEntry = $stmt->fetchObject(PostIndexEntry::class))
        {
            // check if entry with indent + 1 are direct ancestors of our post:
            if($indexEntry->GetIndent() === $ourPostIndent + 1)
            {
                $childOfOurPost = ($indexEntry->GetParentPostId() === $ourPostId);
            }
            if($childOfOurPost)
            {
                // we are leaving if we have reached the same indent again
                // as we have entered the hidden path                
                if($inHiddenPath && $indexEntry->GetIndent() <= $hiddenStartedAtIndent)
                {
                    $inHiddenPath = false;
                }                  
                // Check if we are entering a hidden path part (and not ready in)
                if($indexEntry->IsHidden() > 0 && $inHiddenPath === false)
                {
                    $inHiddenPath = true;
                    $hiddenStartedAtIndent = $indexEntry->GetIndent();
                }
                if(!$inHiddenPath || $includeHidden)
                {
                    array_push($replies, $indexEntry);
                }
            }
        }
        return $replies;
    }

    /**
     * Loads a list of the newest posts.
     * @param int $maxEntries Maximum number of newest entries to load.
     * @return array An array of PostIndexEntry objects. Hold max. 
     * $maxEntries of PostIndexEntry objects, sorted by idpost descending.
     */
    public function LoadRecentPosts(int $maxEntries) : array
    {
        $query = 'SELECT idpost, idthread, parent_idpost, nick, '
                . 'title, indent, creation_ts, '
                . 'content IS NOT NULL AS has_content,'
                . 'hidden '
                . 'FROM post_table LEFT JOIN '
                . 'user_table ON post_table.iduser = user_table.iduser '
                . 'WHERE hidden = 0 '
                . 'ORDER BY idpost DESC LIMIT :maxEntries';
        $stmt = $this->prepare($query);
        $stmt->execute(array( ':maxEntries' => $maxEntries));
        $replies = array();
        while($indexEntry = $stmt->fetchObject(PostIndexEntry::class))
        {
            array_push($replies, $indexEntry);
        }
        return $replies;
    }

    /**
     * Load a post from the post_table. Searches for a row matching passed
     * $idPost. Creates a Post object if such a row is found and returns that
     * Post object. Returns NULL if no matching row is found.
     * @param int $idPost
     * @return \Post
     * @throws Exception If a database operation fails.
     */  
    public function LoadPost(int $idPost) : ?Post
    {
        assert($idPost > 0);
        $query = 'SELECT idpost, idthread, parent_idpost, nick, '
                . 'post_table.iduser AS iduser, '
                . 'title, content, `rank`, indent, '
                . 'creation_ts, '
                . 'post_table.email AS email, '
                . 'link_url, link_text, img_url, old_no, '
                . 'hidden, ip_address '
                . 'FROM post_table '
                . 'LEFT JOIN user_table '
                . 'ON post_table.iduser = user_table.iduser '
                . 'WHERE idpost = :idpost';
        $stmt = $this->prepare($query);
        $stmt->execute(array('idpost' => $idPost));
        $post = $stmt->fetchObject(Post::class);
        if($post === false)
        {
            $post = null;
        }
        return $post;
    }

    public function SearchPosts(string $searchString, string $nick, 
            int $limit, int $offset, 
            SortField $sortField, SortOrder $sortOrder,
            bool $noReplies) : array
    {
        $query = '';
        $params = array();
        if($searchString)
        {
            $mode = 'IN NATURAL LANGUAGE MODE';
            // if we have symbols from a binary search, switch to that mode
            if(preg_match('/\+|\-|<|>|\(.+\)|\*|~/', $searchString))
            {
                $mode = 'IN BOOLEAN MODE';
            }            
            // full-text search if we have a searchString
            $query = 'SELECT p.idpost AS idpost, p.iduser AS iduser, '
                    . 'p.title AS title, p.creation_ts AS creation_ts, '
                    . 'u.nick AS nick, '
                    . 'p.content IS NOT NULL AS has_content, '
                    . 'MATCH (title, content) '
                    . 'AGAINST (:search_string1 ' . $mode . ') AS relevance '
                    . 'FROM post_table p LEFT JOIN user_table u '
                    . 'ON p.iduser = u.iduser '
                    . 'WHERE p.hidden = 0 '
                    . 'AND MATCH (title, content) '
                    . 'AGAINST (:search_string2 ' . $mode . ') ';
            $params[':search_string1'] = $searchString;
            $params[':search_string2'] = $searchString;
            // add an optional nick clause
            if($nick)
            {
                $query.= 'AND u.nick = :nick ';
                $params[':nick'] = $nick;
            }
        }
        else if($nick)
        {
            // user-search only
            $query = 'SELECT p.idpost AS idpost, p.iduser AS iduser, '
                    . 'p.title AS title, p.creation_ts AS creation_ts, '
                    . 'u.nick AS nick, '
                    . 'p.content IS NOT NULL AS has_content '
                    . 'FROM post_table p LEFT JOIN user_table u '
                    . 'ON p.iduser = u.iduser '
                    . 'WHERE p.hidden = 0 '
                    . 'AND u.nick = :nick ';
            $params[':nick'] = $nick;
            // for a user-search only, we have no relevance. Fall back to date
            if($sortField === SortField::FIELD_RELEVANCE)
            {
                $sortField = SortField::FIELD_DATE;
            }
        }
        // add an optinal no replies clause
        if($noReplies === true)
        {
            $query.= 'AND p.parent_idpost IS NULL ';
        }
        // add the order by clause
        $query.= 'ORDER BY ' . $sortField->value . ' ' .$sortOrder->value;
        // and the limit with an offset
        $query.= ' LIMIT :offset, :limit';
        $params[':offset'] = $offset;
        $params[':limit'] = $limit;
        // ready to query
        $stmt = $this->prepare($query);
        $stmt->execute($params);
        $results = array();
        while($searchResult = $stmt->fetchObject(SearchResult::class))
        {
            array_push($results, $searchResult);
        }
        return $results;
    }

    /**
     * Return an array holding the email addresses
     * of all registered admin users
     * @return array All admin users, ordered by id
     */
    public function GetAdminMails() : array
    {
        $adminMailAddresses = array();
        $query = 'SELECT email FROM user_table '
                . 'WHERE admin > 0 AND active > 0 ORDER BY iduser';
        $stmt = $this->prepare($query);
        $stmt->execute();
        while($row = $stmt->fetch())
        {
            $adminEmail = $row['email'];
            array_push($adminMailAddresses, $adminEmail);
        }
        return $adminMailAddresses;
    }

    /**
     * @param array values
     * @throws InvalidArgumentException If one of the values
     * is empty or contains only whitespaces
     */
    private function validateNonEmpty(array $values) : void
    {
        foreach($values as $v)
        {
            if(empty(trim($v)))
            {
                throw new InvalidArgumentException('Empty parameter value not allowed');
            }
        }
    }

    /**
     * @param array values
     * @throws InvalidArgumentException If one of the values
     * is empty or contains only whitespaces
     */
    private function validateNotWhitespaceOnly(array $values) : void
    {
        foreach($values as $v)
        {
            if(!empty($v) && empty(trim($v)))
            {
                throw new InvalidArgumentException('Whitespace-only parameter value not allowed');
            }
        }
    }    

    private bool $m_connected;
    private bool $m_readOnly;
}
