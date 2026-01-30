<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../../src/pageparts/TopNavigation.php';


/**
 * No Database stuff required
 */
final class TopNavigationTest extends TestCase
{
    public static function setUpBeforeClass(): void {}

    protected function setUp(): void {}

    protected function assertPreConditions(): void {}

    public static function providerPageUris(): array
    {
        return [
            ['index.php', Page::INDEX, null],
            ["/", Page::INDEX, null],
            ['', Page::INDEX, null],
            ['postentry.php', Page::POST_ENTRY, null],
            ['postentry.php?post=1', Page::POST_ENTRY, null],
            ['recent.php', Page::RECENT_ENTRIES, null],
            ['search.php', Page::SEARCH, null],
            ['search.php?search=1', Page::SEARCH, null],
            ['textformatierung.php', Page::FORMATING, null],
            ['stammposter.php', Page::STAMMPOSTER, null],
            ['register.php', Page::REGISTER, null],
            ['register.php?register=1', Page::REGISTER, null],
            ['showentry.php', Page::SHOW_ENTRY, 22],
            ['showentry.php?idpost=22', Page::SHOW_ENTRY, 22],
        ];
    }

    #[DataProvider('providerPageUris')]
    public function testConstructTopNavigation(string $pageUri, Page $page, ?int $postId): void
    {
        $_SERVER['REQUEST_URI'] = 'https://somewhere.com/' . $pageUri;
        $nav = new TopNavigation($postId);
        static::assertTrue($page === $nav->getPage());
    }

    public function testPostIdMustBeSetForShowPage(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://somewhere.com/showentry.php';
        $this->expectException(InvalidArgumentException::class);
        $nav = new TopNavigation();
    }

    public function testRenderHtmlDiv_shouldDisplayExpectedPagesForIndex(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://somewhere.com/index.php';
        $nav = new TopNavigation(null);

        $html = $nav->renderHtmlDiv();

        static::assertStringContainsString('[ <a href="postentry.php">Beitrag Schreiben</a> ]', $html);
        static::assertStringContainsString('[ <a href="recent.php">Neue Beiträge</a> ]', $html);
        static::assertStringContainsString('[ <a href="search.php">Suchen</a> ]', $html);
        static::assertStringContainsString('[ <a href="textformatierung.php">Textformatierung</a> ]', $html);
        static::assertStringContainsString('[ <a href="stammposter.php">Stammposter</a> ]', $html);
        static::assertStringContainsString('[ <a href="register.php">Registrieren</a> ]', $html);
        static::assertStringContainsString('[ <a href="contact.php">Kontakt</a> ]', $html);
    }

    public function testRenderHtmlDiv_shouldHaveReplyOptionAsFirstEntryIfPostIsDisplayed(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://somewhere.com/showentry.php?idpost=1313';
        $nav = new TopNavigation(1313);

        $html = $nav->renderHtmlDiv();

        static::assertStringContainsString('<div class="fullwidthcenter">' . PHP_EOL . ' [ <a href="postentry.php?idparentpost=1313">Antworten</a> ]', $html);
    }

    public function testRenderHtmlDiv_shouldNotLinkToPostNewEntryIfEntryIsBeingDisplayed(): void
    {
        $_SERVER['REQUEST_URI'] = 'https://somewhere.com/showentry.php?idpost=1313';
        $nav = new TopNavigation(1313);

        $html = $nav->renderHtmlDiv();

        static::assertStringNotContainsString('<a href="postentry.php">Beitrag Schreiben</a>', $html);
    }

}
