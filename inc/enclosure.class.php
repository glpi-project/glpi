<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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

use Glpi\Application\View\TemplateRenderer;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Enclosure Class
**/
class Enclosure extends CommonDBTM {
   use Glpi\Features\DCBreadcrumb;
   use Glpi\Features\Clonable;

   // From CommonDBTM
   public $dohistory                   = true;
   static $rightname                   = 'datacenter';

   public function getCloneRelations() :array {
      return [
         Item_Enclosure::class,
         Item_Devices::class,
         NetworkPort::class
      ];
   }

   static function getTypeName($nb = 0) {
      return _n('Enclosure', 'Enclosures', $nb);
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong)
         ->addImpactTab($ong, $options)
         ->addStandardTab('Item_Enclosure', $ong, $options)
         ->addStandardTab('Item_Devices', $ong, $options)
         ->addStandardTab('NetworkPort', $ong, $options)
         ->addStandardTab('Infocom', $ong, $options)
         ->addStandardTab('Contract_Item', $ong, $options)
         ->addStandardTab('Document_Item', $ong, $options)
         ->addStandardTab('Ticket', $ong, $options)
         ->addStandardTab('Item_Problem', $ong, $options)
         ->addStandardTab('Change_Item', $ong, $options)
         ->addStandardTab('Log', $ong, $options);
      return $ong;
   }

   function showForm($ID, $options = []) {
      $this->initForm($ID, $options);
      TemplateRenderer::getInstance()->display('asset_form.html.twig', [
         'item'   => $this,
         'params' => $options,
      ]);
      return true;
   }

   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

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
         'id'                 => '40',
         'table'              => 'glpi_enclosuremodels',
         'field'              => 'name',
         'name'               => _n('Model', 'Models', 1),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '31',
         'table'              => 'glpi_states',
         'field'              => 'completename',
         'name'               => __('Status'),
         'datatype'           => 'dropdown',
         'condition'          => ['is_visible_computer' => 1]
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'serial',
         'name'               => __('Serial number'),
         'datatype'           => 'string',
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'otherserial',
         'name'               => __('Inventory number'),
         'datatype'           => 'string',
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

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
         'id'                 => '23',
         'table'              => 'glpi_manufacturers',
         'field'              => 'name',
         'name'               => Manufacturer::getTypeName(1),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '24',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'linkfield'          => 'users_id_tech',
         'name'               => __('Technician in charge of the hardware'),
         'datatype'           => 'dropdown',
         'right'              => 'own_ticket'
      ];

      $tab[] = [
         'id'                 => '49',
         'table'              => 'glpi_groups',
         'field'              => 'completename',
         'linkfield'          => 'groups_id_tech',
         'name'               => __('Group in charge of the hardware'),
         'condition'          => ['is_assign' => 1],
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '61',
         'table'              => $this->getTable(),
         'field'              => 'template_name',
         'name'               => __('Template name'),
         'datatype'           => 'text',
         'massiveaction'      => false,
         'nosearch'           => true,
         'nodisplay'          => true,
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => Entity::getTypeName(1),
         'datatype'           => 'dropdown'
      ];

      $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

      $tab = array_merge($tab, Datacenter::rawSearchOptionsToAdd(get_class($this)));

      return $tab;
   }

   /**
    * Get already filled places
    *
    * @param string  $itemtype  The item type
    * @param integer $items_id  The item's ID
    *
    * @return array [x => ['depth' => 1, 'orientation' => 0, 'width' => 1, 'hpos' =>0]]
    *               orientation will not be available if depth is > 0.5; hpos will not be available
    *               if width is = 1
    */
   public function getFilled($itemtype = null, $items_id = null) {
      global $DB;

      $iterator = $DB->request([
         'FROM'   => Item_Enclosure::getTable(),
         'WHERE'  => [
            'enclosures_id' => $this->getID()
         ]
      ]);

      $filled = [];
      while ($row = $iterator->next()) {
         if (empty($itemtype) || empty($items_id)
            || $itemtype != $row['itemtype'] || $items_id != $row['items_id']
         ) {
            $filled[$row['position']] = $row['position'];
         }
      }
      return $filled;
   }

   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            Item_Enclosure::class,
         ]
      );
   }


   function prepareInputForAdd($input) {
      if (isset($input["id"]) && ($input["id"] > 0)) {
         $input["_oldID"] = $input["id"];
      }
      unset($input['id']);
      unset($input['withtemplate']);
      return $input;
   }


   static function getIcon() {
      return "fas fa-th";
   }
}
