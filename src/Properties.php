<?php

namespace CoderAtHeart\ObjectModel;

use JsonSerializable;

/**
 *  Handy object that handles an array of Properties.
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
     * static constructor
     *
     * @param  array  $definition
     *
     * @return static
     */
    public static function with(array $definition): static
    {
        return new static($definition);
    }



    /**
     * Return the value of the property by reference for it can be modified.
     *
     * @param $key
     *
     * @return mixed
     */
    public function &get($key): mixed
    {
        return $this->properties[$key]->value;
    }



    /**
     * Get all the properties for this definition
     *
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



    /**
     * What should we stash to json?
     *
     * @return Property[]
     */
    public function jsonSerialize(): array
    {
        return $this->properties;
    }



    /**
     * set the value of a property
     *
     * @param $key
     * @param $value
     *
     * @return void
     */
    public function set($key, $value): void
    {
        $this->properties[$key]->set($value);
    }
}
