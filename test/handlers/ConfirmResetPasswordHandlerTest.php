<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../../src/handlers/ConfirmResetPasswordHandler.php';

/**
 * No Database stuff required
 */
final class ConfirmResetPasswordHandlerTest extends TestCase
{
    // required mocks our handler under test depends on
    private ForumDb $db;

    private User $user;

    // our actuall handler to test
    private ConfirmResetPasswordHandler $crph;

    protected function setUp(): void
    {
        $this->db = $this->createMock(ForumDb::class);
        $this->user = $this->createMock(User::class);
        $this->crph = new ConfirmResetPasswordHandler();
        // dont know why we need to set this here, as it is already defined in bootstrap.php
        $_SERVER = array();
        $_SERVER['REMOTE_ADDR'] = '13.13.13.13';
        // must always reset all previously set $_POST and $_GET entries
        $_POST = array();
        $_GET = array();
    }

    public function testValidateParams_failWithoutCodeForGet() : void
    {
        // PARAM_CODE must be set, else we must fail wit h an exception
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_POST[ConfirmHandler::PARAM_CODE]);
        unset($_GET[ConfirmHandler::PARAM_CODE]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ConfirmResetPasswordHandler::MSG_CODE_UNKNOWN);
        $this->expectExceptionCode(ConfirmResetPasswordHandler::MSGCODE_BAD_PARAM);

        $this->crph->HandleRequest($this->db);
    }

    public function testValidateParams_failWithoutCodeForPost() : void
    {
        // PARAM_CODE must be set, else we must fail wit h an exception
        $_SERVER['REQUEST_METHOD'] = 'POST';
        unset($_POST[ConfirmHandler::PARAM_CODE]);
        unset($_GET[ConfirmHandler::PARAM_CODE]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ConfirmResetPasswordHandler::MSG_CODE_UNKNOWN);
        $this->expectExceptionCode(ConfirmResetPasswordHandler::MSGCODE_BAD_PARAM);

        $this->crph->HandleRequest($this->db);
    }

    public function testHandleRequest_failForInvalidCode() : void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET[ConfirmHandler::PARAM_CODE] = 'code';

        // Fail returning a userid for the passed code
        $this->db->method('VerifyPasswordResetCode')->willReturn(0);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ConfirmResetPasswordHandler::MSG_CODE_UNKNOWN);
        $this->expectExceptionCode(ConfirmResetPasswordHandler::MSGCODE_BAD_PARAM);

        $this->crph->HandleRequest($this->db);
    }

    public function testHandleRequest_failForNoLongerExistingUser() : void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET[ConfirmHandler::PARAM_CODE] = 'code';

        // Fail returning a user for the userid stored
        $this->db->method('VerifyPasswordResetCode')->willReturn(1313);
        $this->db->method('LoadUserById')->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ConfirmResetPasswordHandler::MSG_CODE_UNKNOWN);
        $this->expectExceptionCode(ConfirmResetPasswordHandler::MSGCODE_BAD_PARAM);

        $this->crph->HandleRequest($this->db);
    }

    public function testGetUser() : void
    {
        // must return null before the request is handled
        $this->assertNull($this->crph->GetUser());

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET[ConfirmHandler::PARAM_CODE] = 'code';

        $this->db->method('VerifyPasswordResetCode')->willReturn(1313);
        $this->db->method('LoadUserById')->willReturn($this->user);

        // and the user afterwards
        $this->crph->HandleRequest($this->db);
        $this->assertEquals($this->user, $this->crph->GetUser());
    }

    public function testGetType() : void
    {
        $this->assertEquals(ConfirmHandler::VALUE_TYPE_RESETPASS, $this->crph->GetType());
    }

    public function testGetCode() : void
    {
        // must return null before the request is handled
        $this->assertNull($this->crph->GetCode());

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET[ConfirmHandler::PARAM_CODE] = 'code';

        $this->db->method('VerifyPasswordResetCode')->willReturn(1313);
        $this->db->method('LoadUserById')->willReturn($this->user);

        // and the user afterwards
        $this->crph->HandleRequest($this->db);
        $this->assertEquals('code', $this->crph->GetCode());        
    }

    public function testGetText() : void
    {
        // must return something non-empty
        $this->assertTrue(strlen($this->crph->GetConfirmText()) > 0);
        $this->assertTrue(strlen($this->crph->GetSuccessText()) > 0);
    }
}