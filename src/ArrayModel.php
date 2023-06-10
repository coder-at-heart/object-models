<?php

namespace CoderAtHeart\ObjectModel;

use ArrayAccess;
use CoderAtHeart\ObjectModel\Exceptions\ObjectModelException;
use CoderAtHeart\ObjectModel\Models\ObjectValidation;
use CoderAtHeart\ObjectModel\Traits\CanBeConverted;
use CoderAtHeart\ObjectModel\Traits\HasName;
use Countable;
use Iterator;
use JsonSerializable;

/**
 *  Handy object that handles an array of ObjectModels.
 */
class ArrayModel implements ArrayAccess, Countable, Iterator, JsonSerializable
{

    use HasName, CanBeConverted;

    /**
     * The Object Model that each element represents
     *
     * @var string
     */
    protected string $objectModel = '';

    /**
     * TheProperty that each element represents
     *
     * @var Property
     */
    protected Property $property;

    /**
     * The array of items
     *
     * @var ObjectModel[]|Property[]
     */
    private array $items = [];

    /**
     * Position when accessing this as an array
     *
     * @var int
     */
    private int $index = 0;



    /**
     * Constructor
     *
     * @param  string  $name
     * @param  string|null  $objectModel
     * @param  Property|null  $property
     * @param  array|null  $array
     * @param  string|null  $json
     *
     * @throws ObjectModelException
     */
    public function __construct(string $name = '', string $objectModel = null, Property $property = null, ?array $array = [], ?string $json = null)
    {
        if ($property && $objectModel) {
            throw ObjectModelException::withMessage('Cannot create ArrayModel, expected either objectModel or property. Both specified');
        }

        // If we dont have a deinition already
        if ( ! isset($this->property) && ! $this->objectModel) {
            // then make sure we have one on the constrcutor
            if ( ! $property && ! $objectModel) {
                throw ObjectModelException::withMessage('Cannot create ArrayModel, expected either objectModel or property. Neither specified');
            }
        }

        if ($json && ! empty($array)) {
            throw ObjectModelException::withMessage('Cannot create ArrayModel, expected either array or json data. Both specified.');
        }

        if ($name) {
            $this->name = $name;
        }
        if ($objectModel) {
            $this->objectModel = $objectModel;
        }
        if ($property) {
            $this->property = $property;
        }
        if ($json) {
            $this->fill(json_decode($json, JSON_OBJECT_AS_ARRAY));
        }
        if ($array) {
            $this->fill($array);
        }
    }



    /**
     * static creator
     *
     * @param  string  $name
     * @param  string|null  $objectModel
     * @param  Property|null  $property
     * @param  array|null  $array  $array
     * @param  string|null  $json
     *
     * @return static
     * @throws ObjectModelException
     */
    public static function create(string $name = '', string $objectModel = null, Property $property = null, ?array $array = [], ?string $json = null): static
    {
        return new static(
            name       : $name,
            objectModel: $objectModel,
            property   : $property,
            array      : $array,
            json       : $json
        );
    }



    /**
     * count
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }



    /**
     * get the current element
     *
     * @return ObjectModel
     */
    public function current(): ObjectModel
    {
        return $this->items[$this->index];
    }



    /**
     * fill this array.
     *
     * @param  array|ArrayModel  $array  $array
     *
     * @return $this
     * @throws ObjectModelException
     */
    public function fill(array|ArrayModel $array): static
    {
        if ( ! $this->objectModel && ! isset($this->property)) {
            throw ObjectModelException::withMessage("Cannot create array, no objectModel or property has been set");
        }
        foreach ($array as $arrayKey => $arrayValue) {
            if (
                ($this->objectModel && $arrayValue instanceof $this->objectModel) ||
                (isset($this->property) && is_object($arrayValue) && get_class($arrayValue) === get_class($this->property))
            ) {
                $this[$arrayKey] = $arrayValue;
                continue;
            }
            if ($this->objectModel) {
                /** @var ObjectModel $objectModel */

                $objectModel     = $this->objectModel;
                $this[$arrayKey] = $objectModel::create(array: $arrayValue);
            } else {
                $property        = clone $this->property;
                $this[$arrayKey] = $property->set($arrayValue);
            }
        }

        return $this;
    }



    /**
     * What should be turned into json?
     *
     * @return ObjectModel[]
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }



    /**
     * get the key
     *
     * @return int
     */
    public function key(): int
    {
        return $this->index;
    }



    /**
     * next element
     *
     * @return void
     */
    public function next(): void
    {
        ++$this->index;
    }



    /**
     * @param  mixed  $offset
     *
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->value[$offset]);
    }



    /**
     * @param  mixed  $offset
     *
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return isset($this->property) ? $this->items[$offset]->value : $this->items[$offset];
    }



    /**
     * @param  mixed  $offset
     * @param  mixed  $value
     *
     * @return void
     * @throws ObjectModelException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        // we don't have an instance of the object, they're setting this as an array.
        if ($this->objectModel && ! $value instanceof $this->objectModel) {
            /** @var ObjectModel $objectModel */
            $objectModel = $this->objectModel;
            if (is_array($value)) {
                // We're creating one from an array
                $value = $objectModel::create(array: $value);
            } else {
                // Must be json...  but we should never get here.
                $value = $objectModel::create(json: $value);
            }
        } elseif (isset($this->property) && ! $value instanceof $this->property) {
            $property = clone $this->property;
            $property->set($value);
            $value = $property;
        }
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }



    /**
     * delete an item
     *
     * @param  mixed  $offset
     *
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }



    /**
     * rewind the array
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->index = 0;
    }



    /**
     * @param  string  $name
     *
     * @return ArrayModel
     */
    public function setName(string $name): ArrayModel
    {
        $this->name = $name;
        return $this;
    }



    /**
     * do we have an element at this position?
     *
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->items[$this->index]);
    }



    /**
     * validate all Items
     *
     * @return ObjectValidation
     * @throws ObjectModelException
     */
    public function validate(): ObjectValidation
    {
        $valid  = true;
        $errors = [];
        $name   = $this->getName();
        foreach ($this->items as $index => $item) {
            $validationStatus = $item->validate();
            if ( ! $validationStatus->valid) {
                $valid                       = false;
                $errors["{$name}[{$index}]"] = $validationStatus->errors;
            }
        }
        return ObjectValidation::create(array: [
            'name'   => $name,
            'valid'  => $valid,
            'errors' => $errors,
        ]);
    }

}
