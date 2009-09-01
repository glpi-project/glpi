<?php
$NEEDED_ITEMS = array ('computer', 'enterprise', 'monitor', 'networking', 'peripheral',
   'phone', 'planning', 'printer', 'reminder', 'software', 'tracking', 'user');

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");


//export ICAL

if (!isset($_GET["uID"])){
	$_GET["uID"]=$_SESSION["glpiID"];
}
// Send UTF8 Headers
@header ("content-type:text/calendar; charset=UTF-8");
@header("Content-disposition: filename=\"glpi.ics\"");

echo generateIcal($_GET["uID"]);



?>
