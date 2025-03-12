## 📦 Class iml\tinyapi

```txt

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
| \<optional\> $aRequirements | `array` | optional: requirements with subkeys                                 methods                                  ips

### 🔹 public allowIPs()

Set allowed ip addresses by a given list of regex

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $aIpRegex | `array` | array  aIpRegex  array of regex

### 🔹 public allowMethods()

Set allowed http methods

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $aMethods | `array` | array  aMethods  array of strings containing GET, PUT, POST, DELETE, OPTIONS

### 🔹 public allowUsers()

Set allowed users

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $aUsers | `array` | array  aUsers  array of allowed users; key= username ('' or userid); subkeys:                        - 'password'; value = password hash (BASIC AUTH) and/ or                        - 'secret'; clear text for hmac

### 🔹 public appendData()

Append response dataIf no key as 2nd param is given the given array will be added as new array element.With a given key the key will be used to set data (existing key will be replaced)

**Return**: `bool`

**Parameters**: **2**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $aData | `mixed` | additional response data
| \<optional\> $sKey | `string` | optional: use key

### 🔹 public checkIp()

Check allowed ip addresses by a given list of regex.It aborts if no ip address was detected.If access is not allowed it sends a 401 header and aborts.

**Return**: `bool`

**Parameters**: **0**


### 🔹 public checkMethod()

Check allowed http methods

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> $aMethods | `array` | array  aMethods  optional: array of strings containing GET, PUT, POST, DELETE, OPTIONS

### 🔹 public checkUser()

Get an authenticated user and return a detected username as string.Checks are done in that sequence- sent basic auth (base64 encoded <user>:<password>); remark it can override the user of a already authenticated user- sent generated hmac hashsum(<user>:<key>)- already made basic auth from browser- test if anonymous access is allowedRemark: this is a pre check. Your app can make further check like checka role if the found user has access to a function.@example:$oYourApp->setUser($oTinyApi->checkUser());

**Return**: `string`

**Parameters**: **0**


### 🔹 public sendError()

Send error message using the sendJson method.

**Return**: `void`

**Parameters**: **2**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $iHttpstatus | `int` | http statuscode
| \<required\> $sErrormessage | `string` | string with error message

### 🔹 public sendJson()

Send API response:set content type in http response header and transform data to jsonand stop.

**Return**: `void`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> $aData | `array` | array of data to send

### 🔹 public setData()

Set response data; "should" be an array

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $aData | `array` | response data

### 🔹 public setPretty()

Set response data; "should" be an array

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $bPretty | `bool` | response data

### 🔹 public stopIfOptions()

If http method is OPTIONS, send json and stop.

**Return**: `void`

**Parameters**: **0**


---
Generated with Axels PHP class doc parser.