<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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
   die("Sorry. You can't access directly to this file");
}


/**
 * @since version 0.84
 *
 * The base entity for the table. The entity is the base of kind of cell (header or not). It
 * provides facilities to manage the cells such as attributs or specific content (mixing of strings
 * and call of method during table display)
**/
abstract class HTMLTableEntity {

   private $html_id    = '';
   private $html_style = array();
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
   function copyAttributsFrom(HTMLTableEntity $origin) {

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
      if (is_array($html_style)) {
         $this->html_style = array_merge($this->html_style, $html_style);
      } else {
         $this->html_style[] = $html_style;
      }
   }


   /**
    * @param $html_class
   **/
   function setHTMLClass($html_class) {
      if (is_array($html_class)) {
         $this->html_class = array_merge($this->html_class, $html_class);
      } else {
         $this->html_class[] = $html_class;
      }
   }


   /**
    * @param $options   array
   **/
   function displayEntityAttributs(array $options=array()) {

      $id = $this->html_id;
      if (isset($options['id'])) {
         $id = $options['id'];
      }
      if (!empty($id)) {
         echo " id='$id'";
      }

      $style = $this->html_style;
      if (isset($options['style'])) {
         if (is_array($options['style'])) {
            $style = array_merge($style, $options['style']);
         } else {
            $style[] = $options['style'];
         }
      }
      if (count($style) > 0) {
         echo " style='".implode(';', $style)."'";
      }

      $class = $this->html_class;
      if (isset($options['class'])) {
         if (is_array($options['class'])) {
            $class = array_merge($class, $options['class']);
         } else {
            $class[] = $options['class'];
         }
      }
      if (count($class) > 0) {
         echo " class='".implode(' ', $class)."'";
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
         // Manage __RAND__ to be computed on display
         $content = $this->content;
         $content = str_replace('__RAND__',mt_rand(), $content);
         echo $content;
      } else if (is_array($this->content)) {
         foreach ($this->content as $content) {
            if (is_string($content)) {
               // Manage __RAND__ to be computed on display
               $content = str_replace('__RAND__',mt_rand(), $content);
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
