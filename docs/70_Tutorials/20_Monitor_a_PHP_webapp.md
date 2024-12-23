## Introduction

You run a PHP application and want to monitor it. Your luck: A PHP client is delivered in the project. 

* Install the Appmonitor client
* Configure the Appmonitor client

## Install the Appmonitor client

On you system with the web application you need to install the Appmonitor client. 
On a shared hosting you can create a subdirectory eg. `[webroot]/appmonitor/`.
If you have more control you can install it outside webroot and add an alias `/appmonitor`. 

👉 See [Install the Appmonitor client](../60_PHP-client/20_Install_PHP-client.md)

## Configure the Appmonitor client

* If you run a PHP application, where a pre defined client check is delivered (see `./client/plugins/apps/`) then you are lucky again and can profit from it: you can start with a preset of application specific checks.
* If not: the folder `./client/plugins/checks/` contains several check items to test http connections, database connections, files and more.<br>You can create your own application check with these check plugins.

