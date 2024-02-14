<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../BaseTest.php';
require_once __DIR__.'/../../src/helpers/Logger.php';


/**
 * Requires a valid database to connect to, as we
 * want to really test the executed sql.
 */
final class LoggerTest extends BaseTest
{
    public static function setUpBeforeClass(): void
    {
        // The tests shall not rely on a given state of the db,
        // but they need one to add additional entries (and maybe then test for those entries)
        BaseTest::createTestDatabase();
    }

    protected function setUp(): void
    {
        $this->db = new ForumDb(false);
    }

    protected function assertPreConditions(): void
    {
        $this->assertTrue($this->db->IsConnected());
    }       

    public function testConstruct(): void {
        // no matter if we construct with a rw or a ro-db, 
        // or no db at all, logging must just work
        $l1 = new Logger();
        $l1->LogMessage(Logger::LOG_AUTH_FAILED_NO_SUCH_USER, "msg1");

        // construct using a rw-db
        $this->assertFalse($this->db->IsReadOnly());
        $l2 = new Logger($this->db);
        $l2->LogMessage(Logger::LOG_AUTH_FAILED_NO_SUCH_USER, "msg2");

        // construct using a ro-db
        $db = new ForumDb(true);
        $this->assertTrue($db->IsReadOnly());
        $l3 = new Logger($db);
        $l3->LogMessage(Logger::LOG_AUTH_FAILED_NO_SUCH_USER, "msg3");

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
}