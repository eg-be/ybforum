<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../../src/pageparts/SearchForm.php';

final class SearchFormTest extends TestCase
{
    // required stubs test depends on
    private SearchHandler $searchHandler;

    protected function setUp(): void
    {
        $this->searchHandler = static::createStub(SearchHandler::class);
    }

    public function testRenderHtmlForm_shouldContainSearchStringFromHandler(): void
    {
        $this->searchHandler->method('GetSearchString')->willReturn('the-search-string');

        $searchForm = new SearchForm($this->searchHandler);
        $html = $searchForm->RenderHtmlForm();

        static::assertStringContainsString('<input type="text" value="the-search-string" name="' . SearchHandler::PARAM_SEARCH_STRING . '" size="50" maxlength="100"/>', $html);
    }

    public function testRenderHtmlForm_shouldContainSearchNickFromHandler(): void
    {
        $this->searchHandler->method('GetSearchNick')->willReturn('the-search-nick');

        $searchForm = new SearchForm($this->searchHandler);
        $html = $searchForm->RenderHtmlForm();

        static::assertStringContainsString('<input type="text" value="the-search-nick" name="' . SearchHandler::PARAM_NICK . '" size="20" maxlength="60"/>', $html);
    }

    public function testRenderHtmlForm_shouldSetNoRepliesFromHandler(): void
    {
        $this->searchHandler->method('GetNoReplies')->willReturn(true);

        $searchForm = new SearchForm($this->searchHandler);
        $html = $searchForm->RenderHtmlForm();

        static::assertStringContainsString('<input type="checkbox" value="' . SearchHandler::PARAM_NO_REPLIES . '" name="' . SearchHandler::PARAM_NO_REPLIES . '" checked/>', $html);
    }

    public function testRenderHtmlForm_shouldHtmlspecialcharsValuesFromHandler(): void
    {
        $this->searchHandler->method('GetSearchString')->willReturn('search-&&');
        $this->searchHandler->method('GetSearchNick')->willReturn('nick-&&');

        $searchForm = new SearchForm($this->searchHandler);
        $html = $searchForm->RenderHtmlForm();

        static::assertStringContainsString('<input type="text" value="search-&amp;&amp;" name="' . SearchHandler::PARAM_SEARCH_STRING . '" size="50" maxlength="100"/>', $html);
        static::assertStringContainsString('<input type="text" value="nick-&amp;&amp;" name="' . SearchHandler::PARAM_NICK . '" size="20" maxlength="60"/>', $html);
    }

    public function testRenderHtmlForm_shouldInitialyHaveEmptyValues(): void
    {
        $searchForm = new SearchForm(null);
        $html = $searchForm->RenderHtmlForm();

        static::assertStringContainsString('<input type="text" value="" name="' . SearchHandler::PARAM_SEARCH_STRING . '" size="50" maxlength="100"/>', $html);
        static::assertStringContainsString('<input type="text" value="" name="' . SearchHandler::PARAM_NICK . '" size="20" maxlength="60"/>', $html);
    }

    public function testRenderHtmlForm_shouldInitialyNotCheckNoReplies(): void
    {
        $searchForm = new SearchForm(null);
        $html = $searchForm->RenderHtmlForm();

        static::assertStringContainsString('<input type="checkbox" value="' . SearchHandler::PARAM_NO_REPLIES . '" name="' . SearchHandler::PARAM_NO_REPLIES . '"/>', $html);
    }

    public function testRenderHtmlForm_shouldCallEndpoint(): void
    {
        $searchForm = new SearchForm(null);
        $html = $searchForm->RenderHtmlForm();

        static::assertStringContainsString('<form method="post" action="search.php?search=1" accept-charset="utf-8">', $html);
    }

    public function testRenderHtmlForm_shouldHaveSumittInput(): void
    {
        $searchForm = new SearchForm(null);
        $html = $searchForm->RenderHtmlForm();

        static::assertStringContainsString('<input type="submit" value="Suchen"/>', $html);
    }
}
