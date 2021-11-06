<?php

namespace Tomrf\Snek;

class Factory
{
    protected string $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }

    public function make(...$params)
    {
        return new $this->class(...$params);
    }
}
