<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../../src/pageparts/ConfirmForm.php';

final class ConfirmFormTest extends TestCase
{
    // required stubs test depends on
    private ConfirmHandler $confirmHandler;

    // our actuall ConfirmForm to test
    private ConfirmForm $confirmForm;

    protected function setUp(): void
    {
        $this->confirmHandler = static::createStub(ConfirmHandler::class);
        $this->confirmForm = new ConfirmForm($this->confirmHandler);
    }


    public function testRenderHtmlDiv_shouldContainConfirmTextFromHandler(): void
    {
        $this->confirmHandler->method('GetConfirmText')->willReturn('the-confirm-text-to-display');

        $html = $this->confirmForm->renderHtmlDiv();

        static::assertStringContainsString('<span class="fbold">the-confirm-text-to-display</span>', $html);
    }

    public function testRenderHtmlDiv_shouldContainParamFields(): void
    {
        $this->confirmHandler->method('GetType')->willReturn('the-confirm-type');
        $this->confirmHandler->method('GetCode')->willReturn('the-confirm-code');

        $html = $this->confirmForm->renderHtmlDiv();

        static::assertStringContainsString('<input type="hidden" name="' . ConfirmHandler::PARAM_TYPE . '" value="the-confirm-type"/>', $html);
        static::assertStringContainsString('<input type="hidden" name="' . ConfirmHandler::PARAM_CODE . '" value="the-confirm-code"/>', $html);
    }

    public function testRenderHtmlDiv_shouldCallEndpoint(): void
    {
        $html = $this->confirmForm->renderHtmlDiv();

        static::assertStringContainsString('<form method="post" action="confirm.php?confirm=1" accept-charset="utf-8">', $html);
    }

    public function testRenderHtmlDiv_shouldHaveSumittInput(): void
    {
        $html = $this->confirmForm->renderHtmlDiv();

        static::assertStringContainsString('<input type="submit" value="Bestätigen"/>', $html);
    }

}
