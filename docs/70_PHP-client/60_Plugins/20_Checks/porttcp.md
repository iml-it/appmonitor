# PortTcp #

## Description ##

Check if the local server or another host is listening to a given port number.

## Syntax ##

```php
$oMonitor->addCheck(
    [
        "name" => "Port local SSH",
        "description" => "check port 22",
        "check" => [
            "function" => "PortTcp",
            "params" => [
                "host" => [hostname],
                "port" => [port number],
                "timeout" => [time],
            ],
        ],
    ]
);
```

## Parameters ##

| key      | type     | description
|---       |---       |---
|port🔸    |(integer) |port to check
|host      |(string)  |optional: hostname to connect to; if unavailable 127.0.0.1 will be tested
|timeout   |(integer) |optional timeout in sec; default: 5

🔸 required

## Examples ##

### Check local SSH port (22) ###

```php
$oMonitor->addCheck(
    [
        "name" => "Port local SSH",
        "description" => "check port 22",
        "check" => [
            "function" => "PortTcp",
            "params" => [
                "port" => 22,
            ],
        ],
    ]
);
```

### Loop: multiple port check ###

And an additional code snippet for a multiple port check:

```php
$aPorts=[
    "22"=>["SSH"],
    "25"=>["SMTP"],
    "5666"=>["Nagios NRPE"],
    "5667"=>["Nagios NSCA"],
];

foreach($aPorts as $iPort=>$aDescr){
    if (count($aDescr)==1) {
        $aDescr[1]="check port $iPort";
    }
    $oMonitor->addCheck(
        [
            "name" => $aDescr[0],
            "description" => $aDescr[1],
            "check" => [
                "function" => "PortTcp",
                "params" => [
                    "port"=>$iPort
                ],
            ],
        ]
    );
}
```
