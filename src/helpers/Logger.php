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
require_once __DIR__.'/../model/User.php';

enum LogType : string {
    
    // Auth logs
    case LOG_AUTH_FAILED_NO_SUCH_USER = 'AuthFailedNoSuchUser';
    case LOG_AUTH_FAILED_USER_IS_DUMMY = 'AuthFailedUserIsDummy';
    case LOG_AUTH_FAILED_PASSWORD_INVALID = 'AuthFailedPassInvalid';
    case LOG_AUTH_FAILED_USER_INACTIVE = 'AuthFailedUserInactive';
    case LOG_AUTH_FAILED_USING_OLD_PASSWORD = 'AuthFailedOldPassInvalid';
    case LOG_AUTH_USING_OLD_PASSWORD = 'AuthUsingOldPassword';

    // Generic operations that fail
    case LOG_OPERATION_FAILED_MIGRATION_REQUIRED = 'OperationFailedMigrationRequired';
    case LOG_OPERATION_FAILED_USER_IS_DUMMY = 'OperationFailedUserIsDummy';
    case LOG_OPERATION_FAILED_ALREADY_MIGRATED = 'OperationFailedAlreadyMigrated';
    case LOG_OPERATION_FAILED_ALREADY_CONFIRMED = 'OperationFailedAlreadyConfirmed';
    case LOG_OPERATION_FAILED_EMAIL_NOT_UNIQUE = 'OperationFailedEmailNotUnique';
    case LOG_OPERATION_FAILED_NICK_NOT_UNIQUE = 'OperationFailedNickNotUnique';
    case LOG_OPERATION_FAILED_NO_MATCHING_NICK_OR_EMAIL = 'OperationFailedNoMatchingNickOrEmail';
    case LOG_OPERATION_FAILED_USER_HAS_NO_EMAIL = 'OperationFailedUserHasNoEmail';
    case LOG_OPERATION_FAILED_USER_IS_INACTIVE = 'OperationFailedUserIsInactive';
    case LOG_OPERATION_FAILED_EMAIL_BLACKLISTED = 'OperationFailedEmailBlacklisted';
    case LOG_OPERATION_FAILED_EMAIL_REGEX_BLACKLISTED = 'OperationFailedEmailRegexBlacklisted';
    
    // confirmation codes requested and created with success
    case LOG_CONFIRM_MIGRATION_CODE_CREATED = 'ConfirmMigrationCodeCreated';
    case LOG_CONFIRM_REGISTRATION_CODE_CREATED = 'ConfirmRegistrationCodeCreated';
    case LOG_PASS_RESET_CODE_CREATED = 'ConfirmResetPasswordCodeCreated';
    case LOG_CONFIRM_EMAIL_CODE_CREATED = 'ConfirmEmailCodeCreated';
    
    // confirm code failures
    case LOG_CONFIRM_CODE_FAILED_CODE_INVALID = 'ConfirmFailedCodeInvalid';
    case LOG_CONFIRM_CODE_FAILED_NO_MATCHING_USER = 'ConfirmFailedNoMatchingUser';
    case LOG_CONFIRM_REQUEST_IGNORED_IS_PREVIEW = 'ConfirmRequestIgnoredIsPreview';
    
    // user modified with success
    case LOG_USER_PASSWORD_UPDATED = 'UserPasswordUpdated';
    case LOG_USER_EMAIL_UPDATED = 'UserEmailUpdated';
    case LOG_USER_ACTIVED = 'UserActived';
    case LOG_USER_DEACTIVATED = 'UserDeactivated';
    case LOG_USER_MIGRATION_CONFIRMED = 'UserMigrationConfirmed';
    case LOG_USER_REGISTRATION_CONFIRMED = 'UserRegistrationConfirmed';
    case LOG_USER_ADMIN_SET = 'UserAdminSet';
    case LOG_USER_ADMIN_REMOVED = 'UserAdminRemoved';
    case LOG_USER_ACCECPTED = 'UserAccepted';
    case LOG_USER_CREATED = 'UserCreated';
    case LOG_USER_DELETED = 'UserDeleted';
    case LOG_USER_TURNED_INTO_DUMMY = 'UserTurnedIntoDummy';
    
    // notifications sent not related to confirm code
    case LOG_NOTIFIED_USER_ACCEPTED = 'NotifiedUserAccepted';
    case LOG_NOTIFIED_USER_DENIED = 'NotifiedUserDenied';
    case LOG_NOTIFIED_ADMIN_USER_REGISTRATION_CONFIRMED = 'NotifiedAdminUserConfiremdRegistration';
    
    // stammposter
    case LOG_STAMMPOSTER_LOGIN = 'StammposterLogin';
    
    // admin functions
    case LOG_ADMIN_LOGIN = 'AdminLogin';
    case LOG_ADMIN_LOGIN_FAILED_USER_IS_NOT_ADMIN = 'AdminLoginFailedUserIsNoAdmin';
    
    // post modifications
    case LOG_POST_HIDDEN = 'PostHidden';
    case LOG_POST_SHOW = 'PostShow';
    
    // generic mailing failure
    case LOG_MAIL_FAILED = 'MailFailed';
    case LOG_MAIL_SENT = 'MailSent';
    
    // captcha
    case LOG_CAPTCHA_TOKEN_INVALID = 'CaptchaTokenInvalid';
    case LOG_CAPTCHA_SCORE_PASSED = 'CaptchaScorePassed';
    case LOG_CAPTCHA_SCORE_TOO_LOW = 'CaptchaScoreTooLow';
    case LOG_CAPTCHA_WRONG_ACTION = 'CaptchaWrongAction';

    // contact
    case LOG_CONTACT_FORM_SUBMITTED = 'ContactFormSubmitted';

    // Blacklist
    case LOG_BLACKLIST_EMAIL_ADDED = 'BlacklistEmailAdded';
    
    // Extended log
    case LOG_EXT_POST_DISCARDED = 'ExtLogPostDiscarded';
    
    // Fatal errors
    case LOG_ERROR_EXCEPTION_THROWN =  'ErrorExceptionThrown';
}

/**
 * Helper to do all log-handling
 *
 * @author Elias Gerber
 */
class Logger 
{  
    public function __construct(ForumDb $db = null)
    {
        if(!$db || $db->IsReadOnly())
        {
            $db = new ForumDb(false);
        }
        $this->m_db = $db;
        // Init stmts with null, create them when needed
        $this->m_selectTypeStmt = null;
        $this->m_insertLogEntryStmt = null;
        $this->m_insertExtendedInfoStmt = null;

        // we must always have an ip and a request_uri
        $values = filter_var_array($_SERVER, array(
            'REMOTE_ADDR' => FILTER_VALIDATE_IP,
            'REQUEST_URI' => FILTER_SANITIZE_STRING
        ), true);
        $this->m_clientIp = $values['REMOTE_ADDR'];
        $this->m_requestUri = $values['REQUEST_URI'];
    }

    public function LogMessageWithUserId(LogType $logType, int $userId, 
            string $msg = null, string $extendedInfo = null) : void
    {
        $logTypeId = $this->GetLogTypeId($logType);
        $this->InsertLogEntry($logTypeId, $userId, $msg, $extendedInfo);
    }
    
    public function LogMessage(LogType $logType, string $msg, 
            string $extendedInfo = null) : void
    {
        $logTypeId = $this->GetLogTypeId($logType);
        $this->InsertLogEntry($logTypeId, null, $msg, $extendedInfo);
    }
    
    /**
     * Build a string containing userId, nick, email,
     * active, confirmed and need migration info.
     * @param int $userId
     * @return string
     */
    private function GetHistoricUserContext(int $userId) : string
    {
        $context = '';
        $user = User::LoadUserById($this->m_db, $userId);
        if($user)
        {
            $context.= $user->GetMinimalUserInfoAsString();
        }
        else
        {
            $context.= ' <Benutzer ' . $userId . 'existiert nicht in der Datenbank>';
        }
        return $context;
    }
    
    /**
     * Log a message to the log_table
     * @param int $logTypeId id of a log_type_table entry
     * @param int $userId If not null, the iduser of an existing entry 
     * from user_table
     * @param string $msg The value for the message field
     * @param string $extendedInfo If not null, an antry in log_extended_info
     * is created with the passed value
     * @throws Exception
     */
    private function InsertLogEntry(int $logTypeId, int $userId = null, 
            string $msg = null, string $extendedInfo = null) : void
    {
        if(!$this->m_insertLogEntryStmt)
        {
            $query = 'INSERT INTO log_table (idlog_type, iduser, '
                    . 'historic_user_context, message, '
                    . 'request_uri, ip_address, admin_iduser) '
                    . 'VALUES(:idlog_type, :iduser, '
                    . ':historic_user_context, :message, '
                    . ':request_uri, :ip_address, :admin_iduser)';
            $this->m_insertLogEntryStmt = $this->m_db->prepare($query);
        }
                
        $adminIdUser = null;
        if(isset($_SESSION['adminuserid']))
        {
            $adminIdUser = $_SESSION['adminuserid'];
        }
        
        $historicUserContext = null;
        if($userId)
        {
            $historicUserContext = $this->GetHistoricUserContext($userId);
        }
        
        $this->m_insertLogEntryStmt->execute(array(
            ':idlog_type' => $logTypeId,
            ':iduser' => $userId,
            ':historic_user_context' => $historicUserContext,
            ':message' => $msg,
            ':request_uri' => $this->m_requestUri,
            ':ip_address' => $this->m_clientIp,
            ':admin_iduser' => $adminIdUser
        ));
        
        if($this->m_insertLogEntryStmt->rowCount() !== 1)
        {
            throw new Exception('Failed to insert log entry into log_table');
        }
        
        if($extendedInfo)
        {
            $logId = $this->m_db->lastInsertId();
            if($logId <= 0)
            {
                throw new Exception('Failed to get idlog of newly created entry');                
            }
            if(!$this->m_insertExtendedInfoStmt)
            {
                $queryExtend = 'INSERT INTO log_extended_info '
                        . '(idlog, info) '
                        . 'VALUES(:idlog, :info)';
                $this->m_insertExtendedInfoStmt = $this->m_db->prepare($queryExtend);
            }
            $this->m_insertExtendedInfoStmt->execute(array(
                ':idlog' => $logId,
                ':info' => $extendedInfo
            ));
        }
    }
    
    /**
     * Lookup an entry in log_type_table where name matches the passed string
     * @param LogType $logType value for column name
     * @return int value of column idlog_type
     * @throws InvalidArgumentException If no matching row is found
     */
    public function GetLogTypeId(LogType $logType) : int
    {
        if(!$this->m_selectTypeStmt)
        {
            $query = 'SELECT idlog_type FROM log_type_table WHERE name = :name';
            $this->m_selectTypeStmt = $this->m_db->prepare($query);
        }
        $this->m_selectTypeStmt->execute(array(
            ':name' => $logType->value
        ));
        $row = $this->m_selectTypeStmt->fetch();
        if(!$row)
        {
            throw new InvalidArgumentException('LogType ' . $logType->value . ' is not '
                    . 'defined in log_type_table');
        }
        return $row['idlog_type'];
    }
    
    private ?PDOStatement $m_selectTypeStmt;
    private ?PDOStatement $m_insertLogEntryStmt;
    private ?PDOStatement $m_insertExtendedInfoStmt;
    
    private string $m_clientIp;
    private string $m_requestUri;

    private ForumDb $m_db;
}
