
# Notification #

## Introduction ##

Notifications will be triggered if ...

* an application check url was added
* a status of an application changes, eg. from OK to WARNING or back
* an application will be deleted

Sending notifications is blocked when a defined sleep time matches.

The notification uses plugins from server/plugins/notification/. By default
are supported

* email - send an email
* slack - send a slack message using a webhook

When sending emails it collects data from server config and from metadata
of an application check. Each application can define the application specific
receivers (developers, application support). Additionally in the server
config are global receivers getting the notifications for all applications.

When sending a notification it loops over all known notification plugins
and sends messages with all methods as long a TO item exists.

### Email ##

* **FROM** address is taken from server config notification -> from -> email
* **TO** is an array of email addresses; they are merged from
  * email receivers in application metadata
  * server config with global receivers getting notifications for all applications
* **SUBJECT** is a generated language specific text depending on type of change
* **MESSAGE** is a generated language specific text depending on type of change and status

For more details to the subject and message see the section for message texts below.

The email is send as plain text and high priority.

### Slack ###

* FROM - not needed
* **TO** is an array with label + url as items; they are merged from
  * target url in application metadata
  * server config
* SUBJECT - not needed
* **MESSAGE** is a generated language specific text depending on type of change and status

A slack message ist using "[APPMONITOR]" as sending user without icon.
As message text it takes the same generated text like for the email.

## Message texts ##

The sent messages are language dependent texts and the appmonitor is
shipped with a default. In the language file they are defined in the subkey "notifications".

You can override the defaults with defining the keys in the server config
in the section notifications -> messages.

These are the message keys:

* changetype-[N].logmessage
* changetype-[N].email.message
* changetype-[N].email.subject

[N] is an integer value between 0..3 (for result type)

As an example a snippet from server/lang/en-en.json:

```text
...
"notifications":{
    ...
    "changetype-1.logmessage":    "Webapp was added to the Appmonitor: __URL__. __ERROR__",
    "changetype-1.email.subject": "[Appmonitor] :: webapp was added: __URL__.",
    "changetype-1.email.message": "Welcome! The webapp __URL__ was added in the Appmonitor. From now on you will get notifications if the status changes.\n\nurl: __MONITORURL__\n\n__CHECKS__",
    ...
}
```

These texts can contain placeholders.

| Placeholder          | Description                                                |
|---                   |---                                                         |
| _\_\_APPID___        | id of application                                          |
| _\_\_CHANGE___       | one of new\|no change\|change\|deleted (1)                 |
| _\_\_DELTA-TIME___   | delta since last state change i.e. NN min (HH h) (2)       |
| _\_\_ERROR___        | Error message of a failed response                         |
| _\_\_HEADER___       | Http response header (maybe for an email message)          |
| _\_\_HOST___         | hostname (from client meta -> host)                        |
| _\_\_LAST-RESULT___  | result of last check; see RESULT (2)                       |
| _\_\_LAST-TIME___    | time of last check; see TIME (2)                           |
| _\_\_MONITORURL___   | url to monitoring page (3)                                 |
| _\_\_RESULT___       | one of OK\|Unknown\|Warning\|Error (1)                     |
| _\_\_TIME___         | current time YYYY-MM-DD hh:mm:ss                           |
| _\_\_URL___          | url of web application check                               |
| _\_\_WEBSITE___      | name of the website/ service (from client meta -> website) |

Remarks:

* (1) this depends on the set appmonitor server language. The values are these of the English version.
* (2) It requires that a saved state with another status for this url. Value is "-" if there is no state change logged yet
* (3) requires a value for "serverurl" in the config

To preview the texts you can 

* set "debug" to true in you config
* add role "ui-debug" for your user in ./server/config/appmonitor-server-config.json
* open server monitoring in the browser - go into a detail page of any web app
* on the bottom you see all placeholders, current replacements and the preview messages for all change types
