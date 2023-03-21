<?php

namespace CoderAtHeart\ObjectModel\Tests\Models;

use CoderAtHeart\ObjectModel\ObjectModel;
use CoderAtHeart\ObjectModel\Property;
use CoderAtHeart\ObjectModel\Traits\IgnoreUndefinedProperties;

/**
 * @property string url
 *
 */
class Video extends ObjectModel
{
    use IgnoreUndefinedProperties;

    public static function properties(): array
    {
        return [
            Property::string('url'),
        ];
    }

}
