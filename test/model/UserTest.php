<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../BaseTest.php';
require_once __DIR__.'/../../src/model/User.php';


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

    public static function providerUserMock() : array
    {
        $admin = self::mockUser(1, 'admin', 'eg-be@dev',
            1, 1, '2020-03-30 14:30:05', 'initial admin-user',
            '2020-03-30 14:30:15', 
            '$2y$10$n.ZGkNoS3BvavZ3qcs50nelspmTfM3dh8ZLSZ5JXfBvW9rQ6i..VC', null);
        $old = self::mockUser(10, 'old-user', 'old-user@dev',
            0, 0, '2017-12-31 15:21:27', 'needs migration',
            null,
            null, '895e1aace5e13c683491bb26dd7453bf');
        $deactivated = self::mockUser(50, 'deactivated', 'deactivated@dev',
            0, 0, '2021-03-30 14:30:05', 'deactivated by admin',
            '2021-03-30 14:30:15',
            '$2y$10$U2nazhRAEhg1JkXu2Uls0.pnH5Wi9QsyXbmoJMBC2KNYGPN8fezfe', null);
        
        return array(
            [$admin],
            [$old],
            [$deactivated]
        );
    }

    #[DataProvider('providerUserMock')]
    public function testLoadUserById(User $ref) : void
    {
        $user = $this->db->LoadUserById($ref->GetId());
        $this->assertNotNull($user);
        $this->assertObjectEquals($ref, $user);
    }

    public function testLoadUserByIdFail() : void
    {
        $this->assertNull($this->db->LoadUserById(-1));
        $this->assertNull($this->db->LoadUserById(12));
    }

    #[DataProvider('providerUserMock')]
    public function testLoadUserByNick(User $ref) : void
    {
        $user =$this->db->LoadUserByNick($ref->GetNick());
        $this->assertNotNull($user);
        $this->assertObjectEquals($ref, $user);
    }    

    public function testLoadUserByNickFail() : void
    {
        $this->assertNull($this->db->LoadUserByNick('nope'));
        $this->assertNull($this->db->LoadUserByNick(' admin'));

        // it seems whitespaces get trimmed at the end of a prepared statement:
        $this->assertNotNull($this->db->LoadUserByNick('admin '));
    }

    #[DataProvider('providerUserMock')]
    public function testLoadUserByEmail(User $ref) : void
    {
        $user = $this->db->LoadUserByEmail($ref->GetEmail());
        $this->assertNotNull($user);
        $this->assertObjectEquals($ref, $user);
    }

    public function testLoadUserByEmailFail() : void
    {
        $this->assertNull($this->db->LoadUserByEmail('nope@foo'));
        $this->assertNull($this->db->LoadUserByEmail(' eg-be@dev'));
        
        // it seems whitespaces get trimmed at the end of a prepared statement:
        $this->assertNotNull($this->db->LoadUserByEmail('eg-be@dev '));        
    }

    public function testAuth() : void
    {
        $admin = $this->db->LoadUserById(1);
        $this->assertNotNull($admin);
        $this->assertTrue($admin->Auth('admin-pass'));
        $this->assertFalse($admin->Auth(' admin-pass'));

        $oldUser = $this->db->LoadUserById(10);
        $this->assertNotNull($oldUser);
        $this->assertFalse($oldUser->Auth('old-user-pass'));

        $dummy = $this->db->LoadUserById(66);
        $this->assertNotNull($dummy);
        $this->assertFalse($dummy->Auth('dummy-pass'));

        $inactive = $this->db->LoadUserById(51);
        $this->assertNotNull($inactive);
        $this->assertFalse($inactive->Auth('inactive-pass'));        
    }

    public function testOldAuth() : void
    {
        $oldUser = $this->db->LoadUserById(10);
        $this->assertNotNull($oldUser);
        $this->assertTrue($oldUser->OldAuth('old-user-pass'));
        $this->assertFalse($oldUser->OldAuth(' old-user-pass'));
        $this->assertFalse($oldUser->OldAuth('olD-user-pass'));
    }

    public function testEmail() : void
    {
        $mail = self::mockUser(13, 'nick', 'mail@foo.com',
            0, 0, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertSame('mail@foo.com', $mail->GetEmail());
        $this->assertTrue($mail->HasEmail());
        
        $noMail = self::mockUser(13, 'nick', null,
        0, 0, '2020-03-30 14:30:05', null,
        null,
        null, null
        );
        $this->assertNull($noMail->GetEmail());
        $this->assertFalse($noMail->HasEmail());
    }

    public function testAdmin() : void
    {
        $admin = self::mockUser(13, 'nick', null,
            1, 0, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertTrue($admin->IsAdmin());
        $admin = self::mockUser(13, 'nick', null,
            99, 0, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertTrue($admin->IsAdmin());

        $noAdmin = self::mockUser(13, 'nick', null,
            0, 0, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertFalse($noAdmin->IsAdmin());
        $noAdmin = self::mockUser(13, 'nick', null,
            -1, 0, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertFalse($noAdmin->IsAdmin());        
    }

    public function testActive() : void
    {
        $active = self::mockUser(13, 'nick', null,
            0, 1, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertTrue($active->IsActive());
        $active = self::mockUser(13, 'nick', null,
            0, 99, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertTrue($active->IsActive());

        $inactive = self::mockUser(13, 'nick', null,
            0, 0, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertFalse($inactive->IsActive());
        $inactive = self::mockUser(13, 'nick', null,
            0, -3, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertFalse($inactive->IsActive());        
    }
    
    public function testRegistrationMsg() : void
    {
        $msg = self::mockUser(13, 'nick', null,
            0, 0, '2020-03-30 14:30:05', 'message',
            null,
            null, null
        );
        $this->assertSame('message', $msg->GetRegistrationMsg());
        $this->assertTrue($msg->HasRegistrationMsg());
        
        $noMsg = self::mockUser(13, 'nick', null,
        0, 0, '2020-03-30 14:30:05', null,
        null,
        null, null
        );
        $this->assertNull($noMsg->GetRegistrationMsg());
        $this->assertFalse($noMsg->HasRegistrationMsg());        
    }

    public function testConfirmed() : void
    {
        $conf= self::mockUser(13, 'nick', null,
            0, 0, '2020-03-30 14:30:05', 'message',
            '2022-06-21 07:30:05',
            null, null
        );
        $this->assertEquals(new DateTime('2022-06-21 07:30:05'), $conf->GetConfirmationTimestamp());
        $this->assertTrue($conf->IsConfirmed());

        $notConf = self::mockUser(13, 'nick', null,
        0, 0, '2020-03-30 14:30:05', null,
        null,
        null, null
        );
        $this->assertNull($notConf->GetConfirmationTimestamp());
        $this->assertFalse($notConf->IsConfirmed());
    }

    public function testDummy() : void
    {
        $dummy = self::mockUser(13, 'nick', null,
            0, 0, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertTrue($dummy->IsDummyUser());

        $noDummy = self::mockUser(13, 'nick', 'mail@foo.com',
            0, 0, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertFalse($noDummy->IsDummyUser());
        $noDummy = self::mockUser(13, 'nick', null,
            0, 0, '2020-03-30 14:30:05', null,
            null,
            'password', null
        );
        $this->assertFalse($noDummy->IsDummyUser());
        $noDummy = self::mockUser(13, 'nick', null,
            0, 0, '2020-03-30 14:30:05', null,
            null,
            null, 'old-passwd'
        );
        $this->assertFalse($noDummy->IsDummyUser());
    }

    public function testMigrationAndPassword() : void
    {
        $mig = self::mockUser(13, 'nick', null,
            0, 0, '2020-03-30 14:30:05', null,
            null,
            null, 'old-pass'
        );
        $this->assertTrue($mig->HasOldPassword());
        $this->assertTrue($mig->NeedsMigration());
        $this->assertFalse($mig->HasPassword());

        $noMig = self::mockUser(13, 'nick', null,
            0, 0, '2020-03-30 14:30:05', null,
            null,
            null, null
        );
        $this->assertFalse($noMig->HasOldPassword());
        $this->assertFalse($noMig->NeedsMigration());
        $this->assertFalse($noMig->HasPassword());

        $noMig = self::mockUser(13, 'nick', null,
            0, 0, '2020-03-30 14:30:05', null,
            null,
            'new-password', null
        );
        $this->assertFalse($noMig->HasOldPassword());
        $this->assertFalse($noMig->NeedsMigration());
        $this->assertTrue($noMig->HasPassword());

    }
}