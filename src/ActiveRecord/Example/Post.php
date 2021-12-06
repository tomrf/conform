<?php

declare(strict_types=1);

namespace Tomrf\Snek\ActiveRecord\Example;

class Post extends \Tomrf\Snek\ActiveRecord\Model
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
