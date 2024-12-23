## Introduction

The following steps describe a first (and most simple) approach for an application monitoring with IML appmonitor.

If you have installed ...

* the app directly into `[webroot]` (if not: you need `rel` to point to the correct subdir - see below)
* the appmonitor client in `[webroot]/appmonitor/`.

!!! info "Note"
    This is just a quick winner with most basic checks.<br>
    You cannot customize the builtin checks and influence a few metadata.<br>
    Create a custom check and add all checks you need to test tha ability to run the application.

### Minimal variant

https://www.example.com/appmonitor/plugins/apps/[NAME].php

You should get a JSON response. For NAME insert an existing filename for a product. If it was installed directly in the webroot.

### Default parameters

All application checks support the following url (GET) parameters:

| Parameter | Description | Example
|--         |--           |--
| `rel`     | The relative path to the application root. Use this if your application is not installed directly in the webroot.| <https://www.example.com/appmonitor/plugins/apps/[NAME].php?rel=/myapp>
| `host`    | Set a custom name for the serving hostname. Use this override if the default hostname is unwanted.| <https://www.example.com/appmonitor/plugins/apps/[NAME].php?rel=/myapp&host=web01.exampe.com>
| `name`    | The name of the website or web application.| <https://www.example.com/appmonitor/plugins/apps/[NAME].php?rel=/myapp&name=Company%20CMS>
| `tags`    | A list of tags to add to the application. Multiple tags are separated with comma `,`| <https://www.example.com/appmonitor/plugins/apps/[NAME].php?rel=/myapp&tags=tag1.tag2>

## Errors

During the first steps when trying to find the right url you might get these errors:

* `Page Not found`

    **Problem**: You didn't point to the correct file.

    **Solution**: Fix the url to pint to the concrete5.php.

* `ERROR: The given rel dir does not exist below webroot.`

    **Problem**: You used the parameter rel=... but the given directory does not exist below webroot.

    **Solution**: Fix the value behind rel= and set it to an existing directory.
