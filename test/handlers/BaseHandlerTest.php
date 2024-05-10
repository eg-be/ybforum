<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../../src/handlers/BaseHandler.php';

/**
 * No Database stuff required
 */
final class BaseHandlerTest extends TestCase
{
    public static function providerValidateClientIpValue() : array 
    {
        return array(
            // test         // fail
            [null,          true], // just the nul case is actually important
            ["",            true], 
            ["1.2.3.4.5",   true],
            ["foobar",      true],
            ["1.2.3.4",     false],
            ["::1",         false]
        );
    }

    #[DataProvider('providerValidateClientIpValue')]
    public function testValidateClientIpValue(?string $value, bool $fail) {
        if($fail) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage(BaseHandler::MSG_INVALID_CLIENT_IPADDRESS);
            $this->expectExceptionCode(BaseHandler::MSGCODE_BAD_PARAM);
        }
        $res = BaseHandler::ValidateClientIpValue($value);
        $this->assertNull($res);
    }

    public static function providerInvalidEmailValues() : array 
    {
        return array(
            // test     // msg              // fail
            [null,      null,               true],
            [null,      "Cannot be null",   true],
            ["nomail@", null,               true],
            ["nomail@", "nope",             true],
            ["foo@bar", null,               true],
            ["foo@b.c", null,               false]
        );
    }

    #[DataProvider('providerInvalidEmailValues')]
    public function testValidateEmailValueFail(?string $value, ?string $errMsg, bool $fail) {
        if($fail) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionCode(BaseHandler::MSGCODE_BAD_PARAM);
            if(is_null($errMsg)) {
                $this->expectExceptionMessage(BaseHandler::MSG_EMAIL_INVALID);
            } else {
                $this->expectExceptionMessage($errMsg);
            }
        }
        $res = BaseHandler::ValidateEmailValue($value, $errMsg);
        $this->assertNull($res);
    }

    public function testValidateEmailValueValid() {
        BaseHandler::ValidateEmailValue("foo@bar.cc");
        $this->assertTrue(true); // just a dummy
    }

    public static function providerBlacklistEmail() : array
    {
        return array(
            // mail         // exactly   // regex
            ['exactly@m.c', true,       false],
            ['regex@m.c',   false,      true],
            ['both@m.c',    true,       true],
            ['not@m.c',     false,      false]
        );
    }

    #[DataProvider('providerBlacklistEmail')]
    public function testValidateEmailAgainstBlacklist(string $mail, bool $exactly, bool $regex) {
        $db = $this->createMock('ForumDb');
/*        $db->method('IsEmailOnBlacklistExactly')->willReturnMap([
            ['exactly@m.c', 'is_on_blacklist_exactly'],
            ['both@m.c', 'is_on_blacklist_exactly'],
        ]);*/
        $db->method('IsEmailOnBlacklistExactly')->willReturnCallback(
            function() use ($exactly) {
                if($exactly)
                    return "is_on_blacklist_exactly";
                return false;
        });
        $db->method('IsEmailOnBlacklistRegex')->willReturnCallback(
            function() use ($regex) {
                if($regex)
                    return "is_on_blacklist_regex";
                return false;
        });
        $logger = $this->createMock('Logger');
        if($exactly || $regex) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionCode(BaseHandler::MSGCODE_BAD_PARAM);
            if($exactly) {
                $logger->expects($this->once())->method('LogMessage')
                    ->with(LogType::LOG_OPERATION_FAILED_EMAIL_BLACKLISTED);
            } else {
                $logger->expects($this->once())->method('LogMessage')
                    ->with(LogType::LOG_OPERATION_FAILED_EMAIL_REGEX_BLACKLISTED);
            }
//            $logger->expects($this->once())->method('LogMessage'); // a log-entry must be created
//            $logger->expects($this->exactly(1))->method('LogMessage'); // a log-entry must be created
        }
        $res = BaseHandler::ValidateEmailAgainstBlacklist($mail, $db, $logger);
        $this->assertNull($res);
    }

    public static function providerValidateHttpUrlValue() : array
    {
        return array(
            // url                      // path     // msg      // fail
            [null,                      null,       null,       true],
            [null,                      null,       'null-msg', true],
            ['no-url',                  null,       null,       true],
            ['no-url',                  null,       'my-msg',   true],
            ['http://no-path',          true,       null,       true],
            ['http://no-path',          true,       'my-msg',   true],
            ['ssh://wrong-protocol',    null,       null,       true],
            ['ssh://wrong-protocol',    null,       'my-msg',   true],
            ['http://valid',            false,      null,       false],
            ['http://valid/foobar',     true,       null,       false],
            ['https://valid',           false,      null,       false],
            ['https://valid/foobar',    true,       null,       false],
        );
    }

    #[DataProvider('providerValidateHttpUrlValue')]
    public function testValidateHttpUrlValue(?string $value, ?bool $requiresPath, ?string $errMsg, bool $fail) {
        if($fail) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionCode(BaseHandler::MSGCODE_BAD_PARAM);
            if($errMsg) {
                $this->expectExceptionMessage($errMsg);
            } else {
                $this->expectExceptionMessage(BaseHandler::MSG_HTTPURL_INVALID);
            }
        }
        $res = null;
        if(is_null($requiresPath)) {
            $res = BaseHandler::ValidateHttpUrlValue($value, $errMsg);
        } else {
            $res = BaseHandler::ValidateHttpUrlValue($value, $errMsg, $requiresPath);
        }
        $this->assertNull($res);
    }

    public function testValidateIntParam() {
        $errMsg = 'failing for null';
        // dont fail for non-null
        BaseHandler::ValidateIntParam(1313, $errMsg);
        // fail for null
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(BaseHandler::MSGCODE_BAD_PARAM);
        $this->expectExceptionMessage($errMsg);
        BaseHandler::ValidateIntParam(null, $errMsg);
    }

    public static function providerValidateStringParam() : array
    {
        return array(
            // value            // min  // fail
            [null,              0,      true],
            ['',                0,      true],
            [' ',               0,      true],
            ['abc',             0,      false],
            ['tooShort',        9,      true],
            ['o',               1,      false],
            ['enough',          1,      false]
        );
    }    

    #[DataProvider('providerValidateStringParam')]
    public function testValidateStringParam(?string $value, int $minLength, bool $fail) {
        $errMsg = 'this is not ok';
        if($fail) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionCode(BaseHandler::MSGCODE_BAD_PARAM);
            $this->expectExceptionMessage($errMsg);
        }
        $res = BaseHandler::ValidateStringParam($value, $errMsg, $minLength);
        $this->assertNull($res);
    }


    public static function providerReadClientIpParam() : array
    {
        return array(
            // value            // fail
            ['::1',             false],
            ['192.168.1.1',     false],
            ['',                true],
            [null,              true],
        );
    }        
    #[DataProvider('providerReadClientIpParam')]
    public function testReadClientIpParam(?string $value, bool $fail) {
        $_SERVER['REMOTE_ADDR'] = $value;
        $filtered = BaseHandler::ReadClientIpParam();
        if($fail) {
            $this->assertNull($filtered);
        } else {
            $this->assertSame($value, $filtered);
        }
    }
}