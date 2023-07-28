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

require_once __DIR__.'/Logger.php';

/**
 * A helper to verify a captcha, see https://developers.google.com/recaptcha/
 *
 * Upon construction, tries to read the POST parameter value 
 * self::PARAM_CAPTCHA.
 * Call VerifyResponse() to issue a request against google to check
 * if the read response is verified.
 * @author eli
 */
class CaptchaVerifier {
    
    const PARAM_CAPTCHA = 'g-recaptcha-response';    
    
    const MSG_GENERIC_INVALID = 'Captcha not verified';
    const MSGCODE_BAD_PARAM = 400;    
    
    const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    const VERIFY_PARAM_NAME_SECRET = 'secret';
    const VERIFY_PARAM_NAME_RESPONSE = 'response';
    const VERIFY_PARAM_NAME_REMOTEIP = 'remoteip';
    
    public function __construct(string $captchaSecret)
    {
        $this->m_captchaSecret = $captchaSecret;
        
        $this->m_clientIp = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP);
        $value = trim(filter_input(INPUT_POST, self::PARAM_CAPTCHA, FILTER_UNSAFE_RAW));
        if(!$value)
        {
            $this->m_captchaResponse = null;
        }
        else
        {
            $this->m_captchaResponse = $value;
        }
    }
    
    /**
     * Throws an InvalidArgumentException if the self::PARAM_CAPTCHA value
     * read during construction is not valid (according to google).
     * Issues a HTTP POST request to verify the read value.
     * If no value has been read, the same InvalidArgumentException is thrown,
     * or if the read answer cannot be decoded.
     * @throws InvalidArgumentException
     */
    public function VerifyResponse() : void
    {
        if(!$this->m_captchaResponse)
        {
            throw new InvalidArgumentException(self::MSG_GENERIC_INVALID, self::MSGCODE_BAD_PARAM);
        }
        
        // POST to verify the response:
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::VERIFY_URL);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 
                    http_build_query(array(
                        self::VERIFY_PARAM_NAME_RESPONSE => $this->m_captchaResponse,
                        self::VERIFY_PARAM_NAME_SECRET => $this->m_captchaSecret,
                        self::VERIFY_PARAM_NAME_REMOTEIP => $this->m_clientIp)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $serverResponse = curl_exec ($ch);
        curl_close ($ch);
        
        if(!$serverResponse)
        {
            throw new InvalidArgumentException(self::MSG_GENERIC_INVALID, self::MSGCODE_BAD_PARAM);
        }
        $decodedResp = json_decode($serverResponse, true);
        if(!$decodedResp)
        {
            throw new InvalidArgumentException(self::MSG_GENERIC_INVALID, self::MSGCODE_BAD_PARAM);
        }
        if(!$decodedResp['success'])
        {
            $logger = new Logger();
            $errcodes = '';
            if(is_array($decodedResp['error-codes']))
            {
                $errcodes = implode($decodedResp['error-codes']);
            }
            $logger->LogMessage(Logger::LOG_CAPTCHA_TOKEN_INVALID, $errcodes);
            throw new InvalidArgumentException(self::MSG_GENERIC_INVALID, self::MSGCODE_BAD_PARAM);
        }
    }
    
    private ?string $m_captchaResponse;
    private string $m_clientIp;
    private string $m_captchaSecret;
}
