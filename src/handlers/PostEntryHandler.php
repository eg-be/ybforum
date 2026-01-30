<?php

declare(strict_types=1);

/**
 * Copyright 2017 Elias Gerber <eg@zame.ch>
 *
 * This file is part of YbForum1898.
 *
 * YbForum1898 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * YbForum1898 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with YbForum1898.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once __DIR__ . '/BaseHandler.php';
require_once __DIR__ . '/../model/ForumDb.php';
require_once __DIR__ . '/../helpers/Logger.php';
require_once __DIR__ . '/../helpers/ConfigWrapper.php';

/**
 * Read all values required to post a new entry, either as an answer or
 * as a new thread.
 *
 * @author Elias Gerber
 */
class PostEntryHandler extends BaseHandler
{
    public const PARAM_PARENTPOSTID = 'post_parentpostid';
    public const PARAM_TITLE = 'post_title';
    public const PARAM_NICK = 'post_nick';
    public const PARAM_PASS = 'post_pass';
    public const PARAM_EMAIL = 'post_email';
    public const PARAM_CONTENT = 'post_content';
    public const PARAM_LINKURL = 'post_linkurl';
    public const PARAM_LINKTEXT = 'post_linktext';
    public const PARAM_IMGURL = 'post_imgurl';


    public const MSG_AUTH_FAIL = 'Ungültiger Stammpostername / Passwort';
    public const MSG_AUTH_FAIL_PASSWORD_INVALID = 'Ungültiges Passwort';
    public const MSG_AUTH_FAIL_NO_SUCH_USER = 'Unbekannter Stammposter';
    public const MSG_AUTH_FAIL_USER_IS_INACTIVE = 'Stammposter ist nicht aktiv';
    public const MSG_AUTH_FAIL_USER_IS_DUMMY = 'Stammposter ist ein Dummy';
    public const MSG_MIGRATION_REQUIRED = 'MigrationRequired';
    public const MSG_TITLE_TOO_SHORT = 'Betreff muss mindestens '
        . YbForumConfig::MIN_TITLE_LENGTH . ' Zeichen enthalten';

    public function __construct()
    {
        parent::__construct();

        $this->logger = null;
        $this->config = ConfigWrapper::getInstance();

        // Set defaults explicitly
        $this->parentPostId = null;
        $this->nick = null;
        $this->password = null;
        $this->title = null;
        $this->content = null;
        $this->email = null;
        $this->linkUrl = null;
        $this->linkText = null;
        $this->imgUrl = null;
        $this->newPostId = null;
    }

    protected function readParams(): void
    {
        $this->parentPostId = self::readIntParam(self::PARAM_PARENTPOSTID);
        $this->nick = self::readStringParam(self::PARAM_NICK);
        $this->password = self::readStringParam(self::PARAM_PASS);
        $this->title = self::readStringParam(self::PARAM_TITLE);
        $this->content = self::readStringParam(self::PARAM_CONTENT);
        // Read optional values as plain-text and validate them later
        // so that we can send them back to the user on failure
        $this->email = self::readStringParam(self::PARAM_EMAIL);
        $this->linkUrl = self::readStringParam(self::PARAM_LINKURL);
        $this->linkText = self::readStringParam(self::PARAM_LINKTEXT);
        $this->imgUrl = self::readStringParam(self::PARAM_IMGURL);
    }

    protected function validateParams(): void
    {
        // validate what we cannot accept null values for:
        self::validateIntParam($this->parentPostId, parent::MSG_GENERIC_INVALID);
        self::validateStringParam($this->nick, self::MSG_AUTH_FAIL);
        self::validateStringParam($this->password, self::MSG_AUTH_FAIL);
        self::validateStringParam($this->title, self::MSG_TITLE_TOO_SHORT, YbForumConfig::MIN_TITLE_LENGTH);

        // If the user passed an optional value that does not meet the specs,
        // notify the user (instead of discarding silently)
        if ($this->email) {
            self::validateEmailValue($this->email, 'Der Wert ' . $this->email
                    . ' ist keine gültige Mailadresse.');
        }
        if ($this->linkUrl) {
            self::validateHttpUrlValue($this->linkUrl, 'Der Wert ' . $this->linkUrl
                    . ' ist kein gültiger Link. Links müssen mit https://'
                    . ' (oder http://) beginnen.');
        }
        if (($this->linkUrl && !$this->linkText) || ($this->linkText && !$this->linkUrl)) {
            throw new InvalidArgumentException(
                'Wird ein URL Link angegeben '
                    . 'muss auch ein Linktext angegeben werden (und umgekehrt).',
                self::MSGCODE_BAD_PARAM
            );
        }
        if ($this->imgUrl) {
            self::validateHttpUrlValue($this->imgUrl, 'Der Wert ' . $this->imgUrl
                    . ' ist keine gültige Bild URL. Bild URLs müssen mit https://'
                    . ' (oder http://) beginnen und auf eine Bilddatei'
                    . ' verweisen', true);
        }
    }

    /**
     * Build a message containing all values of this post
     * @return string
     */
    private function getExtendedLogMsg(): string
    {
        $extMsg = 'Title: ' . $this->title;
        if ($this->content) {
            $extMsg .= '; Content: ' . $this->content;
        }
        if ($this->email) {
            $extMsg .= '; Email: ' . $this->email;
        }
        if ($this->linkUrl) {
            $extMsg .= '; LinkUrl: ' . $this->linkUrl;
        }
        if ($this->linkText) {
            $extMsg .= '; LinkText: ' . $this->linkText;
        }
        if ($this->imgUrl) {
            $extMsg .= '; ImgUrl: ' . $this->imgUrl;
        }
        return $extMsg;
    }


    protected function handleRequestImpl(ForumDb $db): void
    {
        if (is_null($this->logger)) {
            $this->logger = new Logger($db);
        }
        // reset internal values
        $this->newPostId = null;
        // Authenticate
        // note: The AuthUser of the db will do logging in case of failure
        $userAndAuthFailReason = $db->authUser2($this->nick, $this->password);
        $user = $userAndAuthFailReason[ForumDb::USER_KEY];
        $authFailReason = $userAndAuthFailReason[ForumDb::AUTH_FAIL_REASON_KEY];
        if (!$user) {
            // determine a verbose reason for the auth-fail (which is
            // used in the exception thrown)
            $authFailMsg = self::MSG_AUTH_FAIL;
            if ($authFailReason === ForumDb::AUTH_FAIL_REASON_PASSWORD_INVALID) {
                $authFailMsg = self::MSG_AUTH_FAIL_PASSWORD_INVALID;
            } elseif ($authFailReason === ForumDb::AUTH_FAIL_REASON_NO_SUCH_USER) {
                $authFailMsg  = self::MSG_AUTH_FAIL_NO_SUCH_USER;
            } elseif ($authFailReason === ForumDb::AUTH_FAIL_REASON_USER_IS_INACTIVE) {
                $authFailMsg = self::MSG_AUTH_FAIL_USER_IS_INACTIVE;
            } elseif ($authFailReason === ForumDb::AUTH_FAIL_REASON_USER_IS_DUMMY) {
                $authFailMsg = self::MSG_AUTH_FAIL_USER_IS_DUMMY;
            }

            // Maybe log the data of the post that has been discarded
            if ($this->config->getLogExtendedPostDataOnAuthFailure()) {
                // but if the reason is AUTH_FAIL_REASON_NO_SUCH_USER, only
                // log if explicitely configured to do so
                if ($authFailReason !== ForumDb::AUTH_FAIL_REASON_NO_SUCH_USER || $this->config->getLogAuthFailNoSuchUser()) {
                    $this->logger->logMessage(
                        LogType::LOG_EXT_POST_DISCARDED,
                        $authFailMsg,
                        $this->getExtendedLogMsg()
                    );
                }
            }

            throw new InvalidArgumentException($authFailMsg, parent::MSGCODE_AUTH_FAIL);
        }
        // Check if migration is required
        if ($user->needsMigration()) {
            $this->logger->logMessageWithUserId(LogType::LOG_OPERATION_FAILED_MIGRATION_REQUIRED, $user);
            throw new InvalidArgumentException(self::MSG_MIGRATION_REQUIRED, parent::MSGCODE_AUTH_FAIL);
        }
        if ($this->parentPostId === 0) {
            $this->newPostId = $db->createThread(
                $user,
                $this->title,
                $this->content,
                $this->email,
                $this->linkUrl,
                $this->linkText,
                $this->imgUrl,
                $this->clientIpAddress
            );
        } else {
            $this->newPostId = $db->createReplay(
                $this->parentPostId,
                $user,
                $this->title,
                $this->content,
                $this->email,
                $this->linkUrl,
                $this->linkText,
                $this->imgUrl,
                $this->clientIpAddress
            );
        }
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getNick(): ?string
    {
        return $this->nick;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getLinkUrl(): ?string
    {
        return $this->linkUrl;
    }

    public function getLinkText(): ?string
    {
        return $this->linkText;
    }

    public function getImgUrl(): ?string
    {
        return $this->imgUrl;
    }

    public function getParentPostId(): ?int
    {
        return $this->parentPostId;
    }

    public function getNewPostId(): ?int
    {
        return $this->newPostId;
    }

    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }

    public function setConfigWrapper(ConfigWrapper $config): void
    {
        $this->config = $config;
    }

    private ?Logger $logger;
    private ?ConfigWrapper $config;

    private ?int $parentPostId;
    private ?string $title;
    private ?string $nick;
    private ?string $password;
    private ?string $content;
    private ?string $email;
    private ?string $linkUrl;
    private ?string $linkText;
    private ?string $imgUrl;

    private ?int $newPostId;    ///< Set once HandleRequestImpl has executed successfully
}
