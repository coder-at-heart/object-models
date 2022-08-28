<?php

namespace CoderAtHeart\ObjectModel\Models;

use CoderAtHeart\ObjectModel\ObjectModel;
use CoderAtHeart\ObjectModel\Property;

/**
 * @property string name
 * @property bool valid
 * @property array errors
 */
class ObjectValidation extends ObjectModel
{

    public static function properties(): array
    {
        return [
            Property::string('name')->nullable(),
            Property::bool('valid')->default(false)->required(),
            Property::array('errors')->nullable(),
        ];
    }



    public function isNotValid(): bool
    {
        return ! $this->valid;
    }



    public function isValid(): bool
    {
        return $this->valid;
    }

}
