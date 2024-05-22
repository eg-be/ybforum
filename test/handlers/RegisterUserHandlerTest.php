<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../../src/handlers/RegisterUserHandler.php';

/**
 * No Database stuff required
 */
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
        // must always reset all previously set $_POST entries
        $_POST = array();
    }

    public function testConstruct()
    {
        $this->assertNull($this->ruh->GetNick());
        $this->assertNull($this->ruh->GetEmail());
        $this->assertNull($this->ruh->GetRegMsg());
        $this->assertNull($this->ruh->GetPassword());
        $this->assertNull($this->ruh->GetConfirmPassword());
    }

    public static function providerTestValidateRequiredParams() : array 
    {
        return array(
            // NICK     // EMAIL        // PASS         // FAILURE
            [null,      'foo@bar.com',  '12345678',     RegisterUserHandler::MSG_NICK_TOO_SHORT], // nick not set
            ['foob',    'foo@bar.com',  '12345678',     RegisterUserHandler::MSG_NICK_TOO_SHORT], // nick too short
            ['foobar',  null,           '12345678',     RegisterUserHandler::MSG_EMAIL_INVALID],    // mail not set
            ['foobar',  'foo@bar.com',  null,           RegisterUserHandler::MSG_PASSWORD_TOO_SHORT],    // pass not set
            ['foobar',  'foo@bar.com',  '1234567',      RegisterUserHandler::MSG_PASSWORD_TOO_SHORT]    // pass too short
        );
    }

    #[DataProvider('providerTestValidateRequiredParams')]
    public function testValidateRequiredParams(?string $nick, ?string $email, ?string $password, string $failMessage)
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

    public function testPasswordsMustMatch()
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

    public function testRegisterUser_nickNotUnique()
    {
        $_POST[RegisterUserHandler::PARAM_NICK] = 'nickname';
        $_POST[RegisterUserHandler::PARAM_EMAIL] = 'a@bar.com';
        $_POST[RegisterUserHandler::PARAM_PASS] = 'password';
        $_POST[RegisterUserHandler::PARAM_CONFIRMPASS] = 'password';

        $this->assertTrue(false);
        // todo: re-write the static user code, so that it just forwards the call
        // to a method of the ForumDb, what will allow us mocking things
        // and then one day remove all the static stuff
        //$user = $this->createMock(User::class);
        //$user->method('LoadUserByNick')->willReturn($user);
        //$this->db->method('AuthUser')->with('foo', 'bar')->willReturn($user);

//        $this->expectException(InvalidArgumentException::class);
//        $this->expectExceptionMessage(RegisterUserHandler::MSG_NICK_NOT_UNIQUE);
//        $this->expectExceptionCode(RegisterUserHandler::MSGCODE_BAD_PARAM);
        //$this->ruh->HandleRequest($this->db);
    }
}