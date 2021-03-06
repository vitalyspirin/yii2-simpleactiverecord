# SimpleActiveRecord

Extension of Yii 2 ActiveRecord with automatically generated validators.

## Quick Start

To install it you can just download zip or use composer:
```
composer require vitalyspirin/yii2-simpleactiverecord
```

To use it in code:

```php
use vitalyspirin\yii2\simpleactiverecord\SimpleActiveRecord;


class T1 extends SimpleActiveRecord
{
    
}
```

There is no need to overload rules() function to specify validators. Validators will be added automatically by 
class constructor based on table schema. However in current version relations (based on foreign keys) will not be added.

There are two ways to create an instance of such class. Constructor of SimpleActiveRecord takes one boolean parameter: 
$maximumValidation. If it's 'false' then only those validators will be added that are generated by Gii. In terms of above 
example it will look like this:

```php
$t1 = new T1(false);
```

If to pass 'true' (which is also default) then maximum validation functionality will be added. It includes ranges for integer, 
ranges for enum, pattern for time etc. Of course you can see those validators:

```php
$t1 = new T1();
var_dump($t1->rules());
```

If table schema changed then validators will be adjusted automatically.

## Examples

Let's say we have the following SQL schema:
```sql
CREATE TABLE person
(
  person_id         INT PRIMARY KEY AUTO_INCREMENT,
  person_firstname  VARCHAR(35) NOT NULL,
  person_lastname   VARCHAR(35) NOT NULL,
  person_gender     ENUM('male', 'female'),
  person_dob        DATE NULL,
  person_salary     DECIMAL UNSIGNED
);
```

then if we run the following code:

```php
use vitalyspirin\yii2\simpleactiverecord\SimpleActiveRecord;


class Person extends SimpleActiveRecord
{
    // totally empty class
}

$person = new Person(false  /*Gii style validation only*/ );
var_dump($person->rules());
```

we will get the following output:

```
array (size=5)
  0 => 
    array (size=2)
      0 => 
        array (size=2)
          0 => string 'person_firstname' (length=16)
          1 => string 'person_lastname' (length=15)
      1 => string 'required' (length=8)
  1 => 
    array (size=2)
      0 => 
        array (size=1)
          0 => string 'person_salary' (length=13)
      1 => string 'number' (length=6)
  2 => 
    array (size=2)
      0 => 
        array (size=1)
          0 => string 'person_dob' (length=10)
      1 => string 'safe' (length=4)
  3 => 
    array (size=3)
      0 => 
        array (size=2)
          0 => string 'person_firstname' (length=16)
          1 => string 'person_lastname' (length=15)
      1 => string 'string' (length=6)
      'max' => int 35
  4 => 
    array (size=2)
      0 => 
        array (size=1)
          0 => string 'person_gender' (length=13)
      1 => string 'string' (length=6)
```

Above validators are the same that would be generated by Gii module of Yii. Additional validators can be generated if to call class constructor without false parameter:

```php
$person = new Person();
var_dump($person->rules());
```

In this case the output will be the following (pay attention to 'person_gender', 'person_dob' and 'person_salary' fields):
<pre>
array (size=5)
  0 => 
    array (size=2)
      0 => 
        array (size=2)
          0 => string 'person_firstname' (length=16)
          1 => string 'person_lastname' (length=15)
      1 => string 'required' (length=8)
  1 => 
    array (size=3)
      0 => 
        array (size=1)
          0 => string '<b>person_gender</b>' (length=13)
      <i>1 => string 'in' (length=2)
      'range' => 
        array (size=2)
          0 => string 'male' (length=4)
          1 => string 'female' (length=6)</i>
  2 => 
    array (size=5)
      0 => 
        array (size=1)
          0 => string '<b>person_dob</b>' (length=10)
      <i>1 => string 'date' (length=4)
      'format' => string 'yyyy-MM-dd' (length=10)
      'min' => string '1000-01-01' (length=10)
      'max' => string '9999-12-31' (length=10)</i>
  3 => 
    array (size=3)
      0 => 
        array (size=1)
          0 => string '<b>person_salary</b>' (length=13)
      1 => string 'number' (length=6)
      <i>'min' => int 0</i>
  4 => 
    array (size=3)
      0 => 
        array (size=2)
          0 => string 'person_firstname' (length=16)
          1 => string 'person_lastname' (length=15)
      1 => string 'string' (length=6)
      'max' => int 35
</pre>

You can also pass properties to contructor:
```php
$person = new Person(['person_firstname' => 'John', 'person_lastname' => 'Smith']);
echo $person->person_firstname;
```

In the case above maximum validation will be chosen by default. You can specify validation level explicitly (Gii style validation in the example below):
```php
$person = new Person(false, ['person_firstname' => 'John', 'person_lastname' => 'Smith']);
echo $person->person_firstname;
```

## Additional features

This extension of Active Record has also function getEnumValues() that shows enum values for table column. The following code
```php
$person = new Person();
var_dump($person->getEnumValues());
```
will return 
```
array (size=1)
  'person_gender' => 
    array (size=2)
      0 => string 'male' (length=4)
      1 => string 'female' (length=6)
```


You can also see tests in [SimpleActiveRecordTest.php](tests/unit/SimpleActiveRecordTest.php) for simple examples.
