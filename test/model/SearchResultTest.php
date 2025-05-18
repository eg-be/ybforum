<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../BaseTest.php';
require_once __DIR__.'/../../src/model/SearchResult.php';


/**
 * Just some stupid tests for the accessors.
 * Some values accessed are casted during construction
 */
final class SearchResultTest extends BaseTest
{
    private ForumDb $db;

    public static function setUpBeforeClass(): void
    {

    }

    protected function setUp(): void
    {
    }

    protected function assertPreConditions(): void
    {
    }

    public function testGetPostId() : void {
        $someResult = self::mockSearchResult(99, 'nick', 'title', '2020-03-30 14:50:00', null);
        $this->assertEquals(99, $someResult->GetPostId());
    }

    public function testGetTitle() : void {
        $someResult = self::mockSearchResult(99, 'nick', 'title', '2020-03-30 14:50:00', null);
        $this->assertEquals('title', $someResult->GetTitle());
    }

    public function testGetNick() : void {
        $someResult = self::mockSearchResult(99, 'nick', 'title', '2020-03-30 14:50:00', null);
        $this->assertEquals('nick', $someResult->GetNick());
    }

    public function testGetPostTimestamp() : void {
        $someResult = self::mockSearchResult(99, 'nick', 'title', '2020-03-30 14:50:00', null);
        $this->assertEquals(new DateTime('2020-03-30 14:50:00'), $someResult->GetPostTimestamp());
    }
}