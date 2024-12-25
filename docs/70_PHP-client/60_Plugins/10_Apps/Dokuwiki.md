## Dokuwiki

* Website: <https://www.dokuwiki.org/>
* Author:  splitbrain Andreas Gohr
* Source: <https://github.com/dokuwiki/dokuwiki>
* License: GNU General Public License 2

### Minimal variant

You can open .../appmonitor/plugins/apps/**dokuwiki**.php as url like

<https://www.example.com/appmonitor/plugins/apps/dokuwiki.php>

... or for a subfolder add the url parameter "?rel=[subdir]".

Example:

<https://www.example.com/appmonitor/plugins/apps/dokuwiki.php?rel=/wiki>

You should get a JSON response.

## Errors

During the first steps when trying to find the right url you might get one of these check specific errors (Next to those named in the introduction page):

* `ERROR: Config file was not found. Use ?rel=[subdir] to set the correct subdir to find /conf/local.php.`

    **Problem**: The application root was not found. Below it the file conf/local.php is expected.

    **Solution**: Use the parameter rel=... to set the correct subdir
