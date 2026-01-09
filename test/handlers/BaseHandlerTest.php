<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../../src/handlers/BaseHandler.php';

/**
 * No Database stuff required
 */
final class BaseHandlerTest extends TestCase
{
    public static function providerValidateClientIpValue(): array
    {
        return [
            // test         // fail
            [null,          true], // just the nul case is actually important
            ["",            true],
            ["1.2.3.4.5",   true],
            ["foobar",      true],
            ["1.2.3.4",     false],
            ["::1",         false],
        ];
    }

    #[DataProvider('providerValidateClientIpValue')]
    public function testValidateClientIpValue(?string $value, bool $fail): void
    {
        if ($fail) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage(BaseHandler::MSG_INVALID_CLIENT_IPADDRESS);
            $this->expectExceptionCode(BaseHandler::MSGCODE_BAD_PARAM);
        }
        $res = BaseHandler::ValidateClientIpValue($value);
        static::assertNull($res);
    }

    public static function providerInvalidEmailValues(): array
    {
        return [
            // test     // msg              // fail
            [null,      null,               true],
            [null,      "Cannot be null",   true],
            ["nomail@", null,               true],
            ["nomail@", "nope",             true],
            ["foo@bar", null,               true],
            ["foo@b.c", null,               false],
        ];
    }

    #[DataProvider('providerInvalidEmailValues')]
    public function testValidateEmailValueFail(?string $value, ?string $errMsg, bool $fail): void
    {
        if ($fail) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionCode(BaseHandler::MSGCODE_BAD_PARAM);
            if (is_null($errMsg)) {
                $this->expectExceptionMessage(BaseHandler::MSG_EMAIL_INVALID);
            } else {
                $this->expectExceptionMessage($errMsg);
            }
        }
        $res = BaseHandler::ValidateEmailValue($value, $errMsg);
        static::assertNull($res);
    }

    public function testValidateEmailValueValid(): void
    {
        BaseHandler::ValidateEmailValue("foo@bar.cc");
        static::assertTrue(true); // just a dummy
    }

    public static function providerBlacklistEmail(): array
    {
        return [
            // mail         // exactly   // regex
            ['exactly@m.c', true,       false],
            ['regex@m.c',   false,      true],
            ['both@m.c',    true,       true],
            ['not@m.c',     false,      false],
        ];
    }

    #[DataProvider('providerBlacklistEmail')]
    public function testValidateEmailAgainstBlacklist(string $mail, bool $exactly, bool $regex): void
    {
        $db = static::createStub(ForumDb::class);
        $db->method('IsEmailOnBlacklistExactly')->willReturnCallback(
            function () use ($exactly) {
                if ($exactly) {
                    return "is_on_blacklist_exactly";
                }
                return false;
            }
        );
        $db->method('IsEmailOnBlacklistRegex')->willReturnCallback(
            function () use ($regex) {
                if ($regex) {
                    return "is_on_blacklist_regex";
                }
                return false;
            }
        );

        $logger = static::createStub(Logger::class);
        if ($exactly || $regex) {
            $logger = $this->createMock(Logger::class);
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionCode(BaseHandler::MSGCODE_BAD_PARAM);
            if ($exactly) {
                $logger->expects($this->once())->method('LogMessage')
                    ->with(LogType::LOG_OPERATION_FAILED_EMAIL_BLACKLISTED);
            } else {
                $logger->expects($this->once())->method('LogMessage')
                    ->with(LogType::LOG_OPERATION_FAILED_EMAIL_REGEX_BLACKLISTED);
            }
        }
        $res = BaseHandler::ValidateEmailAgainstBlacklist($mail, $db, $logger);
        static::assertNull($res);
    }

    public static function providerValidateHttpUrlValue(): array
    {
        return [
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
        ];
    }

    #[DataProvider('providerValidateHttpUrlValue')]
    public function testValidateHttpUrlValue(?string $value, ?bool $requiresPath, ?string $errMsg, bool $fail): void
    {
        if ($fail) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionCode(BaseHandler::MSGCODE_BAD_PARAM);
            if ($errMsg) {
                $this->expectExceptionMessage($errMsg);
            } else {
                $this->expectExceptionMessage(BaseHandler::MSG_HTTPURL_INVALID);
            }
        }
        $res = null;
        if (is_null($requiresPath)) {
            $res = BaseHandler::ValidateHttpUrlValue($value, $errMsg);
        } else {
            $res = BaseHandler::ValidateHttpUrlValue($value, $errMsg, $requiresPath);
        }
        static::assertNull($res);
    }

    public function testValidateIntParam(): void
    {
        $errMsg = 'failing for null';
        // dont fail for non-null
        BaseHandler::ValidateIntParam(1313, $errMsg);
        // fail for null
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(BaseHandler::MSGCODE_BAD_PARAM);
        $this->expectExceptionMessage($errMsg);
        BaseHandler::ValidateIntParam(null, $errMsg);
    }

    public static function providerValidateStringParam(): array
    {
        return [
            // value            // min  // fail
            [null,              0,      true],
            ['',                0,      true],
            [' ',               0,      true],
            ['abc',             0,      false],
            ['tooShort',        9,      true],
            ['o',               1,      false],
            ['enough',          1,      false],
        ];
    }

    #[DataProvider('providerValidateStringParam')]
    public function testValidateStringParam(?string $value, int $minLength, bool $fail): void
    {
        $errMsg = 'this is not ok';
        if ($fail) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionCode(BaseHandler::MSGCODE_BAD_PARAM);
            $this->expectExceptionMessage($errMsg);
        }
        $res = BaseHandler::ValidateStringParam($value, $errMsg, $minLength);
        static::assertNull($res);
    }


    public static function providerReadClientIpParam(): array
    {
        return [
            // value            // fail
            ['::1',             false],
            ['192.168.1.1',     false],
            ['',                true],
            [null,              true],
        ];
    }
    #[DataProvider('providerReadClientIpParam')]
    public function testReadClientIpParam(?string $value, bool $fail): void
    {
        $_SERVER['REMOTE_ADDR'] = $value;
        $filtered = BaseHandler::ReadClientIpParam();
        if ($fail) {
            static::assertNull($filtered);
        } else {
            static::assertSame($value, $filtered);
        }
    }

    public static function providerReadEmailParam(): array
    {
        return [
            // value            // fail
            ['eg@mail.com',     false],
            ['',                true],
            [null,              true],
        ];
    }
    #[DataProvider('providerReadEmailParam')]
    public function testReadEmailParam(?string $value, bool $fail): void
    {
        $paramName = 'test';
        $_POST[$paramName] = $value;
        $filtered = BaseHandler::ReadEmailParam($paramName);
        if ($fail) {
            static::assertNull($filtered);
        } else {
            static::assertSame($value, $filtered);
        }
    }

    public function testReadEmailParamNotExisting(): void
    {
        $paramName = 'doesnotexist';
        $filtered = BaseHandler::ReadEmailParam($paramName);
        static::assertNull($filtered);
    }

    public static function providerReadIntParam(): array
    {
        return [
            // value            // fail
            ['foobar',          true],
            ['',                true],
            [null,              true],
            ['0',               false],
            ['1230',            false],
        ];
    }
    #[DataProvider('providerReadIntParam')]
    public function testReadIntParam(?string $value, bool $fail): void
    {
        $paramName = 'test';
        $_POST[$paramName] = $value;
        $filtered = BaseHandler::ReadIntParam($paramName);
        if ($fail) {
            static::assertNull($filtered);
        } else {
            static::assertEquals($value, $filtered);
        }
    }

    public function testReadIntParamNotExistring(): void
    {
        $paramName = 'doesnotexist';
        $filtered = BaseHandler::ReadIntParam($paramName);
        static::assertNull($filtered);
    }

    public static function providerReadStringParam(): array
    {
        return [
            // value            // fail
            ['foobar',          false],
            ['',                true],
            [null,              true],
            ['0',               false],
            ['1230',            false],
        ];
    }
    #[DataProvider('providerReadStringParam')]
    public function testReadStringParam(?string $value, bool $fail): void
    {
        $paramName = 'test';
        $_POST[$paramName] = $value;
        $filtered = BaseHandler::ReadStringParam($paramName);
        if ($fail) {
            static::assertNull($filtered);
        } else {
            static::assertEquals($value, $filtered);
        }
    }

    public function testReadStringParamNotExistring(): void
    {
        $paramName = 'doesnotexist';
        $filtered = BaseHandler::ReadStringParam($paramName);
        static::assertNull($filtered);
    }

    public function testReadRawParamFromGet(): void
    {
        $paramName = 'test';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET[$paramName] = 'value';
        $value = BaseHandler::ReadRawParamFromGetOrPost($paramName);
        static::assertEquals('value', $value);
    }

    public function testReadRawParamFromPost(): void
    {
        $paramName = 'test';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST[$paramName] = 'value';
        $value = BaseHandler::ReadRawParamFromGetOrPost($paramName);
        static::assertEquals('value', $value);
    }

    public function testReadRawParamFromGetOrPost_returnNullForNotExisting(): void
    {
        $paramName = 'foobar';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $value = BaseHandler::ReadRawParamFromGetOrPost($paramName);
        static::assertNull($value);
    }
}
