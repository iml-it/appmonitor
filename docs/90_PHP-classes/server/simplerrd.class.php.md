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



**Return**: ``

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> string $sId = '' | `string` | optional id to set


### 🔹 public add()



**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $aDataItem | `dataitem *` | dataitem


### 🔹 public delete()



**Return**: `bool`

**Parameters**: **0**


### 🔹 public get()



**Return**: `array`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> int $iMax = 0 | `int` | optional: limit


### 🔹 public setId()



**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> string $sId | `string` | 




---
Generated with Axels PHP class doc parser.