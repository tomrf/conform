<?php

namespace Tomrf\Snek;

abstract class Credentials
{
    /**
     * Get the value of dsn.
     */
    abstract public function getDsn();

    /**
     * Get the value of username.
     */
    abstract public function getUsername();

    /**
     * Get the value of password.
     */
    abstract public function getPassword();
}
