## Bundled checks for an application

For a product which has multiple or thousands of instances it is useful, tho have a pre defined set of checks.

This is work in progress.

In the folder plugins/apps are the files that contain grouped checks for a few applications.

In a check script instead of writing single checks include the bundle:

```php
// set variable sApproot
$sApproot = $_SERVER['DOCUMENT_ROOT'];
// include default checks for an application
@require 'plugins/apps/[name-of-app].php';
```
