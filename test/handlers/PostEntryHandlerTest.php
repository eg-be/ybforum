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
    private ConfigWrapper $config;

    // our actuall handler to test
    private PostEntryHandler $peh;

    protected function setUp(): void
    {
        $this->db = $this->createMock(ForumDb::class);
        $this->logger = $this->createMock(Logger::class);
        $this->config = $this->createMock(ConfigWrapper::class);
        $this->peh = new PostEntryHandler();
        $this->peh->SetLogger($this->logger);
        $this->peh->SetConfigWrapper($this->config);
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

    public function testValidateImgUrl() 
    {
        // test that we really validate the passed img url as a http-url
        $_POST[PostEntryHandler::PARAM_PARENTPOSTID] = 0;
        $_POST[PostEntryHandler::PARAM_NICK] = 'foo';
        $_POST[PostEntryHandler::PARAM_PASS] = 'bar';
        $_POST[PostEntryHandler::PARAM_TITLE] = 'abc';
        $_POST[PostEntryHandler::PARAM_IMGURL] = 'ftp://foobar/bla.jpg';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Der Wert ftp://foobar/bla.jpg ist keine gültige Bild URL.');
        $this->expectExceptionCode(PostEntryHandler::MSGCODE_BAD_PARAM);
        $this->peh->HandleRequest($this->db);
    }

    public function testPostEntry_migrationRequired()
    {
        // test that if a user needs migration, the corresponding exception is thrown
        $_POST[PostEntryHandler::PARAM_PARENTPOSTID] = 0;
        $_POST[PostEntryHandler::PARAM_NICK] = 'foo';
        $_POST[PostEntryHandler::PARAM_PASS] = 'bar';
        $_POST[PostEntryHandler::PARAM_TITLE] = 'abc';

        // make the db return a user that needs to migrate
        $user = $this->createMock(User::class);
        $user->method('NeedsMigration')->willReturn(true);
        $this->db->method('AuthUser2')->with('foo', 'bar')->willReturn(array(
            ForumDb::USER_KEY => $user,
            ForumDb::AUTH_FAIL_REASON_KEY => null
        ));

        // expect that the logger is called with the correct params when failing
        $this->logger->expects($this->once())->method('LogMessageWithUserId')
            ->with(LogType::LOG_OPERATION_FAILED_MIGRATION_REQUIRED, $user);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(PostEntryHandler::MSG_MIGRATION_REQUIRED);
        $this->expectExceptionCode(PostEntryHandler::MSGCODE_AUTH_FAIL);
        $this->peh->HandleRequest($this->db);
    }

    public function testPostEntry_authFailed()
    {
        // test that if authentication fails, the corresponding exception is thrown
        $_POST[PostEntryHandler::PARAM_PARENTPOSTID] = 0;
        $_POST[PostEntryHandler::PARAM_NICK] = 'foo';
        $_POST[PostEntryHandler::PARAM_PASS] = 'bar';
        $_POST[PostEntryHandler::PARAM_TITLE] = 'abc';

        // fail auth
        $this->db->method('AuthUser2')->with('foo', 'bar')->willReturn(array(
            ForumDb::USER_KEY => null,
            ForumDb::AUTH_FAIL_REASON_KEY => ForumDb::AUTH_FAIL_REASON_PASSWORD_INVALID
        ));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(PostEntryHandler::MSGCODE_AUTH_FAIL);
        $this->peh->HandleRequest($this->db);
    }

    public static function providerAuthFailReasons() : array 
    {
        return array(
            [ForumDb::AUTH_FAIL_REASON_PASSWORD_INVALID, PostEntryHandler::MSG_AUTH_FAIL_PASSWORD_INVALID],
            [ForumDb::AUTH_FAIL_REASON_USER_IS_INACTIVE, PostEntryHandler::MSG_AUTH_FAIL_USER_IS_INACTIVE],
            [ForumDb::AUTH_FAIL_REASON_USER_IS_DUMMY, PostEntryHandler::MSG_AUTH_FAIL_USER_IS_DUMMY],
            [ForumDb::AUTH_FAIL_REASON_NO_SUCH_USER, PostEntryHandler::MSG_AUTH_FAIL_NO_SUCH_USER]
        );
    }

    #[DataProvider('providerAuthFailReasons')]    
    public function testPostEntry_authFailedExtendedLogIsCalledForAllReasones(int $authFailReason, string $authFailMessage)
    {
        // assume: LOG_EXT_POST_DATA_ON_AUTH_FAILURE is enabled
        // assume: LOG_AUTH_FAIL_NO_SUCH_USER is enabled
        // test that the logging of extended messages is called for all reasons

        $_POST[PostEntryHandler::PARAM_PARENTPOSTID] = 0;
        $_POST[PostEntryHandler::PARAM_NICK] = 'foo';
        $_POST[PostEntryHandler::PARAM_PASS] = 'bar';
        $_POST[PostEntryHandler::PARAM_TITLE] = 'abc';        

        // enable the extended logging
        $this->config->method('getLogExtendedPostDataOnAuthFailure')->willReturn(true);
        $this->config->method('getLogAuthFailNoSuchUser')->willReturn(true);

        // fail auth
        $this->db->method('AuthUser2')->with('foo', 'bar')->willReturn(array(
            ForumDb::USER_KEY => null, 
            ForumDb::AUTH_FAIL_REASON_KEY => $authFailReason
        ));        

        // verify logger gets called
        $this->logger->expects($this->once())->method('LogMessage')
            ->with(LogType::LOG_EXT_POST_DISCARDED, $authFailMessage);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(PostEntryHandler::MSGCODE_AUTH_FAIL);
        
        $this->peh->HandleRequest($this->db);
    }

    #[DataProvider('providerAuthFailReasons')]    
    public function testPostEntry_authFailedExtendedLogIsCalledForAllReasonesExceptNoSuchUser(int $authFailReason, string $authFailMessage)
    {
        // assume: LOG_EXT_POST_DATA_ON_AUTH_FAILURE is enabled
        // assume: LOG_AUTH_FAIL_NO_SUCH_USER is disabled
        // test that the logging of extended messages is called for all reasons,
        // except for AUTH_FAIL_REASON_NO_SUCH_USER

        $_POST[PostEntryHandler::PARAM_PARENTPOSTID] = 0;
        $_POST[PostEntryHandler::PARAM_NICK] = 'foo';
        $_POST[PostEntryHandler::PARAM_PASS] = 'bar';
        $_POST[PostEntryHandler::PARAM_TITLE] = 'abc';        

        // enable the extended logging
        $this->config->method('getLogExtendedPostDataOnAuthFailure')->willReturn(true);
        $this->config->method('getLogAuthFailNoSuchUser')->willReturn(false);

        // fail auth
        $this->db->method('AuthUser2')->with('foo', 'bar')->willReturn(array(
            ForumDb::USER_KEY => null, 
            ForumDb::AUTH_FAIL_REASON_KEY => $authFailReason
        ));        

        // verify logger gets called / or not called
        if($authFailReason === ForumDb::AUTH_FAIL_REASON_NO_SUCH_USER)
        {
            $this->logger->expects($this->never())->method('LogMessage');
        }
        else
        {
            $this->logger->expects($this->once())->method('LogMessage')
                ->with(LogType::LOG_EXT_POST_DISCARDED, $authFailMessage);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(PostEntryHandler::MSGCODE_AUTH_FAIL);
        
        $this->peh->HandleRequest($this->db);
    }

    #[DataProvider('providerAuthFailReasons')]    
    public function testPostEntry_authFailedExtendedLogIsNeverCalled(int $authFailReason, string $authFailMessage)
    {
        // assume: LOG_EXT_POST_DATA_ON_AUTH_FAILURE is disabled
        // assume: LOG_AUTH_FAIL_NO_SUCH_USER is enabled
        // test that the logging of extended messages is never called

        $_POST[PostEntryHandler::PARAM_PARENTPOSTID] = 0;
        $_POST[PostEntryHandler::PARAM_NICK] = 'foo';
        $_POST[PostEntryHandler::PARAM_PASS] = 'bar';
        $_POST[PostEntryHandler::PARAM_TITLE] = 'abc';        

        // enable the extended logging
        $this->config->method('getLogExtendedPostDataOnAuthFailure')->willReturn(false);
        $this->config->method('getLogAuthFailNoSuchUser')->willReturn(true);

        // fail auth
        $this->db->method('AuthUser2')->with('foo', 'bar')->willReturn(array(
            ForumDb::USER_KEY => null, 
            ForumDb::AUTH_FAIL_REASON_KEY => $authFailReason
        ));        

        // verify logger gets not called
        $this->logger->expects($this->never())->method('LogMessage');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(PostEntryHandler::MSGCODE_AUTH_FAIL);
        
        $this->peh->HandleRequest($this->db);
    }    

    public function testPostEntry_paramValuesStored()
    {
        // test that if posting fails, the user-entered values are still available in the handler
        $_POST[PostEntryHandler::PARAM_PARENTPOSTID] = 777;
        $_POST[PostEntryHandler::PARAM_NICK] = 'foo';
        $_POST[PostEntryHandler::PARAM_PASS] = 'bar';
        $_POST[PostEntryHandler::PARAM_TITLE] = 'abc';
        $_POST[PostEntryHandler::PARAM_CONTENT] = 'hello wold';
        $_POST[PostEntryHandler::PARAM_EMAIL] = 'hans@wurst.com';
        $_POST[PostEntryHandler::PARAM_LINKURL] = 'http://foo.bar.com';
        $_POST[PostEntryHandler::PARAM_LINKTEXT] = 'foo-bar-link';
        $_POST[PostEntryHandler::PARAM_IMGURL] = 'https://funny.com/img.jpg';

        // fail auth and read-back the values
        $this->db->method('AuthUser2')->with('foo', 'bar')->willReturn(array(
            ForumDb::USER_KEY => null,
            ForumDb::AUTH_FAIL_REASON_KEY => ForumDb::AUTH_FAIL_REASON_PASSWORD_INVALID
        ));
        try 
        {     
            $this->peh->HandleRequest($this->db);
        }
        catch(InvalidArgumentException $ex) {}
        
        $this->assertSame('abc', $this->peh->GetTitle());
        $this->assertSame('foo', $this->peh->GetNick());
        $this->assertSame('bar', $this->peh->GetPassword());
        $this->assertSame('hello wold', $this->peh->GetContent());
        $this->assertSame('hans@wurst.com', $this->peh->GetEmail());
        $this->assertSame('http://foo.bar.com', $this->peh->GetLinkUrl());
        $this->assertSame('foo-bar-link', $this->peh->GetLinkText());
        $this->assertSame('https://funny.com/img.jpg', $this->peh->GetImgUrl());
    }

    public function testPostEntry_newThread()
    {
        // test that creating a new thread works
        $_POST[PostEntryHandler::PARAM_PARENTPOSTID] = 0;
        $_POST[PostEntryHandler::PARAM_NICK] = 'foo';
        $_POST[PostEntryHandler::PARAM_PASS] = 'bar';
        $_POST[PostEntryHandler::PARAM_TITLE] = 'title';
        $_POST[PostEntryHandler::PARAM_CONTENT] = 'content';
        $_POST[PostEntryHandler::PARAM_EMAIL] = 'hans@wurst.com';
        $_POST[PostEntryHandler::PARAM_LINKURL] = 'http://foo.bar.com';
        $_POST[PostEntryHandler::PARAM_LINKTEXT] = 'foo-bar-link';
        $_POST[PostEntryHandler::PARAM_IMGURL] = 'https://funny.com/img.jpg';

        // make the db return a valid user
        $user = $this->createMock(User::class);
        $user->method('NeedsMigration')->willReturn(false);
        $this->db->method('AuthUser2')->with('foo', 'bar')->willReturn(array(
            ForumDb::USER_KEY => $user,
            ForumDb::AUTH_FAIL_REASON_KEY => null
        ));

        // expect that the db is called with the correct params
        $this->db->expects($this->once())->method('CreateThread')
            ->with($user, 'title', 'content', 'hans@wurst.com',
                    'http://foo.bar.com', 'foo-bar-link', 'https://funny.com/img.jpg',
                    '13.13.13.13'
        );
        $this->peh->HandleRequest($this->db);
    }

    public function testPostEntry_reply()
    {
        // test that creating a new thread works
        $_POST[PostEntryHandler::PARAM_PARENTPOSTID] = 777;
        $_POST[PostEntryHandler::PARAM_NICK] = 'foo';
        $_POST[PostEntryHandler::PARAM_PASS] = 'bar';
        $_POST[PostEntryHandler::PARAM_TITLE] = 'title';
        $_POST[PostEntryHandler::PARAM_CONTENT] = 'content';
        $_POST[PostEntryHandler::PARAM_EMAIL] = 'hans@wurst.com';
        $_POST[PostEntryHandler::PARAM_LINKURL] = 'http://foo.bar.com';
        $_POST[PostEntryHandler::PARAM_LINKTEXT] = 'foo-bar-link';
        $_POST[PostEntryHandler::PARAM_IMGURL] = 'https://funny.com/img.jpg';

        // make the db return a valid user
        $user = $this->createMock(User::class);
        $user->method('NeedsMigration')->willReturn(false);
        $this->db->method('AuthUser2')->with('foo', 'bar')->willReturn(array(
            ForumDb::USER_KEY => $user,
            ForumDb::AUTH_FAIL_REASON_KEY => null
        ));

        // expect that the db is called with the correct params
        $this->db->expects($this->once())->method('CreateReplay')
            ->with(777, $user, 'title', 'content', 'hans@wurst.com',
                    'http://foo.bar.com', 'foo-bar-link', 'https://funny.com/img.jpg',
                    '13.13.13.13'
        );
        $this->peh->HandleRequest($this->db);
    }    
}