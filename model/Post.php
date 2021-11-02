<?php

namespace Model;

class Post extends \Tomrf\Snek\Model
{
    protected string $table = 'post';
    protected string $primaryKey = 'id';

    protected array $protectedColumns = ['id'];
    protected array $columns = [
        'id' => [
            'type' => 'integer',
            'unsigned' => true,
        ],
        'title' => [
            'type' => 'string',
        ],
    ];
}
