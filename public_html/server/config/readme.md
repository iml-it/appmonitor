# IML Appmonitor #

## Description ##

In the `./config/` directory contains the configuration of the server component.

## Security ##

The web access to `./config/` directory should be denied.
This is tested with delivered check.

## Content in the directories ##

| file                                  | description
|---                                    |---
|appmonitor-server-config-defaults.json |default config
|appmonitor-server-config.json          |user config 


DO NOT EDIT appmonitor-server-config-defaults.json.
This file will contain the defaults for new versions and will be overwritten on updates

Recommendations:
- copy appmonitor-server-config-defaults.json to appmonitor-server-config.json 
    (same name without "-defaults") and make the changes in that file
- use the web gui and add a client url
- use the CLI tool (./bin/cli.php) to edit config entries
