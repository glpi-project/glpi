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
 * Datacenter Class
**/
class Datacenter extends CommonDBTM {

   // From CommonDBTM
   public $dohistory                   = true;
   static $rightname                   = 'datacenter';

   static function getTypeName($nb = 0) {
      //TRANS: Test of comment for translation (mark : //TRANS)
      return _n('Data center', 'Data centers', $nb);
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong)
         ->addStandardTab('DCRoom', $ong, $options);
      return $ong;
   }

   function showForm($ID, $options = []) {
      $rand = mt_rand();

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='textfield_name$rand'>".__('Name')."</label></td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name", ['rand' => $rand]);
      echo "</td>";

      echo "<td><label for='dropdown_locations_id$rand'>".__('Location')."</label></td>";
      echo "<td>";
      Location::dropdown([
         'value'  => $this->fields["locations_id"],
         'entity' => $this->fields["entities_id"],
         'rand'   => $rand
      ]);
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);
      return true;
   }

   function rawSearchOptions() {
      global $CFG_GLPI;

      $tab = [];

      $tab[] = [
         'id'                 => 'common',
         'name'               => __('Characteristics')
      ];

      $tab[] = [
         'id'                 => '1',
         'table'              => $this->getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'datatype'           => 'itemlink',
         'massiveaction'      => false // implicit key==1
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false, // implicit field is id
         'datatype'           => 'number'
      ];

      $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

      $tab[] = [
         'id'                 => '19',
         'table'              => $this->getTable(),
         'field'              => 'date_mod',
         'name'               => __('Last update'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '121',
         'table'              => $this->getTable(),
         'field'              => 'date_creation',
         'name'               => __('Creation date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }

   static function getAdditionalMenuLinks() {
      $links = [];
      if (static::canView()) {
         $rooms = "<i class=\"fa fa-building pointer\" title=\"" . DCRoom::getTypeName(Session::getPluralNumber()) .
            "\"></i><span class=\"sr-only\">" . DCRoom::getTypeName(Session::getPluralNumber()). "</span>";
         $links[$rooms] = DCRoom::getSearchURL(false);

      }
      if (count($links)) {
         return $links;
      }
      return false;
   }

   static function getAdditionalMenuOptions() {
      if (static::canView()) {
         return [
            'dcroom' => [
               'title' => DCRoom::getTypeName(Session::getPluralNumber()),
               'page'  => DCRoom::getSearchURL(false),
               'links' => [
                  'add'    => '/front/dcroom.form.php',
                  'search' => '/front/dcroom.php',
               ]
            ]
         ];
      }
   }

}
