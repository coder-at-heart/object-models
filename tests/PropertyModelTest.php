<?php

use Carbon\Carbon;
use CoderAtHeart\ObjectModel\ArrayModel;
use CoderAtHeart\ObjectModel\Property;
use CoderAtHeart\ObjectModel\Tests\Models\People;
use CoderAtHeart\ObjectModel\Tests\Models\Person;
use CoderAtHeart\ObjectModel\Tests\Models\Phone;

/** @var Carbon[] $dates */

$dates = new ArrayModel(
    property: Property::dateTime('datetime'),
    array      : [
     '2011-01-01 01:01:01',
     '2012-02-02 02:02:02',
     '2013-03-03 03:03:03',
     '2014-04-06 04:04:04',
     '2015-05-06 05:05:05',
    ]);

test('array is created successfully', function () use ($dates) {
    expect($dates)->toHaveCount(5);
    expect($dates[0])->toBeInstanceOf(Carbon::class);
});

test('we can add a value to an array', function() use($dates) {
   $dates[] = Carbon::now();
    expect($dates)->toHaveCount(6);
    expect($dates[5])->toBeInstanceOf(Carbon::class);
});


test('we can change an element', function() use($dates) {
    $dates[0] ='1972-03-29 10:10:00';
    expect($dates)->toHaveCount(6);
    expect($dates[0])->toBeInstanceOf(Carbon::class);
});

