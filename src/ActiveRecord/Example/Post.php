<?php

declare(strict_types=1);

namespace Tomrf\Conform\ActiveRecord\Example;

class Post extends \Tomrf\Conform\ActiveRecord\Model
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
