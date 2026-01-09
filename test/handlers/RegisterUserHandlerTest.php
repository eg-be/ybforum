<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

require_once __DIR__ . '/../../src/handlers/RegisterUserHandler.php';

/**
 * No Database stuff required
 */
#[AllowMockObjectsWithoutExpectations]
final class RegisterUserHandlerTest extends TestCase
{
    // required mocks our handler under test depends on
    private ForumDb $db;
    private Mailer $mailer;
    private Logger $logger;

    // our actuall handler to test
    private RegisterUserHandler $ruh;

    protected function setUp(): void
    {
        $this->db = $this->createMock(ForumDb::class);
        $this->mailer = $this->createMock(Mailer::class);
        $this->logger = $this->createMock(Logger::class);
        $this->ruh = new RegisterUserHandler();
        $this->ruh->SetMailer($this->mailer);
        $this->ruh->SetLogger($this->logger);
        // dont know why we need to set this here, as it is already defined in bootstrap.php
        $_SERVER['REMOTE_ADDR'] = '13.13.13.13';
        $_SERVER['REQUEST_URI'] = 'phpunit';
        // must always reset all previously set $_POST entries
        $_POST = [];
    }

    public function testConstruct(): void
    {
        static::assertNull($this->ruh->GetNick());
        static::assertNull($this->ruh->GetEmail());
        static::assertNull($this->ruh->GetRegMsg());
        static::assertNull($this->ruh->GetPassword());
        static::assertNull($this->ruh->GetConfirmPassword());
    }

    public static function providerTestValidateRequiredParams(): array
    {
        return [
            // NICK     // EMAIL        // PASS         // FAILURE
            [null,      'foo@bar.com',  '12345678',     RegisterUserHandler::MSG_NICK_TOO_SHORT], // nick not set
            ['foob',    'foo@bar.com',  '12345678',     RegisterUserHandler::MSG_NICK_TOO_SHORT], // nick too short
            ['foobar',  null,           '12345678',     RegisterUserHandler::MSG_EMAIL_INVALID],    // mail not set
            ['foobar',  'foo@bar.com',  null,           RegisterUserHandler::MSG_PASSWORD_TOO_SHORT],    // pass not set
            ['foobar',  'foo@bar.com',  '1234567',      RegisterUserHandler::MSG_PASSWORD_TOO_SHORT],    // pass too short
        ];
    }

    #[DataProvider('providerTestValidateRequiredParams')]
    public function testValidateRequiredParams(?string $nick, ?string $email, ?string $password, string $failMessage): void
    {
        // test that we fail if required params are not set
        $_POST[RegisterUserHandler::PARAM_NICK] = $nick;
        $_POST[RegisterUserHandler::PARAM_EMAIL] = $email;
        $_POST[RegisterUserHandler::PARAM_PASS] = $password;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($failMessage);
        $this->expectExceptionCode(RegisterUserHandler::MSGCODE_BAD_PARAM);
        $this->ruh->HandleRequest($this->db);
    }

    public function testPasswordsMustMatch(): void
    {
        $_POST[RegisterUserHandler::PARAM_NICK] = 'nickname';
        $_POST[RegisterUserHandler::PARAM_EMAIL] = 'a@bar.com';
        $_POST[RegisterUserHandler::PARAM_PASS] = 'password';
        $_POST[RegisterUserHandler::PARAM_CONFIRMPASS] = 'passwOrd';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(RegisterUserHandler::MSG_PASSWORDS_NOT_MATCH);
        $this->expectExceptionCode(RegisterUserHandler::MSGCODE_BAD_PARAM);
        $this->ruh->HandleRequest($this->db);
    }

    public function testRegisterUser_nickNotUnique(): void
    {
        $_POST[RegisterUserHandler::PARAM_NICK] = 'nickname';
        $_POST[RegisterUserHandler::PARAM_EMAIL] = 'a@bar.com';
        $_POST[RegisterUserHandler::PARAM_PASS] = 'password';
        $_POST[RegisterUserHandler::PARAM_CONFIRMPASS] = 'password';

        $user = $this->createMock(User::class);
        $this->db->method('LoadUserByNick')->with('nickname')->willReturn($user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(RegisterUserHandler::MSG_NICK_NOT_UNIQUE);
        $this->expectExceptionCode(RegisterUserHandler::MSGCODE_BAD_PARAM);
        $this->ruh->HandleRequest($this->db);
    }

    public function testRegisterUser_emailNotUnique(): void
    {
        $_POST[RegisterUserHandler::PARAM_NICK] = 'nickname';
        $_POST[RegisterUserHandler::PARAM_EMAIL] = 'a@bar.com';
        $_POST[RegisterUserHandler::PARAM_PASS] = 'password';
        $_POST[RegisterUserHandler::PARAM_CONFIRMPASS] = 'password';

        $user = $this->createMock(User::class);
        $this->db->method('LoadUserByEmail')->with('a@bar.com')->willReturn($user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(RegisterUserHandler::MSG_EMAIL_NOT_UNIQUE);
        $this->expectExceptionCode(RegisterUserHandler::MSGCODE_BAD_PARAM);
        $this->ruh->HandleRequest($this->db);
    }

    public function testRegisterUser_emailTestedForBlacklist(): void
    {
        // todo: fixme, testing would be easier if we use composition over inheritance
        // especially for these static helper methods from the base-class that are called from inside this
        static::markTestSkipped('todo');
    }

    public function testRegisterUser_userCreatedConfirmCodeSent(): void
    {
        $_POST[RegisterUserHandler::PARAM_NICK] = 'nickname';
        $_POST[RegisterUserHandler::PARAM_EMAIL] = 'a@bar.com';
        $_POST[RegisterUserHandler::PARAM_PASS] = 'password';
        $_POST[RegisterUserHandler::PARAM_CONFIRMPASS] = 'password';

        $user = $this->createMock(User::class);
        $this->db->method('CreateNewUser')->with('nickname', 'a@bar.com')->willReturn($user);
        $this->db->method('RequestConfirmUserCode')->with($user, 'password', 'a@bar.com')->willReturn('the-confirm-code');
        $this->mailer->method('SendRegisterUserConfirmMessage')->with('a@bar.com', 'nickname', 'the-confirm-code')->willReturn(true);

        // just ensure that all the mocked methods have been called
        // as we expect them to work, the user would have been created then
        $this->db->expects($this->once())->method('CreateNewUser')
            ->with('nickname', 'a@bar.com');
        $this->db->expects($this->once())->method('RequestConfirmUserCode')
            ->with($user, 'password', 'a@bar.com');
        $this->mailer->expects($this->once())->method('SendRegisterUserConfirmMessage')
            ->with('a@bar.com', 'nickname', 'the-confirm-code');

        $this->ruh->HandleRequest($this->db);
    }

    public function testRegisterUser_confirmCodeSendFailsUserDeleted(): void
    {
        // if the confirm code cannot be sent the user must be deleted again
        // as it can never ever be activated
        $_POST[RegisterUserHandler::PARAM_NICK] = 'nickname';
        $_POST[RegisterUserHandler::PARAM_EMAIL] = 'a@bar.com';
        $_POST[RegisterUserHandler::PARAM_PASS] = 'password';
        $_POST[RegisterUserHandler::PARAM_CONFIRMPASS] = 'password';

        $user = $this->createMock(User::class);
        $this->db->method('CreateNewUser')->with('nickname', 'a@bar.com')->willReturn($user);
        $this->db->method('RequestConfirmUserCode')->with($user, 'password', 'a@bar.com')->willReturn('the-confirm-code');
        $this->mailer->method('SendRegisterUserConfirmMessage')->with('a@bar.com', 'nickname', 'the-confirm-code')->willReturn(false);

        // just ensure that all the mocked methods have been called
        // as we expect them to work, the user would have been created then
        $this->db->expects($this->once())->method('CreateNewUser')
            ->with('nickname', 'a@bar.com');
        $this->db->expects($this->once())->method('RequestConfirmUserCode')
            ->with($user, 'password', 'a@bar.com');
        $this->mailer->expects($this->once())->method('SendRegisterUserConfirmMessage')
            ->with('a@bar.com', 'nickname', 'the-confirm-code');
        $this->db->expects($this->once())->method('RemoveConfirmUserCode')
            ->with($user);
        $this->db->expects($this->once())->method('DeleteUser')
            ->with($user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(RegisterUserHandler::MSG_SENDING_CONFIRMMAIL_FAILED);
        $this->expectExceptionCode(RegisterUserHandler::MSGCODE_INTERNAL_ERROR);

        $this->ruh->HandleRequest($this->db);
    }
}
