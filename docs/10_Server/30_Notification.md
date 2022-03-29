
# Notification #

## Message texts ##

The sent messages are language dependent texts and the appmonitor is
shipped with a default. In the language file they are defined in the subkey 
"notifications".

You can override the defaults with defining the keys in the server config
in the section notifications -> messages.

These are the message keys:

- changetype-[N].logmessage
- changetype-[N].email.message
- changetype-[N].email.subject

[N] is an integer value between 0..3 (for result type)

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

- (1) this depends on the set appmonitor server language. The values are these of the English version.
- (2) It requires that a saved state with another status for this url. Value is "-" if there is no state change logged yet
- (3) requires a value for "serverurl" in the config

To preview the texts you can 

- set "debug" to true in you config
- open server monitoring in the browser - go into a detail page of any web app
- on the bottom you see all placeholders, current replacements and the preview messages for all change types
