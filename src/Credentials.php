<?php

declare(strict_types=1);

namespace Tomrf\Snek;

abstract class Credentials
{
    /**
     * Get the value of dsn.
     */
    abstract public function getDsn(): string;

    /**
     * Get the value of username.
     */
    abstract public function getUsername(): ?string;

    /**
     * Get the value of password.
     */
    abstract public function getPassword(): ?string;
}
