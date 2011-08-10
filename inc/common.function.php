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
// ******************************************************



?>
