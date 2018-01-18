<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * DocumentType Class
**/
class DocumentType  extends CommonDropdown {

   static $rightname      = 'typedoc';


   function getAdditionalFields() {

      return [['name'  => 'icon',
                         'label' => __('Icon'),
                         'type'  => 'icon'],
                   ['name'  => 'is_uploadable',
                         'label' => __('Authorized upload'),
                         'type'  => 'bool'],
                   ['name'    => 'ext',
                         'label'   => __('Extension'),
                         'type'    => 'text',
                         'comment' => __('May be a regular expression')],
                   ['name'  => 'mime',
                         'label' => __('MIME type'),
                         'type'  => 'text']];
   }


   static function getTypeName($nb = 0) {
      return _n('Document type', 'Document types', $nb);
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'ext',
         'name'               => __('Extension'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'icon',
         'name'               => __('Icon'),
         'massiveaction'      => false,
         'datatype'           => 'specific'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'mime',
         'name'               => __('MIME type'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'is_uploadable',
         'name'               => __('Authorized upload'),
         'datatype'           => 'bool'
      ];

      return $tab;
   }


   /**
    * @since 0.84
   **/
   static function getSpecificValueToDisplay($field, $values, array $options = []) {
      global $CFG_GLPI;

      if (!is_array($values)) {
         $values = [$field => $values];
      }

      switch ($field) {
         case 'icon' :
            if (!empty($values[$field])) {
               return "&nbsp;<img style='vertical-align:middle;' alt='' src='".
                      $CFG_GLPI["typedoc_icon_dir"]."/".$values[$field]."'>";
            }
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }


   /**
    * @since 0.84
    *
    * @param $field
    * @param $name               (default '')
    * @param $values             (default '')
    * @param $options      array
   **/
   static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = []) {

      if (!is_array($values)) {
         $values = [$field => $values];
      }
      $options['display'] = false;
      switch ($field) {
         case 'icon' :
            return Dropdown::dropdownIcons($name, $values[$field],
                                           GLPI_ROOT."/pics/icones", false);
      }
      return parent::getSpecificValueToSelect($field, $name, $values, $options);
   }


   /**
    * @since 0.85
    *
    * @param array $options list of options with theses possible keys:
    *                        - bool 'display', echo the generated html or return it
   **/
   static function showAvailableTypesLink($options = []) {
      global $CFG_GLPI;

      $p['display'] = true;

      //merge default options with options parameter
      $p = array_merge($p, $options);

      $display = "&nbsp;";
      $display .= "<a href='#' onClick=\"".Html::jsGetElementbyID('documenttypelist').
                  ".dialog('open'); return false;\" class='fa fa-info pointer' title='" . __s('Help') . "' >";
      $display .= "<span class='sr-only'>".__s('Help')."></span>";
      $display .= "</a>";
      $display .= Ajax::createIframeModalWindow('documenttypelist',
                                                $CFG_GLPI["root_doc"]."/front/documenttype.list.php",
                                                ['title'   => static::getTypeName(Session::getPluralNumber()),
                                                 'display' => false]);

      if ($p['display']) {
         echo $display;
      } else {
         return $display;
      }
   }
}
