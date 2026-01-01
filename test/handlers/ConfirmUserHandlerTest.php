<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

require_once __DIR__.'/../../src/handlers/ConfirmUserHandler.php';

/**
 * No Database stuff required
 */
#[AllowMockObjectsWithoutExpectations]
final class ConfirmUserHandlerTest extends TestCase
{
    // required mocks our handler under test depends on
    private ForumDb $db;
    private Logger $logger;
    private Mailer $mailer;
    private User $user;

    // our actuall handler to test
    private ConfirmUserHandler $cuh;

    protected function setUp(): void
    {
        $this->db = $this->createMock(ForumDb::class);
        $this->logger = $this->createMock(Logger::class);
        $this->mailer = $this->createMock(Mailer::class);
        $this->user = $this->createStub(User::class);
        $this->cuh = new ConfirmUserHandler();
        $this->cuh->SetLogger($this->logger);
        $this->cuh->SetMailer($this->mailer);
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
        $this->expectExceptionMessage(ConfirmUserHandler::MSG_CODE_UNKNOWN);
        $this->expectExceptionCode(ConfirmUserHandler::MSGCODE_BAD_PARAM);

        $this->cuh->HandleRequest($this->db);
    }

    public function testValidateParams_failWithoutCodeForPost() : void
    {
        // PARAM_CODE must be set, else we must fail wit h an exception
        $_SERVER['REQUEST_METHOD'] = 'POST';
        unset($_POST[ConfirmHandler::PARAM_CODE]);
        unset($_GET[ConfirmHandler::PARAM_CODE]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ConfirmUserHandler::MSG_CODE_UNKNOWN);
        $this->expectExceptionCode(ConfirmUserHandler::MSGCODE_BAD_PARAM);

        $this->cuh->HandleRequest($this->db);
    }

    public function testHandleRequest_failForInvalidCode() : void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET[ConfirmHandler::PARAM_CODE] = 'code';

        // Fail returning a userid for the passed code
        $this->db->method('VerifyConfirmUserCode')->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ConfirmUserHandler::MSG_CODE_UNKNOWN);
        $this->expectExceptionCode(ConfirmUserHandler::MSGCODE_BAD_PARAM);

        // expect that the logger is called with the correct params
        $this->logger->expects($this->once())->method('LogMessage')->with(LogType::LOG_CONFIRM_CODE_FAILED_CODE_INVALID, 'Passed code: code');

        $this->cuh->HandleRequest($this->db);
    }

    public function testHandleRequest_failForNoLongerExistringUser() : void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET[ConfirmHandler::PARAM_CODE] = 'code';

        // Return a user-confirm-entry
        $this->db->method('VerifyConfirmUserCode')->willReturn(array('iduser' => 1313, 
            'password' => 'encrypted', 
            'email' => 'new@mail.com',
            'confirm_source' => ForumDb::CONFIRM_SOURCE_NEWUSER
        ));
        $this->db->method('LoadUserById')->willReturn(null);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ConfirmUserHandler::MSG_CODE_UNKNOWN);
        $this->expectExceptionCode(ConfirmUserHandler::MSGCODE_BAD_PARAM);

        // expect that the logger is called with the correct params
        $this->logger->expects($this->once())->method('LogMessage')->with(LogType::LOG_CONFIRM_CODE_FAILED_NO_MATCHING_USER, 'iduser not found: 1313');

        $this->cuh->HandleRequest($this->db);
    }

    public function testHandleRequest_failForAlreadyConfirmedUser() : void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET[ConfirmHandler::PARAM_CODE] = 'code';

        // Return a user-confirm-entry
        $this->db->method('VerifyConfirmUserCode')->willReturn(array('iduser' => 1313, 
            'password' => 'encrypted', 
            'email' => 'new@mail.com',
            'confirm_source' => ForumDb::CONFIRM_SOURCE_NEWUSER
        ));
        $this->db->method('LoadUserById')->willReturn($this->user);
        $this->user->method('IsConfirmed')->willReturn(true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ConfirmUserHandler::MSG_ALREADY_CONFIRMED);
        $this->expectExceptionCode(ConfirmUserHandler::MSGCODE_BAD_PARAM);

        // expect that the logger is called with the correct params
        $this->logger->expects($this->once())->method('LogMessageWithUserId')->with(LogType::LOG_OPERATION_FAILED_ALREADY_CONFIRMED, $this->user);

        $this->cuh->HandleRequest($this->db);
    }

    public function testHandleRequest_failForAlreadyMigratedUser() : void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET[ConfirmHandler::PARAM_CODE] = 'code';

        // Return a migrate-user-entry
        $this->db->method('VerifyConfirmUserCode')->willReturn(array('iduser' => 1313, 
            'password' => 'encrypted', 
            'email' => 'new@mail.com',
            'confirm_source' => ForumDb::CONFIRM_SOURCE_MIGRATE
        ));
        $this->db->method('LoadUserById')->willReturn($this->user);
        $this->user->method('NeedsMigration')->willReturn(false);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ConfirmUserHandler::MSG_ALREADY_MIGRATED);
        $this->expectExceptionCode(ConfirmUserHandler::MSGCODE_BAD_PARAM);

        // expect that the logger is called with the correct params
        $this->logger->expects($this->once())->method('LogMessageWithUserId')->with(LogType::LOG_OPERATION_FAILED_ALREADY_MIGRATED, $this->user);

        $this->cuh->HandleRequest($this->db);
    }

    public function testHandleRequest_dontConfirmUserInSimulationMode() : void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET[ConfirmHandler::PARAM_CODE] = 'code';

        // Return a user-confirm-entry
        $this->db->method('VerifyConfirmUserCode')->willReturn(array('iduser' => 1313, 
            'password' => 'encrypted', 
            'email' => 'new@mail.com',
            'confirm_source' => ForumDb::CONFIRM_SOURCE_NEWUSER
        ));
        $this->db->method('LoadUserById')->willReturn($this->user);
        $this->user->method('IsConfirmed')->willReturn(false);

        // method to actually confirm must not be called
        $this->db->expects($this->never())->method('ConfirmUser');

        $this->cuh->HandleRequest($this->db);

        // but internal values must have been update
        $this->assertEquals('code', $this->cuh->GetCode());
    }

    public function testHandleRequest_dontMigrateUserInSimulationMode() : void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET[ConfirmHandler::PARAM_CODE] = 'code';

        // Return a user-confirm-entry
        $this->db->method('VerifyConfirmUserCode')->willReturn(array('iduser' => 1313, 
            'password' => 'encrypted', 
            'email' => 'new@mail.com',
            'confirm_source' => ForumDb::CONFIRM_SOURCE_MIGRATE
        ));
        $this->db->method('LoadUserById')->willReturn($this->user);
        $this->user->method('NeedsMigration')->willReturn(true);

        // method to actually confirm must not be called
        $this->db->expects($this->never())->method('ConfirmUser');

        $this->cuh->HandleRequest($this->db);

        // but internal values must have been update
        $this->assertEquals('code', $this->cuh->GetCode());
    }

    public function testHandleRequest_confirmUser() : void
    {
        // if not in simulation mode, we want to update things
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST[ConfirmHandler::PARAM_CODE] = 'code';

        // Return a user-confirm-entry
        $this->db->method('VerifyConfirmUserCode')->willReturn(array('iduser' => 1313, 
            'password' => 'encrypted', 
            'email' => 'new@mail.com',
            'confirm_source' => ForumDb::CONFIRM_SOURCE_NEWUSER
        ));
        $this->db->method('LoadUserById')->willReturn($this->user);
        $this->db->method('GetAdminMails')->willReturn(array('admin1@mail.com', 'admin2@mail.com'));
        $this->user->method('IsConfirmed')->willReturn(false);
        $this->user->method('GetNick')->willReturn('MockUser');
        $this->user->method('GetRegistrationMsg')->willReturn('Hello world');

        // method to actually confirm must be called
        $this->db->expects($this->once())->method('ConfirmUser')->with($this->user, 'encrypted', 'new@mail.com', false);

        // mailer must be called for every admin
        $matcher = $this->exactly(2);
        $this->mailer->expects($matcher)->method('NotifyAdminUserConfirmedRegistration')
            ->willReturnCallback(function(string $nick, string $mail, string $registrationMsg) use ($matcher) 
            {
                $this->assertEquals('MockUser', $nick);
                $this->assertEquals('Hello world', $registrationMsg);
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals('admin1@mail.com', $mail),
                    2 => $this->assertEquals('admin2@mail.com', $mail)
                };
                return true;
            });

        // logger must be called for every admin
        $matcher = $this->exactly(2);
        $mockUser = $this->user;
        $this->logger->expects($matcher)->method('LogMessageWithUserId')
            ->willReturnCallback(function(LogType $logType, User $user, string $msg) use ($matcher, $mockUser) 
            {
                $this->assertEquals(LogType::LOG_NOTIFIED_ADMIN_USER_REGISTRATION_CONFIRMED, $logType);
                $this->assertEquals($this->user, $user);
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals('Mail sent to: admin1@mail.com', $msg),
                    2 => $this->assertEquals('Mail sent to: admin2@mail.com', $msg)
                };
            });            
        
        $this->cuh->HandleRequest($this->db);

        // and property must have been update
        $this->assertEquals('code', $this->cuh->GetCode());

        // must return something non-empty
        $this->assertTrue(strlen($this->cuh->GetConfirmText()) > 0);
        $this->assertStringContainsString('Registrierung', $this->cuh->GetConfirmText());
        $this->assertTrue(strlen($this->cuh->GetSuccessText()) > 0);
        $this->assertStringContainsString('Registrierung', $this->cuh->GetSuccessText());
    }

    public function testHandleRequest_migrateUser() : void
    {
        // if not in simulation mode, we want to update things
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST[ConfirmHandler::PARAM_CODE] = 'code';

        // Return a user-migration-entry
        $this->db->method('VerifyConfirmUserCode')->willReturn(array('iduser' => 1313, 
            'password' => 'encrypted', 
            'email' => 'new@mail.com',
            'confirm_source' => ForumDb::CONFIRM_SOURCE_MIGRATE
        ));
        $this->db->method('LoadUserById')->willReturn($this->user);
        $this->db->method('GetAdminMails')->willReturn(array('admin1@mail.com', 'admin2@mail.com'));
        $this->user->method('NeedsMigration')->willReturn(true);

        // method to actually migrate (=activate) must be called
        $this->db->expects($this->once())->method('ConfirmUser')->with($this->user, 'encrypted', 'new@mail.com', true);          
        
        $this->cuh->HandleRequest($this->db);

        // and property must have been update
        $this->assertEquals('code', $this->cuh->GetCode());

        // must return something non-empty
        $this->assertTrue(strlen($this->cuh->GetConfirmText()) > 0);
        $this->assertStringContainsString('Migration', $this->cuh->GetConfirmText());
        $this->assertTrue(strlen($this->cuh->GetSuccessText()) > 0);
        $this->assertStringContainsString('Migration', $this->cuh->GetSuccessText());
    }

    public function testGetType() : void
    {
        $this->assertEquals(ConfirmHandler::VALUE_TYPE_CONFIRM_USER, $this->cuh->GetType());
    }
}