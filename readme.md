
# APPMONITOR #

Free software and Open Source from University of Bern :: IML - Institute of Medical Education

https://github.com/iml-it/appmonitor

- - -

# Description #

The application monitor is an additional tool to the classic system monitoring of a servers and its services. It makes checks from the point of view of the application. With its credentials and permissions started on the application hosts.


# Features #

- PHP 7 on server (clients can be implemented in other languages too)
- small: 400 kb + docs; no database required


Server webgui
- Filter the view by selecting tags
- Multi language (English ang German language file so far)
- Optional service for permanent checks and notification 24/7
- Notification as email, Slack message (respecting sleep times i.e. during the night)
- CLI tool to automate settings in the server config (with Puppet, Ansible, ...)

**Continue**: [Overview](docs/readme.md)


**Screenshot**:

![Client](docs/images/screenshot-view-client.png "Client view in monitor web gui")