<?php

require_once "classes/appmonitor-checks.class.php";


function getDocs(string $sCheck): array{
        $sPluginFile = strtolower( 'plugins/checks/' . $sCheck . '.php');
        // echo "plugin file: $sPluginFile<br>\n";
        $sCheckClass = 'check' . $sCheck;
        if (!class_exists($sCheckClass)) {
            if (file_exists($sPluginFile)) {
                require_once ($sPluginFile);
            }
        }
        $oPlugin = new $sCheckClass;
        return $oPlugin->explain();

}

$oChecks=new appmonitorcheck();
$aAvailableChecks=$oChecks->listChecks();

$sJs="";
foreach($aAvailableChecks as $sCheck){
    $sJs.= ($sJs ? ",\n" : "" )
        . "\"$sCheck\": ".json_encode(getDocs($sCheck), JSON_PRETTY_PRINT)." "
        ;
}
$sJs="const aChecks = { 
$sJs 
}; ";

// echo "<pre>";
// print_r($aAvailableChecks);
// print_r(getDocs($aAvailableChecks[0]));
// echo "<hr>$sJs";

echo <<< EOSS
<script>

// data for checks
$sJs

const aMeta = {
    "host": {
        "description": "Set the physical hostname where the application runs. If no host is given then php_uname(\“n\”) will be used to set one.",
        "type": "string",
        "required": false,
        "example": "www.example.com",
        "method": "setHost",
        "snippet_php": "",
        "snippet_ini": "",
    },
    "website": {
        "description": "Set a name for this website or application and optional its environment",
        "type": "string",
        "required": true,
        "example": "Company website",
        "method": "setWebsite",
        "snippet_php": "",
        "snippet_ini": "",
    },
    "ttl": {
        "description": "TTL how long the data are valid and when the server should update the application check.",
        "type": "integer",
        "required": false,
        "example": "300",
        "method": "setTTL",
        "snippet_php": "",
        "snippet_ini": "",
    },
    "tags": {
        "description": "List of tags with names of tool, applicatiuon, environment",
        "type": "array",
        "required": false,
        "example": "['blog', 'wordpress', 'live']",
        "method": "addTag",
        "snippet_php": "\$oMonitor->addTag(\"blog\");<br>\$oMonitor->addTag(\"wordpress\");<br>\$oMonitor->addTag(\"live\");<br>",
        "snippet_ini": "tags[]=\"blog\"<br>tags[]=\"wordpress\"<br>tags[]=\"live\"<br>",
    },
}

const aNotifications = {
    "email": {
        "description": "Add emails to notify support teams",
        "type": "string",
        "required": false,
        "example": "support@example.com, developers@example.com",
        "method": "addEmail",
        "snippet_php": "\$oMonitor->addEmail(\"support@example.com\");<br>\$oMonitor->addEmai(\"developers@example.com\");<br>",
        "snippet_ini": "email[]=\"support@example.com\"<br>email[]=\"developers@example.com\"<br>",
    },
    "slack": {
        "description": "Set a name for this website or application and optional its environment",
        "type": "string",
        "required": false,
        "example": "Company website",
        "method": "setWebsite",
        "snippet_php": "\$oMonitor->addSlackWebhook(\"support-channel\", \"https://hooks.slack.com/services/AAAAA/BBBBB/CCCCCC\");<br>",
        "snippet_ini": "slack[\"#support-channel\"]=\"https://hooks.slack.com/services/XXXXXX/YYYYYY/ZZZZZ\"<br>",
    },
}

function setTab(sActiveId, sClass2Search){
    aDivs=document.getElementsByClassName(sClass2Search);
    for (var i=0; i<aDivs.length; i++){
        aDivs[i].style.display=(aDivs[i].id==sActiveId) ? "" : "none";
        aDivs[i].classname=(aDivs[i].id==sActiveId) ? "active" : "";
    }
    oLink=document.getElementById("nav-"+sActiveId);
    if(oLink){
        // oLink.classnames="active";
    }
}


function showMeta(){
    var sDescription="";
    var sPhp="";
    var sIni="";

    sDescription+='<h3>Meta data</h3>';
    sPhp+='require_once(\'classes/appmonitor-client.class.php\');<br>'
        + '\$oMonitor = new appmonitor();<br>'
        + '<br>'
        ;

    sIni+='[meta]<br>'
        ;

    var iCount=0;
    for(s in aMeta) {   
        iCount++;
        var aParam=aMeta[s];
        sDescription+='<div class="param-'+iCount+'"><strong class="param-name">' + s + '</strong>'+ (aParam.required ? ' (🔸 required)' : '') +'<br>'
            + '<em>' + aParam.description + '</em><br>'
            + 'Type: <span class="param-type">' + aParam.type + '</span><br>'
            + 'Example: <span class="param-value">' + aParam.example + '</span><br>'
            // + 'Example: <input type="text" size="50" value="' + aParam.example + '"><br>'
            + '</div><br>'
            ;
        var value=aParam.example;
        if (aParam.type == 'string') {
            value = '"' + value + '"';
        }
        sPhp+=(aParam.snippet_php == "")
            ? '<div class="param-'+iCount+'">\$oMonitor-><span class="param-name">'+aParam.method+'</span>(' + value + '); '+ (aParam.required ? ' // &lt;&lt;&lt; required' : '') +'</div>'
            : '<br><div class="param-'+iCount+'">// repeat \$oMonitor-><span class="param-name">'+aParam.method+'</span>()<br>'+aParam.snippet_php+'</div>'
            ;
        sIni+=(aParam.snippet_ini == "")
            ? '<div class="param-'+iCount+'"><span class="param-name">'+s+'</span>=' + value + '</div>'
            : '<div class="param-'+iCount+'">; define <span class="param-name">'+s+'</span><br>'+aParam.snippet_ini+'</div>'
            ;

    }

    sDescription+='<h3>Notification</h3>';
    sIni+='<br>[notifications]<br>'
        ;

    for(s in aNotifications) {   
        iCount++;
        var aParam=aNotifications[s];
        sDescription+='<div class="param-'+iCount+'"><strong class="param-name">' + s + '</strong>'+ (aParam.required ? ' (🔸 required)' : '') +'<br>'
            + '<em>' + aParam.description + '</em><br>'
            + 'Type: <span class="param-type">' + aParam.type + '</span><br>'
            + 'Example: <span class="param-value">' + aParam.example + '</span><br>'
            // + 'Example: <input type="text" size="50" value="' + aParam.example + '"><br>'
            + '</div><br>'
            ;
        var value=aParam.example;
        if (aParam.type == 'string') {
            value = '"' + value + '"';
        }
        sPhp+=(aParam.snippet_php == "")
            ? '<div class="param-'+iCount+'">\$oMonitor-><span class="param-name">'+aParam.method+'</span>(' + value + '); '+ (aParam.required ? ' // &lt;&lt;&lt; required' : '') +'</div>'
            : '<br><div class="param-'+iCount+'">// repeat \$oMonitor-><span class="param-name">'+aParam.method+'</span>()<br>'+aParam.snippet_php+'</div>'
            ;
        sIni+=(aParam.snippet_ini == "")
            ? '<div class="param-'+iCount+'"><span class="param-name">'+s+'</span>=' + value + '</div>'
            : '<div class="param-'+iCount+'">; define <span class="param-name">'+s+'</span><br>'+aParam.snippet_ini+'</div>'
            ;

    }

    sPhp+='<br>// --- add checks below<br><br>...<br><br>'
        + '// --- finally: send result<br><br>'
        + '\$oMonitor->setResult();<br>'
        + '\$oMonitor->render();<br>'
        ;
    sIni+='<br>; --- add checks below<br>';

    document.getElementById("meta-description").innerHTML = sDescription;
    document.getElementById("meta-php").innerHTML = '<h3>PHP syntax</h3><pre>' + sPhp + '</pre>';
    document.getElementById("meta-ini").innerHTML = '<h3>INI syntax</h3><pre>' + sIni + '</pre>';
}

function selectCheck(sCheck)
{
    var o=aChecks[sCheck];

    var sIntro="";
    var sDescription="";
    var sPhp="";
    var sIni="";

    
    // console.log(o);
    // console.log(o.parameters);

    const sHelpUrl='https://os-docs.iml.unibe.ch/appmonitor/PHP_client/Plugins/Checks/'+sCheck+'.html';
    sIntro+='<h2><span class=\"plugin-name\">🧩 ' + sCheck + '</span></h2>'
        +'<strong>' + o.name + '</strong><br><br>'
        +'👉 ' + o.description+'<br>'
        +'📗 Help page: <a href="'+sHelpUrl+'" target="_blank">'+sHelpUrl+'</a><br>'
        ;
    sDescription+='<h3>Parameters</h3>';

    sPhp+='\$oMonitor->addCheck(<br>[<br>'
        +'    "name" => "&lt;short name for ' + sCheck + ' check>",<br>'
        +'    "description" => "&lt;description for ' + sCheck + ' check>",<br>'
        +'    // "group" => ... ,<br>'
        +'    // "paerent" => ... ,<br>'
        +'    // "worstresult" => ... ,<br>'
        +'<br>'
        +'    "check" => [<br>'
        +'<div class=\"plugin-name\">        "name" => "' + sCheck + '",</div>'
        +'        "rarams" => [<br>'
        ;

    sIni+='["&lt;short name for ' + sCheck + ' check>"]<br>'
        +'description="&lt;short name for ' + sCheck + ' check>"<br>'
        +'<div class=\"plugin-name\">function="' + sCheck + '"</div>'
        +'; group = "{string} &lt;name of a group>"<br>'
        +'; parent = "{string} &lt;section name of another check>"<br>'
        +'; worstresult = {int} 0..3<br>'
        +'<br>'
        ;

    var iCount=0;
    for(s in o.parameters)
    {
        iCount++;
        var aParam=o.parameters[s];
        // console.log(aParam);
        sDescription+='<div class="param-'+iCount+'"><strong class="param-name">' + s + '</strong>'+ (aParam.required ? ' (🔸 required)' : '') +'<br>'
            + '<em>' + aParam.description + '</em><br>'
            + 'Type: <span class="param-type">' + aParam.type + '</span><br>'
            + 'Example: <span class="param-value">' + aParam.example + '</span><br>'
            // + 'Example: <input type="text" size="50" value="' + aParam.example + '"><br>'
            + '</div><br>'
            ;

        var value=aParam.example;
        if (aParam.type == 'string') {
            value = '"' + value + '"';
        }
        sPhp+='<div class="param-'+iCount+'">            "<span class="param-name">'+s+'</span>" => ' + value + ', '+ (aParam.required ? ' // &lt;&lt;&lt; required' : '') +'</div>';
        sIni+='<div class="param-'+iCount+'">param[<span class="param-name">'+s+'</span>]=' + value + '</div>';
        // sPhp += o.parameters[i] + "<br>";
        // sIni += o.parameters[i].ini + "<br>";
    }
    sPhp+='        ],<br>'
        +'    ],<br>'
        +']);';


    document.getElementById("intro").innerHTML = sIntro;
    document.getElementById("description").innerHTML = sDescription;


    document.getElementById("php").innerHTML = '<h3>PHP syntax</h3><pre>' + sPhp + '</pre>';
    document.getElementById("ini").innerHTML = '<h3>INI syntax</h3><pre>' + sIni + '</pre>';
}

window.setTimeout("showMeta();", 100);

</script>
EOSS;

$sList = "";
foreach($aAvailableChecks as $sCheck){
    $sList .= "<a href=\"#\" onclick=\"selectCheck('$sCheck')\">$sCheck</a>";
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appmonitor * Check generator</title>
    <style>
        :root{
            --link-color: #44c;
            --highlight-param-bg: #fec;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 1.0em;
            margin: 0em;
        }
        a{
            color: var(--link-color);
            text-decoration: underline;
        }
        aside{
            border-right: 0px dotted #ccc;
            float: left;
            margin-right: 2em;
            height: 100vH;
        }
        aside a{
            color:#448;
            display: block;
            padding: 0.5em 1em;
            margin-top: 0em;
            text-decoration: none;
            width: 20vH;
        }
        aside a:hover{
            background: #eee;
        }
        aside a:focus{
            background: #dde;
            color: #334;
        }

        main{
            display: block;
        }
        details > summary {
            padding: 4px;
            display: block;
            background-color: #eeeeee;
            border: none;
            box-shadow: 1px 1px 0.5em #bbbbbb;
            cursor: pointer;
        }

        footer{
            position: fixed;
            bottom: 1em;
            right: 1em;
        }
        h1{
            background: #223;
            border-bottom: 1px solid #eee;
            color: #89a;
            padding: 0.5em;
            margin: 0;
        }
        h2{
            color: #225;
            font-size: 1.8em;
            margin-top: 0;
        }
        h3{
            color: #00a;
            font-size: 1.5em;
        }
        p{padding: 1em;}
        pre{
            background: #f8f8f8;
            border: 1px solid #ccc;
            padding: 0.5em;
            overflow: scroll;
        }

        div.description{
            float: left;
            margin-left: 0em;
            width: 20%;
        }
        div.php{
            float: left;
            margin-left: 2em;
            width: 25%;
        }
        div.ini{
            float: left;
            margin-left: 2em;
            width: 25%;
        }

        .plugin-name {color: #900;}
        .param-name{color: #266;}
        .param-type{color: #626;}
        .param-value{color: #11a;}

        html:has(.plugin-name:hover) .plugin-name {background-color: var(--highlight-param-bg);}
        html:has(.param-1:hover) .param-1 {background-color: var(--highlight-param-bg);}
        html:has(.param-2:hover) .param-2 {background-color: var(--highlight-param-bg);}
        html:has(.param-3:hover) .param-3 {background-color: var(--highlight-param-bg);}
        html:has(.param-4:hover) .param-4 {background-color: var(--highlight-param-bg);}
        html:has(.param-5:hover) .param-5 {background-color: var(--highlight-param-bg);}
        html:has(.param-6:hover) .param-6 {background-color: var(--highlight-param-bg);}
        html:has(.param-7:hover) .param-7 {background-color: var(--highlight-param-bg);}
        html:has(.param-8:hover) .param-8 {background-color: var(--highlight-param-bg);}
        html:has(.param-9:hover) .param-9 {background-color: var(--highlight-param-bg);}
        html:has(.param-10:hover) .param-10 {background-color: var(--highlight-param-bg);}
        html:has(.param-11:hover) .param-11 {background-color: var(--highlight-param-bg);}
        html:has(.param-12:hover) .param-12 {background-color: var(--highlight-param-bg);}
        html:has(.param-13:hover) .param-13 {background-color: var(--highlight-param-bg);}
        html:has(.param-14:hover) .param-14 {background-color: var(--highlight-param-bg);}
        html:has(.param-15:hover) .param-15 {background-color: var(--highlight-param-bg);}
    </style>
</head>
<body>


<h1>Appmonitor // Check generator (WIP)</h1>


<details>
    <summary>📜 Metadata</summary>
        <div>

        <p>
            This is a helper to see meta data configuration.<br>
            You see a description for each value and a snippet in PHP and INI (when using compiled amcli binary).
        </p>

            <main>
                <div id="meta-intro">
                    I am an intro text.<br>
                </div>
                <div id="meta-description" class="description"></div>
                <div>
                    <div id="meta-php" class="php"></div>
                    <div id="meta-ini" class="ini"></div>
                </div>
            </main>
        </div>
        <div sytle="clear:both"></div>
            
</details>
<details>
    <summary>🧩 Checks</summary>
        <p>
            This is a helper to see configuration snippets for each check.<br>
            After selecting a check on the left side you get a description for each parameter and a snippet in PHP and INI (when using compiled amcli binary).
        </p>
        <br>
        <aside>
            <?php echo $sList; ?>
        </aside>
        <main>
            <div id="intro">
                &lt;-- Select a plugin to show its help<br>
            </div>
            <div id="description" class="description"></div>
            <div>
                <div id="php" class="php"></div>
                <div id="ini" class="ini"></div>
            </div>
        </main>
</details>

<footer><strong>IML Appmonitor</strong> :: Source: <a href="https://github.com/iml-it/appmonitor">Github</a></footer>

</body>
</html>