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

/**
 * @since 9.2
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * SLM Class
**/
class SLM extends CommonDBTM {

   // From CommonDBTM
   public $dohistory                   = true;

   static protected $forward_entity_to = ['SLA', 'OLA'];

   static $rightname                   = 'slm';

   const TTR = 0; // Time to resolve
   const TTO = 1; // Time to own

   static function getTypeName($nb = 0) {
      return _n('Service level', 'Service levels', $nb);
   }

   /**
    * Force calendar of the SLM if value -1: calendar of the entity
    *
    * @param integer $calendars_id calendars_id of the ticket
   **/
   function setTicketCalendar($calendars_id) {

      if ($this->fields['calendars_id'] == -1) {
         $this->fields['calendars_id'] = $calendars_id;
      }
   }

   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('SLA', $ong, $options);
      $this->addStandardTab('OLA', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            Sla::class,
            Ola::class,
         ]
      );
   }

   /**
    * Print the slm form
    *
    * @param integer $ID ID of the item
    * @param array   $options of possible options:
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    * @return boolean item found
   **/
   function showForm($ID, $options = []) {

      $rowspan = 2;

      $this->initForm($ID, $options);
      $this->showFormHeader($options);
      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name", ['value' => $this->fields["name"]]);
      echo "<td rowspan='".$rowspan."'>".__('Comments')."</td>";
      echo "<td rowspan='".$rowspan."'>
            <textarea cols='45' rows='8' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'><td>".__('Calendar')."</td>";
      echo "<td>";

      Calendar::dropdown(['value'      => $this->fields["calendars_id"],
                          'emptylabel' => __('24/7'),
                          'toadd'      => ['-1' => __('Calendar of the ticket')]]);
      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;
   }


   function rawSearchOptions() {
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
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => 'glpi_calendars',
         'field'              => 'name',
         'name'               => __('Calendar'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      return $tab;
   }


   static function getMenuContent() {

      $menu = [];
      if (static::canView()) {
         $menu['title']           = self::getTypeName(2);
         $menu['page']            = static::getSearchURL(false);
         $menu['links']['search'] = static::getSearchURL(false);
         if (static::canCreate()) {
            $menu['links']['add'] = Slm::getFormURL(false);
         }

         $menu['options']['sla']['title']           = SLA::getTypeName(1);
         $menu['options']['sla']['page']            = SLA::getSearchURL(false);
         $menu['options']['sla']['links']['search'] = SLA::getSearchURL(false);

         $menu['options']['ola']['title']           = OLA::getTypeName(1);
         $menu['options']['ola']['page']            = OLA::getSearchURL(false);
         $menu['options']['ola']['links']['search'] = OLA::getSearchURL(false);

         $menu['options']['slalevel']['title']           = SlaLevel::getTypeName(Session::getPluralNumber());
         $menu['options']['slalevel']['page']            = SlaLevel::getSearchURL(false);
         $menu['options']['slalevel']['links']['search'] = SlaLevel::getSearchURL(false);

         $menu['options']['olalevel']['title']           = OlaLevel::getTypeName(Session::getPluralNumber());
         $menu['options']['olalevel']['page']            = OlaLevel::getSearchURL(false);
         $menu['options']['olalevel']['links']['search'] = OlaLevel::getSearchURL(false);

      }
      if (count($menu)) {
         return $menu;
      }
      return false;
   }

}
