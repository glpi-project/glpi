<?php
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

echo getSingular('criteria')."\n";
echo getPlural('criterias')."\n";

echo getItemTypeForTable('glpi_devicecases');

$result = $DB->list_tables();
$i      = 0;
while ($line = $DB->fetch_array($result)) {
   $itemtype = getItemTypeForTable($line[0]);
   if (!class_exists($itemtype)){
      echo $line[0].' '.$itemtype." does not exists\n";
   }
}
?>