<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../../src/pageparts/ThreadIndexView.php';

final class ThreadIndexViewTest extends TestCase
{
    // required stubs test depends on
    private ForumDb $db;

    protected function setUp(): void
    {
        $this->db = static::createStub(ForumDb::class);
    }

    private function mockThread1IndexEntries(): array
    {
        $postIndexEntry1 = static::createStub(PostIndexEntry::class);
        $postIndexEntry1->method('GetIndent')->willReturn(0);
        $postIndexEntry1->method('GetPostId')->willReturn(1);
        $postIndexEntry1->method('GetTitle')->willReturn('Topic');
        $postIndexEntry1->method('HasContent')->willReturn(true);
        $postIndexEntry1->method('GetNick')->willReturn('Author 1');
        $postIndexEntry1->method('GetPostTimestamp')->willReturn(new DateTime('1983-01-26 02:14:53'));

        $postIndexEntry2 = static::createStub(PostIndexEntry::class);
        $postIndexEntry1->method('GetIndent')->willReturn(1);
        $postIndexEntry1->method('GetPostId')->willReturn(2);
        $postIndexEntry1->method('GetTitle')->willReturn('Re: Topic');
        $postIndexEntry1->method('HasContent')->willReturn(false);
        $postIndexEntry1->method('GetNick')->willReturn('Author 2');
        $postIndexEntry1->method('GetPostTimestamp')->willReturn(new DateTime('1983-01-27 02:14:53'));

        return [$postIndexEntry1, $postIndexEntry2];
    }


    public function testRenderHtmlDivPerThread_shouldCallCallbackWithThreadDiv(): void
    {
        $this->db->method('LoadThreadIndexEntries')->willReturnCallback(function ($pageNr, $nrOfThreads, $dbCallback): void {
            call_user_func($dbCallback, $this->mockThread1IndexEntries());
        });

        $threadIndexView = new ThreadIndexView($this->db, 10, 3);

        $threadIndexView->renderHtmlDivPerThread(function ($html): void {
            static::assertStringContainsString('<p class="nomargin fbold" style="text-indent: 0em"><a href="showentry.php?idpost=1">Topic</a> - <span class="fbold">Author 1</span> - 26.01.1983 02:14:53</p>', $html);
            static::assertStringContainsString('<p class="nomargin fbold" style="text-indent: 0em"><a href="showentry.php?idpost=0"> (o.T.)</a> - <span class="fbold"></span> - </p>', $html);
        });
    }
}
