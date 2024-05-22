<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../../src/handlers/PostEntryHandler.php';

/**
 * No Database stuff required
 */
final class PostEntryHandlerTest extends TestCase
{
    // required mocks our handler under test depends on
    private ForumDb $db;
    private Logger $logger;

    // our actuall handler to test
    private PostEntryHandler $peh;

    protected function setUp(): void
    {
        $this->db = $this->createMock(ForumDb::class);
        $this->logger = $this->createMock(Logger::class);
        $this->peh = new PostEntryHandler();
        $this->peh->SetLogger($this->logger);
        // dont know why we need to set this here, as it is already defined in bootstrap.php
        $_SERVER['REMOTE_ADDR'] = '13.13.13.13';
        // must always reset all previously set $_POST entries
        $_POST = array();
    }

    public function testConstruct()
    {
        $this->assertNull($this->peh->GetTitle());
        $this->assertNull($this->peh->GetNick());
        $this->assertNull($this->peh->GetPassword());
        $this->assertNull($this->peh->GetContent());
        $this->assertNull($this->peh->GetEmail());
        $this->assertNull($this->peh->GetLinkUrl());
        $this->assertNull($this->peh->GetLinkText());
        $this->assertNull($this->peh->GetImgUrl());
        $this->assertNull($this->peh->GetParentPostId());
        $this->assertNull($this->peh->GetNewPostId());
    }

    public static function providerTestValidateRequiredParams() : array 
    {
        return array(
            // PARENT   // NICK         // PASS         // TITLE    // FAILURE
            [null,      'foo',          'bar',          'valid',    PostEntryHandler::MSG_GENERIC_INVALID],  // because no parentPostId set
            [0,         null,           'bar',          'valid',    PostEntryHandler::MSG_AUTH_FAIL],  // missing nick
            [0,         'foo',          null,           'valid',    PostEntryHandler::MSG_AUTH_FAIL],  // missing pass
            [0,         'foo',          'bar',          null,       PostEntryHandler::MSG_TITLE_TOO_SHORT],  // no title
            [0,         'foo',          'bar',          'ab',       PostEntryHandler::MSG_TITLE_TOO_SHORT]  // title too short
        );
    }

    #[DataProvider('providerTestValidateRequiredParams')]
    public function testValidateRequiredParams(?int $idParentPost, ?string $nick, ?string $pass, ?string $title, string $failMessage)
    {
        // test that we fail if required params are not set
        $_POST[PostEntryHandler::PARAM_PARENTPOSTID] = $idParentPost;
        $_POST[PostEntryHandler::PARAM_NICK] = $nick;
        $_POST[PostEntryHandler::PARAM_PASS] = $pass;
        $_POST[PostEntryHandler::PARAM_TITLE] = $title;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($failMessage);
        $this->expectExceptionCode(PostEntryHandler::MSGCODE_BAD_PARAM);
        $this->peh->HandleRequest($this->db);
    }

    public function testValidateEmail() 
    {
        // test that we really validate the passed email
        $_POST[PostEntryHandler::PARAM_PARENTPOSTID] = 0;
        $_POST[PostEntryHandler::PARAM_NICK] = 'foo';
        $_POST[PostEntryHandler::PARAM_PASS] = 'bar';
        $_POST[PostEntryHandler::PARAM_TITLE] = 'abc';
        $_POST[PostEntryHandler::PARAM_EMAIL] = 'foobar';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Der Wert foobar ist keine gültige Mailadresse.');
        $this->expectExceptionCode(PostEntryHandler::MSGCODE_BAD_PARAM);
        $this->peh->HandleRequest($this->db);
    }

    public function testValidateHttpUrl() 
    {
        // test that we really validate the passed http url
        $_POST[PostEntryHandler::PARAM_PARENTPOSTID] = 0;
        $_POST[PostEntryHandler::PARAM_NICK] = 'foo';
        $_POST[PostEntryHandler::PARAM_PASS] = 'bar';
        $_POST[PostEntryHandler::PARAM_TITLE] = 'abc';
        $_POST[PostEntryHandler::PARAM_LINKURL] = 'foobar';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Der Wert foobar ist kein gültiger Link');
        $this->expectExceptionCode(PostEntryHandler::MSGCODE_BAD_PARAM);
        $this->peh->HandleRequest($this->db);
    }

    public static function providerValidateHttpUrlAndTextRequired() : array 
    {
        return array(
            // LINKURL              // LINKTEXT 
            [null,                  'foo'],
            ['http://1898.ch',      null]
        );
    }    

    #[DataProvider('providerValidateHttpUrlAndTextRequired')]
    public function testValidateHttpUrlAndTextRequired(?string $linkUrl, ?string $linkTxt) 
    {
        // test that if either url or link-text is present, both values must be set
        $_POST[PostEntryHandler::PARAM_PARENTPOSTID] = 0;
        $_POST[PostEntryHandler::PARAM_NICK] = 'foo';
        $_POST[PostEntryHandler::PARAM_PASS] = 'bar';
        $_POST[PostEntryHandler::PARAM_TITLE] = 'abc';
        $_POST[PostEntryHandler::PARAM_LINKURL] = $linkUrl;
        $_POST[PostEntryHandler::PARAM_LINKTEXT] = $linkTxt;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Wird ein URL Link angegeben muss auch ein Linktext angegeben werden (und umgekehrt).');
        $this->expectExceptionCode(PostEntryHandler::MSGCODE_BAD_PARAM);
        $this->peh->HandleRequest($this->db);
    }    
}