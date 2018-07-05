
# APPMONITOR #

OPEN SOURCE from University Berne :: IML - Institute of Medical Education

https://github.com/iml-it/appmonitor

- - -

# Description #

The application monitor is an additional too to the classic monitoring of a servers and its services. It makes checks from the point of view of the application. With its credentials and permissions.

The appmonitor is a monitoring tool that consists of 2 parts
- **client** (for each webapplication to check)
  - make necessary checks for the full functionality of a website 
  - responds information as JSON
- **server** instance
  - collects all JSON data and presents an overview over all checks and websites
  - sends notifications to server admins (read from server config) plus developers and product owners (coming from clients metadata)


Continue: [Client](client/readme.md) | [Server](server/readme.md)


# Features #

- PHP 7 on server (clients can be implemented in other languages too)
- small: 100 kb download; no database required


Server webgui
- Themes :-)
- Filter the view by selecting tags
- Multi language (english language file so far)
- Optional service for permanent check and notification 24/7
- Notification as email, Slack message (respecting sleep times)
