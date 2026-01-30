<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

require_once __DIR__ . '/../../src/helpers/Mailer.php';


/**
 * No Database stuff required
 */
#[AllowMockObjectsWithoutExpectations]
final class MailerTest extends TestCase
{
    // required mocks our handler under test depends on
    private Logger $logger;
    private MailerDelegate $delegate;

    // our actuall Mailer to test
    private Mailer $mailer;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->delegate = $this->createMock(MailerDelegate::class);
        $this->mailer = new Mailer($this->delegate, $this->logger);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testConstruct(): void
    {
        // construct without injecting anything
        $mailer = new Mailer();
        $mailFrom = YbForumConfig::MAIL_FROM_NAME . ' <' . YbForumConfig::MAIL_FROM . '>';
        static::assertEquals($mailFrom, $mailer->getMailFrom());
        static::assertEquals(YbForumConfig::MAIL_FROM, $mailer->getReturnPath());
        static::assertEquals(YbForumConfig::MAIL_ALL_BCC, $mailer->getAllMailBcc());
        static::assertEquals('text/plain; charset=utf-8', $mailer->getContentType());

        static::assertInstanceOf(Logger::class, $mailer->getLogger());
        static::assertInstanceOf(PhpMailer::class, $mailer->getMailerDelegate());
    }


    public static function providerSendingSucceeds(): array
    {
        return [
            [true],
            [false],
        ];
    }

    #[DataProvider('providerSendingSucceeds')]
    public function testsendMigrateUserConfirmMessage(bool $sendingSucceeds): void
    {
        $mailto = 'user@mail.com';
        $expectedSubject = '1898-Forum Migration Stammposter';
        $code = 'confirm-code';
        $expectedLink = 'confirm.php?type=confirmuser&code=' . $code;

        // test that the mailer-delegate is called with the expected params
        $matcher = $this->once();
        $this->delegate->expects($matcher)
            ->method('sendMessage')
            ->willReturnCallback(function (string $to, string $subject, string $content, $headers) use ($matcher, $mailto, $expectedSubject, $expectedLink, $sendingSucceeds) {
                $this->assertEquals($mailto, $to);
                $this->assertEquals($expectedSubject, $subject);
                $this->assertStringContainsString($expectedLink, $content);
                return $sendingSucceeds;
            });

        // and the logger in case of success / failure
        if ($sendingSucceeds) {
            $this->logger->expects($this->once())
                ->method('LogMessage')
                ->with(LogType::LOG_MAIL_SENT, 'Mail sent to: ' . $mailto);
        } else {
            $this->logger->expects($this->once())
                ->method('LogMessage')
                ->with(LogType::LOG_MAIL_FAILED, 'Failed to send mail to: ' . $mailto);
        }

        $this->mailer->sendMigrateUserConfirmMessage($mailto, 'nick', $code);
    }

    #[DataProvider('providerSendingSucceeds')]
    public function testsendRegisterUserConfirmMessage(bool $sendingSucceeds): void
    {
        $mailto = 'user@mail.com';
        $expectedSubject = '1898-Forum Registrierung Stammposter';
        $code = 'confirm-code';
        $expectedLink = 'confirm.php?type=confirmuser&code=' . $code;

        // test that the mailer-delegate is called with the expected params
        $matcher = $this->once();
        $this->delegate->expects($matcher)
            ->method('sendMessage')
            ->willReturnCallback(function (string $to, string $subject, string $content, $headers) use ($matcher, $mailto, $expectedSubject, $expectedLink, $sendingSucceeds) {
                $this->assertEquals($mailto, $to);
                $this->assertEquals($expectedSubject, $subject);
                $this->assertStringContainsString($expectedLink, $content);
                return $sendingSucceeds;
            });

        // and the logger in case of success / failure
        if ($sendingSucceeds) {
            $this->logger->expects($this->once())
                ->method('LogMessage')
                ->with(LogType::LOG_MAIL_SENT, 'Mail sent to: ' . $mailto);
        } else {
            $this->logger->expects($this->once())
                ->method('LogMessage')
                ->with(LogType::LOG_MAIL_FAILED, 'Failed to send mail to: ' . $mailto);
        }

        $this->mailer->sendRegisterUserConfirmMessage($mailto, 'nick', $code);
    }

    #[DataProvider('providerSendingSucceeds')]
    public function testsendUpdateEmailConfirmMessage(bool $sendingSucceeds): void
    {
        $mailto = 'user@mail.com';
        $expectedSubject = '1898-Forum aktualisierte Stammposter-Mailadresse bestaetigen';
        $code = 'confirm-code';
        $expectedLink = 'confirm.php?type=updateemail&code=' . $code;

        // test that the mailer-delegate is called with the expected params
        $matcher = $this->once();
        $this->delegate->expects($matcher)
            ->method('sendMessage')
            ->willReturnCallback(function (string $to, string $subject, string $content, $headers) use ($matcher, $mailto, $expectedSubject, $expectedLink, $sendingSucceeds) {
                $this->assertEquals($mailto, $to);
                $this->assertEquals($expectedSubject, $subject);
                $this->assertStringContainsString($expectedLink, $content);
                return $sendingSucceeds;
            });

        // and the logger in case of success / failure
        if ($sendingSucceeds) {
            $this->logger->expects($this->once())
                ->method('LogMessage')
                ->with(LogType::LOG_MAIL_SENT, 'Mail sent to: ' . $mailto);
        } else {
            $this->logger->expects($this->once())
                ->method('LogMessage')
                ->with(LogType::LOG_MAIL_FAILED, 'Failed to send mail to: ' . $mailto);
        }

        $this->mailer->sendUpdateEmailConfirmMessage($mailto, 'nick', $code);
    }

    #[DataProvider('providerSendingSucceeds')]
    public function testsendResetPasswordMessage(bool $sendingSucceeds): void
    {
        $mailto = 'user@mail.com';
        $expectedSubject = '1898-Forum Stammposter-Passwort zuruecksetzen';
        $code = 'confirm-code';
        $expectedLink = 'resetpassword.php?type=resetpass&code=' . $code;

        // test that the mailer-delegate is called with the expected params
        $matcher = $this->once();
        $this->delegate->expects($matcher)
            ->method('sendMessage')
            ->willReturnCallback(function (string $to, string $subject, string $content, $headers) use ($matcher, $mailto, $expectedSubject, $expectedLink, $sendingSucceeds) {
                $this->assertEquals($mailto, $to);
                $this->assertEquals($expectedSubject, $subject);
                $this->assertStringContainsString($expectedLink, $content);
                return $sendingSucceeds;
            });

        // and the logger in case of success / failure
        if ($sendingSucceeds) {
            $this->logger->expects($this->once())
                ->method('LogMessage')
                ->with(LogType::LOG_MAIL_SENT, 'Mail sent to: ' . $mailto);
        } else {
            $this->logger->expects($this->once())
                ->method('LogMessage')
                ->with(LogType::LOG_MAIL_FAILED, 'Failed to send mail to: ' . $mailto);
        }

        $this->mailer->sendResetPasswordMessage($mailto, 'nick', $code);
    }

    public function testsendNotifyUserAcceptedEmail(): void
    {
        $mailto = 'admin@mail.com';
        $expectedSubject = 'Stammposter freigeschaltet';

        // test that the mailer-delegate is called with the expected params
        $matcher = $this->once();
        $this->delegate->expects($matcher)
            ->method('sendMessage')
            ->willReturnCallback(function (string $to, string $subject, string $content, $headers) use ($matcher, $mailto, $expectedSubject) {
                $this->assertEquals($mailto, $to);
                $this->assertEquals($expectedSubject, $subject);
                return true;
            });

        $this->mailer->sendNotifyUserAcceptedEmail($mailto, 'nick');
    }

    public function testsendNotifyUserDeniedEmail(): void
    {
        $mailto = 'admin@mail.com';
        $expectedSubject = 'Registrierung abgelehnt';

        // test that the mailer-delegate is called with the expected params
        $matcher = $this->once();
        $this->delegate->expects($matcher)
            ->method('sendMessage')
            ->willReturnCallback(function (string $to, string $subject, string $content, $headers) use ($matcher, $mailto, $expectedSubject) {
                $this->assertEquals($mailto, $to);
                $this->assertEquals($expectedSubject, $subject);
                return true;
            });

        $this->mailer->sendNotifyUserDeniedEmail($mailto, 'nick');
    }

    public function testnotifyAdminUserConfirmedRegistration(): void
    {
        $mailto = 'admin@mail.com';
        $expectedSubject = 'Benutzer wartet auf Freischaltung';

        // test that the mailer-delegate is called with the expected params
        $matcher = $this->once();
        $this->delegate->expects($matcher)
            ->method('sendMessage')
            ->willReturnCallback(function (string $to, string $subject, string $content, $headers) use ($matcher, $mailto, $expectedSubject) {
                $this->assertEquals($mailto, $to);
                $this->assertEquals($expectedSubject, $subject);
                return true;
            });

        $this->mailer->notifyAdminUserConfirmedRegistration('nick', $mailto, 'registration-message');
    }

    #[DataProvider('providerSendingSucceeds')]
    public function testsendAdminContactMessage($sendingSucceeds): void
    {
        $mailto = 'admin@mail.com';
        $expectedSubject = 'Kontaktnachricht erhalten';
        $expectedContactMail = 'someone@somewhere.com';

        // test that the mailer-delegate is called with the expected params
        $matcher = $this->once();
        $this->delegate->expects($matcher)
            ->method('sendMessage')
            ->willReturnCallback(function (string $to, string $subject, string $content, $headers) use ($matcher, $mailto, $expectedSubject, $expectedContactMail, $sendingSucceeds) {
                $this->assertEquals($mailto, $to);
                $this->assertEquals($expectedSubject, $subject);
                $this->assertStringContainsString($expectedContactMail, $content);
                return $sendingSucceeds;
            });

        // and the logger in case of success / failure
        if ($sendingSucceeds) {
            $this->logger->expects($this->once())
                ->method('LogMessage')
                ->with(LogType::LOG_MAIL_SENT, 'Mail sent to: ' . $mailto);
        } else {
            $this->logger->expects($this->once())
                ->method('LogMessage')
                ->with(LogType::LOG_MAIL_FAILED, 'Failed to send mail to: ' . $mailto);
        }

        $this->mailer->sendAdminContactMessage($expectedContactMail, 'contact-message', $mailto);
    }
}
