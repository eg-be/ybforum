<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../../src/handlers/BaseHandler.php';

/**
 * No Database stuff required
 */
final class BaseHandlerTest extends TestCase
{
    public static function providerInvalidIpValues() : array 
    {
        return array(
            [null], // just the nul case is actually important
            [""], 
            ["1.2.3.4.5"],
            ["foobar"]
        );
    }

    #[DataProvider('providerInvalidIpValues')]
    public function testValidateClientIpValueFail(?string $value) {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(BaseHandler::MSG_INVALID_CLIENT_IPADDRESS);
        BaseHandler::ValidateClientIpValue($value);
    }

    public function testValidateClientIpValueValid() {
        BaseHandler::ValidateClientIpValue("1.2.3.4");
        BaseHandler::ValidateClientIpValue("::1");
        $this->assertTrue(true); // just a dummy
    }

    public static function providerInvalidEmailValues() : array 
    {
        return array(
            [null, null, BaseHandler::MSG_EMAIL_INVALID],
            [null, "Cannot be null", "Cannot be null"],
            ["nomail@", null, BaseHandler::MSG_EMAIL_INVALID],
            ["nomail@", "nope", "nope"],
            ["foo@bar", null, BaseHandler::MSG_EMAIL_INVALID],
        );
    }

    #[DataProvider('providerInvalidEmailValues')]
    public function testValidateEmailValueFail(?string $value, ?string $errMsg, string $expectedErr) {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedErr);
        BaseHandler::ValidateEmailValue($expectedErr, $errMsg);
    }

    public function testValidateEmailValueValid() {
        BaseHandler::ValidateEmailValue("foo@bar.cc");
        $this->assertTrue(true); // just a dummy
    }
}