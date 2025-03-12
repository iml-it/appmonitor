## 📦 Class iml\tinyrouter

```txt

```

## 🔶 Properties

### 🔸 public $sUrl

Requested url@var string


type: string

default value: 

### 🔸 public $sMethod

Used http method@var string


type: string

default value: 

### 🔸 public $aRoutes

Array of defined routes@var array


type: array

default value: 



## 🔷 Methods

### 🔹 public __construct()

Constructor

**Return**: `boolean *`

**Parameters**: **2**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> $aRoutes | `array` | array   array of routes
| \<optional\> $sUrl | `string` | string  incoming url

### 🔹 public getCallback()

Get the callback item of the matching routeIf no route was matching it returns false

**Return**: `array|string|bool`

**Parameters**: **0**


### 🔹 public getRoute()

Get last matching route itemIf no route was matching then it returns []$this->aMatch is set in _getRoute()@see _getRoute()

**Return**: `array`

**Parameters**: **0**


### 🔹 public getSubitems()

Get an array with next level route entries releative to the current route

**Return**: `array`

**Parameters**: **0**


### 🔹 public getUrlParts()

Helper function: get url request parts as array

**Return**: `array`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> $sUrl | `string` | url to handle; /api/v1/productbyid/3424084

### 🔹 public getVar()

Get a single variable in route parts with starting @ characterIf no route was matching or the variable key doesn't exist it returns false

**Return**: `string|bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $sVarname | `string` | name of the variable

### 🔹 public getVars()

Get the variables as keys in route parts with starting @ characterIf no route was matching it returns false

**Return**: `array|boolean *`

**Parameters**: **0**


### 🔹 public setRoutes()

Set routes configuration.It calls the _getRoute method to find the matching route

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<optional\> $aRoutes | `array` | list of [ route, more params ... ]

### 🔹 public setUrl()

Set incoming url, add the request behind protocol and domain.It calls the _getRoute method to find the matching route

**Return**: `bool`

**Parameters**: **1**

| Parameter | Type | Description
|--         |--    |--
| \<required\> $sUrl | `string` | string  sUrl  url to fetch; /api/v1/productbyid/3424084

---
Generated with Axels PHP class doc parser.