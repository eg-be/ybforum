<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../BaseTest.php';
require_once __DIR__ . '/../../src/model/ForumDb.php';

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
    private ForumDb $db;

    public static function setUpBeforeClass(): void
    {
        // Most of the test require just a database,
        // but do not rely on an exact count of things
        // setup here, tests which require a given state
        // shall do that on their own
        BaseTest::createTestDatabase();
    }

    private User $user1;
    private User $user50;
    private User $user101;
    private User $user102;
    private User $user103;
    private User $user666;

    protected function setUp(): void
    {
        // an rw-db
        $this->db = new ForumDb(false);
        // and some user-mocks that return a user-id
        $this->user1 = static::createStub(User::class);
        $this->user1->method('GetId')->willReturn(1);
        $this->user50 = static::createStub(User::class);
        $this->user50->method('GetId')->willReturn(50);
        $this->user101 = static::createStub(User::class);
        $this->user101->method('GetId')->willReturn(101);
        $this->user102 = static::createStub(User::class);
        $this->user102->method('GetId')->willReturn(102);
        $this->user103 = static::createStub(User::class);
        $this->user103->method('GetId')->willReturn(103);

        // non-existing in db
        $this->user666 = static::createStub(User::class);
        $this->user666->method('GetId')->willReturn(666);
    }

    protected function assertPreConditions(): void
    {
        static::assertTrue($this->db->isConnected());
    }

    public function testisReadOnly(): void
    {
        // a database is ro by default
        $ro = new ForumDb();
        static::assertTrue($ro->isReadOnly());
        // except we enfore a rw-db:
        $rw = new ForumDb(false);
        static::assertFalse($rw->isReadOnly());
    }

    public function testgetThreadCount(): void
    {
        BaseTest::createTestDatabase();
        $count = $this->db->getThreadCount();
        static::assertSame(12, $count);
    }

    public function testgetPostCount(): void
    {
        BaseTest::createTestDatabase();
        $count = $this->db->getPostCount();
        static::assertSame(21, $count);
    }

    public function testgetUserCount(): void
    {
        BaseTest::createTestDatabase();
        $count = $this->db->getUserCount();
        static::assertSame(9, $count);
    }

    public function testgetActiveUserCount(): void
    {
        BaseTest::createTestDatabase();
        $count = $this->db->getActiveUserCount();
        static::assertSame(4, $count);
    }

    public function testgetFromAdminDeactivatedUserCount(): void
    {
        BaseTest::createTestDatabase();
        $count = $this->db->getFromAdminDeactivatedUserCount();
        static::assertSame(1, $count);
    }

    public function testgetPendingAdminApprovalUserCount(): void
    {
        BaseTest::createTestDatabase();
        $count = $this->db->getPendingAdminApprovalUserCount();
        static::assertSame(1, $count);
    }

    public function testgetNeedMigrationUserCount(): void
    {
        BaseTest::createTestDatabase();
        $count = $this->db->getNeedMigrationUserCount();
        static::assertSame(1, $count);
    }

    public function testgetDummyUserCount(): void
    {
        BaseTest::createTestDatabase();
        $count = $this->db->getDummyUserCount();
        static::assertSame(1, $count);
    }

    public function testgetLastThreadId(): void
    {
        BaseTest::createTestDatabase();
        $id = $this->db->getLastThreadId();
        static::assertSame(12, $id);
    }

    public function testauthUser(): void
    {
        // a user with a new password that is active is ok:
        $reason = 0;
        $admin = $this->db->authUser('admin', 'admin-pass', $reason);
        static::assertNotNull($admin);
        static::assertSame(1, $admin->getId());
        // active user fails because password missmatch
        $admin = $this->db->authUser('admin', 'foo', $reason);
        static::assertNull($admin);
        static::assertSame(ForumDb::AUTH_FAIL_REASON_PASSWORD_INVALID, $reason);
        // fail because password is correct, but user is inactive:
        $deact = $this->db->authUser('deactivated', 'deactivated-pass', $reason);
        static::assertNull($deact);
        static::assertSame(ForumDb::AUTH_FAIL_REASON_USER_IS_INACTIVE, $reason);
        // fail with wrong password on inactive: must return inactive-reason:
        $deact = $this->db->authUser('deactivated', 'foo', $reason);
        static::assertNull($deact);
        static::assertSame(ForumDb::AUTH_FAIL_REASON_USER_IS_INACTIVE, $reason);
        // fail because its a dummy
        $dummy = $this->db->authUser('dummy', 'foo', $reason);
        static::assertNull($dummy);
        static::assertSame(ForumDb::AUTH_FAIL_REASON_USER_IS_DUMMY, $reason);
        // fails because user is unknown
        $unknown = $this->db->authUser('anyone', 'foo', $reason);
        static::assertNull($unknown);
        static::assertSame(ForumDb::AUTH_FAIL_REASON_NO_SUCH_USER, $reason);
        // and auth a user that needs to migrate (but is not active yet):
        $old = $this->db->authUser('old-user', 'old-user-pass');
        static::assertNotNull($old);
        static::assertSame(10, $old->getId());
        // but fail for an old user if pass is incorrect
        $old = $this->db->authUser('old-user', 'foo', $reason);
        static::assertNull($old);
        static::assertSame(ForumDb::AUTH_FAIL_REASON_PASSWORD_INVALID, $reason);
    }

    public function testauthUser2(): void
    {
        // a user that can loggin: failreason set to null
        $result = $this->db->authUser2('admin', 'admin-pass');
        static::assertNotNull($result);
        static::assertNotNull($result[ForumDb::USER_KEY]);
        static::assertSame(1, $result[ForumDb::USER_KEY]->getId());
        static::assertNull($result[ForumDb::AUTH_FAIL_REASON_KEY]);

        // active user fails because password missmatch
        $result = $this->db->authUser2('admin', 'foo');
        static::assertNotNull($result);
        static::assertNull($result[ForumDb::USER_KEY]);
        static::assertNotNull($result[ForumDb::AUTH_FAIL_REASON_KEY]);
        static::assertSame(ForumDb::AUTH_FAIL_REASON_PASSWORD_INVALID, $result[ForumDb::AUTH_FAIL_REASON_KEY]);
    }

    public function testcreateThread(): void
    {
        $oldThreadCount = $this->db->getThreadCount();
        $oldPostCount = $this->db->getPostCount();
        $user = $this->db->loadUserByNick('admin');
        static::assertNotNull($user);
        // create a new thread with the minimal required arguments
        $minPostId = $this->db->createThread(
            $user,
            'min-thread',
            null,
            null,
            null,
            null,
            null,
            '::1'
        );
        $newThreadCount = $this->db->getThreadCount();
        $newPostCount = $this->db->getPostCount();
        static::assertSame($oldThreadCount + 1, $newThreadCount);
        static::assertSame($oldPostCount + 1, $newPostCount);
        // and one with all possible values set:
        $allPostId = $this->db->createThread(
            $user,
            'all-thread',
            'content',
            'mail@foo.com',
            'http://visit.me',
            'cool link',
            'http://foo/bar.gif',
            '::1'
        );
        $newThreadCount = $this->db->getThreadCount();
        $newPostCount = $this->db->getPostCount();
        static::assertSame($oldThreadCount + 2, $newThreadCount);
        static::assertSame($oldPostCount + 2, $newPostCount);

        // And check that the newly created threads / posts can be read back:
        $minPost = $this->db->loadPost($minPostId);
        static::assertNotNull($minPost);
        $minPostRef = self::mockPost(
            $minPostId,
            $minPost->getThreadId(), // we cannot know the created thread-id, read from db
            null,
            $user->getNick(),
            $user->getId(),
            'min-thread',
            null,
            1,
            0,
            $minPost->getPostTimestamp()->format('Y-m-d H:i:s'),   // not can we know this value
            null,
            null,
            null,
            null,
            null,
            0,
            '::1'
        );
        static::assertObjectEquals($minPostRef, $minPost);

        $allPost = $this->db->loadPost($allPostId);
        static::assertNotNull($allPost);
        $allPostRef = self::mockPost(
            $allPostId,
            $allPost->getThreadId(), // we cannot know the created thread-id, read from db
            null,
            $user->getNick(),
            $user->getId(),
            'all-thread',
            'content',
            1,
            0,
            $allPost->getPostTimestamp()->format('Y-m-d H:i:s'),   // not can we know this value
            'mail@foo.com',
            'http://visit.me',
            'cool link',
            'http://foo/bar.gif',
            null,
            0,
            '::1'
        );
        static::assertObjectEquals($allPostRef, $allPost);
    }

    public function testCreateThreadFailsForInactive(): void
    {
        $inactive = static::createStub(User::class);
        $inactive->method('IsDummyUser')->willReturn(true);
        $inactive->method('IsActive')->willReturn(false);
        $this->expectException(InvalidArgumentException::class);
        $this->db->createThread(
            $inactive,
            'title',
            null,
            null,
            null,
            null,
            null,
            '::1'
        );
    }

    public function testCreateThreadFailsForDummy(): void
    {
        $dummy = static::createStub(User::class);
        $dummy->method('IsDummyUser')->willReturn(true);
        $dummy->method('IsActive')->willReturn(true);
        $this->expectException(InvalidArgumentException::class);
        $this->db->createThread(
            $dummy,
            'title',
            null,
            null,
            null,
            null,
            null,
            '::1'
        );
    }

    public function testCreateReply(): void
    {
        $oldPostCount = $this->db->getPostCount();
        $user = $this->db->loadUserByNick('user2');
        static::assertNotNull($user);
        $parentPost = $this->db->loadPost(26);
        static::assertNotNull($parentPost);
        // create a new post with the minimal required arguments
        $minPostId = $this->db->createReplay(
            $parentPost->getId(),
            $user,
            'min-post',
            null,
            null,
            null,
            null,
            null,
            '::1'
        );
        $newPostCount = $this->db->getPostCount();
        static::assertSame($oldPostCount + 1, $newPostCount);
        // and one with all possible values set:
        $allPostId = $this->db->createReplay(
            $parentPost->getId(),
            $user,
            'all-post',
            'content',
            'mail@foo.com',
            'http://visit.me',
            'cool link',
            'http://foo/bar.gif',
            '::1'
        );
        $newPostCount = $this->db->getPostCount();
        static::assertSame($oldPostCount + 2, $newPostCount);

        // And check that the newly created posts can be read back:
        $minPost = $this->db->loadPost($minPostId);
        static::assertNotNull($minPost);
        $minPostRef = self::mockPost(
            $minPostId,
            $parentPost->getThreadId(), // must be part of parent-thread
            $parentPost->getId(),
            $user->getNick(),
            $user->getId(),
            'min-post',
            null,
            8,
            3,
            $minPost->getPostTimestamp()->format('Y-m-d H:i:s'),   // not can we know this value
            null,
            null,
            null,
            null,
            null,
            0,
            '::1'
        );
        static::assertObjectEquals($minPostRef, $minPost);

        $allPost = $this->db->loadPost($allPostId);
        static::assertNotNull($allPost);
        $allPostRef = self::mockPost(
            $allPostId,
            $parentPost->getThreadId(), // must be part of parent-thread
            $parentPost->getId(),
            $user->getNick(),
            $user->getId(),
            'all-post',
            'content',
            7,
            3,
            $allPost->getPostTimestamp()->format('Y-m-d H:i:s'),   // not can we know this value
            'mail@foo.com',
            'http://visit.me',
            'cool link',
            'http://foo/bar.gif',
            null,
            0,
            '::1'
        );
        static::assertObjectEquals($allPostRef, $allPost);
    }

    public function testCreateReplyFailsForInactive(): void
    {
        $inactive = static::createStub(User::class);
        $inactive->method('IsDummyUser')->willReturn(false);
        $inactive->method('IsActive')->willReturn(false);
        $this->expectException(InvalidArgumentException::class);
        $this->db->createReplay(
            30,
            $inactive,
            'min-post',
            null,
            null,
            null,
            null,
            null,
            '::1'
        );
    }

    public function testCreateReplyFailsForDummy(): void
    {
        $dummy = static::createStub(User::class);
        $dummy->method('IsDummyUser')->willReturn(true);
        $dummy->method('IsActive')->willReturn(true);
        $this->expectException(InvalidArgumentException::class);
        $this->db->createReplay(
            30,
            $dummy,
            'min-post',
            null,
            null,
            null,
            null,
            null,
            '::1'
        );
    }

    public static function providerInvalidParentPostId(): array
    {
        return [
            [-1],
            [18],
        ];
    }

    #[DataProvider('providerInvalidParentPostId')]
    public function testCreateReplyFailsBecauseOfParent(int $parentPostId): void
    {
        $user = $this->db->loadUserByNick('user2');
        static::assertNotNull($user);
        $parentPost = $this->db->loadPost($parentPostId);
        static::assertNull($parentPost);
        $this->expectException(InvalidArgumentException::class);
        $this->db->createReplay(
            $parentPostId,
            $user,
            'min-post',
            null,
            null,
            null,
            null,
            null,
            '::1'
        );
    }

    public static function providerInvalidPostValues(): array
    {
        return [
            [26, '', null, null, null, null, null, '::1'],
            [26, ' ', null, null, null, null, null, '::1'],
            [26, 'cont', ' ', null, null, null, null, '::1'],
            [26, 'cont', null, ' ', null, null, null, '::1'],
            [26, 'cont', null, null, ' ', null, null, '::1'],
            [26, 'cont', null, null, null, ' ', null, '::1'],
            [26, 'cont', null, null, null, null, ' ', '::1'],
            [26, 'cont', null, null, null, null, null, ''],
            [26, 'cont', null, null, null, null, null, ' '],
        ];
    }

    #[DataProvider('providerInvalidPostValues')]
    public function testCreateReplyFailsBecauseOfValues(
        int $parentPostId,
        string $title,
        ?string $content,
        ?string $email,
        ?string $linkUrl,
        ?string $linkText,
        ?string $imgUrl,
        string $clientIpAddress
    ): void {
        $user = static::createStub(User::class);
        $user->method('GetId')->willReturn(102);
        $user->method('GetNick')->willReturn('user2');
        $user->method('GetEmail')->willReturn('user2@dev');
        $this->expectException(InvalidArgumentException::class);
        $this->db->createReplay(
            $parentPostId,
            $user,
            $title,
            $content,
            $email,
            $linkUrl,
            $linkText,
            $imgUrl,
            $clientIpAddress
        );
    }

    public static function providerNewUserData(): array
    {
        return [
            ['foo', 'foo@mail.com', null],      // registration-msg is not required
            ['bar', 'bar@mail.com', 'hello world'],
        ];
    }

    #[DataProvider('providerNewUserData')]
    public function testcreateNewUser(string $nick, string $mail, ?string $regMsg): void
    {
        // Creating a user works, if neither nick nor email is already set:
        // registration_msg is optinal
        // new users are inactive, have no password set and no confirmation-ts

        $newUser = $this->db->createNewUser($nick, $mail, $regMsg);
        static::assertNotNull($newUser);
        static::assertGreaterThan(0, $newUser->getId());
        static::assertNotNull($newUser);

        $newUserRef = self::mockUser(
            $newUser->getId(),
            $nick,
            $mail,
            0,
            0,
            $newUser->getRegistrationTimestamp()->Format('Y-m-d H:i:s'),
            $regMsg,
            null,
            null,
            null
        );

        static::assertObjectEquals($newUserRef, $newUser);
    }

    public static function providerNewUserDataFail(): array
    {
        return [
            ['user1', 'foo@mail.com'],      // nick already set
            ['bar', 'user1@dev'],           // mail already used
            ['user1', 'user1@dev'],         // both invalid
            ['', 'foo@mail.com'],           // no empty values allowed
            [' ', 'foo@mail.com'],           // no empty values allowed
            ['user22', ''],
            ['user22', ' '],
        ];
    }

    #[DataProvider('providerNewUserDataFail')]
    public function testCreateNewUserFail(string $nick, string $mail): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->db->createNewUser($nick, $mail, null);
    }

    public static function providerrequestConfirmUserCode(): array
    {
        return [
            [101, 'new-pass', 'mail@dev', ForumDb::CONFIRM_SOURCE_MIGRATE, '::1'],
            [101, 'new-pass', 'mail@dev', ForumDb::CONFIRM_SOURCE_NEWUSER, '::1'],
        ];
    }

    /**
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
    #[DataProvider('providerRequestConfirmUserCode')]
    public function testRequestConfirmUserCodeCreateEntries(
        int $userId,
        string $newPass,
        string $newMail,
        string $confSource,
        string $clientIp
    ): void {
        // test that entries are created propery
        $now = new DateTime();
        $userMock = static::createStub(User::class);
        $userMock->method('GetId')->willReturn($userId);
        $code = $this->db->requestConfirmUserCode(
            $userMock,
            $newPass,
            $newMail,
            $confSource,
            $clientIp
        );
        static::assertNotEmpty($code);

        // read back the values:
        $query = 'SELECT iduser, email, '
        . 'password, request_date, '
        . 'confirm_source, request_ip_address '
        . 'FROM confirm_user_table '
        . 'WHERE confirm_code = BINARY :confirm_code';
        $stmt = $this->db->prepare($query);
        $stmt->execute([':confirm_code' => $code]);
        $result = $stmt->fetch();
        static::assertNotNull($result);
        static::assertSame($userId, $result['iduser']);
        static::assertSame($newMail, $result['email']);
        static::assertSame($confSource, $result['confirm_source']);
        static::assertSame($clientIp, $result['request_ip_address']);
        // pass must have been hashed properly:
        $hashedPw = $result['password'];
        static::assertNotNull($hashedPw);
        static::assertNotEquals($newPass, $hashedPw);
        static::assertTrue(password_verify($newPass, $hashedPw));
        // request_date must be somewhere around now
        // (note: we do not have ms in the test-db)
        $reqDate = new DateTime($result['request_date']);
        $ts1 = $now->getTimestamp();
        $ts2 = $reqDate->getTimestamp();
        static::assertEqualsWithDelta(
            $now->getTimestamp(),
            $reqDate->getTimestamp(),
            2
        );
    }

    #[DataProvider('providerRequestConfirmUserCode')]
    public function testRequestConfirmUserCodeEntriesRemoved(
        int $userId,
        string $newPass,
        string $newMail,
        string $confSource,
        string $clientIp
    ): void {
        // test that entries referring the same user are removed
        // before a new one is created.
        // our dataprovider uses the same user, check that
        // there is only one entry for that user
        $now = new DateTime();
        $userMock = static::createStub(User::class);
        $userMock->method('GetId')->willReturn($userId);
        $code = $this->db->requestConfirmUserCode(
            $userMock,
            $newPass,
            $newMail,
            $confSource,
            $clientIp
        );
        static::assertNotEmpty($code);
        $inserted = new DateTime();

        // read back the values: Only one entry can exist
        $query = 'SELECT COUNT(*) '
        . 'FROM confirm_user_table '
        . 'WHERE iduser = :iduser';
        $stmt = $this->db->prepare($query);
        $stmt->execute([':iduser' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_NUM);
        static::assertSame(1, $result[0]);
    }

    public static function providerRequestConfirmUserCodeFails(): array
    {
        return [
            [999, 'new-pass', 'mail@dev', ForumDb::CONFIRM_SOURCE_MIGRATE, '::1'],
            [101, '', 'mail@dev', ForumDb::CONFIRM_SOURCE_NEWUSER, '::1'],
            [101, 'new-pass', '', ForumDb::CONFIRM_SOURCE_MIGRATE, '::1'],
            [101, 'new-pass', '', ForumDb::CONFIRM_SOURCE_MIGRATE, ''],
            [101, 'new-pass', 'mail@dev', 'invalid source', '::1'],
        ];
    }

    #[DataProvider('providerRequestConfirmUserCodeFails')]
    public function testRequestConfirmUserCodeFails(
        int $userId,
        string $newPass,
        string $newMail,
        string $confSource,
        string $clientIp
    ): void {
        // fail if user is unknown, if mail or pass is empty
        // or if source is not set to a known value
        $this->expectException(Exception::class);
        $userMock = static::createStub(User::class);
        $userMock->method('GetId')->willReturn($userId);
        $code = $this->db->requestConfirmUserCode(
            $userMock,
            $newPass,
            $newMail,
            $confSource,
            $clientIp
        );
    }

    public function testverifyConfirmUserCode(): void
    {
        // create two entries: one that has elapsed one minute ago
        // and one that will elapse in one minute
        $elapsedCode = $this->db->requestConfirmUserCode(
            $this->user101,
            'new-pw',
            'new@mail',
            ForumDb::CONFIRM_SOURCE_MIGRATE,
            '::1'
        );
        $validCode = $this->db->requestConfirmUserCode(
            $this->user102,
            'valid-pw',
            'valid@mail',
            ForumDb::CONFIRM_SOURCE_NEWUSER,
            '::1'
        );
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
        $stmt->execute([':request_date' => $elapsedDate->format('Y-m-d H:i:s'),
            ':confirm_code' => $elapsedCode]);
        $stmt->execute([':request_date' => $validDate->format('Y-m-d H:i:s'),
            ':confirm_code' => $validCode]);

        // test that an unknown code fails to validate
        static::assertNull($this->db->verifyConfirmUserCode('AB12', true));

        // test for known, but invalid codes (time has elapsed by one minute)
        static::assertNull($this->db->verifyConfirmUserCode($elapsedCode, false));
        // test that those entries are removed always (despite we set remove to false)
        $query = 'SELECT confirm_code FROM confirm_user_table '
            . 'WHERE confirm_code = BINARY :confirm_code';
        $stmt = $this->db->prepare($query);
        $stmt->execute([':confirm_code' => $elapsedCode]);
        $result = $stmt->fetch();
        static::assertFalse($result);

        // test for known valid codes: one that will elapse in one minute
        $values = $this->db->verifyConfirmUserCode($validCode, false);
        static::assertNotNull($values);
        // test it is not removed if not specified
        $stmt->execute([':confirm_code' => $validCode]);
        $result = $stmt->fetch();
        static::assertIsArray($result);
        // test that it is removed if specified
        $values = $this->db->verifyConfirmUserCode($validCode, true);
        static::assertNotNull($values);
        $stmt->execute([':confirm_code' => $validCode]);
        $result = $stmt->fetch();
        static::assertFalse($result);

        // test that the values returned are correct
        static::assertSame(102, $values['iduser']);
        static::assertSame('valid@mail', $values['email']);
        static::assertSame(ForumDb::CONFIRM_SOURCE_NEWUSER, $values['confirm_source']);
        // note: password is hasehd
        password_verify('valid-pw', $values['password']);
    }

    public function testremoveConfirmUserCode(): void
    {
        // insert some entries, test they are removed
        $this->db->requestConfirmUserCode(
            $this->user101,
            'new',
            'new@mail',
            ForumDb::CONFIRM_SOURCE_MIGRATE,
            '::1'
        );
        static::assertSame(1, $this->db->removeConfirmUserCode($this->user101));
        static::assertSame(0, $this->db->removeConfirmUserCode($this->user102));
        // not existing user entry works (this is rather stupid, how would you ever construct such a user?)
        $user33 = static::createStub(User::class);
        $user33->method('GetId')->willReturn(33);
        static::assertSame(0, $this->db->removeConfirmUserCode($user33));
    }

    public function testgetConfirmReason(): void
    {
        $this->db->requestConfirmUserCode(
            $this->user101,
            'new',
            'new@mail',
            ForumDb::CONFIRM_SOURCE_MIGRATE,
            '::1'
        );
        static::assertSame(
            ForumDb::CONFIRM_SOURCE_MIGRATE,
            $this->db->getConfirmReason($this->user101)
        );
        $this->db->requestConfirmUserCode(
            $this->user101,
            'new',
            'new@mail',
            ForumDb::CONFIRM_SOURCE_NEWUSER,
            '::1'
        );
        static::assertSame(
            ForumDb::CONFIRM_SOURCE_NEWUSER,
            $this->db->getConfirmReason($this->user101)
        );
        // test that an invalid reason throws:
        $this->db->removeConfirmUserCode($this->user102);
        $insertQuery = 'INSERT INTO confirm_user_table (iduser, email, '
            . 'password, confirm_code, request_ip_address, '
            . 'confirm_source) '
            . 'VALUES(:iduser, :email, :password, '
            . ':confirm_code, :request_ip_address, :confirm_source)';
        $insertStmt = $this->db->prepare($insertQuery);
        $insertStmt->execute([':iduser' => 102,
            ':email' => 'foo@mail', ':password' => 'pass',
            ':confirm_code' => 'ABC',
            ':request_ip_address' => '::1',
            ':confirm_source' => 'Foobar']);
        $this->expectException(InvalidArgumentException::class);
        $this->db->getConfirmReason($this->user102);
    }

    public function testconfirmUser(): void
    {
        // need a clean database, must work with a user awaiting confirmation
        self::createTestDatabase();
        $user = $this->db->loadUserById(52);
        static::assertNotNull($user);
        static::assertFalse($user->isConfirmed());
        static::assertFalse($user->isActive());
        $newPass = 'new-pw';
        $newMail = 'new@mail';
        // confirm, but dont activate:
        $now = new DateTime();
        $this->db->confirmUser(
            $user,
            password_hash($newPass, PASSWORD_DEFAULT),
            $newMail,
            false
        );
        // test that the user-object we passed now reflects the change,
        // without the need to explicitly reload it:
        static::assertTrue($user->isConfirmed());
        static::assertFalse($user->isActive()); // still not active, only confiremd
        static::assertSame($newMail, $user->getEmail());
        // confirmation-timestamp must be somewhere around now
        static::assertEqualsWithDelta(
            $now->getTimestamp(),
            $user->getConfirmationTimestamp()->getTimestamp(),
            2
        );

        // confirm and activate:
        $this->db->confirmUser(
            $user,
            password_hash($newPass, PASSWORD_DEFAULT),
            $newMail,
            true
        );
        static::assertTrue($user->isConfirmed());
        static::assertTrue($user->isActive());
        // note: To verify the hashed-password, just auth
        // but to auth, user mus tbe active so do that down here
        static::assertTrue($user->auth($newPass));

        // fail for unknown user-id
        $this->expectException(InvalidArgumentException::class);
        $user333 = static::createStub(User::class);
        $user333->method('GetId')->willReturn(333);
        $this->db->confirmUser($user333, 'foo', 'foo@mail', true);
    }

    public function testrequestPasswordResetCode(): void
    {
        // create an entry and verify its created with the proper value
        $now = new DateTime();
        $user = $this->db->loadUserById(52);
        static::assertNotNull($user);
        $code = $this->db->requestPasswordResetCode($user, '::1');
        // verify returned value is in the db
        $query = 'SELECT iduser, request_date '
                . 'FROM reset_password_table '
                . 'WHERE confirm_code = BINARY :confirm_code';
        $stmt = $this->db->prepare($query);
        $stmt->execute([':confirm_code' => $code]);
        $result = $stmt->fetch();
        static::assertIsArray($result);
        static::assertSame($user->getId(), $result['iduser']);
        $ts = new DateTime($result['request_date']);
        static::assertEqualsWithDelta(
            $now->getTimestamp(),
            $ts->getTimestamp(),
            2
        );
        // check that there is only one entry, even if we create a second one:
        $newCode = $this->db->requestPasswordResetCode($user, '::1');
        static::assertNotSame($code, $newCode);
        $query = 'SELECT confirm_code FROM reset_password_table '
            . 'WHERE iduser = :iduser';
        $stmt = $this->db->prepare($query);
        $stmt->execute([':iduser' => $user->getId()]);
        $result = $stmt->fetch();
        static::assertIsArray($result);
        static::assertSame($newCode, $result['confirm_code']);
        static::assertFalse($stmt->fetch()); // no more data
    }

    public function testverifyPasswordResetCode(): void
    {
        // create two codes: One that will expire in one minute
        // and one that has expired one minute ago
        $user101 = $this->db->loadUserById(101);
        $user102 = $this->db->loadUserById(102);
        static::assertNotNull($user101);
        static::assertNotNull($user102);
        $validCode = $this->db->requestPasswordResetCode($user101, '::1');
        $elapsedCode = $this->db->requestPasswordResetCode($user102, '::1');

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
        $stmt->execute([':request_date' => $elapsedDate->format('Y-m-d H:i:s'),
            ':confirm_code' => $elapsedCode]);
        $stmt->execute([':request_date' => $validDate->format('Y-m-d H:i:s'),
            ':confirm_code' => $validCode]);

        // test that an unknown code fails to validate
        static::assertSame(0, $this->db->verifyPasswordResetCode('AB12', true));

        // test for known, but invalid codes (time has elapsed by one minute)
        static::assertSame(0, $this->db->verifyPasswordResetCode($elapsedCode, false));
        // test that those entries are removed always (despite we set remove to false)
        $query = 'SELECT confirm_code FROM reset_password_table '
            . 'WHERE confirm_code = BINARY :confirm_code';
        $stmt = $this->db->prepare($query);
        $stmt->execute([':confirm_code' => $elapsedCode]);
        $result = $stmt->fetch();
        static::assertFalse($result);

        // test for known valid codes: one that will elapse in one minute
        $iduser = $this->db->verifyPasswordResetCode($validCode, false);
        static::assertSame(101, $iduser);
        // test it is not removed if not specified
        $stmt->execute([':confirm_code' => $validCode]);
        $result = $stmt->fetch();
        static::assertIsArray($result);
        // test that it is removed if specified
        $values = $this->db->verifyPasswordResetCode($validCode, true);
        static::assertSame(101, $iduser);
        $stmt->execute([':confirm_code' => $validCode]);
        $result = $stmt->fetch();
        static::assertFalse($result);
    }

    public function testremoveResetPasswordCode(): void
    {
        // insert some entries, test they are removed
        $user101 = $this->db->loadUserById(101);
        static::assertNotNull($user101);
        $this->db->requestPasswordResetCode($user101, '::1');
        static::assertSame(1, $this->db->removeResetPasswordCode($user101));
        static::assertSame(0, $this->db->removeResetPasswordCode($user101));
        // not existing entry works (altough this is stupid, you can never get into that situation - how would you create the user object?)
        $user33 = static::createStub(User::class);
        $user33->method('GetId')->willReturn(33);
        static::assertSame(0, $this->db->removeResetPasswordCode($user33));
    }

    public function testupdateUserPassword(): void
    {
        $user101 = $this->db->loadUserById(101);
        static::assertNotNull($user101);
        $this->db->updateUserPassword($user101, "foobar");
        // User must reflect the change immediately, reload must have been triggered by UpdateUserPassword
        static::assertTrue($user101->auth("foobar"));
        static::assertFalse($user101->auth("Foobar"));
    }

    public function testrequestUpdateEmailCode(): void
    {
        // create an entry and verify its created with the proper value
        $now = new DateTime();
        $user = $this->db->loadUserById(52);
        static::assertNotNull($user);
        $code = $this->db->requestUpdateEmailCode(
            $user,
            'new-mail@mail.com',
            '::1'
        );
        // verify returned value matches entry from the db
        $query = 'SELECT iduser, email, request_date '
                . 'FROM update_email_table '
                . 'WHERE confirm_code = BINARY :confirm_code';
        $stmt = $this->db->prepare($query);
        $stmt->execute([':confirm_code' => $code]);
        $result = $stmt->fetch();
        static::assertIsArray($result);
        static::assertSame($user->getId(), $result['iduser']);
        static::assertSame('new-mail@mail.com', $result['email']);
        $ts = new DateTime($result['request_date']);
        static::assertEqualsWithDelta(
            $now->getTimestamp(),
            $ts->getTimestamp(),
            2
        );
        // check that there is only one entry, even if we create a second one:
        $newCode = $this->db->requestUpdateEmailCode(
            $user,
            'another@mail.com',
            '::1'
        );
        static::assertNotSame($code, $newCode);
        $query = 'SELECT confirm_code FROM update_email_table '
            . 'WHERE iduser = :iduser';
        $stmt = $this->db->prepare($query);
        $stmt->execute([':iduser' => $user->getId()]);
        $result = $stmt->fetch();
        static::assertIsArray($result);
        static::assertSame($newCode, $result['confirm_code']);
        static::assertFalse($stmt->fetch()); // no more data
    }

    public function testverifyUpdateEmailCode(): void
    {
        // create two codes: One that will expire in one minute
        // and one that has expired one minute ago
        $user101 = $this->db->loadUserById(101);
        $user102 = $this->db->loadUserById(102);
        static::assertNotNull($user101);
        static::assertNotNull($user102);
        $validCode = $this->db->requestUpdateEmailCode(
            $user101,
            '101@mail',
            '::1'
        );
        $elapsedCode = $this->db->requestUpdateEmailCode(
            $user102,
            '102@mail',
            '::1'
        );

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
        $stmt->execute([':request_date' => $elapsedDate->format('Y-m-d H:i:s'),
            ':confirm_code' => $elapsedCode]);
        $stmt->execute([':request_date' => $validDate->format('Y-m-d H:i:s'),
            ':confirm_code' => $validCode]);

        // test that an unknown code fails to validate
        static::assertNull($this->db->verifyUpdateEmailCode('AB12', true));

        // test for known, but invalid codes (time has elapsed by one minute)
        static::assertNull($this->db->verifyUpdateEmailCode($elapsedCode, false));
        // test that those entries are removed always (despite we set remove to false)
        $query = 'SELECT confirm_code FROM update_email_table '
            . 'WHERE confirm_code = BINARY :confirm_code';
        $stmt = $this->db->prepare($query);
        $stmt->execute([':confirm_code' => $elapsedCode]);
        $result = $stmt->fetch();
        static::assertFalse($result);

        // test for known valid codes: one that will elapse in one minute
        $values = $this->db->verifyUpdateEmailCode($validCode, false);
        static::assertNotNull($values);
        // test it is not removed if not specified
        $stmt->execute([':confirm_code' => $validCode]);
        $result = $stmt->fetch();
        static::assertIsArray($result);
        // test that it is removed if specified
        $values = $this->db->verifyUpdateEmailCode($validCode, true);
        static::assertNotNull($values);
        $stmt->execute([':confirm_code' => $validCode]);
        $result = $stmt->fetch();
        static::assertFalse($result);

        // test that the values returned are correct
        static::assertSame(101, $values['iduser']);
        static::assertSame('101@mail', $values['email']);
    }

    public function testremoveUpdateEmailCode(): void
    {
        // insert some entries, test they are removed
        $user101 = $this->db->loadUserById(101);
        static::assertNotNull($user101);
        $this->db->requestUpdateEmailCode($user101, 'new@mail', '::1');
        static::assertSame(1, $this->db->removeUpdateEmailCode($user101));
        static::assertSame(0, $this->db->removeUpdateEmailCode($user101));
        // not existing entry works
        $user33 = static::createStub(User::class);
        $user33->method('GetId')->willReturn(33);
        static::assertSame(0, $this->db->removeUpdateEmailCode($user33));
    }

    public function testupdateUserEmail(): void
    {
        $user101 = $this->db->loadUserById(101);
        static::assertNotNull($user101);
        $this->db->updateUserEmail($user101, 'bla@mail');
        // note: User object must reflect the change without reloading
        static::assertNotNull($user101);
        static::assertSame('bla@mail', $user101->getEmail());
    }

    public function testactivateUser(): void
    {
        // rely on a test-database
        self::createTestDatabase();
        // activate one that is not active
        $needsApproval = $this->db->loadUserById(51);
        static::assertNotNull($needsApproval);
        static::assertFalse($needsApproval->isActive());
        $this->db->activateUser($needsApproval);
        // must reload, see #21
        $needsApproval = $this->db->loadUserById(51);
        static::assertTrue($needsApproval->isActive());

        // activating one that is already active, does nothing
        $user101 = $this->db->loadUserById(101);
        static::assertNotNull($user101);
        static::assertTrue($user101->isActive());
        $this->db->activateUser($user101);
        // User must have been updated
        static::assertTrue($user101->isActive());

        // Activating one with a deactivated reason, removes that reason
        $deactivated = $this->db->loadUserById(50);
        static::assertNotNull($deactivated);
        static::assertFalse($deactivated->isActive());
        $query = 'SELECT reason FROM user_deactivated_reason_table '
            . 'WHERE iduser = :iduser';
        $stmt = $this->db->prepare($query);
        $stmt->execute([':iduser' => $deactivated->getId()]);
        $result = $stmt->fetch();
        static::assertIsArray($result);
        $this->db->activateUser($deactivated);
        static::assertTrue($deactivated->isActive());
        $stmt->execute([':iduser' => $deactivated->getId()]);
        $result = $stmt->fetch();
        static::assertFalse($result);
    }

    public static function providerNotExistingNotConfirmed(): array
    {
        return [
            [333], // not existing in db
            [52],    // needs to confirm email
        ];
    }

    #[DataProvider('providerNotExistingNotConfirmed')]
    public function testActivateUserFails(int $userId): void
    {
        $user = static::createStub(User::class);
        $user->method('GetId')->willReturn($userId);
        $this->expectException(InvalidArgumentException::class);
        $this->db->activateUser($user);
    }

    public function testdeactivateUser(): void
    {
        // rely on a test-database
        self::createTestDatabase();
        // mock the admin who is deactivating
        $admin = static::createStub(User::class);
        $admin->method('GetId')->willReturn(1);
        $admin->method('IsAdmin')->willReturn(true);
        $admin->method('IsActive')->willReturn(true);

        // deactivate one that is active
        $user101 = $this->db->loadUserById(101);
        static::assertNotNull($user101);
        static::assertTrue($user101->isActive());
        $this->db->deactivateUser($user101, 'just for fun', $admin);
        static::assertSame($this->db->getDeactivationReason($user101), 'just for fun');
        static::assertFalse($user101->isActive());

        // deactivating one that is already deactivated, does nothing
        // especially, it does not alter the deactivation-reason
        $this->db->deactivateUser($user101, 'deactivate again', $admin);
        static::assertFalse($user101->isActive());
        static::assertSame($this->db->getDeactivationReason($user101), 'just for fun');
    }

    public static function providerNotActiveAdmin(): array
    {
        $notAdmin = TestCase::createStub(User::class);
        $notAdmin->method('IsAdmin')->willReturn(false);
        $notAdmin->method('IsActive')->willReturn(true);
        $notActive = TestCase::createStub(User::class);
        $notActive->method('IsAdmin')->willReturn(true);
        $notActive->method('IsActive')->willReturn(false);

        return [
            [$notAdmin],
            [$notActive],
        ];
    }

    #[DataProvider('providerNotActiveAdmin')]
    public function testDeactiveUserOnlyAdminCan(User $admin): void
    {
        // test that only active admins can deactivate
        $inactive = static::createStub(User::class);
        $inactive->method('IsActive')->willReturn(true);
        $this->expectException(InvalidArgumentException::class);
        $this->db->deactivateUser($inactive, 'not there', $admin);
    }


    public function testgetDeactivationReason(): void
    {
        // check the message for our deactivated user
        $reason = $this->db->getDeactivationReason($this->user50);
        static::assertSame('test deactivated by admin', $reason);
        // non-deactived, or non-existing just return null
        $reason = $this->db->getDeactivationReason($this->user1);
        static::assertNull($reason);
        $reason = $this->db->getDeactivationReason($this->user666);
        static::assertNull($reason);
    }

    public function testsetAdmin(): void
    {
        // rely on a test-database
        self::createTestDatabase();
        // promote one to an admin that is not yet
        $user101 = $this->db->loadUserById(101);
        static::assertNotNull($user101);
        static::assertFalse($user101->isAdmin());
        $this->db->setAdmin($user101, true);
        static::assertTrue($user101->isAdmin());

        // promote an admin to an admin again, nothing changes
        $this->db->setAdmin($user101, true);
        static::assertTrue($user101->isAdmin());

        // and remove admin
        $this->db->setAdmin($user101, false);
        static::assertFalse($user101->isAdmin());

        // remove admin again, nothing changes
        $this->db->setAdmin($user101, false);
        static::assertFalse($user101->isAdmin());
    }

    public function testSetAdminFails(): void
    {
        // mock a user with no confirmation_ts - it cannot become an admin
        $user = static::createStub(User::class);
        $user->method('GetId')->willReturn(1313);
        $user->method('IsConfirmed')->willReturn(false);
        $this->expectException(InvalidArgumentException::class);
        $this->db->setAdmin($user, true);
    }

    public function testmakeDummy(): void
    {
        // rely on a test-database
        self::createTestDatabase();
        // turn a user into a dummy
        $user101 = $this->db->loadUserById(101);
        static::assertNotNull($user101);
        static::assertFalse($user101->isDummyUser());
        $this->db->makeDummy($user101);
        static::assertTrue($user101->isDummyUser());
        // a dummy can be turned into a dummy over and over
        $dummy = $this->db->loadUserById(66);
        static::assertNotNull($dummy);
        static::assertTrue($dummy->isDummyUser());
        $this->db->makeDummy($dummy);
        static::assertTrue($dummy->isDummyUser());
    }


    public static function providerZeroPosts(): array
    {
        return [
            [1],
            [10],
            [50],
            [51],
            [52],
            [66],
        ];
    }

    #[DataProvider('providerZeroPosts')]
    public function testdeleteUser(int $userId): void
    {
        // rely on a test-database
        self::createTestDatabase();
        // only users with 0 posts can be deleted
        // try to delete all users with zero posts
        $user = $this->db->loadUserById($userId);
        static::assertNotNull($user);
        $this->db->deleteUser($user);
        // user must be gone by now
        static::assertNull($this->db->loadUserById($userId));

        // check that deactivated_reason_table has been cleared:
        // (yes, is done by constraint of foreign key)
        $reason = $this->db->getDeactivationReason($user);
        static::assertNull($reason);
    }

    public function testDeleteUserFails(): void
    {
        // rely on a test-database
        self::createTestDatabase();
        // only users with 0 posts can be deleted
        // try to delete a user which has posts
        $user = static::createStub(User::class);
        $user->method('GetId')->willReturn(101);
        $this->expectException(InvalidArgumentException::class);
        $this->db->deleteUser($user);
    }


    public function testgetPostByUserCount(): void
    {
        // rely on a test-database
        self::createTestDatabase();
        static::assertSame(8, $this->db->getPostByUserCount($this->user101));
        static::assertSame(6, $this->db->getPostByUserCount($this->user102));
        static::assertSame(7, $this->db->getPostByUserCount($this->user103));
    }

    public function testsetPostVisible(): void
    {
        // rely on a test-database
        self::createTestDatabase();
        // hide some post within a thread:
        // its child must get hidden too
        $postA1_2 = $this->db->loadPost(23);
        static::assertNotNull($postA1_2);
        static::assertFalse($postA1_2->isHidden());
        $postA1_2_1 = $this->db->loadPost(25);
        static::assertNotNull($postA1_2_1);
        static::assertFalse($postA1_2_1->isHidden());
        static::assertSame(23, $postA1_2_1->getParentPostId());
        // now hide that tree by hiding the parent
        $this->db->setPostVisible(23, false);
        $postA1_2 = $this->db->loadPost(23);
        static::assertTrue($postA1_2->isHidden());
        $postA1_2_1 = $this->db->loadPost(25);
        static::assertTrue($postA1_2_1->isHidden());
        // show again:
        $this->db->setPostVisible(23, true);
        $postA1_2 = $this->db->loadPost(23);
        static::assertFalse($postA1_2->isHidden());
        $postA1_2_1 = $this->db->loadPost(25);
        static::assertFalse($postA1_2_1->isHidden());

        // fail for invalid post-ids
        $this->expectException(InvalidArgumentException::class);
        $this->db->setPostVisible(666, false);
    }

    public function testisDateWithinConfirmPeriod(): void
    {
        $codeValidInterval = new DateInterval(YbForumConfig::CONF_CODE_VALID_PERIOD);
        $oneSecondInterval = new DateInterval('PT3S');
        $elapsed = new DateTime();
        $valid = new DateTime();
        $elapsed->sub($codeValidInterval);
        $elapsed->sub($oneSecondInterval);
        $valid->sub($codeValidInterval);
        $valid->add($oneSecondInterval);
        static::assertFalse($this->db->isDateWithinConfirmPeriod($elapsed));
        static::assertTrue($this->db->isDateWithinConfirmPeriod($valid));
    }

    public function testisEmailOnBlacklistExactly(): void
    {
        // rely on a test-database
        self::createTestDatabase();
        $desc = $this->db->isEmailOnBlacklistExactly('foo@bar.com');
        static::assertSame('foo-bar', $desc);
        $desc = $this->db->isEmailOnBlacklistExactly('foO@bar.net');
        static::assertFalse($desc);
    }

    public function testisEmailOnBlacklistRegex(): void
    {
        // rely on a test-database
        self::createTestDatabase();
        $desc = $this->db->isEmailOnBlacklistRegex('foo@bar.ru');
        static::assertSame('Mailadressen aus .ru sind blockiert.', $desc);
        $desc = $this->db->isEmailOnBlacklistRegex('foO@bar.com');
        static::assertFalse($desc);
    }


    public function testaddBlacklist(): void
    {
        // no one is adding to blacklist from tests, do not restore db
        $desc = $this->db->isEmailOnBlacklistExactly('hans@wurst.com');
        static::assertFalse($desc);
        $this->db->addBlacklist('hans@wurst.com', 'hans wurst');
        $desc = $this->db->isEmailOnBlacklistExactly('hans@wurst.com');
        static::assertSame('hans wurst', $desc);
    }

    public function testgetAdminUsers(): void
    {
        $admins = $this->db->getAdminUsers();
        static::assertCount(1, $admins);
        $adminUser = $admins[0];
        static::assertEquals(1, $adminUser->getId());
        static::assertEquals('admin', $adminUser->getNick());
        static::assertTrue($adminUser->isAdmin());
        static::assertTrue($adminUser->isActive());
    }

    public static function providerloadUser(): array
    {
        $admin = self::mockUser(
            1,
            'admin',
            'eg-be@dev',
            1,
            1,
            '2020-03-30 14:30:05',
            'initial admin-user',
            '2020-03-30 14:30:15',
            '$2y$10$n.ZGkNoS3BvavZ3qcs50nelspmTfM3dh8ZLSZ5JXfBvW9rQ6i..VC',
            null
        );
        $old = self::mockUser(
            10,
            'old-user',
            'old-user@dev',
            0,
            0,
            '2017-12-31 15:21:27',
            'needs migration',
            null,
            null,
            '895e1aace5e13c683491bb26dd7453bf'
        );
        $deactivated = self::mockUser(
            50,
            'deactivated',
            'deactivated@dev',
            0,
            0,
            '2021-03-30 14:30:05',
            'deactivated by admin',
            '2021-03-30 14:30:15',
            '$2y$10$U2nazhRAEhg1JkXu2Uls0.pnH5Wi9QsyXbmoJMBC2KNYGPN8fezfe',
            null
        );

        return [
            [$admin],
            [$old],
            [$deactivated],
        ];
    }

    #[DataProvider('providerLoadUser')]
    public function testloadUserById(User $ref): void
    {
        $user = $this->db->loadUserById($ref->getId());
        static::assertNotNull($user);
        static::assertObjectEquals($ref, $user);
    }

    public function testLoadUserByIdFail(): void
    {
        static::assertNull($this->db->loadUserById(-1));
        static::assertNull($this->db->loadUserById(12));
    }

    #[DataProvider('providerLoadUser')]
    public function testloadUserByNick(User $ref): void
    {
        $user = $this->db->loadUserByNick($ref->getNick());
        static::assertNotNull($user);
        static::assertObjectEquals($ref, $user);
    }

    public function testLoadUserByNickFail(): void
    {
        static::assertNull($this->db->loadUserByNick('nope'));
        static::assertNull($this->db->loadUserByNick(' admin'));

        // it seems whitespaces get trimmed at the end of a prepared statement:
        static::assertNotNull($this->db->loadUserByNick('admin '));
    }

    #[DataProvider('providerLoadUser')]
    public function testloadUserByEmail(User $ref): void
    {
        $user = $this->db->loadUserByEmail($ref->getEmail());
        static::assertNotNull($user);
        static::assertObjectEquals($ref, $user);
    }

    public function testLoadUserByEmailFail(): void
    {
        static::assertNull($this->db->loadUserByEmail('nope@foo'));
        static::assertNull($this->db->loadUserByEmail(' eg-be@dev'));

        // it seems whitespaces get trimmed at the end of a prepared statement:
        static::assertNotNull($this->db->loadUserByEmail('eg-be@dev '));
    }

    public function testLoadThreadIds_noGaps(): void
    {
        // reset to initial state
        BaseTest::createTestDatabase();

        // pagesize 3
        // page 1
        $threadIds = $this->db->loadThreadIds(1, 3);
        static::assertEquals([12, 11, 10], $threadIds);

        // page 3
        $threadIds = $this->db->loadThreadIds(3, 3);
        static::assertEquals([6, 5, 4], $threadIds);

        // with pagesize of 5
        // page 1
        $threadIds = $this->db->loadThreadIds(1, 5);
        static::assertEquals([12, 11, 10, 9, 8], $threadIds);

        // page 3
        $threadIds = $this->db->loadThreadIds(3, 5);
        static::assertEquals([2, 1], $threadIds);

        // if pagenr is way too high, we expect an empty result
        $threadIds = $this->db->loadThreadIds(3, 100);
        static::assertEquals([], $threadIds);
    }

    public function testLoadThreadIds_withGaps(): void
    {
        // reset to initial state
        BaseTest::createTestDatabase();

        // insert some additional threads
        $maxThreadId = $this->db->getLastThreadId();
        $insertedThreadIds = []; // holds inserted ids, the newest (=highest id) first
        $query = 'INSERT INTO thread_table (idthread) VALUES(:idthread)';
        $stmt = $this->db->prepare($query);
        for ($i = $maxThreadId + 1000; $i < $maxThreadId + 1250; $i++) {
            $stmt->execute([':idthread' => $i]);
            $insertedThreadId = intval($this->db->lastInsertId());
            array_unshift($insertedThreadIds, $insertedThreadId);
        }

        // pagesize 20
        // page 1 - holds the 20 newest entries
        $expectedEntries = array_slice($insertedThreadIds, 0, 20);
        $threadIds = $this->db->loadThreadIds(1, 20);
        static::assertEquals($expectedEntries, $threadIds);

        // page 3
        $expectedEntries = array_slice($insertedThreadIds, 2 * 20, 20);
        $threadIds = $this->db->loadThreadIds(3, 20);
        static::assertEquals($expectedEntries, $threadIds);

        // the very last page, must hold a mix of the inserted stuff and the default available test-data
        // total 250 (inserted) + 12 (default) thread-ids: 262
        // with a pagesize of 20 -> 14 pages, but the last page contains only two entries

        // page 14
        $threadIds = $this->db->loadThreadIds(14, 20);
        static::assertEquals([2, 1], $threadIds);

        // page 13
        $threadIds = $this->db->loadThreadIds(13, 20);
        $expectedInsertedEntries = array_slice($insertedThreadIds, 12 * 20, 20); // 10 entries from insertions
        $expectedEntries = [...$expectedInsertedEntries, 12, 11, 10, 9, 8, 7, 6, 5, 4, 3]; // and 10 from default-data
        static::assertEquals($expectedEntries, $threadIds);
    }

    public function testloadThreadIndexEntries(): void
    {
        // reset to initial state
        BaseTest::createTestDatabase();

        // Load the oldest 4 threads with ids [4, 3, 2, 1]
        // as we have 12 threads in the test-data, they are on page 3, if we assume a page-size of 4
        $threads = [];
        $this->db->loadThreadIndexEntries(3, 4, function ($threadIndexes) use (&$threads): void {
            array_push($threads, $threadIndexes);
        });

        // check that all four threads are found
        static::assertCount(4, $threads);
        // in the correct order, which is reversed, as that makes rendering easier
        // (newest threads shall appear first)
        static::assertEquals(4, $threads[0][0]->getThreadId());
        static::assertEquals('Thread 4', $threads[0][0]->getTitle());
        static::assertEquals(3, $threads[1][0]->getThreadId());
        static::assertEquals('Thread 3', $threads[1][0]->getTitle());
        static::assertEquals(2, $threads[2][0]->getThreadId());
        static::assertEquals('Thread 2', $threads[2][0]->getTitle());
        static::assertEquals(1, $threads[3][0]->getThreadId());
        static::assertEquals('Thread 1', $threads[3][0]->getTitle());

        // check that Thread 3 is complete and ready to be rendered:
        $thread3 = $threads[1];
        static::assertCount(8, $thread3);
        static::assertEquals(3, $threads[1][0]->getThreadId());
        static::assertEquals('Thread 3', $thread3[0]->getTitle());
        static::assertEquals(0, $thread3[0]->getIndent());
        static::assertEquals('Thread 3 - A1', $thread3[1]->getTitle());
        static::assertEquals(1, $thread3[1]->getIndent());
        static::assertEquals('Thread 3 - A1-1', $thread3[2]->getTitle());
        static::assertEquals(2, $thread3[2]->getIndent());
        static::assertEquals('Thread 3 - A1-2', $thread3[3]->getTitle());
        static::assertEquals(2, $thread3[3]->getIndent());
        static::assertEquals('Thread 3 - A1-2-1', $thread3[4]->getTitle());
        static::assertEquals(3, $thread3[4]->getIndent());
        static::assertEquals('Thread 3 - A1-3', $thread3[5]->getTitle());
        static::assertEquals(2, $thread3[5]->getIndent());
        static::assertEquals('Thread 3 - A2', $thread3[6]->getTitle());
        static::assertEquals(1, $thread3[6]->getIndent());
        static::assertEquals('Thread 3 - A2-1', $thread3[7]->getTitle());
        static::assertEquals(2, $thread3[7]->getIndent());
    }

    public function testLoadThreadIndexEntries_HiddenPathNotIncluded(): void
    {
        // reset to initial state
        BaseTest::createTestDatabase();

        // Hide the part from 'Thread 3 A1-2' on of Thread 3
        $this->db->setPostVisible(23, false);

        // Load the oldest 4 threads with ids [4, 3, 2, 1]
        // as we have 12 threads in the test-data, they are on page 3, if we assume a page-size of 4
        $threads = [];
        $this->db->loadThreadIndexEntries(3, 4, function ($threadIndexes) use (&$threads): void {
            array_push($threads, $threadIndexes);
        });

        // check that all four threads are found
        static::assertCount(4, $threads);
        // in the correct order, which is reversed, as that makes rendering easier
        // (newest threads shall appear first)
        static::assertEquals(4, $threads[0][0]->getThreadId());
        static::assertEquals('Thread 4', $threads[0][0]->getTitle());
        static::assertEquals(3, $threads[1][0]->getThreadId());
        static::assertEquals('Thread 3', $threads[1][0]->getTitle());
        static::assertEquals(2, $threads[2][0]->getThreadId());
        static::assertEquals('Thread 2', $threads[2][0]->getTitle());
        static::assertEquals(1, $threads[3][0]->getThreadId());
        static::assertEquals('Thread 1', $threads[3][0]->getTitle());

        // check that Thread 3 is complete and ready to be rendered:
        $thread3 = $threads[1];
        static::assertCount(6, $thread3);
        static::assertEquals(3, $threads[1][0]->getThreadId());
        static::assertEquals('Thread 3', $thread3[0]->getTitle());
        static::assertEquals(0, $thread3[0]->getIndent());
        static::assertEquals('Thread 3 - A1', $thread3[1]->getTitle());
        static::assertEquals(1, $thread3[1]->getIndent());
        static::assertEquals('Thread 3 - A1-1', $thread3[2]->getTitle());
        static::assertEquals(2, $thread3[2]->getIndent());
        static::assertEquals('Thread 3 - A1-3', $thread3[3]->getTitle());
        static::assertEquals(2, $thread3[3]->getIndent());
        static::assertEquals('Thread 3 - A2', $thread3[4]->getTitle());
        static::assertEquals(1, $thread3[4]->getIndent());
        static::assertEquals('Thread 3 - A2-1', $thread3[5]->getTitle());
        static::assertEquals(2, $thread3[5]->getIndent());
    }

    public function testloadPostReplies(): void
    {
        // reset to initial state
        BaseTest::createTestDatabase();

        // top-level post 'Thread 1', has no children
        $post_1 = static::createStub(Post::class);
        $post_1->method('GetId')->willReturn(1);
        $post_1->method('GetThreadId')->willReturn(1);
        $post_1->method('GetIndent')->willReturn(0);
        $post_1->method('GetRank')->willReturn(1);

        $replies_1 = $this->db->loadPostReplies($post_1, false);
        static::assertCount(0, $replies_1);

        // top-level post 'Thread 3', has 7 (sub-) children
        $post_3 = static::createStub(Post::class);
        $post_3->method('GetId')->willReturn(3);
        $post_3->method('GetThreadId')->willReturn(3);
        $post_3->method('GetIndent')->willReturn(0);
        $post_3->method('GetRank')->willReturn(1);

        $replies_3 = $this->db->loadPostReplies($post_3, false);
        static::assertCount(7, $replies_3);
        static::assertEquals('Thread 3 - A1', $replies_3[0]->getTitle());
        static::assertEquals('Thread 3 - A1-1', $replies_3[1]->getTitle());
        static::assertEquals('Thread 3 - A1-2', $replies_3[2]->getTitle());
        static::assertEquals('Thread 3 - A1-2-1', $replies_3[3]->getTitle());
        static::assertEquals('Thread 3 - A1-3', $replies_3[4]->getTitle());
        static::assertEquals('Thread 3 - A2', $replies_3[5]->getTitle());
        static::assertEquals('Thread 3 - A2-1', $replies_3[6]->getTitle());

        // only a smaller part of the answers
        $post_20 = static::createStub(Post::class);
        $post_20->method('GetId')->willReturn(20);
        $post_20->method('GetThreadId')->willReturn(3);
        $post_20->method('GetIndent')->willReturn(1);
        $post_20->method('GetRank')->willReturn(2);

        $replies_20 = $this->db->loadPostReplies($post_20, false);
        static::assertCount(4, $replies_20);
        static::assertEquals('Thread 3 - A1-1', $replies_20[0]->getTitle());
        static::assertEquals('Thread 3 - A1-2', $replies_20[1]->getTitle());
        static::assertEquals('Thread 3 - A1-2-1', $replies_20[2]->getTitle());
        static::assertEquals('Thread 3 - A1-3', $replies_20[3]->getTitle());
    }

    public function testLoadPostReplies_HiddenPathNotIncluded(): void
    {
        // reset to initial state
        BaseTest::createTestDatabase();

        // Hide the part from 'Thread 3 A1-2' on of Thread 3
        $this->db->setPostVisible(23, false);

        // top-level post 'Thread 3', has 7 (sub-) children, if hidden path is included
        $post_3 = static::createStub(Post::class);
        $post_3->method('GetId')->willReturn(3);
        $post_3->method('GetThreadId')->willReturn(3);
        $post_3->method('GetIndent')->willReturn(0);
        $post_3->method('GetRank')->willReturn(1);

        $replies_3 = $this->db->loadPostReplies($post_3, true);
        static::assertCount(7, $replies_3);
        static::assertEquals('Thread 3 - A1', $replies_3[0]->getTitle());
        static::assertEquals('Thread 3 - A1-1', $replies_3[1]->getTitle());
        static::assertEquals('Thread 3 - A1-2', $replies_3[2]->getTitle());
        static::assertEquals('Thread 3 - A1-2-1', $replies_3[3]->getTitle());
        static::assertEquals('Thread 3 - A1-3', $replies_3[4]->getTitle());
        static::assertEquals('Thread 3 - A2', $replies_3[5]->getTitle());
        static::assertEquals('Thread 3 - A2-1', $replies_3[6]->getTitle());

        // top-level post 'Thread 3', has 5 (sub-) children, if hidden path is not included
        $post_3 = static::createStub(Post::class);
        $post_3->method('GetId')->willReturn(3);
        $post_3->method('GetThreadId')->willReturn(3);
        $post_3->method('GetIndent')->willReturn(0);
        $post_3->method('GetRank')->willReturn(1);

        $replies_3 = $this->db->loadPostReplies($post_3, false);
        static::assertCount(5, $replies_3);
        static::assertEquals('Thread 3 - A1', $replies_3[0]->getTitle());
        static::assertEquals('Thread 3 - A1-1', $replies_3[1]->getTitle());
        static::assertEquals('Thread 3 - A1-3', $replies_3[2]->getTitle());
        static::assertEquals('Thread 3 - A2', $replies_3[3]->getTitle());
        static::assertEquals('Thread 3 - A2-1', $replies_3[4]->getTitle());
    }

    public function testloadRecentPosts(): void
    {
        // reset to initial state
        BaseTest::createTestDatabase();

        $recent = $this->db->loadRecentPosts(5);
        static::assertCount(5, $recent);
        static::assertEquals('Thread 5 - A1', $recent[0]->getTitle());
        static::assertEquals('Thread 3 - A1-3', $recent[1]->getTitle());
        static::assertEquals('Thread 3 - A1-2-1', $recent[2]->getTitle());
        static::assertEquals('Thread 3 - A2-1', $recent[3]->getTitle());
        static::assertEquals('Thread 3 - A1-2', $recent[4]->getTitle());
    }

    public static function providerPostMock(): array
    {
        // one simple post with no parent:
        $p8 = self::mockPost(
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
        // one with a parent:
        $p21 = self::mockPost(
            21,
            3,
            20,
            'user2',
            102,
            'Thread 3 - A1-1',
            'The quick brown fox jumps over the lazy dog',
            3,
            2,
            '2020-03-30 14:51:00',
            null,
            null,
            null,
            null,
            null,
            0,
            '::1'
        );

        // and one with all fields set:
        $p30 = self::mockPost(
            30,
            5,
            5,
            'user1',
            101,
            'Thread 5 - A1',
            'The quick brown fox jumps over the lazy dog',
            2,
            1,
            '2022-06-22 16:13:25',
            'mail@me.com',
            'https://foobar',
            'Visit me',
            'https://giphy/bar.gif',
            131313,
            0,
            '::1'
        );

        // and a hidden-one
        $p40 = self::mockPost(
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
        return [
            [$p8],
            [$p21],
            [$p30],
            [$p40],
        ];
    }

    #[DataProvider('providerPostMock')]
    public function testloadPost(Post $ref): void
    {
        $post = $this->db->loadPost($ref->getId());
        static::assertNotNull($post);
        static::assertObjectEquals($ref, $post);
    }

    public function testLoadPostFail(): void
    {
        static::assertNull($this->db->loadPost(-1));
        static::assertNull($this->db->loadPost(99));
    }

    public static function providerUserMock(): array
    {
        $admin = self::mockUser(
            1,
            'admin',
            'eg-be@dev',
            1,
            1,
            '2020-03-30 14:30:05',
            'initial admin-user',
            '2020-03-30 14:30:15',
            '$2y$10$n.ZGkNoS3BvavZ3qcs50nelspmTfM3dh8ZLSZ5JXfBvW9rQ6i..VC',
            null
        );
        $old = self::mockUser(
            10,
            'old-user',
            'old-user@dev',
            0,
            0,
            '2017-12-31 15:21:27',
            'needs migration',
            null,
            null,
            '895e1aace5e13c683491bb26dd7453bf'
        );
        $deactivated = self::mockUser(
            50,
            'deactivated',
            'deactivated@dev',
            0,
            0,
            '2021-03-30 14:30:05',
            'deactivated by admin',
            '2021-03-30 14:30:15',
            '$2y$10$U2nazhRAEhg1JkXu2Uls0.pnH5Wi9QsyXbmoJMBC2KNYGPN8fezfe',
            null
        );

        return [
            [$admin],
            [$old],
            [$deactivated],
        ];
    }

    public static function providerSearchStrings(): array
    {
        return [
            ['"Thread 3"', null, false, 8],
            ["Thread 3", null, false, 20],
            ['"Thread 3"', null, true, 1],
            ["Thread 3", null, true, 12],
            ['"Thread 3"', "user3", false, 3],
            ["Thread 3", "user3", false, 6],
            ['"Thread 3"', "user3", true, 1],
            ["Thread 3", "user3", true, 4],
            ["", "user3", false, 6],
            ["", "user3", true, 4],
        ];
    }

    #[DataProvider('providerSearchStrings')]
    public function testsearchPosts(string $searchString, ?string $nick, bool $noReplies, int $numberOfResults): void
    {
        BaseTest::createTestDatabase();
        $res = $this->db->searchPosts($searchString, $nick ? $nick : "", 100, 0, SortField::FIELD_RELEVANCE, SortOrder::ORDER_ASC, $noReplies);
        $resCount = count($res);
        static::assertEquals($numberOfResults, $resCount);
    }

    public function testgetAdminMails(): void
    {
        BaseTest::createTestDatabase();
        $res = $this->db->getAdminMails();
        static::assertCount(1, $res);
        static::assertEquals('eg-be@dev', $res[0]);

        // add another admin
        $user1 = $this->db->loadUserByNick('user1');
        $this->db->setAdmin($user1, true);

        $res = $this->db->getAdminMails();
        static::assertCount(2, $res);
        static::assertEquals('eg-be@dev', $res[0]);
        static::assertEquals('user1@dev', $res[1]);
    }
}
