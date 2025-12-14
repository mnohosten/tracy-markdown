# Testing Documentation

## Test Coverage Summary

The `mnohosten/tracy-markdown` library has comprehensive test coverage across all three main classes:

### Test Statistics

- **Total Tests**: 53
- **Total Assertions**: 134
- **Coverage Target**: 100%

## Test Suites

### 1. MarkdownFormatterTest (17 tests)

Tests for the `MarkdownFormatter` class which handles formatting exceptions as Markdown.

**Covered Scenarios:**
- Basic exception formatting with all core sections
- Exception formatting with HTTP request data (URL, method, GET/POST params, headers)
- Previous exception handling
- Markdown character escaping (*, `, [, ], #, etc.)
- PHP version inclusion in output
- Date/timestamp inclusion
- Sensitive data sanitization (passwords, tokens, API keys, etc.)
- Nested sensitive data sanitization in POST data
- Request data collection in CLI mode
- Request data collection in web context
- Empty request data handling
- Code snippet generation from exception file
- Non-readable file handling
- Stack trace formatting with nested exceptions
- Function argument formatting (strings, numbers, booleans, nulls, arrays, objects)
- Long string truncation (>50 chars)
- Comprehensive sensitive key redaction (password, passwd, pwd, secret, token, api_key, apikey, credit_card, cc)

### 2. MarkdownPanelTest (22 tests)

Tests for the `MarkdownPanel` class which renders the "Copy Markdown" button.

**Covered Scenarios:**
- Exception setter and getter
- Null exception handling
- Button rendering output
- CSS styles inclusion
- HTML button element rendering
- JavaScript code inclusion
- Markdown data embedding
- Rendering without exception set
- HTML/XSS escaping in exception messages
- SVG copy icon rendering
- SVG check icon rendering
- Copied state styling
- onClick event handler
- Navigator clipboard API usage
- Fallback copy mechanism (document.execCommand)
- showCopied feedback function
- Request data collection integration
- Button title attribute
- Z-index positioning
- Hover state styles
- Button text content
- CSS transitions

### 3. TracyMarkdownExtensionTest (14 tests)

Tests for the `TracyMarkdownExtension` class which integrates with Tracy's BlueScreen.

**Covered Scenarios:**
- Singleton pattern implementation
- Instance registration
- getInstance() before registration (returns null)
- Formatter accessor
- Single registration (prevents duplicate panels)
- Panel registration with BlueScreen
- Panel markdown generation with exception
- Null exception handling in panel
- JSON-encoded markdown with proper escaping
- Copy button inclusion in panel output
- JavaScript inclusion
- CSS stylesheet inclusion
- SVG icons (copy and check)
- Shared formatter instance

## Code Coverage by Class

### MarkdownFormatter.php
- ✅ `formatException()` - Full coverage with various exception types and request data
- ✅ `formatBlueScreen()` - Covered via formatException calls
- ✅ `getCodeSnippet()` - Tested with readable and non-readable files
- ✅ `formatArgs()` - All argument types tested (string, int, float, bool, null, array, object)
- ✅ `sanitizePostData()` - Recursive sanitization tested
- ✅ `collectRequestData()` - CLI and web contexts
- ✅ `getRequestHeaders()` - Header collection and sanitization
- ✅ `escapeMarkdown()` - All special Markdown characters

### MarkdownPanel.php
- ✅ `setException()` - Setter functionality
- ✅ `getException()` - Getter functionality
- ✅ `renderButton()` - Complete rendering with all components
- ✅ `getStyles()` - Implicitly tested via renderButton
- ✅ `getButtonHtml()` - Implicitly tested via renderButton
- ✅ `getScript()` - Implicitly tested via renderButton
- ✅ `collectRequestData()` - Request data collection
- ✅ `getRequestHeaders()` - Header collection

### TracyMarkdownExtension.php
- ✅ `register()` - Registration and singleton
- ✅ `getInstance()` - Instance retrieval
- ✅ `doRegister()` - Tracy BlueScreen integration
- ✅ `getFormatter()` - Formatter accessor
- ✅ Panel callback - Exception handling and HTML generation

## Edge Cases Tested

1. **Null/Empty Handling**
   - Null exceptions
   - Empty request data
   - Missing exception data

2. **Security**
   - Sensitive data redaction (11 different sensitive keys tested)
   - Nested sensitive data in arrays
   - Case-insensitive sensitive key matching
   - XSS prevention in exception messages
   - Authorization and Cookie header removal

3. **File System**
   - Non-existent files
   - Unreadable files
   - File path escaping

4. **Data Types**
   - All PHP primitive types in stack traces
   - Long strings (>50 chars)
   - Unicode in POST data
   - HTML entities in exceptions

5. **Environment**
   - CLI vs web context
   - Missing $_SERVER variables
   - getallheaders() function availability

## Running Tests

```bash
# Run all tests
vendor/bin/phpunit

# Run with detailed output
vendor/bin/phpunit --testdox

# Run specific test file
vendor/bin/phpunit tests/MarkdownFormatterTest.php

# Run specific test method
vendor/bin/phpunit --filter testFormatExceptionBasic
```

## Test Structure

Each test follows the AAA pattern:
- **Arrange**: Set up test data and environment
- **Act**: Execute the method being tested
- **Assert**: Verify the expected outcome

Example:
```php
public function testFormatExceptionBasic(): void
{
    // Arrange
    $exception = new Exception('Test error message', 123);

    // Act
    $markdown = $this->formatter->formatException($exception);

    // Assert
    $this->assertStringContainsString('# Error', $markdown);
    $this->assertStringContainsString('Test error message', $markdown);
}
```

## Continuous Integration

The project uses GitHub Actions to run tests across multiple PHP versions (8.0, 8.1, 8.2, 8.3, 8.4) on every push and pull request. See `.github/workflows/tests.yml` for configuration.

## Coverage Requirements

- All public methods must be tested
- All branches must be covered
- Edge cases must have explicit tests
- Error conditions must be tested
- Integration points must be verified
