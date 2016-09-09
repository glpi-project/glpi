<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.
 
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

/** @file
* @brief
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class HTMLTableUnknownHeader       extends Exception {}
class HTMLTableUnknownHeaders      extends Exception {}
class HTMLTableUnknownHeadersOrder extends Exception {}

/**
 * @since version 0.84
**/
abstract class HTMLTableBase  {

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
    * @param $header_object         HTMLTableHeader object
    * @param $allow_super_header    (false by default
   **/
   function appendHeader(HTMLTableHeader $header_object, $allow_super_header=false) {

      if (!$header_object instanceof HTMLTableHeader) {
         throw new Exception('Implementation error: appendHeader requires HTMLTableHeader as parameter');
      }
      $header_name    = '';
      $subHeader_name = '';
      $header_object->getHeaderAndSubHeaderName($header_name, $subHeader_name);
      if ($header_object->isSuperHeader()
          && (!$this->super)
          && (!$allow_super_header)) {
         throw new Exception(sprintf('Implementation error: invalid super header name "%s"',
                                     $header_name));
      }
      if (!$header_object->isSuperHeader()
          && $this->super) {
         throw new Exception(sprintf('Implementation error: invalid super header name "%s"',
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
    * Internal test to see if we can add an header. For instance, we can only add a super header
    * to a table if there is no group defined. And we can only create a sub Header to a group if
    * it contains no row
   **/
   abstract function tryAddHeader();


   /**
    * create a new HTMLTableHeader
    *
    * Depending of "$this" type, this head will be an HTMLTableSuperHeader of a HTMLTableSubHeader
    *
    * @param $name      string            The name that can be refered by getHeaderByName()
    * @param $content   string or array   The content (see HTMLTableEntity#content) of the header
    * @param $super                       HTMLTableSuperHeader object:
    *                                     the header that contains this new header only used
    *                                     for HTMLTableSubHeader (default NULL)
    *                                     (ie: $this instanceof HTMLTableGroup)
    * @param $father                      HTMLTableHeader object: the father of the current header
    *                                     (default NULL)
    *
    * @exception Exception                If there is no super header while creating a sub
    *                                     header or a super header while creating a super one
    *
    * @return the HTMLTableHeader        that have been created
   **/
   function addHeader($name, $content, HTMLTableSuperHeader $super=NULL,
                      HTMLTableHeader $father=NULL) {

      $this->tryAddHeader();
      if (is_null($super)) {
         if (!$this->super) {
            throw new Exception('A sub header requires a super header');
         }
         return $this->appendHeader(new HTMLTableSuperHeader($this, $name, $content,
                                                              $father));
      }
      if ($this->super) {
         throw new Exception('Cannot attach a super header to another header');
      }
      return $this->appendHeader(new HTMLTableSubHeader($super, $name, $content, $father));
   }


   /**
    * @param $name
   **/
   function getSuperHeaderByName($name) {
      return $this->getHeaderByName($name, '');
   }


   /**
    * @param $name
    * @param $sub_name (default NULL)
   **/
   function getHeaderByName($name, $sub_name=NULL) {

      if (is_string($sub_name)) {
         if (isset($this->headers[$name][$sub_name])) {
            return $this->headers[$name][$sub_name];
         }
         throw new HTMLTableUnknownHeader($name.':'.$sub_name);
      }

      foreach ($this->headers as $header) {
         if (isset($header[$name])) {
            return $header[$name];
         }
      }
      throw new HTMLTableUnknownHeader($name);
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
      throw new HTMLTableUnknownHeaders($header_name);
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
      throw new  HTMLTableUnknownHeadersOrder($header_name);

   }
}
?>
