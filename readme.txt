
	APPMONITOR
	
	Universität Bern
	IML - Institut für medizinische Lehre

	
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
  1   = Warning
  2   = Error
  255 = unknown
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
this check is based on netstat command and works unix based systems only.

parameters:
- "port" (integer) port to check
