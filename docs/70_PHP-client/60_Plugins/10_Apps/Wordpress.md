## Wordpress

* Website: <https://wordpress.org/>
* Author: 
* Source: <https://github.com/WordPress/WordPress>
* License: GNU General Public License 2

### Minimal variant

You can open .../appmonitor/plugins/apps/**wordpress**.php as url like

<https://www.example.com/appmonitor/plugins/apps/wordpress.php>

... or for a subfolder add the url parameter "?rel=[subdir]".

Example:

<https://www.example.com/appmonitor/plugins/apps/wordpress.php?rel=/blog>

You should get a JSON response.

## Errors

During the first steps when trying to find the right url you might get one of these check specific errors (Next to those named in the introduction page):

* `ERROR: Config file [wp-config.php] was not found. Set a correct app root pointing to wordpress install dir.`

    **Problem**: The application root was not found. Below it the file wp-config.php is expected.

    **Solution**: Use the parameter rel=... to set the correct subdir
