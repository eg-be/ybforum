<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../../src/pageparts/PostList.php';
require_once __DIR__ . '/../../src/model/PostIndexEntry.php';

final class PostListTest extends TestCase
{
    // required stubs test depends on
    private $entry1;
    private $entry2;
    private $entries;

    protected function setUp(): void
    {
        $this->entry1 = static::createStub(PostIndexEntry::class);
        $this->entry1->method('GetPostId')->willReturn(11);
        $this->entry1->method('GetTitle')->willReturn('title 01');
        $this->entry1->method('HasContent')->willReturn(true);
        $this->entry1->method('GetNick')->willReturn('nick 01');
        $this->entry1->method('GetPostTimestamp')->willReturn(DateTime::createFromFormat('d/m/Y H:i:s', '26/01/1983 02:05:13'));

        $this->entry2 = static::createStub(PostIndexEntry::class);
        $this->entry2->method('GetPostId')->willReturn(22);
        $this->entry2->method('GetTitle')->willReturn('title 02');
        $this->entry2->method('HasContent')->willReturn(true);
        $this->entry2->method('GetNick')->willReturn('nick 02');
        $this->entry2->method('GetPostTimestamp')->willReturn(DateTime::createFromFormat('d/m/Y H:i:s', '30/03/2018 14:15:10'));

        $this->entries = [$this->entry1, $this->entry2];
    }

    public function testRenderListDiv_shouldWrapIntoDiv(): void
    {
        $postList = new PostList($this->entries);

        $html = $postList->RenderListDiv();

        static::assertMatchesRegularExpression('/<div class="fullwidth">.+<\/div>/', $html);
    }

    public function testRenderListDiv_shouldWrapIntoEmptyDiv(): void
    {
        $postList = new PostList([]);

        $html = $postList->RenderListDiv();

        static::assertStringContainsString('<div class="fullwidth"></div>', $html);
    }

    public function testRenderListDiv_shouldLinkToPostDisplayingTitle(): void
    {
        $postList = new PostList($this->entries);

        $html = $postList->RenderListDiv();

        static::assertStringContainsString('<a href="showentry.php?idpost=11">title 01</a>', $html);
        static::assertStringContainsString('<a href="showentry.php?idpost=22">title 02</a>', $html);
    }

    public function testRenderListDiv_shouldLinkToPostDisplayingNoContentTitle(): void
    {
        $entryNoContent = static::createStub(PostIndexEntry::class);
        $entryNoContent->method('GetPostId')->willReturn(11);
        $entryNoContent->method('GetTitle')->willReturn('title 01');
        $entryNoContent->method('HasContent')->willReturn(false);
        $entryNoContent->method('GetNick')->willReturn('nick 01');
        $entryNoContent->method('GetPostTimestamp')->willReturn(DateTime::createFromFormat('d/m/Y', '26/01/1983'));
        $postList = new PostList([$entryNoContent]);

        $html = $postList->RenderListDiv();

        static::assertStringContainsString('<a href="showentry.php?idpost=11">title 01 (o.T.)</a>', $html);
    }

    public function testRenderListDiv_shouldDisplayNick(): void
    {
        $postList = new PostList($this->entries);

        $html = $postList->RenderListDiv();

        static::assertStringContainsString('</a> - <span class="fbold">nick 01</span>', $html);
        static::assertStringContainsString('</a> - <span class="fbold">nick 02</span>', $html);
    }

    public function testRenderListDiv_shouldDisplayPostTimestamp(): void
    {
        $postList = new PostList($this->entries);

        $html = $postList->RenderListDiv();

        static::assertStringContainsString('26.01.1983 02:05:13', $html);
        static::assertStringContainsString('30.03.2018 14:15:10', $html);
    }


    public function testRenderListDiv_shouldCreatePElementForEveryEntry(): void
    {
        $postList = new PostList($this->entries);

        $html = $postList->RenderListDiv();

        static::assertStringContainsString('<p class="nomargin"><a href="showentry.php?idpost=11">title 01</a>', $html);
        static::assertStringContainsString('<p class="nomargin"><a href="showentry.php?idpost=22">title 02</a>', $html);
    }
}
