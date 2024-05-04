<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../BaseTest.php';
require_once __DIR__.'/../../src/helpers/Logger.php';


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
        $this->assertTrue($this->db->IsConnected());
    }       

    public function testConstruct(): void 
    {
        // no matter if we construct with a rw or a ro-db, 
        // or no db at all, logging must just work
        $l1 = new Logger();
        $l1->LogMessage(LogType::LOG_AUTH_FAILED_NO_SUCH_USER, "msg1");

        // construct using a rw-db
        $this->assertFalse($this->db->IsReadOnly());
        $l2 = new Logger($this->db);
        $l2->LogMessage(LogType::LOG_AUTH_FAILED_NO_SUCH_USER, "msg2");

        // construct using a ro-db
        $db = new ForumDb(true);
        $this->assertTrue($db->IsReadOnly());
        $l3 = new Logger($db);
        $l3->LogMessage(LogType::LOG_AUTH_FAILED_NO_SUCH_USER, "msg3");

        // read back
        $query = 'SELECT message '
        . 'FROM log_table '
        . 'ORDER BY idlog DESC';
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $this->assertTrue($stmt->rowCount() >= 3);
        $result = $stmt->fetch();
        $this->assertNotNull($result);
        $this->assertSame('msg3', $result['message']);
        $result = $stmt->fetch();
        $this->assertNotNull($result);
        $this->assertSame('msg2', $result['message']);
        $result = $stmt->fetch();
        $this->assertNotNull($result);
        $this->assertSame('msg1', $result['message']);
    }

    public function testGetLogTypeId(): void 
    {
        $l = new Logger($this->db);
        // just lookup some ids, shall not fail and get different positive ids
        $id1 = $l->GetLogTypeId(LogType::LOG_OPERATION_FAILED_EMAIL_NOT_UNIQUE);
        $id2 = $l->GetLogTypeId(LogType::LOG_CONFIRM_EMAIL_CODE_CREATED);
        $id3 = $l->GetLogTypeId(LogType::LOG_USER_MIGRATION_CONFIRMED);

        $this->assertNotEquals($id1, $id2);
        $this->assertNotEquals($id2, $id3);
    }

    public function testLogMessage(): void
    {
        // just log a test-message without ext-info
        // some properties are set from $_SERVER, what is probably not so nice

        $l = new Logger($this->db);
        $l->LogMessage(LogType::LOG_AUTH_FAILED_NO_SUCH_USER, "testLogMessage-msg1");

        // get the id to compare later
        $id1 = $l->GetLogTypeId(LogType::LOG_AUTH_FAILED_NO_SUCH_USER);

        // read back the values
        $query = 'SELECT idlog, idlog_type, ts, iduser, historic_user_context, message, request_uri, ip_address, admin_iduser '
        . 'FROM log_table '
        . 'ORDER BY idlog DESC';
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $this->assertTrue($stmt->rowCount() >= 1);
        $result = $stmt->fetch();
        $this->assertNotNull($result);
        $this->assertSame($id1, $result['idlog_type']);
        $this->assertNotNull($result['ts']);
        $this->assertNull($result['iduser']);
        $this->assertNull($result['historic_user_context']);
        $this->assertSame('testLogMessage-msg1', $result['message']);
        $this->assertSame('phpunit', $result['request_uri']);
        $this->assertSame('13.13.13.13', $result['ip_address']);
    }

    public function testLogMessageWithExtInfo(): void
    {
        $l = new Logger($this->db);
        $l->LogMessage(LogType::LOG_AUTH_FAILED_NO_SUCH_USER, "testLogMessageWithExtInfo-msg1", "testLogMessageWithExtInfo-ext1");

        // get the id to compare later
        $id1 = $l->GetLogTypeId(LogType::LOG_AUTH_FAILED_NO_SUCH_USER);

        // read back the values
        $query = 'SELECT idlog, idlog_type, ts, iduser, historic_user_context, message, request_uri, ip_address, admin_iduser '
        . 'FROM log_table '
        . 'ORDER BY idlog DESC';
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $this->assertTrue($stmt->rowCount() >= 1);
        $result = $stmt->fetch();
        $this->assertNotNull($result);
        $this->assertSame($id1, $result['idlog_type']);
        $this->assertNotNull($result['ts']);
        $this->assertNull($result['iduser']);
        $this->assertNull($result['historic_user_context']);
        $this->assertSame('testLogMessageWithExtInfo-msg1', $result['message']);
        $this->assertSame('phpunit', $result['request_uri']);
        $this->assertSame('13.13.13.13', $result['ip_address']);

        $idLog = $result['idlog'];
        $query = 'SELECT info '
        . 'FROM log_extended_info '
        . 'WHERE idlog = :idlog';
        $stmt = $this->db->prepare($query);
        $stmt->execute(array(':idlog' => $idLog));
        $this->assertTrue($stmt->rowCount() == 1);
        $result = $stmt->fetch();
        $this->assertNotNull($result);
        $this->assertEquals('testLogMessageWithExtInfo-ext1', $result['info']);
    }

    public function testLogMessageWithUserId(): void
    {
        // just log a test-message with some user-info
        $user101 = $this->createStub(User::class);
        $user101->method('GetId')->willReturn(101);
        $user101->method('GetMinimalUserInfoAsString')->willReturn('IdUser: 101;');

        $l = new Logger($this->db);
        $l->LogMessageWithUserId(LogType::LOG_AUTH_FAILED_NO_SUCH_USER, $user101, "testLogMessageWithUserId-msg1");

        // get the id to compare later
        $id1 = $l->GetLogTypeId(LogType::LOG_AUTH_FAILED_NO_SUCH_USER);

        // read back the values
        $query = 'SELECT idlog, idlog_type, ts, iduser, historic_user_context, message, request_uri, ip_address, admin_iduser '
        . 'FROM log_table '
        . 'ORDER BY idlog DESC';
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $this->assertTrue($stmt->rowCount() >= 1);
        $result = $stmt->fetch();
        $this->assertNotNull($result);
        $this->assertSame($id1, $result['idlog_type']);
        $this->assertNotNull($result['ts']);
        $this->assertEquals(101, $result['iduser']);
        $this->assertNotNull($result['historic_user_context']);
        $this->assertStringContainsString('IdUser: 101;', $result['historic_user_context']);
        $this->assertSame('testLogMessageWithUserId-msg1', $result['message']);
        $this->assertSame('phpunit', $result['request_uri']);
        $this->assertSame('13.13.13.13', $result['ip_address']);
    }

    public function testAllLogTypesDefinedInDb(): void
    {
        $l = new Logger($this->db);

        foreach(LogType::cases() as $lt)
        {
            $id = $l->GetLogTypeId($lt);
            $this->assertGreaterThan(0, $id);
        }
    }
}