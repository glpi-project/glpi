<?php
define('GLPI_ROOT', '..');
include (GLPI_ROOT . "/inc/includes.php");

$dirs = array(GLPI_ROOT,GLPI_ROOT.'/inc/',
               GLPI_ROOT.'/ajax/',
               GLPI_ROOT.'/front/',
               GLPI_ROOT.'/install/');

foreach($dirs as $dir) {
   if ($handle = opendir($dir)) {
      echo "Check dir $handle\n";
      echo "Files :\n";
   
      /* Ceci est la façon correcte de traverser un dossier. */
      while (false !== ($file = readdir($handle))) {
         if ($file != "." && $file != ".." && preg_match('/\.php$/',$file)) {
            echo "$file\n";
            system("php -l ".$dir.'/'.$file);
         }
      }
   
      closedir($handle);
   }
}


?>
