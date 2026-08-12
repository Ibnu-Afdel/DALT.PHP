<?php

declare(strict_types=1);

use Core\MarkdownRenderer;

function markdownRenderer(): MarkdownRenderer
{
    return new MarkdownRenderer();
}

test('renders CommonMark and the GFM features used by course content', function () {
    $html = markdownRenderer()->render(<<<'MARKDOWN'
# Heading

Plain *emphasis* and **strong** with ~~strikethrough~~.

- item
- [x] completed

| Name | Value |
| --- | --- |
| café | ✅ |

https://dalt.test
MARKDOWN);

    expect($html)->toContain('<h1>Heading</h1>')
        ->toContain('<em>emphasis</em>')
        ->toContain('<strong>strong</strong>')
        ->toContain('<del>strikethrough</del>')
        ->toContain('<ul>')
        ->toContain('checked="" disabled="" type="checkbox"')
        ->toContain('<table>')
        ->toContain('café')
        ->toContain('<a href="https://dalt.test">https://dalt.test</a>');
});

test('highlights explicit fences and safely falls back for unknown or unlabelled code', function () {
    $html = markdownRenderer()->render(<<<'MARKDOWN'
```php
<?php echo '<tag>';
```

```not-a-language
<tag attr="quoted">
```

```
  keep indentation
```
MARKDOWN);

    expect($html)->toContain('class="hljs language-php"')
        ->toContain('hljs-keyword')
        ->toContain('&lt;tag&gt;')
        ->toContain('class="hljs language-not-a-language"')
        ->toContain('&lt;tag attr=&quot;quoted&quot;&gt;')
        ->toContain('  keep indentation');
});

test('renders all supported GitHub alert types without changing normal quotes', function (string $type) {
    $html = markdownRenderer()->render("> [!{$type}]\n> Useful context.\n\n> Ordinary quote.");

    expect($html)->toContain('markdown-alert markdown-alert-' . strtolower($type))
        ->toContain('Useful context.')
        ->toContain('<blockquote>')
        ->toContain('Ordinary quote.');
})->with(['NOTE', 'TIP', 'IMPORTANT', 'WARNING', 'CAUTION']);

test('escapes arbitrary raw HTML and disables unsafe link schemes', function () {
    $html = markdownRenderer()->render("<script>alert(\"x\")</script>\n\n[bad](javascript:alert(1))");

    expect($html)->toContain('&lt;script&gt;alert("x")&lt;/script&gt;')
        ->not->toContain('<script>')
        ->not->toContain('href="javascript:');
});

test('preserves the existing attribute-free challenge hint markup only', function () {
    $html = markdownRenderer()->render("<details>\n<summary>Hint</summary>\n\nContent\n\n</details>\n\n<div class=\"unsafe\">Nope</div>");

    expect($html)->toContain('<details>')
        ->toContain('<summary>Hint</summary>')
        ->toContain('</details>')
        ->toContain('&lt;div class="unsafe"&gt;Nope&lt;/div&gt;');
});

test('renders representative PHP, shell, SQL, and Docker course code without corruption', function () {
    $renderer = markdownRenderer();
    $php = $renderer->render((string) file_get_contents(base_path('.dalt/course/lessons/02-routing/README.md')));
    $shell = $renderer->render((string) file_get_contents(base_path('.dalt/course/lessons/06-docker-basics/README.md')));
    $sql = $renderer->render((string) file_get_contents(base_path('.dalt/course/lessons/10-postgres-intermediate/README.md')));
    $docker = $renderer->render((string) file_get_contents(base_path('.dalt/course/lessons/07-dockerfile/README.md')));

    expect($php)->toContain('language-php')->toContain('&lt;?php')
        ->and($shell)->toContain('language-bash')->toContain('docker')
        ->and($sql)->toContain('language-sql')->toContain('SELECT')
        ->and($docker)->toContain('language-dockerfile')->toContain('FROM');
});
