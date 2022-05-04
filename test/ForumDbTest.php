<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../web/model/ForumDb.php';

/**
 * Requires a valid database to connect to, as we
 * want to really test the executed sql
 * on the current installation, this is accessible as
 * user/pass:
 *  root2/master
 * On a fresh installation, just load the test-dbyforum.dump file:
 * In mysql:
 *  CREATE DATABASE dbbforum;
 * From terminal:
 *  mysql -u root2 -p dbybforum < test-dbyforum.dump
 * 
 */
final class ForumDbTest extends TestCase
{
    private $db;

    protected function setUp(): void
    {
        $this->db = new ForumDb();
    }


    protected function assertPreConditions(): void
    {
        $this->assertTrue($this->db->IsConnected());
    }


    public function testIsConnected(): void
    {
        $this->assertTrue(true);
    }
}