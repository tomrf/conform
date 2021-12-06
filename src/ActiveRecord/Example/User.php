<?php

declare(strict_types=1);

namespace Tomrf\Conform\ActiveRecord\Example;

use Tomrf\Conform\Abstract\QueryBuilder;
use Tomrf\Conform\ActiveRecord\ActiveModel;

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

    // public function customer(): QueryBuilder
    // {
    //     return $this->belongsTo(Customer::class);
    // }

    protected function onBeforePersist(): bool
    {
        if (null === $this->get('ref')) {
            $this->set('ref', md5(random_bytes(32)));
        }

        return true;
    }
}
