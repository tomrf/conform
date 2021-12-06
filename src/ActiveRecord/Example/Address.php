<?php

declare(strict_types=1);

namespace Tomrf\Snek\ActiveRecord\Example;

class Address extends \Tomrf\Snek\ActiveRecord\Model
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
