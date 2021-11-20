<?php

declare(strict_types=1);

namespace Tomrf\Snek;

abstract class Credentials
{
    abstract public function getDsn(): string;

    abstract public function getUsername(): ?string;

    abstract public function getPassword(): ?string;
}
