<?php

namespace Model;

class User extends \Tomrf\Snek\Model
{
    /* table */
    protected string $table = 'user';
    protected string $primaryKey = 'id';

    protected array $protectedColumns = ['id'];
    protected array $columns = [
        'id' => [
            'type' => 'integer',
            'unsigned' => true
        ],
        'username' => [
            'type' => 'string'
        ]
    ];
}
