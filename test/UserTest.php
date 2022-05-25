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
    public function testLoadUserById() : void
    {
        $admin = User::LoadUserById($this->db, 1);
        $this->assertNotNull($admin);
        $this->assertEquals(1, $admin->GetId());
        $this->assertEquals('admin', $admin->GetNick());
        $this->assertTrue($admin->HasEmail());
        $this->assertEquals('eg-be@dev', $admin->GetEmail());
        $this->assertTrue($admin->IsAdmin());
        $this->assertTrue($admin->IsActive());
        $this->assertEquals(new DateTime('2020-03-30 14:30:05'), $admin->GetRegistrationTimestamp());
        $this->assertEquals('initial admin-user', $admin->GetRegistrationMsg());
        $this->assertTrue($admin->HasRegistrationMsg());
        $this->assertTrue($admin->IsConfirmed());
        $this->assertEquals(new DateTime('2020-03-30 14:30:15'), $admin->GetConfirmationTimestamp());
        $this->assertFalse($admin->IsDummyUser());
        $this->assertFalse($admin->NeedsMigration());
        $this->assertFalse($admin->NeedsConfirmation());

    }
}