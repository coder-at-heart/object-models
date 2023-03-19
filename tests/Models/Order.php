<?php

namespace CoderAtHeart\ObjectModel\Tests\Models;

use CoderAtHeart\ObjectModel\ObjectModel;
use CoderAtHeart\ObjectModel\Property;

/**
 * @property string id
 * @property array items
 *
 */
class Order extends ObjectModel
{

    public static function properties(): array
    {
        return [
            Property::array('items')->nullable(),
        ];
    }

}
