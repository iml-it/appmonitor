## 📦 Class \appmonitor

```txt
/**
 * ____________________________________________________________________________
 * 
 *  _____ _____ __                   _____         _ _           
 * |     |     |  |      ___ ___ ___|     |___ ___|_| |_ ___ ___ 
 * |-   -| | | |  |__   | .'| . | . | | | | . |   | |  _| . |  _|
 * |_____|_|_|_|_____|  |__,|  _|  _|_|_|_|___|_|_|_|_| |___|_|  
 *                          |_| |_|                              
 *                           _ _         _                                            
 *                       ___| |_|___ ___| |_                                          
 *                      |  _| | | -_|   |  _|                                         
 *                      |___|_|_|___|_|_|_|   
 *                                                               
 * ____________________________________________________________________________
 * 
 * APPMONITOR :: CLASS FOR CLIENT CHECKS<br>
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
 * <br>
 * --- HISTORY:<br>
 * 2014-10-24  0.5    axel.hahn@iml.unibe.ch<br>
 * 2014-11-21  0.6    axel.hahn@iml.unibe.ch  removed meta::ts <br>
 * 2018-08-23  0.50   axel.hahn@iml.unibe.ch  show version<br>
 * 2018-08-24  0.51   axel.hahn@iml.unibe.ch  method to show local status page<br>
 * 2018-08-27  0.52   axel.hahn@iml.unibe.ch  add pdo connect (starting with mysql)<br>
 * 2018-11-05  0.58   axel.hahn@iml.unibe.ch  additional flag in http check to show content<br>
 * 2019-05-31  0.87   axel.hahn@iml.unibe.ch  add timeout as param in connective checks (http, tcp, databases)<br>
 * 2020-05-03  0.110  axel.hahn@iml.unibe.ch  update renderHtmloutput<br>
 * 2023-07-06  0.128  axel.hahn@unibe.ch      update httpcontent check<br>
 * 2024-07-19  0.137  axel.hahn@unibe.ch      php 8 only: use typed variables
 * --------------------------------------------------------------------------------<br>
 * @version 0.137
 * @author Axel Hahn
 * @link TODO
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 */
```

## 🔶 Properties

(none)

## 🔷 Methods

### 🔹 public __construct()

constructor: init data


**Return**: ``

**Parameters**: **0**


### 🔹 public abort()

Stop processing the client checks and abort with an error

**Return**: `void`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sMessage | `string` | text to show after a 503 headline


### 🔹 public addCheck()

Add a check array

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> $aJob = [] | `array *` | array with check data


### 🔹 public addEmail()

Add an email to notifications list

**Return**: `boolean *`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sEmailAddress | `string` | email address to add


### 🔹 public addSlackWebhook()

Add slack channel for notification

**Return**: `bool`

**Parameters**: **2**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sLabel | `string` | 
| \<required\> string $sSlackWebhookUrl | `string` | 


### 🔹 public addTag()

Add a tag for grouping in the server gui.Spaces will be replaced with underscore

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sTag | `string` | tag to add


### 🔹 public checkIp()

Check referers IP address if it matches any entry in the listrequires http request; CLI is always allowedOn deny this method exits with 403 response

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> array $aAllowedIps = [] | `array` | array of allowed ip addresses / ranges<br>                           the ip must match from the beginning, i.e.<br>                           "127.0." will allow requests from 127.0.X.Y


### 🔹 public checkToken()

Check a tokenrequires http request; CLI is always allowedOn deny this method exits with 403 response

**Return**: `bool`

**Parameters**: **2**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sVarname | `string` | name of GET variable
| \<required\> string $sToken | `string` | value


### 🔹 public getResults()

Get full array for response with metadata and checks

**Return**: `array`

**Parameters**: **0**


### 🔹 public listChecks()

list all available check functions. This is a helper class you cann callto get an overview over built in functions. You get a flat array withall function names.

**Return**: `array`

**Parameters**: **0**


### 🔹 public render()

Send http response with header and appmonitor JSON data

**Return**: `string`

**Parameters**: **0**


### 🔹 public renderHtmloutput()

Output appmonitor client status as single html page@example

```txt ob_start();<br>require __DIR__ . '/appmonitor/client/index.php';$sJson=ob_get_contents();ob_end_clean();$oMonitor->renderHtmloutput($sJson);```



**Return**: `string`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sJson | `string` | JSON of client output


### 🔹 public setHost()

Set the physical hostname for metadata; if no host is given thenthe php_uname("n") will be used to set one.

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> string $s = '' | `string` | hostname


### 🔹 public setResult()

Set final result in meta data; if no value was given then itsets the biggest value of any check.

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> int $iResult = -1 | `int` | set resultcode; one of RESULT_OK|RESULT_WARNING|RESULT_ERROR|RESULT_UNKNOWN


### 🔹 public setTTL()

set a ttl value in seconds to define how long a server should notask again for a new status of this instance

**Return**: `boolean *`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> $iTTl = 0 | `TTL *` | TTL value in sec


### 🔹 public setWebsite()

Set a name for this website or application and its environment (dev, test, prod); If you have several application in subdirectories, i.e. /blog,  /shop...then you should the path or any description to identify them tooif no argument is given the name of HTTP_HOST will be used

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> $sWebsite = '' | `Name *` | Name of the website or web application




---
Generated with Axels PHP class doc parser.