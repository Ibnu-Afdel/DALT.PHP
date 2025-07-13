<?php

namespace Core;

class Container
{
    protected $bindings = [];

    public function bind($key, $resolver)
    {
        $this->bindings[$key] = $resolver;
    }

    public function resolve($key)
    {
        if (!array_key_exists($key, $this->bindings)) {
            throw new \Exception("No Matching Binding Found For {$key}");
        }

        $resolver = $this->bindings[$key];
        return call_user_func($resolver);

    }
}