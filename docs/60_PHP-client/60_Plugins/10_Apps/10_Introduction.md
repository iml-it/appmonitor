## Introduction

The following steps describe a first (and most simple) approach for an application monitoring with IML appmonitor.

If you have installed ...

* the app directly into `[webroot]`
* the appmonitor client in `[webroot]/appmonitor/`.

!!! info "Note"
    This is just a quick winner with most basic checks.
    You cannot customize the builtin checks or other metadata.
    Create a custom check and add all checks you need to test tha ability to run the application.

### Minimal variant

https://www.example.com/appmonitor/plugins/apps/[NAME].php

You should get a JSON response. For NAME insert an existing filename for a product.

### Custom application dir

If your CMS isn't installed directly in the webroot but in a subfolder then you can add the url parameter "rel=[subdir]".

Example:

https://www.example.com/appmonitor/plugins/apps/[NAME].php?rel=/myapp

## Errors

During the first steps when trying to find the right url you might get these errors:

* `Page Not found`

    **Problem**: You didn't point to the correct file.

    **Solution**: Fix the url to pint to the concrete5.php.

* `ERROR: The given rel dir does not exist below webroot.`

    **Problem**: You used the parameter rel=... but the given directory does not exist below webroot.

    **Solution**: Fix the value behind rel= and set it to an existing directory.
