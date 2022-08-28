<?php

namespace CoderAtHeart\ObjectModel;

use JsonSerializable;

/**
 *  Handy object that handles an array of ObjectModels.
 */
class Properties implements JsonSerializable
{

    /**
     * the array of items properties
     *
     * @var Property[]
     */
    protected array $properties = [];



    /**
     * @param  Property[]  $definition
     */
    public function __construct(array $definition)
    {
        foreach ($definition as $property) {
            $this->properties[$property->getName()] = $property;
        }
    }



    /**
     * @param  array  $definition
     *
     * @return static
     */
    public static function with(array $definition): static
    {
        return new static($definition);
    }



    public function &get($key): mixed
    {
        return $this->properties[$key]->value;
    }



    /**
     * @return Property[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }



    /**
     * Check if this has a property
     *
     * @param $key
     *
     * @return bool
     */
    public function has($key): bool
    {
        return array_key_exists($key, $this->properties);
    }



    public function jsonSerialize(): array
    {
        return $this->properties;
    }



    public function set($key, $value): void
    {
        $this->properties[$key]->set($value);
    }
}
