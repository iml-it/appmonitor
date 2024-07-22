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

### Sensitive data

These files _may_ contain sensitive data and could be interesting for hackers. Deny the web access for

* [approot]/server/config/
* [approot]/server/tmp/

In both directories is a .htaccess - if you set _AllowOverride Limit_ these .htaccess will be used. Otherwise create a directory or Location section to deny the web access.

```txt
  <Location "/appmonitor/server/config">
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

But shure it is better to have an additional password based access with api users.

### Limit by app configuration

You can limit the ip address in the configuration too.

```txt
    ...
    "api":{
        "sourceips":[
            "^127\\.0\\.0\\.1$"
        ],
    ...
```

Without basic authentication on subdir /api you can handle authenticated requests too.

* works on disabled basic authentication in the webserver config only
* add an api user - set a password value and include "api" in the roles

```txt
    ...
        "apiuser1": {
            "password": "$2y$10$5E4ZWyul.VdZjpP1.Ff6Le0z0kxu3ix7jnbYhv0Zg5vhvhjdJTOm6",
            "comment": "api user for Axels Dashboard",
            "roles": [ "api" ]
        },
```

Then you must send a basic authentication header on each api request.
In javascript you can use the fetch function by adding a custom header field "Authorization"
that contains Username + ":" + Password as base64 encoded string.

Snippet:

```js
var apiuser = "";
var apipassword = "";

var oHeader = { "headers": {
   "Authorization": "Basic " + btoa(apiuser + ":" + apipassword)
   }
};

let response = await fetch(url, oHeader);
```
