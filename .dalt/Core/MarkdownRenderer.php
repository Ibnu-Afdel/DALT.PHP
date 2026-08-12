<?php

declare(strict_types=1);

namespace Core;

use Core\Markdown\AlertExtension;
use Core\Markdown\HighlightedFencedCodeRenderer;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

final class MarkdownRenderer
{
    private MarkdownConverter $converter;

    public function __construct()
    {
        $environment = new Environment([
            // Content must never execute as HTML; approved interactive hints are
            // restored by preserveChallengeHintMarkup() before parsing.
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 100,
            'max_delimiters_per_line' => 1000,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new AlertExtension());
        $environment->addRenderer(FencedCode::class, new HighlightedFencedCodeRenderer(), 10);

        $this->converter = new MarkdownConverter($environment);
    }

    public function render(string $markdown): string
    {
        // Existing challenge hints use semantic disclosure elements. Keep this
        // exact, attribute-free pair while treating every other raw HTML input
        // as text. A per-render token prevents source content from forging tags.
        $token = 'DALT_MARKDOWN_' . bin2hex(random_bytes(16));
        $parts = preg_split('/(^```.*?^```[ \\t]*$)/ms', $markdown, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts !== false) {
            foreach ($parts as $index => $part) {
                if ($index % 2 === 0) {
                    $part = preg_replace_callback(
                        '/<summary>(.*?)<\\/summary>/si',
                        static fn (array $match): string => "<!-- {$token}_SUMMARY:" . rtrim(strtr(base64_encode($match[1]), '+/', '-_'), '=') . ' -->',
                        $part,
                    ) ?? $part;
                    $parts[$index] = str_replace(
                        ['<details>', '</details>'],
                        ["<!-- {$token}_DETAILS_OPEN -->", "<!-- {$token}_DETAILS_CLOSE -->"],
                        $part,
                    );
                }
            }
            $markdown = implode('', $parts);
        }

        $html = str_replace(
            ["&lt;!-- {$token}_DETAILS_OPEN --&gt;", "&lt;!-- {$token}_DETAILS_CLOSE --&gt;"],
            ['<details>', '</details>'],
            $this->converter->convert($markdown)->getContent(),
        );

        return preg_replace_callback(
            '/&lt;!-- ' . preg_quote($token, '/') . '_SUMMARY:([A-Za-z0-9_-]+) --&gt;/',
            static fn (array $match): string => '<summary>' . htmlspecialchars((string) base64_decode(strtr($match[1], '-_', '+/'), true), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</summary>',
            $html,
        ) ?? $html;
    }
}
