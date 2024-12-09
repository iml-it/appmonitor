<?php

$sTO="axel.hahn@unibe.ch";
$sSUBJECT="AppMonitor - ".date("Y-m-d H:i:s");
$sBody="
Hallihallo

Hier ist meine Test-Nachricht.

Viele Grusse
Axel
";

echo "$sTO - $sSUBJECT<br>";
echo "$sBody<br>";

$ret = mail($sTO, $sSUBJECT, $sBody);
var_dump($ret);  // (bool)true

