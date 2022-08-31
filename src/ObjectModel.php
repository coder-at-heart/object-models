<?php

namespace CoderAtHeart\ObjectModel;

use CoderAtHeart\ObjectModel\Exceptions\ObjectModelException;
use CoderAtHeart\ObjectModel\Models\ObjectValidation;
use CoderAtHeart\ObjectModel\Traits\CanBeConverted;
use CoderAtHeart\ObjectModel\Traits\HasName;
use JsonSerializable;

class ObjectModel implements JsonSerializable
{

    use HasName, CanBeConverted;

    /**
     * Used to store the post data
     *
     */
    protected Properties $_properties;



    /**
     * Constructor
     *
     * @param  array|null  $array  $array
     * @param  string|null  $json
     *
     * @throws ObjectModelException
     */
    public function __construct(?array $array = [], ?string $json = '')
    {
        if ($json && ! empty($array)) {
            throw ObjectModelException::withMessage('Cannot create ObjectModel, expected either array or json data. Both specified.');
        }

        $this->_properties = new Properties(static::properties());

        if ($json) {
            $this->fill(json_decode($json, JSON_OBJECT_AS_ARRAY));
        }
        if ( ! empty($array)) {
            $this->fill($array);
        }
    }



    /**
     * Static creator to instantiate this object from either an array or Json
     *
     * Object::CreateFrom(array: $arrayData);
     * Object::CreateFrom(json: $jsonString);
     *
     * @param  array|null  $array  $array
     * @param  string|null  $json
     *
     * @return static
     * @throws ObjectModelException
     */
    public static function create(?array $array = [], ?string $json = ''): static
    {
        return new static(
            array: $array,
            json : $json
        );
    }



    /**
     * Define this Object.
     *
     * @return property[]
     */
    public static function properties(): array
    {
        return [
            Property::string('key'),
            Property::mixed('value'),
        ];
    }



    /**
     * magic getter - note the use of the & - this pulls the value by reference
     * and allows us to magically use it as an array without overload errors
     *
     * $object->array[] = "some value"
     *
     * @param $var
     *
     * @return mixed
     * @throws ObjectModelException
     */
    public function &__get($var)
    {
        if ( ! $this->_properties->has($var)) {
            throw ObjectModelException::withMessage("$var doesn't exist on model:".static::class);
        }
        return $this->_properties->get($var);
    }



    /**
     * used when isset is called
     *
     * @param $var
     *
     * @return bool
     */
    public function __isset($var): bool
    {
        return $this->_properties->has($var);
    }



    /**
     * magic set
     *
     * @param $var
     * @param $value
     *
     * @throws ObjectModelException
     */
    public function __set($var, $value)
    {
        $this->set($var, $value);
    }



    /**
     * Fill data properties from an array
     *
     * @param  array  $array
     *
     * @return static
     * @throws ObjectModelException
     */
    public function fill(array $array): static
    {
        foreach ($array as $field => $value) {
            $this->set($field, $value);
        }

        return $this;
    }



    /**
     * get a property's value
     *
     * @param $key
     *
     * @return mixed
     * @throws ObjectModelException
     */
    public function get($key): mixed
    {
        if ( ! $this->_properties->has($key)) {
            throw ObjectModelException::withMessage("property $key not defined for this object model ".get_class($this));
        }

        return $this->_properties->get($key);
    }



    /**
     * get the properties for this object (an array of names)
     *
     * @return Property[]
     */
    public function getProperties(): array
    {
        return $this->_properties->getProperties();
    }



    public function getRules(): array
    {
        $rules = [];
        foreach ($this->_properties->getProperties() as $property) {
            $rules[$property->getName()] = $property->getRules();
        }
        return $rules;
    }



    /**
     * check if a property has been set
     *
     * @param $key
     *
     * @return bool
     */
    public function has($key): bool
    {
        return $this->_properties->has($key);
    }



    /**
     * What dod we convert to Json?
     *
     * @return Properties
     */
    public function jsonSerialize(): Properties
    {
        return $this->_properties;
    }



    /**
     * Set a property
     *
     * @param $key
     * @param $value
     *
     * @return void
     * @throws ObjectModelException
     */
    public function set($key, $value = null): void
    {
        if ( ! $this->_properties->has($key)) {
            throw ObjectModelException::withMessage("property $key not defined for this object model ".get_class($this));
        }

        $this->_properties->set($key, $value);
    }



    /**
     * return this property so it can be updated
     *
     * $model->updateProperty('name')->jsonCallback(function ($value) {...})
     *
     * @param  string  $name
     *
     * @return Property
     */
    public function updateProperty(string $name): Property
    {
        return $this->_properties[$name];
    }



    /**
     * Validate this Object
     *
     * @throws Exceptions\ObjectModelException
     */
    public function validate(): ObjectValidation
    {
        $valid  = true;
        $errors = [];

        foreach ($this->getProperties() as $property) {
            $validation = $property->validate();
            if ( ! $validation->valid) {
                $valid = false;
                if ( ! empty($validation->errors)) {
                    $errors[] = $validation->errors;
                }
            }
        }

        return ObjectValidation::create(array: [
            'name'   => static::class,
            'valid'  => $valid,
            'errors' => $errors,
        ]);
    }

}
