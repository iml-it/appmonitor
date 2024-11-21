## Concrete 5 CMS

### Minimal variant

You can open .../appmonitor/plugins/apps/**concrete5**.php as url like

<https://www.example.com/appmonitor/plugins/apps/concrete5.php>

You should get a JSON response.

### Custom application dir

If your CMS isn't installed directly in the webroot but in a subfolder then We suggest the following:

(1)

Create a file .../appmonitor/**check_c5.php**.
Define a variable `$sApproot` with the application basedir.

```php
<?php
$sApproot = $_SERVER['DOCUMENT_ROOT'].'/c5'; 
@require 'plugins/apps/concrete5.php';
```

(2)

Then request this file, eg. <https://www.example.com/appmonitor/check_c5.php>.
