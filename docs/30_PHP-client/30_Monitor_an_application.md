# Monitor an application #

To monitor an application we need a file that can send metadata (infos about application, ttl) and the result of one or several checks.

1) start with a single "simple" check and response just an OK. Then add the client url in the appmonitor server backend. The most primitive check in the monitoring is better than no monitoring. 
2) Step by step add more checks to verify that all needed requirements and services that your application can run smoothly. To write your own checks ... these are some ideas you can pick from:
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

3) Add tags and notifications.
4) Finetuning: check some edge cases and security checks.

If you don't know how to continue after the first simple check and  and what else to check ... 

- check if a pre defined check exists in https://github.com/iml-it/appmonitor-clients/tree/master/client
- locate the config
  - try to load the config - check their values.
  - if there is a class with methods to access config data use the application way

## First example ##

Let's have a look to the sample file in public_html/client/index.sample.php ...

```php
require_once('classes/appmonitor-client.class.php');
$oMonitor = new appmonitor();

// set a name with application name and environment or hostname
$oMonitor->setWebsite('[My CMS on host XY]');

// how often the server should ask for updates
$oMonitor->setTTL(300);

// a general include ... the idea is to a file with the same actions on all
// installations and hosts that can be deployed by a software delivery service 
// (Puppet, Ansible, ...)
@include 'general_include.php';

// add any tag to add it in the filter list in the server web gui
// $oMonitor->addTag('cms');
// $oMonitor->addTag('production');

// ----------------------------------------------------------------------

// include default checks for an application
// @require 'plugins/apps/[name-of-app].php';

// add a few custom checks
// $oMonitor->addCheck(...)
$oMonitor->addCheck(
    array(
        "name" => "hello plugin",
        "description" => "Test a plugin ... plugins/checks/hello.php",
        "check" => array(
            "function" => "Hello",
            "params" => array(
                "message" => "Here I am",
            ),
        ),
    )
);

// ----------------------------------------------------------------------

$oMonitor->setResult();
$oMonitor->render();

```

And now we go through it.

# Create a php file #

Go to the directory `[webroot]/appmonitor/`.

Have a look to the example in index.sample.php. Duplicate it to write your own checks.

- If you have a single application below webroot you can create a file **index.php**. 
- If you have multiple applications below webroot (eg. on a shared hosting) then create a unique file per application like **check_blog.php**

The filename itself is not important for functionality. It is used for the server instance to add your application check there.

# Include appmonitor client class #

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

### IP restriction ###

If the http request does not come from one of the pre defined ip ranges the request will stop with http 403 error.

```php
$oMonitor->checkIp(array(
  '127.0.0.1',  
  '::1',
  '192.168.',
));
```

### Usage of a token ###

You can define a token that the server must send in the GET request. Without or with a wrong token the request will stop with an http 403 error.

    $oMonitor->checkToken('token', '12345678');

... to allow response with correct token only, i.e.
http://localhost/appmonitor/client/mycheck.php?token=12345678

# Add Metadata #

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

# Add Checks #

See [Check items](40_Check_items.md) to get a list of all available checks.

Additionally you can write custom checks as plugins. 
See [Write checks](70_Write_checks.md) for details.


# Prepare the response #

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


# Other client functions #


## Abort a check ##

If you process the checks and need to exit the client with a critcal error
you can use the method abort().

This triggers a 503 service unavailable error with a given message.

This method should be used if a basic element is missed to perform a useful
check, i.e. a config file is not found.

```php
$oMonitor->abort([{string} message]);
```

## Snippet: show status locally (without appmonitor server) ##

To show the status page on the application server have a look to the snippet 
below. It can be used to show the current status to the users.
This variant is possible if you don't want to give access to the 
Appmonitor server. 

It does not send any notification. And this simple snippet does not care about the TTL ("yet": you need to build it).

```php
<?php
// execute checks
require __DIR__ . '/check-appmonitor-server.php';
$sJson=ob_get_contents();
ob_end_clean();

// render
$oMonitor->renderHtmloutput($sJson);
```
