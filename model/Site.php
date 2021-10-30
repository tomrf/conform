<?php

namespace Model;

class Site extends \Tomrf\Snek\Model
{
    /* table */
    protected string $table = 'sites'; /* @todo make attribute or something */
    protected string $primaryKey = 'id'; /* ... */

    protected array $protectedColumns = ['id'];
    protected array $columns = [
        'id' => [
            'type' => 'integer',
            'unsigned' => true
        ],
        'name' => [
            'type' => 'string'
        ]
    ];
}
