<?php
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

foreach ($CFG_GLPI['languages'] as $lang) {
   include (GLPI_ROOT . "/locales/".$lang[1]);
}
?>
