<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../../src/handlers/SearchHandler.php';

/**
 * No Database stuff required
 */
final class SearchHandlerTest extends TestCase
{
    // required mocks our handler under test depends on
    private ForumDb $db;
    private PDOStatement $stmt;

    // our actuall handler to test
    private SearchHandler $sh;

    protected function setUp(): void
    {
        $this->db = $this->createStub(ForumDb::class);
        //$this->stmt = $this->createMock(PDOStatement::class);
        //$this->db->method('prepare')->willReturn($this->stmt);
        $this->sh = new SearchHandler();
        // dont know why we need to set this here, as it is already defined in bootstrap.php
        $_SERVER['REMOTE_ADDR'] = '13.13.13.13';
        // must always reset all previously set $_POST entries
        $_POST = array();
    }

    public function testSearch_failsIfNoQueryPassed()
    {
        $_POST[SearchHandler::PARAM_SEARCH_STRING] = '';
        $_POST[SearchHandler::PARAM_NICK] = '';
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(SearchHandler::MSG_NO_SEARCH_PARAMS_GIVEN);
        $this->expectExceptionCode(SearchHandler::MSGCODE_BAD_PARAM);

        $this->sh->HandleRequest($this->db);
    }

    public function testSearch_failsSearchStringTooShort()
    {
        $query = str_pad('a', YbForumConfig::MIN_SEARCH_LENGTH - 1, 'b');
        $_POST[SearchHandler::PARAM_SEARCH_STRING] = $query;
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(SearchHandler::MSG_SEARCH_STRING_TOO_SHORT);
        $this->expectExceptionCode(SearchHandler::MSGCODE_BAD_PARAM);

        $this->sh->HandleRequest($this->db);
    }

    public function testGetValidSortFieldsWithoutSearchStr()
    {
        $_POST[SearchHandler::PARAM_NICK] = 'nickname';
        
        $this->sh->HandleRequest($this->db);

        $fields = $this->sh->GetValidSortFields();
        $this->assertEqualsCanonicalizing(array(
            SortField::FIELD_DATE,
            SortField::FIELD_TITLE,
            SortField::FIELD_NICK), $fields);
    }

    public function testGetValidSortFieldsWithSearchStr()
    {
        $_POST[SearchHandler::PARAM_SEARCH_STRING] = 'my search query';
        
        $this->sh->HandleRequest($this->db);

        $fields = $this->sh->GetValidSortFields();
        // can now be sorted by relevance
        $this->assertEqualsCanonicalizing(array(
            SortField::FIELD_DATE,
            SortField::FIELD_TITLE,
            SortField::FIELD_NICK,
            SortField::FIELD_RELEVANCE
        ), $fields);
    }

    public function testPagination()
    {
        $_POST[SearchHandler::PARAM_SEARCH_STRING] = 'my search query';

        // this tests assumes a total of 2800 results

        // we know the currently configured result-size is 1000
        $this->assertEquals(1000, $this->sh->GetLimit());

        // return 1001 results: The DB is queried with a limit that is set to +1 off the configured limit
        $resultsToReturn = 1001;
        $this->db->method('SearchPosts')->willReturnCallback(
            function() use (&$resultsToReturn) {
                return array_fill(0, $resultsToReturn, $this->createStub(SearchResult::class));
            });

        // the first call shall have 1000 results and more must be available
        $this->sh->HandleRequest($this->db);
        $this->assertEquals(true, $this->sh->HasResults());
        $this->assertEquals(1000, sizeof($this->sh->GetResults()));
        $this->assertEquals(true, $this->sh->MoreRecordsAvailable());
        $this->assertEquals(0, $this->sh->GetResultOffset());
        $this->assertEquals(true, $this->sh->IsFirstRecordBlock());
        $this->assertEquals(1000, $this->sh->GetNextOffset());

        // assume we query the next page: First results are from 0 to 999, therefore offset 1000
        $resultCount = 0;
        $resultsToReturn = 1001;
        $_POST[SearchHandler::PARAM_RESULT_OFFSET] = 1000;
        $this->sh->HandleRequest($this->db);
        $this->assertEquals(true, $this->sh->HasResults());
        $this->assertEquals(1000, sizeof($this->sh->GetResults()));
        $this->assertEquals(true, $this->sh->MoreRecordsAvailable());
        $this->assertEquals(1000, $this->sh->GetResultOffset());
        $this->assertEquals(false, $this->sh->IsFirstRecordBlock());
        $this->assertEquals(2000,  $this->sh->GetNextOffset());

        // query the remaining 800 results
        $resultCount = 0;
        $resultsToReturn = 800;
        $_POST[SearchHandler::PARAM_RESULT_OFFSET] = 2000;
        $this->sh->HandleRequest($this->db);
        $this->assertEquals(true, $this->sh->HasResults());
        $this->assertEquals(800, sizeof($this->sh->GetResults()));
        $this->assertEquals(false, $this->sh->MoreRecordsAvailable());
        $this->assertEquals(2000, $this->sh->GetResultOffset());
        $this->assertEquals(false, $this->sh->IsFirstRecordBlock());
        $this->assertEquals(3000,  $this->sh->GetNextOffset());
    }
}