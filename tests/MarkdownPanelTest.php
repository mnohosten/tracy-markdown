<?php

declare(strict_types=1);

namespace Mnohosten\TracyMarkdown\Tests;

use Exception;
use Mnohosten\TracyMarkdown\MarkdownPanel;
use PHPUnit\Framework\TestCase;

class MarkdownPanelTest extends TestCase
{
    private MarkdownPanel $panel;

    protected function setUp(): void
    {
        $this->panel = new MarkdownPanel();

        // Set up web environment for testing
        $_SERVER['REQUEST_SCHEME'] = 'https';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['get_param' => 'get_value'];
        $_POST = ['post_param' => 'post_value'];
    }

    protected function tearDown(): void
    {
        // Clean up
        unset(
            $_SERVER['REQUEST_SCHEME'],
            $_SERVER['HTTP_HOST'],
            $_SERVER['REQUEST_URI'],
            $_SERVER['REQUEST_METHOD']
        );
        $_GET = [];
        $_POST = [];
    }

    public function testSetAndGetException(): void
    {
        $exception = new Exception('Test exception');
        $this->panel->setException($exception);

        $this->assertSame($exception, $this->panel->getException());
    }

    public function testGetExceptionReturnsNullInitially(): void
    {
        $this->assertNull($this->panel->getException());
    }

    public function testRenderButtonReturnsString(): void
    {
        $exception = new Exception('Test');
        $this->panel->setException($exception);

        $output = $this->panel->renderButton();

        $this->assertIsString($output);
        $this->assertNotEmpty($output);
    }

    public function testRenderButtonIncludesStyles(): void
    {
        $exception = new Exception('Test');
        $this->panel->setException($exception);

        $output = $this->panel->renderButton();

        $this->assertStringContainsString('<style>', $output);
        $this->assertStringContainsString('.tracy-markdown-btn', $output);
        $this->assertStringContainsString('position: fixed', $output);
    }

    public function testRenderButtonIncludesHtmlButton(): void
    {
        $exception = new Exception('Test');
        $this->panel->setException($exception);

        $output = $this->panel->renderButton();

        $this->assertStringContainsString('<button', $output);
        $this->assertStringContainsString('class="tracy-markdown-btn"', $output);
        $this->assertStringContainsString('Copy Markdown', $output);
    }

    public function testRenderButtonIncludesScript(): void
    {
        $exception = new Exception('Test');
        $this->panel->setException($exception);

        $output = $this->panel->renderButton();

        $this->assertStringContainsString('<script>', $output);
        $this->assertStringContainsString('TracyMarkdown', $output);
        $this->assertStringContainsString('copy:', $output);
    }

    public function testRenderButtonIncludesMarkdownData(): void
    {
        $exception = new Exception('Test exception message');
        $this->panel->setException($exception);

        $output = $this->panel->renderButton();

        $this->assertStringContainsString('markdown:', $output);
        // The exception message should be JSON-encoded in the output
        $this->assertStringContainsString('Test exception message', $output);
    }

    public function testRenderButtonWithoutExceptionStillWorks(): void
    {
        $output = $this->panel->renderButton();

        $this->assertIsString($output);
        $this->assertStringContainsString('<style>', $output);
        $this->assertStringContainsString('tracy-markdown-btn', $output);
    }

    public function testRenderButtonEscapesHtmlInException(): void
    {
        $exception = new Exception('<script>alert("xss")</script>');
        $this->panel->setException($exception);

        $output = $this->panel->renderButton();

        // HTML should be escaped in JSON
        $this->assertStringNotContainsString('<script>alert', $output);
    }

    public function testRenderButtonIncludesCopyIcon(): void
    {
        $exception = new Exception('Test');
        $this->panel->setException($exception);

        $output = $this->panel->renderButton();

        $this->assertStringContainsString('<svg', $output);
        $this->assertStringContainsString('copy-icon', $output);
    }

    public function testRenderButtonIncludesCheckIcon(): void
    {
        $exception = new Exception('Test');
        $this->panel->setException($exception);

        $output = $this->panel->renderButton();

        $this->assertStringContainsString('check-icon', $output);
    }

    public function testRenderButtonIncludesCopiedState(): void
    {
        $exception = new Exception('Test');
        $this->panel->setException($exception);

        $output = $this->panel->renderButton();

        $this->assertStringContainsString('.copied', $output);
    }

    public function testRenderButtonIncludesOnClickHandler(): void
    {
        $exception = new Exception('Test');
        $this->panel->setException($exception);

        $output = $this->panel->renderButton();

        $this->assertStringContainsString('onclick="TracyMarkdown.copy()"', $output);
    }

    public function testRenderButtonIncludesNavigatorClipboard(): void
    {
        $exception = new Exception('Test');
        $this->panel->setException($exception);

        $output = $this->panel->renderButton();

        $this->assertStringContainsString('navigator.clipboard', $output);
    }

    public function testRenderButtonIncludesFallbackCopy(): void
    {
        $exception = new Exception('Test');
        $this->panel->setException($exception);

        $output = $this->panel->renderButton();

        $this->assertStringContainsString('fallbackCopy', $output);
        $this->assertStringContainsString('document.execCommand', $output);
    }

    public function testRenderButtonIncludesShowCopied(): void
    {
        $exception = new Exception('Test');
        $this->panel->setException($exception);

        $output = $this->panel->renderButton();

        $this->assertStringContainsString('showCopied', $output);
        $this->assertStringContainsString('Copied!', $output);
    }

    public function testRenderButtonIncludesRequestData(): void
    {
        $exception = new Exception('Test');
        $this->panel->setException($exception);

        $output = $this->panel->renderButton();

        // In CLI mode, request data might be empty, but the structure should be there
        $this->assertStringContainsString('TracyMarkdown', $output);
    }

    public function testRenderButtonIncludesTitle(): void
    {
        $exception = new Exception('Test');
        $this->panel->setException($exception);

        $output = $this->panel->renderButton();

        $this->assertStringContainsString('title=', $output);
    }

    public function testRenderButtonIncludesZIndex(): void
    {
        $exception = new Exception('Test');
        $this->panel->setException($exception);

        $output = $this->panel->renderButton();

        $this->assertStringContainsString('z-index: 30001', $output);
    }

    public function testRenderButtonIncludesHoverStyles(): void
    {
        $exception = new Exception('Test');
        $this->panel->setException($exception);

        $output = $this->panel->renderButton();

        $this->assertStringContainsString(':hover', $output);
        $this->assertStringContainsString(':active', $output);
    }

    public function testRenderButtonIncludesButtonText(): void
    {
        $exception = new Exception('Test');
        $this->panel->setException($exception);

        $output = $this->panel->renderButton();

        $this->assertStringContainsString('btn-text', $output);
        $this->assertStringContainsString('Copy Markdown', $output);
    }

    public function testRenderButtonIncludesTransitions(): void
    {
        $exception = new Exception('Test');
        $this->panel->setException($exception);

        $output = $this->panel->renderButton();

        $this->assertStringContainsString('transition', $output);
    }
}
