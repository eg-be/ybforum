<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/BaseTest.php';
require_once __DIR__.'/PostMock.php';
require_once __DIR__.'/UserMock.php';
require_once __DIR__.'/../web/model/ForumDb.php';

/**
 * Requires a valid database to connect to, as we
 * want to really test the executed sql.
 * 
 * See README.md located in this directory, on how
 * to setup the test-database.
 * 
 */
final class ForumDbTest extends BaseTest
{
    private $db;

    public static function setUpBeforeClass(): void
    {
        // Most of the test require just a database,
        // but do not rely on an exact count of things
        // setup here, tests which require a given state
        // shall do that on their own
        BaseTest::createTestDatabase();
    }

    protected function setUp(): void
    {
        $this->db = new ForumDb(false);
    }

/*    protected function setUp(): void
    {
        // some of the tests will modify the db, 
        // just re-create from scratch on every test
        BaseTest::createTestDatabase();        
        $this->db = new ForumDb(false);
    }*/

    protected function assertPreConditions(): void
    {
        $this->assertTrue($this->db->IsConnected());
    }

    public function testIsReadOnly(): void
    {
        // a database is ro by default
        $ro = new ForumDb();
        $this->assertTrue($ro->IsReadOnly());
        // except we enfore a rw-db:
        $rw = new ForumDb(false);
        $this->assertFalse($rw->IsReadOnly());
    }

    public function testGetThreadCount() : void
    {
        BaseTest::createTestDatabase();
        $count = $this->db->GetThreadCount();
        $this->assertSame(12, $count);
    }

    public function testGetPostCount() : void
    {
        BaseTest::createTestDatabase();
        $count = $this->db->GetPostCount();
        $this->assertSame(21, $count);
    }

    public function testGetUserCount() : void
    {
        BaseTest::createTestDatabase();
        $count = $this->db->GetUserCount();
        $this->assertSame(8, $count);
    }

    public function testGetActiveUserCount() : void
    {
        BaseTest::createTestDatabase();
        $count = $this->db->GetActiveUserCount();
        $this->assertSame(4, $count);
    }

    public function testGetFromAdminDeactivatedUserCount() : void
    {
        BaseTest::createTestDatabase();
        $count = $this->db->GetFromAdminDeactivatedUserCount();
        $this->assertSame(1, $count);
    }

    public function testGetPendingAdminApprovalUserCount() : void
    {
        BaseTest::createTestDatabase();
        $count = $this->db->GetPendingAdminApprovalUserCount();
        $this->assertSame(1, $count);
    }

    public function testGetNeedMigrationUserCount() : void
    {
        BaseTest::createTestDatabase();
        $count = $this->db->GetNeedMigrationUserCount();
        $this->assertSame(1, $count);
    }

    public function testGetDummyUserCount() : void
    {
        BaseTest::createTestDatabase();        
        $count = $this->db->GetDummyUserCount();
        $this->assertSame(1, $count);
    }

    public function testGetLastThreadId() : void
    {
        BaseTest::createTestDatabase();
        $id = $this->db->GetLastThreadId();
        $this->assertSame(12, $id);
    }

    public function testAuthUser() : void
    {
        // a user with a new password that is active is ok:
        $reason = 0;
        $admin = $this->db->AuthUser('admin', 'admin-pass', $reason);
        $this->assertNotNull($admin);
        $this->assertSame(1, $admin->GetId());
        // active user fails because password missmatch
        $admin = $this->db->AuthUser('admin', 'foo', $reason);
        $this->assertNull($admin);
        $this->assertSame(ForumDb::AUTH_FAIL_REASON_PASSWORD_INVALID, $reason);
        // fail because password is correct, but user is inactive:
        $deact = $this->db->AuthUser('deactivated', 'deactivated-pass', $reason);
        $this->assertNull($deact);
        $this->assertSame(ForumDb::AUTH_FAIL_REASON_USER_IS_INACTIVE, $reason);
        // fail with wrong password on inactive: must return inactive-reason:
        $deact = $this->db->AuthUser('deactivated', 'foo', $reason);
        $this->assertNull($deact);
        $this->assertSame(ForumDb::AUTH_FAIL_REASON_USER_IS_INACTIVE, $reason);
        // fail because its a dummy
        $dummy = $this->db->AuthUser('dummy', 'foo', $reason);
        $this->assertNull($dummy);
        $this->assertSame(ForumDb::AUTH_FAIL_REASON_USER_IS_DUMMY, $reason);
        // fails because user is unknown
        $unknown = $this->db->AuthUser('anyone', 'foo', $reason);
        $this->assertNull($unknown);
        $this->assertSame(ForumDb::AUTH_FAIL_REASON_NO_SUCH_USER, $reason);
        // and auth a user that needs to migrate (but is not active yet):
        $old = $this->db->AuthUser('old-user', 'old-user-pass');
        $this->assertNotNull($old);
        $this->assertSame(10, $old->GetId());
        // but fail for an old user if pass is incorrect
        $old = $this->db->AuthUser('old-user', 'foo', $reason);
        $this->assertNull($old);
        $this->assertSame(ForumDb::AUTH_FAIL_REASON_PASSWORD_INVALID, $reason);
    }

    public function testCreateThread() : void
    {
        $oldThreadCount = $this->db->GetThreadCount();
        $oldPostCount = $this->db->GetPostCount();
        $user = User::LoadUserByNick($this->db, 'admin');
        $this->assertNotNull($user);
        // create a new thread with the minimal required arguments
        $minPostId = $this->db->CreateThread($user, 'min-thread', 
            null, null, null, null, null, '::1');
        $newThreadCount = $this->db->GetThreadCount();
        $newPostCount = $this->db->GetPostCount();
        $this->assertSame($oldThreadCount + 1, $newThreadCount);
        $this->assertSame($oldPostCount + 1, $newPostCount);
        // and one with all possible values set:
        $allPostId = $this->db->CreateThread($user, 'all-thread', 
            'content', 'mail@foo.com', 
            'http://visit.me', 'cool link', 
            'http://foo/bar.gif', '::1');
        $newThreadCount = $this->db->GetThreadCount();
        $newPostCount = $this->db->GetPostCount();
        $this->assertSame($oldThreadCount + 2, $newThreadCount);
        $this->assertSame($oldPostCount + 2, $newPostCount);

        // And check that the newly created threads / posts can be read back:
        $minPost = Post::LoadPost($this->db, $minPostId);
        $this->assertNotNull($minPost);
        $minPostRef = new PostMock($minPostId, 
            $minPost->GetThreadId(), // we cannot know the created thread-id, read from db
            null,
            $user->GetNick(), $user->GetId(),
            'min-thread', null,
            1, 0,
            $minPost->GetPostTimestamp()->format('Y-m-d H:i:s'),   // not can we know this value
            null,
            null, null, null,
            null,
            0,
            '::1'
        );
        $this->assertObjectEquals($minPostRef, $minPost);

        $allPost = Post::LoadPost($this->db, $allPostId);
        $this->assertNotNull($allPost);
        $allPostRef = new PostMock($allPostId, 
            $allPost->GetThreadId(), // we cannot know the created thread-id, read from db
            null,
            $user->GetNick(), $user->GetId(),
            'all-thread', 'content',
            1, 0,
            $allPost->GetPostTimestamp()->format('Y-m-d H:i:s'),   // not can we know this value
            'mail@foo.com',
            'http://visit.me', 'cool link', 'http://foo/bar.gif',
            null,
            0,
            '::1'
        );
        $this->assertObjectEquals($allPostRef, $allPost);        
    }

    /**
     * @dataProvider providerInactiveDummy
     * @test
     */
    public function testCreateThreadFails(User $u) : void
    {
        $this->assertTrue($u->IsDummyUser() || $u->IsActive() === false);
        $this->expectException(InvalidArgumentException::class);
        $this->db->CreateThread($u, 'title',
            null, null, null, null, null, '::1');
    }

    public function providerInactiveDummy() : array 
    {
        $db = new ForumDb();
        $deactivated = User::LoadUserByNick($db, 'deactivated');
        $dummy = User::LoadUserByNick($db, 'dummy');
        return array(
            [$deactivated], 
            [$dummy]
        );
    }

    public function testCreateReply() : void
    {
        $oldPostCount = $this->db->GetPostCount();
        $user = User::LoadUserByNick($this->db, 'user2');
        $this->assertNotNull($user);
        $parentPost = Post::LoadPost($this->db, 26);
        $this->assertNotNull($parentPost);
        // create a new post with the minimal required arguments
        $minPostId = $this->db->CreateReplay($parentPost->GetId(), $user, 
            'min-post', null, null, 
            null, null, null, '::1');
        $newPostCount = $this->db->GetPostCount();
        $this->assertSame($oldPostCount + 1, $newPostCount);
        // and one with all possible values set:
        $allPostId = $this->db->CreateReplay($parentPost->GetId(), $user, 
            'all-post', 'content', 'mail@foo.com', 
            'http://visit.me', 'cool link', 
            'http://foo/bar.gif', '::1');
        $newPostCount = $this->db->GetPostCount();
        $this->assertSame($oldPostCount + 2, $newPostCount);

        // And check that the newly created posts can be read back:
        $minPost = Post::LoadPost($this->db, $minPostId);
        $this->assertNotNull($minPost);
        $minPostRef = new PostMock($minPostId, 
            $parentPost->GetThreadId(), // must be part of parent-thread
            $parentPost->GetId(),
            $user->GetNick(), $user->GetId(),
            'min-post', null,
            8, 3,
            $minPost->GetPostTimestamp()->format('Y-m-d H:i:s'),   // not can we know this value
            null,
            null, null, null,
            null,
            0,
            '::1'
        );
        $this->assertObjectEquals($minPostRef, $minPost);

        $allPost = Post::LoadPost($this->db, $allPostId);
        $this->assertNotNull($allPost);
        $allPostRef = new PostMock($allPostId, 
            $parentPost->GetThreadId(), // must be part of parent-thread            
            $parentPost->GetId(),
            $user->GetNick(), $user->GetId(),
            'all-post', 'content',
            7, 3,
            $allPost->GetPostTimestamp()->format('Y-m-d H:i:s'),   // not can we know this value
            'mail@foo.com',
            'http://visit.me', 'cool link', 'http://foo/bar.gif',
            null,
            0,
            '::1'
        );
        $this->assertObjectEquals($allPostRef, $allPost);
    }

    /**
     * @dataProvider providerInactiveDummy
     * @test
     */
    public function testCreateReplyFailsBecauseOfUser(User $u) : void
    {
        $this->assertTrue($u->IsDummyUser() || $u->IsActive() === false);
        $this->expectException(InvalidArgumentException::class);
        $this->db->CreateReplay(30, $u, 
            'min-post', null, null, 
            null, null, null, '::1');
    }

    public function providerInvalidParentPostId() : array 
    {
        return array(
            [-1], 
            [18]
        );
    }    

    /**
     * @dataProvider providerInvalidParentPostId
     * @test
     */
    public function testCreateReplyFailsBecauseOfParent(int $parentPostId) : void
    {
        $user = User::LoadUserByNick($this->db, 'user2');
        $this->assertNotNull($user);
        $parentPost = Post::LoadPost($this->db, $parentPostId);
        $this->assertNull($parentPost);
        $this->expectException(InvalidArgumentException::class);
        $this->db->CreateReplay($parentPostId, $user, 
            'min-post', null, null, 
            null, null, null, '::1');
    }

    public function providerInvalidPostValues() : array 
    {
        $db = new ForumDb();
        $user = User::LoadUserByNick($db, 'user2');
        $this->assertNotNull($user);
        $parentPost = Post::LoadPost($db, 26);
        $this->assertNotNull($parentPost);        
        return array(
            [$parentPost->GetId(), $user, '', null, null, null, null, null, '::1'], 
            [$parentPost->GetId(), $user, ' ', null, null, null, null, null, '::1'], 
            [$parentPost->GetId(), $user, 'cont', ' ', null, null, null, null, '::1'], 
            [$parentPost->GetId(), $user, 'cont', null, ' ', null, null, null, '::1'], 
            [$parentPost->GetId(), $user, 'cont', null, null, ' ', null, null, '::1'], 
            [$parentPost->GetId(), $user, 'cont', null, null, null, ' ', null, '::1'], 
            [$parentPost->GetId(), $user, 'cont', null, null, null, null, ' ', '::1'], 
            [$parentPost->GetId(), $user, 'cont', null, null, null, null, null, ''], 
            [$parentPost->GetId(), $user, 'cont', null, null, null, null, null, ' '], 
        );
    }    

    /**
     * @dataProvider providerInvalidPostValues
     * @test
     */
    public function testCreateReplyFailsBecauseOfValues(int $parentPostId,
        User $user, string $title, 
        ?string $content, ?string $email, 
        ?string $linkUrl, ?string $linkText,
        ?string $imgUrl, string $clientIpAddress) : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->db->CreateReplay($parentPostId, $user, 
            $title, $content, $email, 
            $linkUrl, $linkText, $imgUrl, $clientIpAddress);
    }    

    public function providerNewUserData() : array
    {
        return array(
            ['foo', 'foo@mail.com', null],      // registration-msg is not required
            ['bar', 'bar@mail.com', 'hello world']
        );
    }

    /**
     * @dataProvider providerNewUserData
     * @test
     */
    public function testCreateNewUser(string $nick, string $mail, ?string $regMsg) : void
    {
        // Creating a user works, if neither nick nor email is already set:
        // registration_msg is optinal
        // new users are inactive, have no password set and no confirmation-ts

        $newId = $this->db->CreateNewUser($nick, $mail, $regMsg);
        $this->assertNotNull($newId);
        $this->assertGreaterThan(0, $newId);
        // read back created user and verify:
        $newUser = User::LoadUserById($this->db, $newId);
        $this->assertNotNull($newUser);
        $newUserRef = new UserMock($newId, $nick, $mail, 
            0, 0,
            $newUser->GetRegistrationTimestamp()->Format('Y-m-d H:i:s'), $regMsg,
            null, null, null
        );
        $this->assertObjectEquals($newUserRef, $newUser);
    }

    public function providerNewUserDataFail() : array
    {
        return array(
            ['user1', 'foo@mail.com'],      // nick already set
            ['bar', 'user1@dev'],           // mail already used
            ['user1', 'user1@dev'],         // both invalid
            ['', 'foo@mail.com'],           // no empty values allowed
            [' ', 'foo@mail.com'],           // no empty values allowed
            ['user22', ''],
            ['user22', ' ']
        );
    }    

    /**
     * @dataProvider providerNewUserDataFail
     * @test
     */    
    public function testCreateNewUserFail(string $nick, string $mail) : void
    {
        $this->expectException(InvalidArgumentException::class);        
        $newId = $this->db->CreateNewUser($nick, $mail, null);
    }

    public function providerRequestConfirmUserCode() : array
    {
        return array(
            [101, 'new-pass', 'mail@dev', ForumDb::CONFIRM_SOURCE_MIGRATE, '::1'],
            [101, 'new-pass', 'mail@dev', ForumDb::CONFIRM_SOURCE_NEWUSER, '::1']
        );
    }

    /**
     * @dataProvider providerRequestConfirmUserCode
     * @test
     */       
    public function testRequestConfirmUserCodeCreateEntries(int $userId, 
        string $newPass, string $newMail, string $confSource, 
        string $clientIp) : void
    {
        // test that entries are created propery
        $now = new DateTime();
        $code = $this->db->RequestConfirmUserCode($userId, 
            $newPass, $newMail, $confSource, $clientIp);
        $this->assertNotEmpty($code);

        // read back the values:
        $query = 'SELECT iduser, email, '
        . 'password, request_date, '
        . 'confirm_source, request_ip_address '
        . 'FROM confirm_user_table '
        . 'WHERE confirm_code = BINARY :confirm_code';
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':confirm_code' => $code));
        $result = $stmt->fetch();
        $this->assertNotNull($result);
        $this->assertSame($userId, $result['iduser']);
        $this->assertSame($newMail, $result['email']);
        $this->assertSame($confSource, $result['confirm_source']);
        $this->assertSame($clientIp, $result['request_ip_address']);
        // pass must have been hashed properly:
        $hashedPw = $result['password'];
        $this->assertNotNull($hashedPw);
        $this->assertNotEquals($newPass, $hashedPw);
        $this->assertTrue(password_verify($newPass, $hashedPw));
        // request_date must be somewhere around now
        // (note: we do not have ms in the test-db)
        // attention: MySql returns a local time
        // but DateTime('xx') uses UTC as default?
        $tz = new DateTimeZone('Europe/Zurich');
        $reqDate = new DateTime($result['request_date'], $tz);
        $ts1 = $now->getTimestamp();
        $ts2 = $reqDate->getTimestamp();
        // todo: here, with mysql we get local (?) timestamps
        $this->assertEqualsWithDelta($now->getTimestamp(), 
            $reqDate->getTimestamp(), 2);
    }

    /**
     * @dataProvider providerRequestConfirmUserCode
     * @test
     */       
    public function testRequestConfirmUserCodeEntriesRemoved(int $userId, 
        string $newPass, string $newMail, string $confSource, 
        string $clientIp) : void
    {
        // test that entries referring the same user are removed
        // before a new one is created.
        // our dataprovider uses the same user, check that
        // there is only one entry for that user
        $now = new DateTime();
        $code = $this->db->RequestConfirmUserCode($userId, 
            $newPass, $newMail, $confSource, $clientIp);
        $this->assertNotEmpty($code);
        $inserted = new DateTime();

        // read back the values: Only one entry can exist
        $query = 'SELECT COUNT(*) '
        . 'FROM confirm_user_table '
        . 'WHERE iduser = :iduser';
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':iduser' => $userId));
        $result = $stmt->fetch(PDO::FETCH_NUM);
        $this->assertSame(1, $result[0]);
    }

    public function providerRequestConfirmUserCodeFails() : array
    {
        return array(
            [999, 'new-pass', 'mail@dev', ForumDb::CONFIRM_SOURCE_MIGRATE, '::1'],
            [101, '', 'mail@dev', ForumDb::CONFIRM_SOURCE_NEWUSER, '::1'],
            [101, 'new-pass', '', ForumDb::CONFIRM_SOURCE_MIGRATE, '::1'],
            [101, 'new-pass', '', ForumDb::CONFIRM_SOURCE_MIGRATE, ''],
            [101, 'new-pass', 'mail@dev', 'invalid source', '::1']
        );
    }

    /**
     * @dataProvider providerRequestConfirmUserCodeFails
     * @test
     */       
    public function testRequestConfirmUserCodeFails(int $userId, 
        string $newPass, string $newMail, string $confSource, 
        string $clientIp) : void
    {
        // fail if user is unknown, if mail or pass is empty
        // or if source is not set to a known value
        $this->expectException(Exception::class);        
        $code = $this->db->RequestConfirmUserCode($userId, 
            $newPass, $newMail, $confSource, $clientIp);
    }

    public function testVerifyConfirmUserCode() : void
    {
        // create two entries: one that has elapsed one minute ago
        // and one that will elapse in one minute
        $elapsedCode = $this->db->RequestConfirmUserCode(101, 'new-pw', 'new@mail', 
            ForumDb::CONFIRM_SOURCE_MIGRATE, '::1');
        $validCode = $this->db->RequestConfirmUserCode(102, 'new-ps', 'new@mail',
            ForumDb::CONFIRM_SOURCE_NEWUSER, '::1');
        // modify the timestamps:
        $tz = new DateTimeZone('Europe/Zurich');
        $elapsedDate = new DateTime("now", $tz);
        $validDate = new DateTime("now", $tz);
        $codeValidInterval = new DateInterval(YbForumConfig::CONF_CODE_VALID_PERIOD);
        $oneMinuteInterval = new DateInterval('PT1M');
        $elapsedDate->sub($codeValidInterval);
        $elapsedDate->sub($oneMinuteInterval);
        $validDate->sub($codeValidInterval);
        $validDate->add($oneMinuteInterval);
        $query = 'UPDATE confirm_user_table SET request_date = :request_date '
            . 'WHERE confirm_code = BINARY :confirm_code';
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':request_date' => $elapsedDate->format('Y-m-d H:i:s'), 
            ':confirm_code' => $elapsedCode));
        $stmt->execute(array(':request_date' => $validDate->format('Y-m-d H:i:s'), 
            ':confirm_code' => $validCode));


        // test that an unknown code fails to validate

        // test for known, but invalid codes (time has elapsed by one minute)
        // test that those entries are removed always

        // test for known valid codes: one that will elapse in one minute
        // test it is not removed if not specified
        // test that it is removed if specified

        // test that the values returned are correct
    }
}