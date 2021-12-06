<?php

declare(strict_types=1);

namespace Tomrf\Conform\Interface;

interface ConnectionCredentialsInterface
{
    public function getDsn(): string;

    public function getUsername(): ?string;

    public function getPassword(): ?string;
}
