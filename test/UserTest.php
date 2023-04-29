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

    public function testEmail() : void
    {
        $mail = new UserMock(13, 'nick', 'mail@foo.com',
            0, 0, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertSame('mail@foo.com', $mail->GetEmail());
        $this->assertTrue($mail->HasEmail());
        
        $noMail = new UserMock(13, 'nick', null,
        0, 0, '2020-03-30 14:30:05', null,
        null,
        null, null
        );
        $this->assertNull($noMail->GetEmail());
        $this->assertFalse($noMail->HasEmail());
    }

    public function testAdmin() : void
    {
        $admin = new UserMock(13, 'nick', null,
            1, 0, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertTrue($admin->IsAdmin());
        $admin = new UserMock(13, 'nick', null,
            99, 0, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertTrue($admin->IsAdmin());

        $noAdmin = new UserMock(13, 'nick', null,
            0, 0, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertFalse($noAdmin->IsAdmin());
        $noAdmin = new UserMock(13, 'nick', null,
            -1, 0, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertFalse($noAdmin->IsAdmin());        
    }

    public function testActive() : void
    {
        $active = new UserMock(13, 'nick', null,
            0, 1, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertTrue($active->IsActive());
        $active = new UserMock(13, 'nick', null,
            0, 99, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertTrue($active->IsActive());

        $inactive = new UserMock(13, 'nick', null,
            0, 0, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertFalse($inactive->IsActive());
        $inactive = new UserMock(13, 'nick', null,
            0, -3, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertFalse($inactive->IsActive());        
    }
    
    public function testRegistrationMsg() : void
    {
        $msg = new UserMock(13, 'nick', null,
            0, 0, '2020-03-30 14:30:05', 'message',
            null,
            null, null
        );
        $this->assertSame('message', $msg->GetRegistrationMsg());
        $this->assertTrue($msg->HasRegistrationMsg());
        
        $noMsg = new UserMock(13, 'nick', null,
        0, 0, '2020-03-30 14:30:05', null,
        null,
        null, null
        );
        $this->assertNull($noMsg->GetRegistrationMsg());
        $this->assertFalse($noMsg->HasRegistrationMsg());        
    }

    public function testConfirmed() : void
    {
        $conf= new UserMock(13, 'nick', null,
            0, 0, '2020-03-30 14:30:05', 'message',
            '2022-06-21 07:30:05',
            null, null
        );
        $this->assertEquals(new DateTime('2022-06-21 07:30:05'), $conf->GetConfirmationTimestamp());
        $this->assertTrue($conf->IsConfirmed());
        $this->assertFalse($conf->NeedsConfirmation());

        $notConf = new UserMock(13, 'nick', null,
        0, 0, '2020-03-30 14:30:05', null,
        null,
        null, null
        );
        $this->assertNull($notConf->GetConfirmationTimestamp());
        $this->assertFalse($notConf->IsConfirmed());
        $this->assertTrue($notConf->NeedsConfirmation());
    }

    public function testDummy() : void
    {
        $dummy = new UserMock(13, 'nick', null,
            0, 0, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertTrue($dummy->IsDummyUser());

        $noDummy = new UserMock(13, 'nick', 'mail@foo.com',
            0, 0, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertFalse($noDummy->IsDummyUser());
        $noDummy = new UserMock(13, 'nick', null,
            0, 0, '2020-03-30 14:30:05', null,
            null,
            'password', null
        );
        $this->assertFalse($noDummy->IsDummyUser());
        $noDummy = new UserMock(13, 'nick', null,
            0, 0, '2020-03-30 14:30:05', null,
            null,
            null, 'old-passwd'
        );
        $this->assertFalse($noDummy->IsDummyUser());
    }

    public function testMigrationAndPassword() : void
    {
        $mig = new UserMock(13, 'nick', null,
            0, 0, '2020-03-30 14:30:05', null,
            null,
            null, 'old-pass'
        );
        $this->assertTrue($mig->HasOldPassword());
        $this->assertTrue($mig->NeedsMigration());
        $this->assertFalse($mig->HasPassword());

        $noMig = new UserMock(13, 'nick', null,
            0, 0, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertFalse($noMig->HasOldPassword());
        $this->assertFalse($noMig->NeedsMigration());
        $this->assertFalse($noMig->HasPassword());

        $noMig = new UserMock(13, 'nick', null,
            0, 0, '2020-03-30 14:30:05', null,
            null,
            'new-password', null
        );
        $this->assertFalse($noMig->HasOldPassword());
        $this->assertFalse($noMig->NeedsMigration());
        $this->assertTrue($noMig->HasPassword());

    }
}