## Concrete 5 CMS

### Minimal variant

You can open .../appmonitor/plugins/apps/**concrete5**.php as url like

<https://www.example.com/appmonitor/plugins/apps/concrete5.php>

You should get a JSON response.

### Custom application dir

If your CMS isn't installed directly in the webroot but in a subfolder then you can add the url parameter "?rel=[subdir]".

Example:

<https://www.example.com/appmonitor/plugins/apps/concrete5.php?rel=/c5>

## Errors

During the first steps when trying to find the right url you might get one of these check specific errors (Next to those named in the introduction page):

* `ERROR: Config file was not found. Set a correct $sApproot pointing to C5 install dir.`

    **Problem**: The application root was not found. Below it the file application/config/database.php is expected.

    **Solution**: Use the parameter rel=... to set the correct subdir

* `ERROR: Config file application/config/database.php was read - but database connection could not be detected from it in connections -> '[NAME]'.`

    **Problem**: The database configuration was not detected in the config file.

    **Solution**: Check the configuration file. There is an entry "default-connection". The database settings will be taken from a section with that name. If you C5 instance is running but the configuration differs, then contact the plugin author.
