<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../../src/pageparts/PageNavigationView.php';

final class PageNavigationViewTest extends TestCase
{
    // note: For all tests, skipNrOfPages is set to 25:
    public const MAX_THREADS_PER_PAGE = 10;
    public const MAX_PAGE_NAV_ENTRIES = 3;
    public const SKIP_NR_OF_PAGES = 25;

    public function testRenderHtmlDiv_shouldHaveFirstPageNavigationLink(): void
    {
        // 100 threads, 10 threads per page, current page 2 -> can navigate to first page
        $pageNavView = new PageNavigationView(2, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 100);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringContainsString('<a href="index.php?page=1">&lt;&lt;</a>', $html);
    }

    public function testRenderHtmlDiv_shouldNotHaveFirstPageNavigationLink(): void
    {
        // 100 threads, 10 threads per page, current page 1 -> first page is same as we are on
        $pageNavView = new PageNavigationView(1, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 100);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringNotContainsString('<a href="index.php?page=1">&lt;&lt;</a>', $html);
    }

    public function testRenderHtmlDiv_shouldHaveLastPageNavigationLink(): void
    {
        // 100 threads, 10 threads per page, current page 5 -> lastPage should be 10
        $pageNavView = new PageNavigationView(5, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 100);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringContainsString('<a href="index.php?page=10">&gt;&gt;</a>', $html);

        // 101 threads, 10 threads per page, current page 5 -> lastPage should be 11
        $pageNavView = new PageNavigationView(5, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 101);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringContainsString('<a href="index.php?page=11">&gt;&gt;</a>', $html);
    }

    public function testRenderHtmlDiv_shouldNotHaveLastPageNavigationLink(): void
    {
        // 100 threads, 10 threads per page, current page 10 -> last page is same as we are on
        $pageNavView = new PageNavigationView(1, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 10);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringNotContainsString('>&gt;&gt;</a>', $html);

        // 10 threads, 10 threads per page, current page 1 -> last page is same as we are on
        $pageNavView = new PageNavigationView(1, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 10);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringNotContainsString('>&gt;&gt;</a>', $html);


        // 1 threads, 10 threads per page, current page 1 -> last page is same as we are on
        $pageNavView = new PageNavigationView(5, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 1);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringNotContainsString('>&gt;&gt;</a>', $html);

        // 0 threads, 10 threads per page, current page 1 -> last page is same as we are on
        $pageNavView = new PageNavigationView(5, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 0);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringNotContainsString('>&gt;&gt;</a>', $html);
    }

    public function testRenderHtmlDiv_shouldHaveSkipBackLink(): void
    {
        // 1000 threads, 10 threads per page, current page 27 -> Skipping 25 pages points to page 2
        $pageNavView = new PageNavigationView(27, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 1000);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringContainsString('<span class="navelement"><a href="index.php?page=2">&lt;</a></span>', $html);
    }

    public function testRenderHtmlDiv_shouldNotHaveSkipBackLink(): void
    {
        // 1000 threads, 10 threads per page, current page 26 -> Skipping 25 pages would point to first page, do not show
        $pageNavView = new PageNavigationView(26, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 1000);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringNotContainsString('>&lt;</a>', $html);

        // 1000 threads, 10 threads per page, current page 25 -> Skipping 25 pages would point to first page, do not show
        $pageNavView = new PageNavigationView(25, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 1000);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringNotContainsString('>&lt;</a>', $html);
    }

    public function testRenderHtmlDiv_shouldHaveSkipForwardLink(): void
    {
        // 1000 threads, 10 threads per page, current page 74 -> Skipping 25 pages points to page 99
        $pageNavView = new PageNavigationView(74, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 1000);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringContainsString('<span class="navelement"><a href="index.php?page=99">&gt;</a></span>', $html);
    }

    public function testRenderHtmlDiv_shouldNotHaveSkipForwardLink(): void
    {
        // 1000 threads, 10 threads per page, current page 75 -> Skipping 25 pages would point to last page, do not show
        $pageNavView = new PageNavigationView(75, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 1000);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringNotContainsString('>&gt;</a>', $html);

        // 1000 threads, 10 threads per page, current page 76 -> Skipping 25 pages would point to last page, do not show
        $pageNavView = new PageNavigationView(76, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 1000);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringNotContainsString('>&gt;</a>', $html);
    }

    public function testRenderHtmlDiv_shouldHave3PageNavBackElements(): void
    {
        // 100 threads, 10 threads per page, current page 4 -> Nav back to 3, 2, 1
        $pageNavView = new PageNavigationView(4, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 100);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringContainsString('<span class="navelement"><a href="index.php?page=3">3</a></span>', $html);
        static::assertStringContainsString('<span class="navelement"><a href="index.php?page=2">2</a></span>', $html);
        static::assertStringContainsString('<span class="navelement"><a href="index.php?page=1">1</a></span>', $html);
    }

    public function testRenderHtmlDiv_shouldHave2PageNavBackElements(): void
    {
        // 100 threads, 10 threads per page, current page 3 -> Nav back to 2, 1
        $pageNavView = new PageNavigationView(3, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 100);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringContainsString('<span class="navelement"><a href="index.php?page=2">2</a></span>', $html);
        static::assertStringContainsString('<span class="navelement"><a href="index.php?page=1">1</a></span>', $html);
    }

    public function testRenderHtmlDiv_shouldHave1PageNavBackElements(): void
    {
        // 100 threads, 10 threads per page, current page 2 -> Nav back to 1
        $pageNavView = new PageNavigationView(2, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 100);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringContainsString('<span class="navelement"><a href="index.php?page=1">1</a></span>', $html);
    }

    public function testRenderHtmlDiv_shouldHaveNoPageNavBackElements(): void
    {
        // 100 threads, 10 threads per page, current page 1 -> Cant nav back to page 1
        $pageNavView = new PageNavigationView(1, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 100);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringNotContainsString('<span class="navelement"><a href="index.php?page=1">1</a></span>', $html);
    }

    public function testRenderHtmlDiv_shouldHave3PageNavForwardElements(): void
    {
        // 100 threads, 10 threads per page, current page 7 -> Nav forward to 8, 9, 10
        $pageNavView = new PageNavigationView(7, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 100);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringContainsString('<span class="navelement"><a href="index.php?page=8">8</a></span>', $html);
        static::assertStringContainsString('<span class="navelement"><a href="index.php?page=9">9</a></span>', $html);
        static::assertStringContainsString('<span class="navelement"><a href="index.php?page=10">10</a></span>', $html);
    }

    public function testRenderHtmlDiv_shouldHave2PageNavForwardElements(): void
    {
        // 100 threads, 10 threads per page, current page 8 -> Nav forward to 9, 10
        $pageNavView = new PageNavigationView(8, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 100);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringContainsString('<span class="navelement"><a href="index.php?page=9">9</a></span>', $html);
        static::assertStringContainsString('<span class="navelement"><a href="index.php?page=10">10</a></span>', $html);
    }

    public function testRenderHtmlDiv_shouldHave1PageNavForwardElements(): void
    {
        // 100 threads, 10 threads per page, current page 9 -> Nav forward to 10
        $pageNavView = new PageNavigationView(9, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 100);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringContainsString('<span class="navelement"><a href="index.php?page=10">10</a></span>', $html);
    }

    public function testRenderHtmlDiv_shouldHaveNoPageNavForwardElements(): void
    {
        // 100 threads, 10 threads per page, current page 10 -> Cant nav forward to page 10
        $pageNavView = new PageNavigationView(10, self::MAX_THREADS_PER_PAGE, self::MAX_PAGE_NAV_ENTRIES, self::SKIP_NR_OF_PAGES, 100);
        $html = $pageNavView->renderHtmlDivContent();
        static::assertStringNotContainsString('<span class="navelement"><a href="index.php?page=10">10</a></span>', $html);
    }

}
