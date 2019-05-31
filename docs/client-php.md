<style>
	.required{color:#f22;}
	.optional{color:#888;}
</style>


# [APPMONITOR](readme.md) > [CLIENT](client.md) > PHP client #

Free software and Open Source from University of Bern :: IML - Institute of Medical Education

https://github.com/iml-it/appmonitor

- - -


# Requirements #

(For the delivered PHP client - you can implement a client in other languages)
- PHP7 (up to PHP 7.3; runs on php5 but is not supported)


# Checks on client side #


## Get started ##

The project package contains the PHP client and the server. The PHP client is used for the Appmonitor server to verify its own installation. You should start with the server installation.
see [Server](server.md)

To use the client in any of your installations you need the files from the "client" subdir only.

1) Below a document root of a website create a directory [webroot]/appmonitor/
2) copy all files of [package]/client/ into [webroot]/appmonitor/
3) verify the installation with your browser
   http://localhost/appmonitor/client/check-appmonitor-server.php
   You should see some JSON output

To start with a first check of another web application I suggest 
1) start with a single "simple" check and response just an OK. Then add the client url in the appminitor server backend. The most primitive check in the monitoring is better than no monitoring. 
2) Step by step add more checks to verify that all needed things and services for the application work. 
3) Add tags and notifications.
4) Finetuning: check some edge cases and security checks.

**Suggestions**

If you don't know how to continue after the first simple check and  and what else to check ... 
- check if a pre defined check exists in https://github.com/iml-it/appmonitor-clients/tree/master/client
- locate the config
  - try to load the config - check their values.
  - if there is a class with methods to access config data use the application way

To write your own checks ... these are some ideas you can pick from:
- file check: 
  - is a (config) file readable AND writable
  - is a upload directory writeable?
  - if the maintenance page is triggered by a file: does the maintenance file NOT exist
  - verify security: is a sensitive (config) file or a temp a temp directory writeable but not accessible by http? (requires 2 checks: file and http)
- database checks (PDO)
  - check database connections (remark: read your config for credentials) ... to master and slaves
- http checks
  - check if a remote page (or web api) answers ... and optionally contains given text/ regex
  - check if page sends the correct redirect location
  - check if a request contains the wanted non-OK-status code, i.e. redirect with 307 or a config is NOT accessible and sends a 403 response
- tcp checks
  - do very basic network checks if you don't make a authenticated connect, i.e. to LDAP, SSH, ...
- certificate
  - use the snippet for the certificate check: this check is active if https is used only.


# Docs with code snippets #
   
## Initialisation ##

Have a look to the example in ./client/index.sample.php

The first step is to initialize the client.

```php
require_once('appmonitor-client.class.php');
$oMonitor = new appmonitor();
```

This will set these default of client metadata with 
- hostname
- dominaname of website and 
- ttl of 300 sec (= 5 min)


## Security ##

You should protect internal data about your application.
The php client offers 2 possibilities:

IP restriction

If the http request does not come from one of the pre defined ip ranges the request will stop with http 403 error.

```php
$oMonitor->checkIp(array(
	'127.0.0.1',
	'::1',
	'192.168.',
));
```


Usage of a token

You can define a token that the server must send in the GET request. Without or with a wrong token the request will stop with an http 403 error.

    $oMonitor->checkToken('token', '12345678');

... to allow response with correct token only, i.e.
http://localhost/appmonitor/client/mycheck.php?token=12345678

	
## Notifications ##

You can add add notification targets. At the beginning emails and Slack will be supported. 
Here are 2 methods to add the targets:

```php
// to add notifications
$oMonitor->addEmail('developer@example.com');
$oMonitor->addSlackWebhook(array("dev-webhook"=> "https://hooks.slack.com/services/(...)"));
```

The notification is done on the appmonitor server.


## Add checks ##


You can add several checks with the class that
was initialized on top of the file.

The class has a render method that generates the json for you.

In the area between $oMonitor = new appmonitor(); and render(); you can add
as many checks you want.
The syntax is

```php
$oMonitor->addCheck(
  array(
    "name" => "[short name of the check]",
    "description" => "[an a bit longer description]",
    "check" => [Array for the check],
    "worstresult" => RESULT_WARNING
  )
);
```

| key        | type     | description |
|---         |---       |---
|name        |(string)  | "id" if the check <span class="required">(*)</span>|
|description |(string)  | a short description <span class="required">(*)</span>|
|check       |(array)   | check to perform <span class="required">(*)</span>|
|worstresult |(integer) | optional: limit maximum error level if the check fails<br>if the check should fail then its result is an error - but this check is not highly relevant for a running application then you can override the influence to the total result set a maximum level i.e. RESULT_WARNING.|


The check contains 2 keys:

	"function" => "[Name of a defined check]",
	"params" => [key->value array; count and keys depend on the function]

See the next chapter for the available checks.


## Set total result ##

Set the value meta->result for the total status for your webapp.
There is an automatic function that sets the total result to the worst status of any check: If one check ist on warning, 1 on error - then the total result will be an error. For this simple case you can use 

```php
$oMonitor->setResult();
```


If you made several checks then not each failure maybe critical to have a runnable application. Example: you write data to an external host once per week, but this host not reachable and the check status is error.
For that constellation you need to calculate the result by yourselfs and set it by

```php
$oMonitor->setResult([your value; 0..3]);
```

	
## Render output ##

This method echoes all information as JSON

```php
$oMonitor->render();
```


# Check functions in detail #

## Introduction ##

The checks are defined in appmonitor-checks.class.php as private functions.
To see all defined checks:

```php
print_r($oMonitor->listChecks());
```

- checkCert
- checkDiskfree
- checkFile
- checkHttpContent
- checkMysqlConnect
- checkPdoConnect
- checkPortTcp
- checkSimple
- checkSqliteConnect

## Simple ##

The most simple variant is direct call with the resultcode and output text. 

```php
$oMonitor->addCheck(
	array(
		"name" => "Dummy",
		"description" => "Dummy Test",
		"check" => array(
			"function" => "Simple",
			"params" => array(
				"result" => RESULT_OK,
				"value" => "The dummy test does nothing and was extremely successful",
				"count" => [float value],
				"visual"  => "[styling parameters]",
			),
		),
	)
);
```

Parameters:

| key        | type     | description |
|---         |---       |---
|result      |(integer) | result code <span class="required">(*)</span><br>After loading the client class you can use constants to keep the code more readable<br>RESULT_OK = OK (0)<br>RESULT_UKNOWN = unknown (1)<br>RESULT_WARNING = Warning (2) <br>RESULT_ERROR = Error (3) |
|value       |(string)  | ouput text to describe the result <span class="required">(*)</span> |
|count       |(float)   | ptional; if a count exists in a check then a tile will be rendered |
|visual      |(string)  | optional; used if a "count" was given. see counter description [Client](client.md)|

You can use the simple check to verify just anything that has no pre defined function
yet. Set a value for the text that should be visible and the result code (you should use the constants from table above to keep it more readable).


## Cert ##

Check if a SSL certificate is still valid ... and does not expire soon.

```php
$oMonitor->addCheck(
	array(
		"name" => "Certificate check",
		"description" => "Check if SSL cert is valid and does not expire soon",
		"check" => array(
			"function" => "Cert",
			"params" => array(
				"url"      => [url-to-check],
				"verify"   => [flag-for-verification],
				"warning"  => [days-before-cert-expires],
			),
		),
	)
);
```


Parameters:

| key      | type     | description |
|---       |---       |---
|url       |(string)  |url to connect check i.e. https://example.com:3000; default: own protocol + server of your webapp
|verify    |(boolean) |optional: flag verify certificate; default = true
|warning   |(integer) |optional: count of days to warn; default=30

I recommend to set verify to *true*. If you should get a warning like 

    PHP Warning:  stream_socket_client(): SSL operation failed with code 1. OpenSSL Error messages:
    error:14090086:SSL routines:ssl3_get_server_certificate:certificate verify failed in (...)appmonitor-checks.class.php on line NNN

... then set it back to *false* to make a test for expiration only.


It returns OK if 
- ssl connect is successful
- valid-to date expires in more than 30 days (or given limit)

You get a warning if it expires soon.

You get an error, if 
- it is not a ssl target
- certificate is expired
- ssl connect fails


In most cases you can use this snippet to check the ssl certificate of the own instance

```php
$oMonitor->addCheck(
	array(
		"name" => "Certificate check",
		"description" => "Check if SSL cert is valid and does not expire soon",
		"check" => array(
			"function" => "Cert",
		),
	)
);
```


## Diskfree ##

Check if a given filesystem / directory that it has enough space.

```php
$oMonitor->addCheck(
	array(
		"name" => "check file storage",
		"description" => "The file storage have some space left",
		"check" => array(
			"function" => "Diskfree",
			"params" => array(
				"directory" => "[directory]",
				"warning"   => [size],
				"critical"  => [size],
			),
		),
	)
);
```

Parameters:

| key      | type     | description |
|---       |---       |---
|directory |(string)  | directory to check  <span class="required">(*)</span>
|warning   |{integer\|string} | size for warning level
|critical  |(integer\|string) | size for critical level <span class="required">(*)</span>


Remark to the [size] value:

The values for warning and critical
- must be integer OR
- integer or float added by a size unit (see below)
- warning level must be higher than critical value
- units can be mixed in warning and critical value

supported size units are 


- 'B' byte
- 'KB' kilobyte
- 'MB' megabyte
- 'GB' gigabyte
- 'TB' terabyte

Example for Diskfree size params:

```php
"warning"   => "1.25GB",
"critical"  => "500.7MB",
```
	

## File ##

Check if a file for file, link or directory. Use the parameter "filename" to set the full filename.

Other given parameters are flags to check. Its values can be set to true (flag must must be true) or false (flag must fail to return a true result). Missing flags won't be checked. 

Giving just a filename without any other flag returns true.

**Example 1**: \
check if "filename" is a directory and is writable

```php
$oMonitor->addCheck(
	array(
		"name" => "tmp subdir",
		"description" => "Check cache storage",
		"check" => array(
			"function" => "File",
			"params" => array(
				"filename" => $sApproot . "/server/tmp",
				"dir"      => true,
				"writable" => true,
			),
		),
	)
);
```

**Example 2**: \
With *"exists" => false* you can check if a file does not exist (flag is checked that it is not matching).

```php
$oMonitor->addCheck(
	array(
		"name" => "Maintenance mode",
		"description" => "Check if Maintenance mode is not activated by a flag file",
		"check" => array(
			"function" => "File",
			"params" => array(
				"filename" => "/var/www/maintenance_is_active.txt",
				"exists"      => false,
			),
		),
	)
);
```

Parameters:

| key      | type     | description |
|---       |---       |---
|filename  |(string)  |filename or directory to check  <span class="required">(*)</span>
|exists    |(boolean) |"filename" must exist/ must be absent
|dir       |(boolean) |filetype directory
|file      |(boolean) |filetype file
|link      |(boolean) |filetype symbolic link
|executable|(boolean) |flag executable
|readable  |(boolean) |flag is readable
|writable  |(boolean) |flag is writable


## HttpContent ##

This check verifies if a given url can be requested. Optionally you can test if it follows wanted rules:
* specific http status code
* http response header / response body contains/ not containtains given text text
* http response header / response body matches a given regex


**Example 1**: \
Check if a http reponse is successful.

```php
$oMonitor->addCheck(
	array(
		"name" => "HttpContent 1",
		"description" => "check if the example website sends a response",
		"check" => array(
			"function" => "HttpContent",
			"params" => array(
				"url" => "http://www.example.com/",
			),
		),
	)
);
```


**Example 2**: \
Check if a http reponse is successful and contains a wanted text.

```php
$oMonitor->addCheck(
	array(
		"name" => "HttpContent 1",
		"description" => "check if the example website sends a response and contains hello in the text",
		"check" => array(
			"function" => "HttpContent",
			"params" => array(
				"url" => "http://www.example.com/",
				"bodycontains" => "hello",
			),
		),
	)
);
```


**Example 3**: \
Check the status code: Is the http status a 307 and points to a wanted target?

```php
$oMonitor->addCheck(
	array(
		"name" => "HttpContent 2",
		"description" => "check if the example website is a redirect with 307",
		"check" => array(
			"function" => "HttpContent",
			"params" => array(
				"url" => "https://www.example.com/redirect",
				"headeronly" => true,
				"status" => 307,
				"headerregex" => "#Location: https://www.example.com/mytarget#i",
			),
		),
	)
);
```


Parameters:

| key      | type     | description |
|---       |---       |---
|url               |(string)  |url to fetch <span class="required">(*)</span>
|timeout           |(integer) |optional timeout in sec; default: 5
|headeronly        |(boolean) |optional flag to fetch http response herader only (HEAD request); default: false = returns header and body; 
|follow            |(boolean) |optional flag to follow a location; default: false = do not follow; If you set it to true it ries to follow (but this is not a safe method)
|status            |(integer) |test for an expected http status code; if none is given then test fails on status 400 and greater
|headercontains    |(string)  |test for a string in the http response header; it returns OK if the text was found
|headernotcontains |(string)  |test for a string in the http response header; it returns OK if the text was not found
|headerregex       |(string)  |test for a regex in the http response header; it returns OK if the regex matches
|bodycontains      |(string)  |test for a string in the http response body; it returns OK if the text was found
|bodynotcontains   |(string)  |test for a string in the http response body; it returns OK if the text was not found
|bodyregex         |(string)  |test for a regex in the http response body; it returns OK if the regex matches;

Remarks:

The checks for text strings are case sensitive. If you need a case insensitive test use a regex with "i" modifyer like in example 3.



## MysqlConnect ##

verify a database connection with mysqli real connect function.

```php
// example: parse a mysql connect string
require_once '../config/inc_config.php';
$aDb=parse_url($aCfg['databases']['writer']);
$aDb['path']=str_replace('/', '', $aDb['path']);

$oMonitor->addCheck(
	array(
		"name" => "Mysql Master",
		"description" => "Connect mysql db master ".$aDb['host']." - " . $aDb['path'],
		"check" => array(
			"function" => "MysqlConnect",
			"params" => array(
			  "server"   => $aDb['host'],
			  "user"     => $aDb['user'],
			  "password" => $aDb['pass'],
			  "db"       => $aDb['path'],
			  "port"     => $aDb['port'], // optional
			),
		),
	)
);
```


Parameters:

| key      | type     | description |
|---       |---       |---
|server    |(string)  |hostname/ ip of mysql server <span class="required">(*)</span>
|user      |(string)  |mysql username <span class="required">(*)</span>
|password  |(string)  |password <span class="required">(*)</span>
|db        |(string)  |database name / scheme to connect <span class="required">(*)</span>
|port      |(integer) |database port; optional
|timeout   |(integer) |optional timeout in sec; default: 5

Remark:  
The idea is not to enter credentials in the parameters. You should parse the config of your application and insert its variables.


## PdoConnect ##

verify a database connection with PDO connect.

PDO supports a wide range of database types - see http://php.net/manual/en/pdo.drivers.php.
BUT: I just started with Mysql. To implement more types go to classes/appmonitor-checks.class.php - method checkPdoConnect().

```php
$oMonitor->addCheck(
	array(
		"name" => "Mysql Master",
		"description" => "Connect mysql db master ".$aDb['host']." - " . $aDb['path'],
		"check" => array(
			"function" => "MysqlConnect",
			"params" => array(
			  "connect"  => [pdo connect string],
			  "user"     => [database user],
			  "password" => [password],
			),
		),
	)
);
```


parameters:

| key      | type     | description |
|---       |---       |---
|connect   |(string)  |connect string, i.e. 'mysql:host=localhost;port=3306;dbname=mydatabase;' <span class="required">(*)</span>
|user      |(string)  |mysql username <span class="required">(*)</span>
|password  |(string)  |password <span class="required">(*)</span>
|timeout   |(integer) |optional timeout in sec; default: 5

Remark:  
The idea is not to enter credentials in the parameters. You should parse the config of your application and insert its variables.




## PortTcp ##

Check if the local server is listening to a given port number.


```php
$oMonitor->addCheck(
	array(
		"name" => "Port local SSH",
		"description" => "check port 22",
		"check" => array(
			"function" => "PortTcp",
			"params" => array(
				"port" => 22,
			),
		),
	)
);
```

parameters:

| key      | type     | description |
|---       |---       |---
|port      |(integer) |port to check <span class="required">(*)</span>
|host      |(string)  |optional: hostname to connect to; if unavailable 127.0.0.1 will be tested
|timeout   |(integer) |optional timeout in sec; default: 5

... and an additional code snippet for a multiple port check:

```php
$aPorts=array(
	"22"=>array("SSH", "Secure shell connection"),
	"25"=>array("SMTP"),
	"5666"=>array("Nagios NRPE"),
	"5667"=>array("Nagios NSCA"),
);


foreach($aPorts as $iPort=>$aDescr){
	if (count($aDescr)==1) {
		$aDescr[1]="check port $iPort";
	}
	$oMonitor->addCheck(
		array(
			"name" => $aDescr[0],
			"description" => $aDescr[1],
			"check" => array(
				"function" => "PortTcp",
				"params" => array(
					"port"=>$iPort
				),
			),
		)
	);
}
```


## SqliteConnect ##

Make a database connection to a sqlite database.
The function fails if the filename does not exist or the PDO cannot open it

```php
$o = new PDO("sqlite:".$aParams["db"]);
```


Parameters:

| key      | type     | description |
|---       |---       |---
|db        |(string)  |full path of the sqlite database file <span class="required">(*)</span>
|timeout   |(integer) |optional timeout in sec; default: 5

	
# Additional Metadata #

In the meta section will be set the values with the following methods.

The appmonitor client has 
- add* functions to add values - "as many as you want" by repeating the method
- set* function to set a single attribute - by repeating the method the value will be overwritten


## host ##

Set the physical hostname where the application runs.
If no host is given then php_uname("n") will be used to set one.

```php
// automatic
$oMonitor->setHost();

// set a host manually
$oMonitor->setHost("web-01.example.com");
```


## website ##

Set a name for this website or application and its environment (dev, test, prod).
If you have several application in subdirectories, i.e. /blog,  /shop...
then you should the path or any description to identify them too

If no argument is given the name of HTTP_HOST will be used.


```php
// set the application manually
$oMonitor->setHost("www.example.com - My Wordpress blog");
$oMonitor->setHost("dev.example.com/shop");

// set the application  domain manually
$oMonitor->setHost("Wordpress blog");
```

**Suggestion**
- Verify the displayed name in the starting page of the server web gui: if names too similiar then set something more unique.

## TTL ##

Set a ttl value in seconds to define how long a server should not ask again for a new status of this instance.

```php
$oMonitor->setTTL(60);
```

**Suggestions**
- You can start with 60 (=1 min) or 300 (5 min).
- If you test new checks then set it temporarely to something small, i.e. 5 to get a fresh view with every browser refresh.
	
	
## Notification ##

You have these notification possibilities to get informed if a service is down ... or available again.

### Email ###

Add an E-Mail address.

```php
$oMonitor->addEmail("[your-email-address]");
```


To add several email addresses you need this command with each email address you want to add.

### Slack ###

You need to create a webhook in slack first. Each webhook has an url like https://hooks.slack.com/services/AAAAA/BBBBB/CCCCCC and will send a message to (exactly) one specific channel.
With the method addSlackWebhook you can add a slack channel where to post the notification. Because the url is not readable you can set a label for better reading (I suggest to set the channel name here).

```php
$oMonitor->addSlackWebhook("[Label]", "https://hooks.slack.com/services/AAAAA/BBBBB/CCCCCC");
```


If you would like to notify several Slack channels you need to create an additional Slack Webhook and add it with addSlackWebhook().

## Tags ##

Add a tag to describe the type of the application, the environment, department, dev team, ... whatever.
In the Appmonitor webgui will be dropdown with all tags in alphabetic order. There - or in the tile of the application - you can filter by a tag to get a relevant view for a target group.

```php
$oMonitor->addTag("production");
$oMonitor->addTag("monitoring");
```

**Suggestions**
- set the environment: dev, preview, stage, production
- set name of departments or teams
- set something functional
- use several tags - it's allowed
- discuss conventions between the teams


## Set total result value ##

Each added check has a result. The Method setResult() sets the total result
value for the application. The most simple variant is giving no value. 
then it sets the biggest (worst) value of any check: if one of the check has 
two warnings and one ended in an error then tho total value is an error.

```php
$oMonitor->setResult();
```

If you you want you can finetune your total result.

Example: you make a check for a job that runs in the night or once per week. 
If the check for reachability of a target to send a report fails (=error)
but other checks sy that the application runs fine - the override the error
with a new total result

```
$oMonitor->setResult(2);
```

Remark: Use $oMonitor->getResults() to get all checks and thir results to 
write your custom logic.

**Suggestion**
- before hartcoding something for setResult([new value]) use param "worstresult" in method addCheck()


# Send the response #

## Send JSON ##

After making all checks and setting the total result there is a method to send
the json response:

```php
$oMonitor->render();
```

DEPRECATED: This method supports 2 parameters 

| \#  | variable    | Description |
|--- |---          |---                                        |
| 1  | bPretty     | \{bool\} use pretty print; default: false |
| 2  | bHighlight  | \{bool\} use highligthed html instead of json; default: false; if true the response is tex/html and no valid JSON anymore |


## Snippet: show status locally (without appmonitor server) ##

To show the status page on the application server have a look to the snippet 
below. It can be used to show the current status to the users.
This variant is possible if you don't want to give access to the 
Appmonitor server. 

It does not send any notification. And this simple snippet does not care about the TTL ("yet": you need to build it).

```php
$_SERVER['REMOTE_ADDR']='127.0.0.1';

// execute checks
ob_start();
require __DIR__ . '/../../../appmonitor/index.php';
$sJson=ob_get_contents();
ob_end_clean();

// render
$oMonitor->renderHtmloutput($sJson);
```

## Abort a check ##

If you process the checks and need to exit the client with a critcal error
you can use the method abort().

This triggers a 503 service unavailable error with a given message.

This method should be used if a basic element is missed to perform a useful
check, i.e. a config file is not found.

```php
$oMonitor->abort([{string} message]);
```

