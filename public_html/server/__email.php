<?php

$sTO="axel.hahn@unibe.ch";
$sSUBJECT="AppMonitor - ".date("Y-m-d H:i:s");
$sBody="
Hallihallo<br>
<br>
Hier ist meine Test-Nachricht.<br>
<br>
Viele Grusse<br>
<em>Axel</em>
";

echo "$sTO - $sSUBJECT<br>";
echo "$sBody<br>";

$ret = mail($sTO, $sSUBJECT, $sBody);
var_dump($ret);  // (bool)true

