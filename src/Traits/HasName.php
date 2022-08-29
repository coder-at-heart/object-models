<?php

namespace CoderAtHeart\ObjectModel\Traits;

use ReflectionClass;

trait HasName
{

    /**
     * The property name
     *
     * @var string
     */
    protected string $name = '';



    /**
     * get the name of the Object
     *
     * @return string
     */
    public function getName(): string
    {
        if ( ! $this->name) {
            $reflect    = new ReflectionClass(static::class);
            $this->name = $reflect->getShortName();
        }
        return $this->name;
    }




}
