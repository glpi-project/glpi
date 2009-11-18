<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2009 by the INDEPNET Development Team.

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


if (!defined('GLPI_ROOT')){
   die("Sorry. You can't access directly to this file");
}

//*************************************************************************************************
//*************************************************************************************************
//********************************  Fonctions diverses ********************************************
//*************************************************************************************************
//*************************************************************************************************

/**
* Set the directory where are store the session file
*
**/
function setGlpiSessionPath() {

   if (ini_get("session.save_handler")=="files") {
      session_save_path(GLPI_SESSION_DIR);
   }
}

/**
* Start the GLPI php session
*
**/
function startGlpiSession() {

   if(!session_id()) {
      @session_start();
   }
   // Define current time for sync of action timing
   $_SESSION["glpi_currenttime"]=date("Y-m-d H:i:s");
}

/**
* Is GLPI used in mutli-entities mode ?
*@return boolean
*
**/
function isMultiEntitiesMode() {

   if (!isset($_SESSION['glpi_multientitiesmode'])) {
      if (countElementsInTable("glpi_entities")>0) {
         $_SESSION['glpi_multientitiesmode']=1;
      } else {
         $_SESSION['glpi_multientitiesmode']=0;
      }
   }
   return $_SESSION['glpi_multientitiesmode'];
}

/**
* Is the user have right to see all entities ?
* @return boolean
*
**/
function isViewAllEntities() {
   return ((countElementsInTable("glpi_entities")+1)==count($_SESSION["glpiactiveentities"]));
}

/**
* Log a message in log file
* @param $name string: name of the log file
* @param $text string: text to log
* @param $force boolean: force log in file not seeing use_log_in_files config
*
**/
function logInFile($name,$text,$force=false) {
   global $CFG_GLPI;

   if (isset($CFG_GLPI["use_log_in_files"]) && $CFG_GLPI["use_log_in_files"]||$force) {
      error_log(convDateTime(date("Y-m-d H:i:s"))."\n".$text,3,GLPI_LOG_DIR."/".$name.".log");
   }
}

/**
* Specific error handler in Normal mode
* @param $errno integer: level of the error raised.
* @param $errmsg string: error message.
* @param $filename string: filename that the error was raised in.
* @param $linenum integer: line number the error was raised at.
* @param $vars array: that points to the active symbol table at the point the error occurred.
*
**/
function userErrorHandlerNormal($errno, $errmsg, $filename, $linenum, $vars) {
   global $CFG_GLPI;

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
                       8192 /* E_DEPRECATED */ => 'Deprecated function',
                       16384 /* E_USER_DEPRECATED */ => 'User deprecated function');
   // Les niveaux qui seront enregistrés
   $user_errors = array(E_USER_ERROR,
                        E_USER_WARNING,
                        E_USER_NOTICE);

   $err = $errortype[$errno] . "($errno): $errmsg\n";
   if (in_array($errno, $user_errors)) {
      $err .= "Variables:".wddx_serialize_value($vars,"Variables")."\n";
   }
   if (function_exists("debug_backtrace")) {
      $err .= "Backtrace :\n";
      $traces=debug_backtrace();
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
   logInFile("php-errors",$err."\n");

   return $errortype[$errno];
}

/**
* Specific error handler in Debug mode
* @param $errno integer: level of the error raised.
* @param $errmsg string: error message.
* @param $filename string: filename that the error was raised in.
* @param $linenum integer: line number the error was raised at.
* @param $vars array: that points to the active symbol table at the point the error occurred.
*
**/
function userErrorHandlerDebug($errno, $errmsg, $filename, $linenum, $vars) {
   global $CFG_GLPI;

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
* @return boolean
*
**/
function isCommandLine() {
   return (!isset($_SERVER["SERVER_NAME"]));
}

/**
* Encode string to UTF-8
* @param $string string: string to convert
* @param $from_charset string: original charset (if 'auto' try to autodetect)
*
* @return utf8 string
**/
function encodeInUtf8($string,$from_charset="ISO-8859-1") {

   if (strcmp($from_charset,"auto")==0) {
      $from_charset=mb_detect_encoding($string);
   }
   return mb_convert_encoding($string,"UTF-8",$from_charset);
}

/**
* Decode string from UTF-8 to specified charset
* @param $string string: string to convert
* @param $to_charset string: destination charset (default is ISO-8859-1)
*
* @return converted string
**/
function decodeFromUtf8($string,$to_charset="ISO-8859-1") {
   return mb_convert_encoding($string,$to_charset,"UTF-8");
}

/**
* substr function for utf8 string
* @param $str string: string
* @param $start integer: start of the result substring
* @param $length integer: The maximum length of the returned string if > 0
*
* @return substring
**/
function utf8_substr($str,$start,$length=-1) {

   if ($length==-1) {
      $length=utf8_strlen($str)-$start;
   }
   return mb_substr($str,$start,$length,"UTF-8");
}

/**
* strtolower function for utf8 string
* @param $str string: string
*
* @return lower case string
**/
function utf8_strtolower($str) {

   return mb_strtolower($str,"UTF-8");
}

/**
* strtoupper function for utf8 string
* @param $str string: string
*
* @return upper case string
**/
function utf8_strtoupper($str) {

   return mb_strtoupper($str,"UTF-8");
}

/**
* substr function for utf8 string
* @param $str string: string
* @param $tofound string: string to found
* @param $offset integer: The search offset. If it is not specified, 0 is used.
*
* @return substring
**/
function utf8_strpos($str,$tofound,$offset=0) {
   return mb_strpos($str,$tofound,$offset,"UTF-8");
}

/**
* strlen function for utf8 string
* @param $str string: string
* @return length of the string
**/
function utf8_strlen($str) {
   return mb_strlen($str,"UTF-8");
}

/** Returns the utf string corresponding to the unicode value (from php.net, courtesy - romans@void.lv)
* @param $num integer: character code
*/
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
 * Clean log cron function
 *
 * @param $task instance of CronTask
 *
 **/
function cron_logs($task) {
   global $CFG_GLPI,$DB;

   $vol = 0;

   // Expire Event Log
   if ($task->fields['param'] > 0) {
      $secs = $task->fields['param'] * DAY_TIMESTAMP;

      $query_exp = "DELETE
                    FROM `glpi_events`
                    WHERE UNIX_TIMESTAMP(date) < UNIX_TIMESTAMP()-$secs";

      $DB->query($query_exp);
      $vol += $DB->affected_rows();
   }

   foreach ($DB->request('glpi_crontasks') as $data) {
      if ($data['logs_lifetime']>0) {
         $secs = $data['logs_lifetime'] * DAY_TIMESTAMP;

         $query_exp = "DELETE
                    FROM `glpi_crontaskslogs`
                    WHERE `crontasks_id`='".$data['id']."'
                      AND UNIX_TIMESTAMP(date) < UNIX_TIMESTAMP()-$secs";

         $DB->query($query_exp);
         $vol += $DB->affected_rows();
      }
   }
   $task->setVolume($vol);
   return ($vol>0 ? 1 : 0);
}

/**
* Clean log cron function
*
* @param $task for log
*
**/
function cron_optimize($task=NULL) {
   global $CFG_GLPI,$DB;

   $nb = optimize_tables();

   if ($task) {
      $task->setVolume($nb);
   }
   return 1;
}

/**
 * Garbage collector for expired file session
 *
 * @param $task for log
 *
 **/
function cron_session($task) {
   global $CFG_GLPI;

   // max time to keep the file session
   $maxlifetime = session_cache_expire();
   $nb=0;
   foreach (glob(GLPI_SESSION_DIR."/sess_*") as $filename) {
      if (filemtime($filename) + $maxlifetime < time()) {
         // Delete session file if not delete before
         if (@unlink($filename)) {
            $nb++;
         }
      }
   }
   $task->setVolume($nb);
   if ($nb) {
      $task->log("Clean $nb session file(s) created since more than $maxlifetime seconds\n");
      return 1;
   }
   return 0;
}

/**
 * Get the filesize of a complete directory (from php.net)
 *
 * @param $path string: directory or file to get size
 * @return size of the $path
 **/
function filesizeDirectory($path) {

   if(!is_dir($path)) {
      return filesize($path);
   }
   if ($handle = opendir($path)) {
      $size = 0;
      while (false !== ($file = readdir($handle))) {
         if($file!='.' && $file!='..') {
            $size += filesize($path.'/'.$file);
            $size += filesizeDirectory($path.'/'.$file);
         }
      }
      closedir($handle);
      return $size;
   }
}

/**
 * Get the SEARCH_OPTION array
 *
 * @param $itemtype
 *
 * @return the reference to  array of search options for the given item type
 **/
function &getSearchOptions($itemtype) {
   global $LANG, $CFG_GLPI;

   static $search = array();

   if (!isset($search[$itemtype])) {

      // Pseudo type first
      if ($itemtype==RESERVATION_TYPE) {

         $search[RESERVATION_TYPE][4]['table']     = 'glpi_reservationsitems';
         $search[RESERVATION_TYPE][4]['field']     = 'comment';
         $search[RESERVATION_TYPE][4]['linkfield'] = 'comment';
         $search[RESERVATION_TYPE][4]['name']      = $LANG['common'][25];
         $search[RESERVATION_TYPE][4]['datatype']  = 'text';

         $search[RESERVATION_TYPE]['common'] = $LANG['common'][32];

         $search[RESERVATION_TYPE][1]['table']     = 'reservation_types';
         $search[RESERVATION_TYPE][1]['field']     = 'name';
         $search[RESERVATION_TYPE][1]['linkfield'] = 'name';
         $search[RESERVATION_TYPE][1]['name']      = $LANG['common'][16];
         $search[RESERVATION_TYPE][1]['datatype']  = 'itemlink';

         $search[RESERVATION_TYPE][2]['table']     = 'reservation_types';
         $search[RESERVATION_TYPE][2]['field']     = 'id';
         $search[RESERVATION_TYPE][2]['linkfield'] = 'id';
         $search[RESERVATION_TYPE][2]['name']      = $LANG['common'][2];

         $search[RESERVATION_TYPE][3]['table']     = 'glpi_locations';
         $search[RESERVATION_TYPE][3]['field']     = 'completename';
         $search[RESERVATION_TYPE][3]['linkfield'] = 'locations_id';
         $search[RESERVATION_TYPE][3]['name']      = $LANG['common'][15];

         $search[RESERVATION_TYPE][16]['table']     = 'reservation_types';
         $search[RESERVATION_TYPE][16]['field']     = 'comment';
         $search[RESERVATION_TYPE][16]['linkfield'] = 'comment';
         $search[RESERVATION_TYPE][16]['name']      = $LANG['common'][25];
         $search[RESERVATION_TYPE][16]['datatype']  = 'text';

         $search[RESERVATION_TYPE][70]['table']     = 'glpi_users';
         $search[RESERVATION_TYPE][70]['field']     = 'name';
         $search[RESERVATION_TYPE][70]['linkfield'] = 'users_id';
         $search[RESERVATION_TYPE][70]['name']      = $LANG['common'][34];

         $search[RESERVATION_TYPE][71]['table']     = 'glpi_groups';
         $search[RESERVATION_TYPE][71]['field']     = 'name';
         $search[RESERVATION_TYPE][71]['linkfield'] = 'groups_id';
         $search[RESERVATION_TYPE][71]['name']      = $LANG['common'][35];

         $search[RESERVATION_TYPE][19]['table']     = 'reservation_types';
         $search[RESERVATION_TYPE][19]['field']     = 'date_mod';
         $search[RESERVATION_TYPE][19]['linkfield'] = '';
         $search[RESERVATION_TYPE][19]['name']      = $LANG['common'][26];
         $search[RESERVATION_TYPE][19]['datatype']  = 'datetime';

         $search[RESERVATION_TYPE][23]['table']     = 'glpi_manufacturers';
         $search[RESERVATION_TYPE][23]['field']     = 'name';
         $search[RESERVATION_TYPE][23]['linkfield'] = 'manufacturers_id';
         $search[RESERVATION_TYPE][23]['name']      = $LANG['common'][5];

         $search[RESERVATION_TYPE][24]['table']     = 'glpi_users';
         $search[RESERVATION_TYPE][24]['field']     = 'name';
         $search[RESERVATION_TYPE][24]['linkfield'] = 'users_id_tech';
         $search[RESERVATION_TYPE][24]['name']      = $LANG['common'][10];

         $search[RESERVATION_TYPE][80]['table']     = 'glpi_entities';
         $search[RESERVATION_TYPE][80]['field']     = 'completename';
         $search[RESERVATION_TYPE][80]['linkfield'] = 'entities_id';
         $search[RESERVATION_TYPE][80]['name']      = $LANG['entity'][0];
      } else if ($itemtype==STATE_TYPE) {
         $search[STATE_TYPE]['common'] = $LANG['common'][32];

         $search[STATE_TYPE][1]['table']     = 'state_types';
         $search[STATE_TYPE][1]['field']     = 'name';
         $search[STATE_TYPE][1]['linkfield'] = 'name';
         $search[STATE_TYPE][1]['name']      = $LANG['common'][16];
         $search[STATE_TYPE][1]['datatype']  = 'itemlink';

         $search[STATE_TYPE][2]['table']     = 'state_types';
         $search[STATE_TYPE][2]['field']     = 'id';
         $search[STATE_TYPE][2]['linkfield'] = 'id';
         $search[STATE_TYPE][2]['name']      = $LANG['common'][2];

         $search[STATE_TYPE][31]['table']     = 'glpi_states';
         $search[STATE_TYPE][31]['field']     = 'name';
         $search[STATE_TYPE][31]['linkfield'] = 'states_id';
         $search[STATE_TYPE][31]['name']      = $LANG['state'][0];

         $search[STATE_TYPE][3]['table']     = 'glpi_locations';
         $search[STATE_TYPE][3]['field']     = 'completename';
         $search[STATE_TYPE][3]['linkfield'] = 'locations_id';
         $search[STATE_TYPE][3]['name']      = $LANG['common'][15];

         $search[STATE_TYPE][5]['table']     = 'state_types';
         $search[STATE_TYPE][5]['field']     = 'serial';
         $search[STATE_TYPE][5]['linkfield'] = 'serial';
         $search[STATE_TYPE][5]['name']      = $LANG['common'][19];

         $search[STATE_TYPE][6]['table']     = 'state_types';
         $search[STATE_TYPE][6]['field']     = 'otherserial';
         $search[STATE_TYPE][6]['linkfield'] = 'otherserial';
         $search[STATE_TYPE][6]['name']      = $LANG['common'][20];

         $search[STATE_TYPE][16]['table']     = 'state_types';
         $search[STATE_TYPE][16]['field']     = 'comment';
         $search[STATE_TYPE][16]['linkfield'] = 'comment';
         $search[STATE_TYPE][16]['name']      = $LANG['common'][25];
         $search[STATE_TYPE][16]['datatype']  = 'text';

         $search[STATE_TYPE][70]['table']     = 'glpi_users';
         $search[STATE_TYPE][70]['field']     = 'name';
         $search[STATE_TYPE][70]['linkfield'] = 'users_id';
         $search[STATE_TYPE][70]['name']      = $LANG['common'][34];

         $search[STATE_TYPE][71]['table']     = 'glpi_groups';
         $search[STATE_TYPE][71]['field']     = 'name';
         $search[STATE_TYPE][71]['linkfield'] = 'groups_id';
         $search[STATE_TYPE][71]['name']      = $LANG['common'][35];

         $search[STATE_TYPE][19]['table']     = 'state_types';
         $search[STATE_TYPE][19]['field']     = 'date_mod';
         $search[STATE_TYPE][19]['linkfield'] = '';
         $search[STATE_TYPE][19]['name']      = $LANG['common'][26];
         $search[STATE_TYPE][19]['datatype']  = 'datetime';

         $search[STATE_TYPE][23]['table']     = 'glpi_manufacturers';
         $search[STATE_TYPE][23]['field']     = 'name';
         $search[STATE_TYPE][23]['linkfield'] = 'manufacturers_id';
         $search[STATE_TYPE][23]['name']      = $LANG['common'][5];

         $search[STATE_TYPE][24]['table']     = 'glpi_users';
         $search[STATE_TYPE][24]['field']     = 'name';
         $search[STATE_TYPE][24]['linkfield'] = 'users_id_tech';
         $search[STATE_TYPE][24]['name']      = $LANG['common'][10];

         $search[STATE_TYPE][80]['table']     = 'glpi_entities';
         $search[STATE_TYPE][80]['field']     = 'completename';
         $search[STATE_TYPE][80]['linkfield'] = 'entities_id';
         $search[STATE_TYPE][80]['name']      = $LANG['entity'][0];
      } else {
         $ci = new CommonItem();
         $ci->setType($itemtype,true);
         $search[$itemtype] = $ci->obj->getSearchOptions();
      }

      if (in_array($itemtype, $CFG_GLPI["contract_types"])) {
         $search[$itemtype]['contract'] = $LANG['Menu'][25];

         $search[$itemtype][29]['table']         = 'glpi_contracts';
         $search[$itemtype][29]['field']         = 'name';
         $search[$itemtype][29]['linkfield']     = '';
         $search[$itemtype][29]['name']          = $LANG['common'][16]." ".$LANG['financial'][1];
         $search[$itemtype][29]['forcegroupby']  = true;
         $search[$itemtype][29]['datatype']      = 'itemlink';
         $search[$itemtype][29]['itemlink_type'] = CONTRACT_TYPE;

         $search[$itemtype][30]['table']        = 'glpi_contracts';
         $search[$itemtype][30]['field']        = 'num';
         $search[$itemtype][30]['linkfield']    = '';
         $search[$itemtype][30]['name']         = $LANG['financial'][4]." ".$LANG['financial'][1];
         $search[$itemtype][30]['forcegroupby'] = true;

         $search[$itemtype][130]['table']        = 'glpi_contracts';
         $search[$itemtype][130]['field']        = 'duration';
         $search[$itemtype][130]['linkfield']    = '';
         $search[$itemtype][130]['name']         = $LANG['financial'][8]." ".$LANG['financial'][1];
         $search[$itemtype][130]['forcegroupby'] = true;

         $search[$itemtype][131]['table']        = 'glpi_contracts';
         $search[$itemtype][131]['field']        = 'periodicity';
         $search[$itemtype][131]['linkfield']    = '';
         $search[$itemtype][131]['name']         = $LANG['financial'][69];
         $search[$itemtype][131]['forcegroupby'] = true;

         $search[$itemtype][132]['table']        = 'glpi_contracts';
         $search[$itemtype][132]['field']        = 'begin_date';
         $search[$itemtype][132]['linkfield']    = '';
         $search[$itemtype][132]['name']         = $LANG['search'][8]." ".$LANG['financial'][1];
         $search[$itemtype][132]['forcegroupby'] = true;
         $search[$itemtype][132]['datatype']     = 'date';

         $search[$itemtype][133]['table']        = 'glpi_contracts';
         $search[$itemtype][133]['field']        = 'accounting_number';
         $search[$itemtype][133]['linkfield']    = '';
         $search[$itemtype][133]['name']         = $LANG['financial'][13]." ".$LANG['financial'][1];
         $search[$itemtype][133]['forcegroupby'] = true;

         $search[$itemtype][134]['table']         = 'glpi_contracts';
         $search[$itemtype][134]['field']         = 'end_date';
         $search[$itemtype][134]['linkfield']     = '';
         $search[$itemtype][134]['name']          = $LANG['search'][9]." ".$LANG['financial'][1];
         $search[$itemtype][134]['forcegroupby']  = true;
         $search[$itemtype][134]['datatype']      = 'date_delay';
         $search[$itemtype][134]['datafields'][1] = 'begin_date';
         $search[$itemtype][134]['datafields'][2] = 'duration';

         $search[$itemtype][135]['table']        = 'glpi_contracts';
         $search[$itemtype][135]['field']        = 'notice';
         $search[$itemtype][135]['linkfield']    = '';
         $search[$itemtype][135]['name']         = $LANG['financial'][10]." ".$LANG['financial'][1];
         $search[$itemtype][135]['forcegroupby'] = true;

         $search[$itemtype][136]['table']        = 'glpi_contracts';
         $search[$itemtype][136]['field']        = 'cost';
         $search[$itemtype][136]['linkfield']    = '';
         $search[$itemtype][136]['name']         = $LANG['financial'][5]." ".$LANG['financial'][1];
         $search[$itemtype][136]['forcegroupby'] = true;
         $search[$itemtype][136]['datatype']     = 'decimal';

         $search[$itemtype][137]['table']        = 'glpi_contracts';
         $search[$itemtype][137]['field']        = 'billing';
         $search[$itemtype][137]['linkfield']    = '';
         $search[$itemtype][137]['name']       = $LANG['financial'][11]." ".$LANG['financial'][1];
         $search[$itemtype][137]['forcegroupby'] = true;

         $search[$itemtype][138]['table']        = 'glpi_contracts';
         $search[$itemtype][138]['field']        = 'renewal';
         $search[$itemtype][138]['linkfield']    = '';
         $search[$itemtype][138]['name']      = $LANG['financial'][107]." ".$LANG['financial'][1];
         $search[$itemtype][138]['forcegroupby'] = true;
      }

      // && $itemtype !=  CARTRIDGEITEM_TYPE && $itemtype !=  CONSUMABLEITEM_TYPE
      if (in_array($itemtype, $CFG_GLPI["infocom_types"])) {
         $search[$itemtype]['financial'] = $LANG['financial'][3];

         $search[$itemtype][25]['table']        = 'glpi_infocoms';
         $search[$itemtype][25]['field']        = 'immo_number';
         $search[$itemtype][25]['linkfield']    = '';
         $search[$itemtype][25]['name']         = $LANG['financial'][20];
         $search[$itemtype][25]['forcegroupby'] = true;

         $search[$itemtype][26]['table']        = 'glpi_infocoms';
         $search[$itemtype][26]['field']        = 'order_number';
         $search[$itemtype][26]['linkfield']    = '';
         $search[$itemtype][26]['name']         = $LANG['financial'][18];
         $search[$itemtype][26]['forcegroupby'] = true;

         $search[$itemtype][27]['table']        = 'glpi_infocoms';
         $search[$itemtype][27]['field']        = 'delivery_number';
         $search[$itemtype][27]['linkfield']    = '';
         $search[$itemtype][27]['name']         = $LANG['financial'][19];
         $search[$itemtype][27]['forcegroupby'] = true;

         $search[$itemtype][28]['table']        = 'glpi_infocoms';
         $search[$itemtype][28]['field']        = 'bill';
         $search[$itemtype][28]['linkfield']    = '';
         $search[$itemtype][28]['name']         = $LANG['financial'][82];
         $search[$itemtype][28]['forcegroupby'] = true;

         $search[$itemtype][37]['table']        = 'glpi_infocoms';
         $search[$itemtype][37]['field']        = 'buy_date';
         $search[$itemtype][37]['linkfield']    = '';
         $search[$itemtype][37]['name']         = $LANG['financial'][14];
         $search[$itemtype][37]['datatype']     = 'date';
         $search[$itemtype][37]['forcegroupby'] = true;

         $search[$itemtype][38]['table']        = 'glpi_infocoms';
         $search[$itemtype][38]['field']        = 'use_date';
         $search[$itemtype][38]['linkfield']    = '';
         $search[$itemtype][38]['name']         = $LANG['financial'][76];
         $search[$itemtype][38]['datatype']     = 'date';
         $search[$itemtype][38]['forcegroupby'] = true;

         $search[$itemtype][50]['table']        = 'glpi_budgets';
         $search[$itemtype][50]['field']        = 'name';
         $search[$itemtype][50]['linkfield']    = '';
         $search[$itemtype][50]['name']         = $LANG['financial'][87];
         $search[$itemtype][50]['forcegroupby'] = true;

         $search[$itemtype][51]['table']        = 'glpi_infocoms';
         $search[$itemtype][51]['field']        = 'warranty_duration';
         $search[$itemtype][51]['linkfield']    = '';
         $search[$itemtype][51]['name']         = $LANG['financial'][15];
         $search[$itemtype][51]['forcegroupby'] = true;

         $search[$itemtype][52]['table']        = 'glpi_infocoms';
         $search[$itemtype][52]['field']        = 'warranty_info';
         $search[$itemtype][52]['linkfield']    = '';
         $search[$itemtype][52]['name']         = $LANG['financial'][16];
         $search[$itemtype][52]['forcegroupby'] = true;

         $search[$itemtype][120]['table']         = 'glpi_infocoms';
         $search[$itemtype][120]['field']         = 'end_warranty';
         $search[$itemtype][120]['linkfield']     = '';
         $search[$itemtype][120]['name']          = $LANG['financial'][80];
         $search[$itemtype][120]['datatype']      = 'date';
         $search[$itemtype][120]['datatype']      = 'date_delay';
         $search[$itemtype][120]['datafields'][1] = 'buy_date';
         $search[$itemtype][120]['datafields'][2] = 'warranty_duration';
         $search[$itemtype][120]['forcegroupby']  = true;

         $search[$itemtype][53]['table']        = 'glpi_suppliers_infocoms';
         $search[$itemtype][53]['field']        = 'name';
         $search[$itemtype][53]['linkfield']    = '';
         $search[$itemtype][53]['name']         = $LANG['financial'][26];
         $search[$itemtype][53]['forcegroupby'] = true;

         $search[$itemtype][54]['table']        = 'glpi_infocoms';
         $search[$itemtype][54]['field']        = 'value';
         $search[$itemtype][54]['linkfield']    = '';
         $search[$itemtype][54]['name']         = $LANG['financial'][21];
         $search[$itemtype][54]['datatype']     = 'decimal';
         $search[$itemtype][54]['width']        = 100;
         $search[$itemtype][54]['forcegroupby'] = true;

         $search[$itemtype][55]['table']        = 'glpi_infocoms';
         $search[$itemtype][55]['field']        = 'warranty_value';
         $search[$itemtype][55]['linkfield']    = '';
         $search[$itemtype][55]['name']         = $LANG['financial'][78];
         $search[$itemtype][55]['datatype']     = 'decimal';
         $search[$itemtype][55]['width']        = 100;
         $search[$itemtype][55]['forcegroupby'] = true;

         $search[$itemtype][56]['table']        = 'glpi_infocoms';
         $search[$itemtype][56]['field']        = 'sink_time';
         $search[$itemtype][56]['linkfield']    = '';
         $search[$itemtype][56]['name']         = $LANG['financial'][23];
         $search[$itemtype][56]['forcegroupby'] = true;

         $search[$itemtype][57]['table']        = 'glpi_infocoms';
         $search[$itemtype][57]['field']        = 'sink_type';
         $search[$itemtype][57]['linkfield']    = '';
         $search[$itemtype][57]['name']         = $LANG['financial'][22];
         $search[$itemtype][57]['forcegroupby'] = true;

         $search[$itemtype][58]['table']        = 'glpi_infocoms';
         $search[$itemtype][58]['field']        = 'sink_coeff';
         $search[$itemtype][58]['linkfield']    = '';
         $search[$itemtype][58]['name']         = $LANG['financial'][77];
         $search[$itemtype][58]['forcegroupby'] = true;

         $search[$itemtype][59]['table']        = 'glpi_infocoms';
         $search[$itemtype][59]['field']        = 'alert';
         $search[$itemtype][59]['linkfield']    = '';
         $search[$itemtype][59]['name']         = $LANG['common'][41];
         $search[$itemtype][59]['forcegroupby'] = true;

         $search[$itemtype][122]['table']       = 'glpi_infocoms';
         $search[$itemtype][122]['field']       = 'comment';
         $search[$itemtype][122]['linkfield']   = '';
         $search[$itemtype][122]['name']        = $LANG['common'][25]." - ".$LANG['financial'][3];
         $search[$itemtype][122]['datatype']    = 'text';
         $search[$itemtype][122]['forcegroupby'] = true;
      }

      // Search options added by plugins
      $plugsearch=getPluginSearchOptions($itemtype);
      if (count($plugsearch)) {
         $search[$itemtype] += array('plugins' => $LANG['common'][29]);
         $search[$itemtype] += $plugsearch;
      }
   }
   return $search[$itemtype];
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
   $plug_rel=getPluginsDatabaseRelations();
   if (count($plug_rel)>0) {
      $RELATION=array_merge_recursive($RELATION,$plug_rel);
   }
   return $RELATION;
}

/**
 * Check Write Access to a directory
 *
 * @param $dir string: directory to check
 * @return 2 : creation error 1 : delete error 0: OK
 **/
function testWriteAccessToDirectory($dir) {

   $rand=rand();

   // Check directory creation which can be denied by SElinux
   $sdir = sprintf("%s/test_glpi_%08x", $dir, $rand);
   if (!mkdir($sdir)) {
      return 4;
   } else if (!rmdir($sdir)) {
      return 3;
   }

   // Check file creation
   $path = sprintf("%s/test_glpi_%08x.txt", $dir, $rand);
   $fp = fopen($path,'w');
   if (empty($fp)) {
      return 2;
   } else {
      $fw = fwrite($fp,"This file was created for testing reasons. ");
      fclose($fp);
      $delete = unlink($path);
      if (!$delete) {
         return 1;
      }
   }
   return 0;
}

/**
* Compute PHP memory_limit
*
* @return memory limit
**/
function getMemoryLimit () {

   $mem=ini_get("memory_limit");
   preg_match("/([-0-9]+)([KMG]*)/",$mem,$matches);
   $mem="";
   // no K M or G
   if (isset($matches[1])) {
      if (!isset($matches[2])) {
         $mem=$matches[1];
      } else {
         $mem=$matches[1];
         switch ($matches[2]) {
            case "G" :
               $mem*=1024;
               // nobreak;
            case "M" :
               $mem*=1024;
               // nobreak;
            case "K" :
               $mem*=1024;
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
   echo "<tr class='tab_bg_1'><td class='left'><b>".$LANG['install'][8]."</b></td>";

   // PHP Version  - exclude PHP3, PHP 4 and zend.ze1 compatibility
   if (substr(phpversion(),0,1) == "5") {
      // PHP > 5 ok, now check PHP zend.ze1_compatibility_mode
      if (ini_get("zend.ze1_compatibility_mode") == 1) {
         $error = 2;
         echo "<td class='red'>
               <img src=\"".GLPI_ROOT."/pics/redbutton.png\">".$LANG['install'][10]."</td></tr>";
      } else {
         echo "<td><img src=\"".GLPI_ROOT."/pics/greenbutton.png\" alt='".
                    $LANG['install'][11]."' title='".$LANG['install'][11]."'></td></tr>";
      }
   } else { // PHP <5
      $error = 2;
      echo "<td class='red'>
            <img src=\"".GLPI_ROOT."/pics/redbutton.png\">".$LANG['install'][9]."</td></tr>";
   }

   // Check for mysql extension ni php
   echo "<tr class='tab_bg_1'><td class='left'><b>".$LANG['install'][71]."</b></td>";
   if (!function_exists("mysql_query")) {
      echo "<td class='red'>";
      echo "<img src=\"".GLPI_ROOT."/pics/redbutton.png\">".$LANG['install'][72]."</td></tr>";
      $error = 2;
   } else {
      echo "<td><img src=\"".GLPI_ROOT."/pics/greenbutton.png\" alt='".
                 $LANG['install'][73]."' title='".$LANG['install'][73]."'></td></tr>";
   }

   // session test
   echo "<tr class='tab_bg_1'><td class='left'><b>".$LANG['install'][12]."</b></td>";

   // check whether session are enabled at all!!
   if (!extension_loaded('session')) {
      $error = 2;
      echo "<td class='red'><b>".$LANG['install'][13]."</b></td></tr>";
   } else if ((isset($_SESSION["Test_session_GLPI"]) && $_SESSION["Test_session_GLPI"] == 1) // From install
              || isset($_SESSION["glpi_currenttime"])) { // From Update

      echo "<td><img src=\"".GLPI_ROOT."/pics/greenbutton.png\" alt='".
                 $LANG['install'][14]."' title='".$LANG['install'][14]."'></td></tr>";
   } else if ($error != 2) {
      echo "<td class='red'>";
      echo "<img src=\"".GLPI_ROOT."/pics/redbutton.png\">".$LANG['install'][15]."</td></tr>";
      $error = 1;
   }

   //Test for session auto_start
   if (ini_get('session.auto_start')==1) {
      echo "<tr class='tab_bg_1'><td class='left'><b>".$LANG['install'][68]."</b></td>";
      echo "<td class='red'>";
      echo "<img src=\"".GLPI_ROOT."/pics/redbutton.png\">".$LANG['install'][69]."</td></tr>";
      $error = 2;
   }

   //Test for option session use trans_id loaded or not.
   echo "<tr class='tab_bg_1'><td class='left'><b>".$LANG['install'][74]."</b></td>";
   if (isset($_POST[session_name()]) || isset($_GET[session_name()])) {
      echo "<td class='red'>";
      echo "<img src=\"".GLPI_ROOT."/pics/redbutton.png\">".$LANG['install'][75]."</td></tr>";
      $error = 2;
   } else {
      echo "<td><img src=\"".GLPI_ROOT."/pics/greenbutton.png\" alt='".
                 $LANG['install'][76]."' title='".$LANG['install'][76]."'></td></tr>";
   }

   //Test for sybase extension loaded or not.
   echo "<tr class='tab_bg_1'><td class='left'><b>".$LANG['install'][65]."</b></td>";
   if (ini_get('magic_quotes_sybase')) {
      echo "<td class='red'>";
      echo "<img src=\"".GLPI_ROOT."/pics/redbutton.png\">".$LANG['install'][66]."</td></tr>";
      $error = 2;
   } else {
      echo "<td><img src=\"".GLPI_ROOT."/pics/greenbutton.png\" alt='".
                 $LANG['install'][67]."' title='".$LANG['install'][67]."'></td></tr>";
   }

   //Test for json_encode function.
   echo "<tr class='tab_bg_1'><td class='left'><b>".$LANG['install'][102]."</b></td>";
   if (!function_exists('json_encode') || !function_exists('json_decode')) {
      echo "<td><img src=\"".GLPI_ROOT."/pics/redbutton.png\" >".$LANG['install'][103]."></td></tr>";
      $error = 2;
   } else {
      echo "<td><img src=\"".GLPI_ROOT."/pics/greenbutton.png\" alt='".
                 $LANG['install'][85]."' title='".$LANG['install'][85]."'></td></tr>";
   }

   //Test for mbstring extension.
   echo "<tr class='tab_bg_1'><td class='left'><b>".$LANG['install'][104]."</b></td>";
   if (!extension_loaded('mbstring')) {
      echo "<td><img src=\"".GLPI_ROOT."/pics/redbutton.png\" >".$LANG['install'][105]."></td></tr>";
      $error = 2;
   } else {
      echo "<td><img src=\"".GLPI_ROOT."/pics/greenbutton.png\" alt='".
                 $LANG['install'][85]."' title='".$LANG['install'][85]."'></td></tr>";
   }

   // memory test
   echo "<tr class='tab_bg_1'><td class='left'><b>".$LANG['install'][86]."</b></td>";

   $mem = getMemoryLimit();

   if ( $mem == "" ) { // memory_limit non compilé -> no memory limit
      echo "<td><img src=\"".GLPI_ROOT."/pics/greenbutton.png\" alt='".$LANG['install'][95]." - ".
                 $LANG['install'][89]."' title='".$LANG['install'][95]." - ".
                 $LANG['install'][89]."'></td></tr>";
   } else if( $mem == "-1" ) { // memory_limit compilé mais illimité
      echo "<td><img src=\"".GLPI_ROOT."/pics/greenbutton.png\" alt='".$LANG['install'][96]." - ".
                 $LANG['install'][89]."' title='".$LANG['install'][96]." - ".
                 $LANG['install'][89]."'></td></tr>";
   } else if ($mem<64*1024*1024) { // memoire insuffisante
      echo "<td class='red'><img src=\"".GLPI_ROOT."/pics/redbutton.png\"><b>".
                             $LANG['install'][87]." $mem octets</b><br>".$LANG['install'][88]."<br>".
                             $LANG['install'][90]."</td></tr>";
   } else { // on a sufisament de mémoire on passe à la suite
      echo "<td><img src=\"".GLPI_ROOT."/pics/greenbutton.png\" alt='".$LANG['install'][91]." - ".
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

   $dir_to_check=array(GLPI_DUMP_DIR    => $LANG['install'][16],
                       GLPI_DOC_DIR     => $LANG['install'][21],
                       GLPI_CONFIG_DIR  => $LANG['install'][23],
                       GLPI_SESSION_DIR => $LANG['install'][50],
                       GLPI_CRON_DIR    => $LANG['install'][52],
                       GLPI_CACHE_DIR   => $LANG['install'][99]);
   $error=0;
   foreach ($dir_to_check as $dir => $message) {
      echo "<tr class='tab_bg_1' ><td class='left'><b>".$message."</b></td>";
      $tmperror=testWriteAccessToDirectory($dir);

      switch($tmperror) {
         // Error on creation
         case 4 :
            echo "<td><img src=\"".GLPI_ROOT."/pics/redbutton.png\"><p class='red'>".
                       $LANG['install'][100]."</p> ".$LANG['install'][97]."'".$dir."'</td></tr>";
            $error=2;
            break;

         case 3 :
            echo "<td><img src=\"".GLPI_ROOT."/pics/redbutton.png\"><p class='red'>".
                       $LANG['install'][101]."</p> ".$LANG['install'][97]."'".$dir."'</td></tr>";
            $error=1;
            break;

         // Error on creation
         case 2 :
            echo "<td><img src=\"".GLPI_ROOT."/pics/redbutton.png\"><p class='red'>".
                       $LANG['install'][17]."</p> ".$LANG['install'][97]."'".$dir."'</td></tr>";
            $error=2;
            break;

         case 1 :
            echo "<td><img src=\"".GLPI_ROOT."/pics/redbutton.png\"><p class='red'>".
                       $LANG['install'][19]."</p> ".$LANG['install'][97]."'".$dir."'</td></tr>";
            $error=1;
            break;

         default :
            echo "<td><img src=\"".GLPI_ROOT."/pics/greenbutton.png\" alt='".
                       $LANG['install'][20]."' title='".$LANG['install'][20]."'></td></tr>";
            break;
      }
   }

   // Only write test for GLPI_LOG as SElinux prevent removing log file.
   echo "<tr class='tab_bg_1'><td class='left'><b>".$LANG['install'][53]."</b></td>";
   if (error_log("Test\n", 3, GLPI_LOG_DIR."/php-errors.log")) {
      echo "<td><img src=\"".GLPI_ROOT."/pics/greenbutton.png\" alt='".
                 $LANG['install'][22]."' title='".$LANG['install'][22]."'></td></tr>";
   } else {
      echo "<td><img src=\"".GLPI_ROOT."/pics/redbutton.png\"><p class='red'>".
                 $LANG['install'][19]."</p> ".$LANG['install'][97]."'".GLPI_LOG_DIR."'. ".$LANG['install'][98]."</td></tr>";
      $error=1;
   }
   return $error;
}

/**
 * Strip slash  for variable & array
 *
 * @param $value array or string: item to stripslashes (array or string)
 * @return stripslashes item
 */
function stripslashes_deep($value) {

   $value = is_array($value) ? array_map('stripslashes_deep', $value) :
            (is_null($value) ? NULL : stripslashes($value));
   return $value;
}

/**
 *  Add slash for variable & array
 *
 * @param $value array or string: value to add slashes (array or string)
 * @return addslashes value
 */
function addslashes_deep($value) {

   $value = is_array($value) ? array_map('addslashes_deep', $value) :
            (is_null($value) ? NULL : mysql_real_escape_string($value));
   return $value;
}

/**
 *
 */
function key_exists_deep($need,$tab) {

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
 * @return clean item
 *
 * @see unclean_cross_side_scripting_deep*
 */
function clean_cross_side_scripting_deep($value) {

   $in=array('<',
             '>');
   $out=array("&lt;",
              "&gt;");
   $value = is_array($value) ? array_map('clean_cross_side_scripting_deep', $value) :
            (is_null($value) ? NULL : str_replace($in,$out,$value));
   return $value;
}

/**
 *  Invert fonction from clean_cross_side_scripting_deep
 *
 * @param $value array or string: item to unclean from clean_cross_side_scripting_deep
 * @return unclean item
 * @see clean_cross_side_scripting_deep
 */
function unclean_cross_side_scripting_deep($value) {

   $in=array('<',
             '>');
   $out=array("&lt;",
              "&gt;");
   $value = is_array($value) ? array_map('unclean_cross_side_scripting_deep', $value) :
            (is_null($value) ? NULL : str_replace($out,$in,$value));
   return $value;
}

/**
 *  Resume text for followup
 *
 * @param $string string: string to resume
 * @param $length integer: resume length
 * @return cut string
 */
function resume_text($string,$length=255) {

   if (strlen($string)>$length) {
      $string=utf8_substr($string,0,$length)."&nbsp;(...)";
   }
   return $string;
}

/**
 *  Resume a name for display
 *
 * @param $string string: string to resume
 * @param $length integer: resume length
 * @return cut string
 */
function resume_name($string,$length=255) {

   if (strlen($string)>$length) {
      $string=utf8_substr($string,0,$length)."...";
   }
   return $string;
}


/**
 *  Format mail row
 *
 * @param $string string: label string
 * @param $value string: value string
 * @return string
 */
function mailRow($string,$value) {

   $row=utf8_str_pad( $string . ': ',25,' ', STR_PAD_RIGHT).$value."\n";
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
 * @return string
 */
function utf8_str_pad($input, $pad_length, $pad_string = " ", $pad_type = STR_PAD_RIGHT) {

    $diff = strlen($input) - utf8_strlen($input);
    return str_pad($input, $pad_length+$diff, $pad_string, $pad_type);
}

/**
 * Clean post value for display in textarea
 *
 *@param $value string: string value
 *
 *@return clean value
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
   return str_replace($order,$replace,$value);
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

   $search = array('@<script[^>]*?>.*?</script[^>]*?>@si', // Strip out javascript
                   '@<style[^>]*?>.*?</style[^>]*?>@si', // Strip style tags properly
                   '@<[\/\!]*?[^<>]*?>@si',              // Strip out HTML tags
                   '@<![\s\S]*?--[ \t\n\r]*>@');        // Strip multi-line comments including CDATA

   $value = preg_replace($search, ' ', $value);

   $value = preg_replace("/(&nbsp;| )+/", " ", $value);
   // nettoyer l'apostrophe curly qui pose probleme a certains rss-readers, lecteurs de mail...
   $value = str_replace("&#8217;","'",$value);

   $value = preg_replace("/ +/u", " ", $value);
   $value = preg_replace("/\n{2,}/", "\n\n", $value,-1);
   return trim($value);
}

/**
 * Convert a date YY-MM-DD HH:MM to DD-MM-YY HH:MM for display in a html table
 *
 * @param $time datetime: datetime to convert
 * @return $time or $date
 */
function convDateTime($time) {

   if (is_null($time)) {
      return $time;
   }

   if (!isset($_SESSION["glpidate_format"])) {
      $_SESSION["glpidate_format"]=0;
   }

   switch ($_SESSION['glpidate_format']) {
      case 1 : // DD-MM-YYYY
         $date = substr($time,8,2)."-";   // day
         $date .= substr($time,5,2)."-";  // month
         $date .= substr($time,0,4). " "; // year
         $date .= substr($time,11,5);     // hours and minutes
         return $date;
         break;

      case 2 : // MM-DD-YYYY
         $date = substr($time,5,2)."-";   // month
         $date .= substr($time,8,2)."-";  // day
         $date .= substr($time,0,4). " "; // year
         $date .= substr($time,11,5);     // hours and minutes
         return $date;
         break;

      default : // YYYY-MM-DD
         if (strlen($time)>16) {
            return substr($time,0,16);
         }
         return $time;
         break;
   }
}

/**
 * Convert a date YY-MM-DD to DD-MM-YY for calendar
 *
 * @param $time date: date to convert
 * @return $time or $date
 */
function convDate($time) {
   global $CFG_GLPI;

   if (is_null($time)) {
      return $time;
   }

   if (!isset($_SESSION["glpidate_format"])) {
      $_SESSION["glpidate_format"]=0;
   }

   switch ($_SESSION['glpidate_format']) {
      case 1 : // DD-MM-YYYY
         $date = substr($time,8,2)."-";  // day
         $date .= substr($time,5,2)."-"; // month
         $date .= substr($time,0,4);     // year
         return $date;
         break;

      case 2 : // MM-DD-YYYY
         $date = substr($time,5,2)."-";  // month
         $date .= substr($time,8,2)."-"; // day
         $date .= substr($time,0,4);     // year
         return $date;
         break;

      default : // YYYY-MM-DD
         if (strlen($time)>10) {
            return substr($time,0,10);
         }
         return $time;
         break;
   }
}

/**
 * Convert a number to correct display
 *
 * @param $number float: Number to display
 * @param $edit boolean: display number for edition ? (id edit use . in all case)
 * @param $forcedecimal integer: Force decimal number (do not use default value)
 *
 * @return formatted number
 */
function formatNumber($number,$edit=false,$forcedecimal=-1) {
   global $CFG_GLPI;

   // Php 5.3 : number_format() expects parameter 1 to be double,
   if ($number=="") {
      $number=0;
   } else if ($number=="-") { // used for not defines value (from TableauAmort, p.e.)
      return "-";
   }

   $decimal=$CFG_GLPI["decimal_number"];
   if ($forcedecimal>=0) {
      $decimal=$forcedecimal;
   }

   // Edit : clean display for mysql
   if ($edit) {
      return number_format($number,$decimal,'.','');
   }

   // Display : clean display
   switch ($_SESSION['glpinumber_format']) {
      case 2: // Other French
         return number_format($number,$decimal,',',' ');
         break;

      case 0: // French
         return number_format($number,$decimal,'.',' ');
         break;

      default: // English
         return number_format($number,$decimal,'.',',');
         break;
   }
}

/**
 * Send a file (not a document) to the navigator
 * See Document->send();
 *
 * @param $file string: storage filename
 * @param $filename string: file title
 * @return nothing
 */
function sendFile($file,$filename) {
   global $DB,$LANG;

   // Test securite : document in DOC_DIR
   $tmpfile=str_replace(GLPI_DOC_DIR,"",$file);
   if (strstr($tmpfile,"../") || strstr($tmpfile,"..\\")) {
      logEvent($file, "sendFile", 1, "security", $_SESSION["glpiname"].
               " try to get a non standard file.");
      die("Security attack !!!");
   }

   if (!file_exists($file)) {
      die("Error file $file does not exist");
   }
   $splitter=explode("/",$file);

   $mime="application/octetstream";
   if (preg_match('/\.(...)$/',$file,$regs)){
      switch ($regs[1]) {
         case "sql" :
            $mime="text/x-sql";
            break;

         case "xml" :
            $mime="text/xml";
            break;
      }
   }

   // Now send the file with header() magic
   header("Expires: Mon, 26 Nov 1962 00:00:00 GMT");
   header('Pragma: private'); /// IE BUG + SSL
   header('Cache-control: private, must-revalidate'); /// IE BUG + SSL
   header("Content-disposition: filename=\"$filename\"");
   header("Content-type: ".$mime);

   readfile($file) or die ("Error opening file $file");
}

/**
 * Convert a value in byte, kbyte, megabyte etc...
 *
 * @param $val string: config value (like 10k, 5M)
 * @return $val
 */
function return_bytes_from_ini_vars($val) {

   $val = trim($val);
   $last = utf8_strtolower($val{strlen($val)-1});
   switch($last) {
      // Le modifieur 'G' est disponible depuis PHP 5.1.0
      case 'g':
         $val *= 1024;

      case 'm':
         $val *= 1024;

      case 'k':
         $val *= 1024;
   }

   return $val;
}

/**
 * Header redirection hack
 *
 * @param $dest string: Redirection destination
 * @return nothing
 */
function glpi_header($dest) {

   $toadd='';
   if (!strpos($dest,"?")) {
      $toadd='?tokonq='.getRandomString(5);
   }
   echo "<script language=javascript>
         NomNav = navigator.appName;
         if (NomNav=='Konqueror'){
            window.location=\"".$dest.$toadd."\";
         } else {
            window.location=\"".$dest."\";
         }
      </script>";
   exit();
}

/**
 * Call from a popup Windows, refresh the main Window
 */
function refreshMainWindow() {
   if (isset($_SESSION["glpipopup"]["rand"])) {
      echo "<script type='text/javascript' >\n";
      echo "window.opener.update_results_".$_SESSION["glpipopup"]["rand"]."();";
      echo "</script>";
   }

}

/**
 * Call cron without time check
 *
 * @return boolean : true if launched
 */
function callCronForce() {
   global $CFG_GLPI;

   $path=$CFG_GLPI['root_doc']."/front/cron.php";

   echo "<div style=\"background-image: url('$path');\"></div>";
   return true;
}

/**
 * Call cron if time since last launch elapsed
 *
 * @return nothing
 */
function callCron() {

   if (isset($_SESSION["glpicrontimer"])) {
      // call function callcron() every 5min
      if ((time()-$_SESSION["glpicrontimer"])>300) {
         if (callCronForce()) {
            // Restart timer
            $_SESSION["glpicrontimer"]=time();
         }
      }
   } else {
      // Start timer
      $_SESSION["glpicrontimer"]=time();
   }
}

/**
 * Get hour from sql
 *
 * @param $time datetime: time
 * @return  array
 */
function get_hour_from_sql($time) {

   $t=explode(" ",$time);
   $p=explode(":",$t[1]);
   return $p[0].":".$p[1];
}

/**
 *  Optimize sql table
 *
 * @param $progress_fct function to call to display progress message
 *
 * @return number of tables
 */
function optimize_tables ($progress_fct=NULL){
   global $DB;

   if (function_exists($progress_fct)) {
      $progress_fct("optimize"); // Start
   }
   $result=$DB->list_tables("glpi_%");
   $nb=0;
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
* @return  boolean
**/
function seems_utf8($str) {
   return mb_check_encoding($str,"UTF-8");
}

/**
 * NOT USED IN CORE - Used for update process - Clean string for knowbase display
 * replace nl2br
 *
 * @param $pee string: initial string
 * @param $br boolean: make line breaks ?
 *
 * @return $string
 */
function autop($pee, $br=true) {
   // Thanks  to Matthew Mullenweg

   $pee = preg_replace("/(\r\n|\n|\r)/", "\n", $pee); // cross-platform newlines
   $pee = preg_replace("/\n\n+/", "\n\n", $pee); // take care of duplicates
   $pee = preg_replace('/\n?(.+?)(\n\n|\z)/s', "<p>$1</p>\n", $pee); // make paragraphs,
                                                                     // including one at the end
   if ($br) {
      $pee = preg_replace('|(?<!</p>)\s*\n|', "<br>\n", $pee); // optionally make line breaks
   }
   return $pee;
}

/**
 * NOT USED IN CORE - Used for update process - make url clickable
 *
 * @param $chaine string: initial string
 *
 * @return $string
 */
function clicurl($chaine) {

   $text=preg_replace("`((?:https?|ftp)://\S+)(\s|\z)`", '<a href="$1">$1</a>$2', $chaine);
   return $text;
}

/**
 * NOT USED IN CORE - Used for update process - Split the message into tokens ($inside contains all text inside $start and $end, and $outside contains all text outside)
 *
 * @param $text string: initial text
 * @param $start integer: where to start
 * @param $end integer: where to stop
 *
 * @return array
 */
function split_text($text, $start, $end) {

   // Adapte de PunBB
   //Copyright (C)  Rickard Andersson (rickard@punbb.org)

   $tokens = explode($start, $text);
   $outside[] = $tokens[0];
   $num_tokens = count($tokens);
   for ($i = 1; $i < $num_tokens; ++$i) {
      $temp = explode($end, $tokens[$i]);
      $inside[] = $temp[0];
      $outside[] = $temp[1];
   }
   return array($inside, $outside);
}

/**
 * NOT USED IN CORE - Used for update process - Replace bbcode in text by html tag
 *
 * @param $string string: initial string
 *
 * @return formatted string
 */
function rembo($string) {

   // Adapte de PunBB
   //Copyright (C)  Rickard Andersson (rickard@punbb.org)

   // If the message contains a code tag we have to split it up
   // (text within [code][/code] shouldn't be touched)
   if (strpos($string, '[code]') !== false && strpos($string, '[/code]') !== false) {
      list($inside, $outside) = split_text($string, '[code]', '[/code]');
      $outside = array_map('trim', $outside);
      $string = implode('<">', $outside);
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

   $string=clicurl($string);
   $string=autop($string);

   // If we split up the message before we have to concatenate it together again (code tags)
   if (isset($inside)) {
      $outside = explode('<">', $string);
      $string = '';
      $num_tokens = count($outside);

      for ($i = 0; $i < $num_tokens; ++$i) {
         $string .= $outside[$i];
         if (isset($inside[$i])) {
            $string .= '<br><br><table  class="code center"><tr><td class="punquote">' .
                       '<strong>Code:</strong><br><br><pre>'.trim($inside[$i]).'</pre></td>' .
                       '</tr></table><br>';
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
function makeTextSearch($val,$not=false) {

   $NOT="";
   if ($not) {
      $NOT= " NOT ";
   }
   // Unclean to permit < and > search
   $val=unclean_cross_side_scripting_deep($val);
   if ($val=="NULL"||$val=="null") {
      $SEARCH=" IS $NOT NULL ";
   } else {
      $begin=0;
      $end=0;
      if (($length=strlen($val))>0) {
         if (($val[0]=='^')) {
            $begin=1;
         }
         if ($val[$length-1]=='$'){
            $end=1;
         }
      }
      if ($begin||$end) {
         // no utf8_substr, to be consistent with strlen result
         $val=substr($val,$begin,$length-$end-$begin);
      }

      $SEARCH=" $NOT LIKE '".(!$begin?"%":"").$val.(!$end?"%":"")."' ";
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
   $sql = $field . makeTextSearch($val,$not);

   if (($not && $val!="NULL" && $val!='^$')    // Not something
       ||(!$not && $val=='^$')) {              // Empty
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
 */
function getURLContent ($url, &$msgerr=NULL, $rec=0) {
   global $LANG,$CFG_GLPI;

   $content="";
   $taburl=parse_url($url);

   // Connection directe
   if (empty($CFG_GLPI["proxy_name"])) {
      if ($fp=@fsockopen($taburl["host"], (isset($taburl["port"]) ? $taburl["port"] : 80),
          $errno, $errstr, 1)) {

         if (isset($taburl["path"]) && $taburl["path"]!='/') {
            // retrieve path + args
            $request  = "GET ".strstr($url, $taburl["path"])." HTTP/1.1\r\n";
         } else {
            $request  = "GET / HTTP/1.1\r\n";
         }
         $request .= "Host: ".$taburl["host"]."\r\n";
      } else {
         if (isset($msgerr)) {
            $msgerr=$LANG['setup'][304] . " ($errstr)"; // failed direct connexion - try proxy
         }
         return "";
      }
   } else { // Connection using proxy
      $fp = fsockopen($CFG_GLPI["proxy_name"], $CFG_GLPI["proxy_port"], $errno, $errstr, 1);
      if ($fp) {
         $request  = "GET $url HTTP/1.1\r\n";
         $request .= "Host: ".$CFG_GLPI["proxy_name"]."\r\n";
         if (!empty($CFG_GLPI["proxy_user"])) {
            $request .= "Proxy-Authorization: Basic " . base64_encode ($CFG_GLPI["proxy_user"].":".
                        $CFG_GLPI["proxy_password"]) . "\r\n";
         }
      } else {
         if (isset($msgerr)) {
            $msgerr=$LANG['setup'][311] . " ($errstr)"; // failed proxy connexion
         }
         return "";
      }
   }

   $request .= "User-Agent: GLPI/".trim($CFG_GLPI["version"])."\r\n";
   $request .= "Connection: Close\r\n\r\n";
   fwrite($fp, $request);

   $header=true ;
   $redir=false;
   $errstr="";
   while(!feof($fp)) {
      if ($buf=fgets($fp, 1024)) {
         if ($header) {
            if (strlen(trim($buf))==0) {
               // Empty line = end of header
               $header=false;
            } else if ($redir && preg_match("/^Location: (.*)$/", $buf, $rep)) {
               if ($rec<9) {
                  $desturl=trim($rep[1]);
                  $taburl2=parse_url($desturl);
                  if (isset($taburl2['host'])) {
                     // Redirect to another host
                     return (getURLContent($desturl,$errstr,$rec+1));
                  } else  {
                     // redirect to same host
                     return (getURLContent(
                             (isset($taburl['scheme'])?$taburl['scheme']:'http')."://".$taburl['host'].
                             (isset($taburl['port'])?':'.$taburl['port']:'').$desturl,$errstr,$rec+1));
                  }
               } else {
                  $errstr="Too deep";
                  break;
               }
            } else if (preg_match("/^HTTP.*200.*OK/", $buf)) {
               // HTTP 200 = OK
            } else if (preg_match("/^HTTP.*302/", $buf)) {
               // HTTP 302 = Moved Temporarily
               $redir=true;
            } else if (preg_match("/^HTTP/", $buf)) {
               // Other HTTP status = error
               $errstr=trim($buf);
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
         $msgerr=$LANG['setup'][312]; // no data
      } else {
         $msgerr=$LANG['setup'][310] . " ($errstr)"; // HTTP error
      }
   }
   return $content;
}

/**
* Check if new version is available
*
* @param $auto boolean: check done autically ? (if not display result)
*
* @return string explaining the result
**/
function checkNewVersionAvailable($auto=true) {
   global $DB,$LANG,$CFG_GLPI;

   if (!$auto && !haveRight("check_update","r")) {
      return false;
   }
   if (!$auto) {
      echo "<br>";
   }

   $error="";
   $latest_version = getURLContent("http://glpi-project.org/latest_version", $error);

   if (strlen(trim($latest_version))==0) {
      if (!$auto) {
         echo "<div class='center'> $error </div>";
      } else {
         return $error;
      }
   } else {
      $splitted=explode(".",trim($CFG_GLPI["version"]));
      if ($splitted[0]<10) {
         $splitted[0].="0";
      }
      if ($splitted[1]<10) {
         $splitted[1].="0";
      }
      $cur_version = $splitted[0]*10000+$splitted[1]*100;
      if (isset($splitted[2])) {
         if ($splitted[2]<10) {
            $splitted[2].="0";
         }
         $cur_version+=$splitted[2];
      }
      $splitted=explode(".",trim($latest_version));

      if ($splitted[0]<10) {
         $splitted[0].="0";
      }
      if ($splitted[1]<10) {
         $splitted[1].="0";
      }

      $lat_version = $splitted[0]*10000+$splitted[1]*100;
      if (isset($splitted[2])) {
         if ($splitted[2]<10) {
            $splitted[2].="0";
         }
         $lat_version+=$splitted[2];
      }

      if ($cur_version < $lat_version) {
         $config_object=new Config();
         $input["id"]=1;
         $input["founded_new_version"]=$latest_version;
         $config_object->update($input);
         if (!$auto) {
            echo "<div class='center'>".$LANG['setup'][301]." ".$latest_version."</div>";
            echo "<div class='center'>".$LANG['setup'][302]."</div>";
         } else {
            return $LANG['setup'][301]." ".$latest_version;
         }
      } else {
         if (!$auto) {
            echo "<div class='center'>".$LANG['setup'][303]."</div>";
         } else {
            return $LANG['setup'][303];
         }
      }
   }
   return 1;
}

/**
* Cron job to check if a new version is available
*
* @param $task for log
**/
function cron_check_update($task) {
   global $CFG_GLPI;

   $result=checkNewVersionAvailable(1);
   $task->log($result);

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
function getWarrantyExpir($from,$addwarranty,$deletenotice=0) {
   global $LANG;

   // Life warranty
   if ($addwarranty==-1 && $deletenotice==0) {
      return $LANG['setup'][307];
   }
   if ($from==NULL || empty($from)) {
      return "";
   } else {
      return convDate(date("Y-m-d", strtotime("$from+$addwarranty month -$deletenotice month")));
   }
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
function getExpir($begin,$duration,$notice="0") {
   global $LANG;

   if ($begin==NULL || empty($begin)) {
      return "";
   } else {
      $diff=strtotime("$begin+$duration month -$notice month")-time();
      $diff_days=floor($diff/60/60/24);
      if($diff_days>0) {
         return $diff_days." ".$LANG['stats'][31];
      } else {
         return "<span class='red'>".$diff_days." ".$LANG['stats'][31]."</span>";
      }
   }
}

/**
* Manage login redirection
*
* @param $where string: where to redirect ?
**/
function manageRedirect($where) {
   global $CFG_GLPI,$PLUGIN_HOOKS;

   if (!empty($where)) {
      $data=explode("_",$where);
      if (count($data)>=2 && isset($_SESSION["glpiactiveprofile"]["interface"])
          && !empty($_SESSION["glpiactiveprofile"]["interface"])) {

         switch ($_SESSION["glpiactiveprofile"]["interface"]) {
            case "helpdesk" :
               switch ($data[0]) {
                  case "plugin":
                     if (isset($data[2]) && $data[2]>0
                         && isset($PLUGIN_HOOKS['redirect_page'][$data[1]])
                         && !empty($PLUGIN_HOOKS['redirect_page'][$data[1]])) {

                        glpi_header($CFG_GLPI["root_doc"]."/plugins/".$data[1]."/".$PLUGIN_HOOKS['redirect_page'][$data[1]]."?id=".$data[2]);
                     } else {
                        glpi_header($CFG_GLPI["root_doc"]."/front/helpdesk.public.php");
                     }
                     break;

                  case "tracking":

                  case "ticket": ///TODO prepare update name : delete when tracking -> ticket
                     glpi_header($CFG_GLPI["root_doc"]."/front/helpdesk.public.php?show=user&id=".
                                 $data[1]);
                     break;

                  case "prefs":
                     glpi_header($CFG_GLPI["root_doc"]."/front/user.form.my.php");
                     break;

                  default:
                     glpi_header($CFG_GLPI["root_doc"]."/front/helpdesk.public.php");
                     break;
               }
               break;

            case "central" :
               switch ($data[0]) {
                  case "plugin":
                     if (isset($data[2]) && $data[2]>0
                         && isset($PLUGIN_HOOKS['redirect_page'][$data[1]])
                         && !empty($PLUGIN_HOOKS['redirect_page'][$data[1]])) {

                        glpi_header($CFG_GLPI["root_doc"]."/plugins/".$data[1]."/".$PLUGIN_HOOKS['redirect_page'][$data[1]]."?id=".$data[2]);
                     } else {
                        glpi_header($CFG_GLPI["root_doc"]."/front/central.php");
                     }
                     break;

                  case "prefs":
                     glpi_header($CFG_GLPI["root_doc"]."/front/user.form.my.php");
                     break;

                  default :
                     if (!empty($data[0] )&& $data[1]>0) {
                        glpi_header($CFG_GLPI["root_doc"]."/front/".$data[0].".form.php?id=".$data[1]);
                     } else {
                        glpi_header($CFG_GLPI["root_doc"]."/front/central.php");
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
   return preg_replace('/\"/','&quot;',$string);
}

/**
* Get a random string
*
* @param $length integer: length of the random string
*
* @return random string
**/
function getRandomString($length) {

   $alphabet = "1234567890abcdefghijklmnopqrstuvwxyz";
   $rndstring="";
   for ($a = 0; $a <= $length; $a++) {
      $b = rand(0, strlen($alphabet) - 1);
      $rndstring .= $alphabet[$b];
   }
   return $rndstring;
}

/**
* Make a good string from the unix timestamp $sec
*
* @param $sec integer: timestamp
*
* @param $display_sec boolean: display seconds ?
*
* @return string
**/
function timestampToString($sec,$display_sec=true) {
   global $LANG;
   /// TODO : rewrite to have simple code
   $sec=floor($sec);
   if ($sec<0) {
      $sec=0;
   }

   if ($sec < MINUTE_TIMESTAMP) {
      return $sec." ".$LANG['stats'][34];
   } else if ($sec < HOUR_TIMESTAMP) {
      $min = floor($sec/MINUTE_TIMESTAMP);
      $sec = $sec%MINUTE_TIMESTAMP;
      $out = $min." ".$LANG['stats'][33];
      if ($display_sec && $sec >0) {
         $out .= " ".$sec." ".$LANG['stats'][34];
      }
      return $out;
   } else if ($sec <  DAY_TIMESTAMP) {
      $heure = floor($sec/HOUR_TIMESTAMP);
      $min = floor(($sec%HOUR_TIMESTAMP)/(MINUTE_TIMESTAMP));
      $sec = $sec%MINUTE_TIMESTAMP;
      $out = $heure." ".$LANG['job'][21];
      if ($min>0) {
         $out .= " ".$min." ".$LANG['stats'][33];
      }
      if ($display_sec && $sec >0) {
         $out.=" ".$sec." ".$LANG['stats'][34];
      }
      return $out;
   } else {
      $jour = floor($sec/DAY_TIMESTAMP);
      $heure = floor(($sec%DAY_TIMESTAMP)/(HOUR_TIMESTAMP));
      $min = floor(($sec%HOUR_TIMESTAMP)/(MINUTE_TIMESTAMP));
      $sec = $sec%MINUTE_TIMESTAMP;
      $out = $jour." ".$LANG['stats'][31];
      if ($heure>0) {
         $out .= " ".$heure." ".$LANG['job'][21];
      }

      if ($min>0) {
         $out.=" ".$min." ".$LANG['stats'][33];
      }

      if ($display_sec && $sec >0) {
         $out.=" ".$sec." ".$LANG['stats'][34];
      }
      return $out;
   }
}

/**
* Delete a directory and file contains in it
*
* @param $dir string: directory to delete
**/
function deleteDir($dir) {

   if (file_exists($dir)) {
      chmod($dir,0777);
      if (is_dir($dir)) {
         $id_dir = opendir($dir);
         while($element = readdir($id_dir)) {
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
 * @param $login string: login to check
 * @return boolean
 */
function isValidLogin($login="") {
   return preg_match( "/^[[:alnum:]@.\-_ ]+$/i", $login);
}

/**
 * Determine if Ldap is usable checking ldap extension existence
 * @return boolean
 */
function canUseLdap() {
   return extension_loaded('ldap');
}

/**
 * Determine if Imap/Pop is usable checking extension existence
 * @return boolean
 */
function canUseImapPop() {
   return extension_loaded('imap');
}

/** Converts an array of parameters into a query string to be appended to a URL.
 *
 * @return  string  : Query string to append to a URL.
 * @param   $array  array: parameters to append to the query string.
 * @param   $parent This should be left blank (it is used internally by the function).
 */
function append_params($array, $parent='') {

   $params = array();
   foreach ($array as $k => $v) {
      if (is_array($v)) {
         $params[] = append_params($v, (empty($parent) ? rawurlencode($k) : $parent . '[' .
                     rawurlencode($k) . ']'));
      } else {
         $params[] = (!empty($parent) ? $parent . '[' . rawurlencode($k) . ']' :
                      rawurlencode($k)) . '=' . rawurlencode($v);
      }
   }
   return implode('&', $params);
}

/** Format a size passing a size in octet
 *
 * @param   $size integer: Size in octet
 * @return  formatted size
 */
function getSize($size) {

   $bytes = array('B','KB','MB','GB','TB');
   foreach($bytes as $val) {
      if($size > 1024) {
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
 */
function getCountLogin() {
   global $DB;

   $query="SELECT count(*)
           FROM `glpi_events`
           WHERE `message` LIKE '%logged in%'";

   $query2="SELECT `date`
            FROM `glpi_events`
            ORDER BY `date` ASC LIMIT 1";

   $result=$DB->query($query);
   $result2=$DB->query($query2);
   $nb_login=$DB->result($result,0,0);
   $date=$DB->result($result2,0,0);

   echo '<b>'.$nb_login.'</b> logins since '.$date ;
}

/** Initialise a list of items to use navigate through search results
 *
 * @param $itemtype device type
 * @param $title titre de la liste
 * @param $sub_type of the device (for RULE_TYPE, ...)
 */
function initNavigateListItems($itemtype,$title="",$sub_type=-1) {
   global $LANG;

   if (empty($title)) {
      $title=$LANG['common'][53];
   }
   if (strpos($_SERVER['PHP_SELF'],"tabs")>0) {
      $url=$_SERVER['HTTP_REFERER'];
   } else if (strpos($_SERVER['PHP_SELF'],"dropdown")>0) {
      $url=$_SERVER['PHP_SELF']."?itemtype=$itemtype";
   } else {
      $url=$_SERVER['PHP_SELF'];
   }
   if ($sub_type<0) {
      $_SESSION['glpilisttitle'][$itemtype]=$title;
      $_SESSION['glpilistitems'][$itemtype]=array();
      $_SESSION['glpilisturl'][$itemtype]=$url;
   } else {
      $_SESSION['glpilisttitle'][$itemtype][$sub_type]=$title;
      $_SESSION['glpilistitems'][$itemtype][$sub_type]=array();
      $_SESSION['glpilisturl'][$itemtype][$sub_type]=$url;
   }
}

/** Add an item to the navigate through search results list
 *
 * @param $itemtype device type
 * @param $ID ID of the item
 * @param $sub_type of the device (for RULE_TYPE, ...)
 */
function addToNavigateListItems($itemtype,$ID,$sub_type=-1) {

   if ($sub_type<0) {
      $_SESSION['glpilistitems'][$itemtype][]=$ID;
   } else {
      $_SESSION['glpilistitems'][$itemtype][$sub_type][]=$ID;
   }
}

?>