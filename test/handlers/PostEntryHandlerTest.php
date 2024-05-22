<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../../src/handlers/PostEntryHandler.php';

/**
 * No Database stuff required
 */
final class PostEntryHandlerTest extends TestCase
{
    // required mocks our handler under test depends on
    private ForumDb $db;
    private Logger $logger;

    // our actuall handler to test
    private PostEntryHandler $peh;

    protected function setUp(): void
    {
        $this->db = $this->createMock(ForumDb::class);
        $this->logger = $this->createMock(Logger::class);
        $this->peh = new PostEntryHandler();
        $this->peh->SetLogger($this->logger);
        // dont know why we need to set this here, as it is already defined in bootstrap.php
        $_SERVER['REMOTE_ADDR'] = '13.13.13.13';
    }

    public function testConstruct()
    {
        $this->assertNull($this->peh->GetTitle());
        $this->assertNull($this->peh->GetNick());
        $this->assertNull($this->peh->GetPassword());
        $this->assertNull($this->peh->GetContent());
        $this->assertNull($this->peh->GetEmail());
        $this->assertNull($this->peh->GetLinkUrl());
        $this->assertNull($this->peh->GetLinkText());
        $this->assertNull($this->peh->GetImgUrl());
        $this->assertNull($this->peh->GetParentPostId());
        $this->assertNull($this->peh->GetNewPostId());
    }
}