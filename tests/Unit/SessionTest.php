<?php

declare(strict_types=1);

use Core\Session;

test('persistent values distinguish missing null and non-null data', function () {
    Session::put('name', 'DALT');
    Session::put('nullable', null);

    expect(Session::get('name'))->toBe('DALT')
        ->and(Session::exists('name'))->toBeTrue()
        ->and(Session::has('name'))->toBeTrue()
        ->and(Session::get('nullable', 'fallback'))->toBeNull()
        ->and(Session::exists('nullable'))->toBeTrue()
        ->and(Session::has('nullable'))->toBeFalse()
        ->and(Session::get('missing', 'fallback'))->toBe('fallback')
        ->and(Session::exists('missing'))->toBeFalse();
});

test('flash data is immediate available next request and then expires', function () {
    Session::ageFlashData();
    Session::flash('status', 'saved');

    expect(Session::get('status'))->toBe('saved')
        ->and(Session::getFlash('status'))->toBe('saved');

    Session::ageFlashData();
    expect(Session::get('status'))->toBe('saved');

    Session::ageFlashData();
    expect(Session::get('status', 'expired'))->toBe('expired')
        ->and(Session::exists('status'))->toBeFalse();
});

test('flash temporarily takes precedence over a persistent value', function () {
    Session::put('status', 'persistent');
    Session::flash('status', 'temporary');

    expect(Session::get('status'))->toBe('temporary');

    Session::ageFlashData();
    expect(Session::get('status'))->toBe('temporary');

    Session::ageFlashData();
    expect(Session::get('status'))->toBe('persistent');
});

test('null flash values remain present without becoming has values', function () {
    Session::flash('nullable', null);

    expect(Session::get('nullable', 'fallback'))->toBeNull()
        ->and(Session::exists('nullable'))->toBeTrue()
        ->and(Session::has('nullable'))->toBeFalse();
});

test('now data is available only during the current request', function () {
    Session::ageFlashData();
    Session::now('notice', 'current');

    expect(Session::get('notice'))->toBe('current');

    Session::ageFlashData();
    expect(Session::get('notice', 'expired'))->toBe('expired');
});

test('keep extends selected old flash for one request', function () {
    Session::flash('kept', 'yes');
    Session::flash('expired', 'no');
    Session::ageFlashData();
    Session::keep('kept');
    Session::ageFlashData();

    expect(Session::get('kept'))->toBe('yes')
        ->and(Session::get('expired', 'gone'))->toBe('gone');

    Session::ageFlashData();
    expect(Session::get('kept', 'gone'))->toBe('gone');
});

test('reflash extends all old flash for one request', function () {
    Session::flash('first', 1);
    Session::flash('second', 2);
    Session::ageFlashData();
    Session::reflash();
    Session::ageFlashData();

    expect(Session::get('first'))->toBe(1)
        ->and(Session::get('second'))->toBe(2);
});

test('flashing an old key again replaces its value and lifetime', function () {
    Session::flash('status', 'old value');
    Session::ageFlashData();
    Session::flash('status', 'new value');
    Session::ageFlashData();

    expect(Session::get('status'))->toBe('new value');
});

test('legacy flat flash data migrates for one readable request', function () {
    $_SESSION['_flash'] = ['status' => 'legacy'];

    Session::ageFlashData();
    expect(Session::get('status'))->toBe('legacy');

    Session::ageFlashData();
    expect(Session::get('status', 'gone'))->toBe('gone');
});

test('legacy flash keys named new or old are not lost during migration', function () {
    $_SESSION['_flash'] = ['new' => 'first', 'old' => 'second'];

    Session::ageFlashData();

    expect(Session::get('new'))->toBe('first')
        ->and(Session::get('old'))->toBe('second');
});

test('a redirect response does not erase newly flashed validation state', function () {
    Session::ageFlashData();
    Session::flash('errors', ['email' => 'Required']);
    $response = redirect('/form');

    expect($response->status())->toBe(302);

    Session::ageFlashData();
    expect(Session::get('errors'))->toBe(['email' => 'Required']);
});

test('forget removes persistent new and old forms of selected keys', function () {
    Session::put('persistent', 'value');
    Session::flash('new', 'value');
    Session::flash('old', 'value');
    Session::ageFlashData();
    Session::flash('new', 'value');

    Session::forget(['persistent', 'new', 'old']);

    expect(Session::exists('persistent'))->toBeFalse()
        ->and(Session::exists('new'))->toBeFalse()
        ->and(Session::exists('old'))->toBeFalse();
});

test('unflash and flush have distinct scopes', function () {
    Session::put('persistent', 'value');
    Session::flash('notice', 'value');
    Session::unflash();

    expect(Session::get('persistent'))->toBe('value')
        ->and(Session::exists('notice'))->toBeFalse();

    Session::flush();
    expect($_SESSION)->toBe([]);
});

test('the internal flash key cannot be overwritten through the public API', function () {
    Session::put('_flash', 'corrupt');
})->throws(InvalidArgumentException::class, "reserved '_flash' key");

test('regeneration requires an active native session', function () {
    Session::regenerate();
})->throws(LogicException::class, 'Cannot regenerate an inactive session.');
