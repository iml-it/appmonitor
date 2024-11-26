## 📦 Class \appmonitorserver

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
 * APPMONITOR SERVER<br>
 * <br>
 * THERE IS NO WARRANTY FOR THE PROGRAM, TO THE EXTENT PERMITTED BY APPLICABLE <br>
 * LAW. EXCEPT WHEN OTHERWISE STATED IN WRITING THE COPYRIGHT HOLDERS AND/OR <br>
 * OTHER PARTIES PROVIDE THE PROGRAM ?AS IS? WITHOUT WARRANTY OF ANY KIND, <br>
 * EITHER EXPRESSED OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED <br>
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE. THE <br>
 * ENTIRE RISK AS TO THE QUALITY AND PERFORMANCE OF THE PROGRAM IS WITH YOU. <br>
 * SHOULD THE PROGRAM PROVE DEFECTIVE, YOU ASSUME THE COST OF ALL NECESSARY <br>
 * SERVICING, REPAIR OR CORRECTION.<br>
 * <br>
 * --------------------------------------------------------------------------------<br>
 * @version 0.142
 * @author Axel Hahn
 * @link https://github.com/iml-it/appmonitor
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 * --------------------------------------------------------------------------------<br>
 * 2024-07-17  0.137  axel.hahn@unibe.ch  php 8 only: use typed variables
 * 2024-11-26  0.142  axel.hahn@unibe.ch  handle invalid response data
 */
```

## 🔶 Properties

### 🔸 public $oNotification

notificationhandler object to send email/ slack messagesit is initialized in method loadConfig()@var notificationhandler object


type: notificationhandler

default value: 



## 🔷 Methods

### 🔹 public __construct()

constructor


**Return**: ``

**Parameters**: **0**


### 🔹 public actionAddUrl()

Setup action: add a new url and save the config

**Return**: `bool`

**Parameters**: **2**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sUrl | `string` | url to add
| \<optional\> bool $bMakeCheck = true | `bool` | Flag: check a valid url and response is JSON


### 🔹 public actionDeleteUrl()

Setup action: Delete an url to fetch and trigger to save the new config file

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sUrl | `string` | url to delete in the config


### 🔹 public addUrl()

Add appmonitor url to current object

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $sUrl | `url *` | url to add


### 🔹 public getAlreadyAuthenticatedUser()

Return a detected user from $_SERVER env

**Return**: `string`

**Parameters**: **0**


### 🔹 public getAppIds()

Get a flat array with all application ids and website + urlas subkeys

**Return**: `array`

**Parameters**: **0**


### 🔹 public getConfigVars()

Get a hash with all configuration items

**Return**: `array`

**Parameters**: **0**


### 🔹 public getMonitoringData()

Get all client data and final result as array

**Return**: `array`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> string $sHost = '' | `string` | filter by given hostname


### 🔹 public getResultValue()

Get a readable result by given integer; i.e. 0=OK, 1=unknown, ...

**Return**: `string`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> int $i | `int` | 


### 🔹 public getRoles()

Get roles of a user. If the user itself has no rolesbut was authenticated by the webserver then it getsdefault roles from user "__default_authenticated_user__"

**Return**: `array|bool`

**Parameters**: **0**


### 🔹 public getUser()

Get meta fields for current or given user

**Return**: `array|bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> string $sUsername = '' | `string` | optional: override current user id


### 🔹 public getUserid()

Get current username that was detected or set

**Return**: `string`

**Parameters**: **0**


### 🔹 public getUsername()

Get current username that was detected or set

**Return**: `string`

**Parameters**: **0**


### 🔹 public hasRole()

Return if a user has a given role

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sRequiredRole | `string` | name of the role to verify


### 🔹 public loadClientData()

load monitoring data ... if not done yet; used in gui and api

**Return**: `bool`

**Parameters**: **0**


### 🔹 public loadConfig()

(re) load config and get all urls to fetch (and all other config items)This method - fills $this->_aCfg- newly initializes $this->oNotification

**Return**: `void`

**Parameters**: **0**


### 🔹 public removeUrl()

remove appmonitor url from current object

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sUrl | `string` | url to remove


### 🔹 public saveConfig()

Save the current or new config data as file.

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> array $aNewCfg = [] | `array` | 


### 🔹 public saveUrls()

Save the current or new config data as file.

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> array $aNewCfg = [] | `array` | 


### 🔹 public send()

Write a message to STDOUT (if actiated or logging is on)


**Return**: `bool`

**Parameters**: **2**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sMessage | `string` | message text
| \<optional\> $bShow = false | `flag *` | flag to write to stdout (overrides set show log value)


### 🔹 public setDemoMode()

switch demo mode on and offTODO: check how switch demo mode and handle parameters

**Return**: `bool *`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> $bBool = true | ` *` | 


### 🔹 public setLogging()

Set flag for logging to standard output

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> bool $bShow | `bool` | new logging flag


### 🔹 public setUser()

Set a username to work with

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sNewUser | `string` | username; it should be a user in config users key (or you loose all access)




---
Generated with Axels PHP class doc parser.