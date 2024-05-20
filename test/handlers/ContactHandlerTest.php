<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../../src/handlers/ContactHandler.php';

/**
 * No Database stuff required
 */
final class ContactHandlerTest extends TestCase
{
    // required mocks our handler under test depends on
    private ForumDb $db;
    private Mailer $mailer;
    private Logger $logger;

    // our actuall handler to test
    private ContactHandler $ch;

    protected function setUp(): void
    {
        $this->db = $this->createMock(ForumDb::class);
        $this->mailer = $this->createMock(Mailer::class);
        $this->logger = $this->createMock(Logger::class);
        $this->ch = new ContactHandler();
        $this->ch->SetMailer($this->mailer);
        $this->ch->SetLogger($this->logger);
    }

    public function testConstruct()
    {
        $this->assertNull($this->$ch->GetEmail());
        $this->assertNull($this->$ch->GetEmailRepeat());
        $this->assertNull($this->$ch->GetMsg());
    }

    public function testEmailsMustMatch()
    {
        $_POST[ContactHandler::PARAM_MSG] = 'hello';
        $_POST[ContactHandler::PARAM_EMAIL] = 'a@bar.com';
        $_POST[ContactHandler::PARAM_EMAIL_REPEAT] = 'b@bar.com';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ContactHandler::MSG_EMAIL_DO_NOT_MATCH);
        $this->expectExceptionCode(ContactHandler::MSGCODE_BAD_PARAM);
        $this->ch->HandleRequest($this->db);
    }

    public function testMsgNotEmpty()
    {
        $_POST[ContactHandler::PARAM_MSG] = '';
        $_POST[ContactHandler::PARAM_EMAIL] = 'a@bar.com';
        $_POST[ContactHandler::PARAM_EMAIL_REPEAT] = 'a@bar.com';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(ContactHandler::MSG_EMPTY);
        $this->expectExceptionCode(ContactHandler::MSGCODE_BAD_PARAM);
        $this->ch->HandleRequest($this->db);
    }

    public function testNonValidParamValuesStored()
    {
        // When trying to handle, and param validation fails,
        // the previously tested values must still be present
        $_POST[ContactHandler::PARAM_MSG] = 'hello';
        $_POST[ContactHandler::PARAM_EMAIL] = 'a@bar.com';
        $_POST[ContactHandler::PARAM_EMAIL_REPEAT] = 'b@bar.com';
        try 
        {
            $this->ch->HandleRequest($this->db);
            $this->assertTrue(false); // must never be reached
        }catch(InvalidArgumentException $ex) 
        { 
            // nothing to do here
        }
        // values must be readable now
        $this->assertSame('a@bar.com', $this->$ch->GetEmail());
        $this->assertSame('b@bar.com', $this->$ch->GetEmailRepeat());
        $this->assertSame('hello', $this->$ch->GetMsg());
    }

    public function testSendMsg()
    {
        // setup with correct params: matching mails and non-empty msg
        // must have a log entry and the mailer must have been called
        $_POST[ContactHandler::PARAM_MSG] = 'hello';
        $_POST[ContactHandler::PARAM_EMAIL] = 'a@bar.com';
        $_POST[ContactHandler::PARAM_EMAIL_REPEAT] = 'a@bar.com';

        // make the db return a fake admin-user
        $admin = $this->createMock(User::class);
        $admin->method('GetEmail')->willReturn('admin@1898.ch');
        $this->db->method('GetAdminUsers')->willReturn(array($admin));

        // 
        $this->mailer->method('SendAdminContactMessage')->willReturn(true);

        $this->ch->HandleRequest($this->db);
    }
}