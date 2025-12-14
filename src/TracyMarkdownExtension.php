<?php

declare(strict_types=1);

namespace Mnohosten\TracyMarkdown;

use Tracy\Debugger;
use Throwable;

class TracyMarkdownExtension
{
    private static ?self $instance = null;
    private MarkdownFormatter $formatter;
    private bool $registered = false;

    private function __construct()
    {
        $this->formatter = new MarkdownFormatter();
    }

    public static function register(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        if (!self::$instance->registered) {
            self::$instance->doRegister();
        }

        return self::$instance;
    }

    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    private function doRegister(): void
    {
        $blueScreen = Debugger::getBlueScreen();
        $formatter = $this->formatter;

        // Add panel that renders as a fixed button
        $blueScreen->addPanel(function (?Throwable $e) use ($formatter): ?array {
            if ($e === null) {
                return null;
            }

            $markdown = $formatter->formatException($e, $formatter->collectRequestData());
            $markdownJson = json_encode($markdown, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

            $html = <<<HTML
<style>
.tracy-markdown-copy-btn {
    position: fixed !important;
    top: 10px;
    right: 10px;
    z-index: 30001;
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
.tracy-markdown-copy-btn:hover {
    background: linear-gradient(135deg, #5a6578 0%, #3d4758 100%);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    transform: translateY(-1px);
}
.tracy-markdown-copy-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.tracy-markdown-copy-btn svg {
    width: 16px;
    height: 16px;
    fill: currentColor;
}
.tracy-markdown-copy-btn.copied {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
}
.tracy-markdown-copy-btn.copied .copy-icon {
    display: none;
}
.tracy-markdown-copy-btn.copied .check-icon {
    display: inline-block;
}
.tracy-markdown-copy-btn .check-icon {
    display: none;
}
/* Hide the panel section wrapper */
.tracy-section:has(.tracy-markdown-copy-btn) {
    position: static !important;
    display: contents !important;
}
.tracy-section:has(.tracy-markdown-copy-btn) > .tracy-section-label {
    display: none !important;
}
.tracy-section:has(.tracy-markdown-copy-btn) > .tracy-section-panel {
    display: contents !important;
}
</style>

<button class="tracy-markdown-copy-btn" onclick="TracyMarkdown.copy()" title="Copy error report as Markdown for AI">
    <svg class="copy-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
    </svg>
    <svg class="check-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
    </svg>
    <span class="btn-text">Copy Markdown</span>
</button>

<script>
var TracyMarkdown = {
    markdown: {$markdownJson},

    copy: function() {
        var btn = document.querySelector('.tracy-markdown-copy-btn');
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

            return [
                'tab' => 'Copy Markdown',
                'panel' => $html,
                'bottom' => true,
            ];
        });

        $this->registered = true;
    }

    public function getFormatter(): MarkdownFormatter
    {
        return $this->formatter;
    }
}
