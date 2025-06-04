## Restrict access

**Important remark:**
The appmonitor has no user login. Protect it by configuration of your webserver, i.e. use ip restriction and/ or basic authentication.

## User access to server

### General access to the web interface

Here is a snippet that forces a user login with basic auth - or access from a given
ip address without any password.

```txt
  <Location "/appmonitor/server">
    Require valid-user
    AuthType Basic
    # ...
    <RequireAny>
      Require valid-user
      Require ip 192.168.10.22
    </RequireAny>

  </Location>

  <Location "/appmonitor/api">
    Require all denied
  </Location>
```

Remove \<RequireAny\> and \</RequireAny\> to combine it and allow password based access
from the given ip address and from nowhere else.

### Appmonitor user configuration

Let's have a look to the configuration file.
The Appmonitor tries to fetch an authenticated user from PHP_AUTH_USER. You can add
other fields from the server scope `$_SERVER` in the userfields section:

```txt
    ...
    "userfields": [
        "PHP_AUTH_USER"
    ],
    ...
```

A user could match two catchall fields:

* `*` (asterisk) matches all anonoymous users.
  * giving all roles is for your development environment.
  * If you enable authentication on /server subdirectory in the webserver then this setion is unreachable

* `__default_authenticated_user__` matches any authenticated user.
  * set the default roles here.

Add a section for each known `[username]` to define its roles on account level.

```txt
    "users": {
        "*": {
            "password": false,
            "username": "anonymous",
            "comment": "anonymous access",
            "roles": [ "api", "ui", "ui-config", "ui-debug" ]
        },
        "__default_authenticated_user__": {
            "comment": "default roles for an by the webserver authenticated user",
            "roles": [ "api", "ui" ]
        },
        "axel": {
            "password": false,
            "username": "Axel",
            "comment": "Sysadmin",
            "roles": [
                "*"
            ]
        },

    },
    ...
```

## MFA

⚠️ This feature is work in progress.

Since April 2025 the program includes a mfa client that connects to an MFA server instance - <https://git-repo.iml.unibe.ch/iml-open-source/mfa-server>.

(1)

You need to run a MFA server instance before using before you can configure a connection to it on IML Appmonitor.

In its admin add a new application with name and the url of the web app.
You get a snippet with application id and a generated secret.

(2)

You need to enable mfa.

You can configure the IML appmonitor to use mfa for all users or users with edit permissions only by setting a role.
The value for "include" points to a script that will be includede to ensure that a mfa chllenge was solved.

Configuration snippet:

```txt
    "mfa": {
        "role": "ui-config" << "ui" or "ui-config"
        "include": "/vendor/mfa-client/mfa-ensure.php"
    },
    ...
```

(3)

Create the file `/server/vendor/mfa-client/mfaconfig.php`. Paste the code snippet with the configuration to connect the MFA server api.

```text
<?php

return [

    "api" => "https://mfa.example.com/api/",
    "appid" => "c1cabd22fbdb698861ad08b27de7399a",
    "shared_secret" => "p9wjjXSewZq0VkM1t5Sm3ZbI4ATEVetU",

    "debug" => false,

];
```

## Sensitive data

These files _may_ contain sensitive data and could be interesting for hackers. Deny the web access for

* [approot]/server/config/
* [approot]/server/data/
* [approot]/server/tmp/

In both directories is a .htaccess - if you set _AllowOverride Limit_ these .htaccess will be used. Otherwise create a directory or Location section to deny the web access.

```txt
  <Location "/appmonitor/server/config">
    Require all denied
  </Location>

  <Location "/appmonitor/server/data">
    Require all denied
  </Location>

  <Location "/appmonitor/server/tmp">
    Require all denied
  </Location>
```

## api access

### Limit by webserver configuration

The api access should be limited to the systems that need access to it.

```txt
  <Location "/appmonitor/api">
    ...
    Require ip 192.168.10.22
    ...
  </Location>
```

If request doesn't come from an allowed ip the response is a 401 with html content for your 40x message.

### Limit by app ip address

You can limit the ip address in the configuration:

```txt
    ...
    "api":{
        "sourceips":[
            "^127\\.0\\.0\\.1$"
        ],
    ...
```

If request doesn't come from an allowed ip the response is a 401 with this body

```txt
{ "http": 401, "error": "ERROR: IP 127.0.0.1 is not allowed.", "_header": "HTTP\/1.1 401 Not autorized" }
```

### Define an api user

On server side you must define one or more users with the role "api".
You can use 

* anonymous access or
* basic authentication or
* hmac hash key

#### Allow anonymous access

This is maybe for a dev environment only. Or you can restrict the ip access to single ip addresses.

For user `*` define as settings:

* passworrd: false
* rules: add "api"

```txt
"*": {
    "password": false,
    "roles": [
        "api"
    ]
}
```

#### Basic authentication

Create a password hash eg on terminal:

```txt
php -r "echo password_hash('<your_password>', PASSWORD_DEFAULT);"
$2y$10$4r8V2Ys2euGyJvLALGjsWuZ8BRD7eSEBPOe36UYZApm4cEBpm6BVS
```

This hash must be set as password.

```txt
"api-basic": {
    "passwordhash": "$2y$10$4r8V2Ys2euGyJvLALGjsWuZ8BRD7eSEBPOe36UYZApm4cEBpm6BVS⏎",
    "roles": [
        "api"
    ]
}
```

OR

If you rollout servers with Ansible, Chef, Puppet, ... you maybe cannot create such a hash.
It is possible to configure the password as clear text too:


```txt
"api-basic": {
    "password": "<your_password>",
    "roles": [
        "api"
    ]
}
```

Shell:

In curl you can use the parameter -u: `curl -u "api-basic:<your_password>" <url>`

JS Snippet:

```js
var apiuser = "";
var apipassword = "";

var oHeader = { "headers": {
   "Authorization": "Basic " + btoa(apiuser + ":" + apipassword)
   }
};

let response = await fetch(url, oHeader);
```

#### HMAC hash key

To use hmac hash key you need to define a shared secret for a user.

```txt
"api-hamac": {
    "secret": "Here-is-my-most-secret-secret.",
    "roles": [
        "api"
    ]
}
```

The client must sent an Authentication header with your configured username and base64 encoded hash

```txt
Authorization: HMAC-SHA1 <user>:<HASH>
```

eg.

```txt
Authorization: HMAC-SHA1 api-hamac:HMAC-SHA1 api-test:MjUyZWZjMmMzMTU5ZjNjYWViMDY1OTEzMTY4NzAzZDRiNGY3YjQ5Mw==
```

The hash is generated with

* Method: "GET"
* Request: eg "/api/v1/apps/tags/monitoring/meta"
* Date: eg "Thu, 14 Nov 2024 16:10:06.663974972 CET"

All values must be concatinated with `\n` - but no final `\n`.
It will be hashed with a hmac function using SHA 1 and the shared secret.
Finally the string must be base64 encoded.

Sounds complicated? But you don't need to code it yourself. You can find API clients in PHP and bash using curl here: 
<https://github.com/iml-it/appmonitor-api-client/>. 
With them all given authentication methods above will work.
