<?php
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

echo getSingular('criteria')."\n";
echo getPlural('criterias')."\n";

echo getItemTypeForTable('glpi_devicecases');

   $result = $DB->list_tables();
   $i      = 0;
   while ($line = $DB->fetch_array($result)) {
      // on se limite aux tables prefixees _glpi
      if (strstr($line[0],"glpi_")) {
         $itemtype = getItemTypeForTable($line[0]);
         if (!class_exists($itemtype)){
            echo $line[0].' '.$itemtype." does not exists\n";
         } else {
           // echo "OK\n";
         }
      }
   }


?>
