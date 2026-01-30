<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

require_once __DIR__ . '/../../src/handlers/ConfirmUpdateEmailHandler.php';

/**
 * No Database stuff required
 */
#[AllowMockObjectsWithoutExpectations]
final class ConfirmUpdateEmailHandlerTest extends TestCase
{
    // required mocks our handler under test depends on
    private ForumDb $db;
    private Logger $logger;
    private User $user;

    // our actuall handler to test
    private ConfirmUpdateEmailHandler $cueh;

    protected function setUp(): void
    {
        $this->db = $this->createMock(ForumDb::class);
        $this->logger = $this->createMock(Logger::class);
        $this->user = static::createStub(User::class);
        $this->cueh = new ConfirmUpdateEmailHandler();
        $this->cueh->setLogger($this->logger);
        // dont know why we need to set this here, as it is already defined in bootstrap.php
        $_SERVER = [];
        $_SERVER['REMOTE_ADDR'] = '13.13.13.13';
        // must always reset all previously set $_POST and $_GET entries
        $_POST = [];
        $_GET = [];
    }

    public function testValidateParams_failWithoutCodeForGet(): void
    {
        // PARAM_CODE must be set, else we must fail wit h an exception
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST[ConfirmHandler::PARAM_CODE]);
        unset($_GET[ConfirmHandler::PARAM_CODE]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ConfirmUpdateEmailHandler::MSG_CODE_UNKNOWN);
        $this->expectExceptionCode(ConfirmUpdateEmailHandler::MSGCODE_BAD_PARAM);

        $this->cueh->handleRequest($this->db);
    }

    public function testValidateParams_failWithoutCodeForPost(): void
    {
        // PARAM_CODE must be set, else we must fail wit h an exception
        $_SERVER['REQUEST_METHOD'] = 'POST';
        unset($_POST[ConfirmHandler::PARAM_CODE]);
        unset($_GET[ConfirmHandler::PARAM_CODE]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ConfirmUpdateEmailHandler::MSG_CODE_UNKNOWN);
        $this->expectExceptionCode(ConfirmUpdateEmailHandler::MSGCODE_BAD_PARAM);

        $this->cueh->handleRequest($this->db);
    }

    public function testHandleRequest_failForInvalidCode(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET[ConfirmHandler::PARAM_CODE] = 'code';

        // Fail returning a userid for the passed code
        $this->db->method('VerifyUpdateEmailCode')->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ConfirmUpdateEmailHandler::MSG_CODE_UNKNOWN);
        $this->expectExceptionCode(ConfirmUpdateEmailHandler::MSGCODE_BAD_PARAM);

        // expect that the logger is called with the correct params
        $this->logger->expects($this->once())->method('LogMessage')->with(LogType::LOG_CONFIRM_CODE_FAILED_CODE_INVALID, 'Passed code: code');

        $this->cueh->handleRequest($this->db);
    }

    public function testHandleRequest_failForNoLongerExistingUser(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET[ConfirmHandler::PARAM_CODE] = 'code';

        // Fail returning a user for the userid stored
        $this->db->method('VerifyUpdateEmailCode')->willReturn(['iduser' => 1313, 'email' => 'new@mail.com']);
        $this->db->method('LoadUserById')->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ConfirmUpdateEmailHandler::MSG_CODE_UNKNOWN);
        $this->expectExceptionCode(ConfirmUpdateEmailHandler::MSGCODE_BAD_PARAM);

        // expect that the logger is called with the correct params
        $this->logger->expects($this->once())->method('LogMessage')->with(LogType::LOG_CONFIRM_CODE_FAILED_NO_MATCHING_USER, 'iduser not found : 1313');

        $this->cueh->handleRequest($this->db);
    }

    public function testHandleRequest_failForDummyUser(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET[ConfirmHandler::PARAM_CODE] = 'code';

        // Return a dummy user
        $this->db->method('VerifyUpdateEmailCode')->willReturn(['iduser' => 1313, 'email' => 'new@mail.com']);
        $this->db->method('LoadUserById')->willReturn($this->user);
        $this->user->method('IsDummyUser')->willReturn(true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ConfirmUpdateEmailHandler::MSG_DUMMY_USER);
        $this->expectExceptionCode(ConfirmUpdateEmailHandler::MSGCODE_BAD_PARAM);

        // expect that the logger is called with the correct params
        $this->logger->expects($this->once())->method('LogMessageWithUserId')->with(LogType::LOG_OPERATION_FAILED_USER_IS_DUMMY, $this->user);

        $this->cueh->handleRequest($this->db);
    }

    public function testHandleRequest_dontDoAnythingInSimulationMode(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET[ConfirmHandler::PARAM_CODE] = 'code';

        // Return a valid user
        $this->db->method('VerifyUpdateEmailCode')->willReturn(['iduser' => 1313, 'email' => 'new@mail.com']);
        $this->db->method('LoadUserById')->willReturn($this->user);
        $this->user->method('IsDummyUser')->willReturn(false);

        // method to actually update must not be called
        $this->db->expects($this->never())->method('UpdateUserEmail');

        $this->cueh->handleRequest($this->db);

        // but property must have been update
        static::assertEquals('new@mail.com', $this->cueh->getNewEmail());
    }

    public function testHandleRequest_updateEmail(): void
    {
        // if not in simulation mode, we want to update things
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST[ConfirmHandler::PARAM_CODE] = 'code';

        // Return a valid user
        $this->db->method('VerifyUpdateEmailCode')->willReturn(['iduser' => 1313, 'email' => 'new@mail.com']);
        $this->db->method('LoadUserById')->willReturn($this->user);
        $this->user->method('IsDummyUser')->willReturn(false);

        // method to actually update must be called
        $this->db->expects($this->once())->method('UpdateUserEmail');

        $this->cueh->handleRequest($this->db);

        // and property must have been update
        static::assertEquals('new@mail.com', $this->cueh->getNewEmail());
        static::assertEquals('code', $this->cueh->getCode());

        // must return something non-empty
        static::assertTrue(strlen($this->cueh->getConfirmText()) > 0);
        static::assertTrue(strlen($this->cueh->getSuccessText()) > 0);
    }

    public function testgetType(): void
    {
        static::assertEquals(ConfirmHandler::VALUE_TYPE_UPDATEEMAIL, $this->cueh->getType());
    }
}
