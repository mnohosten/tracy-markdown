# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-12-14

### Added
- Initial release of Tracy Markdown extension
- "Copy Markdown" button in Tracy BlueScreen for easy error reporting
- Comprehensive Markdown formatting of exceptions with:
  - Error type and message
  - Source file location with code snippet (5 lines context)
  - Full stack trace with formatted arguments
  - Previous exception support
  - HTTP request information (URL, method, GET/POST parameters, headers)
  - Environment details (PHP version, Tracy version, timestamp)
- Automatic sensitive data redaction:
  - 11 sensitive field patterns (password, token, api_key, etc.)
  - Nested data support
  - Case-insensitive matching
- Security features:
  - Authorization and Cookie header exclusion
  - XSS prevention in exception messages
  - Proper JSON escaping
- Modern UI with:
  - Responsive button design
  - Hover and click animations
  - Visual feedback (checkmark on successful copy)
  - Mobile browser support
- Clipboard API with automatic fallback to `document.execCommand`
- 100% test coverage with 53 tests and 134 assertions
- GitHub Actions CI/CD for PHP 8.0-8.4
- Comprehensive documentation:
  - README with examples and FAQ
  - TESTING.md with coverage details
  - PHPUnit configuration
  - Example usage file

### Supported Frameworks
- Vanilla PHP applications
- Nette Framework (bootstrap and DI integration)
- Symfony Framework
- Any framework using Tracy debugger

### Requirements
- PHP 8.0 or higher
- Tracy 2.9 or higher

[1.0.0]: https://github.com/mnohosten/tracy-markdown/releases/tag/v1.0.0
