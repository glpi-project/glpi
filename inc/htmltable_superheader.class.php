<?php
/*
 * @version $Id: HEADER 15930 2011-10-25 10:47:55Z jmd $
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

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
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file: Damien Touraine
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


/**
 * Only an HTMLTable_ can create an HTMLTable_SuperHeader.
 * @since version 0.84
**/
class HTMLTable_SuperHeader extends HTMLTable_Header {

   /// The headers of each column
   private $headerSets = array();
   /// The table that owns the current super header
   private $table;


   /**
    * @param $table     HTMLTable_ table owning the current header
    * @param $name      (string) the name of the header
    * @param $content   see HTMLTable_Entity#content
    * @param $father    HTMLTable_Header objet (default NULL)
   **/
   function __construct(HTMLTable_ $table, $name, $content,
                        HTMLTable_SuperHeader $father = NULL) {

      $this->table = $table;
      parent::__construct($name, $content, $father);
   }


   /**
    * Compute the Least Common Multiple of two integers
    *
    * @param $first
    * @param $second
    *
    * @return integer LCM of $first and $second
   **/
   private static function LCM($first, $second) {

      $result = $first * $second;
      while($first > 1){
         $reste = $first % $second;
         if ($reste == 0 ){
            $result = $result / $second;
            break;  // leave when LCM is found
         }
         $first = $second;
         $second = $reste;
      }
      return $result;
   }


   /**
    * @see inc/HTMLTable_Header::isSuperHeader()
   **/
   function isSuperHeader() {
      return true;
   }


   /**
    * @see inc/HTMLTable_Header::getHeaderAndSubHeaderName()
   **/
   function getHeaderAndSubHeaderName(&$header_name, &$subheader_name) {

      $header_name    = $this->getName();
      $subheader_name = '';
   }


   /**
    * @see inc/HTMLTable_Header::getCompositeName()
   **/
   function getCompositeName() {
      return $this->getName().':';
   }


   protected function getTable() {
      return $this->table;
   }


   /**
    * compute the total number of current super header colspan: it is the Least Common
    * Multiple of the colspan of each subHeader it owns.
    *
    * @param $number the colspan for this header given by the group
   **/
   function updateNumberOfSubHeader($number) {
      $this->setColSpan(self::LCM($number, $this->getColSpan()));
   }

   /**
    * @see inc/HTMLTable_Header::getCompositeName()
    * The super headers always have to be displayed, conversely to sub headers
    *
    * @return always true
   **/
   function hasToDisplay() {
      return true;
   }

}
?>
