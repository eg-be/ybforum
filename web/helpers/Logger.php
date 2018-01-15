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

require_once __DIR__.'/../model/ForumDb.php';

/**
 * Helper to do all log-handling
 *
 * @author Elias Gerber
 */
class Logger 
{
    // Auth logs
    const LOG_AUTH_FAILED_NO_SUCH_USER = 'AuthFailedNoSuchUser';
    const LOG_AUTH_FAILED_USER_IS_DUMMY = 'AuthFailedUserIsDummy';
    const LOG_AUTH_FAILED_PASSWORD_INVALID = 'AuthFailedPassInvalid';
    const LOG_AUTH_FAILED_USER_INACTIVE = 'AuthFailedUserInactive';
    const LOG_AUTH_FAILED_USING_OLD_PASSWORD = 'AuthFailedOldPassInvalid';
    const LOG_AUTH_USING_OLD_PASSWORD = 'AuthUsingOldPassword';

    // Generic operations that fail
    const LOG_OPERATION_FAILED_MIGRATION_REQUIRED = 'OperationFailedMigrationRequired';
    const LOG_OPERATION_FAILED_USER_IS_DUMMY = 'OperationFailedUserIsDummy';
    const LOG_OPERATION_FAILED_ALREADY_MIGRATED = 'OperationFailedAlreadyMigrated';
    const LOG_OPERATION_FAILED_ALREADY_CONFIRMED = 'OperationFailedAlreadyConfirmed';
    const LOG_OPERATION_FAILED_EMAIL_NOT_UNIQUE = 'OperationFailedEmailNotUnique';
    const LOG_OPERATION_FAILED_NICK_NOT_UNIQUE = 'OperationFailedNickNotUnique';
    const LOG_OPERATION_FAILED_NO_MATCHING_NICK_OR_EMAIL = 'OperationFailedNoMatchingNickOrEmail';
    const LOG_OPERATION_FAILED_USER_HAS_NO_EMAIL = 'OperationFailedUserHasNoEmail';
    const LOG_OPERATION_FAILED_USER_IS_INACTIVE = 'OperationFailedUserIsInactive';
    
    // confirmation codes requested and created with success
    const LOG_CONFIRM_MIGRATION_CODE_CREATED = 'ConfirmMigrationCodeCreated';
    const LOG_CONFIRM_REGISTRATION_CODE_CREATED = 'ConfirmRegistrationCodeCreated';
    const LOG_PASS_RESET_CODE_CREATED = 'ConfirmResetPasswordCodeCreated';
    const LOG_CONFIRM_EMAIL_CODE_CREATED = 'ConfirmEmailCodeCreated';
    
    // confirm code failures
    const LOG_CONFIRM_CODE_FAILED_CODE_INVALID = 'ConfirmFailedCodeInvalid';
    const LOG_CONFIRM_CODE_FAILED_NO_MATCHING_USER = 'ConfirmFailedNoMatchingUser';
    
    // user modified with success
    const LOG_USER_PASSWORD_UPDATED = 'UserPasswordUpdated';
    const LOG_USER_EMAIL_UPDATED = 'UserEmailUpdated';
    const LOG_USER_ACTIVED = 'UserActived';
    const LOG_USER_DEACTIVATED = 'UserDeactivated';
    const LOG_USER_MIGRATION_CONFIRMED = 'UserMigrationConfirmed';
    const LOG_USER_REGISTRATION_CONFIRMED = 'UserRegistrationConfirmed';
    const LOG_USER_ADMIN_SET = 'UserAdminSet';
    const LOG_USER_ADMIN_REMOVED = 'UserAdminRemoved';
    const LOG_USER_ACCECPTED = 'UserAccepted';
    const LOG_USER_CREATED = 'UserCreated';
    const LOG_USER_DELETED = 'UserDeleted';
    const LOG_USER_TURNED_INTO_DUMMY = 'UserTurnedIntoDummy';
    
    // notifications sent not related to confirm code
    const LOG_NOTIFIED_USER_ACCEPTED = 'NotifiedUserAccepted';
    const LOG_NOTIFIED_USER_DENIED = 'NotifiedUserDenied';
    const LOG_NOTIFIED_ADMIN_USER_REGISTRATION_CONFIRMED = 'NotifiedAdminUserConfiremdRegistration';
    
    // stammposter
    const LOG_STAMMPOSTER_LOGIN = 'StammposterLogin';
    
    // admin functions
    const LOG_ADMIN_LOGIN = 'AdminLogin';
    const LOG_ADMIN_LOGIN_FAILED_USER_IS_NOT_ADMIN = 'AdminLoginFailedUserIsNoAdmin';
    
    // post modifications
    const LOG_POST_HIDDEN = 'PostHidden';
    const LOG_POST_SHOW = 'PostShow';
    
    // generic mailing failure
    const LOG_MAIL_FAILED = 'MailFailed';
    
    // Fatal errors
    const LOG_ERROR_EXCEPTION_THROWN =  'ErrorExceptionThrown';
    
    public function __construct(ForumDb $db = null)
    {
        if(!$db)
        {
            $db = new ForumDb();
        }
        $this->m_db = $db;
        // Init stmts with null, create them when needed
        $this->m_selectTypeStmt = null;
        $this->m_insertLogEntryStmt = null;
    }

    public function LogMessageWithUserId(string $logType, int $userId, string $msg = null)
    {
        $logTypeId = $this->GetLogTypeId($logType);
        $this->InsertLogEntry($logTypeId, $userId, $msg);
    }
    
    public function LogMessage(string $logType, string $msg)
    {
        $logTypeId = $this->GetLogTypeId($logType);
        $this->InsertLogEntry($logTypeId, null, $msg);
    }
    
    private function InsertLogEntry(int $logTypeId, int $userId = null, string $msg = null)
    {
        if(!$this->m_insertLogEntryStmt)
        {
            $query = 'INSERT INTO log_table (idlog_type, iduser, message, '
                    . 'request_uri, ip_address, admin_iduser) '
                    . 'VALUES(:idlog_type, :iduser, :message, '
                    . ':request_uri, :ip_address, :admin_iduser)';
            $this->m_insertLogEntryStmt = $this->m_db->prepare($query);
        }
        
        $clientIp = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP);
        $requestUri = filter_input(INPUT_SERVER, 'REQUEST_URI');
        
        $adminIdUser = null;
        if(isset($_SESSION['adminuserid']))
        {
            $adminIdUser = $_SESSION['adminuserid'];
        }
        
        $this->m_insertLogEntryStmt->execute(array(
            ':idlog_type' => $logTypeId,
            ':iduser' => $userId,
            ':message' => $msg,
            ':request_uri' => $requestUri,
            ':ip_address' => $clientIp,
            ':admin_iduser' => $adminIdUser
        ));
        
        if($this->m_insertLogEntryStmt->rowCount() !== 1)
        {
            throw new Exception('Failed to insert log entry into log_table');
        }
    }
    
    private function GetLogTypeId(string $logType)
    {
        if(!$this->m_selectTypeStmt)
        {
            $query = 'SELECT idlog_type FROM log_type_table WHERE name = :name';
            $this->m_selectTypeStmt = $this->m_db->prepare($query);
        }
        $this->m_selectTypeStmt->execute(array(
            ':name' => $logType
        ));
        $row = $this->m_selectTypeStmt->fetch();
        if(!$row)
        {
            throw new InvalidArgumentException('LogType ' . $logType . ' is not '
                    . 'defined in log_type_table');
        }
        return $row['idlog_type'];
    }
    
    private $m_selectTypeStmt;
    private $m_insertLogEntryStmt;
    
    private $m_db;
}
