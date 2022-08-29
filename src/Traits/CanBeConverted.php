<?php

namespace CoderAtHeart\ObjectModel\Traits;

use ReflectionClass;

trait CanBeConverted
{



    /**
     * Turn this into an Array
     *
     * @return array
     */
    public function toArray(): array
    {
        return json_decode($this->toJson(), JSON_OBJECT_AS_ARRAY);
    }



    /**
     * return the contents of the Object as json
     *
     * @param  int  $flags
     *
     * @return string
     */
    public function toJson(int $flags = 0): string
    {
        return json_encode($this, $flags);
    }

}
