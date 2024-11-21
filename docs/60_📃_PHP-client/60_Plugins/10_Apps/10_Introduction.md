## Introduction

The following steps describe a first (and most simple) approach for an application monitoring with IML appmonitor.

If you have installed ...

* the app directly into `[webroot]`
* the appmonitor client in `[webroot]/appmonitor/`.

### Minimal variant

https://www.example.com/appmonitor/plugins/apps/[NAME].php

You should get a JSON response. For NAME insert an existing filename for a product.

### Custom application dir

If your CMS isn't installed directly in the webroot but in a subfolder then We suggest the following:

(1)

Create a file `[webroot]/appmonitor/check_NAME.php`.
Define a variable `$sApproot` with the application basedir.

```php
<?php
$sApproot = $_SERVER['DOCUMENT_ROOT'].'/somewhere'; 
@require 'plugins/apps/check_NAME.php';
```

!!! "Note"
    This is just a quick winner with most basic checks.
    You cannot customize the builtin checks or other metadata.
    Create a custom check and add all checks you need to test tha ability to run the application.
