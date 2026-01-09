<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../BaseTest.php';
require_once __DIR__ . '/../../src/model/PostIndexEntry.php';


/**
 * Just some stupid tests for the accessors.
 * Some values accessed are casted during construction
 */
final class PostIndexEntryTest extends BaseTest
{
    public function testIsHidden(): void
    {
        // test casting of field hidden
        $notHidden = self::mockPostIndexEntry(
            1,
            1,
            null,
            'some-user',
            'a title',
            0,
            '2020-03-30 14:30:05',
            0,  // has_content
            0 // hidden
        );
        static::assertSame(false, $notHidden->IsHidden());

        $hidden = self::mockPostIndexEntry(
            1,
            1,
            null,
            'some-user',
            'a title',
            0,
            '2020-03-30 14:30:05',
            0,  // has_content
            1 // hidden
        );
        static::assertSame(true, $hidden->IsHidden());
    }

    public function testHasContent(): void
    {
        // test casting of field has_content
        $withContent = self::mockPostIndexEntry(
            1,
            1,
            null,
            'some-user',
            'a title',
            0,
            '2020-03-30 14:30:05',
            1,  // has_content
            0 // hidden
        );
        static::assertSame(true, $withContent->HasContent());

        $withoutContent = self::mockPostIndexEntry(
            1,
            1,
            null,
            'some-user',
            'a title',
            0,
            '2020-03-30 14:30:05',
            0,  // has_content
            1 // hidden
        );
        static::assertSame(false, $withoutContent->HasContent());
    }

    public function testGetPostTimestamp(): void
    {
        // test casting of string-value to
        $withContent = self::mockPostIndexEntry(
            1,
            1,
            null,
            'some-user',
            'a title',
            0,
            '2020-03-30 14:30:05',
            1,  // has_content
            0 // hidden
        );
        static::assertEquals(new DateTime('2020-03-30 14:30:05'), $withContent->GetPostTimestamp());
    }

    // just to make test-coverage look good on stupid accessors
    public function testAllAccessors(): void
    {
        $topPost = self::mockPostIndexEntry(
            11,
            111,
            null,
            'user',
            'title',
            0,
            '2020-03-30 14:30:05',
            1,  // has_content
            0 // hidden
        );
        static::assertEquals(11, $topPost->GetPostId());
        static::assertEquals(111, $topPost->GetThreadId());
        static::assertNull($topPost->GetParentPostId());
        static::assertEquals(0, $topPost->GetIndent());
        static::assertEquals('title', $topPost->GetTitle());
        static::assertTrue($topPost->HasContent());
        static::assertEquals('user', $topPost->GetNick());
        static::assertFalse($topPost->IsHidden());
        static::assertEquals(new DateTime('2020-03-30 14:30:05'), $topPost->GetPostTimestamp());
    }
}
