<?php

namespace Model;

class Site extends \Tomrf\Snek\Model
{
    protected string $table = 'sites';
    protected string $primaryKey = 'id';

    protected array $protectedColumns = ['id'];
    protected array $columns = [
        'id' => [
            'type' => 'integer',
            'unsigned' => true,
        ],
        'name' => [
            'type' => 'string',
        ],
    ];
}
