<?php

use Faker\Generator as Faker;
use Tests\Models\Reply;
use Tests\Models\User;

$factory->define(Reply::class, function (Faker $faker) {
    return [
        'user_id' => factory(User::class)->create()->id,
        'likes_count' => 1
    ];
});
