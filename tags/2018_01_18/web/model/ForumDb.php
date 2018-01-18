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
require_once __DIR__.'/../helpers/ErrorHandler.php';
require_once __DIR__.'/../helpers/Logger.php';
require_once __DIR__.'/../YbForumConfig.php';

/**
 * The database we are working with, as a PDO object.
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
    public function __construct() 
    {
        $this->m_connected = false;
        $dsn = 'mysql:host=' . DbConfig::SERVERNAME .
                ';dbname=' . DbConfig::DEFAULT_DB .
                ';charset=' . DbConfig::CHARSET;
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false
        );
        parent::__construct($dsn, DbConfig::USERNAME, DbConfig::PASSWORD, $options);
        $this->m_connected = true;
    }
  
    /**
    * @return bool True if connected to db (constructor run without exception)
    */    
    public function IsConnected()
    {
        return $this->m_connected;
    }
  
    /**
    * Count number of entries in thread_table
    * @return int
    * @throws Exception If a database operation fails.
    */
    public function GetThreadCount()
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
     * Counter number of entries in post_table
     * @throws Exception If database operation fails
     */
    public function GetPostCount()
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
    public function GetUserCount()
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
    public function GetActiveUserCount()
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
     * Count number of entries in deactivated_user_view
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
     * Count number of entries in pending_admin_approval_view
     * @throws Exception If database operation fails
     */
    public function GetPendingAdminApprovalUserCount()
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
    public function GetNeedMigrationUserCount()
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
    public function GetDummyUserCount()
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
    public function GetLastThreadId()
    {
        $stmt = $this->query('SELECT MAX(idthread) FROM thread_table');
        $result = $stmt->fetch(PDO::FETCH_NUM);
        if($result === false)
        {
            return 0;
        }
        return $result[0];
    }

    /**
     * Authenticate against the user_table. Returns a user object if
     * a user with the passed $nick exists and:
     * - If the user as a (new) password set in field password that matches
     * the passed $password and the user is active, 
     * or:
     * - If the user has an old password set in field old_passwd that matches
     * the passed $password (ignoring active).
     * @param string $password
     * @return User
     */
    public function AuthUser(string $nick, string $password)
    {
        assert(!empty($nick));
        assert(!empty($password));
        
        // log authentication stuff
        $logger = new Logger($this);
        
        $user = User::LoadUserByNick($this, $nick);
        if(!$user)
        {
            $logger->LogMessage(Logger::LOG_AUTH_FAILED_NO_SUCH_USER, 'Passed nick: ' . $nick);
            return null;
        }
        if($user->IsDummyUser())
        {
            $logger->LogMessageWithUserId(Logger::LOG_AUTH_FAILED_USER_IS_DUMMY, $user->GetId());
            return null;
        }
        if(!$user->IsActive() && !$user->NeedsMigration())
        {
            $logger->LogMessageWithUserId(Logger::LOG_AUTH_FAILED_USER_INACTIVE, $user->GetId());
            return null;
        }
        // First try to auth using modern auth, else using old md5 hash auth
        if($user->HasPassword() && $user->Auth($password))
        {
            return $user;
        }
        else if($user->HasOldPassword() && $user->OldAuth($password))
        {
            $logger->LogMessageWithUserId(Logger::LOG_AUTH_USING_OLD_PASSWORD, $user->GetId());
            return $user;
        }
        $logger->LogMessageWithUserId(Logger::LOG_AUTH_FAILED_PASSWORD_INVALID, $user->GetId());        
        return null;
    }

    /**
     * Create a new Thread with the first post. Creates the entry in the
     * thread_table and the first entry for that thread in the post_table.
     * @param User $user User object of authenticated user writing the post.
     * @param string $title Non-empty title of the post.
     * @param mixed $content String with content of the post. Can be null.
     * @param mixed $email String with email. Can be null.
     * @param mixed $linkUrl String with an URL. Can be null.
     * @param mixed $linkText String with a text for the URL. Can be null.
     * @param mixed $imgUrl String with an URL to an image. Can be null.
     * @param string $clientIpAddress Client IP address writing the post.
     * @return int The Value of the field idpost of the post_table for the
     * post just created.
     * @throws InvalidArgumentException If passed user is not active, or
     * if passed user is a dummy.
     * @throws Exception If a database operation fails.
     */
    public function CreateThread(User $user, string $title, $content, $email, 
            $linkUrl, $linkText, $imgUrl, string $clientIpAddress)
    {        
        assert(!empty($title));
        assert(is_null($content) || (is_string($content) && !empty($content)));        
        assert(is_null($email) || (is_string($email) && !empty($email)));
        assert(is_null($linkUrl) || (is_string($linkUrl) && !empty($linkUrl)));
        assert(is_null($linkText) || (is_string($linkText) && !empty($linkText)));
        assert(is_null($imgUrl) || (is_string($imgUrl) && !empty($imgUrl)));
        assert(!empty($clientIpAddress));
        
        if(!$user->IsActive())
        {
            throw new InvalidArgumentException('User ' . $user->GetNick() . ' is not active');
        }
        if($user->IsDummyUser())
        {
            throw new InvalidArgumentException('User ' . $user->GetNick() . ' is a dummy');            
        }
        
        // Start a transaction, insert the thread first
        $this->beginTransaction();
        $query = 'INSERT INTO thread_table () VALUES()';
        $this->query($query);
        $threadId = $this->lastInsertId();
        if($threadId <= 0)
        {
            // should never happen, but rollback and fail
            $this->rollBack ();
            throw new Exception('Newly created thread has an invalid idthread '
                    . 'value of ' . $threadId);
        }
        // and now the actual post
        $query = 'INSERT INTO post_table (idthread, iduser, title, '
                . 'content, rank, indent, email, '
                . 'link_url, link_text, img_url, '
                . 'ip_address) '
                . 'VALUES(:idthread, :iduser, :title, '
                . ':content, 1, 0, :email, '
                . ':link_url, :link_text, :img_url, '
                . ':ip_address)';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':idthread' => $threadId, 
            ':iduser' => $user->GetId(), ':title' => $title,
            ':content' => $content, ':email' => $email,
            ':link_url' => $linkUrl, ':link_text' => $linkText, ':img_url' => $imgUrl,
            ':ip_address' => $clientIpAddress
        ));
        $postId = $this->lastInsertId();
        // and commit
        $this->commit();
        return $postId;
    }
    
    /**
     * Create a reply in post_table. Calls the stored procedure to create
     * a reply entry in post_table.
     * @param int $parentPostId Value of field idpost of parent post.
     * @param User $user User writing the post.
     * @param string $title Title of the post. Must be non-empty.
     * @param mixed $content Content of the post. Can be null.
     * @param mixed $email String with email. Can be null.
     * @param mixed $linkUrl String with an URL. Can be null.
     * @param mixed $linkText String with a text for the URL. Can be null.
     * @param mixed $imgUrl String with an URL to an image. Can be null.
     * @param string $clientIpAddress Client IP address writing the post.
     * @return int The Value of the field idpost of the post_table for the
     * post just created.
     * @throws InvalidArgumentException If passed user is not active, or
     * if passed user is a dummy, or if no post matching $parentPostId exists.
     * @throws Exception If a database operation fails.
     */
    public function CreateReplay(int $parentPostId, User $user, string $title, 
            $content, $email, 
            $linkUrl, $linkText, $imgUrl, string $clientIpAddress)
    {
        assert($parentPostId > 0);
        assert(!empty($title));
        assert(is_null($content) || (is_string($content) && !empty($content)));        
        assert(is_null($email) || (is_string($email) && !empty($email)));
        assert(is_null($linkUrl) || (is_string($linkUrl) && !empty($linkUrl)));
        assert(is_null($linkText) || (is_string($linkText) && !empty($linkText)));
        assert(is_null($imgUrl) || (is_string($imgUrl) && !empty($imgUrl)));
        assert(!empty($clientIpAddress));
        
        if(!$user->IsActive())
        {
            throw new InvalidArgumentException('User ' . $user->GetNick() . ' is not active');
        }
        if($user->IsDummyUser())
        {
            throw new InvalidArgumentException('User ' . $user->GetNick() . ' is a dummy');            
        }
        $parentPost = Post::LoadPost($this, $parentPostId);
        if(!$parentPost)
        {
            throw new InvalidArgumentException('No post exists for passed parent postid ' . $parentPostId);                        
        }
        
        $query = 'CALL insert_reply(:parent_idpost, :iduser, '
                . ':title, :content, :ip_address, '
                . ':email, :link_url, :link_text, :img_url)';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':parent_idpost' => $parentPostId,
            ':iduser' => $user->GetId(), ':title' => $title,
            ':content' => $content, ':ip_address' => $clientIpAddress,
            ':email' => $email, ':link_url' => $linkUrl,
            ':link_text' => $linkText, ':img_url' => $imgUrl
        ));
        $row = $stmt->fetch(PDO::FETCH_NUM);
        return $row[0];
    }
    
    /**
     * Creates a new entry in user_table and returns the iduser of that entry.
     * @param string $nick Value for field nick.
     * @param string $email Value for field email.
     * @param type $registrationMsg Value for field registration_mgs.
     * @return int Value of iduser field.
     */
    public function CreateNewUser(string $nick, string $email,
            $registrationMsg)
    {
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
        $logger = new Logger();
        $logger->LogMessageWithUserId(Logger::LOG_USER_CREATED, $userId);
        return $userId;
    }
    
    /**
     * Creates a new entry in the confirm_user_table with the
     * hashed password and email address and a newly created confirmation code.
     * Returns the confirmation code created.
     * Before creating a new entry, all entries matching the passed $userId 
     * are deleted.
     * @param int $userId id of the user that should be migrated.
     * @param string $newPasswordClearText clear-text password to be used as new password
     * @param string $newEmail email to be set for the user
     * @param string $confirmSource Must be ForumDb::CONFIRM_SOURCE_NEWUSER
     * or ForumDb::CONFIRM_SOURCE_NEWUSER
     * @param string $requestClientIpAddress address initiating the request
     * @return string The confirmation code created
     * @throws Exception If a database operation fails.
     */
    public function RequestConfirmUserCode(int $userId, 
            string $newPasswordClearText, 
            string $newEmail, 
            string $confirmSource,
            string $requestClientIpAddress)
    {
        if(!($confirmSource === self::CONFIRM_SOURCE_MIGRATE || 
                $confirmSource === self::CONFIRM_SOURCE_NEWUSER))
        {
            throw new Exception('$confirmSource must be ' .
                    self::CONFIRM_SOURCE_MIGRATE . ' or ' .
                    self::CONFIRM_SOURCE_NEWUSER);
        }
        // delete an eventually already existing entry first
        $this->RemoveConfirmCode($userId);        
        // generate some random bytes to be used as confirmation code
        $bytes = random_bytes(64);
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
        $insertStmt->execute(array(':iduser' => $userId,
            ':email' => $newEmail, ':password' => $hashedPass,
            ':confirm_code' => $confirmCode, 
            ':request_ip_address' => $requestClientIpAddress,
            ':confirm_source' => $confirmSource
        ));
        
        // and log that we have created a new code
        $logger = new Logger($this);
        $logType = Logger::LOG_CONFIRM_REGISTRATION_CODE_CREATED;
        if($confirmSource === self::CONFIRM_SOURCE_MIGRATE)
        {
            $logType = Logger::LOG_CONFIRM_MIGRATION_CODE_CREATED;
        }
        $logger->LogMessageWithUserId($logType, $userId,  
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
     * exists.
     * @throws Exception If removing a used code fails (or any other database
     * operation fails).
     */
    public function VerifyConfirmUserCode(string $code, bool $remove)
    {
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
            if($this->RemoveConfirmCode($userId) !== 1)
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
     * iduser. 
     * @param int $userId
     * @ return int Number of rows that have been removed
     */
    public function RemoveConfirmCode(int $userId)
    {
        $delQuery = 'DELETE FROM confirm_user_table WHERE iduser = :iduser';
        $delStmt = $this->prepare($delQuery);
        $delStmt->execute(array(':iduser' => $userId));
        return $delStmt->rowCount();
    }
    
    /**
     * Searches for a entry in confirm_user_table matching the passed
     * $userId, and compares the value of the field confirm_source
     * against self::CONFIRM_SOURCE_NEWUSER or 
     * self::CONFIRM_SOURCE_MIGRATE. If the value is one of that
     * defined values, the defined value is returend. Else an
     * InvalidArgumentException is thrown.
     * An InvalidArgumentException is also thrown if no such row matching
     * the passed $userId exists.
     * @param int $userId
     * @throw InvalidArgumentException
     * @return self::CONFIRM_SOURCE_NEWUSER or self::CONFIRM_SOURCE_MIGRATE
     */
    public function GetConfirmReason(int $userId)
    {
        $query = 'SELECT confirm_source '
                . 'FROM confirm_user_table '
                . 'WHERE iduser = :iduser';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':iduser' => $userId));
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
                    . $sourceValue . ' for iduser ' . $userId);
        }
    }
    
    /**
     * Marks a user as confirmed, by setting the field confirmation_ts of
     * the matching row to the current timestamp and updating the row
     * with the passed values.
     * @param int $userId Identify the row in user_table to update.
     * @param string $hashedPassword Value for field password
     * @param string $email Value for field email.
     * @param bool $activate If True, field active will be set to 1, else to 0.
     * @throws Exception If no row was updated
     */
    public function ConfirmUser(int $userId, string $hashedPassword, 
            string $email, bool $activate)
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
            ':iduser' => $userId));
        if($activateStmt->rowCount() === 0)
        {
            throw new Exception('No row updated matching userid ' . $userId);
        }
        
        $logger = new Logger($this);
        if($activate)
        {
            $logger->LogMessageWithUserId(Logger::LOG_USER_MIGRATION_CONFIRMED, $userId);
            $logger->LogMessageWithUserId(Logger::LOG_USER_ACTIVED, $userId);
        }
        else
        {
            $logger->LogMessageWithUserId(Logger::LOG_USER_REGISTRATION_CONFIRMED, $userId);
        }        
    }
    
    /**
     * Creates a new entry in reset_password_table by creating a new 
     * confirmation code. Returns the code created.
     * Before a row is inserted, any row matching passed $user is removed.
     * @param User $user To create an entry for.
     * @param string $requestClientIpAddress
     * @return string Confirmation code.
     */
    public function RequestPasswortResetCode(User $user, 
            string $requestClientIpAddress)
    {
        // Delete any already existing entry first
        $delQuery = 'DELETE FROM reset_password_table '
                . 'WHERE iduser = :iduser';
        $delStmt = $this->prepare($delQuery);
        $delStmt->execute(array(':iduser' => $user->GetId()));
        // Create some randomness and insert as new entry
        $bytes = random_bytes(64);
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
        $logger->LogMessageWithUserId(Logger::LOG_PASS_RESET_CODE_CREATED, 
                $user->GetId(), 'Mailaddress of user: ' . $user->GetEmail());
        
        
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
    public function VerifyPasswortResetCode(string $code, bool $remove)
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
            $delQuery = 'DELETE FROM reset_password_table WHERE confirm_code = :confirm_code';
            $delStmt = $this->prepare($delQuery);
            $delStmt->execute(array(':confirm_code' => $code));
            if($delStmt->rowCount() !== 1)
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
     * Update the password of a user by updating the value in field password
     * in the user_table for a row matching passed $userId in field userid.
     * @param int $userId Identify the row in field userid
     * @param string $clearTextPassword New password to set (will be hashed)
     * @throws Exception If not exactly one row is updated.
     */
    public function UpdateUserPassword(int $userId, string $clearTextPassword)
    {
        $hashedPassword = password_hash($clearTextPassword, PASSWORD_DEFAULT);
        $query = 'UPDATE user_table SET password = :password '
                . 'WHERE iduser = :iduser';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':password' => $hashedPassword,
            ':iduser' => $userId));
        if($stmt->rowCount() !== 1)
        {
            throw new Exception('Not exactly one row was updated in table '
                    . 'user_table matching iduser ' . $userId);
        }
        $logger = new Logger($this);
        $logger->LogMessageWithUserId(Logger::LOG_USER_PASSWORD_UPDATED, $userId);        
    }
    
    /**
     * Creates a new entry in update_email_table with a confirmation code
     * to update the email address of a user. Returns the code created.
     * Before a new entry is created, all entries matching passed $userId
     * are deleted from the update_email_table.
     * @param int $userId To create an entry for.
     * @param string $newEmail The email address that is awaiting confirmation.
     * @param string $requestClientIpAddress
     * @return string confirmation code created.
     */
    public function RequestUpdateEmailCode(int $userId, string $newEmail, 
            string $requestClientIpAddress)
    {        
        // delete an eventually already existing entry first
        $query = 'DELETE FROM update_email_table WHERE iduser = :iduser';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':iduser' => $userId));
        
        // generate some random bytes to be used as confirmation code
        $bytes = random_bytes(64);
        $confirmCode = mb_strtoupper(bin2hex($bytes), 'UTF-8');
        
        // insert it into the update_email_table
        $insertQuery = 'INSERT INTO update_email_table (iduser, email, '
                . 'confirm_code, request_ip_address) '
                . 'VALUES(:iduser, :email, '
                . ':confirm_code, :request_ip_address)';
        $insertStmt = $this->prepare($insertQuery);
        $insertStmt->execute(array(':iduser' => $userId,
            ':email' => $newEmail, 
            ':confirm_code' => $confirmCode, 
            ':request_ip_address' => $requestClientIpAddress
        ));
        
        $logger = new Logger($this);
        $logger->LogMessage(Logger::LOG_CONFIRM_EMAIL_CODE_CREATED, 
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
    public function VerifyUpdateEmailCode(string $code, bool $remove)
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
            $delQuery = 'DELETE FROM update_email_table WHERE confirm_code = :confirm_code';
            $delStmt = $this->prepare($delQuery);
            $delStmt->execute(array(':confirm_code' => $code));
            if($delStmt->rowCount() !== 1)
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
     * Update the email of a user by updating the value in field email
     * in the user_table for a row matching passed $userId in field userid.
     * @param int $userId
     * @param string $email
     * @throws Exception If not exactly one row is updated.
     */
    public function UpdateUserEmail(int $userId, string $email)
    {
        $activateQuery = 'UPDATE user_table SET '
                . 'email = :email '
                . 'WHERE iduser = :iduser';
        $activateStmt = $this->prepare($activateQuery);
        $activateStmt->execute(array(
            ':email' => $email, 
            ':iduser' => $userId));
        if($activateStmt->rowCount() !== 1)
        {
            throw new Exception('Not exactly one row was updated in table '
                    . 'user_table matching iduser ' . $userId);
        }
        $logger = new Logger($this);
        $logger->LogMessageWithUserId(Logger::LOG_USER_EMAIL_UPDATED, $userId, 'New Email: ' . $email);        
    }

    /**
     * Activate a user if that user exists and has confirmed the email address.
     * If user is already activated, this method does nothing.
     * Also removes all entries from the user_deactivated_reason_table that
     * match the passed $userId.
     * @param int $userId
     * @throws InvalidArgumentException If no user with passed $userId exists
     * or if the user has no value in the field confirmed_ts
     */
    public function ActivateUser(int $userId)
    {
        // Get the user first
        $user = User::LoadUserById($this, $userId);
        if(!$user)
        {
            throw new InvalidArgumentException('No user with id ' . $userId . 
                    ' was found');
        }
        if(!$user->IsConfirmed())
        {
            throw new InvalidArgumentException('Cannot activate a user which '
                    . 'has not confiremd his email address');
        }
        if($user->IsActive())
        {
            return;
        }
        $query = 'UPDATE user_table SET active = 1 '
                . 'WHERE iduser = :iduser';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':iduser' => $userId));
        if($stmt->rowCount() !== 1)
        {
            throw new Exception('Not exactly one row was updated in table '
                    . 'user_table matching iduser ' . $userId);
        }
        $logger = new Logger($this);
        $logger->LogMessageWithUserId(Logger::LOG_USER_ACTIVED, $userId);  
        // remove entry from the deactivated reasons table
        $this->ClearDeactivationReason($userId);
    }
    
    /**
     * Deactivate a user. If user is already deactivated, this method
     * does nothing.
     * @param int $userId
     * @throws InvalidArgumentException If no user with passed userid exists
     */
    public function DeactivateUser(int $userId)
    {
        // Get the user first
        $user = User::LoadUserById($this, $userId);
        if(!$user)
        {
            throw new InvalidArgumentException('No user with id ' . $userId . 
                    ' was found');
        }
        if(!$user->IsActive())
        {
            return;
        }        
        // Deactivate the user
        $query = 'UPDATE user_table SET active = 0 '
                . 'WHERE iduser = :iduser';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':iduser' => $userId));
        if($stmt->rowCount() !== 1)
        {
            throw new Exception('Not exactly one row was updated in table '
                    . 'user_table matching iduser ' . $userId);
        }
        // There was a modification, create the corresponding log entry
        $logger = new Logger($this);
        $logger->LogMessageWithUserId(Logger::LOG_USER_DEACTIVATED, $userId);
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
    public function SetDeactivationReason(int $userId, string $reason,
            int $deactivatedByUserId)
    {
        // delete any existing entry in the reasons-table
        $delQuery = 'DELETE FROM user_deactivated_reason_table '
                . 'WHERE iduser = :iduser';
        $delStmt = $this->prepare($delQuery);
        $delStmt->execute(array(':iduser' => $userId));
        // and insert the new reason
        $insQuery = 'INSERT INTO user_deactivated_reason_table '
                . '(iduser, deactivated_by_iduser, reason) '
                . 'VALUES(:iduser, :deactivated_by_iduser, :reason)';
        $insStmt = $this->prepare($insQuery);
        $insStmt->execute(array(':iduser' => $userId, 
            ':deactivated_by_iduser' => $deactivatedByUserId, 
            ':reason' => $reason));
    }
    
    /**
     * Makes a user an admin  if that user exists and has confirmed 
     * the email address.
     * If user is already admin, this method does nothing.
     * @param int $userId
     * @throws InvalidArgumentException If no user with passed $userId exists
     * or if the user has no value in the field confirmed_ts
     */
    public function SetAdmin(int $userId)
    {
        // Get the user first
        $user = User::LoadUserById($this, $userId);
        if(!$user)
        {
            throw new InvalidArgumentException('No user with id ' . $userId . 
                    ' was found');
        }
        if(!$user->IsConfirmed())
        {
            throw new InvalidArgumentException('Cannot propage a user '
                    . 'to an admin who '
                    . 'has not confiremd his email address');
        }
        if($user->IsAdmin())
        {
            return;
        }
        $query = 'UPDATE user_table SET admin = 1 '
                . 'WHERE iduser = :iduser';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':iduser' => $userId));
        if($stmt->rowCount() !== 1)
        {
            throw new Exception('Not exactly one row was updated in table '
                    . 'user_table matching iduser ' . $userId);
        }
        $logger = new Logger($this);
        $logger->LogMessageWithUserId(Logger::LOG_USER_ADMIN_SET, $userId);        
    }
    
    /**
     * Remove admin flag from a user if that user exists.
     * If user is already not an admin, this method does nothing.
     * @param int $userId
     * @throws InvalidArgumentException If no user with passed $userId exists
     */
    public function RemoveAdmin(int $userId)
    {
        // Get the user first
        $user = User::LoadUserById($this, $userId);
        if(!$user)
        {
            throw new InvalidArgumentException('No user with id ' . $userId . 
                    ' was found');
        }
        if(!$user->IsAdmin())
        {
            return;
        }
        $query = 'UPDATE user_table SET admin = 0 '
                . 'WHERE iduser = :iduser';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':iduser' => $userId));
        if($stmt->rowCount() !== 1)
        {
            throw new Exception('Not exactly one row was updated in table '
                    . 'user_table matching iduser ' . $userId);
        }
        $logger = new Logger($this);
        $logger->LogMessageWithUserId(Logger::LOG_USER_ADMIN_REMOVED, $userId);        
    }
    
    /**
     * Remove all entries from user_deactivated_reason_table that match
     * the passed $uesrId
     * @param int $userId
     */
    private function ClearDeactivationReason(int $userId)
    {
        $delQuery = 'DELETE FROM user_deactivated_reason_table '
                . 'WHERE iduser = :iduser';
        $delStmt = $this->prepare($delQuery);
        $delStmt->execute(array(':iduser' => $userId));
    }
    
    /**
     * Turns the passed $userId into a dummy, by setting the values for 
     * the fields email, password and old_passwd to null and 
     * confirmation_ts to null. Also sets active and admin to 0 and
     * removes all entries from the user_deactivated_reason_table
     * @param int $userId
     * @throws IllegalArgumentException If no user with passed $userId exists
     */
    public function MakeDummy(int $userId)
    {
        $user = User::LoadUserById($this, $userId);
        if(!$user)
        {
            throw new InvalidArgumentException('No user with id ' . $userId . 
                    ' was found');
        }        
        $query = 'UPDATE user_table SET email = NULL, password = NULL, '
                . 'old_passwd = NULL, confirmation_ts = NULL, '
                . 'active = 0, admin = 0 '
                . 'WHERE iduser = :iduser';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':iduser' => $userId));
        $logger = new Logger($this);
        $logger->LogMessageWithUserId(Logger::LOG_USER_TURNED_INTO_DUMMY, $user->GetId(), 
                'Previous values: email: ' . $user->GetEmail()
                . '; active: ' . ($user->IsActive() ? '1' : '0')
                . '; admin: ' . ($user->IsAdmin() ? '1' : '0')
                . '; confirmation_ts: ' . ($user->IsConfirmed() ? $user->GetConfirmationTimestamp()->format('d.m.Y H:i:s') : 'null') 
                . '; hadPassword: ' . ($user->HasPassword() ? 'True' : 'False')
                . '; hadOldPasswd: ' . ($user->HasOldPassword() ? 'True' : 'False'));
        $this->ClearDeactivationReason($userId);
    }
    
    /**
     * Deletes a user by removing it entirely from the user_table.
     * This method will fail with an InvalidArgumentException if there are
     * already post from that user
     * 
     * @param int $userId
     */
    public function DeleteUser(int $userId)
    {
        if($this->GetPostByUserCount($userId) > 0)
        {
            throw new InvalidArgumentException('Cannot delete user '
                    . $userId . ' there are already entries in post_table '
                    . 'by that user. Want to turn her into a dummy instead?');
        }
        $query = 'DELETE FROM user_table WHERE iduser = :iduser';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':iduser' => $userId));
        $logger = new Logger($this);
        $logger->LogMessage(Logger::LOG_USER_DELETED, 'User ' . $userId . ' has been deleted');
    }
    
    /**
     * Count the number of entries in post_table that have been created
     * using the passed $userId
     * @param int $userId
     * @return type
     * @throws Exception
     */
    public function GetPostByUserCount(int $userId)
    {
        $query = 'SELECT COUNT(idpost) FROM post_table '
                . 'WHERE iduser = :iduser';
        $stmt = $this->prepare($query);
        $stmt->execute(array(':iduser' => $userId));        
        $result = $stmt->fetch(PDO::FETCH_NUM);
        if($result === false)
        {
            throw new Exception('Failed to get GetPostByUserCount');        
        }
        return $result[0];        
    }
    
    /**
     * Update the field hidden of an entry in post_table. If the field 
     * already contains the value it shall be updated too, this method does 
     * nothing.
     * @param int $postId
     * @param bool $show If true, set hidden to 0, else set hidden to 1.
     * @return type
     * @throws InvalidArgumentException If no post with passed $postId 
     * exists
     * @throws Exception
     */
    public function SetPostVisible(int $postId, bool $show = true)
    {
        $post = Post::LoadPost($this, $postId);
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
        // And hide all children
        $childrenQuery = 'SELECT idpost FROM post_table '
                . 'WHERE parent_idpost = :idpost';
        $childrenStmt = $this->prepare($childrenQuery);
        $childrenStmt->execute(array(':idpost' => $postId));
        while($childRow = $childrenStmt->fetch())
        {
            $childPostId = $childRow['idpost'];
            $this->SetPostVisible($childPostId, $show);
        }
        $logger = new Logger($this);
        $logger->LogMessage(($show ? Logger::LOG_POST_SHOW : Logger::LOG_POST_HIDDEN), 'PostId: ' . $postId);
    }
    
    /**
     * Test if passed DateTime is not older than (now - YbForumConfig::CONF_CODE_VALID_PERIOD)
     * @param DateTime $ts
     * @return bool True if DateTime is new than(now - YbForumConfig::CONF_CODE_VALID_PERIOD)
     */
    private function IsDateWithinConfirmPeriod(DateTime $ts)
    {
        $now = new DateTime();
        $codeValidInterval = new DateInterval(YbForumConfig::CONF_CODE_VALID_PERIOD);
        $minDate = $now;
        $minDate->sub($codeValidInterval);
        $inbetween = ($ts > $minDate);
        return $inbetween;
    }
    
    private $m_connected;
}
