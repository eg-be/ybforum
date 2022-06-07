<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/BaseTest.php';
require_once __DIR__.'/../web/model/User.php';


/**
 * Requires a valid database to connect to, as we
 * want to really test the executed sql.
 * 
 * See README.md located in this directory, on how
 * to setup the test-database.
 * 
 */
final class UserTest extends BaseTest
{
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

    private function validateAdmin(User $user) : void
    {
        $this->assertNotNull($user);
        $this->assertEquals(1, $user->GetId());
        $this->assertEquals('admin', $user->GetNick());
        $this->assertTrue($user->HasEmail());
        $this->assertEquals('eg-be@dev', $user->GetEmail());
        $this->assertTrue($user->IsAdmin());
        $this->assertTrue($user->IsActive());
        $this->assertEquals(new DateTime('2020-03-30 14:30:05'), $user->GetRegistrationTimestamp());
        $this->assertEquals('initial admin-user', $user->GetRegistrationMsg());
        $this->assertTrue($user->HasRegistrationMsg());
        $this->assertTrue($user->IsConfirmed());
        $this->assertEquals(new DateTime('2020-03-30 14:30:15'), $user->GetConfirmationTimestamp());
        $this->assertFalse($user->IsDummyUser());
        $this->assertFalse($user->NeedsMigration());
        $this->assertFalse($user->NeedsConfirmation());
        $this->assertTrue($user->HasPassword());
        $this->assertFalse($user->HasOldPassword());
    }

    private function validateOldUser(User $user) : void
    {
        $this->assertNotNull($user);
        $this->assertEquals(10, $user->GetId());
        $this->assertEquals('old-user', $user->GetNick());
        $this->assertTrue($user->HasEmail());
        $this->assertEquals('old-user@dev', $user->GetEmail());
        $this->assertFalse($user->IsAdmin());
        $this->assertFalse($user->IsActive());
        // Note: In real data, registration-ts is set to the date of the db-migration for old-users.
        $this->assertEquals(new DateTime('2017-12-31 15:21:27'), $user->GetRegistrationTimestamp());
        $this->assertEquals('needs migration', $user->GetRegistrationMsg());
        $this->assertTrue($user->HasRegistrationMsg());
        $this->assertFalse($user->IsConfirmed());
        $this->assertNull($user->GetConfirmationTimestamp());
        $this->assertFalse($user->IsDummyUser());
        $this->assertTrue($user->NeedsMigration());
        $this->assertTrue($user->NeedsConfirmation());
        $this->assertFalse($user->HasPassword());
        $this->assertTrue($user->HasOldPassword());        
    }

    private function validateDeactivated(User $user) : void
    {
        $this->assertNotNull($user);
        $this->assertEquals(50, $user->GetId());
        $this->assertEquals('deactivated', $user->GetNick());
        $this->assertTrue($user->HasEmail());
        $this->assertEquals('deactivated@dev', $user->GetEmail());
        $this->assertFalse($user->IsAdmin());
        $this->assertFalse($user->IsActive());
        $this->assertEquals(new DateTime('2021-03-30 14:30:05'), $user->GetRegistrationTimestamp());
        $this->assertEquals('deactivated by admin', $user->GetRegistrationMsg());
        $this->assertTrue($user->HasRegistrationMsg());
        $this->assertTrue($user->IsConfirmed());
        $this->assertEquals(new DateTime('2021-03-30 14:30:15'), $user->GetConfirmationTimestamp());
        $this->assertFalse($user->IsDummyUser());
        $this->assertFalse($user->NeedsMigration());
        $this->assertFalse($user->NeedsConfirmation());
        $this->assertTrue($user->HasPassword());
        $this->assertFalse($user->HasOldPassword());        
    }

    public function testLoadUserById() : void
    {
        $admin = User::LoadUserById($this->db, 1);
        $this->validateAdmin($admin);
        $oldUser = User::LoadUserById($this->db, 10);
        $this->validateOldUser($oldUser);
        $deactivated = User::LoadUserById($this->db, 50);
        $this->validateDeactivated($deactivated);

        $this->assertNull(User::LoadUserById($this->db, -1));
        $this->assertNull(User::LoadUserById($this->db, 12));
    }

    public function testLoadUserByNick() : void
    {
        $admin = User::LoadUserByNick($this->db, 'admin');
        $this->validateAdmin($admin);
        $oldUser = User::LoadUserByNick($this->db, 'old-user');
        $this->validateOldUser($oldUser);
        $deactivated = User::LoadUserByNick($this->db, 'deactivated');
        $this->validateDeactivated($deactivated);

        $this->assertNull(User::LoadUserByNick($this->db, 'nope'));
        $this->assertNull(User::LoadUserByNick($this->db, ' admin'));

        // it seems whitespaces get trimmed at the end of a prepared statement:
        $this->assertNotNull(User::LoadUserByNick($this->db, 'admin '));
    }

    public function testLoadUserByEmail() : void
    {
        $admin = User::LoadUserByEmail($this->db, 'eg-be@dev');
        $this->validateAdmin($admin);
        $oldUser = User::LoadUserByEmail($this->db, 'old-user@dev');
        $this->validateOldUser($oldUser);
        $deactivated = User::LoadUserByEmail($this->db, 'deactivated@dev');
        $this->validateDeactivated($deactivated);

        $this->assertNull(User::LoadUserByEmail($this->db, 'nope@foo'));
        $this->assertNull(User::LoadUserByEmail($this->db, ' eg-be@dev'));
        
        // it seems whitespaces get trimmed at the end of a prepared statement:
        $this->assertNotNull(User::LoadUserByEmail($this->db, 'eg-be@dev '));
    }

    public function testAuth() : void
    {
        $admin = User::LoadUserById($this->db, 1);
        $this->assertNotNull($admin);
        $this->assertTrue($admin->Auth('admin-pass'));
        $this->assertFalse($admin->Auth(' admin-pass'));

        $oldUser = User::LoadUserById($this->db, 10);
        $this->assertNotNull($oldUser);
        $this->assertFalse($oldUser->Auth('old-user-pass'));

        $dummy = User::LoadUserById($this->db, 66);
        $this->assertNotNull($dummy);
        $this->assertFalse($dummy->Auth('dummy-pass'));

        $inactive = User::LoadUserById($this->db, 51);
        $this->assertNotNull($inactive);
        $this->assertFalse($inactive->Auth('inactive-pass'));        
    }

    public function testOldAuth() : void
    {
        $oldUser = User::LoadUserById($this->db, 10);
        $this->assertNotNull($oldUser);
        $this->assertTrue($oldUser->OldAuth('old-user-pass'));
        $this->assertFalse($oldUser->OldAuth(' old-user-pass'));
        $this->assertFalse($oldUser->OldAuth('olD-user-pass'));
    }
}