<?php

/**
 * Example demonstrating Tracy Markdown extension
 *
 * Run with: php -S localhost:8000 -t examples
 * Then visit: http://localhost:8000
 */

require __DIR__ . '/../vendor/autoload.php';

use Tracy\Debugger;
use Mnohosten\TracyMarkdown\TracyMarkdownExtension;

// Enable Tracy debugger
Debugger::enable(Debugger::Development);

// Register the Markdown extension
TracyMarkdownExtension::register();

// Example class to demonstrate error
class User
{
    public function __construct(
        public int $id,
        public string $email,
        public string $name
    ) {}

    // Note: getIdString() method is intentionally missing to trigger an error
}

class AuthController
{
    public function register(): void
    {
        $user = new User(1, 'test@example.com', 'Test User');

        // This will throw an error - getIdString() doesn't exist
        $token = $user->getIdString();
    }
}

// Trigger the error
$controller = new AuthController();
$controller->register();
