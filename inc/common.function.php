<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2010 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------


if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

//*************************************************************************************************
//*************************************************************************************************
//********************************  Fonctions diverses ********************************************
//*************************************************************************************************
//*************************************************************************************************

/**
 * Set the directory where are store the session file
**/
function setGlpiSessionPath() {

   if (ini_get("session.save_handler")=="files") {
      session_save_path(GLPI_SESSION_DIR);
   }
}


/**
 * Start the GLPI php session
**/
function startGlpiSession() {

   if (!session_id()) {
      @session_start();
   }
   // Define current time for sync of action timing
   $_SESSION["glpi_currenttime"] = date("Y-m-d H:i:s");
}


/**
 * Get form URL for itemtype
 *
 * @param $itemtype string: item type
 * @param $full path or relative one
 *
 * return string itemtype Form URL
**/
function getItemTypeFormURL($itemtype, $full=true) {
   global $CFG_GLPI;

   $dir = ($full ? $CFG_GLPI['root_doc'] : '');

   if ($plug=isPluginItemType($itemtype)) {
      $dir .= "/plugins/".strtolower($plug['plugin']);
      $item = strtolower($plug['class']);

   } else { // Standard case
      $item = strtolower($itemtype);
   }

   return "$dir/front/$item.form.php";
}


/**
 * Get search URL for itemtype
 *
 * @param $itemtype string: item type
 * @param $full path or relative one
 *
 * return string itemtype search URL
**/
function getItemTypeSearchURL($itemtype, $full=true) {
   global $CFG_GLPI;

   $dir = ($full ? $CFG_GLPI['root_doc'] : '');

   if ($plug=isPluginItemType($itemtype)) {
      $dir .=  "/plugins/".strtolower($plug['plugin']);
      $item = strtolower($plug['class']);

   } else { // Standard case
      $item = strtolower($itemtype);
   }

   return "$dir/front/$item.php";
}


/**
 * Get ajax tabs url for itemtype
 *
 * @param $itemtype string: item type
 * @param $full path or relative one
 *
 * return string itemtype tabs URL
**/
function getItemTypeTabsURL($itemtype, $full=true) {
   global $CFG_GLPI;

   $dir = ($full ? $CFG_GLPI['root_doc'] : '');

   if ($plug=isPluginItemType($itemtype)) {
      $dir .= "/plugins/".strtolower($plug['plugin']);
      $item = strtolower($plug['class']);

   } else { // Standard case
      $item = strtolower($itemtype);
   }

   return "$dir/ajax/$item.tabs.php";
}


/**
 * get the Entity of an Item
 *
 * @param $itemtype string item type
 * @param $items_id integer id of the item
 *
 * @return integer ID of the entity or -1
**/
function getItemEntity ($itemtype, $items_id) {

   if ($itemtype && class_exists($itemtype)) {
      $item = new $itemtype();

      if ($item->getFromDB($items_id)) {
         return $item->getEntityID();
      }

   }
   return -1;
}


/**
 * Determine if an object name is a plugin one
 *
 * @param $classname class name to analyze
 *
 * @return false or an object containing plugin name and class name
**/
function isPluginItemType($classname) {

   if (preg_match("/Plugin([A-Z][a-z0-9]+)([A-Z]\w+)/",$classname,$matches)) {
      $plug = array();
      $plug['plugin'] = $matches[1];
      $plug['class']  = $matches[2];
      return $plug;
   }
   // Standard case
   return false;
}


/**
 * Is GLPI used in mutli-entities mode ?
 *
 * @return boolean
**/
function isMultiEntitiesMode() {

   if (!isset($_SESSION['glpi_multientitiesmode'])) {
      if (countElementsInTable("glpi_entities")>0) {
         $_SESSION['glpi_multientitiesmode'] = 1;
      } else {
         $_SESSION['glpi_multientitiesmode'] = 0;
      }
   }

   return $_SESSION['glpi_multientitiesmode'];
}


/**
 * Is the user have right to see all entities ?
 *
 * @return boolean
**/
function isViewAllEntities() {
   return ((countElementsInTable("glpi_entities")+1) == count($_SESSION["glpiactiveentities"]));
}


/**
 * Log a message in log file
 *
 * @param $name string: name of the log file
 * @param $text string: text to log
 * @param $force boolean: force log in file not seeing use_log_in_files config
**/
function logInFile($name, $text, $force=false) {
   global $CFG_GLPI;

   $user = '';
   if (function_exists('getLoginUserID')) {
      $user = " [".getLoginUserID()."]";
   }

   if (isset($CFG_GLPI["use_log_in_files"]) && $CFG_GLPI["use_log_in_files"]||$force) {
      error_log(convDateTime(date("Y-m-d H:i:s"))."$user\n".$text,
                3, GLPI_LOG_DIR."/".$name.".log");
   }
}


/**
 * Log in 'php-errors' all args
**/
function logDebug() {
   static $tps = 0;

   $msg = "";
   foreach (func_get_args() as $arg) {
      if (is_array($arg) || is_object($arg)) {
         $msg .= ' ' . print_r($arg, true);
      } else {
         $msg .= ' ' . $arg;
      }
   }

   if ($tps) {
      $msg .= ' ('.number_format(microtime(true)-$tps,3).'", '.
              number_format(memory_get_usage()/1024/1024,2).'Mio)';
   }

   $tps = microtime(true);
   logInFile('php-errors', $msg."\n",true);
}


/**
 * Specific error handler in Normal mode
 *
 * @param $errno integer: level of the error raised.
 * @param $errmsg string: error message.
 * @param $filename string: filename that the error was raised in.
 * @param $linenum integer: line number the error was raised at.
 * @param $vars array: that points to the active symbol table at the point the error occurred.
**/
function userErrorHandlerNormal($errno, $errmsg, $filename, $linenum, $vars) {

   // Date et heure de l'erreur
   $errortype = array (E_ERROR           => 'Error',
                       E_WARNING         => 'Warning',
                       E_PARSE           => 'Parsing Error',
                       E_NOTICE          => 'Notice',
                       E_CORE_ERROR      => 'Core Error',
                       E_CORE_WARNING    => 'Core Warning',
                       E_COMPILE_ERROR   => 'Compile Error',
                       E_COMPILE_WARNING => 'Compile Warning',
                       E_USER_ERROR      => 'User Error',
                       E_USER_WARNING    => 'User Warning',
                       E_USER_NOTICE     => 'User Notice',
                       E_STRICT          => 'Runtime Notice',
                       // Need php 5.2.0
                       4096 /*E_RECOVERABLE_ERROR*/  => 'Catchable Fatal Error',
                       // Need php 5.3.0
                       8192 /* E_DEPRECATED */       => 'Deprecated function',
                       16384 /* E_USER_DEPRECATED */ => 'User deprecated function');
   // Les niveaux qui seront enregistrés
   $user_errors = array(E_USER_ERROR,
                        E_USER_WARNING,
                        E_USER_NOTICE);

   $err = $errortype[$errno] . "($errno): $errmsg\n";
   if (in_array($errno, $user_errors)) {
      $err .= "Variables:".wddx_serialize_value($vars, "Variables")."\n";
   }

   if (function_exists("debug_backtrace")) {
      $err   .= "Backtrace :\n";
      $traces = debug_backtrace();
      foreach ($traces as $trace) {
         if (isset($trace["file"]) && isset($trace["line"])) {
            $err .= $trace["file"] . ":" . $trace["line"] . "\t\t"
                    . (isset($trace["class"]) ? $trace["class"] : "")
                    . (isset($trace["type"]) ? $trace["type"] : "")
                    . (isset($trace["function"]) ? $trace["function"]."()" : ""). "\n";
         }
      }

   } else {
      $err .= "Script: $filename, Line: $linenum\n" ;
   }

   // sauvegarde de l'erreur, et mail si c'est critique
   logInFile("php-errors", $err."\n");

   return $errortype[$errno];
}


/**
 * Specific error handler in Debug mode
 *
 * @param $errno integer: level of the error raised.
 * @param $errmsg string: error message.
 * @param $filename string: filename that the error was raised in.
 * @param $linenum integer: line number the error was raised at.
 * @param $vars array: that points to the active symbol table at the point the error occurred.
**/
function userErrorHandlerDebug($errno, $errmsg, $filename, $linenum, $vars) {

   // For file record
   $type = userErrorHandlerNormal($errno, $errmsg, $filename, $linenum, $vars);

   // Display
   if (!isCommandLine()) {
      echo '<div style="position:fload-left; background-color:red; z-index:10000"><strong>PHP '.
             $type.': </strong>';
      echo $errmsg.' in '.$filename.' at line '.$linenum.'</div>';
   } else {
      echo 'PHP '.$type.': '.$errmsg.' in '.$filename.' at line '.$linenum."\n";
   }
}


/**
 * Is the script launch in Command line ?
 *
 * @return boolean
**/
function isCommandLine() {
   return (!isset($_SERVER["SERVER_NAME"]));
}


/**
 * Encode string to UTF-8
 *
 * @param $string string: string to convert
 * @param $from_charset string: original charset (if 'auto' try to autodetect)
 *
 * @return utf8 string
**/
function encodeInUtf8($string, $from_charset="ISO-8859-1") {

   if (strcmp($from_charset,"auto")==0) {
      $from_charset = mb_detect_encoding($string);
   }
   return mb_convert_encoding($string, "UTF-8", $from_charset);
}


/**
 * Decode string from UTF-8 to specified charset
 *
 * @param $string string: string to convert
 * @param $to_charset string: destination charset (default is ISO-8859-1)
 *
 * @return converted string
**/
function decodeFromUtf8($string, $to_charset="ISO-8859-1") {
   return mb_convert_encoding($string, $to_charset, "UTF-8");
}


/**
 * substr function for utf8 string
 *
 * @param $str string: string
 * @param $start integer: start of the result substring
 * @param $length integer: The maximum length of the returned string if > 0
 *
 * @return substring
**/
function utf8_substr($str, $start, $length=-1) {

   if ($length==-1) {
      $length = utf8_strlen($str)-$start;
   }
   return mb_substr($str, $start, $length, "UTF-8");
}


/**
 * strtolower function for utf8 string
 *
 * @param $str string: string
 *
 * @return lower case string
**/
function utf8_strtolower($str) {
   return mb_strtolower($str, "UTF-8");
}


/**
 * strtoupper function for utf8 string
 *
 * @param $str string: string
 *
 * @return upper case string
**/
function utf8_strtoupper($str) {
   return mb_strtoupper($str, "UTF-8");
}


/**
 * substr function for utf8 string
 *
 * @param $str string: string
 * @param $tofound string: string to found
 * @param $offset integer: The search offset. If it is not specified, 0 is used.
 *
 * @return substring
**/
function utf8_strpos($str, $tofound, $offset=0) {
   return mb_strpos($str, $tofound, $offset, "UTF-8");
}


/**
 * strlen function for utf8 string
 *
 * @param $str string: string
 *
 * @return length of the string
**/
function utf8_strlen($str) {
   return mb_strlen($str, "UTF-8");
}


/** Returns the utf string corresponding to the unicode value
 * (from php.net, courtesy - romans@void.lv)
 *
 * @param $num integer: character code
**/
function code2utf($num) {

   if ($num < 128) {
      return chr($num);
   }

   if ($num < 2048) {
      return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
   }

   if ($num < 65536) {
      return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
   }

   if ($num < 2097152) {
      return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) .
             chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
   }

   return '';
}


/**
 * Get the filesize of a complete directory (from php.net)
 *
 * @param $path string: directory or file to get size
 *
 * @return size of the $path
**/
function filesizeDirectory($path) {

   if (!is_dir($path)) {
      return filesize($path);
   }

   if ($handle = opendir($path)) {
      $size = 0;

      while (false !== ($file = readdir($handle))) {
         if ($file!='.' && $file!='..') {
            $size += filesize($path.'/'.$file);
            $size += filesizeDirectory($path.'/'.$file);
         }
      }

      closedir($handle);
      return $size;
   }
}


/**
 * Get the $RELATION array. It's defined all relations between tables in the DB.
 *
 * @return the $RELATION array
**/
function getDbRelations() {
   global $CFG_GLPI;

   include (GLPI_ROOT . "/inc/relation.constant.php");

   // Add plugins relations
   $plug_rel = getPluginsDatabaseRelations();
   if (count($plug_rel)>0) {
      $RELATION = array_merge_recursive($RELATION,$plug_rel);
   }
   return $RELATION;
}


/**
 * Check Write Access to a directory
 *
 * @param $dir string: directory to check
 *
 * @return 2 : creation error 1 : delete error 0: OK
**/
function testWriteAccessToDirectory($dir) {

   $rand = rand();

   // Check directory creation which can be denied by SElinux
   $sdir = sprintf("%s/test_glpi_%08x", $dir, $rand);

   if (!mkdir($sdir)) {
      return 4;
   }

   if (!rmdir($sdir)) {
      return 3;
   }

   // Check file creation
   $path = sprintf("%s/test_glpi_%08x.txt", $dir, $rand);
   $fp   = fopen($path, 'w');

   if (empty($fp)) {
      return 2;
   }

   $fw = fwrite($fp, "This file was created for testing reasons. ");
   fclose($fp);
   $delete = unlink($path);

   if (!$delete) {
      return 1;
   }

   return 0;
}


/**
 * Compute PHP memory_limit
 *
 * @return memory limit
**/
function getMemoryLimit () {

   $mem = ini_get("memory_limit");
   preg_match("/([-0-9]+)([KMG]*)/", $mem, $matches);
   $mem = "";

   // no K M or G
   if (isset($matches[1])) {
      $mem = $matches[1];
      if (isset($matches[2])) {
         switch ($matches[2]) {
            case "G" :
               $mem *= 1024;
               // nobreak;

            case "M" :
               $mem *= 1024;
               // nobreak;

            case "K" :
               $mem *= 1024;
               // nobreak;
         }
      }
   }

   return $mem;
}


/**
 * Common Checks needed to use GLPI
 *
 * @return 2 : creation error 1 : delete error 0: OK
**/
function commonCheckForUseGLPI() {
   global $LANG;

   $error = 0;

   // Title
   echo "<tr><th>".$LANG['install'][6]."</th><th >".$LANG['install'][7]."</th></tr>";

   // Parser test
   echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][8]."</td>";

   // PHP Version  - exclude PHP3, PHP 4 and zend.ze1 compatibility
   if (substr(phpversion(),0,1) == "5") {
      // PHP > 5 ok, now check PHP zend.ze1_compatibility_mode
      if (ini_get("zend.ze1_compatibility_mode") == 1) {
         $error = 2;
         echo "<td class='red'>
               <img src='".GLPI_ROOT."/pics/redbutton.png'>".$LANG['install'][10]."</td></tr>";
      } else {
         echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt='".$LANG['install'][11].
                    "' title='".$LANG['install'][11]."'></td></tr>";
      }

   } else { // PHP <5
      $error = 2;
      echo "<td class='red'>
            <img src='".GLPI_ROOT."/pics/redbutton.png'>".$LANG['install'][9]."</td></tr>";
   }

   // Check for mysql extension ni php
   echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][71]."</td>";
   if (!function_exists("mysql_query")) {
      echo "<td class='red'>";
      echo "<img src='".GLPI_ROOT."/pics/redbutton.png'>".$LANG['install'][72]."</td></tr>";
      $error = 2;
   } else {
      echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt='".$LANG['install'][73].
                 "' title='".$LANG['install'][73]."'></td></tr>";
   }

   // session test
   echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][12]."</td>";

   // check whether session are enabled at all!!
   if (!extension_loaded('session')) {
      $error = 2;
      echo "<td class='red b'>".$LANG['install'][13]."</td></tr>";

   } else if ((isset($_SESSION["Test_session_GLPI"]) && $_SESSION["Test_session_GLPI"] == 1) // From install
              || isset($_SESSION["glpi_currenttime"])) { // From Update
      echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt='".$LANG['install'][14].
                 "' title='".$LANG['install'][14]."'></td></tr>";

   } else if ($error != 2) {
      echo "<td class='red'>";
      echo "<img src='".GLPI_ROOT."/pics/redbutton.png'>".$LANG['install'][15]."</td></tr>";
      $error = 1;
   }

   //Test for session auto_start
   if (ini_get('session.auto_start')==1) {
      echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][68]."</td>";
      echo "<td class='red'>";
      echo "<img src='".GLPI_ROOT."/pics/redbutton.png'>".$LANG['install'][69]."</td></tr>";
      $error = 2;
   }

   //Test for option session use trans_id loaded or not.
   echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][74]."</td>";

   if (isset($_POST[session_name()]) || isset($_GET[session_name()])) {
      echo "<td class='red'>";
      echo "<img src='".GLPI_ROOT."/pics/redbutton.png'>".$LANG['install'][75]."</td></tr>";
      $error = 2;

   } else {
      echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt='".$LANG['install'][76].
                 "' title='".$LANG['install'][76]."'></td></tr>";
   }

   //Test for sybase extension loaded or not.
   echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][65]."</td>";

   if (ini_get('magic_quotes_sybase')) {
      echo "<td class='red'>";
      echo "<img src='".GLPI_ROOT."/pics/redbutton.png'>".$LANG['install'][66]."</td></tr>";
      $error = 2;

   } else {
      echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt='".$LANG['install'][67].
                 "' title='".$LANG['install'][67]."'></td></tr>";
   }

   //Test for json_encode function.
   echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][102]."</td>";

   if (!function_exists('json_encode') || !function_exists('json_decode')) {
      echo "<td><img src='".GLPI_ROOT."/pics/redbutton.png'>".$LANG['install'][103]."></td></tr>";
      $error = 2;

   } else {
      echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt='".$LANG['install'][85].
                 "' title='".$LANG['install'][85]."'></td></tr>";
   }

   //Test for mbstring extension.
   echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][104]."</td>";

   if (!extension_loaded('mbstring')) {
      echo "<td><img src='".GLPI_ROOT."/pics/redbutton.png'>".$LANG['install'][105]."></td></tr>";
      $error = 2;

   } else {
      echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt='".$LANG['install'][85].
                 "' title='".$LANG['install'][85]."'></td></tr>";
   }

   // memory test
   echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][86]."</td>";

   $mem = getMemoryLimit();

   if ( $mem == "" ) { // memory_limit non compilé -> no memory limit
      echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt='".$LANG['install'][95]." - ".
                 $LANG['install'][89]."' title='".$LANG['install'][95]." - ".
                 $LANG['install'][89]."'></td></tr>";

   } else if ( $mem == "-1" ) { // memory_limit compilé mais illimité
      echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt='".$LANG['install'][96]." - ".
                 $LANG['install'][89]."' title='".$LANG['install'][96]." - ".
                 $LANG['install'][89]."'></td></tr>";

   } else if ($mem<64*1024*1024) { // memoire insuffisante
      $showmem = $mem/1048576;
      echo "<td class='red'><img src='".GLPI_ROOT."/pics/redbutton.png'><b>".
                             $LANG['install'][87]." $showmem Mo</b><br>".$LANG['install'][88]."<br>".
                             $LANG['install'][90]."</td></tr>";
      $error = 2;

   } else { // on a sufisament de mémoire on passe à la suite
      echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png\' alt='".$LANG['install'][91]." - ".
                 $LANG['install'][89]."' title='".$LANG['install'][91]." - ".
                 $LANG['install'][89]."'></td></tr>";
   }

   $suberr = checkWriteAccessToDirs();

   return ($suberr ? $suberr : $error);
}


/**
 * Check Write Access to needed directories
 *
 * @return 2 : creation error 1 : delete error 0: OK
**/
function checkWriteAccessToDirs() {
   global $LANG;

   $dir_to_check = array(GLPI_DUMP_DIR    => $LANG['install'][16],
                         GLPI_DOC_DIR     => $LANG['install'][21],
                         GLPI_CONFIG_DIR  => $LANG['install'][23],
                         GLPI_SESSION_DIR => $LANG['install'][50],
                         GLPI_CRON_DIR    => $LANG['install'][52],
                         GLPI_CACHE_DIR   => $LANG['install'][99],
                         GLPI_GRAPH_DIR   => $LANG['install'][106]);
   $error = 0;

   foreach ($dir_to_check as $dir => $message) {
      echo "<tr class='tab_bg_1'><td class='left b'>".$message."</td>";
      $tmperror = testWriteAccessToDirectory($dir);

      switch ($tmperror) {
         // Error on creation
         case 4 :
            echo "<td><img src='".GLPI_ROOT."/pics/redbutton.png'><p class='red'>".
                       $LANG['install'][100]."</p> ".$LANG['install'][97]."'".$dir."'</td></tr>";
            $error = 2;
            break;

         case 3 :
            echo "<td><img src='".GLPI_ROOT."/pics/redbutton.png'><p class='red'>".
                       $LANG['install'][101]."</p> ".$LANG['install'][97]."'".$dir."'</td></tr>";
            $error=1;
            break;

         // Error on creation
         case 2 :
            echo "<td><img src='".GLPI_ROOT."/pics/redbutton.png'><p class='red'>".
                       $LANG['install'][17]."</p> ".$LANG['install'][97]."'".$dir."'</td></tr>";
            $error = 2;
            break;

         case 1 :
            echo "<td><img src='".GLPI_ROOT."/pics/redbutton.png'><p class='red'>".
                       $LANG['install'][19]."</p> ".$LANG['install'][97]."'".$dir."'</td></tr>";
            $error = 1;
            break;

         default :
            echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt='".$LANG['install'][20].
                       "' title='".$LANG['install'][20]."'></td></tr>";
      }
   }

   // Only write test for GLPI_LOG as SElinux prevent removing log file.
   echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][53]."</td>";

   if (error_log("Test\n", 3, GLPI_LOG_DIR."/php-errors.log")) {
      echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt='".$LANG['install'][22].
                 "' title='".$LANG['install'][22]."'></td></tr>";

   } else {
      echo "<td><img src='".GLPI_ROOT."/pics/redbutton.png'><p class='red'>".$LANG['install'][19].
                 "</p> ".$LANG['install'][97]."'".GLPI_LOG_DIR."'. ".$LANG['install'][98].
           "</td></tr>";
      $error = 1;
   }
   return $error;
}


/**
 * Strip slash  for variable & array
 *
 * @param $value array or string: item to stripslashes (array or string)
 * @return stripslashes item
**/
function stripslashes_deep($value) {

   $value = is_array($value) ? array_map('stripslashes_deep', $value)
                             : (is_null($value) ? NULL : stripslashes($value));

   return $value;
}


/**
 *  Add slash for variable & array
 *
 * @param $value array or string: value to add slashes (array or string)
 *
 * @return addslashes value
**/
function addslashes_deep($value) {

   $value = is_array($value) ? array_map('addslashes_deep', $value)
                             : (is_null($value) ? NULL : mysql_real_escape_string($value));

   return $value;
}

/**
 *
**/
function key_exists_deep($need, $tab) {

   foreach ($tab as $key => $value) {

      if ($need == $key) {
         return true;
      }

      if (is_array($value) && key_exists_deep($need, $value)) {
         return true;
      }

   }
   return false;
}


/**
 * Prevent from XSS
 * Clean code
 *
 * @param $value array or string: item to prevent (array or string)
 *
 * @return clean item
 *
 * @see unclean_cross_side_scripting_deep*
**/
function clean_cross_side_scripting_deep($value) {

   $in  = array('<',
                '>');
   $out = array('&lt;',
                '&gt;');

   $value = is_array($value) ? array_map('clean_cross_side_scripting_deep', $value)
                             : (is_null($value) ? NULL : str_replace($in,$out,$value));

   return $value;
}


/**
 *  Invert fonction from clean_cross_side_scripting_deep
 *
 * @param $value array or string: item to unclean from clean_cross_side_scripting_deep
 *
 * @return unclean item
 *
 * @see clean_cross_side_scripting_deep
**/
function unclean_cross_side_scripting_deep($value) {

   $in  = array('<',
                '>');
   $out = array('&lt;',
                '&gt;');

   $value = is_array($value) ? array_map('unclean_cross_side_scripting_deep', $value)
                             : (is_null($value) ? NULL : str_replace($out,$in,$value));

   return $value;
}


/**
 * Get ldap query results and clean them at the same time
 *
 * @param link the directory connection
 * @param result the query results
 *
 * @return an array which contains ldap query results
**/
function ldap_get_entries_clean($link, $result) {
   return clean_cross_side_scripting_deep(ldap_get_entries($link, $result));
}

/**
 * Recursivly execute nl2br on an Array
 *
 * @param $value string or array
 *
 * @return array of value (same struct as input)
**/
function nl2br_deep($value) {
   return (is_array($value) ? array_map('nl2br_deep', $value) : nl2br($value));
}


/**
 *  Resume text for followup
 *
 * @param $string string: string to resume
 * @param $length integer: resume length
 *
 * @return cut string
**/
function resume_text($string, $length=255) {

   if (strlen($string)>$length) {
      $string = utf8_substr($string, 0, $length)."&nbsp;(...)";
   }

   return $string;
}

/**
 * Recursivly execute html_entity_decode on an Array
 *
 * @param $value string or array
 *
 * @return array of value (same struct as input)
**/
function html_entity_decode_deep($value) {

   return (is_array($value) ? array_map('html_entity_decode_deep', $value)
                            : html_entity_decode($value, ENT_QUOTES, "UTF-8"));
}

/**
 * Recursivly execute htmlentities on an Array
 *
 * @param $value string or array
 *
 * @return array of value (same struct as input)
**/
function htmlentities_deep($value) {

   return (is_array($value) ? array_map('htmlentities_deep', $value) : htmlentities($value,ENT_QUOTES, "UTF-8"));
}

/**
 *  Resume a name for display
 *
 * @param $string string: string to resume
 * @param $length integer: resume length
 *
 * @return cut string
 **/
function resume_name($string, $length=255) {

   if (strlen($string)>$length) {
      $string = utf8_substr($string, 0, $length)."...";
   }

   return $string;
}


/**
 *  Format mail row
 *
 * @param $string string: label string
 * @param $value string: value string
 *
 * @return string
**/
function mailRow($string, $value) {

   $row = utf8_str_pad( $string . ': ', 25, ' ', STR_PAD_RIGHT).$value."\n";
   return $row;
}


/**
 *  Replace str_pad()
 *  who bug with utf8
 *
 * @param $input string: input string
 * @param $pad_length integer: padding length
 * @param $pad_string string: padding string
 * @param $pad_type: integer: padding type
 *
 * @return string
**/
function utf8_str_pad($input, $pad_length, $pad_string = " ", $pad_type = STR_PAD_RIGHT) {

    $diff = strlen($input) - utf8_strlen($input);
    return str_pad($input, $pad_length+$diff, $pad_string, $pad_type);
}


/**
 * Clean post value for display in textarea
 *
 * @param $value string: string value
 *
 * @return clean value
**/
function cleanPostForTextArea($value) {

   $order   = array('\r\n',
                    '\n',
                    "\\'",
                    '\"',
                    '\\\\');
   $replace = array("\n",
                    "\n",
                    "'",
                    '"',
                    "\\");
   return str_replace($order, $replace, $value);
}


/**
 * Clean display value deleting html tags
 *
 *@param $value string: string value
 *
 *@return clean value
**/
function html_clean($value) {

   $value = preg_replace("/<(p|br)( [^>]*)?".">/i", "\n", $value);

   $specialfilter = array('@<span[^>]*?x-hidden[^>]*?>.*?</span[^>]*?>@si'); // Strip ToolTips
   $value = preg_replace($specialfilter, ' ', $value);

   $search = array('@<script[^>]*?>.*?</script[^>]*?>@si', // Strip out javascript
                   '@<style[^>]*?>.*?</style[^>]*?>@si',   // Strip style tags properly
                   '@<[\/\!]*?[^<>]*?>@si',                // Strip out HTML tags
                   '@<![\s\S]*?--[ \t\n\r]*>@');           // Strip multi-line comments including CDATA

   $value = preg_replace($search, ' ', $value);

   $value = preg_replace("/(&nbsp;| )+/", " ", $value);
   // nettoyer l'apostrophe curly qui pose probleme a certains rss-readers, lecteurs de mail...
   $value = str_replace("&#8217;", "'", $value);

   $value = preg_replace("/ +/u", " ", $value);
   $value = preg_replace("/\n{2,}/", "\n\n", $value,-1);

   return trim($value);
}


/**
 * Convert a date YY-MM-DD to DD-MM-YY for calendar
 *
 * @param $time date: date to convert
 *
 * @return $time or $date
**/
function convDate($time) {

   if (is_null($time) || $time=='NULL') {
      return NULL;
   }

   if (!isset($_SESSION["glpidate_format"])) {
      $_SESSION["glpidate_format"] = 0;
   }

   switch ($_SESSION['glpidate_format']) {
      case 1 : // DD-MM-YYYY
         $date = substr($time, 8, 2)."-";  // day
         $date .= substr($time, 5, 2)."-"; // month
         $date .= substr($time, 0, 4);     // year
         return $date;

      case 2 : // MM-DD-YYYY
         $date = substr($time, 5, 2)."-";  // month
         $date .= substr($time, 8, 2)."-"; // day
         $date .= substr($time, 0, 4);     // year
         return $date;

      default : // YYYY-MM-DD
         if (strlen($time)>10) {
            return substr($time, 0, 10);
         }
         return $time;
   }
}


/**
 * Convert a date YY-MM-DD HH:MM to DD-MM-YY HH:MM for display in a html table
 *
 * @param $time datetime: datetime to convert
 *
 * @return $time or $date
**/
function convDateTime($time) {

   if (is_null($time) || $time=='NULL') {
      return NULL;
   }

   return convDate($time).' '. substr($time, 11, 5);
}


/**
 * Convert a number to correct display
 *
 * @param $number float: Number to display
 * @param $edit boolean: display number for edition ? (id edit use . in all case)
 * @param $forcedecimal integer: Force decimal number (do not use default value)
 *
 * @return formatted number
**/
function formatNumber($number, $edit=false, $forcedecimal=-1) {
   global $CFG_GLPI;

   // Php 5.3 : number_format() expects parameter 1 to be double,
   if ($number=="") {
      $number = 0;

   } else if ($number=="-") { // used for not defines value (from Infocom::Amort, p.e.)
      return "-";
   }

   $decimal = $CFG_GLPI["decimal_number"];
   if ($forcedecimal>=0) {
      $decimal = $forcedecimal;
   }

   // Edit : clean display for mysql
   if ($edit) {
      return number_format($number, $decimal, '.', '');
   }

   // Display : clean display
   switch ($_SESSION['glpinumber_format']) {
      case 2 : // Other French
         return str_replace(' ', '&nbsp;', number_format($number, $decimal, ', ', ' '));

      case 0 : // French
         return str_replace(' ', '&nbsp;', number_format($number, $decimal, '.', ' '));

      default: // English
         return number_format($number, $decimal, '.', ', ');
   }
}


/**
 * Send a file (not a document) to the navigator
 * See Document->send();
 *
 * @param $file string: storage filename
 * @param $filename string: file title
 *
 * @return nothing
**/
function sendFile($file, $filename) {

   // Test securite : document in DOC_DIR
   $tmpfile = str_replace(GLPI_DOC_DIR, "", $file);

   if (strstr($tmpfile,"../") || strstr($tmpfile,"..\\")) {
      Event::log($file, "sendFile", 1, "security",
                 $_SESSION["glpiname"]." try to get a non standard file.");
      die("Security attack !!!");
   }

   if (!file_exists($file)) {
      die("Error file $file does not exist");
   }

   $splitter = explode("/", $file);
   $mime     = "application/octetstream";

   if (preg_match('/\.(...)$/', $file, $regs)) {
      switch ($regs[1]) {
         case "sql" :
            $mime = "text/x-sql";
            break;

         case "xml" :
            $mime = "text/xml";
            break;

         case "csv" :
            $mime = "text/csv";
            break;

         case "svg" :
            $mime = "image/svg+xml";
            break;

         case "png" :
            $mime = "image/png";
            break;
      }
   }

   // Now send the file with header() magic
   header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
   header('Pragma: private'); /// IE BUG + SSL
   header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
   header("Content-disposition: filename='$filename'");
   header("Content-type: ".$mime);

   readfile($file) or die ("Error opening file $file");
}


/**
 * Convert a value in byte, kbyte, megabyte etc...
 *
 * @param $val string: config value (like 10k, 5M)
 *
 * @return $val
**/
function return_bytes_from_ini_vars($val) {

   $val  = trim($val);
   $last = utf8_strtolower($val{strlen($val)-1});

   switch($last) {
      // Le modifieur 'G' est disponible depuis PHP 5.1.0
      case 'g' :
         $val *= 1024;

      case 'm' :
         $val *= 1024;

      case 'k' :
         $val *= 1024;
   }

   return $val;
}


/**
 * Header redirection hack
 *
 * @param $dest string: Redirection destination
 * @return nothing
**/
function glpi_header($dest) {

   $toadd = '';
   if (!strpos($dest,"?")) {
      $toadd = '?tokonq='.getRandomString(5);
   }

   echo "<script language=javascript>
         NomNav = navigator.appName;
         if (NomNav=='Konqueror') {
            window.location='".$dest.$toadd."';
         } else {
            window.location='".$dest."';
         }
      </script>";
   exit();
}


/**
 * Call from a popup Windows, refresh the dropdown in main window
**/
function refreshDropdownPopupInMainWindow() {

   if (isset($_SESSION["glpipopup"]["rand"])) {
      echo "<script type='text/javascript' >\n";
      echo "window.opener.update_results_".$_SESSION["glpipopup"]["rand"]."();";
      echo "</script>";
   }
}


/**
 * Call from a popup Windows, refresh the dropdown in main window
**/
function refreshPopupMainWindow() {

   if (isset($_SESSION["glpipopup"]["rand"])) {
      echo "<script type='text/javascript' >\n";
      echo "window.opener.location.reload(true)";
      echo "</script>";
   }
}


/**
 * Call cron without time check
 *
 * @return boolean : true if launched
**/
function callCronForce() {
   global $CFG_GLPI;

   $path = $CFG_GLPI['root_doc']."/front/cron.php";

   echo "<div style=\"background-image: url('$path');\"></div>";
   return true;
}


/**
 * Call cron if time since last launch elapsed
 *
 * @return nothing
**/
function callCron() {

   if (isset($_SESSION["glpicrontimer"])) {
      // call function callcron() every 5min
      if ((time()-$_SESSION["glpicrontimer"])>300) {

         if (callCronForce()) {
            // Restart timer
            $_SESSION["glpicrontimer"] = time();
         }
      }

   } else {
      // Start timer
      $_SESSION["glpicrontimer"] = time();
   }
}


/**
 * Get hour from sql
 *
 * @param $time datetime: time
 *
 * @return  array
**/
function get_hour_from_sql($time) {

   $t = explode(" ", $time);
   $p = explode(":", $t[1]);

   return $p[0].":".$p[1];
}


/**
 *  Optimize sql table
 *
 * @param $progress_fct function to call to display progress message
 *
 * @return number of tables
**/
function optimize_tables ($progress_fct=NULL) {
   global $DB;

   if (function_exists($progress_fct)) {
      $progress_fct("optimize"); // Start
   }
   $result = $DB->list_tables("glpi_%");
   $nb     = 0;

   while ($line = $DB->fetch_array($result)) {
      $table = $line[0];

      if (function_exists($progress_fct)) {
         $progress_fct("optimize", $table);
      }

      $query = "OPTIMIZE TABLE `".$table."` ;";
      $DB->query($query);
      $nb++;
   }
   $DB->free_result($result);

   if (function_exists($progress_fct)) {
      $progress_fct("optimize"); // End
   }

   return $nb;
}


/**
 * Is a string seems to be UTF-8 one ?
 *
 * @param $str string: string to analyze
 *
 * @return  boolean
**/
function seems_utf8($str) {
   return mb_check_encoding($str, "UTF-8");
}


/**
 * NOT USED IN CORE - Used for update process - Replace bbcode in text by html tag
 * used in update_065_068.php
 *
 * @param $string string: initial string
 *
 * @return formatted string
**/
function rembo($string) {

   // Adapte de PunBB
   //Copyright (C)  Rickard Andersson (rickard@punbb.org)

   // If the message contains a code tag we have to split it up
   // (text within [code][/code] shouldn't be touched)
   if (strpos($string, '[code]') !== false && strpos($string, '[/code]') !== false) {
      list($inside, $outside) = split_text($string, '[code]', '[/code]');
      $outside = array_map('trim', $outside);
      $string  = implode('<">', $outside);
   }

   $pattern = array('#\[b\](.*?)\[/b\]#s',
                    '#\[i\](.*?)\[/i\]#s',
                    '#\[u\](.*?)\[/u\]#s',
                    '#\[s\](.*?)\[/s\]#s',
                    '#\[c\](.*?)\[/c\]#s',
                    '#\[g\](.*?)\[/g\]#s',
                    '#\[email\](.*?)\[/email\]#',
                    '#\[email=(.*?)\](.*?)\[/email\]#',
                    '#\[color=([a-zA-Z]*|\#?[0-9a-fA-F]{6})](.*?)\[/color\]#s');

   $replace = array('<strong>$1</strong>',
                    '<em>$1</em>',
                    '<span class="souligne">$1</span>',
                    '<span class="barre">$1</span>',
                    '<div class="center">$1</div>',
                    '<big>$1</big>',
                    '<a href="mailto:$1">$1</a>',
                    '<a href="mailto:$1">$2</a>',
                    '<span style="color: $1">$2</span>');

   // This thing takes a while! :)
   $string = preg_replace($pattern, $replace, $string);

   $string = clicurl($string);
   $string = autop($string);

   // If we split up the message before we have to concatenate it together again (code tags)
   if (isset($inside)) {
      $outside    = explode('<">', $string);
      $string     = '';
      $num_tokens = count($outside);

      for ($i = 0 ; $i < $num_tokens ; ++$i) {
         $string .= $outside[$i];
         if (isset($inside[$i])) {
            $string .= '<br><br><div class="spaced"><table class="code center"><tr>' .
                       '<td class="punquote"><strong>Code:</strong><br><br><pre>'.
                       trim($inside[$i]).'</pre></td></tr></table></div>';
         }
      }
   }
   return $string;
}


/**
 * Create SQL search condition
 *
 * @param $val string: value to search
 * @param $not boolean: is a negative search ?
 *
 * @return search string
**/
function makeTextSearch($val, $not=false) {

   $NOT = "";
   if ($not) {
      $NOT = "NOT";
   }

   // Unclean to permit < and > search
   $val = unclean_cross_side_scripting_deep($val);

   if ($val=='NULL' || $val=='null') {
      $SEARCH = " IS $NOT NULL ";

   } else {
      $begin = 0;
      $end   = 0;
      if (($length=strlen($val))>0) {
         if (($val[0]=='^')) {
            $begin = 1;
         }

         if ($val[$length-1]=='$') {
            $end = 1;
         }
      }

      if ($begin || $end) {
         // no utf8_substr, to be consistent with strlen result
         $val = substr($val, $begin, $length-$end-$begin);
      }

      $SEARCH = " $NOT LIKE '".(!$begin?"%":"").$val.(!$end?"%":"")."' ";
   }
   return $SEARCH;
}


/**
 * Create SQL search condition
 *
 * @param $field name (should be ` protected)
 * @param $val string: value to search
 * @param $not boolean: is a negative search ?
 * @param $link with previous criteria
 *
 * @return search SQL string
**/
function makeTextCriteria ($field, $val, $not=false, $link='AND') {

   $sql = $field . makeTextSearch($val, $not);

   if (($not && $val!='NULL' && $val!='null' && $val!='^$')    // Not something
       ||(!$not && $val=='^$')) {   // Empty
      $sql = "($sql OR $field IS NULL)";
   }
   return " $link $sql ";
}


/**
 * Get a web page. Use proxy if configured
 *
 * @param $url string: to retrieve
 * @param $msgerr string: set if problem encountered
 * @param $rec integer: internal use only Must be 0
 *
 * @return content of the page (or empty)
**/
function getURLContent ($url, &$msgerr=NULL, $rec=0) {
   global $LANG, $CFG_GLPI;

   $content = "";
   $taburl  = parse_url($url);

   // Connection directe
   if (empty($CFG_GLPI["proxy_name"])) {
      if ($fp=@fsockopen($taburl["host"], (isset($taburl["port"]) ? $taburl["port"] : 80),
                         $errno, $errstr, 1)) {

         if (isset($taburl["path"]) && $taburl["path"]!='/') {
            // retrieve path + args
            $request = "GET ".strstr($url, $taburl["path"])." HTTP/1.1\r\n";
         } else {
            $request = "GET / HTTP/1.1\r\n";
         }

         $request .= "Host: ".$taburl["host"]."\r\n";

      } else {
         if (isset($msgerr)) {
            $msgerr = $LANG['setup'][304] . " ($errstr)"; // failed direct connexion - try proxy
         }
         return "";
      }

   } else { // Connection using proxy
      $fp = fsockopen($CFG_GLPI["proxy_name"], $CFG_GLPI["proxy_port"], $errno, $errstr, 1);

      if ($fp) {
         $request  = "GET $url HTTP/1.1\r\n";
         $request .= "Host: ".$taburl["host"]."\r\n";
         if (!empty($CFG_GLPI["proxy_user"])) {
            $request .= "Proxy-Authorization: Basic " . base64_encode ($CFG_GLPI["proxy_user"].":".
                        $CFG_GLPI["proxy_password"]) . "\r\n";
         }

      } else {
         if (isset($msgerr)) {
            $msgerr = $LANG['setup'][311] . " ($errstr)"; // failed proxy connexion
         }
         return "";
      }
   }

   $request .= "User-Agent: GLPI/".trim($CFG_GLPI["version"])."\r\n";
   $request .= "Connection: Close\r\n\r\n";
   fwrite($fp, $request);

   $header = true ;
   $redir  = false;
   $errstr = "";
   while (!feof($fp)) {
      if ($buf=fgets($fp, 1024)) {
         if ($header) {

            if (strlen(trim($buf))==0) {
               // Empty line = end of header
               $header = false;

            } else if ($redir && preg_match("/^Location: (.*)$/", $buf, $rep)) {
               if ($rec<9) {
                  $desturl = trim($rep[1]);
                  $taburl2 = parse_url($desturl);

                  if (isset($taburl2['host'])) {
                     // Redirect to another host
                     return (getURLContent($desturl, $errstr, $rec+1));
                  }

                  // redirect to same host
                  return (getURLContent((isset($taburl['scheme'])?$taburl['scheme']:'http').
                                        "://".$taburl['host'].
                                        (isset($taburl['port'])?':'.$taburl['port']:'').
                                        $desturl, $errstr, $rec+1));
               }

               $errstr = "Too deep";
               break;

            } else if (preg_match("/^HTTP.*302/", $buf)) {
               // HTTP 302 = Moved Temporarily
               $redir = true;

            } else if (preg_match("/^HTTP/", $buf)) {
               // Other HTTP status = error
               $errstr = trim($buf);
               break;
            }

         } else {
            // Body
            $content .= $buf;
         }
      }
   } // eof

   fclose($fp);

   if (empty($content) && isset($msgerr)) {
      if (empty($errstr)) {
         $msgerr = $LANG['setup'][312]; // no data
      } else {
         $msgerr = $LANG['setup'][310] . " ($errstr)"; // HTTP error
      }
   }
   return $content;
}


/**
 * Check if new version is available
 *
 * @param $auto boolean: check done autically ? (if not display result)
 * @param $messageafterredirect boolean: use message after redirect instead of display
 *
 * @return string explaining the result
**/
function checkNewVersionAvailable($auto=true, $messageafterredirect=false) {
   global $LANG, $CFG_GLPI;

   if (!$auto && !haveRight("check_update","r")) {
      return false;
   }

   if (!$auto && !$messageafterredirect) {
      echo "<br>";
   }

   $error = "";
   $latest_version = getURLContent("http://glpi-project.org/latest_version", $error);

   if (strlen(trim($latest_version))==0) {

      if (!$auto) {

         if ($messageafterredirect) {
            addMessageAfterRedirect($error, true, ERROR);
         } else {
            echo "<div class='center'>$error</div>";
         }

      } else {
         return $error;
      }

   } else {
      $splitted = explode(".", trim($CFG_GLPI["version"]));

      if ($splitted[0]<10) {
         $splitted[0] .= "0";
      }

      if ($splitted[1]<10) {
         $splitted[1] .= "0";
      }

      $cur_version = $splitted[0]*10000+$splitted[1]*100;

      if (isset($splitted[2])) {
         if ($splitted[2]<10) {
            $splitted[2] .= "0";
         }
         $cur_version += $splitted[2];
      }

      $splitted = explode(".", trim($latest_version));

      if ($splitted[0]<10) {
         $splitted[0] .= "0";
      }

      if ($splitted[1]<10) {
         $splitted[1] .= "0";
      }

      $lat_version = $splitted[0]*10000+$splitted[1]*100;

      if (isset($splitted[2])) {
         if ($splitted[2]<10) {
            $splitted[2] .= "0";
         }
         $lat_version += $splitted[2];
      }

      if ($cur_version < $lat_version) {
         $config_object = new Config();
         $input["id"]   = 1;
         $input["founded_new_version"] = $latest_version;
         $config_object->update($input);

         if (!$auto) {
            if ($messageafterredirect) {
               addMessageAfterRedirect($LANG['setup'][301]." ".$latest_version.
                                       $LANG['setup'][302]);

            } else {
               echo "<div class='center'>".$LANG['setup'][301]." ".$latest_version."</div>";
               echo "<div class='center'>".$LANG['setup'][302]."</div>";
            }

         } else {
            if ($messageafterredirect) {
               addMessageAfterRedirect($LANG['setup'][301]." ".$latest_version);
            } else {
               return $LANG['setup'][301]." ".$latest_version;
            }
         }

      } else {
         if (!$auto) {
            if ($messageafterredirect) {
               addMessageAfterRedirect($LANG['setup'][303]);
            } else {
               echo "<div class='center'>".$LANG['setup'][303]."</div>";
            }

         } else {
            if ($messageafterredirect) {
               addMessageAfterRedirect($LANG['setup'][303]);
            } else {
               return $LANG['setup'][303];
            }
         }
      }
   }
   return 1;
}


/**
 * Get date using a begin date and a period in month
 *
 * @param $from date: begin date
 * @param $addwarranty integer: period in months
 * @param $deletenotice integer: period in months of notice
 *
 * @return expiration date string
**/
function getWarrantyExpir($from, $addwarranty, $deletenotice=0) {
   global $LANG;

   // Life warranty
   if ($addwarranty==-1 && $deletenotice==0) {
      return $LANG['setup'][307];
   }

   if ($from==NULL || empty($from)) {
      return "";
   }

   return convDate(date("Y-m-d", strtotime("$from+$addwarranty month -$deletenotice month")));
}


/**
 * Get date using a begin date and a period in month and a notice one
 *
 * @param $begin date: begin date
 * @param $duration integer: period in months
 * @param $notice integer: notice in months
 *
 * @return expiration string
**/
function getExpir($begin, $duration, $notice="0") {
   global $LANG;

   if ($begin==NULL || empty($begin)) {
      return "";
   }

   $diff      = strtotime("$begin+$duration month -$notice month")-time();
   $diff_days = floor($diff/60/60/24);

   if ($diff_days>0) {
      return $diff_days." ".$LANG['stats'][31];
   }

   return "<span class='red'>".$diff_days." ".$LANG['stats'][31]."</span>";
}


/**
 * Manage login redirection
 *
 * @param $where string: where to redirect ?
**/
function manageRedirect($where) {
   global $CFG_GLPI, $PLUGIN_HOOKS;

   if (!empty($where)) {
      $data = explode("_",$where);

      if (count($data)>=2
          && isset($_SESSION["glpiactiveprofile"]["interface"])
          && !empty($_SESSION["glpiactiveprofile"]["interface"])) {

         $forcetab = '';
         if (isset($data[2])) {
            $forcetab = 'forcetab='.$data[2];
         }

         switch ($_SESSION["glpiactiveprofile"]["interface"]) {
            case "helpdesk" :
               switch ($data[0]) {
                  case "plugin" :
                     if (isset($data[3])) {
                        $forcetab = 'forcetab='.$data[3];
                     }
                     if (isset($data[2])
                         && $data[2]>0
                         && isset($PLUGIN_HOOKS['redirect_page'][$data[1]])
                         && !empty($PLUGIN_HOOKS['redirect_page'][$data[1]])) {

                        glpi_header($CFG_GLPI["root_doc"]."/plugins/".$data[1]."/".
                                    $PLUGIN_HOOKS['redirect_page'][$data[1]]."?id=".$data[2].
                                    "&$forcetab");
                     } else {
                        glpi_header($CFG_GLPI["root_doc"]."/front/helpdesk.public.php?$forcetab");
                     }
                     break;

                  // Use for compatibility with old name
                  case "tracking" :
                  case "ticket" :
                     glpi_header($CFG_GLPI["root_doc"]."/front/ticket.form.php?id=".$data[1].
                                 "&$forcetab");
                     break;

                  case "preference" :
                     glpi_header($CFG_GLPI["root_doc"]."/front/preference.php?$forcetab");
                     break;

                  default :
                     glpi_header($CFG_GLPI["root_doc"]."/front/helpdesk.public.php?$forcetab");
                     break;
               }
               break;

            case "central" :
               switch ($data[0]) {
                  case "plugin" :
                     if (isset($data[3])) {
                        $forcetab = 'forcetab='.$data[3];
                     }
                     if (isset($data[2])
                         && $data[2]>0
                         && isset($PLUGIN_HOOKS['redirect_page'][$data[1]])
                         && !empty($PLUGIN_HOOKS['redirect_page'][$data[1]])) {

                        glpi_header($CFG_GLPI["root_doc"]."/plugins/".$data[1]."/".
                                    $PLUGIN_HOOKS['redirect_page'][$data[1]]."?id=".$data[2].
                                    "&$forcetab");
                     } else {
                        glpi_header($CFG_GLPI["root_doc"]."/front/central.php?$forcetab");
                     }
                     break;

                  case "preference" :
                     glpi_header($CFG_GLPI["root_doc"]."/front/preference.php?$forcetab");
                     break;

                  // Use for compatibility with old name
                  case "tracking" :
                     $data[0] = "ticket";

                  default :
                     if (!empty($data[0] )&& $data[1]>0) {
                        glpi_header($CFG_GLPI["root_doc"]."/front/".$data[0].".form.php?id=".
                                    $data[1]."&$forcetab");
                     } else {
                        glpi_header($CFG_GLPI["root_doc"]."/front/central.php?$forcetab");
                     }
                     break;
               }
               break;
         }
      }
   }
}


/**
 * Clean string for input text field
 *
 * @param $string string: input text
 *
 * @return clean string
**/
function cleanInputText($string) {
   return preg_replace('/\"/', '&quot;', $string);
}


/**
 * Get a random string
 *
 * @param $length integer: length of the random string
 *
 * @return random string
**/
function getRandomString($length) {

   $alphabet  = "1234567890abcdefghijklmnopqrstuvwxyz";
   $rndstring = "";

   for ($a=0 ; $a<=$length ; $a++) {
      $b = rand(0, strlen($alphabet) - 1);
      $rndstring .= $alphabet[$b];
   }
   return $rndstring;
}


/**
 * Make a good string from the unix timestamp $sec
 *
 * @param $time integer: timestamp
 * @param $display_sec boolean: display seconds ?
 *
 * @return string
**/
function timestampToString($time, $display_sec=true) {
   global $LANG;

   $sign = '';
   if ($time<0) {
      $sign = '- ';
      $time = abs($time);
   }
   $time = floor($time);

   // Force display seconds if time is null
   if ($time==0) {
      $display_sec = true;
   }

   $units = getTimestampTimeUnits($time);
   $out   = $sign;

   if ($units['day']>0) {
      $out .= " ".$units['day']."&nbsp;".$LANG['stats'][31];
   }

   if ($units['hour']>0) {
      $out .= " ".$units['hour']."&nbsp;".$LANG['job'][21];
   }

   if ($units['minute']>0) {
      $out .= " ".$units['minute']."&nbsp;".$LANG['stats'][33];
   }

   if ($display_sec) {
      $out.=" ".$units['second']."&nbsp;".$LANG['stats'][34];
   }

   return $out;
}


/**
 * Split timestamp in time units
 *
 * @param $time integer: timestamp
 *
 * @return string
**/
function getTimestampTimeUnits($time) {

   $time = abs($time);
   $out['second'] = 0;
   $out['minute'] = 0;
   $out['hour']   = 0;
   $out['day']    = 0;

   $out['second'] = $time%MINUTE_TIMESTAMP;
   $time -= $out['second'];

   if ($time>0) {
      $out['minute'] = ($time%HOUR_TIMESTAMP)/MINUTE_TIMESTAMP;
      $time -= $out['minute']*MINUTE_TIMESTAMP;

      if ($time>0) {
         $out['hour'] = ($time%DAY_TIMESTAMP)/HOUR_TIMESTAMP;
         $time -= $out['hour']*HOUR_TIMESTAMP;

         if ($time>0) {
            $out['day'] = $time/DAY_TIMESTAMP;
         }
      }
   }
   return $out;
}


/**
 * Delete a directory and file contains in it
 *
 * @param $dir string: directory to delete
**/
function deleteDir($dir) {

   if (file_exists($dir)) {
      chmod($dir, 0777);

      if (is_dir($dir)) {
         $id_dir = opendir($dir);
         while ($element = readdir($id_dir)) {
            if ($element != "." && $element != "..") {

               if (is_dir($element)) {
                  deleteDir($dir."/".$element);
               } else {
                  unlink($dir."/".$element);
               }

            }
         }
         closedir($id_dir);
         rmdir($dir);

      } else { // Delete file
         unlink($dir);
      }
   }
}


/**
 * Determine if a login is valid
 *
 * @param $login string: login to check
 *
 * @return boolean
**/
function isValidLogin($login="") {
   return preg_match( "/^[[:alnum:]@.\-_ ]+$/i", $login);
}


/**
 * Determine if Ldap is usable checking ldap extension existence
 *
 * @return boolean
**/
function canUseLdap() {
   return extension_loaded('ldap');
}


/**
 * Determine if Imap/Pop is usable checking extension existence
 *
 * @return boolean
**/
function canUseImapPop() {
   return extension_loaded('imap');
}


/** Converts an array of parameters into a query string to be appended to a URL.
 *
 * @param $array  array: parameters to append to the query string.
 * @param $separator separator : default is & : may be defined as &amp; to display purpose
 * @param $parent This should be left blank (it is used internally by the function).
 *
 * @return string  : Query string to append to a URL.
**/
function append_params($array, $separator='&', $parent='') {

   $params = array();
   foreach ($array as $k => $v) {

      if (is_array($v)) {
         $params[] = append_params($v, $separator, (empty($parent) ? rawurlencode($k) : $parent .
                                                    '[' .rawurlencode($k) . ']'));
      } else {
         $params[] = (!empty($parent) ? $parent . '[' . rawurlencode($k) . ']'
                                      : rawurlencode($k)) . '=' . rawurlencode($v);
      }

   }
   return implode($separator, $params);
}


/** Format a size passing a size in octet
 *
 * @param   $size integer: Size in octet
 *
 * @return  formatted size
**/
function getSize($size) {

   $bytes = array('B', 'KB', 'MB', 'GB', 'TB');
   foreach ($bytes as $val) {
      if ($size > 1024) {
         $size = $size / 1024;
      } else {
         break;
      }
   }
   return round($size, 2)." ".$val;
}


/** Display how many logins since
 *
 * @return  nothing
**/
function getCountLogin() {
   global $DB;

   $query = "SELECT count(*)
             FROM `glpi_events`
             WHERE `message` LIKE '%logged in%'";

   $query2 = "SELECT `date`
              FROM `glpi_events`
              ORDER BY `date` ASC
              LIMIT 1";

   $result   = $DB->query($query);
   $result2  = $DB->query($query2);
   $nb_login = $DB->result($result, 0, 0);
   $date     = $DB->result($result2, 0, 0);

   echo '<b>'.$nb_login.'</b> logins since '.$date ;
}


/** Initialise a list of items to use navigate through search results
 *
 * @param $itemtype device type
 * @param $title titre de la liste
**/
function initNavigateListItems($itemtype, $title="") {
   global $LANG;

   if (empty($title)) {
      $title = $LANG['common'][53];
   }
   $url = '';

   if (!isset($_SERVER['REQUEST_URI']) || strpos($_SERVER['REQUEST_URI'],"tabs")>0) {
      if (isset($_SERVER['HTTP_REFERER'])) {
         $url = $_SERVER['HTTP_REFERER'];
      }

   } else {
      $url = $_SERVER['REQUEST_URI'];
   }

   $_SESSION['glpilisttitle'][$itemtype] = $title;
   $_SESSION['glpilistitems'][$itemtype] = array();
   $_SESSION['glpilisturl'][$itemtype]   = $url;
}


/** Add an item to the navigate through search results list
 *
 * @param $itemtype device type
 * @param $ID ID of the item
**/
function addToNavigateListItems($itemtype, $ID) {
   $_SESSION['glpilistitems'][$itemtype][] = $ID;
}


/**
 * Clean display value for csv export
 *
 * @param $value string value
 *
 * @return clean value
**/
function csv_clean($value) {

   if (get_magic_quotes_runtime()) {
      $value = stripslashes($value);
   }

   $value = str_replace("\"", "''", $value);
   $value = html_clean($value);

   return $value;
}


/**
 * Extract url from web link
 *
 * @param $value string value
 *
 * @return clean value
**/
function weblink_extract($value) {

   $value = preg_replace('/<a\s+href\="([^"]+)"[^>]*>[^<]*<\/a>/i', "$1", $value);
   return $value;
}


/**
 * Clean display value for sylk export
 *
 * @param $value string value
 *
 * @return clean value
**/
function sylk_clean($value) {

   if (get_magic_quotes_runtime()) {
      $value = stripslashes($value);
   }

   $value = preg_replace('/\x0A/', ' ', $value);
   $value = preg_replace('/\x0D/', NULL, $value);
   $value = str_replace("\"", "''", $value);
   $value = str_replace(';', ';;', $value);
   $value = html_clean($value);

   return $value;
}


/**
 * Clean all parameters of an URL. Get a clean URL
**/
function cleanParametersURL($url) {

   $url = preg_replace("/(\/[0-9a-zA-Z\.\-\_]+\.php).*/", "$1", $url);
   return preg_replace("/\?.*/", "", $url);
}


/**
 * Manage planning posted datas (must have begin + duration or end)
 * Compute end if duration is set
**/
function manageBeginAndEndPlanDates(&$data) {

   if (!isset($data['end'])) {
      if (isset($data['begin']) && isset($data['_duration'])) {
         $begin_timestamp = strtotime($data['begin']);
         $data['end']     = date("Y-m-d H:i:s", $begin_timestamp+$data['_duration']);
         unset($data['_duration']);
      }
   }
}

?>
