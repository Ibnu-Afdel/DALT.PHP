<?php

namespace Core;

class Container
{
    protected $bindings = [];
    protected $instances = [];

    public function bind($key, $resolver)
    {
        $this->bindings[$key] = $resolver;
    }

    public function resolve($key)
    {
        if (!array_key_exists($key, $this->bindings)) {
            throw new \Exception("No Matching Binding Found For {$key}");
        }

        if (!isset($this->instances[$key])) {
            $this->instances[$key] = call_user_func($this->bindings[$key]);
        }

        return $this->instances[$key];
    }
}