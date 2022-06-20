<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/BaseTest.php';
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

    protected function setUp(): void
    {
        // some of the tests will modify the db, 
        // just re-create from scratch on every test
        BaseTest::createTestDatabase();        
        $this->db = new ForumDb(false);
    }

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
        $count = $this->db->GetThreadCount();
        $this->assertEquals(12, $count);
    }

    public function testGetPostCount() : void
    {
        $count = $this->db->GetPostCount();
        $this->assertEquals(19, $count);
    }

    public function testGetUserCount() : void
    {
        $count = $this->db->GetUserCount();
        $this->assertEquals(8, $count);
    }

    public function testGetActiveUserCount() : void
    {
        $count = $this->db->GetActiveUserCount();
        $this->assertEquals(4, $count);
    }

    public function testGetFromAdminDeactivatedUserCount() : void
    {
        $count = $this->db->GetFromAdminDeactivatedUserCount();
        $this->assertEquals(1, $count);
    }

    public function testGetPendingAdminApprovalUserCount() : void
    {
        $count = $this->db->GetPendingAdminApprovalUserCount();
        $this->assertEquals(1, $count);
    }

    public function testGetNeedMigrationUserCount() : void
    {
        $count = $this->db->GetNeedMigrationUserCount();
        $this->assertEquals(1, $count);
    }

    public function testGetDummyUserCount() : void
    {
        $count = $this->db->GetDummyUserCount();
        $this->assertEquals(1, $count);
    }

    public function testGetLastThreadId() : void
    {
        $id = $this->db->GetLastThreadId();
        $this->assertEquals(12, $id);
    }

    public function testAuthUser() : void
    {
        // a user with a new password that is active is ok:
        $reason = 0;
        $admin = $this->db->AuthUser("admin", "admin-pass", $reason);
        $this->assertNotNull($admin);
        $this->assertEquals(1, $admin->GetId());
        // active user fails because password missmatch
        $admin = $this->db->AuthUser("admin", "foo", $reason);
        $this->assertNull($admin);
        $this->assertEquals(ForumDb::AUTH_FAIL_REASON_PASSWORD_INVALID, $reason);
        // fail because password is correct, but user is inactive:
        $deact = $this->db->AuthUser("deactivated", "deactivated-pass", $reason);
        $this->assertNull($deact);
        $this->assertEquals(ForumDb::AUTH_FAIL_REASON_USER_IS_INACTIVE, $reason);
        // fail with wrong password on inactive: must return inactive-reason:
        $deact = $this->db->AuthUser("deactivated", "foo", $reason);
        $this->assertNull($deact);
        $this->assertEquals(ForumDb::AUTH_FAIL_REASON_USER_IS_INACTIVE, $reason);
        // fail because its a dummy
        $dummy = $this->db->AuthUser("dummy", "foo", $reason);
        $this->assertNull($dummy);
        $this->assertEquals(ForumDb::AUTH_FAIL_REASON_USER_IS_DUMMY, $reason);
        // fails because user is unknown
        $unknown = $this->db->AuthUser("anyone", "foo", $reason);
        $this->assertNull($unknown);
        $this->assertEquals(ForumDb::AUTH_FAIL_REASON_NO_SUCH_USER, $reason);
        // and auth a user that needs to migrate (but is not active yet):
        $old = $this->db->AuthUser("old-user", "old-user-pass");
        $this->assertNotNull($old);
        $this->assertEquals(10, $old->GetId());
        // but fail for an old user if pass is incorrect
        $old = $this->db->AuthUser("old-user", "foo", $reason);
        $this->assertNull($old);
        $this->assertEquals(ForumDb::AUTH_FAIL_REASON_PASSWORD_INVALID, $reason);
    }

    public function testCreateThread() : void
    {
        $oldCount = $this->db->GetThreadCount();
        $user = User::LoadUserByNick($this->db, "admin");
        $this->assertNotNull($user);
        // create a new thread: only a title must be set
        $newId = $this->db->CreateThread($user, "thread-title", 
            null, null, null, null, null, "::1");
        $newCount = $this->db->GetThreadCount();
        $this->assertEquals($oldCount + 1, $newCount);
        // todo: Check values are inserted correctly
    }

    /**
     * @dataProvider providerInactiveDummy
     * @test
     */
    public function testCreateThreadFails(User $u) : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->db->CreateThread($u, "title",
            null, null, null, null, null, "::1");
    }

    public function providerInactiveDummy() : array 
    {
        $db = new ForumDb();
        $deactivated = User::LoadUserByNick($db, "deactivated");
        $dummy = User::LoadUserByNick($db, "dummy");
        return array(
            [$deactivated], 
            [$dummy]
        );
    }


}