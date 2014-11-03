
	APPMONITOR
	
	University Berne
	IML - Institute for medical education

	
========== ABOUT

The appmonitor consists of 2 parts
- client installation - this makes necessary checks for a website and responds
  JSON data
- server - it collects all JSON data and presents an overview over all checks
  and websites

The server uses 
- a cache class that stores serialized data as file
  http://www.axel-hahn.de/projects/php/ahcache/
- jquery and datatables plugin
  http://jquery.com/
  http://datatables.net/


========== REQUIREMENTS

------- CLIENT

- php5 (*)

This app ships a php client.
You can implement the client in other languages. You need to create scripts
that makes the wanted checks and sends a result as JSON in a given structure.

see section NON-PHP CLIENTS.


------- SERVER

- php5.3
- php5-curl


========== INSTALLATION


------- CLIENT

The client part of the monitoring. 
1) Below a document root of a website create a directory [webroot]/appmonitor/
2) copy all files of [package]/client/ into [webroot]/appmonitor/
3) copy [webroot]/appmonitor/index-sample.php to [webroot]/appmonitor/index.php
4) verify the installation with your browser
   http://[your-website]/appmonitor/

------- SERVER

The webgui that fetches all client checks and renders a website.
1) Below a document root of the monitoring server create a directory 
   [webroot]/appmonitor/
2) copy all files of [package]/server/ into [webroot]/appmonitor/
3) copy [webroot]/appmonitor/appmonitor-server-config-sample.json
   to [webroot]/appmonitor/appmonitor-server-config.json
4) open appmonitor-server-config.json and verify the url for the
   appmonitor client:
   {
       "urls":[
           "http://localhost/appmonitor/"
       ]
   }
5) verify the installation with your browser
   http://[your-website]/appmonitor/server.php

Remarks:
- The filenames of client and server are unique. You can put the files for 
  client and server into the same directory too.
- the server creates a cache directory below webroot
  [webroot]/~cache/
- The steps use the subdir ./appmonitor/ - but you can put all files
  into any directory/ subdirectory.


========== CHECKS ON CLIENT SIDE

in the index.php of a client you can add several checks with the class that
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



------- SIMPLE

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

------- HTTPCONTENT

This check verifies if a given string exists in the reponse body of a given url.

$oMonitor->addCheck(
        array(
            "name" => "HttpContent 1",
            "description" => "check string hello in my url",
            "check" => array(
                "function" => "HttpContent",
                "params" => array(
                    "url" => "http://[server]/[path]/",
                    "value" => "hello",
                ),
            ),
        )
);


parameters:
- url (string) url to fetch
- contains (string) string that must exist in response body


------- checkMysqlConnect 

verify a database connection with mysqli_connect function.

mysqli_connect(
	$aParams["server"], 
	$aParams["user"], 
	$aParams["password"], 
	$aParams["db"]
);

parameters:
- "server" 
- "user" 
- "password"
- "db" 

------- checkSqliteConnect 

make a database connection to a sqlite database.
The function fails if the filename does not exist or the PDO cannot open it
$o = new PDO("sqlite:".$aParams["db"]);

parameters:
- "db" (string) full path of the sqlite database file


------- checkListeningIp

Check if the local server is listening to a given port number.

Remark: 
this check is based on netstat command and works unix based systems only 
(so far).

parameters:
- "port" (integer) port to check


========== NON-PHP CLIENTS

If you dont use php on your webserver you can create your own client that 
returns JSON answers with the conventions described below.


{
    "meta": {
        "host": "[string: name of the computer]", 
        "website": "[string: domain (and maybe path) of the webapp]", 
        "ttl": [integer: ttl for the server gui], 
        "ts": [integer: unix timestamp], 
        "result": [integer: 0..3]
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

----- meta

The meta key has these subkeys

- "host": [string: name of the computer] 
  This is the hostname. The server GUI for the monitoring can group by server. 
  If you host several websites then these have the same "host".

- "website": [string: domain (and maybe path) of the webapp]

- "ttl": [integer: ttl for the server gui]
  Time to live value in seconds. The server GUI respects this value and does
  not ask the appmonitor client more often. A goof value for beginning is
  60 or 300 (1 min/ 5 min)
  
- "ts": [integer: unix timestamp]
  unix timestamp of the request. Maybe it is obsolet.
  
- "result": [integer: 0..3]
  Result code of all checks of the webapp.
  0 - OK
  1 - unknown
  2 - warning
  3 - error
  The server GUI will render the view by webapp by this result code.

  
----- checks

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

