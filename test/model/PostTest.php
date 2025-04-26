<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../BaseTest.php';
require_once __DIR__.'/../../src/model/Post.php';


/**
 * Requires a valid database to connect to, as we
 * want to really test the executed sql.
 * 
 * See README.md located in this directory, on how
 * to setup the test-database.
 * 
 */
final class PostTest extends BaseTest
{
    private ForumDb $db;

    public static function setUpBeforeClass(): void
    {
        // This tests will not modify the db, its enough to re-create
        // the test-db before running all tests from this class
        BaseTest::createTestDatabase();
    }

    protected function setUp(): void
    {
        $this->db = new ForumDb();
    }

    protected function assertPreConditions(): void
    {
        $this->assertTrue($this->db->IsConnected());
    }

    public static function providerPostMock() : array
    {
        // one simple post with no parent:
        $p8 = self::mockPost(8, 8, null,
            'user2', 102, 
            'Thread 8', 'The quick brown fox jumps over the lazy dog',
            1, 0,
            '2020-03-30 14:38:00',
            null,
            null, null, null,
            null,
            0,
            '::1'
        );
        // one with a parent:
        $p21 = self::mockPost(21, 3, 20,
            'user2', 102, 
            'Thread 3 - A1-1', 'The quick brown fox jumps over the lazy dog',
            3, 2,
            '2020-03-30 14:51:00',
            null,
            null, null, null,
            null,
            0,
            '::1'
        );

        // and one with all fields set:
        $p30 = self::mockPost(30, 5, 5,
            'user1', 101, 
            'Thread 5 - A1', 'The quick brown fox jumps over the lazy dog',
            2, 1,
            '2022-06-22 16:13:25',
            'mail@me.com',
            'https://foobar', 'Visit me', 'https://giphy/bar.gif',
            131313,
            0,
            '::1'
        );        
        
        // and a hidden-one
        $p40 = self::mockPost(40, 8, 8,
            'user3', 103, 
            'Thread 8 - A1', 'The quick brown fox jumps over the lazy dog',
            2, 1,
            '2020-03-30 14:50:00',
            null,
            null, null, null,
            null,
            1,
            '::1'
        );
        return array(
            [$p8],
            [$p21],
            [$p30],
            [$p40]
        );
    }

    #[DataProvider('providerPostMock')]
    public function testLoadPost(Post $ref) : void
    {
        $post = Post::LoadPost($this->db, $ref->GetId());
        $this->assertNotNull($post);
        $this->assertObjectEquals($ref, $post);
    }

    public function testLoadPostFail() : void
    {
        $this->assertNull(Post::LoadPost($this->db, -1));
        $this->assertNull(Post::LoadPost($this->db, 99));
    }

    public function testHidden() : void
    {
        $hidden = self::mockPost(40, 8, 8,
            'user3', 103, 
            'Thread 8 - A1', 'The quick brown fox jumps over the lazy dog',
            2, 1,
            '2020-03-30 14:50:00',
            null,
            null, null, null,
            null,
            1,
            '::1'
        );
        $this->assertTrue($hidden->IsHidden());
        $visible = self::mockPost(40, 8, 8,
            'user3', 103, 
            'Thread 8 - A1', 'The quick brown fox jumps over the lazy dog',
            2, 1,
            '2020-03-30 14:50:00',
            null,
            null, null, null,
            null,
            0,
            '::1'
        );
        $this->assertFalse($visible->IsHidden());        
    }

    public function testParentPostId()  : void
    {
        $answer = self::mockPost(40, 8, 85,
            'user3', 103, 
            'Thread 8 - A1', 'The quick brown fox jumps over the lazy dog',
            2, 1,
            '2020-03-30 14:50:00',
            null,
            null, null, null,
            null,
            1,
            '::1'
        );
        $this->assertTrue($answer->HasParentPost());
        $this->assertSame(85, $answer->GetParentPostId());
        $top = self::mockPost(8, 8, null,
            'user2', 102, 
            'Thread 8', 'The quick brown fox jumps over the lazy dog',
            1, 0,
            '2020-03-30 14:38:00',
            null,
            null, null, null,
            null,
            0,
            '::1'
        );
        $this->assertFalse($top->HasParentPost());
        $this->assertNull($top->GetParentPostId());
    }

    public function testContent() : void
    {
        $empty = self::mockPost(40, 8, 85,
            'user3', 103, 
            'Thread 8 - A1', null,
            2, 1,
            '2020-03-30 14:50:00',
            null,
            null, null, null,
            null,
            1,
            '::1'
        );
        $this->assertFalse($empty->HasContent());
        $this->assertNull($empty->GetContent());

        $hasContent = self::mockPost(40, 8, 85,
            'user3', 103, 
            'Thread 8 - A1', 'foobar',
            2, 1,
            '2020-03-30 14:50:00',
            null,
            null, null, null,
            null,
            1,
            '::1'
        );
        $this->assertTrue($hasContent->HasContent());
        $this->assertSame('foobar', $hasContent->GetContent());
    }

    public function testOldPost() : void
    {
        $old = self::mockPost(40, 8, 85,
            'user3', 103, 
            'Thread 8 - A1', null,
            2, 1,
            '2020-03-30 14:50:00',
            null,
            null, null, null,
            898,
            1,
            '::1'
        );
        $this->assertTrue($old->IsOldPost());
        $this->assertSame(898, $old->GetOldPostNo());
        $new = self::mockPost(40, 8, 85,
            'user3', 103, 
            'Thread 8 - A1', null,
            2, 1,
            '2020-03-30 14:50:00',
            null,
            null, null, null,
            null,
            1,
            '::1'
        );
        $this->assertFalse($new->IsOldPost());
        $this->assertNull($new->GetOldPostNo());
    }

    public function testLink() : void
    {
        $noLink = self::mockPost(40, 8, 85,
            'user3', 103, 
            'Thread 8 - A1', null,
            2, 1,
            '2020-03-30 14:50:00',
            null,
            null, null, null,
            null,
            0,
            '::1'
        );
        $this->assertFalse($noLink->HasLinkUrl());
        $this->assertNull($noLink->GetLinkUrl());
        $this->assertFalse($noLink->HasLinkText());
        $this->assertNull($noLink->GetLinkText());
        $withLink = self::mockPost(40, 8, 85,
            'user3', 103, 
            'Thread 8 - A1', null,
            2, 1,
            '2020-03-30 14:50:00',
            null,
            'https://foo.link', 'visit this', null,
            null,
            0,
            '::1'
        );
        $this->assertTrue($withLink->HasLinkUrl());
        $this->assertSame('https://foo.link', $withLink->GetLinkUrl());
        $this->assertTrue($withLink->HasLinkText());
        $this->assertSame('visit this', $withLink->GetLinkText());  
    }

    public function testImg() : void
    {
        $withImg = self::mockPost(40, 8, 85,
            'user3', 103, 
            'Thread 8 - A1', null,
            2, 1,
            '2020-03-30 14:50:00',
            null,
            null, null, 'https://bar.com/foo.gif',
            null,
            0,
            '::1'
        );
        $this->assertTrue($withImg->HasImgUrl());
        $this->assertSame('https://bar.com/foo.gif', $withImg->GetImgUrl());
        $noImg = self::mockPost(40, 8, 85,
            'user3', 103, 
            'Thread 8 - A1', null,
            2, 1,
            '2020-03-30 14:50:00',
            null,
            null, null, null,
            null,
            0,
            '::1'
        );
        $this->assertFalse($noImg->HasImgUrl());
        $this->assertNull($noImg->GetImgUrl());        
    }

    public function testEmail() : void
    {
        $withEmail = self::mockPost(40, 8, 85,
            'user3', 103, 
            'Thread 8 - A1', null,
            2, 1,
            '2020-03-30 14:50:00',
            'me@mail.com',
            null, null, null,
            null,
            0,
            '::1'
        );
        $this->assertTrue($withEmail->HasEmail());
        $this->assertSame('me@mail.com', $withEmail->GetEmail());
        $noEmail = self::mockPost(40, 8, 85,
            'user3', 103, 
            'Thread 8 - A1', null,
            2, 1,
            '2020-03-30 14:50:00',
            null,
            null, null, null,
            null,
            0,
            '::1'
        );
        $this->assertFalse($noEmail->HasEmail());
        $this->assertNull($noEmail->GetEmail());        
    }    
}