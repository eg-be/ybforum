<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/BaseTest.php';
require_once __DIR__.'/UserMock.php';
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

    public function providerUserMock() : array
    {
        $admin = new UserMock(1, 'admin', 'eg-be@dev',
            1, 1, '2020-03-30 14:30:05', 'initial admin-user',
            '2020-03-30 14:30:15', 
            '$2y$10$n.ZGkNoS3BvavZ3qcs50nelspmTfM3dh8ZLSZ5JXfBvW9rQ6i..VC', null);
        $old = new UserMock(10, 'old-user', 'old-user@dev',
            0, 0, '2017-12-31 15:21:27', 'needs migration',
            null,
            null, '895e1aace5e13c683491bb26dd7453bf');
        $deactivated = new UserMock(50, 'deactivated', 'deactivated@dev',
            0, 0, '2021-03-30 14:30:05', 'deactivated by admin',
            '2021-03-30 14:30:15',
            '$2y$10$U2nazhRAEhg1JkXu2Uls0.pnH5Wi9QsyXbmoJMBC2KNYGPN8fezfe', null);
        
        return array(
            [$admin],
            [$old],
            [$deactivated]
        );
    }

    /**
     * @test
     * @dataProvider providerUserMock
     */
    public function testLoadUserById(User $ref) : void
    {
        $user = User::LoadUserById($this->db, $ref->GetId());
        $this->assertNotNull($user);
        $this->assertObjectEquals($ref, $user);
    }

    public function testLoadUserByIdFail() : void
    {
        $this->assertNull(User::LoadUserById($this->db, -1));
        $this->assertNull(User::LoadUserById($this->db, 12));
    }    

    /**
     * @test
     * @dataProvider providerUserMock
     */
    public function testLoadUserByNick(User $ref) : void
    {
        $user = User::LoadUserByNick($this->db, $ref->GetNick());
        $this->assertNotNull($user);
        $this->assertObjectEquals($ref, $user);
    }    

    public function testLoadUserByNickFail() : void
    {
        $this->assertNull(User::LoadUserByNick($this->db, 'nope'));
        $this->assertNull(User::LoadUserByNick($this->db, ' admin'));

        // it seems whitespaces get trimmed at the end of a prepared statement:
        $this->assertNotNull(User::LoadUserByNick($this->db, 'admin '));
    }

        /**
     * @test
     * @dataProvider providerUserMock
     */
    public function testLoadUserByEmail(User $ref) : void
    {
        $user = User::LoadUserByEmail($this->db, $ref->GetEmail());
        $this->assertNotNull($user);
        $this->assertObjectEquals($ref, $user);
    }

    public function testLoadUserByEmailFail() : void
    {
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