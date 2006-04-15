<?php


include ("_relpos.php");
include ($phproot . "/glpi/includes.php");
include ($phproot . "/glpi/includes_planning.php");
include ($phproot . "/glpi/includes_reminder.php");
include ($phproot . "/glpi/includes_tracking.php");
include ($phproot . "/glpi/includes_users.php");
include ($phproot . "/glpi/includes_computers.php");
include ($phproot . "/glpi/includes_printers.php");
include ($phproot . "/glpi/includes_monitors.php");
include ($phproot . "/glpi/includes_peripherals.php");
include ($phproot . "/glpi/includes_networking.php");
include ($phproot . "/glpi/includes_software.php");
include ($phproot . "/glpi/includes_enterprises.php");


//export ICAL

if (!isset($_GET["uID"])) $_GET["uID"]=$_SESSION["glpiID"];
// Send UTF8 Headers
@header ("content-type:text/calendar; charset=UTF-8");
echo generateIcal($_GET["uID"]);



?>