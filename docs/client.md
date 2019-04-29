<style>
	.required{color:#f22;}
	.optional{color:#888;}
</style>


# [APPMONITOR](../readme.md) > CLIENT #

Free software and Open Source from University of Bern :: IML - Institute of Medical Education

https://github.com/iml-it/appmonitor

- - -


# How does it work? #


The main idea is to make the checks with permissions of the application and with its credentials. Check if directories or files are writable, a connection to services like databases, email, external http APIs/ ressources, ... whatever.

An application check finally creates a JSON in a predefined structure.

![Client](images/appmonitor-overview-client.png "Client")



# Requirements #

For the delivered PHP client:
- PHP7 (up to PHP 7.3; runs on php5 but is not supported) \
  see [PHP-Client](client-php.md)

You can implement a client in other languages.


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
