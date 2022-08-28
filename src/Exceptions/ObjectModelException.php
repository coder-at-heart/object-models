<?php

namespace CoderAtHeart\ObjectModel\Exceptions;

use Exception;

class ObjectModelException extends Exception
{

    /**
     * Static constructor
     *
     * @param $message
     *
     * @return self
     */
    public static function withMessage($message): self
    {
        return new static($message);
    }

}
