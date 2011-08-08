<?php
/*
 * @version $Id:
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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

// class Toolbox
class Toolbox {


   /**
    * Convert first caracter in upper
    *
    * @since version 0.83
    *
    * @param $str string to change
    *
    * @return string changed
    */
   static function ucfirst($str) {

      // for foreign language
      $str[0] = mb_strtoupper($str[0], 'UTF-8');
      return $str;
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
   static function strpos($str, $tofound, $offset=0) {
      return mb_strpos($str, $tofound, $offset, "UTF-8");
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
   static function str_pad($input, $pad_length, $pad_string = " ", $pad_type = STR_PAD_RIGHT) {

       $diff = strlen($input) - self::strlen($input);
       return str_pad($input, $pad_length+$diff, $pad_string, $pad_type);
   }


   /**
    * strlen function for utf8 string
    *
    * @param $str string: string
    *
    * @return length of the string
   **/
   static function strlen($str) {
      return mb_strlen($str, "UTF-8");
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
   static function substr($str, $start, $length=-1) {

      if ($length==-1) {
         $length = self::strlen($str)-$start;
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
   static function strtolower($str) {
      return mb_strtolower($str, "UTF-8");
   }


   /**
    * strtoupper function for utf8 string
    *
    * @param $str string: string
    *
    * @return upper case string
   **/
   static function strtoupper($str) {
      return mb_strtoupper($str, "UTF-8");
   }

}
?>