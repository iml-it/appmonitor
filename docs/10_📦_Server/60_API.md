# API (WIP) #

With the API you have read only access to information. Only GET requets are supported.

You get a result as JSON object.

## Installation ##

A non pretty url does not need any configuration on a webserver.

`https://www.example.com/api/?&request=[API-URL]`

To use pretty urls like `https://www.example.com/[API-URL]` you need a rewrite:

```txt
    <location /appmonitor/api>
        ...
        RewriteEngine on
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule (/api/.*)$ index.php?request=$1
    </location>
```

## Usage ##

To access data we start with the api version `/v1`.
You get a list of allowed subitems to add.

In the urls below are placeholders with a starting @ character; optionally followed by ":" and a regex that must be matched.

### /v1/apps - application results ###

To access application results we use the `/v1/apps` path.

* `/v1/apps/id` lists monitored applications. You need the key to fetch data of a single aplication.

  * `/v1/apps/id/@appid:[0-9a-f]*` - with adding the appid you get a list of possible data to fetch. The appid is a md5 hash.

    * `/v1/apps/id/@appid:[0-9a-f]*/meta` returns smallest result set with application name and its status.

    * `/v1/apps/id/@appid:[0-9a-f]*/checks` returns all performed checks for the application.

    * `/v1/apps/id/@appid:[0-9a-f]*/all` returns the largest result set with all metadata and checks. Next to the data from /meta or /checks you get more details like summary, timestamp of result, http response header.

### /v1/tags - tags ###

Shows a list of the tags that are in use in all applications.
