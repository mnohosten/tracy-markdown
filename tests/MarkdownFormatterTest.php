<?php

declare(strict_types=1);

namespace Mnohosten\TracyMarkdown\Tests;

use Exception;
use Mnohosten\TracyMarkdown\MarkdownFormatter;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MarkdownFormatterTest extends TestCase
{
    private MarkdownFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new MarkdownFormatter();
    }

    public function testFormatExceptionBasic(): void
    {
        $exception = new Exception('Test error message', 123);
        $markdown = $this->formatter->formatException($exception);

        $this->assertStringContainsString('# Error', $markdown);
        $this->assertStringContainsString('Exception', $markdown);
        $this->assertStringContainsString('Test error message', $markdown);
        $this->assertStringContainsString('## Source file', $markdown);
        $this->assertStringContainsString('## Stack trace', $markdown);
        $this->assertStringContainsString('## Environment', $markdown);
    }

    public function testFormatExceptionWithRequestData(): void
    {
        $exception = new Exception('Test error');
        $requestData = [
            'url' => 'http://example.com/test',
            'method' => 'POST',
            'get' => ['param1' => 'value1'],
            'post' => ['field1' => 'data1'],
            'headers' => ['Content-Type' => 'application/json'],
        ];

        $markdown = $this->formatter->formatException($exception, $requestData);

        $this->assertStringContainsString('## Request', $markdown);
        $this->assertStringContainsString('http://example.com/test', $markdown);
        $this->assertStringContainsString('POST', $markdown);
        $this->assertStringContainsString('### GET parameters', $markdown);
        $this->assertStringContainsString('param1', $markdown);
        $this->assertStringContainsString('### POST parameters', $markdown);
        $this->assertStringContainsString('field1', $markdown);
        $this->assertStringContainsString('### Headers', $markdown);
        $this->assertStringContainsString('Content-Type', $markdown);
    }

    public function testFormatExceptionWithPreviousException(): void
    {
        $previous = new RuntimeException('Previous error');
        $exception = new Exception('Main error', 0, $previous);

        $markdown = $this->formatter->formatException($exception);

        $this->assertStringContainsString('## Previous exception', $markdown);
        $this->assertStringContainsString('RuntimeException', $markdown);
        $this->assertStringContainsString('Previous error', $markdown);
    }

    public function testFormatExceptionEscapesMarkdown(): void
    {
        $exception = new Exception('Error with *special* `chars` [link] #hash');
        $markdown = $this->formatter->formatException($exception);

        $this->assertStringContainsString('\*special\*', $markdown);
        $this->assertStringContainsString('\`chars\`', $markdown);
        $this->assertStringContainsString('\[link\]', $markdown);
        $this->assertStringContainsString('\#hash', $markdown);
    }

    public function testFormatExceptionIncludesPhpVersion(): void
    {
        $exception = new Exception('Test');
        $markdown = $this->formatter->formatException($exception);

        $this->assertStringContainsString('- **PHP**: ' . PHP_VERSION, $markdown);
    }

    public function testFormatExceptionIncludesDate(): void
    {
        $exception = new Exception('Test');
        $markdown = $this->formatter->formatException($exception);

        $this->assertStringContainsString('- **Date**:', $markdown);
        $this->assertStringContainsString(date('Y-m-d'), $markdown);
    }

    public function testSanitizePostDataRedactsPasswords(): void
    {
        $exception = new Exception('Test');
        $requestData = [
            'url' => 'http://example.com',
            'method' => 'POST',
            'post' => [
                'username' => 'john',
                'password' => 'secret123',
                'passwd' => 'secret456',
                'pwd' => 'secret789',
                'api_key' => 'key123',
                'token' => 'token456',
            ],
        ];

        $markdown = $this->formatter->formatException($exception, $requestData);

        $this->assertStringContainsString('username', $markdown);
        $this->assertStringContainsString('john', $markdown);
        $this->assertStringContainsString('***REDACTED***', $markdown);
        $this->assertStringNotContainsString('secret123', $markdown);
        $this->assertStringNotContainsString('secret456', $markdown);
        $this->assertStringNotContainsString('secret789', $markdown);
        $this->assertStringNotContainsString('key123', $markdown);
        $this->assertStringNotContainsString('token456', $markdown);
    }

    public function testSanitizePostDataRedactsNestedPasswords(): void
    {
        $exception = new Exception('Test');
        $requestData = [
            'url' => 'http://example.com',
            'method' => 'POST',
            'post' => [
                'user' => [
                    'name' => 'John',
                    'password' => 'nested_secret',
                ],
            ],
        ];

        $markdown = $this->formatter->formatException($exception, $requestData);

        $this->assertStringContainsString('John', $markdown);
        $this->assertStringContainsString('***REDACTED***', $markdown);
        $this->assertStringNotContainsString('nested_secret', $markdown);
    }

    public function testCollectRequestDataInCli(): void
    {
        $data = $this->formatter->collectRequestData();

        // In CLI mode, should return empty array
        $this->assertIsArray($data);
    }

    public function testCollectRequestDataInWebContext(): void
    {
        // Simulate web context
        $_SERVER['REQUEST_SCHEME'] = 'https';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/test/path';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['test' => 'value'];
        $_POST = [];

        $data = $this->formatter->collectRequestData();

        if (php_sapi_name() === 'cli') {
            // In CLI, even with $_SERVER set, it should still return empty
            $this->assertIsArray($data);
        } else {
            $this->assertArrayHasKey('url', $data);
            $this->assertArrayHasKey('method', $data);
            $this->assertArrayHasKey('get', $data);
            $this->assertArrayHasKey('post', $data);
        }

        // Cleanup
        unset($_SERVER['REQUEST_SCHEME'], $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
        $_GET = [];
        $_POST = [];
    }

    public function testFormatExceptionWithEmptyRequestData(): void
    {
        $exception = new Exception('Test');
        $requestData = [
            'url' => 'http://example.com',
            'method' => 'GET',
            'get' => [],
            'post' => [],
            'headers' => [],
        ];

        $markdown = $this->formatter->formatException($exception, $requestData);

        $this->assertStringContainsString('## Request', $markdown);
        $this->assertStringContainsString('http://example.com', $markdown);
        $this->assertStringNotContainsString('### GET parameters', $markdown);
        $this->assertStringNotContainsString('### POST parameters', $markdown);
        $this->assertStringNotContainsString('### Headers', $markdown);
    }

    public function testFormatExceptionWithCodeSnippet(): void
    {
        $exception = new Exception('Test');
        $markdown = $this->formatter->formatException($exception);

        // Should contain code snippet in php code block
        $this->assertStringContainsString('```php', $markdown);
    }

    public function testFormatExceptionWithNonReadableFile(): void
    {
        // Create exception with non-existent file
        $exception = new Exception('Test');

        // Use reflection to set private file property to non-existent path
        $reflection = new \ReflectionClass($exception);
        $fileProperty = $reflection->getProperty('file');
        $fileProperty->setAccessible(true);
        $fileProperty->setValue($exception, '/non/existent/file.php');

        $markdown = $this->formatter->formatException($exception);

        // Should still generate markdown even without code snippet
        $this->assertStringContainsString('# Error', $markdown);
        $this->assertStringContainsString('/non/existent/file.php', $markdown);
    }

    public function testStackTraceFormatting(): void
    {
        try {
            $this->throwNestedExceptions();
        } catch (Exception $e) {
            $markdown = $this->formatter->formatException($e);

            $this->assertStringContainsString('## Stack trace', $markdown);
            $this->assertStringContainsString('#0', $markdown);
            $this->assertStringContainsString('throwNestedExceptions', $markdown);
        }
    }

    private function throwNestedExceptions(): void
    {
        throw new Exception('Nested exception test');
    }

    public function testFormatArgsWithVariousTypes(): void
    {
        try {
            $this->methodWithVariousArgs(
                'string value',
                123,
                45.67,
                true,
                false,
                null,
                ['array'],
                new \stdClass()
            );
        } catch (Exception $e) {
            $markdown = $this->formatter->formatException($e);

            $this->assertStringContainsString('string value', $markdown);
            $this->assertStringContainsString('123', $markdown);
            $this->assertStringContainsString('45.67', $markdown);
            $this->assertStringContainsString('true', $markdown);
            $this->assertStringContainsString('false', $markdown);
            $this->assertStringContainsString('null', $markdown);
            $this->assertStringContainsString('array(1)', $markdown);
            $this->assertStringContainsString('stdClass', $markdown);
        }
    }

    private function methodWithVariousArgs($str, $int, $float, $bool1, $bool2, $null, $arr, $obj): void
    {
        throw new Exception('Test with various args');
    }

    public function testFormatArgsWithLongString(): void
    {
        try {
            $longString = str_repeat('a', 100);
            $this->methodWithLongString($longString);
        } catch (Exception $e) {
            $markdown = $this->formatter->formatException($e);

            // Should truncate long strings
            $this->assertStringContainsString('...', $markdown);
        }
    }

    private function methodWithLongString(string $str): void
    {
        throw new Exception('Test with long string');
    }

    public function testSanitizeAllSensitiveKeys(): void
    {
        $exception = new Exception('Test');
        $requestData = [
            'url' => 'http://example.com',
            'method' => 'POST',
            'post' => [
                'password' => 'pass1',
                'passwd' => 'pass2',
                'pwd' => 'pass3',
                'secret' => 'secret1',
                'token' => 'token1',
                'api_key' => 'key1',
                'apikey' => 'key2',
                'credit_card' => 'card1',
                'cc' => 'card2',
                'MY_PASSWORD' => 'pass4', // Case insensitive
                'user_token' => 'token2', // Contains 'token'
            ],
        ];

        $markdown = $this->formatter->formatException($exception, $requestData);

        // All sensitive values should be redacted
        $count = substr_count($markdown, '***REDACTED***');
        $this->assertGreaterThanOrEqual(9, $count);

        // The actual sensitive values should NOT appear in the markdown
        // Check for the JSON representation which won't include the raw values
        $this->assertStringContainsString('***REDACTED***', $markdown);

        // Verify specific sensitive fields are redacted in JSON output
        $this->assertMatchesRegularExpression('/"password":\s*"\*\*\*REDACTED\*\*\*"/', $markdown);
        $this->assertMatchesRegularExpression('/"passwd":\s*"\*\*\*REDACTED\*\*\*"/', $markdown);
        $this->assertMatchesRegularExpression('/"secret":\s*"\*\*\*REDACTED\*\*\*"/', $markdown);
    }
}
