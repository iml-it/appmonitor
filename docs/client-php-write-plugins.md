<style>
	.required{color:#f22;}
	.optional{color:#888;}
</style>


# [APPMONITOR](readme.md) > [CLIENT](client.md) > [PHP-Client](client-php.md) > Write PHP plugins #

Free software and Open Source from University of Bern :: IML - Institute of Medical Education

https://github.com/iml-it/appmonitor

- - -


# Introduction #

The php client contains a few checks. You can extend the functionality by 
writing custom plugins. 



# Conventions #

* custom checks are in the "plugins" subdirectory
* naming convention: they must start with "check" + [name of your check] + ".php"
* it must be a class that extends appmonitorcheck
* the class name must be "check" + [name of your check]
* it needs a run method that gets an array as argument

The built in functions and custom checks are listed with listChecks method:

```php
require_once('classes/appmonitor-client.class.php');
$oMonitor = new appmonitor();
print_r($oMonitor->listChecks());
```

# Example #

A good starting point is the file [client]/plugins/hello.php

* To access the checkHello.php you need to use "Hello" in the value check -> function.
* A plugin gets the array check -> params as argument. The following snippet will use an array with a single key "message"

In the check script:

```php
require_once('classes/appmonitor-client.class.php');
$oMonitor = new appmonitor();
// (...)
$oMonitor->addCheck(
    array(
        "name" => "check plugin",
        "description" => "test an external plugin plugins/checkHello.php",
        "check" => array(
            "function" => "Hello",
            "params" => array(
                "message" => "Here I am",
            ),
        ),
    )
);
```

In the plugins/checkHello.php the class name ist the same like the filename
(without ".php"):

It needs a method run() that must be public.

```php
class checkHello extends appmonitorcheck{
    // (...)
    public function run($aParams){
        // (...)
    }
}
```

Inside the class I suggest to use to verify your needed keys with
_checkArrayKeys()

```php
$this->_checkArrayKeys($aParams, "message");
```

After making the magic things things of your custom check you need to 
return a result set as an array.

The class appmonitorcheck that calls your plugin will put your response
to \_setReturn():

```php
require_once($sPluginFile);
$oPlogin = new $sCheck;
$this->_setReturn($oPlogin->run($aParams));
```

The checkHello has a documented section. It returns the minimal variant
with return code and a message - but no counter.



```php
// see method appmonitorcheck->_setReturn()
// 
// {integer} you should use a RESULT_XYZ constant:
//              RESULT_OK|RESULT_UNKNOWN|RESULT_WARNING|RESULT_ERROR
// {string}  output text 
// {array}   optional: counter data
//              type   => {string} "counter"
//              count  => {float}  value
//              visual => {string} one of bar|line|simple (+params)
//           
return array(
    RESULT_OK, 
    'Hello world! My message is: ' .$aParams['message']
);
```

This returns an OK with the message "Hello world! My message is: Here I am".
