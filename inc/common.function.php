<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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
 along with GLPI; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
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
      if ($itemtype == 'Cartridge') {
         $itemtype = 'CartridgeItem';
      }
      if ($itemtype == 'Consumable') {
         $itemtype = 'ConsumableItem';
      }
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

   /// TODO only use common.tabs.php when all items migrate to new tabs system or clean tabs file on migration

   $predir = ($full ? $CFG_GLPI['root_doc'] : '');
   $dir = '';

   if ($plug=isPluginItemType($itemtype)) {
      $dir = "/plugins/".strtolower($plug['plugin']);
      $item = strtolower($plug['class']);

   } else { // Standard case
      $item = strtolower($itemtype);
   }

   $filename = $dir."/ajax/$item.tabs.php";
   // Use default one is tabs not exists
   if (!file_exists(GLPI_ROOT.$filename)) {
      $filename = "/ajax/common.tabs.php";
   }
   return $predir.$filename;
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
   // Command line can see all entities
   return (isCommandLine()
           || (countElementsInTable("glpi_entities")+1) == count($_SESSION["glpiactiveentities"]));
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
 * Check Write Access to needed directories
 *
 * @param $fordebug boolean display for debug
 *
 * @return 2 : creation error 1 : delete error 0: OK
**/
function checkWriteAccessToDirs($fordebug=false) {
   global $LANG;

   $dir_to_check = array(GLPI_CONFIG_DIR  => $LANG['install'][23],
                         GLPI_DOC_DIR     => $LANG['install'][21],
                         GLPI_DUMP_DIR    => $LANG['install'][16],
                         GLPI_SESSION_DIR => $LANG['install'][50],
                         GLPI_CRON_DIR    => $LANG['install'][52],
                         GLPI_CACHE_DIR   => $LANG['install'][99],
                         GLPI_GRAPH_DIR   => $LANG['install'][106]);
   $error = 0;

   foreach ($dir_to_check as $dir => $message) {

      if (!$fordebug) {
         echo "<tr class='tab_bg_1'><td class='left b'>".$message."</td>";
      }
      $tmperror = testWriteAccessToDirectory($dir);

      $errors = array(4 => $LANG['install'][100],
                      3 => $LANG['install'][101],
                      2 => $LANG['install'][17],
                      1 => $LANG['install'][19]);

      if ($tmperror > 0) {
         if ($fordebug) {
            echo "<img src='".GLPI_ROOT."/pics/redbutton.png'> ".$LANG['install'][97]." $dir - ".
                           $errors[$tmperror]."\n";
         } else {
            echo "<td><img src='".GLPI_ROOT."/pics/redbutton.png'><p class='red'>".
                       $errors[$tmperror]."</p> ".$LANG['install'][97]."'".$dir."'</td></tr>";
         }
         $error = 2;
      } else {
         if ($fordebug) {
            echo "<img src='".GLPI_ROOT."/pics/greenbutton.png'>$dir : OK\n";
         } else {
            echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt=\"".$LANG['install'][20].
                        "\" title=\"".$LANG['install'][20]."\"></td></tr>";
         }
      }
   }

   // Only write test for GLPI_LOG as SElinux prevent removing log file.
   if (!$fordebug) {
      echo "<tr class='tab_bg_1'><td class='left b'>".$LANG['install'][53]."</td>";
   }

   if (error_log("Test\n", 3, GLPI_LOG_DIR."/php-errors.log")) {
      if ($fordebug) {
         echo "<img src='".GLPI_ROOT."/pics/greenbutton.png'>".GLPI_LOG_DIR." : OK\n";
      } else {
         echo "<td><img src='".GLPI_ROOT."/pics/greenbutton.png' alt=\"".$LANG['install'][22].
                    "\" title=\"".$LANG['install'][22]."\"></td></tr>";
      }

   } else {
      if ($fordebug) {
         echo "<img src='".GLPI_ROOT."/pics/redbutton.png'>".$LANG['install'][97]." : ".
                        GLPI_LOG_DIR."\n";
      } else {
         echo "<td><img src='".GLPI_ROOT."/pics/redbutton.png'>".
                   "<p class='red'>".$LANG['install'][19]."</p>".
                   $LANG['install'][97]."'".GLPI_LOG_DIR."'</td></tr>";
      }
      $error = 1;
   }
   return $error;
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
 *  Resume text for followup
 *
 * @param $string string: string to resume
 * @param $length integer: resume length
 *
 * @return cut string
**/
function resume_text($string, $length=255) {

   if (strlen($string)>$length) {
      $string = Toolbox::substr($string, 0, $length)."&nbsp;(...)";
   }

   return $string;
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
      $string = Toolbox::substr($string, 0, $length)."...";
   }

   return $string;
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

   $number = doubleval($number);

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
 * Convert a value in byte, kbyte, megabyte etc...
 *
 * @param $val string: config value (like 10k, 5M)
 *
 * @return $val
**/
function return_bytes_from_ini_vars($val) {

   $val  = trim($val);
   $last = Toolbox::strtolower($val{strlen($val)-1});

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
 * @param $migration migration class
 * @param $cron to know if optimize must be done
 *
 * @return number of tables
**/
function optimize_tables ($migration=NULL, $cron=false) {
   global $DB;

   if (!is_null($migration) && method_exists ($migration,'displayMessage')) {
      $migration->displayMessage("optimize - start");
   }
   $result = $DB->list_tables("glpi_%");
   $nb     = 0;


   while ($line = $DB->fetch_array($result)) {
      $table = $line[0];

   // For big database to reduce delay of migration
      if ($cron || countElementsInTable($table) < 15000000) {

         if (!is_null($migration) && method_exists ($migration,'displayMessage')) {
            $migration->displayMessage("optimize - $table");
         }

         $query = "OPTIMIZE TABLE `".$table."` ;";
         $DB->query($query);
         $nb++;
      }
   }
   $DB->free_result($result);

   if (!is_null($migration) && method_exists ($migration,'displayMessage') ){
      $migration->displayMessage("optimize - end");
   }


   return $nb;
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
   $val = Toolbox::unclean_cross_side_scripting_deep($val);

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
         // no Toolbox::substr, to be consistent with strlen result
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
                        Toolbox::decrypt($CFG_GLPI["proxy_passwd"],GLPIKEY)) . "\r\n";
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

            } else if (preg_match("/^HTTP.*200.*OK/", $buf)) {
               // HTTP 200 = OK

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

   return Toolbox::convDate(date("Y-m-d", strtotime("$from+$addwarranty month -$deletenotice month")));
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
      return $diff_days." ".Toolbox::ucfirst($LANG['calendar'][12]);
   }

   return "<span class='red'>".$diff_days." ".Toolbox::ucfirst($LANG['calendar'][12])."</span>";
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
         // Plugin tab
         if (isset($data[3])) {
            $forcetab .= '_'.$data[3];
         }

         switch ($_SESSION["glpiactiveprofile"]["interface"]) {
            case "helpdesk" :
               switch ($data[0]) {
                  case "plugin" :
                     $plugin = $data[1];
                     $valid  = false;
                     if (isset($PLUGIN_HOOKS['redirect_page'][$plugin])
                         && !empty($PLUGIN_HOOKS['redirect_page'][$plugin])) {
                        // Simple redirect
                        if (!is_array($PLUGIN_HOOKS['redirect_page'][$plugin])) {
                           if (isset($data[2]) && $data[2]>0) {
                              $valid = true;
                              $id    = $data[2];
                              $page  = $PLUGIN_HOOKS['redirect_page'][$plugin];
                           }
                           $forcetabnum = 3 ;
                        } else { // Complex redirect
                           if (isset($data[2])
                               && !empty($data[2])
                               && isset($data[3])
                               && $data[3] > 0
                               && isset($PLUGIN_HOOKS['redirect_page'][$plugin][$data[2]])
                               && !empty($PLUGIN_HOOKS['redirect_page'][$plugin][$data[2]])) {
                              $valid = true;
                              $id    = $data[3];
                              $page  = $PLUGIN_HOOKS['redirect_page'][$plugin][$data[2]];
                           }
                           $forcetabnum = 4 ;
                        }
                     }

                     if (isset($data[$forcetabnum])) {
                        $forcetab = 'forcetab='.$data[$forcetabnum];
                     }

                     if ($valid) {
                        glpi_header($CFG_GLPI["root_doc"]."/plugins/$plugin/$page?id=$id&$forcetab");
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
                     $plugin = $data[1];
                     $valid  = false;
                     if (isset($PLUGIN_HOOKS['redirect_page'][$plugin])
                         && !empty($PLUGIN_HOOKS['redirect_page'][$plugin])) {
                        // Simple redirect
                        if (!is_array($PLUGIN_HOOKS['redirect_page'][$plugin])) {
                           if (isset($data[2]) && $data[2]>0) {
                              $valid = true;
                              $id    = $data[2];
                              $page  = $PLUGIN_HOOKS['redirect_page'][$plugin];
                           }
                           $forcetabnum = 3 ;
                        } else { // Complex redirect
                           if (isset($data[2])
                               && !empty($data[2])
                               && isset($data[3])
                               && $data[3] > 0
                               && isset($PLUGIN_HOOKS['redirect_page'][$plugin][$data[2]])
                               && !empty($PLUGIN_HOOKS['redirect_page'][$plugin][$data[2]])) {
                              $valid = true;
                              $id    = $data[3];
                              $page  = $PLUGIN_HOOKS['redirect_page'][$plugin][$data[2]];
                           }
                           $forcetabnum = 4 ;
                        }
                     }

                     if (isset($data[$forcetabnum])) {
                        $forcetab = 'forcetab='.$data[$forcetabnum];
                     }

                     if ($valid) {
                        glpi_header($CFG_GLPI["root_doc"]."/plugins/$plugin/$page?id=$id&$forcetab");
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
      $out .= " ".$units['day']."&nbsp;".Toolbox::ucfirst($LANG['calendar'][12]);
   }

   if ($units['hour']>0) {
      $out .= " ".$units['hour']."&nbsp;".Toolbox::ucfirst($LANG['gmt'][1]);
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

   $time = round(abs($time));
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
 * Determine if a login is valid
 *
 * @param $login string: login to check
 *
 * @return boolean
**/
function isValidLogin($login="") {
   return preg_match( "/^[[:alnum:]@.\-_ ]+$/i", $login);
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
   $value = Html::clean($value);

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
   $value = Html::clean($value);

   return $value;
}




/**
 * Manage planning posted datas (must have begin + duration or end)
 * Compute end if duration is set
 *
 * @param $data array data to process
 *
 * @return processed datas
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




//******************* FUNCTIONS NEVER USED *******************

   /**
    *  Format mail row
    *
    * @param $string string: label string
    * @param $value string: value string
    *
    * @return string
   **/
   function mailRow($string, $value) {

      $row = Toolbox::str_pad( $string . ': ', 25, ' ', STR_PAD_RIGHT).$value."\n";
      return $row;
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

// ******************************************************



?>
