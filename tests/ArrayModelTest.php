<?php

use Carbon\Carbon;
use CoderAtHeart\ObjectModel\ArrayModel;
use CoderAtHeart\ObjectModel\Tests\Models\People;
use CoderAtHeart\ObjectModel\Tests\Models\Person;
use CoderAtHeart\ObjectModel\Tests\Models\Phone;

/** @var Person[] $people */
/** @var Person[] $people2 */
$people = new ArrayModel(
    objectModel: Person::class,
    array      : [
        [
            'name' => 'Sunil',
        ],
        [
            'name' => 'Jeff',
            'age'  => 54,
        ],
        [
            'name' => 'Ed',
            'age'  => 34,
        ],
    ]);

test('objects are created successfully', function () use ($people) {
    expect($people)->toHaveCount(3)
        ->and($people[0]->name)->toBe('Sunil')
        ->and($people[1]->name)->toBe('Jeff')
        ->and($people[2]->name)->toBe('Ed')
        ->and($people[2]->birthday->format('jS F Y'))->toBe(Carbon::now()->format('jS F Y'))
        ->and($people[1]->alarm->format('H:i:s'))->toBe('08:00:00')
        ->and($people[1]->home->address_1)->toBeEmpty();
});

test('we can duplicate arrays from arrays and json', function () use ($people) {
    $json    = $people->toJson();
    $people2 = People::create(json: $json);
    expect($people2)->toHaveCount(3)
        ->and($people2[0]->name)->toBe('Sunil')
        ->and($people2[1]->name)->toBe('Jeff')
        ->and($people2[2]->name)->toBe('Ed')
        ->and($people2[2]->birthday->format('jS F Y'))->toBe(Carbon::now()->format('jS F Y'))
        ->and($people2[1]->alarm->format('H:i:s'))->toBe('08:00:00')
        ->and($people2[1]->home->address_1)->toBeEmpty();
});

test('we can add to the  array', function () use ($people) {
    $people[] = Person::create(array: ['name' => 'Marcus', 'alarm' => '05:30:00']);
    expect($people)->toHaveCount(4)
        ->and($people[3]->name)->toBe('Marcus')
        ->and($people[3]->birthday->format('jS F Y'))->toBe(Carbon::now()->format('jS F Y'))
        ->and($people[3]->alarm->format('g:i a'))->toBe('5:30 am')
        ->and($people[3]->home->address_1)->toBeEmpty();
});

test('we can add to the deep array', function () use ($people) {
    $homePhone         = new Phone();
    $homePhone->label  = 'home';
    $homePhone->number = '01234 567890';

    $people[0]->phone_numbers[] = $homePhone;
    expect(count($people[0]->phone_numbers))->toBe(1)
        ->and($people[0]->phone_numbers[0]->label)->toBe('home');

    $people[1]->friends = ['Alice', 'Isabel', 'Mike'];
    expect($people[1]->friends)->toHaveCount(3);
    $people[1]->friends[] = 'Marcus';
    expect($people[1]->friends[3])->toBe('Marcus');
});

test('we can use an array as an array', function () use ($people) {
    foreach ($people as $person) {
        expect($person->name)->toBeString()->not->toBeEmpty()
            ->and($person->birthday)->toBeInstanceOf(Carbon::class)
            ->and($person->alarm)->toBeInstanceOf(Carbon::class)
            ->and($person->friends)->toBeArray();
    }
});
