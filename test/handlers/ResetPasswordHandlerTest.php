<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

require_once __DIR__ . '/../../src/handlers/ResetPasswordHandler.php';

/**
 * No Database stuff required
 */
#[AllowMockObjectsWithoutExpectations]
final class ResetPasswordHandlerTest extends TestCase
{
    // required mocks our handler under test depends on
    private ForumDb $db;
    private Logger $logger;
    private Mailer $mailer;

    // our actuall handler to test
    private ResetPasswordHandler $rph;

    protected function setUp(): void
    {
        $this->db = $this->createMock(ForumDb::class);
        $this->logger = $this->createMock(Logger::class);
        $this->mailer = $this->createMock(Mailer::class);
        $this->rph = new ResetPasswordHandler();
        $this->rph->setLogger($this->logger);
        $this->rph->setMailer($this->mailer);
        // dont know why we need to set this here, as it is already defined in bootstrap.php
        $_SERVER['REMOTE_ADDR'] = '13.13.13.13';
        // must always reset all previously set $_POST entries
        $_POST = [];
    }

    public function testConstruct(): void
    {
        static::assertNull($this->rph->getNick());
        static::assertNull($this->rph->getEmail());
    }

    public static function providerTestResetByNickOrEmail(): array
    {
        return [
            // EMAIL_OR_NICK
            ['foo'],
            ['foo@bar.com'],
        ];
    }

    #[DataProvider('providerTestResetByNickOrEmail')]
    public function testReset_byNickOrEmail(string $nickOrEmail): void
    {
        // test that we interpret the passed value as email first and then as nick
        $_POST[ResetPasswordHandler::PARAM_EMAIL_OR_NICK] = $nickOrEmail;

        $user = $this->createMock(User::class);
        $user->method('HasEmail')->willReturn(true);
        $user->method('GetEmail')->willReturn('foo@bar.com');
        $user->method('GetNick')->willReturn('foo');
        $user->method('IsDummyUser')->willReturn(false);
        $user->method('IsActive')->willReturn(true);
        $user->method('NeedsMigration')->willReturn(false);
        $this->db->method('LoadUserByNick')->with('foo')->willReturn($user);
        $this->db->method('LoadUserByEmail')->with('foo@bar.com')->willReturn($user);
        $this->db->method('RequestPasswordResetCode')->with($user)->willReturn('confirm-code');
        $this->mailer->method('SendResetPasswordMessage')->with('foo@bar.com', 'foo', 'confirm-code')->willReturn(true);

        // expect that the db is called with the correct params
        $this->db->expects($this->once())->method('RequestPasswordResetCode')
            ->with($user, '13.13.13.13');

        // and the mailer to actually send the mail
        $this->mailer->expects($this->once())->method('SendResetPasswordMessage')
        ->with('foo@bar.com', 'foo', 'confirm-code');

        $this->rph->handleRequest($this->db);
    }

    public static function providerTestReset_failsIfNickNorMailFound(): array
    {
        return [
            // EMAIL_OR_NICK
            ['notexistring'],
            ['unknown@bar.com'],
        ];
    }

    #[DataProvider('providerTestReset_failsIfNickNorMailFound')]
    public function testReset_failsIfNickNorMailFound(string $nickOrEmail): void
    {
        $_POST[ResetPasswordHandler::PARAM_EMAIL_OR_NICK] = $nickOrEmail;

        // expect that the logger is called with the correct params when failing
        $this->logger->expects($this->once())->method('LogMessage')
            ->with(LogType::LOG_OPERATION_FAILED_NO_MATCHING_NICK_OR_EMAIL, 'Passed nick or email: ' . $nickOrEmail);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ResetPasswordHandler::MSG_UNKNOWN_EMAIL_OR_NICK);
        $this->expectExceptionCode(ResetPasswordHandler::MSGCODE_BAD_PARAM);

        $this->rph->handleRequest($this->db);
    }

    public function testReset_failsIfUserHasNoMail(): void
    {
        $_POST[ResetPasswordHandler::PARAM_EMAIL_OR_NICK] = 'foo';

        $user = $this->createMock(User::class);
        $user->method('HasEmail')->willReturn(false);
        $this->db->method('LoadUserByNick')->with('foo')->willReturn($user);

        // expect that the logger is called with the correct params when failing
        $this->logger->expects($this->once())->method('LogMessageWithUserId')
            ->with(LogType::LOG_OPERATION_FAILED_USER_HAS_NO_EMAIL, $user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ResetPasswordHandler::MSG_USER_HAS_NO_EMAIL);
        $this->expectExceptionCode(ResetPasswordHandler::MSGCODE_BAD_PARAM);

        $this->rph->handleRequest($this->db);
    }

    public function testReset_failsIfUserIsDummy(): void
    {
        $_POST[ResetPasswordHandler::PARAM_EMAIL_OR_NICK] = 'foo';

        $user = $this->createMock(User::class);
        $user->method('HasEmail')->willReturn(true);
        $user->method('IsDummyUser')->willReturn(true);
        $this->db->method('LoadUserByNick')->with('foo')->willReturn($user);

        // expect that the logger is called with the correct params when failing
        $this->logger->expects($this->once())->method('LogMessageWithUserId')
            ->with(LogType::LOG_OPERATION_FAILED_USER_IS_DUMMY, $user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ResetPasswordHandler::MSG_DUMMY_USER);
        $this->expectExceptionCode(ResetPasswordHandler::MSGCODE_BAD_PARAM);

        $this->rph->handleRequest($this->db);
    }

    public function testReset_failsIfUserIsInactive(): void
    {
        $_POST[ResetPasswordHandler::PARAM_EMAIL_OR_NICK] = 'foo';

        $user = $this->createMock(User::class);
        $user->method('HasEmail')->willReturn(true);
        $user->method('IsDummyUser')->willReturn(false);
        $user->method('IsActive')->willReturn(false);
        $this->db->method('LoadUserByNick')->with('foo')->willReturn($user);

        // expect that the logger is called with the correct params when failing
        $this->logger->expects($this->once())->method('LogMessageWithUserId')
            ->with(LogType::LOG_OPERATION_FAILED_USER_IS_INACTIVE, $user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ResetPasswordHandler::MSG_USER_INACTIVE);
        $this->expectExceptionCode(ResetPasswordHandler::MSGCODE_BAD_PARAM);

        $this->rph->handleRequest($this->db);
    }

    public function testReset_IfUserIsInactiveButNeedsMigration(): void
    {
        $_POST[ResetPasswordHandler::PARAM_EMAIL_OR_NICK] = 'foo';

        $user = $this->createMock(User::class);
        $user->method('HasEmail')->willReturn(true);
        $user->method('GetEmail')->willReturn('foo@bar.com');
        $user->method('GetNick')->willReturn('foo');
        $user->method('IsDummyUser')->willReturn(false);
        $user->method('IsActive')->willReturn(false);
        $user->method('NeedsMigration')->willReturn(true);
        $this->db->method('LoadUserByNick')->with('foo')->willReturn($user);
        $this->db->method('RequestPasswordResetCode')->with($user)->willReturn('confirm-code');
        $this->mailer->method('SendResetPasswordMessage')->with('foo@bar.com', 'foo', 'confirm-code')->willReturn(true);

        // expect that the db is called with the correct params
        $this->db->expects($this->once())->method('RequestPasswordResetCode')
            ->with($user, '13.13.13.13');

        // and the mailer to actually send the mail
        $this->mailer->expects($this->once())->method('SendResetPasswordMessage')
        ->with('foo@bar.com', 'foo', 'confirm-code');

        $this->rph->handleRequest($this->db);
    }

    public function testReset_removeResetPasswordCodeIfMailingFails(): void
    {
        $_POST[ResetPasswordHandler::PARAM_EMAIL_OR_NICK] = 'foo';

        $user = $this->createMock(User::class);
        $user->method('HasEmail')->willReturn(true);
        $user->method('GetEmail')->willReturn('foo@bar.com');
        $user->method('GetNick')->willReturn('foo');
        $user->method('IsDummyUser')->willReturn(false);
        $user->method('IsActive')->willReturn(true);
        $user->method('NeedsMigration')->willReturn(false);
        $this->db->method('LoadUserByNick')->with('foo')->willReturn($user);
        $this->db->method('RequestPasswordResetCode')->with($user)->willReturn('confirm-code');
        $this->mailer->method('SendResetPasswordMessage')->willReturn(false);

        // expect that the db is called with the correct params
        $this->db->expects($this->once())->method('RequestPasswordResetCode')
            ->with($user, '13.13.13.13');

        // and the mailer to actually try to send the mail
        $this->mailer->expects($this->once())->method('SendResetPasswordMessage')
        ->with('foo@bar.com', 'foo', 'confirm-code');

        // expect that the db is requested to remove the created code again
        $this->db->expects($this->once())->method('RemoveResetPasswordCode')
            ->with($user);

        // before failing
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ResetPasswordHandler::MSG_SENDING_CONFIRMMAIL_FAILED);
        $this->expectExceptionCode(ResetPasswordHandler::MSGCODE_INTERNAL_ERROR);

        $this->rph->handleRequest($this->db);
    }
}
