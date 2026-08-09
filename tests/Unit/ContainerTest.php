<?php

declare(strict_types=1);

use Core\Container;

final class ContainerLeaf
{
}

final class ContainerBranch
{
    public function __construct(public readonly ContainerLeaf $leaf)
    {
    }
}

interface ContainerContract
{
}

abstract class ContainerAbstractService
{
}

final class ContainerImplementation implements ContainerContract
{
}

final class ContainerContractConsumer
{
    public function __construct(public readonly ContainerContract $service)
    {
    }
}

final class ContainerPrimitiveConsumer
{
    public function __construct(public readonly string $name)
    {
    }
}

final class ContainerDefaultConsumer
{
    public function __construct(public readonly string $name = 'default')
    {
    }
}

final class ContainerNullableConsumer
{
    public function __construct(public readonly ?ContainerContract $service)
    {
    }
}

final class ContainerUnionConsumer
{
    public function __construct(public readonly ContainerLeaf|ContainerBranch $service)
    {
    }
}

final class ContainerCircularA
{
    public function __construct(public readonly ContainerCircularB $b)
    {
    }
}

final class ContainerCircularB
{
    public function __construct(public readonly ContainerCircularA $a)
    {
    }
}

test('transient bindings build a fresh value for each resolution', function () {
    $container = new Container();
    $builds = 0;
    $container->bind('service', function () use (&$builds): object {
        $builds++;

        return new stdClass();
    });

    $first = $container->resolve('service');
    $second = $container->resolve('service');

    expect($first)->not->toBe($second)
        ->and($builds)->toBe(2);
});

test('singleton bindings build once including when the value is null', function () {
    $container = new Container();
    $builds = 0;
    $container->singleton('nullable', function () use (&$builds): mixed {
        $builds++;

        return null;
    });

    expect($container->resolve('nullable'))->toBeNull()
        ->and($container->resolve('nullable'))->toBeNull()
        ->and($builds)->toBe(1)
        ->and($container->resolved('nullable'))->toBeTrue();
});

test('instance stores an already-built value including null', function () {
    $container = new Container();
    $service = new stdClass();
    $container->instance('service', $service);
    $container->instance('nullable', null);

    expect($container->resolve('service'))->toBe($service)
        ->and($container->resolve('nullable'))->toBeNull();
});

test('rebinding removes a stale shared instance', function () {
    $container = new Container();
    $container->singleton('service', fn (): string => 'old');
    expect($container->resolve('service'))->toBe('old');

    $container->singleton('service', fn (): string => 'new');

    expect($container->resolve('service'))->toBe('new');
});

test('binding factories receive their current container', function () {
    $container = new Container();
    $received = null;
    $container->bind('service', function (Container $current) use (&$received): string {
        $received = $current;

        return 'built';
    });

    expect($container->resolve('service'))->toBe('built')
        ->and($received)->toBe($container)
        ->and($container->resolve(Container::class))->toBe($container);
});

test('existing zero-argument factories remain supported', function () {
    $container = new Container();
    $container->bind('service', fn (): string => 'built');

    expect($container->resolve('service'))->toBe('built');
});

test('concrete classes and nested dependencies are constructed automatically', function () {
    $container = new Container();

    $branch = $container->resolve(ContainerBranch::class);

    expect($branch)->toBeInstanceOf(ContainerBranch::class)
        ->and($branch->leaf)->toBeInstanceOf(ContainerLeaf::class)
        ->and($container->resolve(ContainerBranch::class))->not->toBe($branch);
});

test('interfaces can be bound to concrete implementations', function () {
    $container = new Container();
    $container->bind(ContainerContract::class, ContainerImplementation::class);

    $consumer = $container->resolve(ContainerContractConsumer::class);

    expect($consumer->service)->toBeInstanceOf(ContainerImplementation::class);
});

test('constructor defaults and unresolved nullable contracts are explicit fallbacks', function () {
    $container = new Container();

    expect($container->resolve(ContainerDefaultConsumer::class)->name)->toBe('default')
        ->and($container->resolve(ContainerNullableConsumer::class)->service)->toBeNull();
});

test('missing bindings and required primitives fail clearly', function () {
    (new Container())->resolve('missing');
})->throws(RuntimeException::class, "Cannot resolve 'missing': no binding or concrete class exists.");

test('an unbound interface fails as a missing binding', function () {
    (new Container())->resolve(ContainerContract::class);
})->throws(RuntimeException::class, "Cannot resolve 'ContainerContract': no binding");

test('an abstract class asks for an implementation binding', function () {
    (new Container())->resolve(ContainerAbstractService::class);
})->throws(RuntimeException::class, 'bind its interface or abstract class to an implementation');

test('required primitive constructor dependencies fail clearly', function () {
    (new Container())->resolve(ContainerPrimitiveConsumer::class);
})->throws(RuntimeException::class, 'Cannot resolve required parameter $name');

test('union constructor dependencies require an explicit design', function () {
    (new Container())->resolve(ContainerUnionConsumer::class);
})->throws(RuntimeException::class, 'union and intersection dependencies require an explicit value');

test('circular dependencies report their resolution path', function () {
    (new Container())->resolve(ContainerCircularA::class);
})->throws(RuntimeException::class, sprintf(
    'Circular dependency detected: %s -> %s -> %s',
    ContainerCircularA::class,
    ContainerCircularB::class,
    ContainerCircularA::class,
));

test('call combines named values class injection defaults and nullable values', function () {
    $container = new Container();

    $result = $container->call(
        fn (
            ContainerLeaf $service,
            string $id,
            string $suffix = 'default',
            ?ContainerContract $optional = null,
        ): array => [$service, $id, $suffix, $optional],
        ['id' => '42'],
    );

    expect($result[0])->toBeInstanceOf(ContainerLeaf::class)
        ->and($result[1])->toBe('42')
        ->and($result[2])->toBe('default')
        ->and($result[3])->toBeNull();
});

test('call fails clearly for an unresolved required value', function () {
    (new Container())->call(fn (string $missing): string => $missing);
})->throws(RuntimeException::class, 'Cannot resolve required parameter $missing while building callable.');

test('call accepts an explicit value for a union parameter', function () {
    $result = (new Container())->call(
        fn (string|int $id): string|int => $id,
        ['id' => '42'],
    );

    expect($result)->toBe('42');
});
