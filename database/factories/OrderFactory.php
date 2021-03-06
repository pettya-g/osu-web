<?php

$factory->define(App\Models\Store\Order::class, function (Faker\Generator $faker) {
    return  [
        'user_id' => function () {
            return factory(App\Models\User::class)->create(['user_sig' => ''])->user_id;
        },
    ];
});

$factory->defineAs(App\Models\Store\Order::class, 'paid', function (Faker\Generator $faker) use ($factory) {
    $raw = $factory->raw(App\Models\Store\Order::class);
    $date = Carbon\Carbon::now();

    return array_merge($raw, [
        'paid_at' => $date,
        'status' => 'paid',
        'transaction_id' => "test-{$date->timestamp}",
    ]);
});

$factory->state(App\Models\Store\Order::class, 'incart', function (Faker\Generator $faker) {
    return [
        'status' => 'incart',
    ];
});

$factory->state(App\Models\Store\Order::class, 'processing', function (Faker\Generator $faker) {
    return [
        'status' => 'processing',
    ];
});

$factory->state(App\Models\Store\Order::class, 'checkout', function (Faker\Generator $faker) {
    return [
        'status' => 'checkout',
    ];
});
