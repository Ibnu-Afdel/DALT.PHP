<?php

declare(strict_types=1);

namespace Core\Markdown;

use League\CommonMark\Node\Block\AbstractBlock;

final class AlertBlock extends AbstractBlock
{
    public function __construct(public readonly string $type)
    {
        parent::__construct();
    }
}
