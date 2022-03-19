<?php

declare(strict_types=1);

namespace Tomrf\Conform\Interface;

interface ConnectionInterface
{
    public function isConnected(): bool;
}
