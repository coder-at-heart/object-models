<?php

namespace CoderAtHeart\ObjectModel\Tests\Models;

use CoderAtHeart\ObjectModel\ObjectModel;
use CoderAtHeart\ObjectModel\Tests\CustomProperties\CustomProperties;

/**
 * @property string address_1
 * @property string address_2
 * @property string city
 * @property string zip
 */
class AddressUS extends ObjectModel
{

    public static function properties(): array
    {
        return [
            CustomProperties::string('address_1'),
            CustomProperties::string('address_2'),
            CustomProperties::string('city'),
            CustomProperties::zipCode('zip'),
        ];
    }

}
