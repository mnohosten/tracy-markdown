<?php

declare(strict_types=1);

namespace Mnohosten\TracyMarkdown;

use Throwable;
use Tracy\Helpers;

class MarkdownFormatter
{
    public function formatException(Throwable $exception, ?array $requestData = null): string
    {
        $markdown = [];

        // Error header
        $markdown[] = '# Error';
        $markdown[] = '';
        $markdown[] = '**' . get_class($exception) . '**: ' . $this->escapeMarkdown($exception->getMessage());
        $markdown[] = '';

        // Source file
        $markdown[] = '## Source file';
        $markdown[] = '';
        $markdown[] = '**File**: `' . $exception->getFile() . ':' . $exception->getLine() . '`';
        $markdown[] = '';

        // Code snippet
        $codeSnippet = $this->getCodeSnippet($exception->getFile(), $exception->getLine());
        if ($codeSnippet) {
            $markdown[] = '```php';
            $markdown[] = $codeSnippet;
            $markdown[] = '```';
            $markdown[] = '';
        }

        // Stack trace
        $markdown[] = '## Stack trace';
        $markdown[] = '';
        $markdown[] = '```';
        foreach ($exception->getTrace() as $i => $frame) {
            $file = $frame['file'] ?? '[internal]';
            $line = $frame['line'] ?? '?';
            $class = $frame['class'] ?? '';
            $type = $frame['type'] ?? '';
            $function = $frame['function'] ?? '';
            $args = $this->formatArgs($frame['args'] ?? []);

            $markdown[] = sprintf(
                '#%d %s:%s %s%s%s(%s)',
                $i,
                $file,
                $line,
                $class,
                $type,
                $function,
                $args
            );
        }
        $markdown[] = '```';
        $markdown[] = '';

        // Previous exception
        if ($exception->getPrevious()) {
            $markdown[] = '## Previous exception';
            $markdown[] = '';
            $markdown[] = '**' . get_class($exception->getPrevious()) . '**: ' . $this->escapeMarkdown($exception->getPrevious()->getMessage());
            $markdown[] = '';
            $markdown[] = '**File**: `' . $exception->getPrevious()->getFile() . ':' . $exception->getPrevious()->getLine() . '`';
            $markdown[] = '';
        }

        // Request data
        if ($requestData) {
            $markdown[] = '## Request';
            $markdown[] = '';

            if (isset($requestData['url'])) {
                $markdown[] = '**URL**: `' . $requestData['url'] . '`';
            }
            if (isset($requestData['method'])) {
                $markdown[] = '**Method**: `' . $requestData['method'] . '`';
            }
            $markdown[] = '';

            if (!empty($requestData['get'])) {
                $markdown[] = '### GET parameters';
                $markdown[] = '';
                $markdown[] = '```json';
                $markdown[] = json_encode($requestData['get'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $markdown[] = '```';
                $markdown[] = '';
            }

            if (!empty($requestData['post'])) {
                $markdown[] = '### POST parameters';
                $markdown[] = '';
                $markdown[] = '```json';
                $markdown[] = json_encode($this->sanitizePostData($requestData['post']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $markdown[] = '```';
                $markdown[] = '';
            }

            if (!empty($requestData['headers'])) {
                $markdown[] = '### Headers';
                $markdown[] = '';
                $markdown[] = '```';
                foreach ($requestData['headers'] as $name => $value) {
                    $markdown[] = $name . ': ' . $value;
                }
                $markdown[] = '```';
                $markdown[] = '';
            }
        }

        // Environment info
        $markdown[] = '## Environment';
        $markdown[] = '';
        $markdown[] = '- **PHP**: ' . PHP_VERSION;
        $markdown[] = '- **Tracy**: ' . (defined('\Tracy\Debugger::VERSION') ? \Tracy\Debugger::VERSION : 'unknown');
        $markdown[] = '- **Date**: ' . date('Y-m-d H:i:s');
        $markdown[] = '';

        return implode("\n", $markdown);
    }

    public function formatBlueScreen(\Tracy\BlueScreen $blueScreen, Throwable $exception): string
    {
        return $this->formatException($exception, $this->collectRequestData());
    }

    private function getCodeSnippet(string $file, int $line, int $contextLines = 5): ?string
    {
        if (!is_file($file) || !is_readable($file)) {
            return null;
        }

        $lines = file($file);
        if ($lines === false) {
            return null;
        }

        $start = max(0, $line - $contextLines - 1);
        $end = min(count($lines), $line + $contextLines);

        $snippet = [];
        for ($i = $start; $i < $end; $i++) {
            $lineNum = $i + 1;
            $prefix = $lineNum === $line ? '> ' : '  ';
            $snippet[] = sprintf('%s%4d | %s', $prefix, $lineNum, rtrim($lines[$i]));
        }

        return implode("\n", $snippet);
    }

    private function formatArgs(array $args): string
    {
        $formatted = [];
        foreach ($args as $arg) {
            if (is_string($arg)) {
                $formatted[] = strlen($arg) > 50 ? '"' . substr($arg, 0, 50) . '..."' : '"' . $arg . '"';
            } elseif (is_int($arg) || is_float($arg)) {
                $formatted[] = (string) $arg;
            } elseif (is_bool($arg)) {
                $formatted[] = $arg ? 'true' : 'false';
            } elseif (is_null($arg)) {
                $formatted[] = 'null';
            } elseif (is_array($arg)) {
                $formatted[] = 'array(' . count($arg) . ')';
            } elseif (is_object($arg)) {
                $formatted[] = get_class($arg);
            } else {
                $formatted[] = gettype($arg);
            }
        }
        return implode(', ', $formatted);
    }

    private function sanitizePostData(array $data): array
    {
        $sensitiveKeys = ['password', 'passwd', 'pwd', 'secret', 'token', 'api_key', 'apikey', 'credit_card', 'cc'];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);
            foreach ($sensitiveKeys as $sensitive) {
                if (str_contains($lowerKey, $sensitive)) {
                    $data[$key] = '***REDACTED***';
                    break;
                }
            }
            if (is_array($value)) {
                $data[$key] = $this->sanitizePostData($value);
            }
        }

        return $data;
    }

    public function collectRequestData(): array
    {
        $data = [];

        if (php_sapi_name() !== 'cli') {
            $data['url'] = ($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' .
                          ($_SERVER['HTTP_HOST'] ?? 'localhost') .
                          ($_SERVER['REQUEST_URI'] ?? '/');
            $data['method'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $data['get'] = $_GET;
            $data['post'] = $_POST;
            $data['headers'] = $this->getRequestHeaders();
        }

        return $data;
    }

    private function getRequestHeaders(): array
    {
        $headers = [];

        if (function_exists('getallheaders')) {
            $headers = getallheaders() ?: [];
        } else {
            foreach ($_SERVER as $key => $value) {
                if (str_starts_with($key, 'HTTP_')) {
                    $header = str_replace('_', '-', substr($key, 5));
                    $header = ucwords(strtolower($header), '-');
                    $headers[$header] = $value;
                }
            }
        }

        // Remove sensitive headers
        unset($headers['Authorization'], $headers['Cookie']);

        return $headers;
    }

    private function escapeMarkdown(string $text): string
    {
        // Escape characters that have special meaning in Markdown
        return str_replace(
            ['\\', '`', '*', '_', '{', '}', '[', ']', '(', ')', '#', '+', '-', '.', '!', '|'],
            ['\\\\', '\`', '\*', '\_', '\{', '\}', '\[', '\]', '\(', '\)', '\#', '\+', '\-', '\.', '\!', '\|'],
            $text
        );
    }
}
