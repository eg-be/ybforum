<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../../src/handlers/ConfirmHandlerFactory.php';

/**
 * No Database stuff required
 */
final class ConfirmHandlerFactoryTest extends TestCase
{
    public static function providerFactoryInstance(): array
    {
        return [
            [ConfirmHandler::VALUE_TYPE_CONFIRM_USER, ConfirmUserHandler::class],
            [ConfirmHandler::VALUE_TYPE_UPDATEEMAIL, ConfirmUpdateEmailHandler::class],
            [ConfirmHandler::VALUE_TYPE_RESETPASS, ConfirmResetPasswordHandler::class],
        ];
    }

    #[DataProvider('providerFactoryInstance')]
    public function testCreateHandlerFromPost(string $paramTypeValue, string $instanceType): void
    {
        $_POST[ConfirmHandler::PARAM_TYPE] = $paramTypeValue;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $handler = ConfirmHandlerFactory::createHandler();
        static::assertInstanceOf($instanceType, $handler);
    }

    #[DataProvider('providerFactoryInstance')]
    public function testCreateHandlerFromGet(string $paramTypeValue, string $instanceType): void
    {
        $_POST[ConfirmHandler::PARAM_TYPE] = $paramTypeValue;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $handler = ConfirmHandlerFactory::createHandler();
        static::assertInstanceOf($instanceType, $handler);
    }

    public function testCreateHandlerInvalid(): void
    {
        $_POST[ConfirmHandler::PARAM_TYPE] = 'notExisting';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid type');
        $this->expectExceptionCode(400);

        $handler = ConfirmHandlerFactory::createHandler();
    }
}
