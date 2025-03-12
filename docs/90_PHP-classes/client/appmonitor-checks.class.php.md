## 📦 Class \appmonitorcheck

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
 * APPMONITOR :: CLASS FOR CLIENT TEST FUNCTIONS<br>
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
 * 2015-04-08  0.9    axel.hahn@iml.unibe.ch  added sochket test: checkPortTcp<br>
 * 2018-06-29  0.24   axel.hahn@iml.unibe.ch  add file and directory checks<br>
 * 2018-07-17  0.42   axel.hahn@iml.unibe.ch  add port on mysqli check<br>
 * 2018-07-26  0.46   axel.hahn@iml.unibe.ch  fix mysql connection check with empty port param<br>
 * 2018-08-14  0.47   axel.hahn@iml.unibe.ch  appmonitor client: use timeout of 5 sec for tcp socket connections<br>
 * 2018-08-15  0.49   axel.hahn@iml.unibe.ch  cert check: added flag to skip verification<br>
 * 2018-08-23  0.50   axel.hahn@iml.unibe.ch  replace mysqli connect with mysqli real connect (to use a timeout)<br>
 * 2018-08-27  0.52   axel.hahn@iml.unibe.ch  add pdo connect (starting with mysql)<br>
 * 2018-11-05  0.58   axel.hahn@iml.unibe.ch  additional flag in http check to show content<br>
 * 2019-05-31  0.87   axel.hahn@iml.unibe.ch  add timeout as param in connective checks (http, tcp, databases)<br>
 * 2019-06-05  0.88   axel.hahn@iml.unibe.ch  add plugins<br>
 * 2021-10-28  0.93   axel.hahn@iml.unibe.ch  add plugins<br>
 * 2021-12-14  0.93   axel.hahn@iml.unibe.ch  split plugins into single files; added key group in a check<br>
 * 2023-06-02  0.125  axel.hahn@unibe.ch      replace array_key_exists for better readability
 * 2024-07-22  0.137  axel.hahn@unibe.ch      php 8 only: use typed variables
 * 2025-02-28  0.152  axel.hahn@unibe.ch      listChecks: add loop over currently loaded classes
 * 2025-03-03  0.153  axel.hahn@unibe.ch      getSize() preg_replace did not work in compiled binary
 * 2025-03-04  0.154  axel.hahn@unibe.ch      finish with existcode instead of die()
 * --------------------------------------------------------------------------------<br>
 * @version 0.154
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

Constructor (nothing here)


**Return**: ``

**Parameters**: **0**


### 🔹 public listChecks()

List all available checks. This is a helper class you can callto get an overview over built in functions and plugins. You get a flat array with all function names.

**Return**: `array`

**Parameters**: **0**


### 🔹 public makeCheck()

Perform a check

**Return**: `array`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $aConfig | `array` | configuration array for a check, eg.<br>

```txt <br>[<br>    [name] => Dummy<br>    [description] => Dummy Test<br>    [check] => [<br>        [function] => [check function] // i.e. Simple<br>        [params] => [array]            // optional; arguments for Check function<br>                                       // its keys depend on the function  <br>    ]<br>]<br>```

### 🔹 public respond()

Final call of class: send response (data array)

**Return**: `array *`

**Parameters**: **0**


---
Generated with Axels PHP class doc parser.