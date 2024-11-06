<?php
/**
 * =======================================================================
 * 
 * PHP EMAIL CATCHER
 * Read emails sent by mail() and browse them
 * 
 * 👤 Author: Axel Hahn, Institute for Medical Education, University of Bern
 * 📄 Source: <https://git-repo.iml.unibe.ch/iml-open-source/php-emailcatcher>
 * 📗 Docs: <https://os-docs.iml.unibe.ch/php-emailcatcher/>
 * 📜 License: GNU GPL 3.0
 * 
 * ----------------------------------------------------------------------
 * 2024-10-08  v0.1  initial version
 * 2024-10-09  v0.2  add links
 * 2024-10-21  v0.3  add tiles on top; add email search
 * =======================================================================
 */
require_once('classes/emailcatcher.class.php');


// $sJsonFile='/tmp/mailin.txt.json';

$sOpen=$_GET['open'] ?? '';
$sShowHtml=$_GET['html'] ?? '';

// ----------------------------------------------------------------------
// FUNCTIONS
// ----------------------------------------------------------------------



function showEmail($sId)
{
    $sReturn='';
    $oMail=new emailcatcher();
    if(!$oMail->setId($sId)){
        $sReturn.="❌ ERROR: Unable to open non existing email id<br>";
    } else {

        $bIsHtml=strstr( $oMail->getBody(), '<html>');
        $sReturn.= '<div id="singlemessage">
            <div class="header">
                <span class="right"><a href="?" class="button close">❌</a>&nbsp;&nbsp;&nbsp;</span>
                <table>
                    <tr><td class="small">🕜 DATE</td><td>'.$oMail->getField('date').'</td></tr>
                    <tr><td class="small">👤 TO</td><td>'.$oMail->getField('to').'</td></tr>
                </table>
                <strong>'.$oMail->getField('subject').'</strong>
            </div>
            <div class="content">
                📜 Header:<br>
                <pre>'.$oMail->getHeader().'</pre>
                <br>🗨️ '
                .($bIsHtml
                    ? '<a href="?open='.$sId.'&html=1" class="button">👁️ Show message as HTML</a><br><br>'
                    : 'Text only:'
                )
                .'<pre>'.htmlentities($oMail->getBody()).'</pre>'
                .'<br>
                <span class="right"><a href="?" class="button close">❌ Close</a></span><br>
                <br>'
            .'</div>'
        .'</div>'
        ;
    }
    return $sReturn;
}

function showHtmlEmail($sId): void
{
    $oMail=new emailcatcher();
    echo '<button onclick="history.back();return false;">back</button><br>';
    if(!$oMail->setId($sId)){
        echo "❌ ERROR: Unable to open non existing email id<br>";
    } else {
        echo $oMail->getBody();
    }
    die();
}
// ----------------------------------------------------------------------
// MAIN
// ----------------------------------------------------------------------

$oMail=new emailcatcher();
$aEmails=$oMail->readEmails();

$sOut='';
$sMessage='';

if(!count($aEmails)){
    $sOut='❌ No email was found.<br>';
} else {

    // get a single email if id was given.
    if ($sOpen){
        if($sShowHtml=="1"){
            showHtmlEmail($sOpen);
        }
        $sMessage=showEmail($sOpen);    
    }

    // show list of emails
    $sTable='';
    $sLatest='';
    foreach($aEmails as $aEmail){

        // --- age of last email
        $sId=$aEmail['id'];
        if(!$sLatest){
            $iAge=date('U') - date('U', strtotime($aEmail['date']));
            $sLatest='Less than 1 min ago.';
            if($iAge>60){
                $sLatest=round($iAge / 60).' min ago';
            }
            if($iAge>60*60){
                $sLatest=round($iAge / 60 / 60 ).' h ago';
            }
            if($iAge>60*60*24){
                $sLatest=round($iAge / 60 / 60 / 24 ).' d ago';
            }
        }

        // --- table with emails
        $sTable.=($sId!=$sOpen 
            ? '<tr>
                <td><a href="?open='.$sId.'">✉️ '.htmlentities($aEmail['subject']).'</a></td>
                <td><a href="?open='.$sId.'">'.htmlentities($aEmail['to']).'</a></td>
                <td><a href="?open='.$sId.'">'.$aEmail['date'].'</a></td>
                </tr>
                '
            : '<tr class="active">
                <td><span>🔶 '. htmlentities($aEmail['subject']).'</span></td>
                <td><span>'.htmlentities($aEmail['to']).'</span></td>
                <td><span>'.$aEmail['date'].'</span></td>
            </tr>'
        );
    }
    $sOut='<div class="box">Messages<br><strong>'.count($aEmails).'</strong></div>'
        .'<div class="box">Last<br><strong>'.$sLatest .'</strong></div>'
        . '<div><input type="text" id="search" size="30" placeholder="Search..."></div>'
        . '<br><br>' 
        ;
    $sOut.='<table id="messagestable">
    <thead>
        <tr><th>Subject</th><th>To</th><th class="date">Date</th></tr>
    </thead>
    <tbgody>'
    .$sTable
    .'</tbody></table>' 
    ;
}


// ----------------------------------------------------------------------
// write html page
// ----------------------------------------------------------------------

?><!doctype html>
<html>
<head>
    <title>Email catcher :: viewer</title>
    <link rel="stylesheet" href="viewer.css">

</head>
<body>
    
    <h1><a href="?">🕶️ Email catcher :: viewer</a></h1>

    <div id="messages"><?php echo $sOut ?></div>

    <footer>
        Email catcher
        📄 <a href="https://git-repo.iml.unibe.ch/iml-open-source/php-emailcatcher" target="source">source</a>
        📗 <a href="https://os-docs.iml.unibe.ch/php-emailcatcher/" target="docs">docs</a>
    </footer>

    <?php echo $sMessage ?>
    <script src="viewer.js"></script>

</body>
</html>
