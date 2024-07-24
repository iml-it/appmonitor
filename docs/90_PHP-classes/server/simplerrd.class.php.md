## 📦 Class \simpleRrd

```txt
/**
 * simple storages to keep last N items of an object
 *
 * @author hahn
 * 
 * 2024-07-23  axel.hahn@unibe.ch  php 8 only: use typed variables
 */
```

## 🔶 Properties

(none)

## 🔷 Methods

### 🔹 public __construct()

Constructor

**Return**: ``

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> string $sId = '' | `string` | optional id to set


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


### 🔹 public get()

Get array with stored items

**Return**: `array`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> int $iMax = 0 | `int` | optional: limit


### 🔹 public setId()

Set id for this rrd value store

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sId | `string` | 




---
Generated with Axels PHP class doc parser.