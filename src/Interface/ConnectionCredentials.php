<?php

declare(strict_types=1);

namespace Tomrf\Snek\Interface;

interface ConnectionCredentials
{
    public function getDsn(): string;

    public function getUsername(): ?string;

    public function getPassword(): ?string;
}
