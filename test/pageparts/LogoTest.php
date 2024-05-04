<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__.'/../BaseTest.php';
require_once __DIR__.'/../../src/pageparts/Logo.php';


/**
 * No Database stuff required
 */
final class LogoTest extends TestCase
{
    public function testRenderHtmlDiv() 
    {
        $logo = new Logo();
        $html = $logo->renderHtmlDiv();
        // todo: use value from config
        $this->assertStringContainsString('<img', $html);
    }       
}