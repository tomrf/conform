<?php

declare(strict_types=1);

namespace Model;

use Tomrf\Snek\ActiveRecord\ActiveModel;
use Tomrf\Snek\Connection;
use Tomrf\Snek\QueryBuilder;

class User extends ActiveModel
{
    protected string $table = 'user';
    protected string $primaryKey = 'id';

    protected array $protectedColumns = ['id', 'password'];
    protected array $columns = [
        'id' => [
            'type' => 'integer',
            'unsigned' => true,
        ],
        'username' => [
            'type' => 'string',
        ],
        'password' => [
            'type' => 'string',
            'default' => '-',
        ],
    ];

    public function address(): QueryBuilder
    {
        return $this->hasOne(Address::class);
    }

    public function post(): QueryBuilder
    {
        return $this->hasMany(Post::class);
    }

    public function customer(): QueryBuilder
    {
        return $this->belongsTo(Customer::class);
    }

    public static function new(?Connection $connection = null): self
    {
        return parent::new($connection);
    }

    protected function onBeforePersist(): bool
    {
        if (null === $this->get('ref')) {
            $this->set('ref', md5(random_bytes(32)));
        }

        return true;
    }
}
