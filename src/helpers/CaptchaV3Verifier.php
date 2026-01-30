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

require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/HttpRequest.php';
require_once __DIR__ . '/CurlHttpRequest.php';
require_once __DIR__ . '/../handlers/BaseHandler.php';

/**
 * A helper to verify a captcha, see https://developers.google.com/recaptcha/
 *
 * Upon construction, tries to read the POST parameter value
 * self::PARAM_CAPTCHA.
 * Call verifyResponse() to issue a request against google to check
 * if the read response is verified.
 * @author eli
 */
class CaptchaV3Verifier
{
    public const PARAM_CAPTCHA = 'g-recaptcha-response';

    public const MSG_GENERIC_INVALID = 'Captcha not verified';
    public const MSGCODE_BAD_PARAM = 400;

    public const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    public const VERIFY_PARAM_NAME_SECRET = 'secret';
    public const VERIFY_PARAM_NAME_RESPONSE = 'response';
    public const VERIFY_PARAM_NAME_REMOTEIP = 'remoteip';

    public function __construct(
        string $captchaSecret,
        float $requiredScore,
        string $action,
        ?HttpRequest $httpRequest = null,
        ?Logger $logger = null
    ) {
        $this->m_captchaSecret = $captchaSecret;
        $this->m_requiredScore = $requiredScore;
        $this->m_action = $action;

        $this->m_clientIp = BaseHandler::readClientIpParam();
        $this->m_captchaResponse = BaseHandler::readStringParam(self::PARAM_CAPTCHA);

        if (is_null($httpRequest)) {
            $this->m_httpRequest = new CurlHttpRequest();
        } else {
            $this->m_httpRequest = $httpRequest;
        }
        if (is_null($logger)) {
            $this->m_logger = new Logger();
        } else {
            $this->m_logger = $logger;
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
    public function verifyResponse(): void
    {
        if (!$this->m_captchaResponse) {
            throw new InvalidArgumentException(self::MSG_GENERIC_INVALID, self::MSGCODE_BAD_PARAM);
        }

        // POST to verify the response:
        $decodedResp = $this->m_httpRequest->postReceiveJson(
            self::VERIFY_URL,
            ([
                self::VERIFY_PARAM_NAME_RESPONSE => $this->m_captchaResponse,
                self::VERIFY_PARAM_NAME_SECRET => $this->m_captchaSecret,
                self::VERIFY_PARAM_NAME_REMOTEIP => $this->m_clientIp])
        );
        if (!$decodedResp) {
            throw new InvalidArgumentException(self::MSG_GENERIC_INVALID, self::MSGCODE_BAD_PARAM);
        }
        if (!$decodedResp['success']) {
            $errcodes = '';
            if (is_array($decodedResp['error-codes'])) {
                $errcodes = implode('', $decodedResp['error-codes']);
            }
            $this->m_logger->logMessage(LogType::LOG_CAPTCHA_TOKEN_INVALID, $errcodes);
            throw new InvalidArgumentException(self::MSG_GENERIC_INVALID, self::MSGCODE_BAD_PARAM);
        }
        if ($decodedResp['action'] !== $this->m_action) {
            $this->m_logger->logMessage(LogType::LOG_CAPTCHA_WRONG_ACTION, 'expected action \'' . $this->m_action . '\' but received \'' . $decodedResp['action'] . '\'');
            throw new InvalidArgumentException(self::MSG_GENERIC_INVALID, self::MSGCODE_BAD_PARAM);
        }
        if ($decodedResp['score'] < $this->m_requiredScore) {
            $this->m_logger->logMessage(LogType::LOG_CAPTCHA_SCORE_TOO_LOW, 'min required ' . $this->m_requiredScore . ', received ' . $decodedResp['score']);
            throw new InvalidArgumentException(self::MSG_GENERIC_INVALID, self::MSGCODE_BAD_PARAM);
        } else {
            $this->m_logger->logMessage(LogType::LOG_CAPTCHA_SCORE_PASSED, 'min required ' . $this->m_requiredScore . ', received ' . $decodedResp['score']);
        }
    }

    public function getCaptchaRespone(): ?string
    {
        return $this->m_captchaResponse;
    }

    public function getClientIp(): string
    {
        return $this->m_clientIp;
    }

    private ?string $m_captchaResponse;
    private string $m_clientIp;
    private string $m_captchaSecret;
    private string $m_action;
    private float $m_requiredScore;

    private HttpRequest $m_httpRequest;
    private Logger $m_logger;
}
