<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

require_once __DIR__ . '/../../src/handlers/MigrateUserHandler.php';

/**
 * No Database stuff required
 */
#[AllowMockObjectsWithoutExpectations]
final class MigrateUserHandlerTest extends TestCase
{
    // required mocks our handler under test depends on
    private ForumDb $db;
    private Logger $logger;
    private Mailer $mailer;

    private User $user;

    // our actuall handler to test
    private MigrateUserHandler $muh;

    protected function setUp(): void
    {
        $this->db = $this->createMock(ForumDb::class);
        $this->logger = $this->createMock(Logger::class);
        $this->mailer = $this->createMock(Mailer::class);
        $this->user = static::createStub(User::class);
        $this->user->method('GetNick')->willReturn('foo');
        $this->user->method('GetId')->willReturn(10);
        $this->muh = new MigrateUserHandler();
        $this->muh->SetLogger($this->logger);
        $this->muh->SetMailer($this->mailer);
        //$this->ueh->SetMailer($this->mailer);
        // dont know why we need to set this here, as it is already defined in bootstrap.php
        $_SERVER['REMOTE_ADDR'] = '13.13.13.13';
        // must always reset all previously set $_POST entries
        $_POST = [];
    }

    public function testMigrateUser_failsIfNoNickPassed(): void
    {
        $_POST[MigrateUserHandler::PARAM_NICK] = '';
        $_POST[MigrateUserHandler::PARAM_OLDPASS] = 'old-password';
        $_POST[MigrateUserHandler::PARAM_NEWPASS] = 'my-password';
        $_POST[MigrateUserHandler::PARAM_CONFIRMNEWPASS] = 'my-something-else';
        $_POST[MigrateUserHandler::PARAM_NEWEMAIL] = 'foo@bar.com';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(MigrateUserHandler::MSG_AUTH_FAIL);
        $this->expectExceptionCode(MigrateUserHandler::MSGCODE_BAD_PARAM);

        $this->muh->HandleRequest($this->db);
    }

    public function testMigrateUser_failsIfNoOldPasswordPassed(): void
    {
        $_POST[MigrateUserHandler::PARAM_NICK] = '';
        $_POST[MigrateUserHandler::PARAM_OLDPASS] = '';
        $_POST[MigrateUserHandler::PARAM_NEWPASS] = 'my-password';
        $_POST[MigrateUserHandler::PARAM_CONFIRMNEWPASS] = 'my-something-else';
        $_POST[MigrateUserHandler::PARAM_NEWEMAIL] = 'foo@bar.com';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(MigrateUserHandler::MSG_AUTH_FAIL);
        $this->expectExceptionCode(MigrateUserHandler::MSGCODE_BAD_PARAM);

        $this->muh->HandleRequest($this->db);
    }

    public function testMigrateUser_failsIfNewPasswortTooShort(): void
    {
        $password = str_pad('', YbForumConfig::MIN_PASSWWORD_LENGTH - 1, 'a');

        $_POST[MigrateUserHandler::PARAM_NICK] = 'foo';
        $_POST[MigrateUserHandler::PARAM_OLDPASS] = 'old-password';
        $_POST[MigrateUserHandler::PARAM_NEWPASS] = $password;
        $_POST[MigrateUserHandler::PARAM_CONFIRMNEWPASS] = $password;
        $_POST[MigrateUserHandler::PARAM_NEWEMAIL] = 'foo@bar.com';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(MigrateUserHandler::MSG_PASSWORD_TOO_SHORT);
        $this->expectExceptionCode(MigrateUserHandler::MSGCODE_BAD_PARAM);

        $this->muh->HandleRequest($this->db);
    }

    public function testMigrateUser_failsIfNewPasswordsDoNotMatch(): void
    {
        $_POST[MigrateUserHandler::PARAM_NICK] = 'foo';
        $_POST[MigrateUserHandler::PARAM_OLDPASS] = 'old-password';
        $_POST[MigrateUserHandler::PARAM_NEWPASS] = 'my-password';
        $_POST[MigrateUserHandler::PARAM_CONFIRMNEWPASS] = 'my-something-else';
        $_POST[MigrateUserHandler::PARAM_NEWEMAIL] = 'foo@bar.com';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(MigrateUserHandler::MSG_PASSWORDS_NOT_MATCH);
        $this->expectExceptionCode(MigrateUserHandler::MSGCODE_BAD_PARAM);

        $this->muh->HandleRequest($this->db);
    }

    public function testMigrateUser_failsIfNoSuchNick(): void
    {
        $password = str_pad('', YbForumConfig::MIN_PASSWWORD_LENGTH, 'a');

        $_POST[MigrateUserHandler::PARAM_NICK] = 'foo';
        $_POST[MigrateUserHandler::PARAM_OLDPASS] = 'old-password';
        $_POST[MigrateUserHandler::PARAM_NEWPASS] = $password;
        $_POST[MigrateUserHandler::PARAM_CONFIRMNEWPASS] = $password;
        $_POST[MigrateUserHandler::PARAM_NEWEMAIL] = 'foo@bar.com';

        $this->db->method('LoadUserByNick')->with('foo')->willReturn(null);

        // expect that the logger is called with the correct params
        $this->logger->expects($this->once())->method('LogMessage')
            ->with(LogType::LOG_AUTH_FAILED_NO_SUCH_USER, 'Passed nick: foo');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(MigrateUserHandler::MSG_AUTH_FAIL);
        $this->expectExceptionCode(MigrateUserHandler::MSGCODE_AUTH_FAIL);

        $this->muh->HandleRequest($this->db);
    }

    public function testMigrateUser_failsIfUserIsDummy(): void
    {
        $password = str_pad('', YbForumConfig::MIN_PASSWWORD_LENGTH, 'a');

        $_POST[MigrateUserHandler::PARAM_NICK] = 'foo';
        $_POST[MigrateUserHandler::PARAM_OLDPASS] = 'old-password';
        $_POST[MigrateUserHandler::PARAM_NEWPASS] = $password;
        $_POST[MigrateUserHandler::PARAM_CONFIRMNEWPASS] = $password;
        $_POST[MigrateUserHandler::PARAM_NEWEMAIL] = 'foo@bar.com';

        $this->db->method('LoadUserByNick')->with('foo')->willReturn($this->user);
        $this->user->method('IsDummyUser')->willReturn(true);

        // expect that the logger is called with the correct params
        $this->logger->expects($this->once())->method('LogMessageWithUserId')
            ->with(LogType::LOG_AUTH_FAILED_USER_IS_DUMMY, $this->user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(MigrateUserHandler::MSG_AUTH_FAIL);
        $this->expectExceptionCode(MigrateUserHandler::MSGCODE_AUTH_FAIL);

        $this->muh->HandleRequest($this->db);
    }

    public function testMigrateUser_failsIfMigrationNotRequired(): void
    {
        $password = str_pad('', YbForumConfig::MIN_PASSWWORD_LENGTH, 'a');

        $_POST[MigrateUserHandler::PARAM_NICK] = 'foo';
        $_POST[MigrateUserHandler::PARAM_OLDPASS] = 'old-password';
        $_POST[MigrateUserHandler::PARAM_NEWPASS] = $password;
        $_POST[MigrateUserHandler::PARAM_CONFIRMNEWPASS] = $password;
        $_POST[MigrateUserHandler::PARAM_NEWEMAIL] = 'foo@bar.com';

        $this->db->method('LoadUserByNick')->with('foo')->willReturn($this->user);
        $this->user->method('IsDummyUser')->willReturn(false);
        $this->user->method('NeedsMigration')->willReturn(false);

        // expect that the logger is called with the correct params
        $this->logger->expects($this->once())->method('LogMessageWithUserId')
            ->with(LogType::LOG_OPERATION_FAILED_ALREADY_MIGRATED, $this->user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(MigrateUserHandler::MSG_ALREADY_MIGRATED);
        $this->expectExceptionCode(MigrateUserHandler::MSGCODE_BAD_PARAM);

        $this->muh->HandleRequest($this->db);
    }

    public function testMigrateUser_failsIfOldPasswordDoesNotMatch(): void
    {
        $password = str_pad('', YbForumConfig::MIN_PASSWWORD_LENGTH, 'a');

        $_POST[MigrateUserHandler::PARAM_NICK] = 'foo';
        $_POST[MigrateUserHandler::PARAM_OLDPASS] = 'old-password';
        $_POST[MigrateUserHandler::PARAM_NEWPASS] = $password;
        $_POST[MigrateUserHandler::PARAM_CONFIRMNEWPASS] = $password;
        $_POST[MigrateUserHandler::PARAM_NEWEMAIL] = 'foo@bar.com';

        $this->db->method('LoadUserByNick')->with('foo')->willReturn($this->user);
        $this->user->method('IsDummyUser')->willReturn(false);
        $this->user->method('NeedsMigration')->willReturn(true);
        $this->user->method('OldAuth')->willReturn(false);

        // expect that the logger is called with the correct params
        $this->logger->expects($this->once())->method('LogMessageWithUserId')
            ->with(LogType::LOG_AUTH_FAILED_USING_OLD_PASSWORD, $this->user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(MigrateUserHandler::MSG_AUTH_FAIL);
        $this->expectExceptionCode(MigrateUserHandler::MSGCODE_AUTH_FAIL);

        $this->muh->HandleRequest($this->db);
    }

    public function testMigrateUser_failsIfMailUsedForOtherUser(): void
    {
        $password = str_pad('', YbForumConfig::MIN_PASSWWORD_LENGTH, 'a');

        $_POST[MigrateUserHandler::PARAM_NICK] = 'foo';
        $_POST[MigrateUserHandler::PARAM_OLDPASS] = 'old-password';
        $_POST[MigrateUserHandler::PARAM_NEWPASS] = $password;
        $_POST[MigrateUserHandler::PARAM_CONFIRMNEWPASS] = $password;
        $_POST[MigrateUserHandler::PARAM_NEWEMAIL] = 'foo@bar.com';

        $someUser = $this->createMock(User::class);
        $someUser->method('GetId')->willReturn(100);
        $this->db->method('LoadUserByNick')->with('foo')->willReturn($this->user);
        $this->db->method('LoadUserByEmail')->with('foo@bar.com')->willReturn($someUser);

        $this->user->method('IsDummyUser')->willReturn(false);
        $this->user->method('NeedsMigration')->willReturn(true);
        $this->user->method('OldAuth')->willReturn(true);

        // expect that the logger is called with the correct params
        $invokedCount = $this->exactly(2);
        $mockUser = $this->user;
        $this->logger->expects($invokedCount)
            ->method('LogMessageWithUserId')
            ->willReturnCallback(function ($logType, $user) use ($invokedCount, $mockUser): void {
                if ($invokedCount->numberOfInvocations() === 1) {
                    $this->assertSame(LogType::LOG_AUTH_USING_OLD_PASSWORD, $logType);
                    $this->assertSame($mockUser, $user);
                } else {
                    $this->assertSame(LogType::LOG_OPERATION_FAILED_EMAIL_NOT_UNIQUE, $logType);
                    $this->assertSame($mockUser, $user);
                }
            });

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(MigrateUserHandler::MSG_EMAIL_NOT_UNIQUE);
        $this->expectExceptionCode(MigrateUserHandler::MSGCODE_BAD_PARAM);

        $this->muh->HandleRequest($this->db);
    }

    public function testMigrateUser_failsIfEmailBlacklisted(): void
    {
        $password = str_pad('', YbForumConfig::MIN_PASSWWORD_LENGTH, 'a');

        $_POST[MigrateUserHandler::PARAM_NICK] = 'foo';
        $_POST[MigrateUserHandler::PARAM_OLDPASS] = 'old-password';
        $_POST[MigrateUserHandler::PARAM_NEWPASS] = $password;
        $_POST[MigrateUserHandler::PARAM_CONFIRMNEWPASS] = $password;
        $_POST[MigrateUserHandler::PARAM_NEWEMAIL] = 'foo@bar.com';

        $someUser = $this->createMock(User::class);
        $someUser->method('GetId')->willReturn(100);
        $this->db->method('LoadUserByNick')->with('foo')->willReturn($this->user);
        $this->db->method('LoadUserByEmail')->with('foo@bar.com')->willReturn(null);
        $this->db->method('IsEmailOnBlacklistExactly')->willReturn('You are blacklisted');

        $this->user->method('IsDummyUser')->willReturn(false);
        $this->user->method('NeedsMigration')->willReturn(true);
        $this->user->method('OldAuth')->willReturn(true);

        // expect that the logger is called with the correct params
        $this->logger->expects($this->once())->method('LogMessageWithUserId')
            ->with(LogType::LOG_AUTH_USING_OLD_PASSWORD, $this->user);
        $this->logger->expects($this->once())->method('LogMessage')
            ->with(LogType::LOG_OPERATION_FAILED_EMAIL_BLACKLISTED);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(MigrateUserHandler::MSG_EMAIL_BLACKLISTED . 'You are blacklisted');
        $this->expectExceptionCode(MigrateUserHandler::MSGCODE_BAD_PARAM);

        $this->muh->HandleRequest($this->db);
    }

    public function testMigrateUser_newMailMatchesOldMail(): void
    {
        $password = str_pad('', YbForumConfig::MIN_PASSWWORD_LENGTH, 'a');

        $_POST[MigrateUserHandler::PARAM_NICK] = 'foo';
        $_POST[MigrateUserHandler::PARAM_OLDPASS] = 'old-password';
        $_POST[MigrateUserHandler::PARAM_NEWPASS] = $password;
        $_POST[MigrateUserHandler::PARAM_CONFIRMNEWPASS] = $password;
        $_POST[MigrateUserHandler::PARAM_NEWEMAIL] = 'foo@bar.com';

        $someUser = $this->createMock(User::class);
        $someUser->method('GetId')->willReturn(100);
        $this->db->method('LoadUserByNick')->with('foo')->willReturn($this->user);
        $this->db->method('LoadUserByEmail')->with('foo@bar.com')->willReturn($this->user);
        $this->db->method('IsEmailOnBlacklistExactly')->willReturn(false);
        $this->db->method('RequestConfirmUserCode')->willReturn('confirm-code');

        $this->mailer->method('SendMigrateUserConfirmMessage')->with('foo@bar.com', 'foo', 'confirm-code')->willReturn(true);

        $this->user->method('IsDummyUser')->willReturn(false);
        $this->user->method('NeedsMigration')->willReturn(true);
        $this->user->method('OldAuth')->willReturn(true);

        // expect that the logger is called with the correct params
        $this->logger->expects($this->once())->method('LogMessageWithUserId')
            ->with(LogType::LOG_AUTH_USING_OLD_PASSWORD, $this->user);

        // expect that the db is called with the correct params
        $this->db->expects($this->once())->method('RequestConfirmUserCode')
            ->with(
                $this->user,
                $password,
                'foo@bar.com',
                ForumDb::CONFIRM_SOURCE_MIGRATE,
                '13.13.13.13'
            );

        // and the mailer to actually try to send the mail
        $this->mailer->expects($this->once())->method('SendMigrateUserConfirmMessage')
        ->with('foo@bar.com', 'foo', 'confirm-code');

        $this->muh->HandleRequest($this->db);
    }

    public function testMigrateUser_noMailSetBeforeAndMailNotUsedSomewhereElse(): void
    {
        $password = str_pad('', YbForumConfig::MIN_PASSWWORD_LENGTH, 'a');

        $_POST[MigrateUserHandler::PARAM_NICK] = 'foo';
        $_POST[MigrateUserHandler::PARAM_OLDPASS] = 'old-password';
        $_POST[MigrateUserHandler::PARAM_NEWPASS] = $password;
        $_POST[MigrateUserHandler::PARAM_CONFIRMNEWPASS] = $password;
        $_POST[MigrateUserHandler::PARAM_NEWEMAIL] = 'foo@bar.com';

        $someUser = $this->createMock(User::class);
        $someUser->method('GetId')->willReturn(100);
        $this->db->method('LoadUserByNick')->with('foo')->willReturn($this->user);
        $this->db->method('LoadUserByEmail')->with('foo@bar.com')->willReturn(null);
        $this->db->method('IsEmailOnBlacklistExactly')->willReturn(false);
        $this->db->method('RequestConfirmUserCode')->willReturn('confirm-code');

        $this->mailer->method('SendMigrateUserConfirmMessage')->with('foo@bar.com', 'foo', 'confirm-code')->willReturn(true);

        $this->user->method('IsDummyUser')->willReturn(false);
        $this->user->method('NeedsMigration')->willReturn(true);
        $this->user->method('OldAuth')->willReturn(true);

        // expect that the logger is called with the correct params
        $this->logger->expects($this->once())->method('LogMessageWithUserId')
            ->with(LogType::LOG_AUTH_USING_OLD_PASSWORD, $this->user);

        // expect that the db is called with the correct params
        $this->db->expects($this->once())->method('RequestConfirmUserCode')
            ->with(
                $this->user,
                $password,
                'foo@bar.com',
                ForumDb::CONFIRM_SOURCE_MIGRATE,
                '13.13.13.13'
            );

        // and the mailer to actually try to send the mail
        $this->mailer->expects($this->once())->method('SendMigrateUserConfirmMessage')
        ->with('foo@bar.com', 'foo', 'confirm-code');

        $this->muh->HandleRequest($this->db);
    }
}
