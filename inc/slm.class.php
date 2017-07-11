<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2017 Teclib' and contributors.
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

/** @file
* @brief
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
      return _n('SLM', 'SLMs', $nb);
   }

   /**
    * Force calendar of the SLM if value -1: calendar of the entity
    *
    * @param $calendars_id calendars_id of the ticket
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
      global $DB;

      $query = "DELETE
                FROM `glpi_slas`
                WHERE `slms_id` = '".$this->fields['id']."'";
      $DB->query($query);

      $query = "DELETE
                FROM `glpi_olas`
                WHERE `slms_id` = '".$this->fields['id']."'";
      $DB->query($query);
   }

   /**
    * Print the slm form
    *
    * @param $ID        integer  ID of the item
    * @param $options   array    of possible options:
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    *@return boolean item found
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


   function getSearchOptionsNew() {
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

      return $tab;
   }

   /**
    *  @see CommonGLPI::getMenuContent()
    *
    *  @since version 9.1
    **/
   static function getMenuContent() {

      $menu = [];
      if (Config::canUpdate()) {
         $menu['title']           = self::getTypeName(1);
         $menu['page']            = '/front/slm.php';
         $menu['links']['search'] = '/front/slm.php';
         $menu['links']['add']    = '/front/slm.form.php';

         $menu['options']['sla']['title']           = SLA::getTypeName(1);
         $menu['options']['sla']['page']            = '/front/sla.php';
         $menu['options']['sla']['links']['search'] = '/front/sla.php';

         $menu['options']['ola']['title']           = OLA::getTypeName(1);
         $menu['options']['ola']['page']            = '/front/ola.php';
         $menu['options']['ola']['links']['search'] = '/front/ola.php';

         $menu['options']['slalevel']['title']           = SlaLevel::getTypeName(Session::getPluralNumber());
         $menu['options']['slalevel']['page']            = '/front/slalevel.php';
         $menu['options']['slalevel']['links']['search'] = '/front/slalevel.php';

         $menu['options']['olalevel']['title']           = OlaLevel::getTypeName(Session::getPluralNumber());
         $menu['options']['olalevel']['page']            = '/front/olalevel.php';
         $menu['options']['olalevel']['links']['search'] = '/front/olalevel.php';

      }
      if (count($menu)) {
         return $menu;
      }
      return false;
   }

}
