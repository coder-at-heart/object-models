# A handy way to define, use and convert object and arrays to json

This Laravel package:

- Provides a fluent way to define structure / schema over objects
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

Extend the ObjectModel class and extend override the `properties()` method

I usually create an `app/ObjectModels` folder to store my models

Here's an example from the tests folder:

```php
<?php

namespace CoderAtHeart\ObjectModel\Tests\Models;

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
 * @property bool subscribed
 * @property array friends
 * @property Carbon[] important_dates
 */
class Person extends ObjectModel
{

    public static function properties(): array
    {
        return [
            Property::string('name')->required(),
            Property::integer('age')->nullable(),
            Property::email('email'),
            Property::objectModel('home', Address::class),
            Property::objectModel('business', Address::class),
            Property::objectModelArray('phone_numbers', Phone::class),
            Property::date('birthday'),
            Property::time('alarm')->default('08:00:00'),
            Property::bool('subscribed')->default(false),
            Property::array('friends'),
            Property::propertyArray('important_dates', Property::date('date')),
        ];
    }

}

```

And the `Address`

```php

namespace CoderAtHeart\ObjectModel\Tests\Models;

use CoderAtHeart\ObjectModel\ObjectModel;
use CoderAtHeart\ObjectModel\Property;
use CoderAtHeart\ObjectModel\Tests\ENUMs\Country;

/**
 * @property string address_1
 * @property string address_2
 * @property string city
 * @property string postcode
 * @property Country country_code
 */
class Address extends ObjectModel
{

    public static function properties(): array
    {
        return [
            Property::string('address_1'),
            Property::string('address_2'),
            Property::string('city'),
            Property::enum('country_code', Country::class)->default(Country::GB)->required(),
            Property::string('postcode'),
        ];
    }

}
```

The `Country` ENUM

```php
<?php

namespace CoderAtHeart\ObjectModel\Tests\ENUMs;

enum Country : string
{

   case US = 'us';
   case GB = 'gb';

}
```

And the `Phone`:

```php
<?php

namespace CoderAtHeart\ObjectModel\Tests\Models;

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
    'important_dates' => [
        '2011-01-01 01:01:01',
        '2012-02-02 02:02:02',
        '2013-03-03 03:03:03',
        '2014-04-06 04:04:04',
        '2015-05-06 05:05:05',
    ]
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

use CoderAtHeart\ObjectModel\ArrayModel;

$numbers = ArrayModel::create(objectModel: Phone::class);

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

// Access it like an object
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
        return Person::create(json: $value);
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
use CoderAtHeart\ObjectModel\ArrayModel;use CoderAtHeart\ObjectModel\Tests\Models\Person;use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class LogCast implements CastsAttributes
{

    public function get($model, string $key, $value, array $attributes)
    {
        return PhonesNumbers::create(json: $value);
      // or
        return ArrayModel::create(json: $value, objectModel: Phone::class);
    }

    public function set($model, string $key, $value, array $attributes)
    {
        return $value->toJson();
    }
}

```

## Built-in Property Types

| **Type**                 | **Create With**                | **Underlying Object** |
|--------------------------|--------------------------------|-----------------------|
| Another Model            | `Property::objectModel();`     | `ObjectModel `        |
| Bool                     | `Property::bool();`            | php bool              |
| Date                     | `Property::date();`            | `Carbon`              |
| DateTime                 | `Property::dateTime();`        | `Carbon`              |
| ENUM                     | `Property::enum();`            | php ENUM              |
| Email                    | `Property::email();`           | php string            |
| Float                    | `Property::float();`           | php string            |
| Integer                  | `Property::integer();`         | php integer           |
| Strings                  | `Property::string();`          | php string            |
| Time                     | `Property::time();`            | `Carbon`              |
| an Array                 | `Property::array();`           | php array             |
| an Array of other models | `Property::objectModelArray(); | arrayModel            |
| an Array of property     | `Property::propertyArray();    | arrayModel            |
| an Object                | `Property::object();`          | the Object            |
| mixed                    | `Property::mixed();`           | any                   |


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


v1.0.2
