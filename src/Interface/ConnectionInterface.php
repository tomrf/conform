<?php

declare(strict_types=1);

namespace Tomrf\Conform\Interface;

use PDO;

interface ConnectionInterface
{
    /**
     * Get PDO options array for this connection.
     *
     * @return null|array<int, int>
     */
    public function getOptions(): ?array;

    public function isConnected(): bool;
}
