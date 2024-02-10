<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../BaseTest.php';
require_once __DIR__.'/../../src/model/PostIndexEntry.php';


/**
 * Requires a valid database to connect to, as we
 * want to really test the executed sql.
 * 
 * See README.md located in this directory, on how
 * to setup the test-database.
 * 
 */
final class PostIndexEntryTest extends BaseTest
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

    /**
     * @Test
     * Just a dummy to avoid a warning because we have no tests yet
     */
    public function testDummy(): void
    {
        // todo: fixme
        $this->assertTrue(true);
    }
}