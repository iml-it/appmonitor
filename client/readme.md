
# [APPMONITOR](../readme.md) > CLIENT #

OPEN SOURCE from University Berne :: IML - Institute of Medical Education

https://github.com/iml-it/appmonitor

- - -


# How does ist work? #

An application creates a JSON in a predefined structure. 

The main idea is to make the checks with permissions of the application and with the own credentials. Check if directories or files are writable, a connection to services like databases, email, external http APIs/ ressources, ... whatever.


# Requirements #

(For the delivered PHP client - you can implement a client in other languages)
- PHP7 (up to PHP 7.2; runs on php5 but is not suppoorted)



# Description of metadata / NON-PHP clients #

If you dont use php on your webserver you can create your own client that 
returns JSON answers with the conventions described below.


    {
    "meta": {
        "host": "[string: name of the computer]", 
        "website": "[string: description of the webapp]", 
        "ttl": [integer: ttl for the server gui], 
        "result": [integer: 0..3],
        "notifications": {
            "email": [
                "email1@example.com",
                "email2@example.com"
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
            "value": "[string: result in words]" 
        },
	...
        {
            "name": "[string: short name of the test N]", 
            "description": "[string: a description what the test N is verifying]", 
            "result": [integer: 0..3]
            "value": "[string: result in words]" 
        }
    ] 
    }

The response has 2 keys:

- meta: metadata for the check
- checks: container for all checks

## meta ##

The meta key has these subkeys

- "host": [string: name of the computer] \
  This is the hostname. The server GUI for the monitoring can group by server. 
  If you host several websites then these have the same "host".
- "website": [string: domain (and maybe path) of the webapp or any clear description. 
  Suggestion: [Application] - [vhost]/path, i.e. "My Wordpress blog - example.com/blog/".
- "ttl": [integer: ttl for the server gui] \
  Time to live value in seconds. The server GUI respects this value and does
  not ask the appmonitor client more often. A goof value for beginning is
  60 or 300 (1 min/ 5 min)
- "result": [integer: 0..3] \
  Result code of all checks of the webapp. \
  0 - OK \
  1 - unknown \
  2 - warning \
  3 - error \
  The server GUI will render the view by webapp by this result code.
- "notifications": notification targets (optional) \
  Here can be the subkeys 
  - "email": flat list of emails
  - "slack": key-value list with a readable label for the target channel and the Slack webhook url


  
## checks ##

The section "checks" is a container for the result of all checks.
As an example: To verify the health of a webapp you need to check if the
database is available, permissions exist on needed files or directories,
if the port of a needed service is available.
All these things are several single checks you have to put in the checks
key for the response.

Each check must have these keys:

- "name": [string: short name of the test N]
  This string is for you - make it unique to identify it in the server GUI.
  i.e. "Mysql-db ABC"
- "description": [string: a description what the test N is verifying]
  This string is for you - you see the description in the server GUI
  i.e. "Check mysql-db ABC on the server db01"
- "result": [integer: 0..3]
  result code of the check. The values are the same like the result in the 
  meta section.
  Based on the result code the server GUI renders the item for the check
  (i.e. green if OK, red on error)
- "value": [string: result in words]
  A human readable text of the result of the ckeck
  i.e. 
  - OK, database was connected successfully
  - ERROR: no write permission on file XY


# Checks on client side with PHP #


## Get started ##

The client part of the monitoring. 

1) Below a document root of a website create a directory [webroot]/appmonitor/
2) copy all files of [package]/client/ into [webroot]/appmonitor/
3) verify the installation with your browser
   http://localhost/appmonitor/client/check-appmonitor-server.php
   You should see some JSON output

   
   
### Initialiasation ###

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

- checkSimple 
- checkHttpContent 
- checkMysqlConnect 
- checkSqliteConnect 
- checkListeningIp


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


## SIMPLE ##



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

- result (integer)  
  0   = OK  
  1   = unknown  
  2   = Warning  
  3   = Error  
- value (string)


You can use the simple check to verify anything that has no pre defined function
yet.

## HTTPCONTENT ##

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
                ),
            ),
        )
    );


parameters:

- url (string) url to fetch
- contains (string) string that must exist in response body


## checkMysqlConnect ##

verify a database connection with mysqli_connect function.

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
                ),
            ),
        )
    );

parameters:

- "server"   - hostname/ ip of mysql server
- "user"     - mysql username
- "password" - password
- "db"       - database to connect


## checkSqliteConnect ##

Make a database connection to a sqlite database.
The function fails if the filename does not exist or the PDO cannot open it
`$o = new PDO("sqlite:".$aParams["db"]);`

parameters:

- "db" (string) full path of the sqlite database file


## checkPortTcp ##

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

- "port" (integer) port to check
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

