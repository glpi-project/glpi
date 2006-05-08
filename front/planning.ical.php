<?php


include ("_relpos.php");

$NEEDED_ITEMS=array("planning","tracking","user","computer","printer","monitor","peripheral","networking","software","enterprise","reminder","phone");
include ($phproot . "/inc/includes.php");


//export ICAL

if (!isset($_GET["uID"])) $_GET["uID"]=$_SESSION["glpiID"];
// Send UTF8 Headers
@header ("content-type:text/calendar; charset=UTF-8");
echo generateIcal($_GET["uID"]);



?>