## Concrete 5 CMS

### Minimal variant

The following steps describe a first (and most simple) approach for a Wordpress monitoring with IML appmonitor.

If you have installed ...

* Concrete5 directly into `[webroot]`
* the appmonitor client in `[webroot]/appmonitor/`.

Create a file `[webroot]/appmonitor/minimal.php` with this content:

```php
<?php
$sApproot=$_SERVER['DOCUMENT_ROOT'];
@require 'plugins/apps/concrete5.php';
```

If Conrete5 was installed in a subdirectory you need to set its path, eg.

```php
$sApproot = $_SERVER['DOCUMENT_ROOT'].'/c5'; 
```

Then request this file, eg. <https://example.com/appmonitor/minimal.php>. You should get a JSON response.

If so then add this url in Appmonitor server. Done.

!!! "Note"
    This is the most simple variant and just a quick winner.
    You cannot customize the builtin checks or other metadata. 
