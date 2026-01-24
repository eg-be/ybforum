<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../../src/pageparts/SearchResultsView.php';

final class SearchResultsViewTest extends TestCase
{
    // required stubs test depends on
    private SearchHandler $searchHandler;

    protected function setUp(): void
    {
        $this->searchHandler = static::createStub(SearchHandler::class);
    }

    public function testRenderResultsNavigationDiv_shouldShowEmptyDiv(): void
    {
        $this->searchHandler->method('IsFirstRecordBlock')->willReturn(true);
        $this->searchHandler->method('MoreRecordsAvailable')->willReturn(false);

        $searchResultsView = new SearchResultsView($this->searchHandler);
        $html = $searchResultsView->RenderResultsNavigationDiv();

        static::assertStringContainsString('<div></div>', $html);
    }

    public function testRenderResultsNavigationDiv_shouldShowNavigateToPreviousResults(): void
    {
        $this->searchHandler->method('IsFirstRecordBlock')->willReturn(false);
        $this->searchHandler->method('GetPreviousOffset')->willReturn(10000);
        $this->searchHandler->method('GetSortField')->willReturn(SortField::FIELD_RELEVANCE);
        $this->searchHandler->method('GetSortOrder')->willReturn(SortOrder::ORDER_ASC);
        $this->searchHandler->method('GetLimit')->willReturn(1000);

        $searchResultsView = new SearchResultsView($this->searchHandler);
        $html = $searchResultsView->RenderResultsNavigationDiv();

        static::assertStringContainsString('<a class="fbold" href="#" onclick="document.getElementById(\'form_previous_results\').submit()">&lt;-- Vorherige 1000 Resultate &lt;--</a>', $html);
    }

    public function testRenderResultsNavigationDiv_shouldShowNavigateToNextResults(): void
    {
        $this->searchHandler->method('MoreRecordsAvailable')->willReturn(true);
        $this->searchHandler->method('GetPreviousOffset')->willReturn(10000);
        $this->searchHandler->method('GetSortField')->willReturn(SortField::FIELD_RELEVANCE);
        $this->searchHandler->method('GetSortOrder')->willReturn(SortOrder::ORDER_ASC);
        $this->searchHandler->method('GetLimit')->willReturn(1000);

        $searchResultsView = new SearchResultsView($this->searchHandler);
        $html = $searchResultsView->RenderResultsNavigationDiv();

        static::assertStringContainsString('<a class="fbold" style="float: right;" href="#" onclick="document.getElementById(\'form_next_results\').submit()">--&gt; Nächste 1000 Resultate --&gt;</a>', $html);
    }

    public function testRenderResultsNavigationDiv_shouldHaveHiddenSearchFormForPreviousResults(): void
    {
        $this->searchHandler->method('IsFirstRecordBlock')->willReturn(false);
        $this->searchHandler->method('MoreRecordsAvailable')->willReturn(false);
        $this->searchHandler->method('GetPreviousOffset')->willReturn(9000);
        $this->searchHandler->method('GetSortField')->willReturn(SortField::FIELD_RELEVANCE);
        $this->searchHandler->method('GetSortOrder')->willReturn(SortOrder::ORDER_ASC);
        $this->searchHandler->method('GetLimit')->willReturn(1000);
        $this->searchHandler->method('GetSearchString')->willReturn('the-search-string');
        $this->searchHandler->method('GetSearchNick')->willReturn('the-search-nick');
        $this->searchHandler->method('GetNoReplies')->willReturn(true);

        $searchResultsView = new SearchResultsView($this->searchHandler);
        $html = $searchResultsView->RenderResultsNavigationDiv();

        static::assertStringContainsString('<form id="form_previous_results" style="display: inline-block;" method="post" action="search.php?search=1" accept-charset="utf-8">', $html);
        static::assertStringContainsString('<input type="hidden" name="' . SearchHandler::PARAM_SEARCH_STRING . '" value="the-search-string"/>', $html);
        static::assertStringContainsString('<input type="hidden" name="' . SearchHandler::PARAM_NICK . '" value="the-search-nick"/>', $html);
        static::assertStringContainsString('<input type="hidden" name="' . SearchHandler::PARAM_RESULT_OFFSET . '" value="9000"/>', $html);
        static::assertStringContainsString('<input type="hidden" name="' . SearchHandler::PARAM_SORT_FIELD . '" value="relevance"/>', $html);
        static::assertStringContainsString('<input type="hidden" name="' . SearchHandler::PARAM_SORT_ORDER . '" value="ASC"/>', $html);
        static::assertStringContainsString('<input type="hidden" name="' . SearchHandler::PARAM_NO_REPLIES . '" value="' . SearchHandler::PARAM_NO_REPLIES . '"/>', $html);
    }

    public function testRenderResultsNavigationDiv_shouldHaveHiddenSearchFormForNextResults(): void
    {
        $this->searchHandler->method('IsFirstRecordBlock')->willReturn(true);
        $this->searchHandler->method('MoreRecordsAvailable')->willReturn(true);
        $this->searchHandler->method('GetNextOffset')->willReturn(9000);
        $this->searchHandler->method('GetSortField')->willReturn(SortField::FIELD_RELEVANCE);
        $this->searchHandler->method('GetSortOrder')->willReturn(SortOrder::ORDER_ASC);
        $this->searchHandler->method('GetLimit')->willReturn(1000);
        $this->searchHandler->method('GetSearchString')->willReturn('the-search-string');
        $this->searchHandler->method('GetSearchNick')->willReturn('the-search-nick');
        $this->searchHandler->method('GetNoReplies')->willReturn(true);

        $searchResultsView = new SearchResultsView($this->searchHandler);
        $html = $searchResultsView->RenderResultsNavigationDiv();

        static::assertStringContainsString('<form id="form_next_results" style="display: inline-block;" method="post" action="search.php?search=1" accept-charset="utf-8">', $html);
        static::assertStringContainsString('<input type="hidden" name="' . SearchHandler::PARAM_SEARCH_STRING . '" value="the-search-string"/>', $html);
        static::assertStringContainsString('<input type="hidden" name="' . SearchHandler::PARAM_NICK . '" value="the-search-nick"/>', $html);
        static::assertStringContainsString('<input type="hidden" name="' . SearchHandler::PARAM_RESULT_OFFSET . '" value="9000"/>', $html);
        static::assertStringContainsString('<input type="hidden" name="' . SearchHandler::PARAM_SORT_FIELD . '" value="relevance"/>', $html);
        static::assertStringContainsString('<input type="hidden" name="' . SearchHandler::PARAM_SORT_ORDER . '" value="ASC"/>', $html);
        static::assertStringContainsString('<input type="hidden" name="' . SearchHandler::PARAM_NO_REPLIES . '" value="' . SearchHandler::PARAM_NO_REPLIES . '"/>', $html);
    }

    public function testRenderResultsNavigationDiv_hiddenSearchFormShouldHtmlspecialcharsValuesFromHandler(): void
    {
        $this->searchHandler->method('IsFirstRecordBlock')->willReturn(true);
        $this->searchHandler->method('MoreRecordsAvailable')->willReturn(true);
        $this->searchHandler->method('GetSortField')->willReturn(SortField::FIELD_RELEVANCE);
        $this->searchHandler->method('GetSortOrder')->willReturn(SortOrder::ORDER_ASC);
        $this->searchHandler->method('GetSearchString')->willReturn('search&&&');
        $this->searchHandler->method('GetSearchNick')->willReturn('nick&&&');

        $searchResultsView = new SearchResultsView($this->searchHandler);
        $html = $searchResultsView->RenderResultsNavigationDiv();

        static::assertStringContainsString('<input type="hidden" name="' . SearchHandler::PARAM_SEARCH_STRING . '" value="search&amp;&amp;&amp;"/>', $html);
        static::assertStringContainsString('<input type="hidden" name="' . SearchHandler::PARAM_NICK . '" value="nick&amp;&amp;&amp;"/>', $html);
    }

    public function testRenderSortDiv_shouldMarkCurrentSortFieldWithCurrentSortOrderAsc(): void
    {
        $this->searchHandler->method('GetSortField')->willReturn(SortField::FIELD_NICK);
        $this->searchHandler->method('GetSortOrder')->willReturn(SortOrder::ORDER_ASC);
        $this->searchHandler->method('GetValidSortFields')->willReturn([SortField::FIELD_DATE, SortField::FIELD_TITLE, SortField::FIELD_NICK]);

        $searchResultsView = new SearchResultsView($this->searchHandler);
        $html = $searchResultsView->RenderSortDiv();

        static::assertStringContainsString('<a href="#" class="fitalic" onclick="document.getElementById(\'form_sort_nick\').submit()">Stammposter &#8593;</a>', $html);
    }

    public function testRenderSortDiv_shouldMarkCurrentSortFieldWithCurrentSortOrderDesc(): void
    {
        $this->searchHandler->method('GetSortField')->willReturn(SortField::FIELD_NICK);
        $this->searchHandler->method('GetSortOrder')->willReturn(SortOrder::ORDER_DESC);
        $this->searchHandler->method('GetValidSortFields')->willReturn([SortField::FIELD_DATE, SortField::FIELD_TITLE, SortField::FIELD_NICK]);

        $searchResultsView = new SearchResultsView($this->searchHandler);
        $html = $searchResultsView->RenderSortDiv();

        static::assertStringContainsString('<a href="#" class="fitalic" onclick="document.getElementById(\'form_sort_nick\').submit()">Stammposter &#8595;</a>', $html);
    }

    public function testRenderSortDiv_shouldHaveLinksForNonActiveSortFields(): void
    {
        $this->searchHandler->method('GetSortField')->willReturn(SortField::FIELD_NICK);
        $this->searchHandler->method('GetSortOrder')->willReturn(SortOrder::ORDER_DESC);
        $this->searchHandler->method('GetValidSortFields')->willReturn([SortField::FIELD_DATE, SortField::FIELD_TITLE, SortField::FIELD_NICK]);

        $searchResultsView = new SearchResultsView($this->searchHandler);
        $html = $searchResultsView->RenderSortDiv();

        static::assertStringContainsString('<a href="#" onclick="document.getElementById(\'form_sort_creation_ts\').submit()">Datum </a>', $html);
        static::assertStringContainsString('<a href="#" onclick="document.getElementById(\'form_sort_title\').submit()">Titel </a>', $html);
    }

    public function testRenderSortDiv_shouldHaveHiddenSortFormForEveryField(): void
    {
        $this->searchHandler->method('GetSortField')->willReturn(SortField::FIELD_NICK);
        $this->searchHandler->method('GetSortOrder')->willReturn(SortOrder::ORDER_DESC);
        $this->searchHandler->method('GetValidSortFields')->willReturn([SortField::FIELD_DATE, SortField::FIELD_TITLE, SortField::FIELD_NICK]);

        $searchResultsView = new SearchResultsView($this->searchHandler);
        $html = $searchResultsView->RenderSortDiv();

        static::assertStringContainsString('<form id="form_sort_title" style="display: inline-block;" method="post" action="search.php?search=1" accept-charset="utf-8">', $html);
        static::assertStringContainsString('<form id="form_sort_creation_ts" style="display: inline-block;" method="post" action="search.php?search=1" accept-charset="utf-8">', $html);
        static::assertStringContainsString('<form id="form_sort_nick" style="display: inline-block;" method="post" action="search.php?search=1" accept-charset="utf-8">', $html);
    }

    public function testRenderSortDiv_shouldSetOffset0ForEveryField(): void
    {
        $this->searchHandler->method('GetSortField')->willReturn(SortField::FIELD_NICK);
        $this->searchHandler->method('GetSortOrder')->willReturn(SortOrder::ORDER_DESC);
        $this->searchHandler->method('GetValidSortFields')->willReturn([SortField::FIELD_DATE, SortField::FIELD_TITLE, SortField::FIELD_NICK]);

        $searchResultsView = new SearchResultsView($this->searchHandler);
        $html = $searchResultsView->RenderSortDiv();

        static::assertMatchesRegularExpression('/<form id="form_sort_title".+<input type="hidden" name="search_result_offset" value="0"\/>/', $html);
        static::assertMatchesRegularExpression('/<form id="form_sort_creation_ts".+<input type="hidden" name="search_result_offset" value="0"\/>/', $html);
        static::assertMatchesRegularExpression('/<form id="form_sort_nick".+<input type="hidden" name="search_result_offset" value="0"\/>/', $html);
    }

    public function testRenderResultsDiv_shouldInlcudeLinksToPost(): void
    {
        $result1 = static::createStub(SearchResult::class);
        $result1->method('GetPostId')->willReturn(101);
        $result1->method('GetTitle')->willReturn('Title 1');
        $result1->method('GetNick')->willReturn('Nick 1');
        $result1->method('GetPostTimestamp')->willReturn(new DateTime('2020-03-30 14:30:05'));

        $result2 = static::createStub(SearchResult::class);
        $result2->method('GetPostId')->willReturn(222);
        $result2->method('GetTitle')->willReturn('Title 2');
        $result2->method('GetNick')->willReturn('Nick 2');
        $result2->method('GetPostTimestamp')->willReturn(new DateTime('2018-03-30 13:00:59'));

        $this->searchHandler->method('GetResults')->willReturn([$result1, $result2]);

        $searchResultsView = new SearchResultsView($this->searchHandler);
        $html = $searchResultsView->RenderResultsDiv();

        static::assertStringContainsString('<p class="nomargin"><a href="showentry.php?idpost=101">Title 1</a> - <span class="fbold">Nick 1</span> - 30.03.2020 14:30:05</p>', $html);
        static::assertStringContainsString('<p class="nomargin"><a href="showentry.php?idpost=222">Title 2</a> - <span class="fbold">Nick 2</span> - 30.03.2018 13:00:59</p>', $html);
    }

    public function testRenderResultsDiv_shouldHtmlspecialcharsValues(): void
    {
        $result1 = static::createStub(SearchResult::class);
        $result1->method('GetPostId')->willReturn(101);
        $result1->method('GetTitle')->willReturn('Title &');
        $result1->method('GetNick')->willReturn('Nick &');
        $result1->method('GetPostTimestamp')->willReturn(new DateTime('2020-03-30 14:30:05'));

        $this->searchHandler->method('GetResults')->willReturn([$result1]);

        $searchResultsView = new SearchResultsView($this->searchHandler);
        $html = $searchResultsView->RenderResultsDiv();

        static::assertStringContainsString('<p class="nomargin"><a href="showentry.php?idpost=101">Title &amp;</a> - <span class="fbold">Nick &amp;</span> - 30.03.2020 14:30:05</p>', $html);
    }
}
