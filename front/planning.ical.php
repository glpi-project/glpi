<?php
$NEEDED_ITEMS=array("planning","tracking","user","computer","printer","monitor","peripheral","networking","software","enterprise","reminder","phone");

define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");


//export ICAL

if (!isset($_GET["uID"])) $_GET["uID"]=$_SESSION["glpiID"];
// Send UTF8 Headers
@header ("content-type:text/calendar; charset=UTF-8");
@header("Content-disposition: filename=\"glpi.ics\"");

echo generateIcal($_GET["uID"]);



?>
