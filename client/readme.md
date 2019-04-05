<style>
	.required{color:#f22;}
	.optional{color:#888;}
</style>


# [APPMONITOR](../readme.md) > CLIENT #

OPEN SOURCE from University of Bern :: IML - Institute of Medical Education

https://github.com/iml-it/appmonitor

- - -


# How does it work? #

An application creates a JSON in a predefined structure. 

The main idea is to make the checks with permissions of the application and with the own credentials. Check if directories or files are writable, a connection to services like databases, email, external http APIs/ ressources, ... whatever.


# Requirements #

(For the delivered PHP client - you can implement a client in other languages)
- PHP7 (up to PHP 7.2; runs on php5 but is not supported)



# Description of metadata / NON-PHP clients #

If you dont use php on your webserver you can create your own client that 
returns JSON answers with the conventions described below.


    {
    "meta": {
        "host": "[string: name of the computer]", 
        "website": "[string: description of the webapp]", 
        "ttl": [integer: ttl for the server gui],
        "result": [integer: 0..3],
        "tags": [
            "[tag 1]",
            "[tag N]"
        ],
        "time": "[value]ms",
        "notifications": {
            "email": [
                "email_1@example.com",
                "email_N@example.com"
            ],
            "slack": {
                "#dev-channel": "https:\/\/hooks.slack.com\/services\/AAAAA\/BBBBB\/CCCCCC",
                "#productowner-channel": "https:\/\/hooks.slack.com\/services\/XXXXXX\/YYYYYY\/ZZZZZ"
                }
            }
        }
    }, 
    "checks": [
        {
            "name": "[string: short name of the test 1]", 
            "description": "[string: a description what the test is verifying]", 
            "result": [integer: 0..3]
            "value": "[string: result in words]",
            "time": "[value]ms"
        },
        ...
        {
            "name": "[string: short name of the test N]", 
            "description": "[string: a description what the test N is verifying]", 
            "result": [integer: 0..3]
            "value": "[string: result in words]" 
            "time": "[value]ms"
        }
    ] 
    }

The response has 2 keys:

- meta: metadata for the check
- checks: container for all checks

## meta ##

The meta key has these subkeys

- *"host"*: [string: name of the computer] <span class="required">(*)</span>\
  This is the hostname. The server GUI for the monitoring can group by server. 
  If you host several websites then these have the same "host".
- *"website"*: [string: domain (and maybe path) of the webapp or any clear description. <span class="required">(*)</span>\
  Suggestion: [Application] - [vhost]/path, i.e. "My Wordpress blog - example.com/blog/".
- *"ttl"*: [integer: ttl for the server gui] <span class="optional">(optional)</span> \
  Time to live value in seconds. The server GUI respects this value and does
  not ask the appmonitor client more often. A goof value for beginning is
  60 or 300 (1 min/ 5 min)
- *"result"*: [integer: 0..3] <span class="required">(*)</span>\
  Result code of all checks of the webapp. \
  0 - OK \
  1 - unknown \
  2 - warning \
  3 - error \
  The server GUI will render the view by webapp by this result code.
- *"tags"*: array of tags <span class="optional">(optional)</span> \
  You can send tags to describe kind of tool, department/ developer team, whatever.
  In the server webgui you will see a list of all tags of all monitors and can filter them
- *"time"*: "[value]ms" <span class="optional">(optional)</span> \
  total time that was used for complete run of all checks
  The value must be a float in milliseconds plus additional "ms" (without space). \
  Example: `"time": "0.628ms"`  
- *"notifications"*: notification targets <span class="optional">(optional)</span> \
  Here can be the subkeys (one, any or none)
  - *"email"*: flat list of emails
  - *"slack"*: key-value list with a readable label for the target channel and the Slack webhook url

<span class="required">(*)</span> The keys "host", "website" and "result" are required.

## Checks ##

The section "checks" is a container for the result of all checks.
As an example: To verify the health of a webapp you need to check if the
database is available, permissions exist on needed files or directories,
if the port of a needed service is available.
All these things are several single checks you have to put in the checks
key for the response.

Each check must have these keys:

- *"name"*: [string: short name of the test N] <span class="required">(*)</span> \
  This string is for you - make it unique to identify it in the server GUI.
  i.e. "Mysql-db ABC"
- *"description"*: [string: a description what the test N is verifying] <span class="required">(*)</span> \
  This string is for you - you see the description in the server GUI
  i.e. "Check mysql-db ABC on the server db01"
- *"result"*: [integer: 0..3] <span class="required">(*)</span> \
  result code of the check. The values are the same like the result in the 
  meta section.
  Based on the result code the server GUI renders the item for the check
  (i.e. green if OK, red on error)
- *"value"*: [string: result in words] <span class="required">(*)</span> \
  A human readable text of the result of the ckeck
  i.e. 
  - OK, database was connected successfully
  - ERROR: no write permission on file XY
- *"time"*: "[value]ms" <span class="optional">(optional)</span>\
  time that was used for the single check. The value must be a float in milliseconds plus additional "ms" (without space). \
  Example: `"time": "0.628ms"`

<span class="required">(*)</span> The keys "name", "description", "value" and "result" are required.


# Checks on client side with PHP client #


## Get started ##

The client part of the monitoring. 

1) Below a document root of a website create a directory [webroot]/appmonitor/
2) copy all files of [package]/client/ into [webroot]/appmonitor/
3) verify the installation with your browser
   http://localhost/appmonitor/client/check-appmonitor-server.php
   You should see some JSON output

   
   
### Initialisation ###

Have a look to the example in ./client/index.sample.php

The first step is to initialize the client.

    require_once('appmonitor-client.class.php');
    $oMonitor = new appmonitor();

This will set these default of client metadata with 
- hostname
- dominaname of website and 
- ttl of 300 sec (= 5 min)


### Security ###

You should protect internal data about your application.
The php client offers 2 possibilities:

IP restriction

If the http request does not come from one of the pre defined ip ranges the request will stop with http 403 error.

    $oMonitor->checkIp(array(
        '127.0.0.1',
        '::1',
        '192.168.',
    ));

Usage of a token

You can define a token that the server must send in the GET request. Without or with a wrong token the request will stop with an http 403 error.

    $oMonitor->checkToken('token', '12345678');

... to allow response with correct token only, i.e.
http://localhost/appmonitor/client/mycheck.php?token=12345678

	
### Notifications ###

You can add add notification targets. At the beginning emails and Slack will be supported. 
Here are 2 methods to add the targets:

    // to add notifications
    $oMonitor->addEmail('developer@example.com');
    $oMonitor->addSlackWebhook(array("dev-webhook"=> "https://hooks.slack.com/services/(...)"));

The notification is done on the appmonitor server.


### Add checks ###


You can add several checks with the class that
was initialized on top of the file.

The class has a render method that generates the json for you.

In the area between $oMonitor = new appmonitor(); and render(); you can add
as many checks you want.
The syntax is

	$oMonitor->addCheck(
	array(
		"name" => "[short name of the check]",
		"description" => "[an a bit longer description]",
		"check" => [Array for the check],
	)
	);

The check contains 2 keys:

	"function" => "[Name of a defined check]",
	"params" => [key->value array; count and keys depend on the function]

The checks are defined in appmonitor-checks.class.php as private functions.
To see all defined checks:

    print_r($oMonitor->listChecks());

- checkCert
- checkDiskfree
- checkFile
- checkHttpContent
- checkMysqlConnect
- checkPdoConnect
- checkPortTcp
- checkSimple
- checkSqliteConnect


### Set total result ###

Set the value meta->result for the total status for your webapp.
There is an automatic function that sets the total result to the worst status of any check: If one check ist on warning, 1 on error - then the total result will be an error. For this simple case you can use 

    $oMonitor->setResult();

If you made several checks then not each failure maybe critical to have a runnable application. Example: you write data to an external host once per week, but this host not reachable and the check status is error.
For that constellation you need to calculate the result by yourselfs and set it by

    $oMonitor->render([your value; 0..3]);
	
### Render output ###

This method echoes all information as JSON

    $oMonitor->render();

If you wish to read it then you can use true as param. This feature requires PHP 5.4 or higher.

    $oMonitor->render(true);


## Check functions ##

### Simple ###


The most simple variant is direct call with the resultcode and output text. 

	$oMonitor->addCheck(
        array(
            "name" => "Dummy",
            "description" => "Dummy Test",
            "check" => array(
                "function" => "Simple",
                "params" => array(
                    "result" => 0,
                    "value" => "The dummy test does nothing and was extremely successful",
                ),
            ),
        )
	);


parameters:

- "result" (integer) <span class="required">(*)</span> \
  0   = OK \
  1   = unknown  \
  2   = Warning \
  3   = Error 
- "value" (string) <span class="required">(*)</span>


You can use the simple check to verify anything that has no pre defined function
yet.


### Cert ###

Check if a SSL certificate is still valid ... and does not expire soon.

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


parameters:

- "url" (string) optional: url to connect check i.e. https://example.com:3000; default: own protocol + server of your webapp
- "verify" (boolean) optional: flag verify certificate; default = true
- "warning" (integer) optional: count of days to warn; default=30

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

    $oMonitor->addCheck(
        array(
            "name" => "Certificate check",
            "description" => "Check if SSL cert is valid and does not expire soon",
            "check" => array(
                "function" => "Cert",
            ),
        )
    );



### Diskfree ###

Check if a given filesystem / directory that it has enough space.

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


parameters:

- "directory" (string) directory to check  <span class="required">(*)</span>
- "warning" {integer|string} - size for warning level
- "critical" (integer|string) - size for critical level <span class="required">(*)</span>

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

    "warning"   => "1.25GB",
    "critical"  => "500.7MB",

	

### File ###

Check if a file for file, link or directory. Use the parameter "filename" to set the full filename.

Other given parameters are flags to check. Its values can be set to true (flag must must be true) or false (flag must fail to return a true result). Missing flags won't be checked. 

Giving just a filename without any other flag returns true.

**Example 1**: \
check if "filename" is a directory and is writable

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

**Example 2**: \
With *"exists" => false* you can check if a file does not exist (flag is checked that it is not matching).

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


parameters:

- "filename" (string) filename or directory to check  <span class="required">(*)</span>
- "exists" (boolean) - "filename" must exist/ must be absent
- "dir" (boolean) - filetype directory
- "file" (boolean) - filetype file
- "link" (boolean) - filetype symbolic link
- "executable" (boolean) - flag executable
- "readable" (boolean) - flag is readable
- "writable" (boolean) - flag is writable



### HttpContent ###

This check verifies if a given string exists in the reponse body of a given url.

    $oMonitor->addCheck(
        array(
            "name" => "HttpContent 1",
            "description" => "check string hello in my url",
            "check" => array(
                "function" => "HttpContent",
                "params" => array(
                    "url" => "http://[server]/[path]/",
                    "contains" => "hello",
                    "content" => true
                ),
            ),
        )
    );


parameters:

- url (string) url to fetch <span class="required">(*)</span>
- contains (string) string that must exist in response body <span class="required">(*)</span>
- content (boolean) optional flag: show response? default is false


### MysqlConnect ###

verify a database connection with mysqli real connect function.

    // example: parse a myasl connect string
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

parameters:

- "server"   - hostname/ ip of mysql server <span class="required">(*)</span>
- "user"     - mysql username <span class="required">(*)</span>
- "password" - password <span class="required">(*)</span>
- "db"       - database name / scheme to connect <span class="required">(*)</span>
- "port"     - database port; optional

Remark:  
The idea is not to enter credentials in the parameters. You should parse the config of your application and insert its variables.


### PdoConnect ###

verify a database connection with PDO connect.

PDO supports a wide range of database types - see http://php.net/manual/en/pdo.drivers.php.
BUT: I just started with Mysql. To implement more types go to classes/appmonitor-checks.class.php - method checkPdoConnect().


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

parameters:

- "conect"   - conect string, i.e. 'mysql:host=localhost;port=3306;dbname=mydatabase;' <span class="required">(*)</span>
- "user"     - mysql username <span class="required">(*)</span>
- "password" - password <span class="required">(*)</span>

Remark:  
The idea is not to enter credentials in the parameters. You should parse the config of your application and insert its variables.




### PortTcp ###

Check if the local server is listening to a given port number.


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

parameters:

- "port" (integer) port to check <span class="required">(*)</span>
- "host" (string)  optional: hostname to connect to; if unavailable 127.0.0.1
  will be tested

... and an additional code snippet for a multiple port check:

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


### SqliteConnect ###

Make a database connection to a sqlite database.
The function fails if the filename does not exist or the PDO cannot open it
`$o = new PDO("sqlite:".$aParams["db"]);`

parameters:

- "db" (string) full path of the sqlite database file <span class="required">(*)</span>

	
## Additional Metadata ##

In the meta section will be set the values with the following methods.

The appmonitor client has 
- add* functions to add values - "as many as you want" by repeating the method
- set* function to set a single attribute - by repeating the method the value will be overwritten


### host ###

Set the physical hostname where the application runs.
If no host is given then php_uname("n") will be used to set one.

    // automatic
    $oMonitor->setHost();

    // set a host manually
    $oMonitor->setHost("web-01.example.com");


### website ###

Set a name for this website or application and its environment (dev, test, prod).
If you have several application in subdirectories, i.e. /blog,  /shop...
then you should the path or any description to identify them too

If no argument is given the name of HTTP_HOST will be used.


    // set the application manually
    $oMonitor->setHost("www.example.com - My Wordpress blog");
    $oMonitor->setHost("dev.example.com/shop");

    // set the application  domain manually
    $oMonitor->setHost("Wordpress blog");

### TTL ###

Set a ttl value in seconds to define how long a server should not ask again for a new status of this instance.

You can start with 60 (=1 min) or 300 (5 min).

    $oMonitor->setTTL(60);

	
### Notification ###

You have these notification possibilities to get informed if a service is down ... or available again.

**Email**

Add an E-Mail address.

     $oMonitor->addEmail("[your-email-address]");

To add several email addresses you need this command with each email address you want to add.

**Slack**

You need to create a webhook in slack first. Each webhook has an url like https://hooks.slack.com/services/AAAAA/BBBBB/CCCCCC and will send a message to (exactly) one specific channel.
With the method addSlackWebhook you can add a slack channel where to post the notification. Because the url is not readable you can set a label for better reading (I suggest to set the channel name here).

     $oMonitor->addSlackWebhook("[Label]", "https://hooks.slack.com/services/AAAAA/BBBBB/CCCCCC");

If you would like to notify several Slack channels you need to create an additional Slack Webhook and add it with addSlackWebhook().

### Tags ###

Add a tag to describe the type of the application, the environment, department, dev team, ... whatever.
In the Appmonitor webgui will be dropdown with all tags in alphabetic order. There you can filter monitor checks.

     $oMonitor->addTag("production");
     $oMonitor->addTag("monitoring");



### Set total result value ###

Each added check has a result. The Method setResult() sets the total result
value for the application. The most simple variant is giving no value. 
then it sets the biggest (worst) value of any check: if one of the check has 
two warnings and one ended in an error then tho total value is an error.

    $oMonitor->setResult();

If you you want you can finetune your total result.

Example: you make a check for a job that runs in the night or once per week. 
If the check for reachability of a target to send a report fails (=error)
but other checks sy that the application runs fine - the override the error
with a new total result

    $oMonitor->setResult(2);

Remark: Use $oMonitor->getResults() to get all checks and thir results to 
write your custom logic.


## Send the response ##

### Send JSON ###

After making all checks and setting the total result there is a method to send
the json response:

    $oMonitor->render();

This method supports 2 parameters


| #  | variable | Description |
|--- |---          |---                                                                    |
| 1  | bPretty     | \{bool\} use pretty print; default: false |
| 2  | bHighlight  | \{bool\} use highligthed html instead of json; default: false; if true the response is tex/html and no valid JSON anymore |


### Snippet: show status locally (without appmonitor server) ###

To show the status page on the application server have a look to the snippet 
below. It can be used to show the current status to the users.
This variant is possible if you don't want to give access to the 
Appmonitor server. 

It does not send any notification. And this simple snippet does not care about the TTL ("yet": you need to build it).

    $_SERVER['REMOTE_ADDR']='127.0.0.1';

    // execute checks
    ob_start();
    require __DIR__ . '/../../../appmonitor/index.php';
    $sJson=ob_get_contents();
    ob_end_clean();

    // render
    $oMonitor->renderHtmloutput($sJson);


