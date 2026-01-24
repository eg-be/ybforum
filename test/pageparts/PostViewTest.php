<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../../src/pageparts/PostView.php';

final class PostViewTest extends TestCase
{
    // required stubs test depends on
    private $db;
    private $post;

    protected function setUp(): void
    {
        $this->db = static::createStub(ForumDb::class);
        $this->post = static::createStub(Post::class);
    }

    public function testRenderHtmlTitleDivContent_shouldIncludePostTitleForPostWithContent(): void
    {
        $this->post->method('GetTitle')->willReturn('The-Title');
        $this->post->method('HasContent')->willReturn(true);
        $postView = new PostView($this->db, $this->post, null);

        $html = $postView->renderHtmlTitleDivContent();

        static::assertStringContainsString('<div class="fullwidthcenter generictitle">The-Title</div>', $html);
    }

    public function testRenderHtmlTitleDivContent_shouldIncludePostTitleForPostWithoutContent(): void
    {
        $this->post->method('GetTitle')->willReturn('The-Title');
        $this->post->method('HasContent')->willReturn(false);
        $postView = new PostView($this->db, $this->post, null);

        $html = $postView->renderHtmlTitleDivContent();

        static::assertStringContainsString('<div class="fullwidthcenter generictitle">The-Title (o.T.)</div>', $html);
    }

    public function testRenderHtmlTitleDivContent_shouldHtmlEncodeSpecialChars(): void
    {
        $this->post->method('GetTitle')->willReturn('&&&');
        $this->post->method('HasContent')->willReturn(true);
        $postView = new PostView($this->db, $this->post, null);

        $html = $postView->renderHtmlTitleDivContent();

        static::assertStringContainsString('<div class="fullwidthcenter generictitle">&amp;&amp;&amp;</div>', $html);
    }

    public function testRenderHtmlTitleDivContent_shouldIncludeNickOfAuthor(): void
    {
        $this->post->method('GetNick')->willReturn('The-Author');
        $postView = new PostView($this->db, $this->post, null);

        $html = $postView->renderHtmlTitleDivContent();

        static::assertStringContainsString('<div>geschrieben von <span id="postnick">The-Author</span>', $html);
    }

    public function testRenderHtmlTitleDivContent_shouldHtmlEncodeNickOfAuthor(): void
    {
        $this->post->method('GetNick')->willReturn('The-&&&');
        $postView = new PostView($this->db, $this->post, null);

        $html = $postView->renderHtmlTitleDivContent();

        static::assertStringContainsString('<div>geschrieben von <span id="postnick">The-&amp;&amp;&amp;</span>', $html);
    }

    public function testRenderHtmlTitleDivContent_shouldIncludeFormatedTimestamp(): void
    {
        $this->post->method('GetPostTimestamp')->willReturn(new DateTime('1983-01-26 02:14:53'));
        $postView = new PostView($this->db, $this->post, null);

        $html = $postView->renderHtmlTitleDivContent();

        static::assertStringContainsString('am 26.01.1983 um 02:14:53', $html);
    }

    public function testRenderHtmlTitleDivContent_shouldIncludeLinkToParentPost(): void
    {
        $parentPost = static::createStub(Post::class);
        $parentPost->method('GetId')->willReturn(102);
        $parentPost->method('GetTitle')->willReturn('Parent-Post-Title');
        $parentPost->method('GetNick')->willReturn('Parent-Post-Nick');

        $postView = new PostView($this->db, $this->post, $parentPost);

        $html = $postView->renderHtmlTitleDivContent();

        static::assertStringContainsString('als Antwort auf: <a class="fbold" href="showentry.php?idpost=102">Parent-Post-Title</a> von Parent-Post-Nick', $html);
    }

    public function testRenderHtmlTitleDivContent_shouldNotIncludeLinkToParentPost(): void
    {
        $postView = new PostView($this->db, $this->post, null);

        $html = $postView->renderHtmlTitleDivContent();

        static::assertStringNotContainsString('<a class="fbold" href="showentry.php?idpost=', $html);
    }

    public function testRenderHtmlTitleDivContent_shouldHtmlspecialcharsParentPostProperties(): void
    {
        $parentPost = static::createStub(Post::class);
        $parentPost->method('GetId')->willReturn(102);
        $parentPost->method('GetTitle')->willReturn('Parent-Post&');
        $parentPost->method('GetNick')->willReturn('Parent-Nick&');

        $postView = new PostView($this->db, $this->post, $parentPost);

        $html = $postView->renderHtmlTitleDivContent();

        static::assertStringContainsString('als Antwort auf: <a class="fbold" href="showentry.php?idpost=102">Parent-Post&amp;</a> von Parent-Nick&amp;', $html);
    }

    public function testRenderHtmlPostContentDiv_shouldContainextraData(): void
    {
        $this->post->method('IsOldPost')->willReturn(true);
        $this->post->method('GetOldPostNo')->willReturn(101);
        $this->post->method('HasImgUrl')->willReturn(true);
        $this->post->method('GetImgUrl')->willReturn('img-url-value');
        $this->post->method('HasLinkUrl')->willReturn(true);
        $this->post->method('GetLinkUrl')->willReturn('link-url-value');
        $this->post->method('HasLinkText')->willReturn(true);
        $this->post->method('GetLinkText')->willReturn('link-text-value');
        $this->post->method('HasEmail')->willReturn(true);
        $this->post->method('GetEmail')->willReturn('email-value');

        $postView = new PostView($this->db, $this->post, null);

        $html = $postView->renderHtmlPostContentDivContent();
        static::assertStringContainsString('data-oldno="101"', $html);
        static::assertStringContainsString('data-imgurl="img-url-value"', $html);
        static::assertStringContainsString('data-linkurl="link-url-value"', $html);
        static::assertStringContainsString('data-linktext="link-text-value"', $html);
        static::assertStringContainsString('data-email="email-value"', $html);
    }

    public function testRenderHtmlPostContentDiv_shouldContainextraDataHtmlspecialchars(): void
    {
        $this->post->method('HasImgUrl')->willReturn(true);
        $this->post->method('GetImgUrl')->willReturn('img-url-value&');
        $this->post->method('HasLinkUrl')->willReturn(true);
        $this->post->method('GetLinkUrl')->willReturn('link-url-value&');
        $this->post->method('HasLinkText')->willReturn(true);
        $this->post->method('GetLinkText')->willReturn('link-text-value&');
        $this->post->method('HasEmail')->willReturn(true);
        $this->post->method('GetEmail')->willReturn('email-value&');

        $postView = new PostView($this->db, $this->post, null);

        $html = $postView->renderHtmlPostContentDivContent();
        static::assertStringContainsString('data-imgurl="img-url-value&amp;"', $html);
        static::assertStringContainsString('data-linkurl="link-url-value&amp;"', $html);
        static::assertStringContainsString('data-linktext="link-text-value&amp;"', $html);
        static::assertStringContainsString('data-email="email-value&amp;"', $html);
    }

    public function testRenderHtmlPostContentDiv_shouldContainContent(): void
    {
        $this->post->method('HasContent')->willReturn(true);
        $this->post->method('GetContent')->willReturn('the-post-content');

        $postView = new PostView($this->db, $this->post, null);

        $html = $postView->renderHtmlPostContentDivContent();
        static::assertStringContainsString('<div id="postcontent" class="postcontent">the-post-content</div>', $html);
    }

    public function testRenderHtmlPostContentDiv_shouldContainContentHtmlspecialchars(): void
    {
        $this->post->method('HasContent')->willReturn(true);
        $this->post->method('GetContent')->willReturn('the-post-content&&&');

        $postView = new PostView($this->db, $this->post, null);

        $html = $postView->renderHtmlPostContentDivContent();
        static::assertStringContainsString('<div id="postcontent" class="postcontent">the-post-content&amp;&amp;&amp;</div>', $html);
    }

    public function testRenderHtmlPostContentDiv_shouldContainNoContent(): void
    {
        $this->post->method('HasContent')->willReturn(false);

        $postView = new PostView($this->db, $this->post, null);

        $html = $postView->renderHtmlPostContentDivContent();
        static::assertStringContainsString('<div id="postcontent" class="nocontent fullwidthcenter">Dieser Eintrag hat keinen Text!</div>', $html);
    }

    public function testRenderHtmlThreadDivContent_shouldReturnEmptyString(): void
    {
        $this->post->method('IsHidden')->willReturn(true);

        $postView = new PostView($this->db, $this->post, null);

        $html = $postView->renderHtmlThreadDivContent();

        static::assertEmpty($html);
    }

    public function testRenderHtmlThreadDivContent_shouldContainReplyWithContent(): void
    {
        $this->post->method('IsHidden')->willReturn(false);
        $this->post->method('GetIndent')->willReturn(5);

        $reply = static::createStub(PostIndexEntry::class);
        $reply->method('GetIndent')->willReturn(6);
        $reply->method('GetPostId')->willReturn(1313);
        $reply->method('GetTitle')->willReturn('Reply-Title');
        $reply->method('HasContent')->willReturn(true);
        $reply->method('GetNick')->willReturn('Reply-Nick');
        $reply->method('GetPostTimestamp')->willReturn(new DateTime('1983-01-26 02:14:53'));

        $this->db->method('LoadPostReplies')->willReturn([$reply]);


        $postView = new PostView($this->db, $this->post, null);

        $html = $postView->renderHtmlThreadDivContent();

        static::assertStringContainsString('<p class="nomargin" style="text-indent: 0em"><a href="showentry.php?idpost=1313">Reply-Title</a> - <span class="fbold">Reply-Nick</span> - 26.01.1983 02:14:53</p>', $html);
    }

    public function testRenderHtmlThreadDivContent_shouldContainReplyWithoutContent(): void
    {
        $this->post->method('IsHidden')->willReturn(false);
        $this->post->method('GetIndent')->willReturn(5);

        $reply = static::createStub(PostIndexEntry::class);
        $reply->method('GetIndent')->willReturn(6);
        $reply->method('GetPostId')->willReturn(1313);
        $reply->method('GetTitle')->willReturn('Reply-Title');
        $reply->method('HasContent')->willReturn(false);
        $reply->method('GetNick')->willReturn('Reply-Nick');
        $reply->method('GetPostTimestamp')->willReturn(new DateTime('1983-01-26 02:14:53'));

        $this->db->method('LoadPostReplies')->willReturn([$reply]);


        $postView = new PostView($this->db, $this->post, null);

        $html = $postView->renderHtmlThreadDivContent();

        static::assertStringContainsString('<p class="nomargin" style="text-indent: 0em"><a href="showentry.php?idpost=1313">Reply-Title (o.T.)</a> - <span class="fbold">Reply-Nick</span> - 26.01.1983 02:14:53</p>', $html);
    }

}
