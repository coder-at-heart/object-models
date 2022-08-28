<?php

namespace CoderAtHeart\ObjectModel;

use ArrayAccess;
use CoderAtHeart\ObjectModel\Exceptions\ObjectModelException;
use CoderAtHeart\ObjectModel\Models\ObjectValidation;
use CoderAtHeart\ObjectModel\Traits\ConvertsTo;
use Countable;
use Iterator;
use JsonSerializable;

/**
 *  Handy object that handles an array of ObjectModels.
 */
class ArrayModel implements ArrayAccess, Countable, Iterator, JsonSerializable
{

    use ConvertsTo;

    /**
     * The name of the property
     *
     * @var string
     */
    protected string $name = '';

    /**
     * The Object Model that each element represents
     *
     * @var string
     */
    protected string $objectModel;

    /**
     * The array of items
     *
     * @var ObjectModel[]
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
     */
    public function __construct(string $name = '')
    {
        if ($name) {
            $this->name = $name;
        }
    }



    /**
     * Static creator to instantiate this opbject from either an array or Json
     *
     * Object::CreateFrom(array: $arrayData);
     * Object::CreateFrom(json: $jsonString);
     *
     * @param  array  $array
     * @param  mixed  $json
     *
     * @return static
     * @throws ObjectModelException
     */
    public static function createFrom(array $array = [], mixed $json = ''): static
    {
        $object = new static();
        if ( ! empty($json)) {
            $array = json_decode($json, JSON_OBJECT_AS_ARRAY);
        }
        if (empty($array)) {
            return $object;
        }
        return $object->fill($array);
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
     * @param  array  $array
     *
     * @return $this
     * @throws ObjectModelException
     */
    public function fill(array $array): static
    {
        foreach ($array as $arrayKey => $arrayValue) {
            if ($arrayValue instanceof $this->objectModel) {
                $this[$arrayKey] = $arrayValue;
                continue;
            }
            /** @var ObjectModel $objectModel */
            $objectModel = $this->objectModel;

            $this[$arrayKey] = $objectModel::createFrom(array: $arrayValue);
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
     * @return ObjectModel
     */
    public function offsetGet(mixed $offset): ObjectModel
    {
        return $this->items[$offset];
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
        $objectModel = $this->objectModel;
        /**
         * @var ObjectModel $objectModel
         */
        // we don't have an instance of the object, they're setting this as an array.
        if ( ! $value instanceof $this->objectModel) {
            if (is_array($value)) {
                // We're creating one from an array
                $value = $objectModel::createFrom(array: $value);
            } else {
                // Must be json...  but we should never get here.
                $value = $objectModel::createFrom(json: $value);
            }
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
        return ObjectValidation::createFrom(array: [
            'name'   => $name,
            'valid'  => $valid,
            'errors' => $errors,
        ]);
    }

}
