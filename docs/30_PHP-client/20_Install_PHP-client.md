# Get started #

The project package contains the PHP client and the server.

If you run an pplication, CMS, Blog, ... if it is using PHP then you need the files of subdir "client" from [package]/public_html/ only.

# Manual installation #

## Get sources ##

Get the files

- with git pull or
- download and extract the archive in a temporary directory

## Copy needed files ##

If you run an pplication, CMS, Blog, ... if it is using PHP then you need the files of subdir "client" from [package]/public_html/.

1) Below the document root of a website create a new directory, i.e. [webroot]/appmonitor/
2) copy all files of subdir "client" from [package]/public_html/client/ into [webroot]/appmonitor/

# Install with a script #

There is a script in `public_html/client/git_update_appmonitor.sh` that automates the manual steps above.
It acts linke an installer and updater.

Requirements:

- Bash
- rsync
- git

Steps:

- Create a directory "appmonitor" below your webroot.
- Copy git_update_appmonitor.sh there or fetch it as raw file `wget https://raw.githubusercontent.com/iml-it/appmonitor/master/public_html/client/git_update_appmonitor.sh`
- set execute permissions `chmod 755 git_update_apmonitor.sh`
- install files `./git_update_appmonitor.sh`

`./git_update_appmonitor.sh -h` shows a help.

```text

          +-----------------------------------+
          |                                   |
          |  INSTALLER  |                     |
          |      +      |  Appmonitor client  |
          |   UPDATER   |                     |
          |                                   |
          +--------------------------- v0.2 --+


    This is a helper script to get the files of the IML Appmonitor
    client part only.

    This script clones and updates the repository in the /tmp 
    directory and syncs the client files of it to a given directory.

    In the first run it works like an installer.
    On additional runs it updates the files.

    USAGE:

    ./git_update_appmonitor.sh [target path]

        default target is [.] (current directory)

    ./git_update_appmonitor.sh -h|--help

        Show this help.

```
