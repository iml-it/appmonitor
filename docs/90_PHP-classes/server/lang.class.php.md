## 📦 Class \lang

```txt
/**
 * LANG
 * language class
 * - load language file 
 * - get texts by keys - incl. unlimited nested subkeys 
 * --------------------------------------------------------------------------------<br>
 * @author Axel Hahn
 * @link TODO
 * @license GPL
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL 3.0
 * @package IML-Appmonitor
 * 
 * 2024-07-19  axel.hahn@unibe.ch  php 8 only: use typed variables
 */
```

## 🔶 Properties

(none)

## 🔷 Methods

### 🔹 public __construct()

constructor


**Return**: ``

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> $sLang = '' | ` *` | 


### 🔹 public getAllEntries()

Get array with all entries in language file

**Return**: `array`

**Parameters**: **0**


### 🔹 public getAllLanguages()

Get all available config files as flat array

**Return**: `array`

**Parameters**: **0**


### 🔹 public load()

Load language texts with given language name. It loads the texts from the language json file and updates the value for current language.It returns false if the given language doesn't exist

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sLang | `string` | name of language (without extension "json")


### 🔹 public tr()

Translate a text with language file and key(s)A found text key in the key has priotity vs global definitions in root level

**Return**: `string *`

**Parameters**: **2**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sWord | `string` | item to find in language array
| \<optional\> $aSubkeys = false | `subkeys *` | subkeys to walk in (for nested lang files); eg ["gui"]




---
Generated with Axels PHP class doc parser.