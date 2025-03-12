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
 * 2024-11-11  1.2  axel.hahn@unibe.ch  show memory usage in log output
 */
```

## 🔶 Properties

(none)

## 🔷 Methods

### 🔹 public __construct()

Initialize tiniservice

**Return**: `boolean *`

**Parameters**: **3**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $sAppname | `string` | string   app id to prevent starting a script multiuple times
| \<optional\> $iNewSleeptime | `int` | integer  idle time between loops
| \<optional\> $sTmpdir | `string` | string   custom temp dir

### 🔹 public canStart()

Check if application can start. It checks the existance of touch fileif it was found then an older file will be ignored.


**Return**: `bool`

**Parameters**: **0**


### 🔹 public denyRoot()

Do not allow to run as root (forNIX systems)

**Return**: `bool`

**Parameters**: **0**


### 🔹 public send()

Write a message to STDOUT (if actiated or debug is on) andtouch the run file


**Return**: `bool`

**Parameters**: **2**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $sMessage | `string` | message text
| \<optional\> $bShow | `bool` | flag to write to stdout

### 🔹 public setAppname()

Set an application name.It is used to create a run file.


**Return**: `bool`

**Parameters**: **2**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $sAppname | `string` | name of the application
| \<optional\> $sTmpdir | `string` | optional: location of temp dir; default: system temp (often /tmp)

### 🔹 public setDebug()

Enable/ disable debug


**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $bDebug | `bool` | flag with true|false

### 🔹 public setSleeptime()

Set a new sleep time

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $iNewSleeptime | `int` | value in seconds

### 🔹 public sigHandler()

Signal handler - UNTESTED

**Return**: `void`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $signo | `int` | sent signal

### 🔹 public sleep()

Sleep a bit


**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> $bShow | `bool` | flag to write to stdout

### 🔹 public touch()

Write the message to a touch file ... as a life sign


**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $sMessage | `string` | message text

---
Generated with Axels PHP class doc parser.