<?php

declare(strict_types=1);

namespace Tests;

use Core\App;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** @var array<string, mixed> */
    private array $serverBeforeTest;

    /** @var array<string, mixed> */
    private array $getBeforeTest;

    /** @var array<string, mixed> */
    private array $postBeforeTest;

    /** @var array<string, mixed> */
    private array $cookieBeforeTest;

    /** @var array<string, mixed> */
    private array $filesBeforeTest;

    /** @var array<string, mixed> */
    private array $requestBeforeTest;

    /** @var array<string, mixed> */
    private array $environmentBeforeTest;

    /** @var array<string, mixed> */
    private array $sessionBeforeTest;

    private mixed $containerBeforeTest;

    private int|false $statusBeforeTest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serverBeforeTest = $_SERVER;
        $this->getBeforeTest = $_GET;
        $this->postBeforeTest = $_POST;
        $this->cookieBeforeTest = $_COOKIE;
        $this->filesBeforeTest = $_FILES;
        $this->requestBeforeTest = $_REQUEST;
        $this->environmentBeforeTest = $_ENV;
        $this->sessionBeforeTest = $_SESSION ?? [];
        $this->containerBeforeTest = App::containerOrNull();
        $this->statusBeforeTest = http_response_code();

        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
        $_FILES = [];
        $_REQUEST = [];
        $_SESSION = [];
        http_response_code(200);
        header_remove();
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBeforeTest;
        $_GET = $this->getBeforeTest;
        $_POST = $this->postBeforeTest;
        $_COOKIE = $this->cookieBeforeTest;
        $_FILES = $this->filesBeforeTest;
        $_REQUEST = $this->requestBeforeTest;
        $_ENV = $this->environmentBeforeTest;
        $_SESSION = $this->sessionBeforeTest;
        if ($this->containerBeforeTest instanceof \Core\Container) {
            App::setContainer($this->containerBeforeTest);
        } else {
            App::forgetContainer();
        }
        header_remove();
        http_response_code(is_int($this->statusBeforeTest) ? $this->statusBeforeTest : 200);

        parent::tearDown();
    }
}
