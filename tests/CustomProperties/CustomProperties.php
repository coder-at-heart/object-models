<?php

namespace CoderAtHeart\ObjectModel\Tests\CustomProperties;

use CoderAtHeart\ObjectModel\Property;

/**
 * @property string label
 * @property string number
 */
class CustomProperties extends Property
{

    /**
     * zipCode
     *
     * @param  string  $name
     *
     * @return static
     */
    public static function zipCode(string $name): Property
    {
        return self::property($name)
            ->addRule('numeric|min:5')
            ->jsonCallback(function ($value) {
                return str_replace(' ', '', $value);
            })
            ->setCallback(function ($value) {
                return (string) $value;
            })
            ->set(null);
    }

}
