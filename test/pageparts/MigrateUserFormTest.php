<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../../src/pageparts/MigrateUserForm.php';

final class MigrateUserFormTest extends TestCase
{
    // required stubs test depends on

    // our actuall MigrateUserForm to test
    private MigrateUserForm $migrateUserForm;

    protected function setUp(): void
    {
        $this->migrateUserForm = new MigrateUserForm('initial-nick', 'initial-email@foobar.com', 'source-location');
    }

    public function testRenderHtmlDiv_shouldDisplayInitialValues(): void
    {
        $html = $this->migrateUserForm->renderHtmlDiv();

        static::assertStringContainsString('<input type="text" value="initial-nick" name="' . MigrateUserHandler::PARAM_NICK . '" size="20" maxlength="60"/>', $html);
        static::assertStringContainsString('<input type="text" value="initial-email@foobar.com" name="' . MigrateUserHandler::PARAM_NEWEMAIL . '" size="20" maxlength="191"/>', $html);
    }

    public function testRenderHtmlDiv_shouldHaveAllFields(): void
    {
        $html = $this->migrateUserForm->renderHtmlDiv();

        static::assertStringContainsString('<input type="password" name="' . MigrateUserHandler::PARAM_OLDPASS . '" size="20" maxlength="60"/>', $html);
        static::assertStringContainsString('<input type="password" name="' . MigrateUserHandler::PARAM_NEWPASS . '" size="20" maxlength="60"/>', $html);
        static::assertStringContainsString('<input type="password" name="' . MigrateUserHandler::PARAM_CONFIRMNEWPASS . '" size="20" maxlength="60"/>', $html);
    }

    public function testRenderHtmlDiv_cancelShouldNavigateToPassedSource(): void
    {
        $html = $this->migrateUserForm->renderHtmlDiv();

        static::assertStringContainsString('<input type="button" value="Abbrechen" onclick="document.location = \'source-location?migrationended=1\';"/>', $html);
    }

    public function testRenderHtmlDiv_cancelShouldNavigateToIndex(): void
    {
        $migrateUserForm = new MigrateUserForm('initial-nick', 'initial-email@foobar.com', null);
        $html = $migrateUserForm->renderHtmlDiv();

        static::assertStringContainsString('<input type="button" value="Abbrechen" onclick="document.location = \'index.php\';"/>', $html);
    }

    public function testRenderHtmlDiv_shouldHaveSubmitInput(): void
    {
        $html = $this->migrateUserForm->renderHtmlDiv();

        static::assertStringContainsString('<input type="submit" value="Passwort ändern und Mailadresse bestätigen"/>', $html);
    }

    public function testRenderHtmlDiv_shouldCallEndpoint(): void
    {
        $html = $this->migrateUserForm->renderHtmlDiv();

        static::assertStringContainsString('<form id="requestmigrateform" method="post" action="migrateuser.php?migrate=1" accept-charset="utf-8">', $html);
    }

}
