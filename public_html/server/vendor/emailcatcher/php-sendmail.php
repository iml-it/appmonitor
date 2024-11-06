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
 * =======================================================================
 */

require_once('classes/emailcatcher.class.php');

// you will likely need to handle additional arguments such as "-f"
// $args = $_SERVER['argv'];

$oMail=new emailcatcher();
$oMail->catchEmail();

// return 0 to indicate acceptance of the message (not necessarily delivery)
return 0;
