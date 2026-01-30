<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../BaseTest.php';
require_once __DIR__ . '/../../src/helpers/Logger.php';


/**
 * Requires a valid database to connect to, as we
 * want to really test the executed sql.
 */
final class LoggerTest extends BaseTest
{
    private ForumDb $db;

    public static function setUpBeforeClass(): void
    {
        // The tests shall not rely on a given state of the db,
        // but they need one to add additional entries (and maybe then test for those entries)
        BaseTest::createTestDatabase();
    }

    protected function setUp(): void
    {
        $this->db = new ForumDb(false);
        // the following two values are set from bootstrap.php, but any other test
        // might overwrite it, so set them again here where we actually test for those two
        $_SERVER['REMOTE_ADDR'] = '13.13.13.13';
        $_SERVER['REQUEST_URI'] = 'phpunit';
    }

    protected function assertPreConditions(): void
    {
        static::assertTrue($this->db->isConnected());
    }

    public function testConstruct(): void
    {
        // no matter if we construct with a rw or a ro-db,
        // or no db at all, logging must just work
        $l1 = new Logger();
        $l1->logMessage(LogType::LOG_AUTH_FAILED_NO_SUCH_USER, "msg1");

        // construct using a rw-db
        static::assertFalse($this->db->isReadOnly());
        $l2 = new Logger($this->db);
        $l2->logMessage(LogType::LOG_AUTH_FAILED_NO_SUCH_USER, "msg2");

        // construct using a ro-db
        $db = new ForumDb(true);
        static::assertTrue($db->isReadOnly());
        $l3 = new Logger($db);
        $l3->logMessage(LogType::LOG_AUTH_FAILED_NO_SUCH_USER, "msg3");

        // read back
        $query = 'SELECT message '
        . 'FROM log_table '
        . 'ORDER BY idlog DESC';
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        static::assertTrue($stmt->rowCount() >= 3);
        $result = $stmt->fetch();
        static::assertNotNull($result);
        static::assertSame('msg3', $result['message']);
        $result = $stmt->fetch();
        static::assertNotNull($result);
        static::assertSame('msg2', $result['message']);
        $result = $stmt->fetch();
        static::assertNotNull($result);
        static::assertSame('msg1', $result['message']);
    }

    public function testgetLogTypeId(): void
    {
        $l = new Logger($this->db);
        // just lookup some ids, shall not fail and get different positive ids
        $id1 = $l->getLogTypeId(LogType::LOG_OPERATION_FAILED_EMAIL_NOT_UNIQUE);
        $id2 = $l->getLogTypeId(LogType::LOG_CONFIRM_EMAIL_CODE_CREATED);
        $id3 = $l->getLogTypeId(LogType::LOG_USER_MIGRATION_CONFIRMED);

        static::assertNotEquals($id1, $id2);
        static::assertNotEquals($id2, $id3);
    }

    public function testlogMessage(): void
    {
        // just log a test-message without ext-info
        // some properties are set from $_SERVER, what is probably not so nice

        $l = new Logger($this->db);
        $l->logMessage(LogType::LOG_AUTH_FAILED_NO_SUCH_USER, "testLogMessage-msg1");

        // get the id to compare later
        $id1 = $l->getLogTypeId(LogType::LOG_AUTH_FAILED_NO_SUCH_USER);

        // read back the values
        $query = 'SELECT idlog, idlog_type, ts, iduser, historic_user_context, message, request_uri, ip_address, admin_iduser '
        . 'FROM log_table '
        . 'ORDER BY idlog DESC';
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        static::assertTrue($stmt->rowCount() >= 1);
        $result = $stmt->fetch();
        static::assertNotNull($result);
        static::assertSame($id1, $result['idlog_type']);
        static::assertNotNull($result['ts']);
        static::assertNull($result['iduser']);
        static::assertNull($result['historic_user_context']);
        static::assertSame('testLogMessage-msg1', $result['message']);
        static::assertSame('phpunit', $result['request_uri']);
        static::assertSame('13.13.13.13', $result['ip_address']);
    }

    public function testLogMessageShouldGetTruncated(): void
    {
        // just log a test-message without ext-info
        // some properties are set from $_SERVER, what is probably not so nice
        $msg = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit.";
        $msgTruncated = mb_substr($msg, 0, 255, 'UTF-8');

        $l = new Logger($this->db);
        $l->logMessage(LogType::LOG_CONTACT_FORM_SUBMITTED, $msg);

        // get the id to compare later
        $id1 = $l->getLogTypeId(LogType::LOG_CONTACT_FORM_SUBMITTED);

        // read back the values
        $query = 'SELECT idlog, idlog_type, ts, iduser, historic_user_context, message, request_uri, ip_address, admin_iduser '
        . 'FROM log_table '
        . 'ORDER BY idlog DESC';
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        static::assertTrue($stmt->rowCount() >= 1);
        $result = $stmt->fetch();
        static::assertNotNull($result);
        static::assertSame($id1, $result['idlog_type']);
        static::assertNotNull($result['ts']);
        static::assertNull($result['iduser']);
        static::assertNull($result['historic_user_context']);
        static::assertSame($msgTruncated, $result['message']);
        static::assertSame('phpunit', $result['request_uri']);
        static::assertSame('13.13.13.13', $result['ip_address']);
    }

    public function testLogMessageWithExtInfo(): void
    {
        $l = new Logger($this->db);
        $l->logMessage(LogType::LOG_AUTH_FAILED_NO_SUCH_USER, "testLogMessageWithExtInfo-msg1", "testLogMessageWithExtInfo-ext1");

        // get the id to compare later
        $id1 = $l->getLogTypeId(LogType::LOG_AUTH_FAILED_NO_SUCH_USER);

        // read back the values
        $query = 'SELECT idlog, idlog_type, ts, iduser, historic_user_context, message, request_uri, ip_address, admin_iduser '
        . 'FROM log_table '
        . 'ORDER BY idlog DESC';
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        static::assertTrue($stmt->rowCount() >= 1);
        $result = $stmt->fetch();
        static::assertNotNull($result);
        static::assertSame($id1, $result['idlog_type']);
        static::assertNotNull($result['ts']);
        static::assertNull($result['iduser']);
        static::assertNull($result['historic_user_context']);
        static::assertSame('testLogMessageWithExtInfo-msg1', $result['message']);
        static::assertSame('phpunit', $result['request_uri']);
        static::assertSame('13.13.13.13', $result['ip_address']);

        $idLog = $result['idlog'];
        $query = 'SELECT info '
        . 'FROM log_extended_info '
        . 'WHERE idlog = :idlog';
        $stmt = $this->db->prepare($query);
        $stmt->execute([':idlog' => $idLog]);
        static::assertTrue($stmt->rowCount() == 1);
        $result = $stmt->fetch();
        static::assertNotNull($result);
        static::assertEquals('testLogMessageWithExtInfo-ext1', $result['info']);
    }

    public function testlogMessageWithUserId(): void
    {
        // just log a test-message with some user-info
        $user101 = static::createStub(User::class);
        $user101->method('GetId')->willReturn(101);
        $user101->method('GetMinimalUserInfoAsString')->willReturn('IdUser: 101;');

        $l = new Logger($this->db);
        $l->logMessageWithUserId(LogType::LOG_AUTH_FAILED_NO_SUCH_USER, $user101, "testLogMessageWithUserId-msg1");

        // get the id to compare later
        $id1 = $l->getLogTypeId(LogType::LOG_AUTH_FAILED_NO_SUCH_USER);

        // read back the values
        $query = 'SELECT idlog, idlog_type, ts, iduser, historic_user_context, message, request_uri, ip_address, admin_iduser '
        . 'FROM log_table '
        . 'ORDER BY idlog DESC';
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        static::assertTrue($stmt->rowCount() >= 1);
        $result = $stmt->fetch();
        static::assertNotNull($result);
        static::assertSame($id1, $result['idlog_type']);
        static::assertNotNull($result['ts']);
        static::assertEquals(101, $result['iduser']);
        static::assertNotNull($result['historic_user_context']);
        static::assertStringContainsString('IdUser: 101;', $result['historic_user_context']);
        static::assertSame('testLogMessageWithUserId-msg1', $result['message']);
        static::assertSame('phpunit', $result['request_uri']);
        static::assertSame('13.13.13.13', $result['ip_address']);
    }

    public function testLogMessageWithAdminContext(): void
    {
        // just log a test-message where user-context is set
        $_SESSION['adminuserid'] = 1;
        $user101 = static::createStub(User::class);
        $user101->method('GetId')->willReturn(101);
        $user101->method('GetMinimalUserInfoAsString')->willReturn('IdUser: 101;');

        $l = new Logger($this->db);
        $l->logMessageWithUserId(LogType::LOG_NOTIFIED_USER_ACCEPTED, $user101);

        // get the id to compare later
        $id1 = $l->getLogTypeId(LogType::LOG_NOTIFIED_USER_ACCEPTED);

        // read back the values
        $query = 'SELECT idlog, idlog_type, ts, iduser, historic_user_context, message, request_uri, ip_address, admin_iduser '
        . 'FROM log_table '
        . 'ORDER BY idlog DESC';
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        static::assertTrue($stmt->rowCount() >= 1);
        $result = $stmt->fetch();
        static::assertNotNull($result);
        static::assertSame($id1, $result['idlog_type']);
        static::assertNotNull($result['ts']);
        static::assertEquals(101, $result['iduser']);
        static::assertNotNull($result['historic_user_context']);
        static::assertStringContainsString('IdUser: 101;', $result['historic_user_context']);
        static::assertSame('phpunit', $result['request_uri']);
        static::assertSame('13.13.13.13', $result['ip_address']);
        static::assertSame(1, $result['admin_iduser']);
    }

    public function testAllLogTypesDefinedInDb(): void
    {
        $l = new Logger($this->db);

        foreach (LogType::cases() as $lt) {
            $id = $l->getLogTypeId($lt);
            static::assertGreaterThan(0, $id);
        }
    }
}
