<?php

namespace Modules\ProjectX\Utils;

use League\CommonMark\GithubFlavoredMarkdownConverter;

class ChatMessageRendererUtil
{
    protected GithubFlavoredMarkdownConverter $converter;

    public function __construct()
    {
        $config = [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
        ];

        $this->converter = new GithubFlavoredMarkdownConverter($config);
    }

    public function renderForRole(string $content, string $role): string
    {
        if (trim($content) === '') {
            return '';
        }

        if ($role === 'assistant' || $role === 'system') {
            return $this->renderMarkdown($content);
        }

        return nl2br(e($content));
    }

    public function renderMarkdown(string $markdown): string
    {
        $html = (string) $this->converter->convert($markdown);

        return $this->normalizeHtml($html);
    }

    protected function normalizeHtml(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $previousUseInternalErrors = libxml_use_internal_errors(true);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $wrappedHtml = '<div id="projectx-chat-content-root">' . $html . '</div>';
        $dom->loadHTML(mb_convert_encoding($wrappedHtml, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//script|//style') as $node) {
            $node->parentNode?->removeChild($node);
        }

        $this->decorateTypography($xpath);
        $this->decorateLists($xpath);
        $this->decorateCode($xpath);
        $this->decorateLinks($xpath);
        $this->decorateTables($dom, $xpath);

        $output = '';
        $root = $dom->getElementById('projectx-chat-content-root');
        if ($root !== null) {
            foreach ($root->childNodes as $childNode) {
                $output .= $dom->saveHTML($childNode);
            }
        } else {
            $output = $dom->saveHTML();
        }

        libxml_clear_errors();
        libxml_use_internal_errors($previousUseInternalErrors);

        return trim($output);
    }

    protected function decorateTypography(\DOMXPath $xpath): void
    {
        $headingClasses = [
            'h1' => 'fs-2 fw-bold text-gray-900 mt-6 mb-4',
            'h2' => 'fs-3 fw-bold text-gray-900 mt-6 mb-4',
            'h3' => 'fs-4 fw-bold text-gray-900 mt-5 mb-3',
            'h4' => 'fs-5 fw-bold text-gray-900 mt-5 mb-3',
            'h5' => 'fs-6 fw-bold text-gray-900 mt-4 mb-3',
            'h6' => 'fs-7 fw-bold text-gray-900 mt-4 mb-2',
        ];

        foreach ($headingClasses as $tag => $classes) {
            foreach ($xpath->query('//' . $tag) as $node) {
                $this->appendClass($node, $classes);
            }
        }

        foreach ($xpath->query('//p') as $node) {
            $this->appendClass($node, 'mb-4 text-gray-900 fw-normal');
        }

        foreach ($xpath->query('//blockquote') as $node) {
            $this->appendClass($node, 'border-start border-4 border-primary ps-4 py-2 my-4 text-gray-700 fst-italic');
        }
    }

    protected function decorateLists(\DOMXPath $xpath): void
    {
        foreach ($xpath->query('//ul') as $node) {
            $this->appendClass($node, 'ps-6 mb-4');
        }

        foreach ($xpath->query('//ol') as $node) {
            $this->appendClass($node, 'ps-6 mb-4');
        }

        foreach ($xpath->query('//li') as $node) {
            $this->appendClass($node, 'mb-2');
        }
    }

    protected function decorateCode(\DOMXPath $xpath): void
    {
        foreach ($xpath->query('//pre') as $node) {
            $this->appendClass($node, 'bg-light rounded p-4 mb-4 overflow-auto');
        }

        foreach ($xpath->query('//code') as $node) {
            if ($node->parentNode && $node->parentNode->nodeName === 'pre') {
                $this->appendClass($node, 'text-gray-900 fs-8');
                continue;
            }

            $this->appendClass($node, 'bg-light rounded px-2 py-1 fs-8 text-gray-900');
        }
    }

    protected function decorateLinks(\DOMXPath $xpath): void
    {
        foreach ($xpath->query('//a') as $node) {
            if (! $node instanceof \DOMElement) {
                continue;
            }

            $this->appendClass($node, 'link-primary fw-semibold');
            $node->setAttribute('target', '_blank');
            $node->setAttribute('rel', 'noopener noreferrer nofollow');
        }
    }

    protected function decorateTables(\DOMDocument $dom, \DOMXPath $xpath): void
    {
        foreach ($xpath->query('//table') as $node) {
            if (! $node instanceof \DOMElement) {
                continue;
            }

            $this->appendClass($node, 'table table-row-dashed align-middle gs-0 gy-3 mb-2');

            $parent = $node->parentNode;
            if (! $parent instanceof \DOMElement || $parent->nodeName !== 'div' || strpos(' ' . $parent->getAttribute('class') . ' ', ' table-responsive ') !== false) {
                if ($parent instanceof \DOMElement && $parent->nodeName === 'div' && strpos(' ' . $parent->getAttribute('class') . ' ', ' table-responsive ') !== false) {
                    continue;
                }
            }

            $wrapper = $dom->createElement('div');
            $wrapper->setAttribute('class', 'table-responsive mb-4');
            $parent?->replaceChild($wrapper, $node);
            $wrapper->appendChild($node);
        }
    }

    protected function appendClass(\DOMNode $node, string $classes): void
    {
        if (! $node instanceof \DOMElement) {
            return;
        }

        $existing = trim((string) $node->getAttribute('class'));
        $merged = trim($existing . ' ' . $classes);
        $node->setAttribute('class', $merged);
    }
}
