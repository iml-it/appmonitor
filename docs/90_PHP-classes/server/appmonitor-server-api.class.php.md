## 📦 Class \appmonitorserver_api

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
 * OTHER PARTIES PROVIDE THE PROGRAM "AS IS" WITHOUT WARRANTY OF ANY KIND, <br>
 * EITHER EXPRESSED OR IMPLIED, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED <br>
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE. THE <br>
 * ENTIRE RISK AS TO THE QUALITY AND PERFORMANCE OF THE PROGRAM IS WITH YOU. <br>
 * SHOULD THE PROGRAM PROVE DEFECTIVE, YOU ASSUME THE COST OF ALL NECESSARY <br>
 * SERVICING, REPAIR OR CORRECTION.<br>
 * <br>
 * --------------------------------------------------------------------------------<br>
 * @version v0.137
 * @author Axel Hahn
 * @link https://github.com/iml-it/appmonitor
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 * --------------------------------------------------------------------------------<br>
 * 2024-07-17  0.137  axel.hahn@unibe.ch  php 8 only: use typed variables
 */
```

## 🔶 Properties

### 🔸 public $oNotification




type: notificationhandler

default value: 



## 🔷 Methods

### 🔹 public __construct()




**Return**: ``

**Parameters**: **0**


### 🔹 public actionAddUrl()



**Return**: `bool`

**Parameters**: **2**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sUrl | `string` | url to add
| \<optional\> bool $bMakeCheck = true | `bool` | Flag: check a valid url and response is JSON


### 🔹 public actionDeleteUrl()



**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sUrl | `string` | url to delete in the config


### 🔹 public addUrl()



**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $sUrl | `url *` | url to add


### 🔹 public apiGetFilteredApp()



**Return**: `array`

**Parameters**: **2**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> array $aFilter = [] | `array` | filter definitions using AND condition over all filters<br>                          appid   - string of appid<br>                          tags    - array of tags that must match (AND condition)<br>                          website - substring of website
| \<optional\> string $outmode = 'all' | `string` | kind of result data; one of appid|checks|meta|all


### 🔹 public apiGetHeatlth()



**Return**: `array`

**Parameters**: **0**


### 🔹 public apiGetTags()



**Return**: `array`

**Parameters**: **0**


### 🔹 public getAlreadyAuthenticatedUser()



**Return**: `string`

**Parameters**: **0**


### 🔹 public getApiConfig()



**Return**: `array`

**Parameters**: **0**


### 🔹 public getApiUsers()



**Return**: `array`

**Parameters**: **0**


### 🔹 public getAppIds()



**Return**: `array`

**Parameters**: **0**


### 🔹 public getConfigVars()



**Return**: `array`

**Parameters**: **0**


### 🔹 public getMonitoringData()



**Return**: `array`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> string $sHost = '' | `string` | filter by given hostname


### 🔹 public getResultValue()



**Return**: `string`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> int $i | `int` | 


### 🔹 public getRoles()



**Return**: `array|bool`

**Parameters**: **0**


### 🔹 public getUser()



**Return**: `array|bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> string $sUsername = '' | `string` | optional: override current user id


### 🔹 public getUserid()



**Return**: `string`

**Parameters**: **0**


### 🔹 public getUsername()



**Return**: `string`

**Parameters**: **0**


### 🔹 public hasRole()



**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sRequiredRole | `string` | name of the role to verify


### 🔹 public loadClientData()



**Return**: `bool`

**Parameters**: **0**


### 🔹 public loadConfig()



**Return**: `void`

**Parameters**: **0**


### 🔹 public removeUrl()



**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sUrl | `string` | url to remove


### 🔹 public saveConfig()



**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> array $aNewCfg = [] | `array` | new configuration data


### 🔹 public send()




**Return**: `bool`

**Parameters**: **2**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sMessage | `string` | message text
| \<optional\> $bShow = false | `flag *` | flag to write to stdout (overrides set show log value)


### 🔹 public setDemoMode()



**Return**: `bool *`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> $bBool = true | ` *` | 


### 🔹 public setLogging()



**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> bool $bShow | `bool` | new logging flag


### 🔹 public setUser()



**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sNewUser | `string` | username; it should be a user in config users key (or you loose all access)




---
Generated with Axels PHP class doc parser.