<?php

use CoderAtHeart\ObjectModel\Exceptions\ObjectModelException;
use CoderAtHeart\ObjectModel\Tests\Models\Order;
use CoderAtHeart\ObjectModel\Tests\Models\Video;

it('throws an exception if you try and set a property that does not exist', function () {
    $order = new Order();
    expect(fn() => $order->monkey = '')->toThrow(ObjectModelException::class);
});

it('Does not throw an exception if you try and assign a property that does not exist when using ignoreUndefinedProperty', function () {
    $video = new Video();
    expect(fn() => $video->monkey = '')->not->toThrow(ObjectModelException::class);
});
