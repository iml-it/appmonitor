
# APPMONITOR #

Free software and Open Source from University of Bern :: IML - Institute of Medical Education

https://github.com/iml-it/appmonitor

- - -

# Description #

The application monitor is an additional tool to the classic monitoring of a servers and its services. It makes checks from the point of view of the application. With its credentials and permissions.



# Features #

- PHP 7 on server (clients can be implemented in other languages too)
- small: 200 kb download; no database required


Server webgui
- Themes :-)
- Filter the view by selecting tags
- Multi language (English ang German language file so far)
- Optional service for permanent check and notification 24/7
- Notification as email, Slack message (respecting sleep times i.e. during the night)
- CLI tool for automation of the server config (with Puppet, Ansible, ...)

**more**: [Overview](docs/overview.md)

Remark: For ready to use client checks for a few products see
https://github.com/iml-it/appmonitor-clients/