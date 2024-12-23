## Matomo

Website: <https://matomo.org/>

### Minimal variant

You can open .../appmonitor/plugins/apps/**matomo**.php as url like

<https://www.example.com/appmonitor/plugins/apps/matomo.php>

You should get a JSON response.

### Custom application dir

If your Webapp isn't installed directly in the webroot but in a subfolder then you can add the url parameter "?rel=[subdir]".

Example:

<https://www.example.com/appmonitor/plugins/apps/matomo.php?rel=/matomo>

## Errors

During the first steps when trying to find the right url you might get one of these check specific errors (Next to those named in the introduction page):

* `ERROR: Config file was not found. Set a correct $sApproot pointing to Matomo install dir`

    **Problem**: The application root was not found. Below it the file config/config.ini.php is expected.

    **Solution**: Use the parameter rel=... to set the correct subdir
