<?php

declare(strict_types=1);

namespace Tomrf\Conform\Abstract;

use Tomrf\Conform\Interface\ConnectionCredentialsInterface;

abstract class ConnectionCredentials implements ConnectionCredentialsInterface
{
    abstract public function getDsn(): string;

    abstract public function getUsername(): ?string;

    abstract public function getPassword(): ?string;
}
