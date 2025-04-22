<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../../src/handlers/UpdatePasswordHandler.php';

/**
 * No Database stuff required
 */
final class UpdatePasswordHandlerTest extends TestCase
{
    // required mocks our handler under test depends on
    private ForumDb $db;
    private Logger $logger;
    private User $user;

    // our actuall handler to test
    private UpdatePasswordHandler $uph;

    protected function setUp(): void
    {
        $this->db = $this->createMock(ForumDb::class);
        $this->logger = $this->createMock(Logger::class);
        $this->user = $this->createMock(User::class);
        $this->user->method('GetNick')->willReturn('foo');
        $this->user->method('GetEmail')->willReturn('foo@bar.com');
        $this->uph = new UpdatePasswordHandler($this->user);
        $this->uph->SetLogger($this->logger);
        //$this->ueh->SetMailer($this->mailer);
        // dont know why we need to set this here, as it is already defined in bootstrap.php
        $_SERVER['REMOTE_ADDR'] = '13.13.13.13';
        // must always reset all previously set $_POST entries
        $_POST = array();
    }

    public function testUpdatePassword_failsIfNewPasswordsDoNotMatch()
    {
        $_POST[UpdatePasswordHandler::PARAM_NEWPASS] = 'my-password';
        $_POST[UpdatePasswordHandler::PARAM_CONFIRMNEWPASS] = 'my-something-else';
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(UpdatePasswordHandler::MSG_PASSWORDS_NOT_MATCH);
        $this->expectExceptionCode(UpdatePasswordHandler::MSGCODE_BAD_PARAM);

        $this->uph->HandleRequest($this->db);
    }

    public function testUpdatePassword_failsIfNewPasswordTooShort()
    {
        $password = str_pad('', YbForumConfig::MIN_PASSWWORD_LENGTH - 1, 'a');

        $_POST[UpdatePasswordHandler::PARAM_NEWPASS] = $password;
        $_POST[UpdatePasswordHandler::PARAM_CONFIRMNEWPASS] = $password;
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(UpdatePasswordHandler::MSG_PASSWORD_TOO_SHORT);
        $this->expectExceptionCode(UpdatePasswordHandler::MSGCODE_BAD_PARAM);

        $this->uph->HandleRequest($this->db);
    }

    public function testUpdatePassword_failsIfUserIsInactive()
    {
        $password = str_pad('', YbForumConfig::MIN_PASSWWORD_LENGTH, 'a');
        
        $_POST[UpdatePasswordHandler::PARAM_NEWPASS] = $password;
        $_POST[UpdatePasswordHandler::PARAM_CONFIRMNEWPASS] = $password;

        $this->user->method('IsActive')->willReturn(false);
        $this->user->method('NeedsMigration')->willReturn(false);
        
        // expect that the logger is called with the correct params when failing
        $this->logger->expects($this->once())->method('LogMessageWithUserId')
            ->with(LogType::LOG_OPERATION_FAILED_USER_IS_INACTIVE, $this->user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(UpdatePasswordHandler::MSG_USER_INACTIVE);
        $this->expectExceptionCode(UpdatePasswordHandler::MSGCODE_BAD_PARAM);

        $this->uph->HandleRequest($this->db);
    }

    public function testUpdatePassword_ifUserIsInactiveButNeedsMigration()
    {
        $password = str_pad('', YbForumConfig::MIN_PASSWWORD_LENGTH, 'a');
        
        $_POST[UpdatePasswordHandler::PARAM_NEWPASS] = $password;
        $_POST[UpdatePasswordHandler::PARAM_CONFIRMNEWPASS] = $password;

        $this->user->method('IsActive')->willReturn(false);
        $this->user->method('NeedsMigration')->willReturn(true);
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // expect that the db is called with the correct params
        $this->db->expects($this->once())->method('ConfirmUser')
            ->with($this->user);

        $this->uph->HandleRequest($this->db);
    }

    public function testUpdatePassword_ifUserIsActive()
    {
        $password = str_pad('', YbForumConfig::MIN_PASSWWORD_LENGTH, 'a');
        
        $_POST[UpdatePasswordHandler::PARAM_NEWPASS] = $password;
        $_POST[UpdatePasswordHandler::PARAM_CONFIRMNEWPASS] = $password;

        $this->user->method('IsActive')->willReturn(true);
        
        // expect that the db is called with the correct params
        $this->db->expects($this->once())->method('UpdateUserPassword')
            ->with($this->user, $password);

        $this->uph->HandleRequest($this->db);
    }

}