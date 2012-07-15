<?php
/*
 * @version $Id$
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
 *
 * The base entity for the table. The entity is the base of kind of cell (header or not). It
 * provides facilities to manage the cells such as attributs or specific content (mixing of strings
 * and call of method during table display)
**/
abstract class HTMLTable_Entity {

   private $html_id;
   private $html_style;
   private $html_class = array();

   private $content;


   /**
    * Constructor of an entity
    *
    * @param $content The content of a cell, header, ... Can simply be a string. But it can also
    *                 be a call to a specific function during the rendering of the table in case
    *                 of direct display function (for instance: Dropdown::showInteger). A function
    *                 call is an array containing two elements : 'function', the name the function
    *                 and 'parameters', an array of the parameters given to the function.
   **/
   function __construct($content) {
      $this->content = $content;
   }


   /**
    * @param $origin
   **/
   function copyAttributsFrom(HTMLTable_Entity $origin) {

      $this->html_id    = $origin->html_id;
      $this->html_style = $origin->html_style;
      $this->html_class = $origin->html_class;
    }


   /**
    * @param $html_id
   **/
   function setHTMLID($html_id) {
      $this->html_id = $html_id;
   }


   /**
    * userfull ? function never called
    *
    * @param $html_style
   **/
   function setHTMLStyle($html_style) {
      $this->html_style = $html_style;
   }


   /**
    * @param $html_class
   **/
   function setHTMLClass($html_class) {
      if (is_string($html_class)) {
         $this->html_class[] = $html_class;
      }
   }


   function displayEntityAttributs() {

      if (!empty($this->html_id)) {
         echo " id='".$this->html_id."'";
      }
      if (!empty($this->html_style)) {
         echo " style='".$this->html_style."'";
      }
      if (count($this->html_class) > 0) {
         echo " class='".implode(' ', $this->html_class)."'";
      }
   }


   /**
    * @param $content
   **/
   function setContent($content) {
      $this->content = $content;
   }


   function displayContent() {

      if (is_string($this->content)) {
         echo $this->content;
      } else if (is_array($this->content)) {
         foreach ($this->content as $content) {
            if (is_string($content)) {
               echo $content;
            } else if (isset($content['function'])) {
               if (isset($content['parameters'])) {
                  $parameters = $content['parameters'];
               } else {
                  $parameters = array();
               }
               call_user_func_array ($content['function'], $parameters);
            }
         }
      }
   }
}
?>
