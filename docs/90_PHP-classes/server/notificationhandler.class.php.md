## 📦 Class \notificationhandler

```txt
/**
 * ____________________________________________________________________________
 * 
 *  _____ _____ __                   _____         _ _           
 * |     |     |  |      ___ ___ ___|     |___ ___|_| |_ ___ ___ 
 * |-   -| | | |  |__   | .'| . | . | | | | . |   | |  _| . |  _|
 * |_____|_|_|_|_____|  |__,|  _|  _|_|_|_|___|_|_|_|_| |___|_|  
 *                          |_| |_|                              
 *                                                                                                                             
 *                       ___ ___ ___ _ _ ___ ___                                      
 *                      |_ -| -_|  _| | | -_|  _|                                     
 *                      |___|___|_|  \_/|___|_|                                       
 *                                                               
 * ____________________________________________________________________________
 * 
 * notificationhandler
 *
 * @author hahn
 * 
 * 2024-07-17  axel.hahn@unibe.ch  php 8 only: use typed variables
 * 2024-11-06  axel.hahn@unibe.ch  update html email output
 * 2025-02-21  axel.hahn@unibe.ch  use sqlite as storage
 */
```

## 🔶 Properties

(none)

## 🔷 Methods

### 🔹 public __construct()

init

**Return**: ``

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> array $aOptions = [] | `array` | options array with the keys<br>                         - {string} lang       language of the GUI<br>                         - {string} serverurl  base url of the web app to build an url to an app specific page<br>                         - {string} notifications  appmionitor config settings in notification settings (for sleeptime and messages)


### 🔹 public countLogitems()

Get count of notification log entries

**Return**: `int`

**Parameters**: **0**


### 🔹 public deleteApp()

Delete application: this method triggers deletion of its notification data and last resultTriggered by apmonitor-server class - actionDeleteUrl(string $sUrl)

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sAppId | `string` | app id


### 🔹 public getAppLastResult()

Get 2nd last resultset of an application

**Return**: `array *`

**Parameters**: **0**


### 🔹 public getAppNotificationdata()

Get array with notification data of an apptaken from check result meta -> notifications merged with server config

**Return**: `array`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> string $sType = '' | `string` | optional: type email|slack; defailt: false (=return all keys)


### 🔹 public getAppResult()

Get current result from cache using a shared cache object that was written by appmonitor-server class

**Return**: `array`

**Parameters**: **0**


### 🔹 public getLogdata()

Get current log data and filter them

**Return**: `array`

**Parameters**: **3**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> array $aFilter = [] | `array` | filter with possible keys timestamp|changetype|status|appid|message (see addLogitem())<br>                         - mode  {string} "last" = newest entries first<br>                         - count {integer} number of entries to return<br>                         - page  {integer}
| \<optional\> int $iLimit = 0 | `int` | set a maximum of log entries
| \<optional\> bool $bRsort = true | `bool` | flag to reverse sort logs; default is true (=newest entry first)


### 🔹 public getMessageReplacements()

Helper function: get the array with all current replacements in message texts with key = placeholder and value = replacement

**Return**: `array`

**Parameters**: **0**


### 🔹 public getPlugins()

Get an array with notification pluginsIt is a list of basenames in the plugin directory server/plugins/notification/.phpAdditionally its functions will be included to be used in sendAllNotifications

**Return**: `array`

**Parameters**: **0**


### 🔹 public getReplacedMessage()

Helper function: generate message text frem template based on type ofchange, its template and the values of check data

**Return**: `string`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $sMessageId | `one *` | one of changetype-[N].logmessage | changetype-[N].email.message | email.subject


### 🔹 public isSleeptime()

Check if a defined sleep time was reached.It returns false if no sleep time is defined.It returns the 1st matching regex if a match was found.

**Return**: `string|bool`

**Parameters**: **0**


### 🔹 public notify()

Detect if a notification is needed.It returns false if a sleep time was detected. Othwerwise it returns true.

**Return**: `bool`

**Parameters**: **0**


### 🔹 public setApp()

Set application with its current check result

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sAppId | `string` | application id




---
Generated with Axels PHP class doc parser.