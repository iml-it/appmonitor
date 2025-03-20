# validateparam.class.php

## Description

This class can validate values and arrays.
You define type and validateion rules. A validation method checks the given value against your rules. It returns the error message(s). No error means, the value is ok.

## Usage

```php

$oVal=new validateparam();

// check an array
$aErrors=$oVal->validateArray($aRules, $aGivenValues, $bStrict);
if(count($aErrors)){
    echo "Something is wrong: <pre>".print_r($aErrors, 1)."</pre>";
    exit(1);
}
```

## Validate Arrays

First something visual - an example:

```txt
Array
(
    [name] => Array
        (
            [type] => string
            [required] => true
            [regex] => /./
        )

    [description] => Array
        (
            [type] => string
            [required] => true
            [regex] => /./
        )
        :
    [worstresult] => Array
        (
            'type' => 'int',
            'required' => false,
            'description' => 'A failed check is max counted as given result. Use it on not required but optional checks',
            'min'=>RESULT_OK,
            'max'=>RESULT_ERROR,
        )
)
```


### Type check

Each key can be marked to be a value of a given type.

It can be a mandantory value or optional.

| Name       | Type       | Description
|--          |--          |--
| 'type'     | {string}   | variable type that must match; one of "array", "bool", "count", "float", "int", "integer", "string"
| 'required' | {bool}     | define value as required

Next to these keys per type you can define validation rules in dependency of the type.

### Validate numbers

This section describes values of the `type`

* int|integer - integer values
* float - float values (or integer)

Values you can verify if it is in a wanted range.

| Name       | Type         | Description
|--          |--            |--
| 'min'      | {float\|int} | allowed minimum value
| 'max'      | {float\|int} | allowed maximum value
| 'oneof'    | {array}      | value must match one of the given values

### Validate string

Values of type "string" can be verified

* against a given regex
* with a set of allowed values

| Name       | Type         | Description
|--          |--            |--
| 'regex'    | {string}     | value must match given regex
| 'oneof'    | {array}      | value must match one of the given values

### Validate with presets

In the file *validateparam.settings.php* you can put presets and their validation rules for elements that repeat.

```php
<?php
/*

    validation rules 
    if a param has the key 'validate' that matches a key then its values will
    be added for validation.

    SYNTAX

    KEY - value for'validate' key
    VALUE - array with these possible keys
            - type  - set a type
            - regex - define a regex for type sting
            - min, max - range for types float and int
            - oneof
*/


return [
    'hostname'   => [ 'type' => 'string', 'regex' => '/^[a-z0-9\_\-\.]/i'],
    'portnumber' => [ 'type' => 'int',    'min' => 0, 'max' => 65535],
    'website'    => [ 'type' => 'string', 'regex' => '/https?:\/\//'],
];
```

```txt
[port] => Array
    (
        [type] => int
        [required] => true
        [validate] => portnumber
    )
```
