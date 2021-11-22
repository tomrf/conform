<?php

declare(strict_types=1);

namespace Model;

class Address extends \Tomrf\Snek\Model
{
    protected string $table = 'address';
    protected string $primaryKey = 'id';

    protected array $protectedColumns = ['id'];
    protected array $columns = [
        'id' => [
            'type' => 'integer',
            'unsigned' => true,
        ],
    ];
}
