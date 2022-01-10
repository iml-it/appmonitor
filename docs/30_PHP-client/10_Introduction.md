<style>
	.required{color:#f22;}
	.optional{color:#888;}
</style>

[TOC]
# Requirements #

The delivered PHP client (you can implement a client in other languages) needs

- PHP 7 or 8 (up to PHP 8.1)

# Quick start #

## Check on Appmonitor server ##

The project package contains the PHP client and the server. The PHP client is used for the Appmonitor server to verify its own installation. 

If you have a server installation (see [Server](../10_Server/10_Introduction.md)) verify the installation with your browser <http://localhost/appmonitor/client/check-appmonitor-server.php> You should see some JSON output.

## Installation for another PHP application ##

If you run an pplication, CMS, Blog, ... if it is using PHP then you need the files of subdir "client" from [package]/public_html/.

1) Below the document root of a website create a directory [webroot]/appmonitor/
2) copy all files of subdir "client" from [package]/public_html/client/ into [webroot]/appmonitor/
