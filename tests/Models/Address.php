<?php

namespace CoderAtHeart\ObjectModel\Tests\Models;

use CoderAtHeart\ObjectModel\ObjectModel;
use CoderAtHeart\ObjectModel\Property;
use CoderAtHeart\ObjectModel\Tests\ENUMs\Country;

/**
 * @property string address_1
 * @property string address_2
 * @property string city
 * @property string postcode
 * @property Country country_code
 */
class Address extends ObjectModel
{

    public static function properties(): array
    {
        return [
            Property::string('address_1'),
            Property::string('address_2'),
            Property::string('city'),
            Property::enum('country_code', Country::class)->default(Country::GB)->required(),
            Property::string('postcode'),
        ];
    }

}
