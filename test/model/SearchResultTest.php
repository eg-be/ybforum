<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../BaseTest.php';
require_once __DIR__.'/../../src/model/SearchResult.php';


/**
 * Requires a valid database to connect to, as we
 * want to really test the executed sql.
 * 
 * See README.md located in this directory, on how
 * to setup the test-database.
 * 
 */
final class SearchResultTest extends BaseTest
{
    private ForumDb $db;

    public static function setUpBeforeClass(): void
    {
        // This tests will not modify the db, its enough to re-create
        // the test-db before running all tests from this class
       // BaseTest::createTestDatabase();
    }

    protected function setUp(): void
    {
        //$this->db = new ForumDb();
    }

    protected function assertPreConditions(): void
    {
        //$this->assertTrue($this->db->IsConnected());
    }

    // todo: add some tests
}