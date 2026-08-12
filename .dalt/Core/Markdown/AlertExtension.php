<?php

declare(strict_types=1);

namespace Core\Markdown;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Text;

final class AlertExtension implements ExtensionInterface
{
    private const TYPES = ['NOTE', 'TIP', 'IMPORTANT', 'WARNING', 'CAUTION'];

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            ->addRenderer(AlertBlock::class, new AlertBlockRenderer())
            ->addEventListener(DocumentParsedEvent::class, $this->transform(...));
    }

    private function transform(DocumentParsedEvent $event): void
    {
        foreach ($this->descendants($event->getDocument()) as $node) {
            if (!$node instanceof BlockQuote || !$node->firstChild() instanceof Paragraph) {
                continue;
            }

            $paragraph = $node->firstChild();
            $first = $paragraph->firstChild();
            if (!$first instanceof Text || !preg_match('/^\\[!(NOTE|TIP|IMPORTANT|WARNING|CAUTION)\\](?:\\s|$)/', $first->getLiteral(), $matches)) {
                continue;
            }

            $type = $matches[1];
            if (!in_array($type, self::TYPES, true)) {
                continue;
            }

            $first->setLiteral((string) preg_replace('/^\\[!' . $type . '\\][ \\t]?/', '', $first->getLiteral(), 1));
            $alert = new AlertBlock($type);
            $alert->replaceChildren($node->children());
            $node->replaceWith($alert);
        }
    }

    /** @return iterable<\League\CommonMark\Node\Node> */
    private function descendants(\League\CommonMark\Node\Node $node): iterable
    {
        foreach ($node->children() as $child) {
            yield $child;
            yield from $this->descendants($child);
        }
    }
}
