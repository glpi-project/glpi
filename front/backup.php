<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

include ('../inc/includes.php');

if (isset($_POST['check_version'])) {
   Session::checkRight('backup', Backup::CHECKUPDATE);
   Toolbox::checkNewVersionAvailable(0, true);
   Html::back();
}

Session::checkRight("backup", READ);

Html::header(__('Maintenance'), $_SERVER['PHP_SELF'], "admin", "backup");

$max_time = min(get_cfg_var("max_execution_time"), get_cfg_var("max_input_time"));

if ($max_time == 0) {
   $defaulttimeout  = 60;
   $defaultrowlimit = 5;

} else if ($max_time > 5) {
   $defaulttimeout  = $max_time-2;
   $defaultrowlimit = 5;

} else {
   $defaulttimeout  = max(1, $max_time-2);
   $defaultrowlimit = 2;
}


/**
 * Generate an XML backup file of the database
 * @global DB $DB
 */
function xmlbackup() {
   global $CFG_GLPI, $DB;

   //on parcoure la DB et on liste tous les noms des tables dans $table
   //on incremente $query[] de "select * from $table"  pour chaque occurence de $table

   $result = $DB->listTables();
   $i      = 0;
   while ($line = $result->next()) {
      $table = $line['TABLE_NAME'];

      $query[$i] = "SELECT *
                    FROM `$table`";
      $i++;
   }

   // Filename
   $time_file = date("Y-m-d-H-i");
   $filename = GLPI_DUMP_DIR . "/glpi-backup-" . GLPI_VERSION . "-$time_file.xml";

   $A = new XML();

   // Your query
   $A->SqlString = $query;

   //File path
   $A->FilePath = $filename;

   // Define layout type
   $A->Type = 4;

   // Generate the XML file
   $A->DoXML();

   // In case of error, display it
   if ($A->IsError == 1) {
      printf(__('ERROR:'), $A->ErrorString);
   }
}

/**
 * Init time to computer time spend
 * @global type $TPSDEB
 * @global int $TPSCOUR
 */
function init_time() {
   global $TPSDEB, $TPSCOUR;

   list($usec,$sec) = explode(" ", microtime());
   $TPSDEB          = $sec;
   $TPSCOUR         = 0;
}

/**
 * Get current time
 * @global type $TPSDEB
 * @global type $TPSCOUR
 */
function current_time() {
   global $TPSDEB, $TPSCOUR;

   list($usec,$sec) = explode(" ", microtime());
   $TPSFIN          = $sec;
   if (round($TPSFIN-$TPSDEB, 1) >= $TPSCOUR+1) {//une seconde de plus
      $TPSCOUR = round($TPSFIN-$TPSDEB, 1);
   }
}

/**
 * Get data of a table
 * @param DB $DB
 * @param string $table table name
 * @param integer $from FROM xxx
 * @param integer $limit LIMIT xxx
 * @return string SQL query "INSERT INTO..."
 */
function get_content($DB, $table, $from, $limit) {

   $content = "";

   $result = $DB->request($table, ['START' => $from, 'LIMIT' => $limit]);

   if ($result) {
      $num_fields = $DB->num_fields($result);

      while ($row = $DB->fetch_row($result)) {
         $insert = "INSERT INTO `$table` VALUES (";

         for ($j = 0; $j < $num_fields; $j++) {
            if (is_null($row[$j])) {
               $insert .= "NULL,";
            } else if ($row[$j] != "") {
               $insert .= "'" . addslashes($row[$j]) . "',";
            } else {
               $insert .= "'',";
            }
         }
         $insert = preg_replace("/,$/", "", $insert);
         $insert .= ");\n";
         $content .= $insert;
      }
   }
   return $content;
}

/**  Get structure of a table
 *
 * @param $DB     DB object
 * @param $table  table name
**/
function get_def($DB, $table) {

   $def  = "### Dump table $table\n\n";
   $def .= "DROP TABLE IF EXISTS `$table`;\n";

   $query  = "SHOW CREATE TABLE `$table`";
   $result = $DB->query($query);
   $DB->query("SET SESSION sql_quote_show_create = 1");
   $row = $DB->fetch_row($result);

   $def .= preg_replace("/AUTO_INCREMENT=\w+/i", "", $row[1]);
   $def .= ";";
   return $def."\n\n";
}


/**  Restore a mysql dump
 *
 * @param $DB        DB object
 * @param $dumpFile  dump file
 * @param $duree     max delay before refresh
**/
function restoreMySqlDump($DB, $dumpFile, $duree) {
   global $DB, $TPSCOUR, $offset, $cpt;

   // $dumpFile, fichier source
   // $duree=timeout pour changement de page (-1 = aucun)

   // Desactivation pour empecher les addslashes au niveau de la creation des tables
   // En plus, au niveau du dump on considere qu'on est bon
   // set_magic_quotes_runtime(0);

   if (!file_exists($dumpFile)) {
      echo sprintf(__('File %s not found.'), $dumpFile)."<br>";
      return false;
   }
   if (substr($dumpFile, -2) == "gz") {
      $fileHandle = gzopen($dumpFile, "rb");
   } else {
      $fileHandle = fopen($dumpFile, "rb");
   }

   if (!$fileHandle) {
      //TRASN: %s is the name of the file
      echo sprintf(__('Unauthorized access to the file %s'), $dumpFile)."<br>";
      return false;
   }

   if ($offset != 0) {
      if (substr($dumpFile, -2) == "gz") {
         if (gzseek($fileHandle, $offset, SEEK_SET) != 0) { //erreur
            //TRANS: %s is the number of the byte
            printf(__("Unable to find the byte %s"), Html::formatNumber($offset, false, 0));
            echo "<br>";
            return false;
         }
      } else {
         if (fseek($fileHandle, $offset, SEEK_SET) != 0) { //erreur
            //TRANS: %s is the number of the byte
            printf(__("Unable to find the byte %s"), Html::formatNumber($offset, false, 0));
            echo "<br>";
            return false;
         }
      }
      Html::glpi_flush();
   }

   $formattedQuery = "";

   if (substr($dumpFile, -2) == "gz") {
      while (!gzeof($fileHandle)) {
         current_time();
         if (($duree > 0)
             && ($TPSCOUR >= $duree)) { //on atteint la fin du temps imparti
            return true;
         }

         // specify read length to be able to read long lines
         $buffer = gzgets($fileHandle, 102400);

         // do not strip comments due to problems when # in begin of a data line
         $formattedQuery .= $buffer;

         if (substr(rtrim($formattedQuery), -1) == ";") {
            // Do not use the $DB->query
            if ($DB->query($formattedQuery)) { //if no success continue to concatenate
               $offset         = gztell($fileHandle);
               $formattedQuery = "";
               $cpt++;
            }
         }
      }
   } else {
      while (!feof($fileHandle)) {
         current_time();
         if (($duree > 0)
               && ($TPSCOUR >= $duree)) { //on atteint la fin du temps imparti
            return true;
         }

         // specify read length to be able to read long lines
         $buffer = fgets($fileHandle, 102400);

         // do not strip comments due to problems when # in begin of a data line
         $formattedQuery .= $buffer;

         if (substr(rtrim($formattedQuery), -1) == ";") {
            // Do not use the $DB->query
            if ($DB->query($formattedQuery)) { //if no success continue to concatenate
               $offset         = ftell($fileHandle);
               $formattedQuery = "";
               $cpt++;
            }
         }
      }
   }

   if ($DB->error) {
      echo "<hr>";
      //TRANS: %s is the SQL query which generates the error
      printf(__("SQL error starting from %s"), "[$formattedQuery]");
      echo "<br>".$DB->error()."<hr>";
   }

   if (substr($dumpFile, -2) == "gz") {
      gzclose($fileHandle);
   } else {
      fclose($fileHandle);
   }
   $offset = -1;
   return true;
}


/**  Backup a glpi DB
 *
 * @param $DB        DB object
 * @param $dumpFile  dump file
 * @param $duree     max delay before refresh
 * @param $rowlimit  rowlimit to backup in one time
**/
function backupMySql($DB, $dumpFile, $duree, $rowlimit) {
   global $TPSCOUR, $offsettable, $offsetrow, $cpt;

   // $dumpFile, fichier source
   // $duree=timeout pour changement de page (-1 = aucun)

   if (function_exists('gzopen')) {
      $fileHandle = gzopen($dumpFile, "a");
   } else {
      $fileHandle = gzopen64($dumpFile, "a");
   }

   if (!$fileHandle) {
      //TRANS: %s is the name of the file
      echo sprintf(__('Unauthorized access to the file %s'), $dumpFile)."<br>";
      return false;
   }

   if ($offsettable == 0 && $offsetrow == -1) {
      $time_file = date("Y-m-d-H-i");
      $cur_time  = date("Y-m-d H:i");
      $todump    = "#GLPI Dump database on $cur_time\n";
      gzwrite ($fileHandle, $todump);
   }

   $result = $DB->listTables();
   $numtab = 0;
   while ($t = $result->next()) {
      $tables[$numtab] = $t['TABLE_NAME'];
      $numtab++;
   }

   for (; $offsettable<$numtab; $offsettable++) {
      // Dump de la structure table
      if ($offsetrow == -1) {
         $todump = "\n".get_def($DB, $tables[$offsettable]);
         gzwrite ($fileHandle, $todump);
         $offsetrow++;
         $cpt++;
      }
      current_time();
      if (($duree > 0)
          && ($TPSCOUR >= $duree)) { //on atteint la fin du temps imparti
         return true;
      }
      $fin = 0;
      while (!$fin) {
         $todump    = get_content($DB, $tables[$offsettable], $offsetrow, $rowlimit);
         $rowtodump = substr_count($todump, "INSERT INTO");

         if ($rowtodump > 0) {
            gzwrite ($fileHandle, $todump);
            $cpt       += $rowtodump;
            $offsetrow += $rowlimit;
            if ($rowtodump<$rowlimit) {
               $fin = 1;
            }
            current_time();
            if (($duree > 0)
                && ($TPSCOUR >= $duree)) { //on atteint la fin du temps imparti
               return true;
            }

         } else {
            $fin       = 1;
            $offsetrow = -1;
         }
      }

      if ($fin) {
         $offsetrow = -1;
      }
      current_time();
      if (($duree > 0)
          && ($TPSCOUR >= $duree)) { //on atteint la fin du temps imparti
         return true;
      }
   }

   if ($DB->error()) {
      echo "<hr>";
      //TRANS: %s is the SQL query which generates the error
      printf(__("SQL error starting from %s"), "[$formattedQuery]");
      echo "<br>".$DB->error()."<hr>";
   }
   $offsettable = -1;
   gzclose($fileHandle);
   return true;
}


// #################" DUMP sql#################################

if (isset($_GET["dump"]) && $_GET["dump"] != "") {
   $time_file = date("Y-m-d-H-i");
   $cur_time  = date("Y-m-d H:i");
   $filename  = GLPI_DUMP_DIR . "/glpi-backup-".GLPI_VERSION."-$time_file.sql.gz";

   if (!isset($_GET["duree"]) && is_file($filename)) {
      echo "<div class='center'>".__('The file already exists')."</div>";

   } else {
      init_time(); //initialise le temps
      //debut de fichier

      if (!isset($_GET["offsettable"])) {
         $offsettable = 0;
      } else {
         $offsettable = $_GET["offsettable"];
      }

      //debut de fichier
      if (!isset($_GET["offsetrow"])) {
         $offsetrow = -1;
      } else {
         $offsetrow = $_GET["offsetrow"];
      }

      //timeout de 5 secondes par defaut, -1 pour utiliser sans timeout
      if (!isset($_GET["duree"])) {
         $duree = $defaulttimeout;
      } else {
         $duree = $_GET["duree"];
      }

      //Limite de lignes a dumper a chaque fois
      if (!isset($_GET["rowlimit"])) {
         $rowlimit = $defaultrowlimit;
      } else {
         $rowlimit = $_GET["rowlimit"];
      }

      //si le nom du fichier n'est pas en parametre le mettre ici
      if (!isset($_GET["fichier"])) {
         $fichier = $filename;
      } else {
         $fichier = $_GET["fichier"];
      }

      $tot = $DB->listTables()->count();
      if (isset($offsettable)) {
         if ($offsettable >= 0) {
            $percent = min(100, round(100*$offsettable/$tot, 0));
         } else {
            $percent = 100;
         }
      } else {
         $percent = 0;
      }

      if ($percent >= 0) {
         Html::displayProgressBar(400, $percent);
         echo '<br>';
      }

      if ($offsettable >= 0) {
         if (backupMySql($DB, $fichier, $duree, $rowlimit)) {
            echo "<div class='center spaced'>".
                 "<a href=\"backup.php?dump=1&duree=$duree&rowlimit=$rowlimit&offsetrow=".
                    "$offsetrow&offsettable=$offsettable&cpt=$cpt&fichier=$fichier\">".
                    __('Automatic redirection, else click')."</a>";
            echo "<script type='text/javascript'>" .
                "window.location=\"backup.php?dump=1&duree=$duree&rowlimit=".
                     "$rowlimit&offsetrow=$offsetrow&offsettable=$offsettable&cpt=$cpt&fichier=".
                     "$fichier\";</script></div>";
            Html::glpi_flush();
            exit;
         }
      }
   }
}


// ##############################   fin dump sql########################""""


// ################################## dump XML #############################

if (isset($_GET["xmlnow"]) && ($_GET["xmlnow"] != "")) {
   xmlbackup();
}

// ################################## fin dump XML #############################

if (isset($_GET["file"]) && ($_GET["file"] != "") && is_file(GLPI_DUMP_DIR . "/" . $_GET["file"])) {
   $filepath = realpath(GLPI_DUMP_DIR . "/" . $_GET['file']);
   if (is_file($filepath) && Toolbox::startsWith($filepath, GLPI_DUMP_DIR)) {
      $_SESSION['TRY_OLD_CONFIG_FIRST'] = true;
      init_time(); //initialise le temps

      //debut de fichier
      if (!isset($_GET["offset"])) {
         $offset = 0;
      } else {
         $offset = $_GET["offset"];
      }

      //timeout de 5 secondes par defaut, -1 pour utiliser sans timeout
      if (!isset($_GET["duree"])) {
         $duree = $defaulttimeout;
      } else {
         $duree = $_GET["duree"];
      }

      $fsize = filesize($filepath);
      if (isset($offset)) {
         if ($offset == -1) {
            $percent = 100;
         } else {
            $percent = min(100, round(100*$offset/$fsize, 0));
         }
      } else {
         $percent = 0;
      }

      if ($percent >= 0) {
         Html::displayProgressBar(400, $percent);
         echo '<br>';
      }

      if ($offset != -1) {
         if (restoreMySqlDump($DB, $filepath, $duree)) {
            echo "<div class='center'>".
               "<a href=\"backup.php?file=".$_GET["file"]."&amp;duree=$duree&amp;offset=".
                     "$offset&amp;cpt=$cpt&amp;donotcheckversion=1\">";
            echo __('Automatic redirection, else click')."</a>";
            echo "<script language='javascript' type='text/javascript'>".
                  "window.location=\"backup.php?file=".
                  $_GET["file"]."&duree=$duree&offset=$offset&cpt=$cpt&donotcheckversion=1\";".
                  "</script></div>";
            Html::glpi_flush();
            exit;
         }

      } else {
         // Compatiblity for old version for utf8 complete conversion
         $cnf                = new Config();
         $input['id']        = 1;
         $input['utf8_conv'] = 1;
         $cnf->update($input);
      }
   }
}

if (isset($_POST["delfile"])) {
   if (isset($_POST['file']) && ($_POST["file"] != "")) {
      $filepath = realpath(GLPI_DUMP_DIR . "/" . $_POST['file']);
      if (is_file($filepath) && Toolbox::startsWith($filepath, GLPI_DUMP_DIR)) {
         $filename = $_POST["file"];
         unlink($filepath);
         // TRANS: %s is a file name
         echo "<div class ='center spaced'>".sprintf(__('%s deleted'), $filename)."</div>";
      }
   }
}

if (Session::haveRight('backup', Backup::CHECKUPDATE)) {
   echo "<div class='center spaced'><table class='tab_glpi'>";
   echo "<tr class='tab_bg_1'><td colspan='4' class='center b'>";
   Html::showSimpleForm($_SERVER['PHP_SELF'], 'check_version',
                        __('Check if a new version is available'));
   echo "</td></tr></table></div>";
}

// Title backup
echo "<div class='center'>";
if (Session::haveRight('backup', CREATE)) {
   echo "<table class='tab_glpi'><tr><td colspan='4'>";
   echo "<div class='warning'><i class='fa fa-exclamation-triangle fa-5x'></i><ul><li>";
   echo __('GLPI internal backup system is a helper for very small instances.');
   echo "<br/>" . __('You should rather use a dedicated tool on your server.');
   echo "</li></ul></div>";
   echo "</td></tr><tr><td>";
   echo "<i class='fa fa-save fa-3x'></i>";
         "</td>";
   echo "<td><a class='vsubmit'
              href=\"#\" ".HTML::addConfirmationOnAction(__('Backup the database?'),
                                                         "window.location='".$CFG_GLPI["root_doc"].
                                                           "/front/backup.php?dump=dump'").
              ">".__('SQL Dump')."</a>&nbsp;</td>";
   echo "<td><a class='vsubmit'
              href=\"#\" ".HTML::addConfirmationOnAction(__('Backup the database?'),
                                                         "window.location='".$CFG_GLPI["root_doc"].
                                                           "/front/backup.php?xmlnow=xmlnow'").
              ">".__('XML Dump')."</a>&nbsp;</td>";
   echo "</tr></table>";
}
echo "<br><table class='tab_cadre' cellpadding='5'>".
     "<tr class='center'>".
     "<th><u><i>".__('File')."</i></u></th>".
     "<th><u><i>".__('Size')."</i></u></th>".
     "<th><u><i>".__('Date')."</i></u></th>".
     "<th colspan='3'>&nbsp;</th>".
     "</tr>";

$dir   = opendir(GLPI_DUMP_DIR);
$files = [];
while ($file = readdir($dir)) {
   if (($file != ".") && ($file != "..")
       && (preg_match("/\.sql.gz$/i", $file)
           || preg_match("/\.sql$/i", $file))) {

      $files[$file] = filemtime(GLPI_DUMP_DIR . "/" . $file);
   }
}
arsort($files);

if (count($files)) {
   foreach ($files as $file => $date) {
      $taille_fic = filesize(GLPI_DUMP_DIR . "/" . $file);
      echo "<tr class='tab_bg_2'><td>$file&nbsp;</td>".
           "<td class='right'>".Toolbox::getSize($taille_fic)."</td>".
           "<td>&nbsp;" . Html::convDateTime(date("Y-m-d H:i", $date)) . "</td>";
      if (Session::haveRight('backup', PURGE)) {
         echo "<td>&nbsp;";
              //TRANS: %s is the filename
              $string = sprintf(__('Delete the file %s?'), $file);
              Html::showSimpleForm($_SERVER['PHP_SELF'], 'delfile',
                                   _x('button', 'Delete permanently'),
                                   ['file' => $file], '', '', $string);

         echo "</td>";
         echo "<td>&nbsp;";
         // Multiple confirmation
         $string   = [];
         //TRANS: %s is the filename
         $string[] = [sprintf(__('Replace the current database with the backup file %s?'),
                                   $file)];
         $string[] = [__('Warning, your actual database will be totaly overwriten by the database you want to restore !!!')];

         echo "<a class='vsubmit' href=\"#\" ".HTML::addConfirmationOnAction($string,
                                        "window.location='".$CFG_GLPI["root_doc"].
                                        "/front/backup.php?file=$file&amp;donotcheckversion=1'").
              ">".__('Restore')."</a>&nbsp;</td>";
      }
      if (Session::haveRight('backup', CREATE)) {
         echo "<td>&nbsp;".
              "<a class='vsubmit' href=\"document.send.php?file=_dumps/$file\">".__('Download').
              "</a></td>";
      }
      echo "</tr>";
   }
}
closedir($dir);

$dir = opendir(GLPI_DUMP_DIR);
unset($files);
$files = [];

while ($file = readdir($dir)) {
   if (($file != ".") && ($file != "..")
       && preg_match("/\.xml$/i", $file)) {

      $files[$file] = filemtime(GLPI_DUMP_DIR . "/" . $file);
   }
}
arsort($files);

if (count($files)) {
   foreach ($files as $file => $date) {
      $taille_fic = filesize(GLPI_DUMP_DIR . "/" . $file);
      echo "<tr class='tab_bg_1'><td colspan='6'><hr noshade></td></tr>".
           "<tr class='tab_bg_2'><td>$file&nbsp;</td>".
            "<td class='right'>".Toolbox::getSize($taille_fic)."</td>".
            "<td>&nbsp;" . Html::convDateTime(date("Y-m-d H:i", $date)) . "</td>";
      if (Session::haveRight('backup', PURGE)) {
         echo "<td colspan=2>";
         //TRANS: %s is the filename
         $string = sprintf(__('Delete the file %s?'), $file);
         Html::showSimpleForm($_SERVER['PHP_SELF'], 'delfile', _x('button', 'Delete permanently'),
                              ['file' => $file], '', '', $string);
         echo "</td>";
      }
      if (Session::haveRight('backup', CREATE)) {
         echo "<td>&nbsp;<a class='vsubmit' href=\"document.send.php?file=_dumps/$file\">".
                       __('Download')."</a></td>";
      }
      echo "</tr>";
   }
}
closedir($dir);

echo "</table>";
echo "</div>";

Html::footer();
