<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../BaseTest.php';
require_once __DIR__ . '/../../src/model/Post.php';


/**
 * Just some stupid tests for the accessors.
 * Some values accessed are casted during construction
 */
final class PostTest extends BaseTest
{
    public static function setUpBeforeClass(): void {}

    protected function setUp(): void {}

    protected function assertPreConditions(): void {}

    public function testHidden(): void
    {
        $hidden = self::mockPost(
            40,
            8,
            8,
            'user3',
            103,
            'Thread 8 - A1',
            'The quick brown fox jumps over the lazy dog',
            2,
            1,
            '2020-03-30 14:50:00',
            null,
            null,
            null,
            null,
            null,
            1,
            '::1'
        );
        static::assertTrue($hidden->IsHidden());
        $visible = self::mockPost(
            40,
            8,
            8,
            'user3',
            103,
            'Thread 8 - A1',
            'The quick brown fox jumps over the lazy dog',
            2,
            1,
            '2020-03-30 14:50:00',
            null,
            null,
            null,
            null,
            null,
            0,
            '::1'
        );
        static::assertFalse($visible->IsHidden());
    }

    public function testParentPostId(): void
    {
        $answer = self::mockPost(
            40,
            8,
            85,
            'user3',
            103,
            'Thread 8 - A1',
            'The quick brown fox jumps over the lazy dog',
            2,
            1,
            '2020-03-30 14:50:00',
            null,
            null,
            null,
            null,
            null,
            1,
            '::1'
        );
        static::assertTrue($answer->HasParentPost());
        static::assertSame(85, $answer->getParentPostId());
        $top = self::mockPost(
            8,
            8,
            null,
            'user2',
            102,
            'Thread 8',
            'The quick brown fox jumps over the lazy dog',
            1,
            0,
            '2020-03-30 14:38:00',
            null,
            null,
            null,
            null,
            null,
            0,
            '::1'
        );
        static::assertFalse($top->HasParentPost());
        static::assertNull($top->getParentPostId());
    }

    public function testContent(): void
    {
        $empty = self::mockPost(
            40,
            8,
            85,
            'user3',
            103,
            'Thread 8 - A1',
            null,
            2,
            1,
            '2020-03-30 14:50:00',
            null,
            null,
            null,
            null,
            null,
            1,
            '::1'
        );
        static::assertFalse($empty->HasContent());
        static::assertNull($empty->getContent());

        $hasContent = self::mockPost(
            40,
            8,
            85,
            'user3',
            103,
            'Thread 8 - A1',
            'foobar',
            2,
            1,
            '2020-03-30 14:50:00',
            null,
            null,
            null,
            null,
            null,
            1,
            '::1'
        );
        static::assertTrue($hasContent->HasContent());
        static::assertSame('foobar', $hasContent->getContent());
    }

    public function testOldPost(): void
    {
        $old = self::mockPost(
            40,
            8,
            85,
            'user3',
            103,
            'Thread 8 - A1',
            null,
            2,
            1,
            '2020-03-30 14:50:00',
            null,
            null,
            null,
            null,
            898,
            1,
            '::1'
        );
        static::assertTrue($old->IsOldPost());
        static::assertSame(898, $old->GetOldPostNo());
        $new = self::mockPost(
            40,
            8,
            85,
            'user3',
            103,
            'Thread 8 - A1',
            null,
            2,
            1,
            '2020-03-30 14:50:00',
            null,
            null,
            null,
            null,
            null,
            1,
            '::1'
        );
        static::assertFalse($new->IsOldPost());
        static::assertNull($new->GetOldPostNo());
    }

    public function testLink(): void
    {
        $noLink = self::mockPost(
            40,
            8,
            85,
            'user3',
            103,
            'Thread 8 - A1',
            null,
            2,
            1,
            '2020-03-30 14:50:00',
            null,
            null,
            null,
            null,
            null,
            0,
            '::1'
        );
        static::assertFalse($noLink->HasLinkUrl());
        static::assertNull($noLink->getLinkUrl());
        static::assertFalse($noLink->HasLinkText());
        static::assertNull($noLink->getLinkText());
        $withLink = self::mockPost(
            40,
            8,
            85,
            'user3',
            103,
            'Thread 8 - A1',
            null,
            2,
            1,
            '2020-03-30 14:50:00',
            null,
            'https://foo.link',
            'visit this',
            null,
            null,
            0,
            '::1'
        );
        static::assertTrue($withLink->HasLinkUrl());
        static::assertSame('https://foo.link', $withLink->getLinkUrl());
        static::assertTrue($withLink->HasLinkText());
        static::assertSame('visit this', $withLink->getLinkText());
    }

    public function testImg(): void
    {
        $withImg = self::mockPost(
            40,
            8,
            85,
            'user3',
            103,
            'Thread 8 - A1',
            null,
            2,
            1,
            '2020-03-30 14:50:00',
            null,
            null,
            null,
            'https://bar.com/foo.gif',
            null,
            0,
            '::1'
        );
        static::assertTrue($withImg->HasImgUrl());
        static::assertSame('https://bar.com/foo.gif', $withImg->getImgUrl());
        $noImg = self::mockPost(
            40,
            8,
            85,
            'user3',
            103,
            'Thread 8 - A1',
            null,
            2,
            1,
            '2020-03-30 14:50:00',
            null,
            null,
            null,
            null,
            null,
            0,
            '::1'
        );
        static::assertFalse($noImg->HasImgUrl());
        static::assertNull($noImg->getImgUrl());
    }

    public function testEmail(): void
    {
        $withEmail = self::mockPost(
            40,
            8,
            85,
            'user3',
            103,
            'Thread 8 - A1',
            null,
            2,
            1,
            '2020-03-30 14:50:00',
            'me@mail.com',
            null,
            null,
            null,
            null,
            0,
            '::1'
        );
        static::assertTrue($withEmail->HasEmail());
        static::assertSame('me@mail.com', $withEmail->getEmail());
        $noEmail = self::mockPost(
            40,
            8,
            85,
            'user3',
            103,
            'Thread 8 - A1',
            null,
            2,
            1,
            '2020-03-30 14:50:00',
            null,
            null,
            null,
            null,
            null,
            0,
            '::1'
        );
        static::assertFalse($noEmail->HasEmail());
        static::assertNull($noEmail->getEmail());
    }

    // just to make test-coverage look good on stupid accessors
    public function testAllAccessors(): void
    {
        $topPost = self::mockPost(
            40,
            8,
            null,
            'user',
            99,
            'title',
            'content',
            1,
            0,
            '2020-03-30 14:50:00',
            null,
            null,
            null,
            null,
            null,
            0,
            '::1'
        );
        static::assertEquals(40, $topPost->GetId());
        static::assertEquals(8, $topPost->GetThreadId());
        static::assertFalse($topPost->IsHidden());
        static::assertNull($topPost->getParentPostId());
        static::assertFalse($topPost->HasParentPost());
        static::assertEquals('title', $topPost->getTitle());
        static::assertEquals('user', $topPost->getNick());
        static::assertEquals(99, $topPost->GetUserId());
        static::assertEquals(new DateTime('2020-03-30 14:50:00'), $topPost->GetPostTimestamp());
        static::assertTrue($topPost->HasContent());
        static::assertEquals('content', $topPost->getContent());
        static::assertNull($topPost->GetOldPostNo());
        static::assertFalse($topPost->IsOldPost());
        static::assertFalse($topPost->HasLinkUrl());
        static::assertNull($topPost->getLinkUrl());
        static::assertFalse($topPost->HasLinkText());
        static::assertNull($topPost->getLinkText());
        static::assertFalse($topPost->HasImgUrl());
        static::assertNull($topPost->getImgUrl());
        static::assertFalse($topPost->HasEmail());
        static::assertNull($topPost->getEmail());
        static::assertEquals(1, $topPost->GetRank());
        static::assertEquals(0, $topPost->GetIndent());
        static::assertEquals('::1', $topPost->GetIpAddress());
    }
}
