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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class DatabaseServerInstance extends CommonDBChild {

   // From CommonDBTM
   public $auto_message_on_action   = true;
   static $rightname                   = 'database';

   // From CommonDBChild
   static public $itemtype       = 'DatabaseServer';
   static public $items_id       = 'databaseservers_id';

   static function getTypeName($nb = 0) {
      return _n('Database server instance', 'Database server instances', $nb);
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong)
         ->addImpactTab($ong, $options)
         ->addStandardTab('Document_Item', $ong, $options)
         ->addStandardTab('KnowbaseItem_Item', $ong, $options)
         ->addStandardTab('Notepad', $ong, $options)
         ->addStandardTab('Log', $ong, $options);
      return $ong;
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if (!$withtemplate
          && ($item->getType() == DatabaseServer::class)
          && $item->canView()) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = countElementsInTable(
               self::getTable(), [
                  'databaseservers_id' => $item->getID(),
                  'is_deleted' => 0
               ]);
         }
         return self::createTabEntry(self::getTypeName(), $nb);
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      self::showForDatabase($item);
   }

   /**
    * Display instances for database
    *
    * @param DatabaseServer $database Database object
    *
    * @return void|boolean
   **/
   static function showForDatabase(DatabaseServer $database) {

      $ID = $database->fields['id'];

      if (!$database->getFromDB($ID) || !$database->can($ID, READ)) {
         return false;
      }
      $canedit = $database->canEdit($ID);

      if ($canedit) {
         echo "<div class='center firstbloc'>".
                "<a class='vsubmit' href='".static::getFormURL()."?databaseservers_id=$ID'>";
         echo __('Add an instance');
         echo "</a></div>\n";
      }

      echo "<div class='center'>";

      $instances = getAllDataFromTable(
         self::getTable(), [
            'WHERE'  => [
               'databaseservers_id' => $ID,
            ],
            'ORDER'  => 'name'
         ]
      );

      echo "<table class='tab_cadre_fixehov'>";

      Session::initNavigateListItems(
         self::class,
         sprintf(
            __('%1$s = %2$s'), DatabaseServer::getTypeName(1),
            (empty($database->fields['name']) ? "($ID)" : $database->fields['name'])));

      if (empty($instances)) {
         echo "<tr><th>".__('No instance linked')."</th></tr>";
      } else {
         echo "<tr class='noHover'><th colspan='10'>".self::getTypeName(Session::getPluralNumber())."</th></tr>";

         $header = "<tr><th>".__('Name')."</th>";
         $header .= "<th>"._n('Port', 'Ports', 1)."</th>";
         $header .= "<th>".__('Size')."</th>";
         $header .= "<th>".__('Has backup')."</th>";
         $header .= "</tr>";
         echo $header;

         $inst = new self();
         foreach ($instances as $instance) {
            $inst->getFromDB($instance['id']);
            echo "<tr class='".((isset($instance['is_deleted']) && $instance['is_deleted'])?"tab_bg_2_2'":"tab_bg_2")."'>";
            echo "<td>".$inst->getLink()."</td>";
            echo "<td>".$instance['port']."</td>";
            echo "<td>".$instance['size']."</td>";
            echo "<td>".Dropdown::getYesNo($inst->fields['is_onbackup'])."</td>";
            echo "</tr>";
            Session::addToNavigateListItems('DatabaseServerInstance', $instance['id']);

         }
         echo $header;
      }
      echo "</table>";
      echo "</div>";
   }

   function showForm($ID, $options = []) {
      $rand = mt_rand();
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";

      $rand = mt_rand();
      $tplmark = $this->getAutofillMark('name', $options);

      echo "<td><label for='textfield_name$rand'>".__('Name') . "</label></td>";
      echo "<td>";
      Html::autocompletionTextField(
         $this,
         'name',
         [
            'value'     => $this->fields['name'],
            'rand'      => $rand
         ]
      );
      echo "</td>";
      echo "<td><label for='is_active$rand'>".__('Is active')."</label></td>";
      echo "<td>";
      Dropdown::showYesNo('is_active', $this->fields['is_active']);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='port$rand'>"._n('Port', 'Ports', 1)."</label></td>";
      echo "<td>";
      echo Html::input(
         'port', [
            'id' => 'port'.$rand,
            'type' => 'number',
            'value' => $this->fields['port']
         ]
      );
      echo "</td>";
      echo "<td><label for='size$rand'>".__('Size')."</label></td>";
      echo "<td>";
      echo Html::input(
         'size', [
            'id' => 'size'.$rand,
            'type' => 'number',
            'value' => $this->fields['size']
         ]
      );
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='is_onbackup$rand'>".__('Has backup')."</label></td>";
      echo "<td>";
      Dropdown::showYesNo('is_onbackup', $this->fields['is_onbackup']);
      echo "</td>";
      echo "<td><label for='date_lastbackup$rand'>".__('Last backup date')."</label></td>";
      echo "<td>";
      Html::showDateTimeField(
         "date_lastbackup", [
         'value'      => $this->fields['date_lastbackup'],
         'maybeempty' => true
         ]);
      echo "</td></tr>\n";

      $database = new DatabaseServer();
      $database->getFromDB($this->fields['databaseservers_id']);
      echo "<tr>";
      echo "<td>".DatabaseServer::getTypeName(1)."</td>";
      echo "<td>";
      echo $database->getLink();
      echo Html::hidden('databaseservers_id', ['value' => $this->fields['databaseservers_id']]);
      echo "</td>";
      echo "</tr>";

      $this->showFormButtons($options);

      return true;
   }

   public function prepareInputForAdd($input) {
      if (isset($input['date_lastbackup']) && empty($input['date_lastbackup'])) {
         unset($input['date_lastbackup']);
      }

      if (isset($input['size']) && empty($input['size'])) {
         unset($input['size']);
      }

      return $input;
   }

   static public function rawSearchOptionsToAdd() {
      $tab = [];
      $name = _n('Instance', 'Instances', Session::getPluralNumber());

      $tab[] = [
         'id'                 => 'databseinstance',
         'name'               => $name
      ];

      $tab[] = [
         'id'                 => '167',
         'table'              => self::getTable(),
         'field'              => 'name',
         'name'               => __('Name'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '168',
         'table'              => self::getTable(),
         'field'              => 'port',
         'name'               => _n('Port', 'Ports', 1),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'integer',
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '166',
         'table'              => self::getTable(),
         'field'              => 'size',
         'name'               => __('Size'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'integer',
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '169',
         'table'              => self::getTable(),
         'field'              => 'is_active',
         'linkfield'          => '',
         'name'               => __('Active'),
         'datatype'           => 'bool',
         'joinparams'         => [
            'jointype'           => 'child'
         ],
         'massiveaction'      => false,
         'forcegroupby'       => true,
         'searchtype'         => ['equals']
      ];

      $tab[] = [
         'id'                 => '170',
         'table'              => self::getTable(),
         'field'              => 'is_onbackup',
         'linkfield'          => '',
         'name'               => __('Is on backup'),
         'datatype'           => 'bool',
         'joinparams'         => [
            'jointype'           => 'child'
         ],
         'massiveaction'      => false,
         'forcegroupby'       => true,
         'searchtype'         => ['equals']
      ];

      $tab[] = [
         'id'                 => '171',
         'table'              => self::getTable(),
         'field'              => 'date_lastboot',
         'name'               => __('Last boot date'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'date',
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '172',
         'table'              => self::getTable(),
         'field'              => 'date_lastbackup',
         'name'               => __('Last backup date'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'date',
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      return $tab;
   }
}
