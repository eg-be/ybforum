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

require_once __DIR__.'/../helpers/Logger.php';

/**
 * Abstract handler to be used as base for all handlers. Provides methods
 * to syntactically validate arguments that are being read from POST data.
 * 
 * Classes extending from BaseHandler must implement ReadParams(), 
 * ValiateParams() and HandleRequestImpl(ForumDb $db). 
 * If the public HandleRequest(ForumDb $db) of the BaseHandler is called,
 * the BaseHandler will call ReadParams(), ValidateParams() and then 
 * HandleRequestImpl(ForumDb $db). If in any of these methods an InvalidArgumentException
 * occurs, the InvalidArgumentException is stored internally as last exception and then
 * re-thrown. As a general rule the method ReadParams() should not throw, 
 * but simply stored the parameter values for later use or set them to
 * null if the values are invalid: A form working with a handler can
 * re-read the values passed from the user and set them again.
 * Implementing Handlers can store an eventually later required object internally
 * and then provide a corresponding getter for clients.
 *
 * The BaseHandler will always read the client IP address as a parameter.
 * 
 * @author Elias Gerber
 */
abstract class BaseHandler 
{
    final const MSG_INVALID_CLIENT_IPADDRESS = 'Invalid REMOTE_ADDR';
    final const MSG_EMAIL_INVALID = 'Ungültige Mailadresse.';
    final const MSG_HTTPURL_INVALID = 'Ungültige (http(s)) URL.';
    final const MSG_EMAIL_BLACKLISTED = 'Mailadresse ist nicht zugelassen: ';
    
    final const MSG_GENERIC_INVALID = 'Invalid or missing parameter value';
    final const MSGCODE_BAD_PARAM = 400;
    final const MSGCODE_AUTH_FAIL = 401;
    final const MSGCODE_INTERNAL_ERROR = 500;
    
    /**
     * Create a new instance, sets clientIpAddress and lastException to null.
     */
    public function __construct() 
    {
        $this->clientIpAddress = null;
        $this->lastException = null;
    }
    
    /**
     * Reads client IP address from INPUT_SERVER REMOTE_ADDR using 
     * FILTER_VALIDATE_IP.
     * @return string or null if not a valid IP address.
     */
    protected function ReadClientIpParam() :?string
    {
        $clientIp = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP);
        if(!$clientIp)
        {
            return null;
        }
        return $clientIp;
    }
    
    /**
     * Throws InvalidArgumentException with MSG_INVALID_CLIENT_IPADDRESS if
     * $value is not a IP address or null
     * @param ?string $value
     * @throws InvalidArgumentException
     */
    public static function ValidateClientIpValue(?string $value) : void
    {
        if(!$value || filter_var($value, FILTER_VALIDATE_IP) === false)
        {
            throw new InvalidArgumentException(self::MSG_INVALID_CLIENT_IPADDRESS, self::MSGCODE_BAD_PARAM);
        }
    }
    
    /**
     * Reads an email address from INPUT_POST using FILTER_VALIDATE_EMAIL.
     * @param string $paramName
     * @return string or null if value is not a valid email address.
     */
    protected function ReadEmailParam(string $paramName) : ?string
    {
        assert(!empty($paramName));
        $email = trim(filter_input(INPUT_POST, $paramName, FILTER_VALIDATE_EMAIL));
        if(!$email)
        {
            return null;
        }
        return $email;
    }
    
    /**
     * Throw InvalidArgumentException if $value is not an email address.
     * If $errMessage is null, the message for the InvalidArgumentException
     * will be with MSG_EMAIL_INVALID , else it is the passed $errMessage
     * @param ?string $value
     * @param string $errMessage
     * @throws InvalidArgumentException
     */
    public static function ValidateEmailValue(?string $value, ?string $errMessage = null) : void
    {
        if(!$value || filter_var($value, FILTER_VALIDATE_EMAIL) === false)
        {
            if(!$errMessage)
            {
                $errMessage = self::MSG_EMAIL_INVALID;
            }
            throw new InvalidArgumentException($errMessage, self::MSGCODE_BAD_PARAM);            
        }
    }
    
    /**
     * Throws an InvalidArgumentException if passed email matches any blacklist.
     * Creates corresponding log-entry.
     * @param string $email
     * @param ForumDb $db
     * @param Logger $logger
     * @throws InvalidArgumentException
     */
    protected function ValidateEmailAgainstBlacklist(string $email, ForumDb $db, 
            Logger $logger) : void
    {
        $mailOnBlacklistExactly = $db->IsEmailOnBlacklistExactly($email);
        if($mailOnBlacklistExactly)
        {
            $logger->LogMessage(
                    LogType::LOG_OPERATION_FAILED_EMAIL_BLACKLISTED, 
                    $mailOnBlacklistExactly
                    . '(Mail: ' . $email . ')');
            throw new InvalidArgumentException(
                    self::MSG_EMAIL_BLACKLISTED . $mailOnBlacklistExactly,
                    self::MSGCODE_BAD_PARAM);
        }
        $mailMatchesBlacklistRegex = $db->IsEmailOnBlacklistRegex($email);
        if($mailMatchesBlacklistRegex)
        {
            $logger->LogMessage(
                    LogType::LOG_OPERATION_FAILED_EMAIL_REGEX_BLACKLISTED, 
                    $mailMatchesBlacklistRegex 
                    . '(Mail: ' . $email . ')');
            throw new InvalidArgumentException(
                    self::MSG_EMAIL_BLACKLISTED . $mailMatchesBlacklistRegex,
                    self::MSGCODE_BAD_PARAM);            
        }
    }
    
    /**
     * Reads an URL value from INPUT_POST using FILTER_VALIDATE_URL.
     * Note that this does not enforce any protocol (ssh:// would be fine)
     * @param string $paramName
     * @return string or null. 
     */
    protected function ReadUrlParam(string $paramName) : ?string
    {
        assert(!empty($paramName));
        $url = trim(filter_input(INPUT_POST, $paramName, FILTER_VALIDATE_URL));
        if(!$url)
        {
            return null;
        }
        return $url;
    }
    
    /**
     * Throw InvalidArgumentException if $value is not an url address, 
     * and if it does not start with either 'http://' or 'https://'.
     * If $errMessage is null, the message for the InvalidArgumentException
     * will be with MSG_HTTPURL_INVALID , else it is the passed $errMessage
     * @param ?string $value
     * @param string $errMessage
     * @throws InvalidArgumentException
     */
    protected function ValidateHttpUrlValue(?string $value, string $errMessage = null, 
            bool $requirePath = false) : void
    {
        if(!$errMessage)
        {
            $errMessage = self::MSG_HTTPURL_INVALID;
        }
        if(!$value)
        {
            throw new InvalidArgumentException($errMessage, self::MSGCODE_BAD_PARAM);
        }
        if($requirePath && filter_var($value, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED) === false)
        {
            throw new InvalidArgumentException($errMessage, self::MSGCODE_BAD_PARAM);
        }
        else if(filter_var($value, FILTER_VALIDATE_URL) === false)
        {
            throw new InvalidArgumentException($errMessage, self::MSGCODE_BAD_PARAM);
        }
        if(!(strncasecmp($value, 'https://', 8) === 0 || strncasecmp($value, 'http://', 7) === 0))
        {
            throw new InvalidArgumentException($errMessage, self::MSGCODE_BAD_PARAM);
        }
    }    
    
    /**
     * Reads an int value from INPUT_POST using FILTER_VALIDATE_INT.
     * @param string $paramName
     * @return int or null.
     */
    protected function ReadIntParam(string $paramName) : ?int
    {
        assert(!empty($paramName));
        $value = filter_input(INPUT_POST, $paramName, FILTER_VALIDATE_INT);
        if($value === FALSE)
        {
            return null;
        }
        return $value;
    }
    
    /**
     * Throws an InvalidArgumentException with passed $errorMsg if 
     * $value is not an int value.
     * @param type $value
     * @param type $errorMsg
     * @throws InvalidArgumentException
     */
    protected function ValidateIntParam($value, $errorMsg) : void
    {
        assert(!empty($errorMsg));
        if(!is_int($value))
        {
            throw new InvalidArgumentException($errorMsg, self::MSGCODE_BAD_PARAM);            
        }
    }
    
    /**
     * Reads a string from INPUT_POST using FILTER_UNSAFE_RAW
     * @param string $paramName
     * @return string or null if no such parameter exists, or the value is 
     * empty.
     */
    protected function ReadStringParam(string $paramName) : ?string
    {
        assert(!empty($paramName));
        $value = filter_input(INPUT_POST, $paramName, FILTER_UNSAFE_RAW);
        if(!is_null($value))
        {
            $value = trim($value);
        }
        if(!$value)
        {
            return null;
        }
        return $value;
    }
    
    /**
     * Throws an InvalidArgumentException with passed $errorMsg if $value 
     * is not a string, or empty, or if $minLength is set to a value > 0, is 
     * shorted than $minLength
     * @param type $value
     * @param string $errorMsg
     * @param int $minLength
     * @throws InvalidArgumentException
     */
    protected function ValidateStringParam($value, string $errorMsg, int $minLength = 0) : void
    {
        assert(!empty($errorMsg));
        if(!is_string($value) || !$value)
        {
            throw new InvalidArgumentException($errorMsg, self::MSGCODE_BAD_PARAM);
        }
        if($minLength > 0 && mb_strlen($value, 'UTF-8') < $minLength)                
        {
            throw new InvalidArgumentException($errorMsg, self::MSGCODE_BAD_PARAM);            
        }
    }
    
    /**
     * Reads the client IP address, then calls ReadParams() and 
     * ValidateParams(), followed by HandleRequestImpl().
     * If any of the methods throws an InvalidArgumentException, that
     * IllegalArgumentExeption is remembered as member lastException and then
     * re-thrown.
     * If HandleRequestImpl() succeeds, the internal lastException member
     * is cleared.
     * @param ForumDb $db Database.
     * @throws InvalidArgumentException
     */
    public function HandleRequest(ForumDb $db) : void
    {
        try
        {
            // Always need client-ip
            $this->clientIpAddress = $this->ReadClientIpParam();
            
            // First read all values, so they can be written back to the user
            // in case of failue
            $this->ReadParams();
            // and now validate
            self::ValidateClientIpValue($this->clientIpAddress);
            $this->ValidateParams();
            
            // And handle. remember an eventually occuring exception
            $this->HandleRequestImpl($db);
            $this->lastException = null;
        } 
        catch (InvalidArgumentException $ex) 
        {
            $this->lastException = $ex;
            throw $ex;
        }
    }
    
    /**
     * @return boolean True if during last run of HandleRequest an
     * InvalidArgumentException was thrown and that Exception was not cleared
     * using ClearLastException().
     */
    public function HasException() : bool
    {
        return $this->lastException !== null;
    }
    
    /**
     * @return IllegalArgumentExeption or null 
     */
    public function GetLastException() : ?InvalidArgumentException
    {
        return $this->lastException;
    }
    
    /**
     * Sets internal member lastExeption to null
     */
    public function ClearLastException() : void
    {
        $this->lastException = null;
    }
    
    /**
     * Read all parameters required, but avoid throwing an Exception.
     */
    protected abstract function ReadParams() : void;
    
    /**
     * Check that parameters are (syntactically) valid, throw an 
     * InvalidArgumentException if not.
     */
    protected abstract function ValidateParams() : void;
    
    /**
     * Handle the request using the previously read and validated 
     * parameters.
     */
    protected abstract function HandleRequestImpl(ForumDb $db) : void;
    
    protected ?string $clientIpAddress;
    
    protected ?InvalidArgumentException $lastException;
}
