<?php

namespace CoderAtHeart\ObjectModel;

use BackedEnum;
use Carbon\Carbon;
use Closure;
use CoderAtHeart\ObjectModel\Exceptions\ObjectModelException;
use CoderAtHeart\ObjectModel\Models\ObjectValidation;
use CoderAtHeart\ObjectModel\Rules\IsInstanceOf;
use CoderAtHeart\ObjectModel\Traits\CanBeConverted;
use CoderAtHeart\ObjectModel\Traits\HasName;
use DateTimeZone;
use Illuminate\Support\Facades\Validator;
use JsonSerializable;

class Property implements JsonSerializable
{

    use HasName, CanBeConverted;

    /**
     * The value for this property.
     *
     * @var mixed|null
     */
    public mixed $value = null;

    /**
     * The rules of property
     *
     * @var array
     */
    protected array $rules = [];

    /**
     * the callback that converts this to be stored as json
     *
     * @var Closure
     */
    protected Closure $json_callback;

    /**
     * What to do when the value is set.
     *
     * @var Closure
     */
    protected Closure $set_callback;



    /**
     * constructor and set some defaults
     *
     * @param  string  $name
     */
    public function __construct(string $name = '')
    {
        if ($name) {
            $this->name = $name;
        }
        $this->jsonCallback(function ($value) {
            return $value;
        });
        $this->setCallback(function ($value) {
            return $value;
        });
    }



    /**
     * A normal php array
     *
     * @param  string  $name
     *
     * @return Property
     */
    public static function array(string $name): Property
    {
        return static::arrayProperty(name: $name);
    }



    /**
     * an Array Model Object
     *
     * @param  string  $name
     * @param  string  $arrayModel
     *
     * @return Property
     */
    public static function arrayModel(string $name, string $arrayModel): Property
    {
        return static::arrayProperty(name: $name, arrayModel: $arrayModel);
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
            ->jsonCallback(function (?Carbon $value) use ($format) {
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
                return is_numeric($value) ? (int) $value : null;
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

                return $objectModel::create(array: $value);
            })
            ->set(null);
    }



    /**
     * A dynamically created array of object models
     *
     * @param  string  $name
     * @param  string  $objectModel
     *
     * @return Property
     */
    public static function objectModelArray(string $name, string $objectModel): Property
    {
        return static::arrayProperty(name: $name, objectModel: $objectModel);
    }



    /**
     * static constructor
     *
     * @param  string  $name
     *
     * @return Property
     */
    public static function property(string $name): Property
    {
        $property = new self($name);
        return $property->setCallback(function ($value) {
            return (string) $value;
        })->set(null);
    }



    /**
     * An Array of properties
     *
     * @param  string  $name
     * @param  Property  $property
     *
     * @return Property
     */
    public static function propertyArray(string $name, Property $property): Property
    {
        return static::arrayProperty(name: $name, property: $property);
    }



    /**
     * A string property
     *
     * @param  string  $name
     *
     * @return static
     */
    public static function string(string $name): Property
    {
        return self::property($name)
            ->addRule('string');
    }



    /**
     * Time property - uses Carbon
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
     * rl property
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
     * add a laravel validation rule
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
     * Set a bunch of rules
     *
     * @param  array  $rules
     *
     * @return $this
     */
    public function addRules(array $rules): Property
    {
        $this->rules = array_merge($this->rules, $rules);
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
     * get all rules for this property
     *
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }



    /**
     * Does this property contain a rule?
     *
     * @param  string  $rule
     *
     * @return bool
     */
    public function hasRule(string $rule): bool
    {
        return in_array($rule, $this->rules);
    }



    /**
     * can this property be null?
     *
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->hasRule('nullable');
    }



    /**
     * is a value required?
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->hasRule('required');
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



    /**
     * convert this property to json
     *
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return call_user_func($this->json_callback, $this->value);
    }



    /**
     * Shortcut to set a nullable rule
     *
     * @return $this
     */
    public function nullable(): Property
    {
        return $this->addRule('nullable');
    }



    /**
     * shortcut to set a required rule
     *
     * @return $this
     */
    public function required(): Property
    {
        return $this->addRule('required');
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
        if ($this->isNullable() && empty($value)) {
            $this->value = null;
        } else {
            $this->value = call_user_func($this->set_callback, $value);
        }
        return $this;
    }



    /**
     * Set a custom closure for setting the value
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
     * Validate this property
     *
     * @return ObjectValidation
     * @throws ObjectModelException
     */
    public function validate(): ObjectValidation
    {
        if ($this->value instanceof ArrayModel || $this->value instanceof ObjectModel) {
            return $this->value->validate();
        }

        $validator = Validator::make([$this->getName() => $this->value], [$this->getName() => $this->rules]);

        return ObjectValidation::create(array: [
            'name'   => $this->getName(),
            'valid'  => $validator->passes(),
            'errors' => $validator->getMessageBag()->toArray(),
        ]);
    }



    /**
     * An Array property
     *
     * @param  string  $name
     * @param  string|null  $arrayModel
     * @param  string|null  $objectModel
     * @param  Property|null  $property
     *
     * @return static
     */
    protected static function arrayProperty(string $name, string $arrayModel = null, string $objectModel = null, Property $property = null): Property
    {
        // the ArrayModel class is the default handler for objectModel and property arrays
        if ( ! $arrayModel && ($objectModel || $property)) {
            $arrayModel = ArrayModel::class;
        }

        if ($arrayModel && $arrayModel != ArrayModel::class) {
            // An ArrayModel has been extended...
            $array = new $arrayModel(name: $name);
            $rule  = new IsInstanceOf($arrayModel);
        } elseif ($objectModel) {
            // this is an array of objectModels
            $array = new $arrayModel(name: $name, objectModel: $objectModel);
            $rule  = new IsInstanceOf(ArrayModel::class);
        } elseif ($property) {
            // this is an array of properties
            $array = new $arrayModel(name: $name, property: $property);
            $rule  = new IsInstanceOf(ArrayModel::class);
        } else {
            // just a normal array
            $array = [];
            $rule  = 'array';
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

                // Values should be objects or properties.
                if ($arrayModel && count($value) > 0) {
                    return $array->fill($value);
                }

                // Must be a normal array
                return $value;
            })
            ->set(null);
    }

}
