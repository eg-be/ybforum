<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/BaseTest.php';
require_once __DIR__.'/PostMock.php';
require_once __DIR__.'/UserMock.php';
require_once __DIR__.'/../src/model/ForumDb.php';

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
        $this->assertSame(9, $count);
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
     * note: This tests will only work if MariaDB and PHP both have
     * the the same Timezone set. To check the timezone with php:
     *  php -a
     *  php > echo date_default_timezone_get();
     *  Europe/Zurich
     * MariaDB will probably just use the SYSTEM timezone:
     *  MariaDB [(none)]> SHOW GLOBAL VARIABLES LIKE 'time_zone';
     *  +---------------+--------+
     *  | Variable_name | Value  |
     *  +---------------+--------+
     *  | time_zone     | SYSTEM |
     *  +---------------+--------+
     *  1 row in set (0.000 sec)
     * so, just check /etc/timezone
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
        $reqDate = new DateTime($result['request_date']);
        $ts1 = $now->getTimestamp();
        $ts2 = $reqDate->getTimestamp();
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
        $validCode = $this->db->RequestConfirmUserCode(102, 'valid-pw', 'valid@mail',
            ForumDb::CONFIRM_SOURCE_NEWUSER, '::1');
        // modify the timestamps:
        $elapsedDate = new DateTime();
        $validDate = new DateTime();
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
        $this->assertNull($this->db->VerifyConfirmUserCode('AB12', true));

        // test for known, but invalid codes (time has elapsed by one minute)
        $this->assertNull($this->db->VerifyConfirmUserCode($elapsedCode, false));
        // test that those entries are removed always (despite we set remove to false)
        $query = 'SELECT confirm_code FROM confirm_user_table '
            . 'WHERE confirm_code = BINARY :confirm_code';
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':confirm_code' => $elapsedCode));
        $result = $stmt->fetch();
        $this->assertFalse($result);
        
        // test for known valid codes: one that will elapse in one minute
        $values = $this->db->VerifyConfirmUserCode($validCode, false);
        $this->assertNotNull($values);
        // test it is not removed if not specified
        $stmt->execute(array(':confirm_code' => $validCode));
        $result = $stmt->fetch();
        $this->assertIsArray($result);        
        // test that it is removed if specified
        $values = $this->db->VerifyConfirmUserCode($validCode, true);
        $this->assertNotNull($values);
        $stmt->execute(array(':confirm_code' => $validCode));
        $result = $stmt->fetch();
        $this->assertFalse($result);  
        
        // test that the values returned are correct
        $this->assertSame(102, $values['iduser']);
        $this->assertSame('valid@mail', $values['email']);
        $this->assertSame(ForumDb::CONFIRM_SOURCE_NEWUSER, $values['confirm_source']);
        // note: password is hasehd
        password_verify('valid-pw', $values['password']);
    }

    public function testRemoveConfirmUserCode() : void
    {
        // insert some entries, test they are removed
        $this->db->RequestConfirmUserCode(101, 'new', 'new@mail', 
            ForumDb::CONFIRM_SOURCE_MIGRATE, '::1');
        $this->assertSame(1, $this->db->RemoveConfirmUserCode(101));
        $this->assertSame(0, $this->db->RemoveConfirmUserCode(101));
        // not existing entry works
        $this->assertSame(0, $this->db->RemoveConfirmUserCode(33));
    }

    public function testGetConfirmReason() : void
    {
        $this->db->RequestConfirmUserCode(101, 'new', 'new@mail', 
            ForumDb::CONFIRM_SOURCE_MIGRATE, '::1');
        $this->assertSame(ForumDb::CONFIRM_SOURCE_MIGRATE, 
            $this->db->GetConfirmReason(101));
        $this->db->RequestConfirmUserCode(101, 'new', 'new@mail', 
            ForumDb::CONFIRM_SOURCE_NEWUSER, '::1');
        $this->assertSame(ForumDb::CONFIRM_SOURCE_NEWUSER, 
        $this->db->GetConfirmReason(101));
        // test that an invalid reason throws:
        $this->db->RemoveConfirmUserCode(102);
        $insertQuery = 'INSERT INTO confirm_user_table (iduser, email, '
            . 'password, confirm_code, request_ip_address, '
            . 'confirm_source) '
            . 'VALUES(:iduser, :email, :password, '
            . ':confirm_code, :request_ip_address, :confirm_source)';
        $insertStmt = $this->db->prepare($insertQuery);
        $insertStmt->execute(array(':iduser' => 102,
            ':email' => 'foo@mail', ':password' => 'pass',
            ':confirm_code' => 'ABC', 
            ':request_ip_address' => '::1',
            ':confirm_source' => 'Foobar'));
        $this->expectException(InvalidArgumentException::class);
        $this->db->GetConfirmReason(102);
    }

    public function testConfirmUser() : void
    {
        // need a clean database, must work with a use awaiting confirmation
        self::createTestDatabase();
        $user = User::LoadUserById($this->db, 52);
        $this->assertNotNull($user);
        $this->assertFalse($user->IsConfirmed());
        $this->assertFalse($user->IsActive());
        $newPass = 'new-pw';
        $newMail = 'new@mail';
        // confirm, but dont activate:
        $now = new DateTime();
        $this->db->ConfirmUser($user->GetId(), 
            password_hash($newPass, PASSWORD_DEFAULT),
            $newMail, false);
        $user = User::LoadUserById($this->db, 52);
        $this->assertNotNull($user);
        $this->assertTrue($user->IsConfirmed());
        $this->assertFalse($user->IsActive());
        $this->assertSame($newMail, $user->GetEmail());
        // confirmation-timestamp must be somewhere around now
        $this->assertEqualsWithDelta($now->getTimestamp(), 
            $user->GetConfirmationTimestamp()->getTimestamp(), 2);

        // confirm and activate:
        $this->db->ConfirmUser($user->GetId(), 
            password_hash($newPass, PASSWORD_DEFAULT),
            $newMail, true);
        $user = User::LoadUserById($this->db, 52);
        $this->assertNotNull($user);
        $this->assertTrue($user->IsConfirmed());
        $this->assertTrue($user->IsActive());
        // note: To verify the hashed-password, just auth
        // but to auth, user mus tbe active so do that down here
        $this->assertTrue($user->Auth($newPass));
        
        // fail for unknown user-id
        $this->expectException(InvalidArgumentException::class);
        $this->db->ConfirmUser(333, 'foo', 'foo@mail', true);
    }

    public function testRequestPasswordResetCode() : void
    {
        // create an entry and verify its created with the proper value
        $now = new DateTime();
        $user = User::LoadUserById($this->db, 52);
        $this->assertNotNull($user);
        $code = $this->db->RequestPasswordResetCode($user, '::1');
        // verify returned value is in the db
        $query = 'SELECT iduser, request_date '
                . 'FROM reset_password_table '
                . 'WHERE confirm_code = BINARY :confirm_code';
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':confirm_code' => $code));
        $result = $stmt->fetch();
        $this->assertIsArray($result);
        $this->assertSame($user->GetId(), $result['iduser']);
        $ts = new DateTime($result['request_date']);
        $this->assertEqualsWithDelta($now->getTimestamp(), 
            $ts->getTimestamp(), 2);
        // check that there is only one entry, even if we create a second one:
        $newCode = $this->db->RequestPasswordResetCode($user, '::1');
        $this->assertNotSame($code, $newCode);
        $query = 'SELECT confirm_code FROM reset_password_table '
            . 'WHERE iduser = :iduser';
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':iduser' => $user->getId()));
        $result = $stmt->fetch();
        $this->assertIsArray($result);
        $this->assertSame($newCode, $result['confirm_code']);
        $this->assertFalse($stmt->fetch()); // no more data
    }

    public function testVerifyPasswordResetCode() : void
    {
        // create two codes: One that will expire in one minute
        // and one that has expired one minute ago
        $user101 = User::LoadUserById($this->db, 101);
        $user102 = User::LoadUserById($this->db, 102);
        $this->assertNotNull($user101);
        $this->assertNotNull($user102);
        $validCode = $this->db->RequestPasswordResetCode($user101, '::1');        
        $elapsedCode = $this->db->RequestPasswordResetCode($user102, '::1');        

        // modify the timestamps in the db:
        $elapsedDate = new DateTime();
        $validDate = new DateTime();
        $codeValidInterval = new DateInterval(YbForumConfig::CONF_CODE_VALID_PERIOD);
        $oneMinuteInterval = new DateInterval('PT1M');
        $elapsedDate->sub($codeValidInterval);
        $elapsedDate->sub($oneMinuteInterval);
        $validDate->sub($codeValidInterval);
        $validDate->add($oneMinuteInterval);
        $query = 'UPDATE reset_password_table SET request_date = :request_date '
                . 'WHERE confirm_code = BINARY :confirm_code';
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':request_date' => $elapsedDate->format('Y-m-d H:i:s'), 
            ':confirm_code' => $elapsedCode));
        $stmt->execute(array(':request_date' => $validDate->format('Y-m-d H:i:s'), 
            ':confirm_code' => $validCode));        

        // test that an unknown code fails to validate
        $this->assertSame(0, $this->db->VerifyPasswordResetCode('AB12', true));

        // test for known, but invalid codes (time has elapsed by one minute)
        $this->assertSame(0, $this->db->VerifyPasswordResetCode($elapsedCode, false));
        // test that those entries are removed always (despite we set remove to false)
        $query = 'SELECT confirm_code FROM reset_password_table '
            . 'WHERE confirm_code = BINARY :confirm_code';
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':confirm_code' => $elapsedCode));
        $result = $stmt->fetch();
        $this->assertFalse($result);
        
        // test for known valid codes: one that will elapse in one minute
        $iduser = $this->db->VerifyPasswordResetCode($validCode, false);
        $this->assertSame(101, $iduser);
        // test it is not removed if not specified
        $stmt->execute(array(':confirm_code' => $validCode));
        $result = $stmt->fetch();
        $this->assertIsArray($result);        
        // test that it is removed if specified
        $values = $this->db->VerifyPasswordResetCode($validCode, true);
        $this->assertSame(101, $iduser);
        $stmt->execute(array(':confirm_code' => $validCode));
        $result = $stmt->fetch();
        $this->assertFalse($result);        
    }

    public function testRemoveResetPasswordCode() : void
    {
        // insert some entries, test they are removed
        $user101 = User::LoadUserById($this->db, 101);
        $this->assertNotNull($user101);        
        $this->db->RequestPasswordResetCode($user101, '::1');
        $this->assertSame(1, $this->db->RemoveResetPasswordCode($user101->getId()));
        $this->assertSame(0, $this->db->RemoveResetPasswordCode($user101->getId()));
        // not existing entry works
        $this->assertSame(0, $this->db->RemoveResetPasswordCode(33));        
    }

    public function testUpdateUserPassword() : void
    {
        $user101 = User::LoadUserById($this->db, 101);
        $this->assertNotNull($user101);
        $this->db->UpdateUserPassword($user101->GetId(), "foobar");
        // note: Must reload the user after a password-change
        $user101 = User::LoadUserById($this->db, 101);
        $this->assertNotNull($user101);
        $this->assertTrue($user101->Auth("foobar"));
        $this->assertFalse($user101->Auth("Foobar"));
    }

    public function testRequestUpdateEmailCode() : void
    {
        // create an entry and verify its created with the proper value
        $now = new DateTime();
        $user = User::LoadUserById($this->db, 52);
        $this->assertNotNull($user);
        $code = $this->db->RequestUpdateEmailCode($user->GetId(), 
            'new-mail@mail.com', '::1');
        // verify returned value matches entry from the db
        $query = 'SELECT iduser, email, request_date '
                . 'FROM update_email_table '
                . 'WHERE confirm_code = BINARY :confirm_code';
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':confirm_code' => $code));
        $result = $stmt->fetch();
        $this->assertIsArray($result);
        $this->assertSame($user->GetId(), $result['iduser']);
        $this->assertSame('new-mail@mail.com', $result['email']);
        $ts = new DateTime($result['request_date']);
        $this->assertEqualsWithDelta($now->getTimestamp(), 
            $ts->getTimestamp(), 2);
        // check that there is only one entry, even if we create a second one:
        $newCode = $this->db->RequestUpdateEmailCode($user->GetId(), 
            'another@mail.com', '::1');
        $this->assertNotSame($code, $newCode);
        $query = 'SELECT confirm_code FROM update_email_table '
            . 'WHERE iduser = :iduser';
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':iduser' => $user->getId()));
        $result = $stmt->fetch();
        $this->assertIsArray($result);
        $this->assertSame($newCode, $result['confirm_code']);
        $this->assertFalse($stmt->fetch()); // no more data
    }

    public function testVerifyUpdateEmailCode() : void
    {
        // create two codes: One that will expire in one minute
        // and one that has expired one minute ago
        $user101 = User::LoadUserById($this->db, 101);
        $user102 = User::LoadUserById($this->db, 102);
        $this->assertNotNull($user101);
        $this->assertNotNull($user102);
        $validCode = $this->db->RequestUpdateEmailCode($user101->GetId(), 
            '101@mail', '::1');
        $elapsedCode = $this->db->RequestUpdateEmailCode($user102->GetId(), 
            '102@mail', '::1');

        // modify the timestamps in the db:
        $elapsedDate = new DateTime();
        $validDate = new DateTime();
        $codeValidInterval = new DateInterval(YbForumConfig::CONF_CODE_VALID_PERIOD);
        $oneMinuteInterval = new DateInterval('PT1M');
        $elapsedDate->sub($codeValidInterval);
        $elapsedDate->sub($oneMinuteInterval);
        $validDate->sub($codeValidInterval);
        $validDate->add($oneMinuteInterval);
        $query = 'UPDATE update_email_table SET request_date = :request_date '
                . 'WHERE confirm_code = BINARY :confirm_code';
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':request_date' => $elapsedDate->format('Y-m-d H:i:s'), 
            ':confirm_code' => $elapsedCode));
        $stmt->execute(array(':request_date' => $validDate->format('Y-m-d H:i:s'), 
            ':confirm_code' => $validCode));        

        // test that an unknown code fails to validate
        $this->assertNull($this->db->VerifyUpdateEmailCode('AB12', true));

        // test for known, but invalid codes (time has elapsed by one minute)
        $this->assertNull($this->db->VerifyUpdateEmailCode($elapsedCode, false));
        // test that those entries are removed always (despite we set remove to false)
        $query = 'SELECT confirm_code FROM update_email_table '
            . 'WHERE confirm_code = BINARY :confirm_code';
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':confirm_code' => $elapsedCode));
        $result = $stmt->fetch();
        $this->assertFalse($result);
        
        // test for known valid codes: one that will elapse in one minute
        $values = $this->db->VerifyUpdateEmailCode($validCode, false);
        $this->assertNotNull($values);
        // test it is not removed if not specified
        $stmt->execute(array(':confirm_code' => $validCode));
        $result = $stmt->fetch();
        $this->assertIsArray($result);        
        // test that it is removed if specified
        $values = $this->db->VerifyUpdateEmailCode($validCode, true);
        $this->assertNotNull($values);
        $stmt->execute(array(':confirm_code' => $validCode));
        $result = $stmt->fetch();
        $this->assertFalse($result);

        // test that the values returned are correct
        $this->assertSame(101, $values['iduser']);
        $this->assertSame('101@mail', $values['email']);
    }

    public function testRemoveUpdateEmailCode() : void
    {
        // insert some entries, test they are removed
        $user101 = User::LoadUserById($this->db, 101);
        $this->assertNotNull($user101);
        $this->db->RequestUpdateEmailCode($user101->GetId(), 'new@mail', '::1');
        $this->assertSame(1, $this->db->RemoveUpdateEmailCode($user101->getId()));
        $this->assertSame(0, $this->db->RemoveUpdateEmailCode($user101->getId()));
        // not existing entry works
        $this->assertSame(0, $this->db->RemoveUpdateEmailCode(33));        
    }

    public function testUpdateUserEmail() : void
    {
        $user101 = User::LoadUserById($this->db, 101);
        $this->assertNotNull($user101);
        $this->db->UpdateUserEmail($user101->GetId(), 'bla@mail');
        // note: Must reload the user after a password-change
        $user101 = User::LoadUserById($this->db, 101);
        $this->assertNotNull($user101);
        $this->assertSame('bla@mail', $user101->GetEmail());
    }

    public function testActivateUser() : void
    {
        // rely on a test-database
        self::createTestDatabase();
        // activate one that is not active
        $needsApproval = User::LoadUserById($this->db, 51);
        $this->assertNotNull($needsApproval);
        $this->assertFalse($needsApproval->IsActive());
        $this->db->ActivateUser($needsApproval->GetId());
        // must reload, see #21
        $needsApproval = User::LoadUserById($this->db, 51);
        $this->assertTrue($needsApproval->IsActive());

        // activating one that is already active, does nothing
        $user101 = User::LoadUserById($this->db, 101);
        $this->assertNotNull($user101);
        $this->assertTrue($user101->IsActive());
        $this->db->ActivateUser($user101->GetId());
        // must reload, see #21
        $user101 = User::LoadUserById($this->db, 101);
        $this->assertTrue($user101->IsActive());

        // Activating one with a deactivated reason, removes that reason
        $deactivated = User::LoadUserById($this->db, 50);
        $this->assertNotNull($deactivated);
        $this->assertFalse($deactivated->IsActive());
        $query = 'SELECT reason FROM user_deactivated_reason_table '
            . 'WHERE iduser = :iduser';
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':iduser' => $deactivated->GetId()));
        $result = $stmt->fetch();
        $this->assertIsArray($result);
        $this->db->ActivateUser($deactivated->GetId());
        $deactivated = User::LoadUserById($this->db, 50);  // must reload, see #21
        $this->assertTrue($deactivated->IsActive());
        $stmt->execute(array(':iduser' => $deactivated->GetId()));
        $result = $stmt->fetch();
        $this->assertFalse($result);
    }

    public function providerNotExistingNotConfirmed() : array 
    {
        return array(
            [333], // not existing in db
            [52]    // needs to confirm email
        );
    }    

    /**
     * @test
     * @dataProvider providerNotExistingNotConfirmed
     */
    public function testActivateUserFails(int $userId) : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->db->ActivateUser($userId);
    }

    public function testDeactivateUser() : void
    {
        // rely on a test-database
        self::createTestDatabase();
        // deactivate one that is active
        $user101 = User::LoadUserById($this->db, 101);
        $this->assertNotNull($user101);
        $this->assertTrue($user101->IsActive());
        $this->db->DeactivateUser($user101->GetId(), 'just for fun', 1);
        $this->assertSame($this->db->GetDeactivationReason(101), 'just for fun');
        // must reload, see #21
        $user101 = User::LoadUserById($this->db, 101);
        $this->assertFalse($user101->IsActive());

        // deactivating one that is already deactivated, does nothing
        // especially, it does not alter the deactivation-reason
        $this->db->DeactivateUser($user101->GetId(), 'deactivate again', 1);
        // must reload, see #21
        $user101 = User::LoadUserById($this->db, 101);
        $this->assertFalse($user101->IsActive());
        $this->assertSame($this->db->GetDeactivationReason(101), 'just for fun');

        // not-existing cant be deactivated
        $this->expectException(InvalidArgumentException::class);
        $this->db->DeactivateUser(333, 'not there', 1);
    }

    public function testGetDeactivationReason() : void
    {
        // check the message for our deactivated user
        $reason = $this->db->GetDeactivationReason(50);
        $this->assertSame('test deactivated by admin', $reason);
        // non-deactived, or non-existing just return null
        $reason = $this->db->GetDeactivationReason(1);
        $this->assertNull($reason);
        $reason = $this->db->GetDeactivationReason(666);
        $this->assertNull($reason);
    }

    public function testSetAdmin() : void
    {
        // rely on a test-database
        self::createTestDatabase();
        // promote one to an admin that is not yet
        $user101 = User::LoadUserById($this->db, 101);
        $this->assertNotNull($user101);
        $this->assertFalse($user101->IsAdmin());
        $this->db->SetAdmin($user101->GetId(), true);
        // must reload, see #21
        $user101 = User::LoadUserById($this->db, 101);
        $this->assertTrue($user101->IsAdmin());

        // promote an admin to an admin again, nothing changes
        $this->db->SetAdmin($user101->GetId(), true);
        // must reload, see #21
        $user101 = User::LoadUserById($this->db, 101);
        $this->assertTrue($user101->IsAdmin());

        // and remove admin
        $this->db->SetAdmin($user101->GetId(), false);
        // must reload, see #21
        $user101 = User::LoadUserById($this->db, 101);
        $this->assertFalse($user101->IsAdmin());

        // remove admin again, nothing changes
        $this->db->SetAdmin($user101->GetId(), false);
        // must reload, see #21
        $user101 = User::LoadUserById($this->db, 101);
        $this->assertFalse($user101->IsAdmin());        
    }

    /**
     * @test
     * @dataProvider providerNotExistingNotConfirmed
     */
    public function testSetAdminFails(int $userId) : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->db->SetAdmin($userId, true);
    }

    public function testMakeDummy() : void
    {
        // rely on a test-database
        self::createTestDatabase();
        // turn a user into a dummy
        $user101 = User::LoadUserById($this->db, 101);
        $this->assertNotNull($user101);
        $this->assertFalse($user101->IsDummyUser());
        $this->db->MakeDummy($user101->GetId());
        // must reload, see #21
        $user101 = User::LoadUserById($this->db, 101);
        $this->assertTrue($user101->IsDummyUser());
        // a dummy can be turned into a dummy over and over
        $dummy = User::LoadUserById($this->db, 66);
        $this->assertNotNull($dummy);
        $this->assertTrue($dummy->IsDummyUser());
        $this->db->MakeDummy($dummy->GetId());
        // must reload, see #21
        $dummy = User::LoadUserById($this->db, 66);
        $this->assertTrue($dummy->IsDummyUser());

        // not-existing cant be turned into dummy
        $this->expectException(InvalidArgumentException::class);
        $this->db->MakeDummy(333);        
    }


    public function providerZeroPosts() : array 
    {
        return array(
            [1],
            [10],
            [50] ,
            [51],
            [52],
            [66]
        );
    }


    public function providerHasPostsAndNotExisting() : array
    {
        return array(
            [101],
            [102],
            [103],
            [999]
        );
    }

    /**
    * @test
    * @dataProvider providerZeroPosts
    */
    public function testDeleteUser(int $userId) : void
    {
        // rely on a test-database
        self::createTestDatabase();   
        // only users with 0 posts can be deleted
        // try to delete all users with zero posts
        $user = User::LoadUserById($this->db, $userId);
        $this->assertNotNull($user);
        $this->db->DeleteUser($user->GetId());
        // user must be gone by now
        $user = User::LoadUserById($this->db, $userId);
        $this->assertNull($user);

        // check that deactivated_reason_table has been cleared:
        // (yes, is done by constraint of foreign key)
        $reason = $this->db->GetDeactivationReason($userId);
        $this->assertNull($reason);
    }

    /**
    * @test
    * @dataProvider providerHasPostsAndNotExisting
    */
    public function testDeleteUserFails(int $userId) : void
    {
        // rely on a test-database
        self::createTestDatabase();   
        // only users with 0 posts can be deleted
        // try to delete all users with zero posts
        $this->expectException(InvalidArgumentException::class);
        $this->db->DeleteUser($userId);
    }


    public function testGetPostByUserCount() : void
    {
        // rely on a test-database
        self::createTestDatabase();
        $this->assertSame(8, $this->db->GetPostByUserCount(101));
        $this->assertSame(6, $this->db->GetPostByUserCount(102));
        $this->assertSame(7, $this->db->GetPostByUserCount(103));
        $this->assertSame(0, $this->db->GetPostByUserCount(1));
        $this->assertSame(0, $this->db->GetPostByUserCount(666));
    }

    public function testSetPostVisible() : void
    {
        // rely on a test-database
        self::createTestDatabase();
        // hide some post within a thread:
        // its child must get hidden too
        $postA1_2 = Post::LoadPost($this->db, 23);
        $this->assertNotNull($postA1_2);
        $this->assertFalse($postA1_2->IsHidden());
        $postA1_2_1 = Post::LoadPost($this->db, 25);
        $this->assertNotNull($postA1_2_1);
        $this->assertFalse($postA1_2_1->IsHidden());
        $this->assertSame(23, $postA1_2_1->GetParentPostId());
        // now hide that tree by hiding the parent
        $this->db->SetPostVisible(23, false);
        $postA1_2 = Post::LoadPost($this->db, 23);
        $this->assertTrue($postA1_2->IsHidden());
        $postA1_2_1 = Post::LoadPost($this->db, 25);
        $this->assertTrue($postA1_2_1->IsHidden());
        // show again:
        $this->db->SetPostVisible(23, true);
        $postA1_2 = Post::LoadPost($this->db, 23);
        $this->assertFalse($postA1_2->IsHidden());
        $postA1_2_1 = Post::LoadPost($this->db, 25);
        $this->assertFalse($postA1_2_1->IsHidden());

        // fail for invalid post-ids
        $this->expectException(InvalidArgumentException::class);
        $this->db->SetPostVisible(666, false);
    }

    public function testIsDateWithinConfirmPeriod() : void
    {
        $codeValidInterval = new DateInterval(YbForumConfig::CONF_CODE_VALID_PERIOD);
        $oneSecondInterval = new DateInterval('PT3S');
        $elapsed = new DateTime();
        $valid = new DateTime();
        $elapsed->sub($codeValidInterval);
        $elapsed->sub($oneSecondInterval);
        $valid->sub($codeValidInterval);
        $valid->add($oneSecondInterval);
        $this->assertFalse($this->db->IsDateWithinConfirmPeriod($elapsed));
        $this->assertTrue($this->db->IsDateWithinConfirmPeriod($valid));
    }

    public function testIsEmailOnBlacklistExactly() : void
    {
        // rely on a test-database
        self::createTestDatabase();        
        $desc = $this->db->IsEmailOnBlacklistExactly('foo@bar.com');
        $this->assertSame('foo-bar', $desc);
        $desc = $this->db->IsEmailOnBlacklistExactly('foO@bar.net');
        $this->assertFalse($desc);
    }

    public function testIsEmailOnBlacklistRegex() : void
    {
        // rely on a test-database
        self::createTestDatabase();
        $desc = $this->db->IsEmailOnBlacklistRegex('foo@bar.ru');
        $this->assertSame('Mailadressen aus .ru sind blockiert.', $desc);
        $desc = $this->db->IsEmailOnBlacklistRegex('foO@bar.com');
        $this->assertFalse($desc);
    }


    public function testAddBlacklist() : void
    {
        // no one is adding to blacklist from tests, do not restore db
        $desc = $this->db->IsEmailOnBlacklistExactly('hans@wurst.com');
        $this->assertFalse($desc);
        $this->db->AddBlacklist('hans@wurst.com', 'hans wurst');
        $desc = $this->db->IsEmailOnBlacklistExactly('hans@wurst.com');
        $this->assertSame('hans wurst', $desc);
    }
}