<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../BaseTest.php';
require_once __DIR__.'/../../src/model/PostIndexEntry.php';


/**
 * Just some stupid tests for the accessors.
 * Some values accessed are casted during construction
 */
final class PostIndexEntryTest extends BaseTest
{
    public function testIsHidden() : void
    {
        // test casting of field hidden
        $notHidden = self::mockPostIndexEntry(1, 1,
            null, 'some-user', 'a title',
            0,
            '2020-03-30 14:30:05',
            0,  // has_content
            0 // hidden
        );
        $this->assertSame(false, $notHidden->IsHidden());

        $hidden = self::mockPostIndexEntry(1, 1,
            null, 'some-user', 'a title',
            0,
            '2020-03-30 14:30:05',
            0,  // has_content
            1 // hidden
        );
        $this->assertSame(true, $hidden->IsHidden());
    }

    public function testHasContent() : void
    {
        // test casting of field has_content
        $withContent = self::mockPostIndexEntry(1, 1,
            null, 'some-user', 'a title',
            0,
            '2020-03-30 14:30:05',
            1,  // has_content
            0 // hidden
        );
        $this->assertSame(true, $withContent->HasContent());

        $withoutContent = self::mockPostIndexEntry(1, 1,
            null, 'some-user', 'a title',
            0,
            '2020-03-30 14:30:05',
            0,  // has_content
            1 // hidden
        );
        $this->assertSame(false, $withoutContent->HasContent());
    }
    
    public function testGetPostTimestamp() : void
    {
        // test casting of string-value to 
        $withContent = self::mockPostIndexEntry(1, 1,
            null, 'some-user', 'a title',
            0,
            '2020-03-30 14:30:05',
            1,  // has_content
            0 // hidden
        );
        $this->assertEquals(new DateTime('2020-03-30 14:30:05'), $withContent->GetPostTimestamp());
    }

    // just to make test-coverage look good on stupid accessors
    public function testAllAccessors() : void
     {
        $topPost = self::mockPostIndexEntry(11, 111,
            null, 'user', 'title',
            0,
            '2020-03-30 14:30:05',
            1,  // has_content
            0 // hidden
        );
        $this->assertEquals(11, $topPost->GetPostId());
        $this->assertEquals(111, $topPost->GetThreadId());
        $this->assertNull($topPost->GetParentPostId());
        $this->assertEquals(0, $topPost->GetIndent());
        $this->assertEquals('title', $topPost->GetTitle());
        $this->assertTrue($topPost->HasContent());
        $this->assertEquals('user', $topPost->GetNick());
        $this->assertFalse($topPost->IsHidden());
        $this->assertEquals(new DateTime('2020-03-30 14:30:05'), $topPost->GetPostTimestamp());        
     }
}