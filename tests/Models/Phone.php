<?php

namespace CoderAtHeart\ObjectModel\Tests\Models;

use CoderAtHeart\ObjectModel\ObjectModel;
use CoderAtHeart\ObjectModel\Property;

/**
 * @property string label
 * @property string number
 */
class Phone extends ObjectModel
{

    public static function properties(): array
    {
        return [
            Property::string('label'),
            Property::string('number'),
        ];
    }

}
