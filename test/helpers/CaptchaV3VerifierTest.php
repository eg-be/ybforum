<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

require_once __DIR__ . '/../../src/helpers/CaptchaV3Verifier.php';


/**
 * No Database stuff required
 */
#[AllowMockObjectsWithoutExpectations]
final class CaptchaV3VerifierTest extends TestCase
{
    // required stubs our handler under test depends on
    private Logger $logger;
    private HttpRequest $httpRequest;

    // our actuall CaptchaV3Verifier to test
    private CaptchaV3Verifier $verifier;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->httpRequest = static::createStub(HttpRequest::class);
        // dont know why we need to set this here, as it is already defined in bootstrap.php
        $_SERVER = [];
        $_SERVER['REMOTE_ADDR'] = '13.13.13.13';
        // must always reset all previously set $_POST and $_GET entries
        $_POST = [];
        $_GET = [];
    }

    public function testConstruct(): void
    {
        $_POST[CaptchaV3Verifier::PARAM_CAPTCHA] = 'captcha-response';
        // ensure param is read if set via post
        $this->verifier = new CaptchaV3Verifier('secret', 0.5, 'the-action', $this->httpRequest, $this->logger);

        static::assertEquals('13.13.13.13', $this->verifier->getClientIp());
        static::assertEquals('captcha-response', $this->verifier->getCaptchaRespone());
    }

    public function testVerifyResponseFailIfResponseNotSet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(CaptchaV3Verifier::MSG_GENERIC_INVALID);
        $this->expectExceptionCode(CaptchaV3Verifier::MSGCODE_BAD_PARAM);

        $this->verifier = new CaptchaV3Verifier('secret', 0.5, 'the-action', $this->httpRequest, $this->logger);

        $this->verifier->verifyResponse();
    }

    public function testVerifyResponseFailIfVerificationFails(): void
    {
        $_POST[CaptchaV3Verifier::PARAM_CAPTCHA] = 'captcha-response';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(CaptchaV3Verifier::MSG_GENERIC_INVALID);
        $this->expectExceptionCode(CaptchaV3Verifier::MSGCODE_BAD_PARAM);

        $this->httpRequest->method('postReceiveJson')->willReturn(null);

        $this->verifier = new CaptchaV3Verifier('secret', 0.5, 'the-action', $this->httpRequest, $this->logger);

        $this->verifier->verifyResponse();
    }

    public function testVerifyResponseFailIfVerificationReturnsNoSuccess(): void
    {
        $_POST[CaptchaV3Verifier::PARAM_CAPTCHA] = 'captcha-response';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(CaptchaV3Verifier::MSG_GENERIC_INVALID);
        $this->expectExceptionCode(CaptchaV3Verifier::MSGCODE_BAD_PARAM);

        $this->httpRequest->method('postReceiveJson')->willReturn([
            'success' => false,
            'error-codes' => ['timeout-or-duplicate'],
        ]);

        $this->logger->expects($this->once())->method('LogMessage')
            ->with(LogType::LOG_CAPTCHA_TOKEN_INVALID, 'timeout-or-duplicate');

        $this->verifier = new CaptchaV3Verifier('secret', 0.5, 'the-action', $this->httpRequest, $this->logger);

        $this->verifier->verifyResponse();
    }

    public function testVerifyResponseFailIfVerificationIsForDifferentAction(): void
    {
        $_POST[CaptchaV3Verifier::PARAM_CAPTCHA] = 'captcha-response';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(CaptchaV3Verifier::MSG_GENERIC_INVALID);
        $this->expectExceptionCode(CaptchaV3Verifier::MSGCODE_BAD_PARAM);

        $this->httpRequest->method('postReceiveJson')->willReturn([
            'success' => true,
            'challenge_ts' => '2025-06-01T17:08:15Z',
            'hostname' => 'localhost',
            'score' => 0.9,
            'action' => 'another-action',
        ]);

        $this->logger->expects($this->once())->method('LogMessage')
            ->with(LogType::LOG_CAPTCHA_WRONG_ACTION, 'expected action \'the-action\' but received \'another-action\'');

        $this->verifier = new CaptchaV3Verifier('secret', 0.5, 'the-action', $this->httpRequest, $this->logger);

        $this->verifier->verifyResponse();
    }

    public function testVerifyResponseFailIfVerificationScoreIsTooLow(): void
    {
        $_POST[CaptchaV3Verifier::PARAM_CAPTCHA] = 'captcha-response';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(CaptchaV3Verifier::MSG_GENERIC_INVALID);
        $this->expectExceptionCode(CaptchaV3Verifier::MSGCODE_BAD_PARAM);

        $this->httpRequest->method('postReceiveJson')->willReturn([
            'success' => true,
            'challenge_ts' => '2025-06-01T17:08:15Z',
            'hostname' => 'localhost',
            'score' => 0.48,
            'action' => 'the-action',
        ]);

        $this->logger->expects($this->once())->method('LogMessage')
            ->with(LogType::LOG_CAPTCHA_SCORE_TOO_LOW, 'min required 0.5, received 0.48');

        $this->verifier = new CaptchaV3Verifier('secret', 0.5, 'the-action', $this->httpRequest, $this->logger);

        $this->verifier->verifyResponse();
    }

    public function testVerifyResponseSucceedsForMinimalScore(): void
    {
        $_POST[CaptchaV3Verifier::PARAM_CAPTCHA] = 'captcha-response';

        $this->httpRequest->method('postReceiveJson')->willReturn([
            'success' => true,
            'challenge_ts' => '2025-06-01T17:08:15Z',
            'hostname' => 'localhost',
            'score' => 0.5,
            'action' => 'the-action',
        ]);

        $this->logger->expects($this->once())->method('LogMessage')
            ->with(LogType::LOG_CAPTCHA_SCORE_PASSED, 'min required 0.5, received 0.5');

        $this->verifier = new CaptchaV3Verifier('secret', 0.5, 'the-action', $this->httpRequest, $this->logger);

        $this->verifier->verifyResponse();
    }
}
