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
 * DocumentType Class
**/
class DocumentType  extends CommonDropdown {

   static $rightname      = 'typedoc';


   function getAdditionalFields() {

      return array(array('name'  => 'icon',
                         'label' => __('Icon'),
                         'type'  => 'icon'),
                   array('name'  => 'is_uploadable',
                         'label' => __('Authorized upload'),
                         'type'  => 'bool'),
                   array('name'    => 'ext',
                         'label'   => __('Extension'),
                         'type'    => 'text',
                         'comment' => __('May be a regular expression')),
                   array('name'  => 'mime',
                         'label' => __('MIME type'),
                         'type'  => 'text'));
   }


   static function getTypeName($nb=0) {
      return _n('Document type', 'Document types', $nb);
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {

      $tab                       = parent::getSearchOptions();

      $tab[3]['table']           = $this->getTable();
      $tab[3]['field']           = 'ext';
      $tab[3]['name']            = __('Extension');
      $tab[3]['datatype']        = 'string';

      $tab[6]['table']           = $this->getTable();
      $tab[6]['field']           = 'icon';
      $tab[6]['name']            = __('Icon');
      $tab[6]['massiveaction']   = false;
      $tab[6]['datatype']        = 'specific';

      $tab[4]['table']           = $this->getTable();
      $tab[4]['field']           = 'mime';
      $tab[4]['name']            = __('MIME type');
      $tab[4]['datatype']        = 'string';

      $tab[5]['table']           = $this->getTable();
      $tab[5]['field']           = 'is_uploadable';
      $tab[5]['name']            = __('Authorized upload');
      $tab[5]['datatype']        = 'bool';

      return $tab;
   }


   /**
    * @since version 0.84
   **/
   static function getSpecificValueToDisplay($field, $values, array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
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
    * @since version 0.84
    *
    * @param $field
    * @param $name               (default '')
    * @param $values             (default '')
    * @param $options      array
   **/
   static function getSpecificValueToSelect($field, $name='', $values='', array $options=array()) {

      if (!is_array($values)) {
         $values = array($field => $values);
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
    * @since version 0.85
   **/
   static function showAvailableTypesLink() {
      global $CFG_GLPI;

      echo " <a href='#' onClick=\"".Html::jsGetElementbyID('documenttypelist').".dialog('open');\">";
      echo "<img src='".$CFG_GLPI["root_doc"]."/pics/info-small.png' title=\"".__s('Help')."\"
             alt=\"".__s('Help')."\" class='calendrier pointer'>";
      echo "</a>";
      Ajax::createIframeModalWindow('documenttypelist',
                                    $CFG_GLPI["root_doc"]."/front/documenttype.list.php",
                                    array('title' => static::getTypeName(Session::getPluralNumber())));
   }
}
?>
