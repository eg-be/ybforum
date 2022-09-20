<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../web/model/ForumDb.php';

/**
 * Tests requiring a database can just derive from this class.
 * This class extends itself from TestCase and will create 
 * the initial test-database in its setUp() method,
 * ensuring a fresh instance for every test.
 * 
 */
class BaseTest extends TestCase
{
    //protected $db;

    const TEST_DB = [
        __DIR__.'/../database/dbybforum-no-data.dump.sql',
        __DIR__.'/../database/log_type_table_data.dump.sql',
        __DIR__.'/data/users.sql',
        __DIR__.'/data/threads.sql',
        __DIR__.'/data/posts.sql',
        __DIR__.'/data/blacklist.sql'
    ];

    protected static function createTestDatabase() : void
    {
        // restore an empty database for the tests
        foreach(self::TEST_DB as $file)
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


/*
    protected function setUp(): void
    {
        //$this->createTestDatabase();
        //$this->db = new ForumDb();
    }

    protected function assertPreConditions(): void
    {
        //$this->assertTrue($this->db->IsConnected());
    }    
    */
}