<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../web/model/ForumDb.php';

/**
 * Requires a valid database to connect to, as we
 * want to really test the executed sql.
 * 
 * See README.md located in this directory, on how
 * to setup the test-database.
 * 
 */
final class ForumDbTest extends TestCase
{
    private $db;

    const TEST_DB = [
        __DIR__.'/../database/dbybforum-no-data.dump.sql',
        __DIR__.'/../database/log_type_table_data.dump.sql'
    ];

    public static function setUpBeforeClass(): void
    {
        // restore an empty database for the tests
        foreach(ForumDbTest::TEST_DB as $file)
        {
            $cmd = sprintf('mysql -h localhost -u %s -p%s %s < %s 2>&1', 
            DbConfig::RW_USERNAME, DbConfig::RW_PASSWORD, DbConfig::DEFAULT_DB, $file);
            $output = null;
            $result_code = null;
            fwrite(STDOUT, 'Executing: ' . $cmd . PHP_EOL);
            $res = exec($cmd, $output, $result_code);
            if($res === false || $result_code !== 0)
            {
                throw new Exception('Failed to init test-datase: ' . implode(PHP_EOL, $output));
            }
            foreach($output as $res)
            {
                fwrite(STDOUT, $res . PHP_EOL);
            }
        }
    }

    protected function setUp(): void
    {
        $this->db = new ForumDb();
    }


    protected function assertPreConditions(): void
    {
        $this->assertTrue($this->db->IsConnected());
    }

    public function testIsReadOnly(): void
    {
        // a database is ro by default
        $this->assertTrue($this->db->IsReadOnly());
        // except we enfore a rw-db:
        $this->db = new ForumDb(false);
        $this->assertFalse($this->db->IsReadOnly());
    }

    public function testGetThreadCount() : void
    {
        $count = $this->db->GetThreadCount();
        $this->assertEquals(0, $count);
    }
}