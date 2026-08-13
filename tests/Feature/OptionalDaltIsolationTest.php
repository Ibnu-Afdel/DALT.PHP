<?php

declare(strict_types=1);

test('the root manifests do not own DALT-only dependencies', function () {
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
    $package = json_decode((string) file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['require'])->not->toHaveKey('league/commonmark')
        ->and($composer['require'])->not->toHaveKey('scrivo/highlight.php')
        ->and($composer['autoload']['psr-4']['Core\\'])->toBe('framework/Core/')
        ->and($package['dependencies'] ?? [])->not->toHaveKey('vue')
        ->and($package['dependencies'] ?? [])->not->toHaveKey('reka-ui')
        ->and($package['dependencies'] ?? [])->not->toHaveKey('shadcn-vue')
        ->and($package['dependencies'] ?? [])->not->toHaveKey('lucide-vue-next');
});
