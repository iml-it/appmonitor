<?php
/*

    validation rules 
    if a param has the key 'validate' that matches a key then its values will
    be added for validation.

    This file is included in the constructor of the validateparam class

    SYNTAX:

    KEY - value for'validate' key
    VALUE - array with these possible keys
            - type  - set a type
            - regex - define a regex for type sting
            - min, max - range for types float and int
            - oneof
*/


$this->_aValidationDefs = [
    'hostname'   => [ 'type' => 'string', 'regex' => '/^[a-z0-9\_\-\.]/i'],
    'portnumber' => [ 'type' => 'int',    'min' => 0, 'max' => 65535],
    'website'    => [ 'type' => 'string', 'regex' => '/https?:\/\//'],
];
