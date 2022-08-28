<?php

namespace CoderAtHeart\ObjectModel;

use BackedEnum;
use Carbon\Carbon;
use Closure;
use CoderAtHeart\ObjectModel\Exceptions\ObjectModelException;
use CoderAtHeart\ObjectModel\Models\ObjectValidation;
use CoderAtHeart\ObjectModel\Rules\IsInstanceOf;
use DateTimeZone;
use Illuminate\Support\Facades\Validator;
use JsonSerializable;

class Property implements JsonSerializable
{

    /**
     * The value for this property.
     * This is public in order for it can be pulled by reference see &__get in reservation Object Model
     *
     * @var mixed|null
     */
    public mixed $value = null;

    /**
     * The rules of property
     *
     * @var array
     */
    private array $rules = [];

    /**
     * The property name
     *
     * @var string
     */
    private string $name;

    /**
     * the callback that converts this to be stored as json
     *
     * @var Closure
     */
    private Closure $json_callback;

    /**
     * What to do when the value is set.
     *
     * @var Closure
     */
    private Closure $set_callback;



    /**
     * constructor and set some defaults
     *
     * @param  string  $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->jsonCallback(function ($value) {
            return $value;
        });
        $this->setCallback(function ($value) {
            return $value;
        });
    }



    /**
     * An Array property
     *
     * @param  string  $name
     * @param  string|null  $arrayModel
     *
     * @return static
     */
    public static function array(string $name, string $arrayModel = null): Property
    {
        // If there is no object model, then this is just a plain array of data.
        if ($arrayModel) {
            $array = new $arrayModel(name: $name);
            $rule    = new IsInstanceOf($arrayModel);
        } else {
            $array = [];
            $rule    = 'array';
        }

        return self::property($name)
            ->addRule($rule)
            ->setCallback(function ($value) use ($name, $array, $arrayModel) {
                if (is_null($value)) {
                    return $array;
                }

                if ( ! is_countable($value)) {
                    throw ObjectModelException::withMessage("{$name} expected an array of data");
                }

                if ($arrayModel && count($value) > 0) {
                    return $array->fill($value);
                }

                return $value;
            })
            ->set(null);
    }



    /**
     * A Bool property
     *
     * @param  string  $name
     *
     * @return static
     */
    public static function bool(string $name): Property
    {
        return self::property($name)
            ->addRule('bool')
            ->setCallback(function ($value) {
                return (bool) $value;
            })
            ->set(null);
    }



    /**
     * Date - but using Carbon
     *
     * @param  string  $name
     * @param  string  $format
     * @param  string  $timezone
     *
     * @return static
     */
    public static function date(string $name, string $format = 'Y-m-d', string $timezone = 'UTC'): Property
    {
        return self::dateTime($name, $format, $timezone);
    }



    /**
     * Date - but using Carbon
     *
     * @param  string  $name
     * @param  string  $format
     * @param  string  $timezone
     *
     * @return static
     */
    public static function dateTime(string $name, string $format = 'Y-m-d H:i:s', string $timezone = 'UTC'): Property
    {
        $timezone = new DateTimeZone($timezone);

        return self::property($name)
            ->addRule(new IsInstanceOf(Carbon::class))
            ->jsonCallback(function (Carbon $value) use ($format) {
                return $value?->format($format);
            })
            ->setCallback(function ($value) use ($name, $format, $timezone) {
                if (is_null($value)) {
                    return new Carbon(tz: $timezone);
                }

                if ($value instanceof Carbon) {
                    return $value;
                }
                if (is_object($value)) {
                    throw ObjectModelException::withMessage("{$name} expected Carbon received instance of ".get_class($value));
                }
                return Carbon::createFromFormat($format, $value)->shiftTimezone($timezone);
            })
            ->set(null);
    }



    /**
     * An Email string
     *
     * @param  string  $name
     *
     * @return static
     */
    public static function email(string $name): Property
    {
        return self::property($name)
            ->addRule('email')
            ->setCallback(function ($value) {
                return $value ? strtolower(trim($value)) : null;
            })
            ->set(null);
    }



    /**
     * An ENUM property
     *
     * @param  string  $name
     * @param  string  $enum
     *
     * @return static
     */
    public static function enum(string $name, string $enum): Property
    {
        return self::property($name)
            ->addRule(new IsInstanceOf($enum))
            ->jsonCallback(function ($value) use ($enum) {
                if ($value instanceof $enum) {
                    return $value->value;
                }
                return null;
            })
            ->setCallback(function ($value) use ($name, $enum) {
                if (is_null($value)) {
                    return null;
                }

                /** @var BackedEnum $enum */
                if ($value instanceof $enum) {
                    return $value;
                }
                if ($value instanceof BackedEnum) {
                    throw ObjectModelException::withMessage("{$name} expected class {$enum} received instance of ".get_class($value));
                }
                return $enum::from($value);
            })
            ->set(null);
    }



    /**
     * A decimal property
     *
     * @param  string  $name
     *
     * @return static
     */
    public static function float(string $name): Property
    {
        return self::property($name)
            ->addRule('numeric')
            ->setCallback(function ($value) {
                return $value ? (float) $value : null;
            })
            ->set(null);
    }



    /**
     * An integer property
     *
     * @param  string  $name
     *
     * @return static
     */
    public static function integer(string $name): Property
    {
        return self::property($name)
            ->addRule('integer')
            ->setCallback(function ($value) {
                return $value ? (int) $value : null;
            })
            ->set(null);
    }



    /**
     * A mixed property
     *
     * @param  string  $name
     *
     * @return static
     */
    public static function mixed(string $name): Property
    {
        return self::property($name)
            ->setCallback(function ($value) {
                return $value;
            })
            ->set(null);
    }



    /**
     * An Object property
     *
     * @param  string  $name
     * @param  string  $class
     *
     * @return static
     */
    public static function object(string $name, string $class): Property
    {
        return self::property($name)
            ->addRule(new IsInstanceOf($class))
            ->jsonCallback(function ($value) {
                return base64_encode(serialize($value));
            })
            ->setCallback(function ($value) use ($name, $class) {
                if (is_null($value)) {
                    return new $class();
                }
                if ($value instanceof $class) {
                    return $value;
                }
                if (is_object($value)) {
                    throw ObjectModelException::withMessage("{$name} expected class {$class} received instance of ".get_class($value));
                }

                return unserialize(base64_decode($value));
            })
            ->set(null);
    }



    /**
     * a property that is another object Model
     *
     * @param  string  $name
     * @param  string  $objectModel
     *
     * @return static
     */
    public static function objectModel(string $name, string $objectModel): Property
    {
        return self::property($name)
            ->addRule(new IsInstanceOf($objectModel))
            ->setCallback(function ($value) use ($name, $objectModel) {

                if (is_null($value)) {
                    return new $objectModel;
                }

                /** @var ObjectModel $objectModel */
                if ($value instanceof $objectModel) {
                    return $value;
                }

                if (is_object($value)) {
                    throw ObjectModelException::withMessage("{$name} expected class {$objectModel} received instance of ".get_class($value));
                }

                return $objectModel::createFrom(array: $value);
            })
            ->set(null);
    }



    public static function property(string $name): Property
    {
        $property = new self($name);
        return $property->setCallback(function ($value) {
            return (string) $value;
        })->set(null);
    }



    /**
     * A String property
     *
     * @param  string  $name
     *
     * @return static
     */
    public static function string(string $name): Property
    {
        return self::property($name);
    }



    /**
     * Time - but using Carbon
     *
     * @param  string  $name
     * @param  string  $format
     * @param  string  $timezone
     *
     * @return static
     */
    public static function time(string $name, string $format = 'H:i:s', string $timezone = 'UTC'): Property
    {
        return self::dateTime($name, $format);
    }



    /**
     * A url property
     *
     * @param  string  $name
     *
     * @return static
     */
    public static function url(string $name): Property
    {
        return self::property($name)
            ->addRule('url')
            ->set(null);
    }



    /**
     * adda  rule
     *
     * @param  mixed  $rule
     *
     * @return $this
     */
    public function addRule(mixed $rule): Property
    {
        $this->rules[] = $rule;
        return $this;
    }



    /**
     * What's the default?
     *
     * @param $value
     *
     * @return $this
     */
    public function default($value): Property
    {
        return $this->set($value);
    }



    /**
     * get the name of the property
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }



    /**
     * Set a custom json converter
     *
     * @param  Closure  $closure
     *
     * @return $this
     */
    public function jsonCallback(Closure $closure): Property
    {
        $this->json_callback = $closure;
        return $this;
    }



    public function jsonSerialize(): mixed
    {
        return call_user_func($this->json_callback, $this->value);
    }



    /**
     * Set a nullable rule
     *
     * @return $this
     */
    public function nullable(): Property
    {
        return $this->addRule('nullable');
    }



    /**
     * set a required rule
     *
     * @return $this
     */
    public function required(): Property
    {
        return $this->addRule('required');
    }



    /**
     * Set a bunch of rules
     *
     * @param  array  $rules
     *
     * @return $this
     */
    public function rules(array $rules): Property
    {
        $this->rules = array_merge($this->rules, $rules);
        return $this;
    }



    /**
     * set the value of this property
     *
     * @param  mixed  $value
     *
     * @return Property
     */
    public function set(mixed $value): Property
    {
        $this->value = call_user_func($this->set_callback, $value);
        return $this;
    }



    /**
     * Set a custom function for settinhg the value
     *
     * @param  Closure  $closure
     *
     * @return $this
     */
    public function setCallback(Closure $closure): Property
    {
        $this->set_callback = $closure;
        return $this;
    }



    /**
     * @return ObjectValidation
     * @throws ObjectModelException
     */
    public function validate(): ObjectValidation
    {
        if ($this->value instanceof ArrayModel || $this->value instanceof ObjectModel) {
            return $this->value->validate();
        }

        $validator = Validator::make([$this->name => $this->value], [$this->name => $this->rules]);

        return ObjectValidation::createFrom(array: [
            'name'   => $this->name,
            'valid'  => $validator->passes(),
            'errors' => $validator->getMessageBag()->toArray(),
        ]);
    }

}
