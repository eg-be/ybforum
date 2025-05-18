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

    /**
     * Create an instance of Post using the passed values.
     * Object is created using reflection and is an instance of User,
     * so assertions like assertObjectEquals() can be used.
     */
    protected static function mockPost(int $idpost, int $idthread, 
        ?int $parent_idpost, string $nick, int $iduser,
        string $title, ?string $content,
        int $rank, int $indent,
        string $creation_ts,
        ?string $email,
        ?string $link_url, ?string $link_text, ?string $img_url,
        ?int $old_no,
        int $hidden,
        string $ip_address) : Post
    {

        $ref = new ReflectionClass(Post::class);
        $ctor = $ref->getConstructor();
        $ctor->setAccessible(true);
        $post = $ref->newInstanceWithoutConstructor();
        $ref->getProperty('idpost')->setValue($post, $idpost);
        $ref->getProperty('idthread')->setValue($post, $idthread);
        $ref->getProperty('parent_idpost')->setValue($post, $parent_idpost);
        $ref->getProperty('nick')->setValue($post, $nick);
        $ref->getProperty('iduser')->setValue($post, $iduser);
        $ref->getProperty('title')->setValue($post, $title);
        $ref->getProperty('content')->setValue($post, $content);
        $ref->getProperty('rank')->setValue($post, $rank);
        $ref->getProperty('indent')->setValue($post, $indent);
        $ref->getProperty('creation_ts')->setValue($post, $creation_ts);
        $ref->getProperty('email')->setValue($post, $email);
        $ref->getProperty('link_url')->setValue($post, $link_url);
        $ref->getProperty('link_text')->setValue($post, $link_text);
        $ref->getProperty('img_url')->setValue($post, $img_url);
        $ref->getProperty('old_no')->setValue($post, $old_no);
        $ref->getProperty('hidden')->setValue($post, $hidden);
        $ref->getProperty('ip_address')->setValue($post, $ip_address);
        $ctor->invoke($post);
        return $post;
    }

    /**
     * Create an instance of Post using the passed values.
     * Object is created using reflection and is an instance of User,
     * so assertions like assertObjectEquals() can be used.
     */
    protected static function mockPostIndexEntry(
        int $idpost,
        int $idthread, 
        ?int $parent_idpost,
        string $nick,
        string $title,
        int $indent,
        string $creation_ts,
        int $has_content,
        int $hidden
    ) : PostIndexEntry
    {
        $ref = new ReflectionClass(PostIndexEntry::class);
        $ctor = $ref->getConstructor();
        $ctor->setAccessible(true);
        $postIndexEntry = $ref->newInstanceWithoutConstructor();
        $ref->getProperty('idpost')->setValue($postIndexEntry, $idpost);
        $ref->getProperty('idthread')->setValue($postIndexEntry, $idthread);
        $ref->getProperty('parent_idpost')->setValue($postIndexEntry, $parent_idpost);
        $ref->getProperty('nick')->setValue($postIndexEntry, $nick);
        $ref->getProperty('title')->setValue($postIndexEntry, $title);
        $ref->getProperty('indent')->setValue($postIndexEntry, $indent);
        $ref->getProperty('creation_ts')->setValue($postIndexEntry, $creation_ts);
        $ref->getProperty('has_content')->setValue($postIndexEntry, $has_content);
        $ref->getProperty('hidden')->setValue($postIndexEntry, $hidden);
        $ctor->invoke($postIndexEntry);
        return $postIndexEntry;
    }

    protected static function mockSearchResult(
        int $idpost,
        string $nick,
        string $title,
        string $creation_ts,
        ?float $relevance
    ) : SearchResult 
    {
        $ref = new ReflectionClass(SearchResult::class);
        $ctor = $ref->getConstructor();
        $ctor->setAccessible(true);
        $searchResult = $ref->newInstanceWithoutConstructor();
        $ref->getProperty('idpost')->setValue($searchResult, $idpost);
        $ref->getProperty('nick')->setValue($searchResult, $nick);
        $ref->getProperty('title')->setValue($searchResult, $title);
        $ref->getProperty('creation_ts')->setValue($searchResult, $creation_ts);
        if(!is_null($relevance)) {
            $ref->getProperty('relevance')->setValue($searchResult, $relevance);
        }
        $ctor->invoke($searchResult);
        return $searchResult;
    }
}