<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../BaseTest.php';
require_once __DIR__ . '/../../src/model/User.php';


/**
 * Mostly tests about the constructor / getters parsing things fine.
 * And test all logic implemented in User
 */
final class UserTest extends BaseTest
{
    private ForumDb $db;

    public static function setUpBeforeClass(): void {}

    protected function setUp(): void {}

    protected function assertPreConditions(): void {}

    public function testauth(): void
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
        static::assertTrue($admin->auth('admin-pass'));
        static::assertFalse($admin->auth(' admin-pass'));

        $oldUser = self::mockUser(
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
        static::assertFalse($oldUser->auth('old-user-pass'));

        $dummy = self::mockUser(
            66,
            'dummy',
            null,
            0,
            0,
            '2020-03-30 14:30:05',
            'initial dummy',
            null,
            null,
            null
        );
        static::assertFalse($dummy->auth('dummy-pass'));

        $needsApproval = self::mockUser(
            51,
            'needs-approval',
            'needs-approval@dev',
            0,
            0,
            '2020-03-30 14:30:05',
            'inactive (but confirmed) waiting for admin confirmation',
            '2025-04-26 22:00:08',
            '$2y$10$vzzdRF/SrnhQxSwrbVyFNeW07E5dKdx3Nwwix.ONMCDDResM4zq5u',
            null
        );
        static::assertFalse($needsApproval->auth('inactive-pass'));
    }

    public function testoldauth(): void
    {
        $oldUser = self::mockUser(
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
        static::assertFalse($oldUser->auth('old-user-pass'));
        static::assertTrue($oldUser->oldauth('old-user-pass'));
        static::assertFalse($oldUser->oldauth(' old-user-pass'));
        static::assertFalse($oldUser->oldauth('olD-user-pass'));
    }

    public function testOldAuth_noOldPasswordSet(): void
    {
        $user = self::mockUser(
            1,
            'user',
            'user@dev',
            0,
            1,
            '2020-03-30 14:30:05',
            'just a user with no old password set',
            '2020-03-30 14:30:15',
            '$2y$10$n.ZGkNoS3BvavZ3qcs50nelspmTfM3dh8ZLSZ5JXfBvW9rQ6i..VC',
            null
        );
        static::assertFalse($user->oldauth('some password'));
    }

    public function testEmail(): void
    {
        $mail = self::mockUser(
            13,
            'nick',
            'mail@foo.com',
            0,
            0,
            '2020-03-30 14:30:05',
            null,
            null,
            null,
            null
        );
        static::assertSame('mail@foo.com', $mail->getEmail());
        static::assertTrue($mail->hasEmail());

        $noMail = self::mockUser(
            13,
            'nick',
            null,
            0,
            0,
            '2020-03-30 14:30:05',
            null,
            null,
            null,
            null
        );
        static::assertNull($noMail->getEmail());
        static::assertFalse($noMail->hasEmail());
    }

    public function testAdmin(): void
    {
        $admin = self::mockUser(
            13,
            'nick',
            null,
            1,
            0,
            '2020-03-30 14:30:05',
            null,
            null,
            null,
            null
        );
        static::assertTrue($admin->isAdmin());
        $admin = self::mockUser(
            13,
            'nick',
            null,
            99,
            0,
            '2020-03-30 14:30:05',
            null,
            null,
            null,
            null
        );
        static::assertTrue($admin->isAdmin());

        $noAdmin = self::mockUser(
            13,
            'nick',
            null,
            0,
            0,
            '2020-03-30 14:30:05',
            null,
            null,
            null,
            null
        );
        static::assertFalse($noAdmin->isAdmin());
        $noAdmin = self::mockUser(
            13,
            'nick',
            null,
            -1,
            0,
            '2020-03-30 14:30:05',
            null,
            null,
            null,
            null
        );
        static::assertFalse($noAdmin->isAdmin());
    }

    public function testActive(): void
    {
        $active = self::mockUser(
            13,
            'nick',
            null,
            0,
            1,
            '2020-03-30 14:30:05',
            null,
            null,
            null,
            null
        );
        static::assertTrue($active->isActive());
        $active = self::mockUser(
            13,
            'nick',
            null,
            0,
            99,
            '2020-03-30 14:30:05',
            null,
            null,
            null,
            null
        );
        static::assertTrue($active->isActive());

        $inactive = self::mockUser(
            13,
            'nick',
            null,
            0,
            0,
            '2020-03-30 14:30:05',
            null,
            null,
            null,
            null
        );
        static::assertFalse($inactive->isActive());
        $inactive = self::mockUser(
            13,
            'nick',
            null,
            0,
            -3,
            '2020-03-30 14:30:05',
            null,
            null,
            null,
            null
        );
        static::assertFalse($inactive->isActive());
    }

    public function testRegistrationMsg(): void
    {
        $msg = self::mockUser(
            13,
            'nick',
            null,
            0,
            0,
            '2020-03-30 14:30:05',
            'message',
            null,
            null,
            null
        );
        static::assertSame('message', $msg->getRegistrationMsg());
        static::assertTrue($msg->hasRegistrationMsg());

        $noMsg = self::mockUser(
            13,
            'nick',
            null,
            0,
            0,
            '2020-03-30 14:30:05',
            null,
            null,
            null,
            null
        );
        static::assertNull($noMsg->getRegistrationMsg());
        static::assertFalse($noMsg->hasRegistrationMsg());
    }

    public function testConfirmed(): void
    {
        $conf = self::mockUser(
            13,
            'nick',
            null,
            0,
            0,
            '2020-03-30 14:30:05',
            'message',
            '2022-06-21 07:30:05',
            null,
            null
        );
        static::assertEquals(new DateTime('2022-06-21 07:30:05'), $conf->getConfirmationTimestamp());
        static::assertTrue($conf->isConfirmed());

        $notConf = self::mockUser(
            13,
            'nick',
            null,
            0,
            0,
            '2020-03-30 14:30:05',
            null,
            null,
            null,
            null
        );
        static::assertNull($notConf->getConfirmationTimestamp());
        static::assertFalse($notConf->isConfirmed());
    }

    public function testDummy(): void
    {
        $dummy = self::mockUser(
            13,
            'nick',
            null,
            0,
            0,
            '2020-03-30 14:30:05',
            null,
            null,
            null,
            null
        );
        static::assertTrue($dummy->isDummyUser());

        $noDummy = self::mockUser(
            13,
            'nick',
            'mail@foo.com',
            0,
            0,
            '2020-03-30 14:30:05',
            null,
            null,
            null,
            null
        );
        static::assertFalse($noDummy->isDummyUser());
        $noDummy = self::mockUser(
            13,
            'nick',
            null,
            0,
            0,
            '2020-03-30 14:30:05',
            null,
            null,
            'password',
            null
        );
        static::assertFalse($noDummy->isDummyUser());
        $noDummy = self::mockUser(
            13,
            'nick',
            null,
            0,
            0,
            '2020-03-30 14:30:05',
            null,
            null,
            null,
            'old-passwd'
        );
        static::assertFalse($noDummy->isDummyUser());
    }

    public function testMigrationAndPassword(): void
    {
        $mig = self::mockUser(
            13,
            'nick',
            null,
            0,
            0,
            '2020-03-30 14:30:05',
            null,
            null,
            null,
            'old-pass'
        );
        static::assertTrue($mig->hasOldPassword());
        static::assertTrue($mig->needsMigration());
        static::assertFalse($mig->hasPassword());

        $noMig = self::mockUser(
            13,
            'nick',
            null,
            0,
            0,
            '2020-03-30 14:30:05',
            null,
            null,
            null,
            null
        );
        static::assertFalse($noMig->hasOldPassword());
        static::assertFalse($noMig->needsMigration());
        static::assertFalse($noMig->hasPassword());

        $noMig = self::mockUser(
            13,
            'nick',
            null,
            0,
            0,
            '2020-03-30 14:30:05',
            null,
            null,
            'new-password',
            null
        );
        static::assertFalse($noMig->hasOldPassword());
        static::assertFalse($noMig->needsMigration());
        static::assertTrue($noMig->hasPassword());
    }

    public function testgetFullUserInfoAsString(): void
    {
        $conf = self::mockUser(
            13,
            'nick',
            'mail@foo.com',
            0,
            0,
            '2020-03-30 14:30:05',
            'message',
            '2022-06-21 07:30:05',
            null,
            null
        );
        static::assertEquals('IdUser: 13; Nick: nick; Email: mail@foo.com; Active: No; Confirmed: Yes; Needs Migration: No; HasPassword: No; HasOldPassword: No; IsAdmin: No; IsDummy: No; Registration Timestamp: 30.03.2020 14:30:05; Confirmation Timestamp: 21.06.2022 07:30:05; Registration Message: message', $conf->getFullUserInfoAsString());

        $notConf = self::mockUser(
            13,
            'nick',
            null,
            0,
            0,
            '2020-03-30 14:30:05',
            null,
            null,
            null,
            null
        );
        static::assertEquals('IdUser: 13; Nick: nick; Email: <No Email set>; Active: No; Confirmed: No; Needs Migration: No; HasPassword: No; HasOldPassword: No; IsAdmin: No; IsDummy: Yes; Registration Timestamp: 30.03.2020 14:30:05; Confirmation Timestamp: <Not Confirmed>; Registration Message: <No Registration Message set>', $notConf->getFullUserInfoAsString());
    }
}
