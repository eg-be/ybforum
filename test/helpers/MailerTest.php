<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../../src/helpers/Mailer.php';


/**
 * No Database stuff required
 */
final class MailerTest extends TestCase
{
    // required mocks our handler under test depends on
    private Logger $logger;

    // our actuall Mailer to test
    private Mailer $mailer;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->mailer = new Mailer();
        //$this->rph->SetLogger($this->logger);        
    }

    public function testConstruct() : void
    {
        $mailFrom = YbForumConfig::MAIL_FROM_NAME . ' <' . YbForumConfig::MAIL_FROM . '>';
        $this->assertEquals($mailFrom, $this->mailer->getMailFrom());
        $this->assertEquals(YbForumConfig::MAIL_FROM, $this->mailer->getReturnPath());
        $this->assertEquals(YbForumConfig::MAIL_ALL_BCC, $this->mailer->getAllMailBcc());
        $this->assertEquals('text/plain; charset=utf-8', $this->mailer->getContentType());

    }
}