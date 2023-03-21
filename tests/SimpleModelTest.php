<?php

use CoderAtHeart\ObjectModel\Tests\Models\Order;

it('returns null for an array with nullable()', function () {
    $order = new Order();

    expect($order->items)->toBeNull();
});
