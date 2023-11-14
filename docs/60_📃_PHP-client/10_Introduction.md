# Requirements #

The delivered PHP client (you can implement a client in other languages) needs

- PHP 7 or 8 on server (clients can be implemented in other languages too)
- small: less 1 MB + docs
- low requirements: even no database required

# Quick start #

## Check on Appmonitor server ##

The project package contains the PHP client and the server. The PHP client is used for the Appmonitor server to verify its own installation.

If you have a server installation (see [Server](../10_Server/10_Installation.md)) verify the installation with your browser <http://localhost/appmonitor/client/check-appmonitor-server.php> You should see some JSON output.
