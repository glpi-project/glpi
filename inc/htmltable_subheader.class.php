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
 * @since version 0.84
**/
class HTMLTable_SubHeader extends HTMLTable_Header {

   // The headers of each column
   private $header;


   /**
    * @param $header    HTMLTable_SuperHeader object
    * @param $name
    * @param $content
    * @param $father    HTMLTable_Header object (default NULL)
   **/
   function __construct(HTMLTable_SuperHeader $header, $name,  $content,
                        HTMLTable_Header $father=NULL) {

      $this->header = $header;
      parent::__construct($name, $content, $father);
   }


   function isSuperHeader() {
      return false;
   }


   /**
    * @see inc/HTMLTable_Header::getHeaderAndSubHeaderName()
   **/
  function getHeaderAndSubHeaderName(&$header_name, &$subheader_name) {

      $header_name    = $this->header->getName();
      $subheader_name = parent::getName();
   }


   function getCompositeName() {
      return $this->header->getCompositeName().$this->getName();
   }


   protected function getTable() {
      return $this->header->getTable();
   }


   function getHeader() {
      return $this->header;
   }


   /**
    * @param $numberOfSubHeaders
   **/
   function updateColSpan($numberOfSubHeaders) {
      $this->setColSpan($this->header->getColSpan() / $numberOfSubHeaders);
   }
}
?>
