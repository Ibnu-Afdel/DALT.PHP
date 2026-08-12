<?php

declare(strict_types=1);

namespace Core\Markdown;

use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;

final class AlertBlockRenderer implements NodeRendererInterface
{
    public function render(Node $node, ChildNodeRendererInterface $childRenderer): \Stringable
    {
        AlertBlock::assertInstanceOf($node);

        return new HtmlElement(
            'aside',
            [
                'class' => 'markdown-alert markdown-alert-' . strtolower($node->type),
                'role' => 'note',
                'aria-label' => ucfirst(strtolower($node->type)),
            ],
            $childRenderer->getInnerSeparator() . $childRenderer->renderNodes($node->children()) . $childRenderer->getInnerSeparator(),
        );
    }
}
