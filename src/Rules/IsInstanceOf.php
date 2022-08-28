<?php

namespace CoderAtHeart\ObjectModel\Rules;

use Illuminate\Contracts\Validation\Rule;

class IsInstanceOf implements Rule
{

    /**
     * The nme of the class the property should be an instance of
     *
     * @var string
     */
    private string $class;



    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(string $class)
    {
        $this->class = $class;
    }



    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return ':attribute: is not an instance of ' . $this->class;
    }



    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return $value instanceof $this->class;
    }
}
