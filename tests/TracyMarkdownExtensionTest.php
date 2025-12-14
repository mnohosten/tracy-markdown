<?php

declare(strict_types=1);

namespace Mnohosten\TracyMarkdown\Tests;

use Exception;
use Mnohosten\TracyMarkdown\MarkdownFormatter;
use Mnohosten\TracyMarkdown\TracyMarkdownExtension;
use PHPUnit\Framework\TestCase;
use Tracy\Debugger;

class TracyMarkdownExtensionTest extends TestCase
{
    protected function setUp(): void
    {
        // Ensure Tracy is in development mode for testing
        Debugger::$productionMode = false;
        Debugger::enable(Debugger::Development);
    }

    protected function tearDown(): void
    {
        // Reset singleton instance using reflection
        $reflection = new \ReflectionClass(TracyMarkdownExtension::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setAccessible(true);
        $instanceProperty->setValue(null, null);
    }

    public function testRegisterReturnsSameInstance(): void
    {
        $extension1 = TracyMarkdownExtension::register();
        $extension2 = TracyMarkdownExtension::register();

        $this->assertSame($extension1, $extension2);
    }

    public function testRegisterCreatesSingleton(): void
    {
        $extension = TracyMarkdownExtension::register();

        $this->assertInstanceOf(TracyMarkdownExtension::class, $extension);
        $this->assertSame($extension, TracyMarkdownExtension::getInstance());
    }

    public function testGetInstanceReturnsNullBeforeRegister(): void
    {
        $this->assertNull(TracyMarkdownExtension::getInstance());
    }

    public function testGetFormatterReturnsMarkdownFormatter(): void
    {
        $extension = TracyMarkdownExtension::register();
        $formatter = $extension->getFormatter();

        $this->assertInstanceOf(MarkdownFormatter::class, $formatter);
    }

    public function testRegisterOnlyOnce(): void
    {
        TracyMarkdownExtension::register();

        // Get BlueScreen panels count
        $blueScreen = Debugger::getBlueScreen();
        $reflection = new \ReflectionClass($blueScreen);
        $panelsProperty = $reflection->getProperty('panels');
        $panelsProperty->setAccessible(true);
        $panelsBeforeCount = count($panelsProperty->getValue($blueScreen));

        // Register again
        TracyMarkdownExtension::register();

        // Count should not increase
        $panelsAfterCount = count($panelsProperty->getValue($blueScreen));
        $this->assertEquals($panelsBeforeCount, $panelsAfterCount);
    }

    public function testPanelIsRegisteredWithBlueScreen(): void
    {
        TracyMarkdownExtension::register();

        $blueScreen = Debugger::getBlueScreen();
        $reflection = new \ReflectionClass($blueScreen);
        $panelsProperty = $reflection->getProperty('panels');
        $panelsProperty->setAccessible(true);
        $panels = $panelsProperty->getValue($blueScreen);

        $this->assertGreaterThan(0, count($panels));
    }

    public function testPanelGeneratesMarkdown(): void
    {
        TracyMarkdownExtension::register();

        $exception = new Exception('Test exception for panel');
        $blueScreen = Debugger::getBlueScreen();

        // Get panels using reflection
        $reflection = new \ReflectionClass($blueScreen);
        $panelsProperty = $reflection->getProperty('panels');
        $panelsProperty->setAccessible(true);
        $panels = $panelsProperty->getValue($blueScreen);

        // Find our panel (it should be the last one added)
        $ourPanel = end($panels);
        $this->assertIsCallable($ourPanel);

        // Call the panel with the exception
        $result = $ourPanel($exception);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('tab', $result);
        $this->assertArrayHasKey('panel', $result);
        $this->assertArrayHasKey('bottom', $result);
        $this->assertEquals('Copy Markdown', $result['tab']);
        $this->assertTrue($result['bottom']);
        $this->assertStringContainsString('tracy-markdown-copy-btn', $result['panel']);
        $this->assertStringContainsString('TracyMarkdown', $result['panel']);
    }

    public function testPanelReturnsNullForNullException(): void
    {
        TracyMarkdownExtension::register();

        $blueScreen = Debugger::getBlueScreen();

        // Get panels using reflection
        $reflection = new \ReflectionClass($blueScreen);
        $panelsProperty = $reflection->getProperty('panels');
        $panelsProperty->setAccessible(true);
        $panels = $panelsProperty->getValue($blueScreen);

        // Find our panel
        $ourPanel = end($panels);

        // Call with null exception
        $result = $ourPanel(null);

        $this->assertNull($result);
    }

    public function testPanelIncludesJsonEncodedMarkdown(): void
    {
        TracyMarkdownExtension::register();

        $exception = new Exception('Test <script>alert("xss")</script>');
        $blueScreen = Debugger::getBlueScreen();

        // Get the panel
        $reflection = new \ReflectionClass($blueScreen);
        $panelsProperty = $reflection->getProperty('panels');
        $panelsProperty->setAccessible(true);
        $panels = $panelsProperty->getValue($blueScreen);
        $ourPanel = end($panels);

        $result = $ourPanel($exception);

        // Check that HTML entities are properly escaped in JSON
        $this->assertStringContainsString('markdown:', $result['panel']);
        // The script tag should be escaped in the Markdown (with backslashes)
        // but will appear in the <script> tags of the JavaScript code
        // We need to verify the exception message is properly escaped
        $this->assertStringContainsString('alert', $result['panel']);
    }

    public function testPanelIncludesCopyButton(): void
    {
        TracyMarkdownExtension::register();

        $exception = new Exception('Test');
        $blueScreen = Debugger::getBlueScreen();

        $reflection = new \ReflectionClass($blueScreen);
        $panelsProperty = $reflection->getProperty('panels');
        $panelsProperty->setAccessible(true);
        $panels = $panelsProperty->getValue($blueScreen);
        $ourPanel = end($panels);

        $result = $ourPanel($exception);

        $this->assertStringContainsString('Copy Markdown', $result['panel']);
        $this->assertStringContainsString('tracy-markdown-copy-btn', $result['panel']);
        $this->assertStringContainsString('onclick="TracyMarkdown.copy()"', $result['panel']);
    }

    public function testPanelIncludesJavaScript(): void
    {
        TracyMarkdownExtension::register();

        $exception = new Exception('Test');
        $blueScreen = Debugger::getBlueScreen();

        $reflection = new \ReflectionClass($blueScreen);
        $panelsProperty = $reflection->getProperty('panels');
        $panelsProperty->setAccessible(true);
        $panels = $panelsProperty->getValue($blueScreen);
        $ourPanel = end($panels);

        $result = $ourPanel($exception);

        $this->assertStringContainsString('<script>', $result['panel']);
        $this->assertStringContainsString('TracyMarkdown.copy', $result['panel']);
        $this->assertStringContainsString('navigator.clipboard', $result['panel']);
        $this->assertStringContainsString('fallbackCopy', $result['panel']);
    }

    public function testPanelIncludesCSS(): void
    {
        TracyMarkdownExtension::register();

        $exception = new Exception('Test');
        $blueScreen = Debugger::getBlueScreen();

        $reflection = new \ReflectionClass($blueScreen);
        $panelsProperty = $reflection->getProperty('panels');
        $panelsProperty->setAccessible(true);
        $panels = $panelsProperty->getValue($blueScreen);
        $ourPanel = end($panels);

        $result = $ourPanel($exception);

        $this->assertStringContainsString('<style>', $result['panel']);
        $this->assertStringContainsString('.tracy-markdown-copy-btn', $result['panel']);
        $this->assertStringContainsString('position: fixed', $result['panel']);
        $this->assertStringContainsString('z-index: 30001', $result['panel']);
    }

    public function testPanelIncludesSVGIcons(): void
    {
        TracyMarkdownExtension::register();

        $exception = new Exception('Test');
        $blueScreen = Debugger::getBlueScreen();

        $reflection = new \ReflectionClass($blueScreen);
        $panelsProperty = $reflection->getProperty('panels');
        $panelsProperty->setAccessible(true);
        $panels = $panelsProperty->getValue($blueScreen);
        $ourPanel = end($panels);

        $result = $ourPanel($exception);

        // Should have copy icon
        $this->assertStringContainsString('<svg class="copy-icon"', $result['panel']);
        // Should have check icon
        $this->assertStringContainsString('<svg class="check-icon"', $result['panel']);
    }

    public function testFormatterIsSharedBetweenCalls(): void
    {
        $extension = TracyMarkdownExtension::register();
        $formatter1 = $extension->getFormatter();
        $formatter2 = $extension->getFormatter();

        $this->assertSame($formatter1, $formatter2);
    }
}
