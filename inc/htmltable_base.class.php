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

class HTMLTable_UnknownHeader extends Exception {}
class HTMLTable_UnknownHeaders extends Exception {}
class HTMLTable_UnknownHeadersOrder extends Exception {}

/**
 * @since version 0.84
**/
abstract class HTMLTable_Base  {

   private $headers = array();
   private $headers_order = array();
   private $headers_sub_order = array();
   private $super;


   /**
    * @param $super
   **/
   function __construct($super) {
      $this->super = $super;
   }


   /**
    * @param unknown_type $super_header_name
   **/
   protected function getSuperHeader($super_header_name) {
      return $this->getHeader($super_header_name);
   }


   /**
    * @param $header_object         HTMLTable_Header object
    * @param $allow_super_header    (false by default
   **/
   function appendHeader(HTMLTable_Header $header_object, $allow_super_header=false) {

      if (!$header_object instanceof HTMLTable_Header) {
         throw new Exception('Implementation error: appendHeader requires HTMLTable_Header as parameter');
      }
      $header_object->getHeaderAndSubHeaderName($header_name, $subHeader_name);
      if ($header_object->isSuperHeader()
          && (!$this->super)
          && (!$allow_super_header)) {
         throw new Exception(sprintf('Implementation error : invalid super header name "%s"',
                                     $header_name));
      }
      if (!$header_object->isSuperHeader()
          && $this->super) {
         throw new Exception(sprintf('Implementation error : invalid super header name "%s"',
                                     $header_name));
      }

      if (!isset($this->headers[$header_name])) {
         $this->headers[$header_name]           = array();
         $this->headers_order[]                 = $header_name;
         $this->headers_sub_order[$header_name] = array();
      }
      if (!isset($this->headers[$header_name][$subHeader_name])) {
         $this->headers_sub_order[$header_name][] = $subHeader_name;
      }
      $this->headers[$header_name][$subHeader_name] = $header_object;
      return $header_object;
   }


   /**
    * @param $header_name
    * @param $sub_header_name (default '')
   **/
   function getHeader($header_name, $sub_header_name='') {

      if (isset($this->headers[$header_name][$sub_header_name])) {
         return $this->headers[$header_name][$sub_header_name];
      }
      if ($header_name == '') {
         foreach ($this->headers as $name => $headers) {
            if (isset($headers[$sub_header_name])) {
               return $headers[$sub_header_name];
            }
         }
      }
      throw new HTMLTable_UnknownHeader($header_name.":".$sub_header_name);
   }


   /**
    * @param $header_name  (default '')
   **/
   function getHeaders($header_name='') {

      if (empty($header_name)) {
         return $this->headers;
      }
      if (isset($this->headers[$header_name])) {
         return $this->headers[$header_name];
      }
      throw new HTMLTable_UnknownHeaders($header_name);
   }


   /**
    * @param $header_name  (default '')
   **/
   function getHeaderOrder($header_name='') {

      if (empty($header_name)) {
         return $this->headers_order;
      }
      if (isset($this->headers_sub_order[$header_name])) {
         return $this->headers_sub_order[$header_name];
      }
      throw new  HTMLTable_UnknownHeadersOrder($header_name);

   }
}
?>
