<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../src/model/ForumDb.php';

/**
 * Can be used as base-class for tests requiring a DB.
 * Provides a static helper method to re-create a fresh
 * copy for the tests.
 */
class BaseTest extends TestCase
{
    const TEST_DB = [
        __DIR__.'/../database/dbybforum-no-data.dump.sql',
        __DIR__.'/../database/log_type_table_data.dump.sql',
        __DIR__.'/data/users.sql',
        __DIR__.'/data/threads.sql',
        __DIR__.'/data/posts.sql',
        __DIR__.'/data/blacklist.sql'
    ];

    /**
     * Run the scripts defined in TEST_DB
     * @param bool $verbose If true, the cmd executed and all output is printed
     */
    protected static function createTestDatabase(bool $verbose = false) : void
    {
        // restore an empty database for the tests
        foreach(self::TEST_DB as $file)
        {
            $cmd = sprintf('mariadb -h localhost -P 3306 -u %s -p%s %s < %s 2>&1', 
            DbConfig::RW_USERNAME, DbConfig::RW_PASSWORD, DbConfig::DEFAULT_DB, $file);
            $output = null;
            $result_code = null;
            if($verbose === true)
            {
                fwrite(STDOUT, 'Executing: ' . $cmd . PHP_EOL);
            }
            $res = exec($cmd, $output, $result_code);
            if($res === false || $result_code !== 0)
            {
                $msg = 'Failed to init test-datase [cmd-executed: ' . $cmd . ']: ' . implode(PHP_EOL, $output);
                fwrite(STDOUT, $msg);
                throw new Exception($msg);
            }
            if($verbose === true)
            {
                foreach($output as $res)
                {
                    fwrite(STDOUT, $res . PHP_EOL);
                }
            }
        }
    }

    /**
     * Create an instance of User using the passed values.
     * Object is created using reflection and is an instance of User,
     * so assertions like assertObjectEquals() can be used.
     */
    protected static function mockUser(int $iduser, string $nick, ?string $email,
        int $admin, int $active, 
        string $registration_ts, ?string $registration_msg, 
        ?string $confirmation_ts,
        ?string $password, ?string $old_passwd) : User
    {

        $ref = new ReflectionClass(User::class);
        $ctor = $ref->getConstructor();
        $ctor->setAccessible(true);
        $user = $ref->newInstanceWithoutConstructor();
        $ref->getProperty('iduser')->setValue($user, $iduser);
        $ref->getProperty('nick')->setValue($user, $nick);
        $ref->getProperty('email')->setValue($user, $email);
        $ref->getProperty('admin')->setValue($user, $admin);
        $ref->getProperty('active')->setValue($user, $active);
        $ref->getProperty('registration_ts')->setValue($user, $registration_ts);
        $ref->getProperty('registration_msg')->setValue($user, $registration_msg);
        $ref->getProperty('confirmation_ts')->setValue($user, $confirmation_ts);
        $ref->getProperty('password')->setValue($user, $password);
        $ref->getProperty('old_passwd')->setValue($user, $old_passwd);
        $ctor->invoke($user);
        return $user;        
    }
}