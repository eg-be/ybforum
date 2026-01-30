<?php

declare(strict_types=1);

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require 'ImporterConfig.php';

// use exceptions throw from mysqli when possible
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/**
 * Description of UserImporter
 *
 * @author eli
 */
class DataImporter
{
    public function __construct()
    {
        $this->m_sourceDb = $this->createDb(
            ImporterConfig::SERVERNAME,
            ImporterConfig::USERNAME,
            ImporterConfig::PASSWORD,
            ImporterConfig::SOURCE_CHARSET,
            ImporterConfig::SOURCE_DB
        );

        $this->m_destDb = $this->createDb(
            ImporterConfig::SERVERNAME,
            ImporterConfig::USERNAME,
            ImporterConfig::PASSWORD,
            ImporterConfig::DEST_CHARSET,
            ImporterConfig::DEST_DB
        );
    }

    public function createDb(
        string $server,
        string $user,
        string $pass,
        string $charset,
        string $defaultDb
    ) {
        $db = new mysqli($server, $user, $pass);

        if ($db->connect_error) {
            throw new Exception('Failed to connect to db ('
                    . $db->connect_errno . '): '
                    . $db->connect_error);
        }
        if (!$db->set_charset($charset)) {
            throw new Exception('Failed to set charset '
                . $charset . ' on db');
        }
        if (!$db->select_db($defaultDb)) {
            throw new Exception('Failed to set default db on db ('
                    . $db->connect_errno . '): '
                    . $db->connect_error);
        }
        return $db;
    }


    /**
     * Tries to decrypt passed text: First apply the old DecryptText function
     * until the string does no longer change. Then apply html_entity_decode
     * once. If the resulted string is empty, null is returned, else the string.
     * @param string $txt
     * @return type
     */
    public function decryptAll(string $txt)
    {
        // First decode with this "special" encoding used in functions.php
        // do that until no more changes happen (see for example old post with
        // id 442380: obviously, the title gets decoded only once, what
        // results in an invalid &#27; char, but in the thread view at the end
        // things get decoded at least twice.. )
        $decrypted = $this->decryptText($txt);
        while ($decrypted !== $txt) {
            $txt = $decrypted;
            $decrypted = $this->decryptText($txt);
        }

        // and decode html_entity. we have them in author names and
        // in registration msgs
        $decoded = html_entity_decode($decrypted, ENT_COMPAT, 'UTF-8');

        if (empty($decoded)) {
            return null;
        }

        return $decoded;
    }

    /**
     * Removes all occurrences (without any replacement) of the following chars:
     * <br>
     * Replace the following occurrences:
     * <p> with \n\n
     * @param string $txt
     * @return string
     */
    public function cleanText(string $txt)
    {
        if (is_null($txt)) {
            return $txt;
        }
        $brCleaned = str_replace('<br>', '', $txt);
        $pCleaned = str_replace('<p>', "\n\n", $brCleaned);
        $anfangCleaned = str_replace('<!-- Anfang BeitragsText -->', '', $pCleaned);
        $endeCleaned = str_replace('<!-- Ende BeitragsText -->', '', $anfangCleaned);
        // and remove everything after '<!-- AnfangLinie -->'
        // usually its <!-- EndeLinie -->
        $endOfPost = strpos($endeCleaned, '<!-- AnfangLinie -->');
        if ($endOfPost !== false) {
            $endeCleaned = substr($endeCleaned, 0, $endOfPost - 1);
        }

        if (empty($endeCleaned)) {
            return null;
        }
        $imgCleaned = $this->replaceIncontentImgTag($endeCleaned);
        $urlCleaned = $this->replaceIncontentLinkTag($imgCleaned);

        return $urlCleaned;
    }

    /**
     * Replace occurrences of
     * <img src="http://www.bscyb.ch/yb-news-detail?id=15777">
     * and
     * <img src=http://www.bscyb.ch/yb-news-detail?id=15777>
     * and
     * <img>http://www.uebersteiger.de/ausgaben/28/heul2.jpg</img>
     * with the [img] tag in the format
     * [img]http://www.bscyb.ch/yb-news-detail?id=15777[/img]
     * @param string $txt
     */
    public function replaceIncontentImgTag(string $txt)
    {
        $imgRegex = '/<img\s+src="(.+)">/';
        $matches = [];
        while (preg_match($imgRegex, $txt, $matches)) {
            $newImgTag = '[img]' . $matches[1] . '[/img]';
            $txt = str_replace($matches[0], $newImgTag, $txt);
        }

        $imgRegex2 = '/<img\s+src=([^\s]+).*>/';
        while (preg_match($imgRegex2, $txt, $matches)) {
            $newImgTag = '[img]' . $matches[1] . '[/img]';
            $txt = str_replace($matches[0], $newImgTag, $txt);
        }

        $imgRegex3 = '/<img>(http:\/\/.+)<\/img>/';
        while (preg_match($imgRegex3, $txt, $matches)) {
            $newImgTag = '[img]' . $matches[1] . '[/img]';
            $txt = str_replace($matches[0], $newImgTag, $txt);
        }

        return $txt;
    }

    /**
     * Replace occurrences of
     * <a href="http://www.bscyb.ch/yb-news-detail?id=15777" target=_top>Hauen wir die Basler weg!</a>
     * or
     * <a href=http://www.bscyb.ch/yb-news-detail?id=15777 target=_top>Hauen wir die Basler weg!</a>
     * with the [url] tag in the format
     * [url=http://www.bscyb.ch/yb-news-detail?id=15777]Hauen wir die Basler weg![/url]
     * @param string $txt
     */
    public function replaceIncontentLinkTag(string $txt)
    {
        $linkRegex = '/<a\s+href="(.+)".*>(.+)<\/a>/';
        $matches = [];
        while (preg_match($linkRegex, $txt, $matches)) {
            $newUrlTag = '[url=' . $matches[1] . ']' . $matches[2] . '[/url]';
            $txt = str_replace($matches[0], $newUrlTag, $txt);
        }

        $linkRegex2 = '/<a\s+href=([^\s]+).*>(.+)<\/a>/';
        while (preg_match($linkRegex2, $txt, $matches)) {
            $newUrlTag = '[url=' . $matches[1] . ']' . $matches[2] . '[/url]';
            $txt = str_replace($matches[0], $newUrlTag, $txt);
        }

        return $txt;
    }

    /**
     * This is the old DecryptText from functions.php from the orignal
     * forum code. Note that here we use utf-8 for the symbols, not some
     * asci encoding
     * @param type $sText
     * @return type
     */
    public function decryptText($sText)
    {
        $sWrkTxt = $sText;

        $sWrkTxt = str_replace("&#x80;", "€", $sWrkTxt);
        $sWrkTxt = str_replace("&#10;", chr(10), $sWrkTxt);
        $sWrkTxt = str_replace("&#13;", chr(13), $sWrkTxt);
        $sWrkTxt = str_replace("&#42;", "*", $sWrkTxt);
        $sWrkTxt = str_replace("&#27;", "'", $sWrkTxt);
        $sWrkTxt = str_replace("&#38;", "&", $sWrkTxt);
        $sWrkTxt = str_replace("&#34;", "\"", $sWrkTxt);
        $sWrkTxt = str_replace("&#59;", ";", $sWrkTxt);

        return ($sWrkTxt);
    }

    public function throwDbException(mysqli $db, string $msg): void
    {
        $errMsg = '';
        if ($msg && !empty($msg)) {
            $errMsg = $msg;
        }
        $errMsg .= ': ' . $db->errno . ': ' . $db->error;
        throw new Exception($errMsg);
    }

    public function throwStmtException(mysqli_stmt $stmt): void
    {
        $errMsg = $stmt->errno . ': ' . $stmt->error;
        throw new Exception($errMsg);
    }

    /**
     * Check if in post_table a row with a matching old_nr exists. If one is
     * found, the idpost value is returned, 0 else.
     * @param type $oldNr
     * @return int
     */
    public function getPostId(int $oldNo)
    {
        assert($oldNo > 0);
        $query = 'SELECT idpost FROM post_table WHERE old_no = ?';
        $stmt = $this->m_destDb->prepare($query);
        if (!$stmt) {
            $this->throwDbException($this->m_destDb, null);
        }
        if (!$stmt->bind_param('i', $oldNo)) {
            $this->throwStmtException($stmt);
        }
        if (!$stmt->execute()) {
            $this->throwStmtException($stmt);
        }
        $idpost = 0;
        if (!$stmt->bind_result($idpost)) {
            $this->throwStmtException($stmt);
        }
        if ($stmt->fetch()) {
            return $idpost;
        }
        return 0;
    }

    /**
     * Searches in user_table for a row where nick matches passed $author value.
     * If such a row is found, the iduser value is returned, else 0.
     * @param type $author
     */
    public function getUserId(string $author)
    {
        assert(!empty($author));
        $query = 'SELECT iduser FROM user_table WHERE nick = ?';
        $stmt = $this->m_destDb->prepare($query);
        if (!$stmt) {
            $this->throwDbException($this->m_destDb, null);
        }
        if (!$stmt->bind_param('s', $author)) {
            $this->throwStmtException($stmt);
        }
        if (!$stmt->execute()) {
            $this->throwStmtException($stmt);
        }
        $userId = 0;
        if (!$stmt->bind_result($userId)) {
            $this->throwStmtException($stmt);
        }
        if ($stmt->fetch()) {
            return $userId;
        }
        return 0;
    }

    /**
     * Create a new entry in user_table, with passed nick. Only field set is
     * the nick field. Returned is the id of the newly created user. If
     * creating the user fails, an exception is thrown
     * @param type $author
     */
    public function createMissingUser(string $author)
    {
        echo 'Creating user entry for missing author with nick ' . $author . "\n";
        assert(!empty($author));
        $query = 'INSERT INTO user_table (nick) VALUES(?)';
        $stmt = $this->m_destDb->prepare($query);
        if (!$stmt) {
            $this->throwDbException($this->m_destDb, null);
        }
        if (!$stmt->bind_param('s', $author)) {
            $this->throwStmtException($stmt);
        }
        if (!$stmt->execute()) {
            $this->throwStmtException($stmt);
        }
        $userId = $this->m_destDb->insert_id;
        $stmt->close();
        echo 'Successfully created user entry for nick ' . $author . ' with '
                . 'iduser ' . $userId . "\n";
        return $userId;
    }

    /**
     * Searches in user_table for a user matching $author, If none is found,
     * a new user is created.
     * The id of a user matching $author is returned
     * @param string $author
     */
    public function ensureUserId(string $author)
    {
        assert(!empty($author));

        // decode author name
        $authorDecoded = $this->decryptAll($author);
        $userId = $this->getUserId($authorDecoded);
        if ($userId === 0) {
            throw new Exception('Missing user Id ' . $userId . ' but we need all users when appending');
            $userId = $this->createMissingUser($authorDecoded);
        }
        return $userId;
    }

    public function importThreads(): void
    {
        $importCount = 0;
        $importSkip = 0;

        // the entry of a thread is a post that hat no parent
        $query = 'SELECT no, thread '
                . 'FROM forum_forum '
                . 'WHERE preno = 0 AND no >= 1080532 ORDER BY no ASC';
        $stmt = $this->m_sourceDb->prepare($query);
        $stmt->execute();
        $stmt->store_result();
        $no = 0;
        $thread = 0;
        $stmt->bind_result($no, $thread);
        while ($stmt->fetch()) {
            /*if($no !== 661747)
            {
                continue;
            }*/
            echo 'Import Thread ' . $no . ' with root post no ' . $no . "\n";
            $threadId = $this->importThread($no, $thread);
            if ($threadId > 0) {
                echo ' Succesfully importeded with new ThreadId ' . $threadId . "\n";
                $importCount++;
            } else {
                echo ' Failed to import ' . "\n";
                $importSkip++;
            }
            /*if($importCount >= 100)
            {
                break;
            }*/
        }
        echo 'Imported ' . $importCount . ' Threads' . "\n";
        echo 'Skipped ' . $importSkip . ' Threads' . "\n";
    }

    public function importThread(int $oldRootPostNo, int $oldThreadNo)
    {
        assert($oldRootPostNo > 0);
        assert($oldThreadNo >= 0);

        // check that we have not imported that post-nr yet
        if ($this->getPostId($oldRootPostNo)) {
            echo ' A post entry with old_no value ' . $oldRootPostNo
                    . ' already exists' . "\n";
            return 0;
        }

        // Create the thread entry in our thread_table
        $createThreadQuery = 'INSERT INTO thread_table '
                . '(old_threadno, old_rootpostno) '
                . 'VALUES(?, ?)';
        $createThreadStmt = $this->m_destDb->prepare($createThreadQuery);
        $createThreadStmt->bind_param('ii', $oldThreadNo, $oldRootPostNo);
        $createThreadStmt->execute();
        $newThreadId = $this->m_destDb->insert_id;
        $createThreadStmt->close();
        echo ' Created entry in thread_table with idthread ' . $newThreadId
                . "\n";

        $newPostId = $this->importRootPost($oldRootPostNo, $newThreadId);

        // and walk and import all children
        $this->walkAndImportChildren($oldRootPostNo, $newPostId);

        return $newThreadId;
    }

    public function readOldPostEntry(int $oldPostNo)
    {
        assert($oldPostNo > 0);

        $oldPostQuery = 'SELECT no, preno, author, email, regular, date, '
                . 'time, picurl, homeurl, homename, subject, del, tclose, '
                . 'ptext, ip '
                . 'FROM forum_forum '
                . 'WHERE no = ' . $oldPostNo;

        $result = $this->m_sourceDb->query($oldPostQuery);
        if (!$result) {
            return null;
        }
        $resObject = $result->fetch_object();
        $result->close();
        return $resObject;
    }

    public function formatDatetimeString(string $oldPostDate, string $oldPostTime)
    {
        assert(!empty($oldPostDate));
        assert(!empty($oldPostTime));
        $dateParts = explode('.', $oldPostDate);
        $newDate = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
        $formatedDatetime = $newDate . ' ' . $oldPostTime;
        return $formatedDatetime;
    }


    public function importRootPost(int $oldPostNr, int $newThreadId)
    {
        assert($oldPostNr > 0);
        assert($newThreadId > 0);

        echo '  Importing root post with old_no ' . $oldPostNr . ' into thread '
                . $newThreadId . ".. ";

        // Read the existing data into an object where columns match props
        $oldPostData = $this->readOldPostEntry($oldPostNr);

        // Build all properties required to import
        $userId = $this->ensureUserId($oldPostData->author);
        $title = $this->decryptAll($oldPostData->subject);
        if (is_null($title)) {
            $title = 'Empty Title';
        }
        $content = $oldPostData->ptext;
        if (!is_null($content)) {
            $content = $this->decryptAll($content);
            if (!is_null($content)) {
                $content = $this->cleanText($content);
            }
        }
        $creation = $this->formatDatetimeString($oldPostData->date, $oldPostData->time);
        $email = $this->decryptAll($oldPostData->email);
        $url = $this->decryptAll($oldPostData->homeurl);
        $urlText = $this->decryptAll($oldPostData->homename);
        $urlImg = $this->decryptAll($oldPostData->picurl);
        $remoteAddr = $this->decryptAll($oldPostData->ip);
        $del = $oldPostData->del;
        if (is_null($remoteAddr)) {
            $remoteAddr = '127.0.0.1';
        }
        $hidden = 0;
        if ($del == 'X' || $del == '1') {
            $hidden = 1;
        }
        $createRootPostQuery = 'INSERT INTO post_table '
                . '(idthread, parent_idpost, iduser, title, content, '
                . '`rank`, indent, creation_ts, email, '
                . 'link_url, link_text, img_url, '
                . 'ip_address, old_no, hidden) '
                . 'VALUES(?, NULL, ?, ?, ?, 1, 0, ?, ?, ?, ?, ?, ?, ?, ?)';
        $createRootPostStmt = $this->m_destDb->prepare($createRootPostQuery);
        $createRootPostStmt->bind_param(
            'iissssssssii',
            $newThreadId,
            $userId,
            $title,
            $content,
            $creation,
            $email,
            $url,
            $urlText,
            $urlImg,
            $remoteAddr,
            $oldPostNr,
            $hidden
        );
        $createRootPostStmt->execute();
        $newPostId = $this->m_destDb->insert_id;
        $createRootPostStmt->close();

        echo 'Done, new postId is ' . $newPostId . "\n";

        return $newPostId;
    }

    public function walkAndImportChildren(int $oldParentPostNo, int $newParentPostId): void
    {
        assert($oldParentPostNo > 0);
        assert($newParentPostId > 0);

        // get all children of this old post no
        $oldPostChildrenQuery = 'SELECT no, preno, author, email, regular, date, '
                . 'time, picurl, homeurl, homename, subject, del, tclose, '
                . 'ptext, ip '
                . 'FROM forum_forum WHERE preno = ' . $oldParentPostNo . ' '
                . 'ORDER BY no ASC';

        $result = $this->m_sourceDb->query($oldPostChildrenQuery);
        if (!$result) {
            $this->throwDbException($this->m_sourceDb, null);
        }
        while ($oldPostData = $result->fetch_object()) {
            // Import this old post
            $no = $oldPostData->no;
            echo '  Importing post with old_no ' . $no . ' as child of '
                . $newParentPostId . ".. ";
            $userId = $this->ensureUserId($oldPostData->author);
            $title = $this->decryptAll($oldPostData->subject);
            if (is_null($title)) {
                $title = 'Empty Title';
            }
            $content = $oldPostData->ptext;
            if (!is_null($content)) {
                $content = $this->decryptAll($content);
                if (!is_null($content)) {
                    $content = $this->cleanText($content);
                }
            }
            $creation = $this->formatDatetimeString($oldPostData->date, $oldPostData->time);
            $email = $this->decryptAll($oldPostData->email);
            $linkUrl = $this->decryptAll($oldPostData->homeurl);
            $linkText = $this->decryptAll($oldPostData->homename);
            $imgUrl = $this->decryptAll($oldPostData->picurl);
            $ipAddress = $this->decryptAll($oldPostData->ip);
            if (is_null($ipAddress)) {
                $ipAddress = '127.0.0.1';
            }
            $del = $oldPostData->del;
            // And import throug the sp
            $createReplyQuery = 'CALL insert_reply(?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $createReplyStmt = $this->m_destDb->prepare($createReplyQuery);
            $createReplyStmt->bind_param(
                'iisssssss',
                $newParentPostId,
                $userId,
                $title,
                $content,
                $ipAddress,
                $email,
                $linkUrl,
                $linkText,
                $imgUrl
            );
            $createReplyStmt->execute();
            // Read back new id of post just created
            $newPostId = 0;
            $createReplyStmt->bind_result($newPostId);
            $createReplyStmt->fetch();
            $createReplyStmt->close();
            // And update those properties not set correctly from the sp:
            $hidden = 0;
            if ($del == 'X' || $del == '1') {
                $hidden = 1;
            }
            $updateQuery = 'UPDATE post_table SET creation_ts = ?, old_no = ?, '
                    . 'hidden = ? WHERE idpost = ?';
            $updateStmt = $this->m_destDb->prepare($updateQuery);
            $updateStmt->bind_param('siii', $creation, $no, $hidden, $newPostId);
            $updateStmt->execute();
            $updateStmt->close();

            echo 'Done, new postId is ' . $newPostId . "\n";

            // And walk the children of the just created post
            $this->walkAndImportChildren($no, $newPostId);
        }
    }

    // stuff to import users
    // =====================

    public function importUsers(): void
    {
        $importCount = 0;
        $importSkip = 0;
        $sourceQuery = 'SELECT name, passwd, email, regmsg FROM forum_regusr';
        $checkNotExistQuery = 'SELECT iduser FROM user_table WHERE nick = ?';
        $checkEmailUniqueQuery = 'SELECT iduser FROM user_table WHERE email = ?';
        $importQuery = 'INSERT INTO user_table (nick, email, registration_msg, '
                . 'old_passwd) VALUES(?, ?, ?, ?)';
        $sourceStmt = $this->m_sourceDb->prepare($sourceQuery);
        $checkNotExistStmt = $this->m_destDb->prepare($checkNotExistQuery);
        $checkEmailUniqueStmt = $this->m_destDb->prepare($checkEmailUniqueQuery);
        $importStmt = $this->m_destDb->prepare($importQuery);
        !$sourceStmt->execute();
        $sourceStmt->store_result();
        $name = '';
        $passwd = '';
        $email = '';
        $regmsg = '';
        $sourceStmt->bind_result($name, $passwd, $email, $regmsg);
        $existingId = 0;
        $existingEmailUserId = 0;
        $dupliCounter = 0;
        $checkNotExistStmt->bind_param('s', $name);
        $checkNotExistStmt->bind_result($existingId);
        $checkEmailUniqueStmt->bind_param('s', $email);
        $checkEmailUniqueStmt->bind_result($existingEmailUserId);
        $importStmt->bind_param('ssss', $name, $email, $regmsg, $passwd);
        while ($sourceStmt->fetch()) {
            $name = $this->decryptAll($name);
            $checkNotExistStmt->execute();
            if ($checkNotExistStmt->fetch()) {
                echo 'Skipping import of user ' . $name . ', such a user '
                        . 'already exists with id ' . $existingId . "\n";
                $importSkip++;
            } else {
                echo 'Need to import user ' . $name . "\n";
                if (empty(trim($passwd))) {
                    $passwd = null;
                }
                if (empty(trim($email))) {
                    $email = null;
                } else {
                    $existingEmailUserId = 0;
                    $dupliCounter = 0;
                    $origEmail = $email;
                    $checkEmailUniqueStmt->execute();
                    while ($checkEmailUniqueStmt->fetch()) {
                        $email = $origEmail . '_duplicate_' . $dupliCounter;
                        $dupliCounter++;
                        $checkEmailUniqueStmt->execute();
                    }
                }
                if (empty(trim($regmsg))) {
                    $regmsg = null;
                } else {
                    $regmsg = $this->decryptAll($regmsg);
                }
                $importStmt->execute();
                $importCount++;
            }
        }
        echo 'Imported ' . $importCount . ' Users' . "\n";
        echo 'Skipped ' . $importSkip . ' Users' . "\n";
    }



    private $m_sourceDb;
    private $m_destDb;
}
