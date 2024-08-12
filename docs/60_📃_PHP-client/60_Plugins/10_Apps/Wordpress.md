## Wordpress

### Minimal variant

The following steps describe a first (and most simple) approach for a Wordpress monitoring with IML appmonitor.

If you have installed ...

* Wordpress directly into `[webroot]`
* the appmonitor client in `[webroot]/appmonitor/`.

Create a file `[webroot]/appmonitor/minimal.php` with this content:

```php
<?php
$sApproot = $_SERVER['DOCUMENT_ROOT']; 
@require 'plugins/apps/wordpress.php';
```

If wordpress was installed in a subdirectory you need to set its path, eg.

```php
$sApproot = $_SERVER['DOCUMENT_ROOT'].'/wordpress'; 
```

Then request this file, eg. <https://example.com/appmonitor/minimal.php>. You should get a JSON response.

If so then add this url in Appmonitor server. Done.

!!! "Note"
    This is the most simple variant and just a quick winner.
    You cannot customize the builtin checks or other metadata. 

### Customize checks

This is the preferred variant. You can...

* set metadata: 
  * name of the instance (website)
  * customize the ttl
  * set an app specific contact that will be informed on errors
* add additional checks

Approach:

* initialize the appmonitor class
* set wanted metadata
* include the app plugin for wordpress
* add your own checks
* send the response

Create a file `[webroot]/appmonitor/check-wordpress.php` with this content:

```php
<?php

// ----------------------------------------------------------------------
// initialize
require_once('classes/appmonitor-client.class.php');
$oMonitor = new appmonitor();

// ----------------------------------------------------------------------
// set metadata
// $oMonitor->setWebsite('My wordpress Blog');
// $oMonitor->setTTL(300);
// $oMonitor->addTag('production');

// ----------------------------------------------------------------------
// include the app plugin for wordpress
$sApproot = $_SERVER['DOCUMENT_ROOT']; 
@require 'plugins/apps/wordpress.php';

// ----------------------------------------------------------------------
// add your custom checks
// $oMonitor->addCheck(...)

// ----------------------------------------------------------------------
// send the response

$oMonitor->setResult();
$oMonitor->render();

// ----------------------------------------------------------------------
```

Check it with <https://example.com/appmonitor/check-wordpress.php>.
