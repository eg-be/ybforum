<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../../src/pageparts/PostEntryForm.php';

final class PostEntryFormTest extends TestCase
{
    // required stubs test depends on

    // our actuall PostEntryForm to test
    //    private PostEntryForm $$postEnt;

    protected function setUp(): void
    {
        //        $this->migrateUserForm = new MigrateUserForm('initial-nick', 'initial-email@foobar.com', 'source-location');
    }

    public function testRenderHtmlForm_shouldDisplayValuesFromPassedHandler(): void
    {
        $postEntryHandler = static::createStub(PostEntryHandler::class);
        $postEntryHandler->method('GetImgUrl')->willReturn('img-url-from-handler');
        $postEntryHandler->method('GetLinkText')->willReturn('link-text-from-handler');
        $postEntryHandler->method('GetLinkUrl')->willReturn('link-url-from-handler');
        $postEntryHandler->method('GetTitle')->willReturn('title-from-handler');
        $postEntryHandler->method('GetContent')->willReturn('content-from-handler');
        $postEntryHandler->method('GetNick')->willReturn('nick-from-handler');
        $postEntryHandler->method('GetPassword')->willReturn('password-from-handler');
        $postEntryHandler->method('GetEmail')->willReturn('email-from-handler');


        $postEntryForm = new PostEntryForm(null, $postEntryHandler);
        $html = $postEntryForm->renderHtmlForm();

        static::assertMatchesRegularExpression('/<input type="text".+value="nick-from-handler".+name="' . PostEntryHandler::PARAM_NICK . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="password".+value="password-from-handler".+name="' . PostEntryHandler::PARAM_PASS . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="email-from-handler".+name="' . PostEntryHandler::PARAM_EMAIL . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="title-from-handler".+name="' . PostEntryHandler::PARAM_TITLE . '".+>/', $html);
        static::assertMatchesRegularExpression('/<textarea name="' . PostEntryHandler::PARAM_CONTENT . '".+>content-from-handler<\/textarea>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="link-url-from-handler".+name="' . PostEntryHandler::PARAM_LINKURL . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="link-text-from-handler".+name="' . PostEntryHandler::PARAM_LINKTEXT . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="img-url-from-handler".+name="' . PostEntryHandler::PARAM_IMGURL . '".+>/', $html);
    }

    public function testRenderHtmlForm_shouldDisplayValuesFromParentPost(): void
    {
        $parentPost = static::createStub(Post::class);
        $parentPost->method('HasImgUrl')->willReturn(true);
        $parentPost->method('GetImgUrl')->willReturn('img-url-from-parent-post');
        $parentPost->method('HasLinkText')->willReturn(true);
        $parentPost->method('GetLinkText')->willReturn('link-text-from-parent-post');
        $parentPost->method('HasLinkUrl')->willReturn(true);
        $parentPost->method('GetLinkUrl')->willReturn('link-url-from-parent-post');
        $parentPost->method('GetTitle')->willReturn('title-from-parent-post');
        $parentPost->method('HasContent')->willReturn(true);
        $parentPost->method('GetContent')->willReturn('content-from-parent-post');

        $postEntryForm = new PostEntryForm($parentPost, null);
        $html = $postEntryForm->renderHtmlForm();

        static::assertMatchesRegularExpression('/<input type="text".+value="Re: title-from-parent-post".+name="' . PostEntryHandler::PARAM_TITLE . '".+>/', $html);
        static::assertMatchesRegularExpression('/<textarea name="' . PostEntryHandler::PARAM_CONTENT . '".+>\[i\]content-from-parent-post\[\/i\]<\/textarea>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="link-url-from-parent-post".+name="' . PostEntryHandler::PARAM_LINKURL . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="link-text-from-parent-post".+name="' . PostEntryHandler::PARAM_LINKTEXT . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="img-url-from-parent-post".+name="' . PostEntryHandler::PARAM_IMGURL . '".+>/', $html);
    }

    public function testRenderHtmlForm_shouldDisplayValuesFromPassedHandlerNotFromPassedPost(): void
    {
        // values from handler have precedence
        $postEntryHandler = static::createStub(PostEntryHandler::class);
        $postEntryHandler->method('GetImgUrl')->willReturn('img-url-from-handler');
        $postEntryHandler->method('GetLinkText')->willReturn('link-text-from-handler');
        $postEntryHandler->method('GetLinkUrl')->willReturn('link-url-from-handler');
        $postEntryHandler->method('GetTitle')->willReturn('title-from-handler');
        $postEntryHandler->method('GetContent')->willReturn('content-from-handler');
        $postEntryHandler->method('GetNick')->willReturn('nick-from-handler');
        $postEntryHandler->method('GetPassword')->willReturn('password-from-handler');
        $postEntryHandler->method('GetEmail')->willReturn('email-from-handler');

        $parentPost = static::createStub(Post::class);
        $parentPost->method('HasImgUrl')->willReturn(true);
        $parentPost->method('GetImgUrl')->willReturn('img-url-from-parent-post');
        $parentPost->method('HasLinkText')->willReturn(true);
        $parentPost->method('GetLinkText')->willReturn('link-text-from-parent-post');
        $parentPost->method('HasLinkUrl')->willReturn(true);
        $parentPost->method('GetLinkUrl')->willReturn('link-url-from-parent-post');
        $parentPost->method('GetTitle')->willReturn('title-from-parent-post');
        $parentPost->method('HasContent')->willReturn(true);
        $parentPost->method('GetContent')->willReturn('content-from-parent-post');

        $postEntryForm = new PostEntryForm($parentPost, $postEntryHandler);
        $html = $postEntryForm->renderHtmlForm();

        static::assertMatchesRegularExpression('/<input type="text".+value="nick-from-handler".+name="' . PostEntryHandler::PARAM_NICK . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="password".+value="password-from-handler".+name="' . PostEntryHandler::PARAM_PASS . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="email-from-handler".+name="' . PostEntryHandler::PARAM_EMAIL . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="title-from-handler".+name="' . PostEntryHandler::PARAM_TITLE . '".+>/', $html);
        static::assertMatchesRegularExpression('/<textarea name="' . PostEntryHandler::PARAM_CONTENT . '".+>content-from-handler<\/textarea>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="link-url-from-handler".+name="' . PostEntryHandler::PARAM_LINKURL . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="link-text-from-handler".+name="' . PostEntryHandler::PARAM_LINKTEXT . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="img-url-from-handler".+name="' . PostEntryHandler::PARAM_IMGURL . '".+>/', $html);
    }

    public function testRenderHtmlForm_shouldHtmlspecialcharsValuesFromHandler(): void
    {
        $postEntryHandler = static::createStub(PostEntryHandler::class);
        $postEntryHandler->method('GetImgUrl')->willReturn('img-url-&<>');
        $postEntryHandler->method('GetLinkText')->willReturn('link-text-&<>');
        $postEntryHandler->method('GetLinkUrl')->willReturn('link-url-&<>');
        $postEntryHandler->method('GetTitle')->willReturn('title-&<>');
        $postEntryHandler->method('GetContent')->willReturn('content-&<>');
        $postEntryHandler->method('GetNick')->willReturn('nick-&<>');
        $postEntryHandler->method('GetPassword')->willReturn('password-&<>');
        $postEntryHandler->method('GetEmail')->willReturn('email-&<>');


        $postEntryForm = new PostEntryForm(null, $postEntryHandler);
        $html = $postEntryForm->renderHtmlForm();

        static::assertMatchesRegularExpression('/<input type="text".+value="nick-&amp;&lt;&gt;".+name="' . PostEntryHandler::PARAM_NICK . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="password".+value="password-&amp;&lt;&gt;".+name="' . PostEntryHandler::PARAM_PASS . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="email-&amp;&lt;&gt;".+name="' . PostEntryHandler::PARAM_EMAIL . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="title-&amp;&lt;&gt;".+name="' . PostEntryHandler::PARAM_TITLE . '".+>/', $html);
        static::assertMatchesRegularExpression('/<textarea name="' . PostEntryHandler::PARAM_CONTENT . '".+>content-&amp;&lt;&gt;<\/textarea>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="link-url-&amp;&lt;&gt;".+name="' . PostEntryHandler::PARAM_LINKURL . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="link-text-&amp;&lt;&gt;".+name="' . PostEntryHandler::PARAM_LINKTEXT . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="img-url-&amp;&lt;&gt;".+name="' . PostEntryHandler::PARAM_IMGURL . '".+>/', $html);
    }

    public function testRenderHtmlForm_shouldHtmlspecialcharValuesFromParentPost(): void
    {
        $parentPost = static::createStub(Post::class);
        $parentPost->method('HasImgUrl')->willReturn(true);
        $parentPost->method('GetImgUrl')->willReturn('img-url-&<>');
        $parentPost->method('HasLinkText')->willReturn(true);
        $parentPost->method('GetLinkText')->willReturn('link-text-&<>');
        $parentPost->method('HasLinkUrl')->willReturn(true);
        $parentPost->method('GetLinkUrl')->willReturn('link-url-&<>');
        $parentPost->method('GetTitle')->willReturn('title-&<>');
        $parentPost->method('HasContent')->willReturn(true);
        $parentPost->method('GetContent')->willReturn('content-&<>');

        $postEntryForm = new PostEntryForm($parentPost, null);
        $html = $postEntryForm->renderHtmlForm();

        static::assertMatchesRegularExpression('/<input type="text".+value="Re: title-&amp;&lt;&gt;".+name="' . PostEntryHandler::PARAM_TITLE . '".+>/', $html);
        static::assertMatchesRegularExpression('/<textarea name="' . PostEntryHandler::PARAM_CONTENT . '".+>\[i\]content-&amp;&lt;&gt;\[\/i\]<\/textarea>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="link-url-&amp;&lt;&gt;".+name="' . PostEntryHandler::PARAM_LINKURL . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="link-text-&amp;&lt;&gt;".+name="' . PostEntryHandler::PARAM_LINKTEXT . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+value="img-url-&amp;&lt;&gt;".+name="' . PostEntryHandler::PARAM_IMGURL . '".+>/', $html);
    }

    public function testRenderHtmlForm_shouldHaveAllFields(): void
    {
        $postEntryForm = new PostEntryForm(null, null);
        $html = $postEntryForm->renderHtmlForm();

        static::assertMatchesRegularExpression('/<input type="text".+name="' . PostEntryHandler::PARAM_NICK . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="password".+name="' . PostEntryHandler::PARAM_PASS . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+name="' . PostEntryHandler::PARAM_EMAIL . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+name="' . PostEntryHandler::PARAM_TITLE . '".+>/', $html);
        static::assertMatchesRegularExpression('/<textarea name="' . PostEntryHandler::PARAM_CONTENT . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="hidden".+name="' . PostEntryHandler::PARAM_PARENTPOSTID . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+name="' . PostEntryHandler::PARAM_LINKURL . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+name="' . PostEntryHandler::PARAM_LINKTEXT . '".+>/', $html);
        static::assertMatchesRegularExpression('/<input type="text".+name="' . PostEntryHandler::PARAM_IMGURL . '".+>/', $html);
    }

    public function testRenderHtmlForm_shouldSetParentPostIdTo0(): void
    {
        $postEntryForm = new PostEntryForm(null, null);
        $html = $postEntryForm->renderHtmlForm();

        static::assertStringContainsString('<input type="hidden" name="post_parentpostid" value="0"/>', $html);
    }

    public function testRenderHtmlForm_shouldSetParentPostIdToValueFromParentPost(): void
    {
        $parentPost = static::createStub(Post::class);
        $parentPost->method('GetId')->willReturn(666);

        $postEntryForm = new PostEntryForm($parentPost, null);
        $html = $postEntryForm->renderHtmlForm();

        static::assertStringContainsString('<input type="hidden" name="post_parentpostid" value="666"/>', $html);
    }

    public function testRenderHtmlForm_shouldSetParentPostIdToValueFromPassedHandler(): void
    {
        $postEntryHandler = static::createStub(PostEntryHandler::class);
        $postEntryHandler->method('GetParentPostId')->willReturn(777);

        $postEntryForm = new PostEntryForm(null, $postEntryHandler);
        $html = $postEntryForm->renderHtmlForm();

        static::assertStringContainsString('<input type="hidden" name="post_parentpostid" value="777"/>', $html);
    }

    public function testRenderHtmlForm_shouldHaveSubmitInput(): void
    {
        $postEntryForm = new PostEntryForm(null, null);
        $html = $postEntryForm->renderHtmlForm();

        static::assertStringContainsString('<input type="submit" value="Eintrag senden"/>', $html);
    }

    public function testRenderHtmlForm_shouldHaveResetInput(): void
    {
        $postEntryForm = new PostEntryForm(null, null);
        $html = $postEntryForm->renderHtmlForm();

        static::assertStringContainsString('<input type="reset" value="Eintrag löschen"/>', $html);
    }

    public function testRenderHtmlForm_shouldHavePreviewInput(): void
    {
        $postEntryForm = new PostEntryForm(null, null);
        $html = $postEntryForm->renderHtmlForm();

        static::assertStringContainsString('<input type="button" value="Vorschau" onclick="preview();"/>', $html);
    }

    public function testRenderHtmlForm_shouldCallEndpoint(): void
    {
        $postEntryForm = new PostEntryForm(null, null);
        $html = $postEntryForm->renderHtmlForm();

        static::assertStringContainsString('<form id="postform" method="post" action="postentry.php?post=1" accept-charset="utf-8">', $html);
    }

}
