<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../BaseTest.php';
require_once __DIR__.'/../../src/pageparts/TopNavigation.php';


/**
 * No Database stuff required
 */
final class TopNavigationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
    }

    protected function setUp(): void
    {
    }

    protected function assertPreConditions(): void
    {

    }

    public static function providerPageUris() : array 
    {
        return array(
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
            ['showentry.php?idpost=22', Page::SHOW_ENTRY, 22]
        );
    }

    #[DataProvider('providerPageUris')]
    public function testConstructTopNavigation(string $pageUri, Page $page, ?int $postId) 
    {
        $_SERVER['REQUEST_URI'] = 'https://somewhere.com/' . $pageUri;
        $nav = new TopNavigation($postId);
        $this->assertTrue($page === $nav->GetPage());
    }

    public function testPageIdMustBeSetForShowPage() 
    {
        $_SERVER['REQUEST_URI'] = 'https://somewhere.com/showentry.php';
        $this->expectException(InvalidArgumentException::class);
        $nav = new TopNavigation();
    }    
}