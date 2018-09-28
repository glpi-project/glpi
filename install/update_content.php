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

//#################### INCLUDE & SESSIONS ############################
define('GLPI_ROOT', realpath('..'));

// Do not include config.php so set root_doc
$CFG_GLPI['root_doc'] = '..';

include_once (GLPI_ROOT . "/inc/based_config.php");
include_once (GLPI_ROOT . "/inc/db.function.php");
include_once (GLPI_CONFIG_DIR . "/config_db.php");

Session::setPath();
Session::start();

if (!isset($_SESSION['do_content_update'])) {
   die("Sorry. You can't access this file directly");
}

// Init debug variable
Toolbox::setDebugMode(Session::DEBUG_MODE, 0, 0, 1);

//################################ Functions ################################


$max_time = min(get_cfg_var("max_execution_time"), get_cfg_var("max_input_time"));

if ($max_time>5) {
   $defaulttimeout  = $max_time-2;
   $defaultrowlimit = 1;

} else {
   $defaulttimeout  = 1;
   $defaultrowlimit = 1;
}

$DB = new DB();


function init_time() {
   global $TPSDEB, $TPSCOUR;

   list ($usec, $sec) = explode(" ", microtime());
   $TPSDEB  = $sec;
   $TPSCOUR = 0;
}


function current_time() {
   global $TPSDEB,$TPSCOUR;

   list ($usec, $sec) = explode(" ", microtime());
   $TPSFIN = $sec;

   if (round($TPSFIN-$TPSDEB, 1)>=$TPSCOUR+1) {//une seconde de plus
      $TPSCOUR = round($TPSFIN-$TPSDEB, 1);
   }
}


function get_update_content($DB, $table, $from, $limit, $conv_utf8) {

   $content = "";
   $DB->query("SET NAMES latin1");

   $result = $DB->query("SELECT *
                         FROM `$table`
                         LIMIT $from, $limit");

   if ($result) {
      while ($row = $DB->fetch_assoc($result)) {
         if (isset($row["id"])) {
            $insert = "UPDATE `$table`
                       SET ";

            foreach ($row as $key => $val) {
               $insert .= " `".$key."` = ";

               if (!isset($val)) {
                  $insert .= "NULL,";

               } else if ($val != "") {
                  if ($conv_utf8) {
                     // Gestion users AD qui sont deja en UTF8
                     if ($table!="glpi_users" || !Toolbox::seems_utf8($val)) {
                        $val = Toolbox::encodeInUtf8($val);
                     }
                  }
                  $insert .= "'".addslashes($val)."',";

               } else {
                  $insert .= "'',";
               }
            }

            $insert  = preg_replace("/,$/", "", $insert);
            $insert .=" WHERE `id` = '".$row["id"]."' ";
            $insert .= ";\n";
            $content .= $insert;
         }
      }
   }
   return $content;
}


function UpdateContent($DB, $duree, $rowlimit, $conv_utf8, $complete_utf8) {
   global $TPSCOUR, $offsettable, $offsetrow, $cpt;
   // $dumpFile, fichier source
   // $database, nom de la base de données cible
   // $mysqlUser, login pouyr la connexion au serveur MySql
   // $mysqlPassword, mot de passe
   // $histMySql, nom de la machine serveur MySQl
   // $duree=timeout pour changement de page (-1 = aucun)

   $result = $DB->listTables();
   $numtab = 0;
   while ($t = $result->next()) {
      $tables[$numtab] = $t['TABLE_NAME'];
      $numtab++;
   }

   for (; $offsettable<$numtab; $offsettable++) {
      // Dump de la structyre table
      if ($offsetrow==-1) {
         if ($complete_utf8) {
            $DB->query("ALTER TABLE `".$tables[$offsettable]."`
                        DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci");

            $data = $DB->list_fields($tables[$offsettable]);

            foreach ($data as $key =>$val) {
               if (preg_match("/^char/i", $val["Type"])) {
                  $default = "NULL";
                  if (!empty($val["Default"]) && !is_null($val["Default"])) {
                     $default = "'".$val["Default"]."'";
                  }
                  $DB->query("ALTER TABLE `".$tables[$offsettable]."`
                              CHANGE `".$val["Field"]."` `".$val["Field"]."` ".$val["Type"]."
                              CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT $default");

               } else if (preg_match("/^varchar/i", $val["Type"])) {
                  $default = "NULL";
                  if (!empty($val["Default"]) && !is_null($val["Default"])) {
                     $default = "'".$val["Default"]."'";
                  }
                  $DB->query("ALTER TABLE `".$tables[$offsettable]."`
                              CHANGE `".$val["Field"]."` `".$val["Field"]."` VARCHAR( 255 )
                              CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT $default");

               } else if (preg_match("/^longtext/i", $val["Type"])) {
                  $DB->query("ALTER TABLE `".$tables[$offsettable]."`
                              CHANGE `".$val["Field"]."` `".$val["Field"]."` LONGTEXT
                              CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL");

               } else if (preg_match("/^text/i", $val["Type"])) {
                  $DB->query("ALTER TABLE `".$tables[$offsettable]."`
                              CHANGE `".$val["Field"]."` `".$val["Field"]."` TEXT
                              CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL");
               }
            }

         }
         $offsetrow++;
         $cpt++;
      }

      current_time();
      if ($duree>0 && $TPSCOUR>=$duree) {//on atteint la fin du temps imparti
         return true;
      }

      $fin = 0;
      while (!$fin) {
         $todump    = get_update_content($DB, $tables[$offsettable], $offsetrow, $rowlimit,
                                         $conv_utf8);
         $rowtodump = substr_count($todump, "UPDATE ");

         if ($rowtodump>0) {
            $DB->query("SET NAMES utf8");
            $result = $DB->query($todump);

            $cpt       += $rowtodump;
            $offsetrow += $rowlimit;

            if ($rowtodump<$rowlimit) {
               $fin = 1;
            }
            current_time();

            if ($duree>0 && $TPSCOUR>=$duree) {//on atteint la fin du temps imparti
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

      if ($duree>0 && $TPSCOUR>=$duree) {//on atteint la fin du temps imparti
         return true;
      }

   }

   if ($DB->error()) {
      echo "<hr>";
      printf(__("SQL error starting from %s"), "[$formattedQuery]");
      echo "<br>".$DB->error()."<hr>";
   }

   $offsettable = -1;
   return true;
}

//########################### Script start ################################

Session::loadLanguage();

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");

echo "<!DOCTYPE html>";
echo "<html lang='fr'>";
echo "<head>";
echo "<meta charset='utf-8'>";
echo "<meta http-equiv='Content-Script-Type' content='text/javascript'>";
echo "<meta http-equiv='Content-Style-Type' content='text/css'>";
echo "<title>Setup GLPI</title>";
// CSS
echo "<link rel='stylesheet' href='../css/style_install.css' type='text/css' media='screen' >";

echo "</head>";
echo "<body>";
echo "<div id='principal'>";
echo "<div id='bloc'>";
echo "<div id='logo_bloc'></div>";
echo "<h2>GLPI SETUP</h2>";
//end style and co

// #################" UPDATE CONTENT #################################

$time_file = date("Y-m-d-h-i");
$cur_time  = date("Y-m-d H:i");

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

$tot = $DB->listTables()->count();

if (isset($offsettable)) {
   if ($offsettable>=0) {
      $percent = min(100, round(100*$offsettable/$tot, 0));
   } else {
      $percent = 100;
   }

} else {
   $percent = 0;
}

$conv_utf8     = false;
$complete_utf8 = true;
$config_table  = "glpi_config";
if ($DB->tableExists("glpi_configs")) {
   $config_table = "glpi_configs";
}

if (!$DB->fieldExists($config_table, "utf8_conv", false)) {
   $conv_utf8 = true;
} else {
   $query = "SELECT `utf8_conv`
             FROM `$config_table`
             WHERE `id` = '1'";

   $result = $DB->query($query);
   $data   = $DB->fetch_assoc($result);

   if ($data["utf8_conv"]) {
      $complete_utf8 = false;
   }
}

if ($offsettable>=0 && $complete_utf8) {
   if ($percent >= 0) {
      Html::displayProgressBar(400, $percent);
      echo "</div></div></body></html>";
      Html::glpi_flush();
   }

   if (UpdateContent($DB, $duree, $rowlimit, $conv_utf8, $complete_utf8)) {
      echo "<br><a href='update_content.php?dump=1&amp;duree=$duree&amp;rowlimit=".
                 "$rowlimit&amp;offsetrow=$offsetrow&amp;offsettable=$offsettable&amp;cpt=$cpt'>".
                 __('Automatic redirection, else click')."</a>";
      echo "<script language='javascript' type='text/javascript'>
             window.location=\"update_content.php?dump=1&duree=$duree&rowlimit=$rowlimit&offsetrow=".
             "$offsetrow&offsettable=$offsettable&cpt=$cpt\";</script>";
      Html::glpi_flush();
      exit;
   }

} else {
   echo "<p><a class='vsubmit' href='../index.php'>".__('Use GLPI')."</a></p>";
   echo "</div></div></body></html>";
}

if ($conv_utf8) {
   $query = "ALTER TABLE `$config_table`
             ADD `utf8_conv` INT( 11 ) DEFAULT '0' NOT NULL";
   $DB->queryOrDie($query, " 0.6 add utf8_conv to $config_table");
}

if ($complete_utf8) {
   $DB->query("ALTER DATABASE `".$DB->dbdefault."` DEFAULT
               CHARACTER SET utf8 COLLATE utf8_unicode_ci");

   $DB->query("UPDATE `$config_table`
               SET `utf8_conv` = '1'
               WHERE `id` = '1'");
}
