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
| \<optional\> array $aRequirements = [] | `array` | optional: requirements with subkeys                                 methods                                  ips


### 🔹 public allowIPs()

Set allowed ip addresses by a given list of regex

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> array $aIpRegex | `array` | array  aIpRegex  array of regex


### 🔹 public allowMethods()

Set allowed http methods

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> array $aMethods | `array` | array  aMethods  array of strings containing GET, PUT, POST, DELETE, OPTIONS


### 🔹 public allowUsers()

Set allowed users

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> array $aUsers | `array` | array  aUsers  array of allowed users; key= username; value = password hash (BASIC AUTH)                ''          =>  false,          - allow anonymous requests                'apiuser'    => '[passwordhash]' - an api user that can send an basic auth header


### 🔹 public appendData()

Append response dataIf no key as 2nd param is given the given array will be added as new array element.With a given key the key will be used to set data (existing key will be replaced)

**Return**: `bool`

**Parameters**: **2**

| Parameter | Type | Description
|--         |--    |--
| \<required\> mixed $aData | `mixed` | additional response data
| \<optional\> string $sKey = '' | `string` | optional: use key


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
| \<optional\> array $aMethods = [] | `array` | array  aMethods  optional: array of strings containing GET, PUT, POST, DELETE, OPTIONS


### 🔹 public checkUser()

Get an authenticated user and return a detected username as string.Checks are done in that sequence- sent basic auth (user:password); remark it can override the user of a already authenticated user- already made basic auth from $_SERVER- test if anonymous access is allowedRemark: this is a pre check. Your app can make further check like checka role if the found user has access to a function.@example:$oYourApp->setUser($oTinyApi->checkUser());if (!$oYourApp->hasRole('api')){    $oTinyApi->sendError(403, 'ERROR: Your user has no permission to access the api.');    die();};

**Return**: `string`

**Parameters**: **0**


### 🔹 public sendError()

Send error message using the sendJson method.

**Return**: `void`

**Parameters**: **2**

| Parameter | Type | Description
|--         |--    |--
| \<required\> int $iHttpstatus | `int` | http statuscode
| \<required\> string $sErrormessage | `string` | string with error message


### 🔹 public sendJson()

Send API response:set content type in http response header and transform data to jsonand stop.

**Return**: `void`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> array $aData = [] | `array` | array of data to send


### 🔹 public setData()

Set response data; "should" be an array

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> array $aData | `array` | response data


### 🔹 public setPretty()

Set response data; "should" be an array

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> bool $bPretty | `bool` | response data


### 🔹 public stopIfOptions()

If http method is OPTIONS, send json and stop.

**Return**: `void`

**Parameters**: **0**




---
Generated with Axels PHP class doc parser.