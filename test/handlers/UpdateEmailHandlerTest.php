<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../../src/handlers/UpdateEmailHandler.php';

/**
 * No Database stuff required
 */
final class UpdateEmailHandlerTest extends TestCase
{
    // required mocks our handler under test depends on
    private ForumDb $db;
    private Mailer $mailer;
    private User $user;

    // our actuall handler to test
    private UpdateEmailHandler $ueh;

    protected function setUp(): void
    {
        $this->db = $this->createMock(ForumDb::class);
        $this->mailer = $this->createMock(Mailer::class);
        $this->user = $this->createMock(User::class);
        $this->user->method('GetNick')->willReturn('foo');
        $this->user->method('GetEmail')->willReturn('foo@bar.com');
        $this->ueh = new UpdateEmailHandler($this->user);
        $this->ueh->SetMailer($this->mailer);
        // dont know why we need to set this here, as it is already defined in bootstrap.php
        $_SERVER['REMOTE_ADDR'] = '13.13.13.13';
        // must always reset all previously set $_POST entries
        $_POST = array();
    }

    public function testUpdateEmail_failsIfNewEmailIsSameAsOld()
    {
        $_POST[UpdateEmailHandler::PARAM_NEWEMAIL] = 'foo@bar.com';

//        $this->db->method('LoadUserByEmail')->with('foo@bar.com')->willReturn($this->user);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(UpdateEmailHandler::MSG_EMAIL_NOT_DIFFERENT);
        $this->expectExceptionCode(UpdateEmailHandler::MSGCODE_BAD_PARAM);

        $this->ueh->HandleRequest($this->db);
    }        

    public function testUpdateEmail_failsIfNewMailUsedInOtherAccount()
    {
        $_POST[UpdateEmailHandler::PARAM_NEWEMAIL] = 'used@by-someone-else.com';

        $OtherUser = $this->createMock(User::class);
        $this->db->method('LoadUserByEmail')->with('used@by-someone-else.com')->willReturn($OtherUser);
                
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(UpdateEmailHandler::MSG_EMAIL_NOT_UNIQUE);
        $this->expectExceptionCode(UpdateEmailHandler::MSGCODE_BAD_PARAM);

        $this->ueh->HandleRequest($this->db);
    }

    public function testUpdateEmail()
    {
        $_POST[UpdateEmailHandler::PARAM_NEWEMAIL] = 'new@bar.com';

        $this->db->method('RequestUpdateEmailCode')->with($this->user, 'new@bar.com')->willReturn('confirm-code');
        $this->mailer->method('SendUpdateEmailConfirmMessage')->with('new@bar.com', 'foo', 'confirm-code')->willReturn(true);

        // expect that the db is called with the correct params
        $this->db->expects($this->once())->method('RequestUpdateEmailCode')
            ->with($this->user, 'new@bar.com');

        // and the mailer to actually send the mail
        $this->mailer->expects($this->once())->method('SendUpdateEmailConfirmMessage')
            ->with('new@bar.com', 'foo', 'confirm-code');                

        $this->ueh->HandleRequest($this->db);
    }

    public function test_removeUpdateEmailCodeIfMailingFails()
    {
        $_POST[UpdateEmailHandler::PARAM_NEWEMAIL] = 'new@bar.com';

        $this->db->method('RequestUpdateEmailCode')->with($this->user, 'new@bar.com')->willReturn('confirm-code');
        $this->mailer->method('SendUpdateEmailConfirmMessage')->willReturn(false);

        // expect that the db is called with the correct params
        $this->db->expects($this->once())->method('RequestUpdateEmailCode')
            ->with($this->user, 'new@bar.com');

        // and the mailer to actually send the mail
        $this->mailer->expects($this->once())->method('SendUpdateEmailConfirmMessage')
            ->with('new@bar.com', 'foo', 'confirm-code');

        // expect that the db is requested to remove the created code again
        $this->db->expects($this->once())->method('RemoveUpdateEmailCode')
            ->with($this->user);

        // before failing
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(UpdateEmailHandler::MSG_SENDING_CONFIRMMAIL_FAILED);
        $this->expectExceptionCode(UpdateEmailHandler::MSGCODE_INTERNAL_ERROR);

        $this->ueh->HandleRequest($this->db);
    }    
}