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
