# A handy way to define, use and convert object and arrays to json

This Laravel package:

- Provides an fluent way to define structure / schema over objects
- Allows you to validate those objects in a consistent way 
- Easily cast eloquent json attributes to objectModels for easy handling
- Reduces the number of meta tables in your app

## Requirements

This package requires 
- PHP 8.1 and 
- Laravel 8.0 or higher.

## Installation

Install it:

```bash
composer require coder-at-heart/object-model
```

## Defining ObjectModels

Extend the ObjectModel class and extend override the definition method

I usually create an `app/ObjectModels` folder to store my models

Here's a `Person` :

```php
<?php

use Carbon\Carbon;
use CoderAtHeart\ObjectModel\ObjectModel;
use CoderAtHeart\ObjectModel\Property;

/**
 * @property string name
 * @property int age
 * @property string email
 * @property Address home
 * @property Address business
 * @property Phone[] phone_numbers
 * @property Carbon birthday
 * @property Carbon alarm
 */
class Person extends ObjectModel
{

    public static function properties(): array
    {
        return [
            Property::string('name'),
            Property::integer('age'),
            Property::email('email'),
            Property::objectModel('home', Address::class),
            Property::objectModel('business', Address::class),
            Property::array('phone_numbers', PhonesNumbers::class),
            Property::date('birthday'),
            Property::time('alarm'),
        ];
    }

}
```

And the `Address`

```php
<?php

use CoderAtHeart\ObjectModel\ObjectModel;
use CoderAtHeart\ObjectModel\Property;

/**
 * @property string address_1
 * @property string address_2
 * @property string city
 * @property string postcode
 */
class Address extends ObjectModel
{

    public static function properties(): array
    {
        return [
            Property::string('address_1'),
            Property::string('address_2'),
            Property::string('city'),
            Property::string('postcode'),
        ];
    }

}

```

And a `Phone`:

```php
<?php

use CoderAtHeart\ObjectModel\ObjectModel;
use CoderAtHeart\ObjectModel\Property;

/**
 * @property string label
 * @property string number
 */
class Phone extends ObjectModel
{

    public static function properties(): array
    {
        return [
            Property::string('label'),
            Property::string('number'),
        ];
    }

}
```

...and lastly and array of `Phone` numbers
 
```php
<?php

use CoderAtHeart\ObjectModel\ArrayModel;

class PhonesNumbers extends ArrayModel
{

    protected string $objectModel = Phone::class;

}

```

Note the use of the docBlock at the top of the classes - this helps with type hinting in your ide.

## Instantiating Objects

Can be done through arrays, json or directly

```php
// Create from an array of data
$person = Person::createFrom(array:[
    'name' => 'Coder At Heart',
    'age' => 30,
    'email' => 'coderatheart@gmail.com',
    'home' => [
        'address_1' => 'Some Street',
        'address_2' => 'Some Area',
        'city' => 'Some City',
        'postcode' => 'AB12 3CD',
    ],
    'business' => [
        'address_1' => 'Some Business',
        'address_2' => 'Some Location',
        'city' => 'Some City',
        'postcode' => 'AB99 9DC',
    ],
    'phone_numbers' => [
        [
            'label' => 'home',
            'number' => '01234 67890'
        ],
        [
            'label' => 'mobile',
            'number' => '09999 123456'
        ],
    ],
    'birthday' => '1990-01-01'  
]);

echo $person->name;
// the return value is the underlying object. in this case
// a Carbon Object 
echo $person->birthday->format('js F Y'); 
// Access deep objects
echo $person->home->address_1;  
echo $person->phone_numbers[1]->number;  

// Save this to json
$json = $person->toJson();

// Create a new person 
$bob = Person::createFrom(json: $json)
$bob->name = 'Bob';

// Convert the person to an array
$array = $bob->toArray();

$fred= Person::createFrom(array: $bob)
$fred->name = 'Fred';
$fred->age = 25;

// Just like a normal object:
$isabel = new Person();
$isabel->name = 'Isabel';
$isabel->age =  35;
$isabel->birthday =  new Carbon("2002-11-23"); 

```

## ArrayModels

You don't need an Object Model to use an Array Object:

```php
<?php
$numbers = new  PhonesNumbers();

// Create a new phone number
$homePhone         = new Phone();
$homePhone->label  = 'home';
$homePhone->number = '01234 567890';

$numbers[] = $homePhone;
echo $numbers[0]->number;

// add to it like a normal array
$numbers[] = [
    'label'  => 'business',
    'number' => '01234 567890',
];

echo $numbers[1]->label;

```

## Casting eloquent model attributes and json columns

You can use ObjectModels and ArrayModels when creating custom casts in your app - Especially useful when casting json columns.

ObjectsModels:

```php
<?php

namespace App\Casts;

use App\ObjectModels\Person;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class PersonCast implements CastsAttributes
{

    public function get($model, string $key, $value, array $attributes)
    {
        return Person::createFrom(json: $value);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return $value->toJson();
    }
}

```

And ArrayModels:

```php
<?php

namespace App\Casts;

use App\ObjectModels\PhonesNumbers;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class LogCast implements CastsAttributes
{

    public function get($model, string $key, $value, array $attributes)
    {
        return PhonesNumbers::createFrom(json: $value);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return $value->toJson();
    }
}

```

## Built-in Property Types

| **Type**                 | **Create With**                                   | **Underlying Object** |
|--------------------------|---------------------------------------------------|-----------------------|
| Integer                  | `Property::integer();`                            | php integer           |
| Strings                  | `Property::string();`                             | php string            |
| Email                    | `Property::email();`                              | php string            |
| Date                     | `Property::date();`                               | `Carbon`              |
| Time                     | `Property::time();`                               | `Carbon`              |
| DateTime                 | `Property::dateTime();`                           | `Carbon`              |
| Another Model            | `Property::objectModel();`                        | `ObjectModel `        |
| an Array                 | `Property::array();`                              | php array             |
| an Array of Other Models | `Property::array(arrayModel: ArrayModel::class);` | `ArrayModel`          |
| mixed                    | `Property::mixed();`                              | any                   |
| an Object                | `Property::object();`                             | the Object            |
| Bool                     | `Property::bool();`                               | php bool              |
| ENUM                     | `Property::enum();`                               | php ENUM              |


## Custom Property Types

Extend the `Property` class to add in your own propertires. 

```php
<?php

use CoderAtHeart\ObjectModel\Property;

/**
 * @property string label
 * @property string number
 */
class CustomProperties extends Property
{

    /**
     * zipCode
     *
     * @param  string  $name
     *
     * @return static
     */
    public static function zipCode(string $name): Property
    {
        return self::property($name)
            ->addRule('numeric|min:5')
            // jsonCallback will be called when teh object is converted to json 
            ->jsonCallback(function ($value) {
                return str_replace(' ', '', $value);
            })
            // This callback is called when the value is set
            ->setCallback(function ($value) {
                return (string) $value;
            })
            ->set(null);
    }

}

```

The two main callbacks are `jsonCallback` called when the property is going to be turned into json, and `setCallback` when the property is assigned a value.

Here's an object using Custom properties

```php
<?php

use CoderAtHeart\ObjectModel\ObjectModel;
use CoderAtHeart\ObjectModel\CustomProperties;

/**
 * @property string address_1
 * @property string address_2
 * @property string city
 * @property string zip
 */
class Address extends ObjectModel
{

    public static function properties(): array
    {
        return [
            CustomProperties::string('address_1'),
            CustomProperties::string('address_2'),
            CustomProperties::string('city'),
            CustomProperties::zipCode('zip'),
        ];
    }

}


```

## Object Validation

Object and Arrays can be validated. What you get back is a ObjectValidation Object

```php
<?php

$validation = $person->validate();

// $validation is an ObjectModel
echo $validation->valid; 
// or
echo $validation->isValid();

//  the name of the object / array
echo $validation->name;

// the errors
dd($validation->errros);


```

_Note:_ ObjectModels or ArrayModels are not validated automatically.

## Adding Rules
When you're defining object you can add any valid laravel validation rules

```php
<?php

class Person extends ObjectModel
{

    public static function properties(): array
    {
        return [
            Property::string('name')->required(), //  just adds 'required' to the rules array
            Property::integer('age')->nullable(), // optional
            Property::email('email')->addRule('min:20'),
            Property::string('first_name')->addRule(new Rule()), // custom rules
        ];
    }

}
```

## Got an idea / Suggestion / Improvement? 

Let me know... somehow.


## Support

- [Issue Tracker](https://github.com/code-at-heart/object-model/issues/)

## License

ObjectModel is licensed under the [MIT License](LICENSE).

## Change Log

- 1.0.0 Initial release
