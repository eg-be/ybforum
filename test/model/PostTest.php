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
        static::assertTrue($hidden->isHidden());
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
        static::assertFalse($visible->isHidden());
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
        static::assertTrue($answer->hasParentPost());
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
        static::assertFalse($top->hasParentPost());
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
        static::assertFalse($empty->hasContent());
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
        static::assertTrue($hasContent->hasContent());
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
        static::assertTrue($old->isOldPost());
        static::assertSame(898, $old->getOldPostNo());
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
        static::assertFalse($new->isOldPost());
        static::assertNull($new->getOldPostNo());
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
        static::assertFalse($noLink->hasLinkUrl());
        static::assertNull($noLink->getLinkUrl());
        static::assertFalse($noLink->hasLinkText());
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
        static::assertTrue($withLink->hasLinkUrl());
        static::assertSame('https://foo.link', $withLink->getLinkUrl());
        static::assertTrue($withLink->hasLinkText());
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
        static::assertTrue($withImg->hasImgUrl());
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
        static::assertFalse($noImg->hasImgUrl());
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
        static::assertTrue($withEmail->hasEmail());
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
        static::assertFalse($noEmail->hasEmail());
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
        static::assertEquals(40, $topPost->getId());
        static::assertEquals(8, $topPost->getThreadId());
        static::assertFalse($topPost->isHidden());
        static::assertNull($topPost->getParentPostId());
        static::assertFalse($topPost->hasParentPost());
        static::assertEquals('title', $topPost->getTitle());
        static::assertEquals('user', $topPost->getNick());
        static::assertEquals(99, $topPost->getUserId());
        static::assertEquals(new DateTime('2020-03-30 14:50:00'), $topPost->getPostTimestamp());
        static::assertTrue($topPost->hasContent());
        static::assertEquals('content', $topPost->getContent());
        static::assertNull($topPost->getOldPostNo());
        static::assertFalse($topPost->isOldPost());
        static::assertFalse($topPost->hasLinkUrl());
        static::assertNull($topPost->getLinkUrl());
        static::assertFalse($topPost->hasLinkText());
        static::assertNull($topPost->getLinkText());
        static::assertFalse($topPost->hasImgUrl());
        static::assertNull($topPost->getImgUrl());
        static::assertFalse($topPost->hasEmail());
        static::assertNull($topPost->getEmail());
        static::assertEquals(1, $topPost->getRank());
        static::assertEquals(0, $topPost->getIndent());
        static::assertEquals('::1', $topPost->getIpAddress());
    }
}
