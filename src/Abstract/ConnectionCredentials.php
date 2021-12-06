<?php

declare(strict_types=1);

namespace Tomrf\Snek\Abstract;

use Tomrf\Snek\Interface\ConnectionCredentialsInterface;

abstract class ConnectionCredentials implements ConnectionCredentialsInterface
{
    abstract public function getDsn(): string;

    abstract public function getUsername(): ?string;

    abstract public function getPassword(): ?string;
}
