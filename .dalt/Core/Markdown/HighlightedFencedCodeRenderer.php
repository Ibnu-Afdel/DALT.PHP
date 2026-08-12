<?php

declare(strict_types=1);

namespace Core\Markdown;

use Highlight\Highlighter;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;

final class HighlightedFencedCodeRenderer implements NodeRendererInterface
{
    /** @var array<string, string> */
    private const LANGUAGE_ALIASES = [
        'dockerfile' => 'dockerfile',
        'env' => 'bash',
        'html' => 'xml',
        'sh' => 'bash',
        'text' => 'plaintext',
        'yaml' => 'yaml',
    ];

    private Highlighter $highlighter;

    public function __construct()
    {
        $this->highlighter = new Highlighter();
    }

    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        FencedCode::assertInstanceOf($node);

        $sourceLanguage = strtolower((string) ($node->getInfoWords()[0] ?? ''));
        $language = self::LANGUAGE_ALIASES[$sourceLanguage] ?? $sourceLanguage;
        $code = $node->getLiteral();
        $classes = ['hljs'];

        if ($sourceLanguage !== '') {
            $classes[] = 'language-' . preg_replace('/[^a-z0-9_-]/', '', $sourceLanguage);
        }

        $rendered = Xml::escape($code);
        if ($language !== '') {
            try {
                $result = $this->highlighter->highlight($language, $code);
                $rendered = $result->value;
            } catch (\DomainException) {
                // Unknown fences remain safely escaped, copyable code.
            }
        }

        return new HtmlElement('pre', [], new HtmlElement('code', ['class' => implode(' ', $classes)], $rendered));
    }
}
