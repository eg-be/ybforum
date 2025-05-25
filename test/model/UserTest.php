<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../BaseTest.php';
require_once __DIR__.'/../../src/model/User.php';


 /**
  * Mostly tests about the constructor / getters parsing things fine.
  * And test all logic implemented in User
  */
final class UserTest extends BaseTest
{
    private ForumDb $db;

    public static function setUpBeforeClass(): void
    {
    }

    protected function setUp(): void
    {
    }

    protected function assertPreConditions(): void
    {
    }       

    public function testAuth() : void
    {
        $admin = self::mockUser(1, 'admin', 'eg-be@dev',
            1, 1,
            '2020-03-30 14:30:05', 'initial admin-user',
            '2020-03-30 14:30:15', 
            '$2y$10$n.ZGkNoS3BvavZ3qcs50nelspmTfM3dh8ZLSZ5JXfBvW9rQ6i..VC', null);
        $this->assertTrue($admin->Auth('admin-pass'));
        $this->assertFalse($admin->Auth(' admin-pass'));

        $oldUser = self::mockUser(10, 'old-user', 'old-user@dev',
            0, 0,
            '2017-12-31 15:21:27', 'needs migration',
            null,
            null, '895e1aace5e13c683491bb26dd7453bf');
        $this->assertFalse($oldUser->Auth('old-user-pass'));

        $dummy = self::mockUser(66, 'dummy', null,
            0, 0,
            '2020-03-30 14:30:05', 'initial dummy',
            null,
            null, null
        );
        $this->assertFalse($dummy->Auth('dummy-pass'));

        $needsApproval = self::mockUser(51, 'needs-approval', 'needs-approval@dev',
            0, 0,
            '2020-03-30 14:30:05', 'inactive (but confirmed) waiting for admin confirmation',
            '2025-04-26 22:00:08',
            '$2y$10$vzzdRF/SrnhQxSwrbVyFNeW07E5dKdx3Nwwix.ONMCDDResM4zq5u', null
        );
        $this->assertFalse($needsApproval->Auth('inactive-pass'));
    }

    public function testOldAuth() : void
    {
        $oldUser = self::mockUser(10, 'old-user', 'old-user@dev',
            0, 0,
            '2017-12-31 15:21:27', 'needs migration',
            null,
            null, '895e1aace5e13c683491bb26dd7453bf');
        $this->assertFalse($oldUser->Auth('old-user-pass'));
        $this->assertTrue($oldUser->OldAuth('old-user-pass'));
        $this->assertFalse($oldUser->OldAuth(' old-user-pass'));
        $this->assertFalse($oldUser->OldAuth('olD-user-pass'));
    }

    public function testOldAuth_noOldPasswordSet() : void
    {
        $user = self::mockUser(1, 'user', 'user@dev',
            0, 1,
            '2020-03-30 14:30:05', 'just a user with no old password set',
            '2020-03-30 14:30:15', 
            '$2y$10$n.ZGkNoS3BvavZ3qcs50nelspmTfM3dh8ZLSZ5JXfBvW9rQ6i..VC', null);
        $this->assertFalse($user->OldAuth('some password'));
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

    public function testGetFullUserInfoAsString() : void
    {
        $conf= self::mockUser(13, 'nick', 'mail@foo.com',
            0, 0, '2020-03-30 14:30:05', 'message',
            '2022-06-21 07:30:05',
            null, null
        );
        $this->assertEquals('IdUser: 13; Nick: nick; Email: mail@foo.com; Active: No; Confirmed: Yes; Needs Migration: No; HasPassword: No; HasOldPassword: No; IsAdmin: No; IsDummy: No; Registration Timestamp: 30.03.2020 14:30:05; Confirmation Timestamp: 21.06.2022 07:30:05; Registration Message: message', $conf->GetFullUserInfoAsString());

        $notConf = self::mockUser(13, 'nick', null,
        0, 0, '2020-03-30 14:30:05', null,
        null,
        null, null
        );
        $this->assertEquals('IdUser: 13; Nick: nick; Email: <No Email set>; Active: No; Confirmed: No; Needs Migration: No; HasPassword: No; HasOldPassword: No; IsAdmin: No; IsDummy: Yes; Registration Timestamp: 30.03.2020 14:30:05; Confirmation Timestamp: <Not Confirmed>; Registration Message: <No Registration Message set>', $notConf->GetFullUserInfoAsString());
    }
}