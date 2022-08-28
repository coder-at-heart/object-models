<?php

namespace CoderAtHeart\ObjectModel\Tests\Models;

use Carbon\Carbon;
use CoderAtHeart\ObjectModel\ObjectModel;
use CoderAtHeart\ObjectModel\Property;
use CoderAtHeart\ObjectModel\Tests\Objects\Invoice;

/**
 * @property string name
 * @property int age
 * @property string email
 * @property Address home
 * @property Address business
 * @property Phone[] phone_numbers
 * @property Carbon birthday
 * @property Carbon alarm
 * @property bool subscribed
 * @property array friends
 */
class Person extends ObjectModel
{

    public static function properties(): array
    {
        return [
            Property::string('name'),
            Property::integer('age'),
            Property::email('email'),
            Property::objectModel('home', Address::class),
            Property::objectModel('business', Address::class),
            Property::array('phone_numbers', PhoneNumbers::class),
            Property::date('birthday'),
            Property::time('alarm')->default('08:00:00'),
            Property::bool('subscribed')->default(false),
            Property::array('friends'),
        ];
    }

}
