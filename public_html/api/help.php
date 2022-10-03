<?php
/**
 * included in index.php
 * show help on callback _help_
 */

 if(!isset($aRoutes)){
    header("HTTP/1.1 400 Bad request");
    die('<h1>400 Bad request</h1>');
}

$sHelp='';
foreach($aRoutes as $aRoute){
    $sPath=$aRoute[0];
    $oCallback=$aRoute[1];
    $sHelp.='<tr>
    <td><strong>'.$sPath.'</strong></td>
    <td><pre>'.print_r($oCallback, 1).'</pre></td>
    </tr>
    ';
}
echo '<!DOCTYPE html>
<html>
    <head>    
        <link rel="stylesheet" href="help.css" media="all" />
    </head>
    <body>
        <h1>Appmonitor API</h1>
        <table>
        <tr><th>Route</th><th>Callback</th></tr>
        '.$sHelp.'
        </table>
    </body>
</html>';
