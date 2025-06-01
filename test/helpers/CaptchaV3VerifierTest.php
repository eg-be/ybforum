<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../../src/helpers/CaptchaV3Verifier.php';


/**
 * No Database stuff required
 */
final class CaptchaV3VerifierTest extends TestCase
{
    // required mocks our handler under test depends on
    private Logger $logger;
    private HttpRequest $httpRequest;

    // our actuall CaptchaV3Verifier to test
    private CaptchaV3Verifier $verifier;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->httpRequest = $this->createMock(HttpRequest::class);
        // dont know why we need to set this here, as it is already defined in bootstrap.php
        $_SERVER = array();
        $_SERVER['REMOTE_ADDR'] = '13.13.13.13';
        // must always reset all previously set $_POST and $_GET entries
        $_POST = array();
        $_GET = array();
    }

    public function testConstruct() : void
    {
        $_POST[CaptchaV3Verifier::PARAM_CAPTCHA] = 'captcha-response';
        // ensure param is read if set via post
        $this->verifier = new CaptchaV3Verifier('secret', 0.5, 'the-action', $this->httpRequest, $this->logger);

        $this->assertEquals('13.13.13.13', $this->verifier->GetClientIp());
        $this->assertEquals('captcha-response', $this->verifier->GetCaptchaRespone());
    }

    public function testVerifyResponseFailIfResponseNotSet()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(CaptchaV3Verifier::MSG_GENERIC_INVALID);
        $this->expectExceptionCode(CaptchaV3Verifier::MSGCODE_BAD_PARAM);

        $this->verifier = new CaptchaV3Verifier('secret', 0.5, 'the-action', $this->httpRequest, $this->logger);

        $this->verifier->VerifyResponse();
    }

    public function testVerifyResponseFailIfVerificationFails()
    {
        $_POST[CaptchaV3Verifier::PARAM_CAPTCHA] = 'captcha-response';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(CaptchaV3Verifier::MSG_GENERIC_INVALID);
        $this->expectExceptionCode(CaptchaV3Verifier::MSGCODE_BAD_PARAM);

        $this->httpRequest->method('postReceiveJson')->willReturn(null);

        $this->verifier = new CaptchaV3Verifier('secret', 0.5, 'the-action', $this->httpRequest, $this->logger);

        $this->verifier->VerifyResponse();
    }

    public function testVerifyResponseFailIfVerificationReturnsNoSuccess()
    {
        $_POST[CaptchaV3Verifier::PARAM_CAPTCHA] = 'captcha-response';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(CaptchaV3Verifier::MSG_GENERIC_INVALID);
        $this->expectExceptionCode(CaptchaV3Verifier::MSGCODE_BAD_PARAM);

        $this->httpRequest->method('postReceiveJson')->willReturn(array(
            'success' => false,
            'error-codes' => array('timeout-or-duplicate')
        ));

        $this->logger->expects($this->once())->method('LogMessage')
            ->with(LogType::LOG_CAPTCHA_TOKEN_INVALID, 'timeout-or-duplicate');

        $this->verifier = new CaptchaV3Verifier('secret', 0.5, 'the-action', $this->httpRequest, $this->logger);

        $this->verifier->VerifyResponse();
    }

    public function testVerifyResponseFailIfVerificationIsForDifferentAction()
    {
        $_POST[CaptchaV3Verifier::PARAM_CAPTCHA] = 'captcha-response';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(CaptchaV3Verifier::MSG_GENERIC_INVALID);
        $this->expectExceptionCode(CaptchaV3Verifier::MSGCODE_BAD_PARAM);

        $this->httpRequest->method('postReceiveJson')->willReturn(array(
            'success' => true,
            'challenge_ts' => '2025-06-01T17:08:15Z',
            'hostname' => 'localhost',
            'score' => 0.9,
            'action' => 'another-action'
        ));

        $this->logger->expects($this->once())->method('LogMessage')
            ->with(LogType::LOG_CAPTCHA_WRONG_ACTION, 'expected action \'the-action\' but received \'another-action\'');

        $this->verifier = new CaptchaV3Verifier('secret', 0.5, 'the-action', $this->httpRequest, $this->logger);

        $this->verifier->VerifyResponse();
    }

    public function testVerifyResponseFailIfVerificationScoreIsTooLow()
    {
        $_POST[CaptchaV3Verifier::PARAM_CAPTCHA] = 'captcha-response';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(CaptchaV3Verifier::MSG_GENERIC_INVALID);
        $this->expectExceptionCode(CaptchaV3Verifier::MSGCODE_BAD_PARAM);

        $this->httpRequest->method('postReceiveJson')->willReturn(array(
            'success' => true,
            'challenge_ts' => '2025-06-01T17:08:15Z',
            'hostname' => 'localhost',
            'score' => 0.48,
            'action' => 'the-action'
        ));

        $this->logger->expects($this->once())->method('LogMessage')
            ->with(LogType::LOG_CAPTCHA_SCORE_TOO_LOW, 'min required 0.5, received 0.48');

        $this->verifier = new CaptchaV3Verifier('secret', 0.5, 'the-action', $this->httpRequest, $this->logger);

        $this->verifier->VerifyResponse();
    }

    public function testVerifyResponseSucceedsForMinimalScore()
    {
        $_POST[CaptchaV3Verifier::PARAM_CAPTCHA] = 'captcha-response';

        $this->httpRequest->method('postReceiveJson')->willReturn(array(
            'success' => true,
            'challenge_ts' => '2025-06-01T17:08:15Z',
            'hostname' => 'localhost',
            'score' => 0.5,
            'action' => 'the-action'
        ));

        $this->logger->expects($this->once())->method('LogMessage')
            ->with(LogType::LOG_CAPTCHA_SCORE_PASSED, 'min required 0.5, received 0.5');

        $this->verifier = new CaptchaV3Verifier('secret', 0.5, 'the-action', $this->httpRequest, $this->logger);

        $this->verifier->VerifyResponse();
    }
}