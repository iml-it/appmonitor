<style>
	.required{color:#f22;}
	.optional{color:#888;}
</style>

[UP: PHP client: default checks](../client-php-checks.md)

--- 

# PortTcp #


## Description ##

Check if the local server or another host is listening to a given port number.


## Syntax ##

```php
$oMonitor->addCheck(
	array(
		"name" => "Port local SSH",
		"description" => "check port 22",
		"check" => array(
			"function" => "PortTcp",
			"params" => array(
				"host" => [hostname],
				"port" => [port number],
				"timeout" => [time],
			),
		),
	)
);
```


## Parameters ##

| key      | type     | description |
|---       |---       |---
|port      |(integer) |port to check <span class="required">(*)</span>
|host      |(string)  |optional: hostname to connect to; if unavailable 127.0.0.1 will be tested
|timeout   |(integer) |optional timeout in sec; default: 5


## Examples ##

**Check local SSH port (22)**
```php
$oMonitor->addCheck(
	array(
		"name" => "Port local SSH",
		"description" => "check port 22",
		"check" => array(
			"function" => "PortTcp",
			"params" => array(
				"port" => 22,
			),
		),
	)
);
```

**Loop: multiple port check**

And an additional code snippet for a multiple port check:

```php
$aPorts=array(
	"22"=>array("SSH"),
	"25"=>array("SMTP"),
	"5666"=>array("Nagios NRPE"),
	"5667"=>array("Nagios NSCA"),
);


foreach($aPorts as $iPort=>$aDescr){
	if (count($aDescr)==1) {
		$aDescr[1]="check port $iPort";
	}
	$oMonitor->addCheck(
		array(
			"name" => $aDescr[0],
			"description" => $aDescr[1],
			"check" => array(
				"function" => "PortTcp",
				"params" => array(
					"port"=>$iPort
				),
			),
		)
	);
}
```
