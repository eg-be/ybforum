<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../BaseTest.php';
require_once __DIR__ . '/../../src/model/SearchResult.php';


/**
 * Just some stupid tests for the accessors.
 * Some values accessed are casted during construction
 */
final class SearchResultTest extends BaseTest
{
    public static function setUpBeforeClass(): void {}

    protected function setUp(): void {}

    protected function assertPreConditions(): void {}

    public function testGetPostId(): void
    {
        $someResult = self::mockSearchResult(99, 'nick', 'title', '2020-03-30 14:50:00', null);
        static::assertEquals(99, $someResult->GetPostId());
    }

    public function testgetTitle(): void
    {
        $someResult = self::mockSearchResult(99, 'nick', 'title', '2020-03-30 14:50:00', null);
        static::assertEquals('title', $someResult->getTitle());
    }

    public function testgetNick(): void
    {
        $someResult = self::mockSearchResult(99, 'nick', 'title', '2020-03-30 14:50:00', null);
        static::assertEquals('nick', $someResult->getNick());
    }

    public function testGetPostTimestamp(): void
    {
        $someResult = self::mockSearchResult(99, 'nick', 'title', '2020-03-30 14:50:00', null);
        static::assertEquals(new DateTime('2020-03-30 14:50:00'), $someResult->GetPostTimestamp());
    }
}
