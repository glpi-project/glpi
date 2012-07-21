<?php
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

Toolbox::setDebugMode(Session::DEBUG_MODE, 0, 0, 1);

foreach ($CFG_GLPI['languages'] as $key => $lang) {
   Session::loadLanguage($key);
}
?>
