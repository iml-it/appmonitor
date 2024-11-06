## 📦 Class \tinyservice

```txt
/**
 * tinyservice
 *
 * TODO: check a running PID instead of timeout based on sleep time
 *      MS WINDOWS: tasklist /FI "PID eq [PID]"
 *      *NIX: if(!file_exists('/proc/'.$pid))
 *
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
 * @version 1.1
 * @author Axel Hahn
 * @link https://github.com/iml-it/appmonitor
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 * 
 * 2024-07-17  1.1  axel.hahn@unibe.ch  php 8 only: use typed variables
 */
```

## 🔶 Properties

(none)

## 🔷 Methods

### 🔹 public __construct()



**Return**: `boolean *`

**Parameters**: **3**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sAppname | `string` | string   app id to prevent starting a script multiuple times
| \<optional\> int $iNewSleeptime = 10 | `int` | integer  idle time between loops
| \<optional\> string $sTmpdir = '' | `string` | string   custom temp dir


### 🔹 public canStart()




**Return**: `bool`

**Parameters**: **0**


### 🔹 public denyRoot()



**Return**: `bool`

**Parameters**: **0**


### 🔹 public send()




**Return**: `bool`

**Parameters**: **2**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sMessage | `string` | message text
| \<optional\> bool $bShow = false | `bool` | flag to write to stdout


### 🔹 public setAppname()




**Return**: `bool`

**Parameters**: **2**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sAppname | `string` | name of the application
| \<optional\> string $sTmpdir = '' | `string` | optional: location of temp dir; default: system temp (often /tmp)


### 🔹 public setDebug()




**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> bool $bDebug | `bool` | flag with true|false


### 🔹 public setSleeptime()



**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> int $iNewSleeptime | `int` | value in seconds


### 🔹 public sigHandler()



**Return**: `void`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> int $signo | `int` | sent signal


### 🔹 public sleep()




**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> bool $bShow = false | `bool` | flag to write to stdout


### 🔹 public touch()




**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sMessage | `string` | message text




---
Generated with Axels PHP class doc parser.