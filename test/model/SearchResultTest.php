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

    public static function providerSearchStrings() : array 
    {
        return array(
            ['"Thread 3"', null, false, 8],
            ["Thread 3", null, false, 20],
            ['"Thread 3"', null, true, 1],
            ["Thread 3", null, true, 12],
            ['"Thread 3"', "user3", false, 3],
            ["Thread 3", "user3", false, 6],
            ['"Thread 3"', "user3", true, 1],
            ["Thread 3", "user3", true, 4],
            ["", "user3", false, 6],
            ["", "user3", true, 4],
        );
    }

    #[DataProvider('providerSearchStrings')]
    public function testSearchPosts(string $searchString, ?string $nick, bool $noReplies, int $numberOfResults) 
    {

        $res = SearchResult::SearchPosts($this->db, $searchString, $nick ? $nick : "", 100, 0, SearchResult::SORT_FIELD_RELEVANCE, SearchResult::SORT_ORDER_ASC, $noReplies);
        $resCount = count($res);
        $this->assertEquals($numberOfResults, $resCount);
    }
}