<?php

use CoderAtHeart\ObjectModel\Tests\Models\Order;

test('array with nullable are null', function () {
    $order = new Order();

    expect($order->items)->toBeNull();
});
