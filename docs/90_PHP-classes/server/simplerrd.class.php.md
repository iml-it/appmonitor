## 📦 Class \simpleRrd

```txt
/**
 * simple storages to keep last N items of an object
 *
 * @author hahn
 * 
 * 2024-07-23  axel.hahn@unibe.ch  php 8 only: use typed variables
 * 2025-02-21  axel.hahn@unibe.ch  use sqlite as storage
 */
```

## 🔶 Properties

(none)

## 🔷 Methods

### 🔹 public __construct()

Constructor

**Return**: ``

**Parameters**: **2**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> string $sAppId = '' | `string` | optional id to set
| \<optional\> string $sCounterId = '' | `string` | 


### 🔹 public add()

Add data item.This action will limit the count of max items and save it to cache.It returns the success of save action.

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $aDataItem | `dataitem *` | dataitem


### 🔹 public delete()

Delete current application

**Return**: `bool`

**Parameters**: **0**


### 🔹 public deleteApp()

Delete current application

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> string $sAppid = '' | `string` | 


### 🔹 public get()

Get array with stored items

**Return**: `array`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> int $iMax = 0 | `int` | optional: limit


### 🔹 public getCountersOfApp()

Get array of ids of counters of current application

**Return**: `array`

**Parameters**: **0**


### 🔹 public setApp()

Set an application by its id to set counters for

**Return**: `string *`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sAppId | `string` | 


### 🔹 public setId()

Set id for this rrd value store

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $sCountername | ` *` | 




---
Generated with Axels PHP class doc parser.