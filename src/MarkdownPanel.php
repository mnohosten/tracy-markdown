<?php

declare(strict_types=1);

namespace Mnohosten\TracyMarkdown;

use Throwable;
use Tracy\BlueScreen;

class MarkdownPanel
{
    private MarkdownFormatter $formatter;
    private ?Throwable $exception = null;

    public function __construct()
    {
        $this->formatter = new MarkdownFormatter();
    }

    public function setException(Throwable $exception): void
    {
        $this->exception = $exception;
    }

    public function getException(): ?Throwable
    {
        return $this->exception;
    }

    public function renderButton(): string
    {
        return $this->getStyles() . $this->getButtonHtml() . $this->getScript();
    }

    private function getStyles(): string
    {
        return <<<'HTML'
<style>
.tracy-markdown-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
    color: #fff !important;
    border: none;
    border-radius: 6px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-decoration: none !important;
}
.tracy-markdown-btn:hover {
    background: linear-gradient(135deg, #5a6578 0%, #3d4758 100%);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transform: translateY(-1px);
}
.tracy-markdown-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.tracy-markdown-btn svg {
    width: 16px;
    height: 16px;
    fill: currentColor;
}
.tracy-markdown-btn.copied {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
}
.tracy-markdown-btn.copied svg.copy-icon {
    display: none;
}
.tracy-markdown-btn.copied svg.check-icon {
    display: inline-block;
}
.tracy-markdown-btn svg.check-icon {
    display: none;
}
.tracy-markdown-container {
    position: fixed;
    top: 10px;
    right: 10px;
    z-index: 30001;
}
</style>
HTML;
    }

    private function getButtonHtml(): string
    {
        return <<<'HTML'
<div class="tracy-markdown-container">
    <button class="tracy-markdown-btn" onclick="TracyMarkdown.copy()" title="Copy error report as Markdown">
        <svg class="copy-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
        </svg>
        <svg class="check-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
        </svg>
        <span class="btn-text">Copy Markdown</span>
    </button>
</div>
HTML;
    }

    private function getScript(): string
    {
        $markdown = $this->exception ? $this->formatter->formatException($this->exception, $this->collectRequestData()) : '';
        $markdownJson = json_encode($markdown, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        return <<<HTML
<script>
var TracyMarkdown = {
    markdown: {$markdownJson},

    copy: function() {
        var btn = document.querySelector('.tracy-markdown-btn');
        var btnText = btn.querySelector('.btn-text');

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(this.markdown).then(function() {
                TracyMarkdown.showCopied(btn, btnText);
            }).catch(function() {
                TracyMarkdown.fallbackCopy(btn, btnText);
            });
        } else {
            this.fallbackCopy(btn, btnText);
        }
    },

    fallbackCopy: function(btn, btnText) {
        var textarea = document.createElement('textarea');
        textarea.value = this.markdown;
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();

        try {
            document.execCommand('copy');
            this.showCopied(btn, btnText);
        } catch (e) {
            btnText.textContent = 'Failed!';
            setTimeout(function() {
                btnText.textContent = 'Copy Markdown';
            }, 2000);
        }

        document.body.removeChild(textarea);
    },

    showCopied: function(btn, btnText) {
        btn.classList.add('copied');
        btnText.textContent = 'Copied!';

        setTimeout(function() {
            btn.classList.remove('copied');
            btnText.textContent = 'Copy Markdown';
        }, 2000);
    }
};
</script>
HTML;
    }

    private function collectRequestData(): array
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

        unset($headers['Authorization'], $headers['Cookie']);

        return $headers;
    }
}
