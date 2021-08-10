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

/**
 * Database Class
**/
class Database extends CommonDBChild {

   // From CommonDBTM
   public $auto_message_on_action   = true;
   static $rightname                = 'database';
   static public $mustBeAttached    = false;

   // From CommonDBChild
   static public $itemtype       = 'DatabaseInstance';
   static public $items_id       = 'databaseinstances_id';

   static function getTypeName($nb = 0) {
      return _n('Database', 'Databases', $nb);
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


   function showForm($ID, $options = []) {
      $rand = mt_rand();
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";

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
      $database = new DatabaseInstance();
      $database->getFromDB($this->fields['databaseinstances_id']);
      echo "<tr>";
      echo "<td>".DatabaseInstance::getTypeName(1)."</td>";
      echo "<td>";
      if (isset($_REQUEST['databaseinstances_id']) && !empty($_REQUEST['databaseinstances_id'])) {
         echo $database->getLink();
         echo Html::hidden('databaseinstances_id', ['value' => $this->fields['databaseinstances_id']]);
      } else {
         $database::dropdown(['value' => $this->fields['databaseinstances_id']]);
      }
      echo "</td>";
      echo "<td><label for='size$rand'>".sprintf(__('%1$s (%2$s)'), __('Size'), __('Mio'))."</label></td>";
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

      $this->showFormButtons($options);

      return true;
   }


   static function getIcon() {
      return "fas fa-database";
   }


   static public function rawSearchOptionsToAdd() {
      $tab = [];
      $name = self::getTypeName(Session::getPluralNumber());

      $tab[] = [
         'id'                 => 'database',
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
         'id'                 => '166',
         'table'              => self::getTable(),
         'field'              => 'size',
         'name'               => sprintf(__('%1$s (%2$s)'), __('Size'), __('Mio')),
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

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if (!$withtemplate
         && ($item->getType() == DatabaseInstance::class)
         && $item->canView()) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = countElementsInTable(
               self::getTable(), [
               'databaseinstances_id' => $item->getID(),
               'is_deleted' => 0
               ]);
         }
         return self::createTabEntry(self::getTypeName(), $nb);
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      self::showForInstance($item);
   }

   /**
    * Display instances for database
    *
    * @param DatabaseInstance $instance Database object
    *
    * @return void|boolean
    **/
   static function showForInstance(DatabaseInstance $instance) {

      $ID = $instance->fields['id'];

      if (!$instance->getFromDB($ID) || !$instance->can($ID, READ)) {
         return false;
      }
      $canedit = $instance->canEdit($ID);

      if ($canedit) {
         echo "<div class='center firstbloc'>".
            "<a class='vsubmit' href='".static::getFormURL()."?databaseinstances_id=$ID'>";
         echo __('Add a database');
         echo "</a></div>\n";
      }

      echo "<div class='center'>";

      $databases = getAllDataFromTable(
         self::getTable(), [
            'WHERE'  => [
               'databaseinstances_id' => $ID,
            ],
            'ORDER'  => 'name'
         ]
      );

      echo "<table class='tab_cadre_fixehov'>";

      Session::initNavigateListItems(
         self::class,
         sprintf(
            __('%1$s = %2$s'), DatabaseInstance::getTypeName(1),
            (empty($instance->fields['name']) ? "($ID)" : $instance->fields['name'])));

      if (empty($databases)) {
         echo "<tr><th>".__('No database')."</th></tr>";
      } else {
         echo "<tr class='noHover'><th colspan='10'>".self::getTypeName(Session::getPluralNumber())."</th></tr>";

         $header = "<tr><th>".__('Name')."</th>";
         $header .= "<th>".sprintf(__('%1$s (%2$s)'), __('Size'), __('Mio'))."</th>";
         $header .= "<th>".__('Is active')."</th>";
         $header .= "<th>".__('Has backup')."</th>";
         $header .= "</tr>";
         echo $header;

         $db = new self();
         foreach ($databases as $row) {
            $db->getFromDB($row['id']);
            echo "<tr class='".((isset($row['is_deleted']) && $row['is_deleted'])?"tab_bg_2_2'":"tab_bg_2")."'>";
            echo "<td>".$db->getLink()."</td>";
            echo "<td>".$row['size']."</td>";
            echo "<td>".Dropdown::getYesNo($db->fields['is_active'])."</td>";
            echo "<td>".Dropdown::getYesNo($db->fields['is_onbackup'])."</td>";
            echo "</tr>";
            Session::addToNavigateListItems('DatabaseInstance', $row['id']);

         }
         echo $header;
      }
      echo "</table>";
      echo "</div>";
   }

   public function prepareInputForAdd($input) {
      if (isset($input['date_lastbackup']) && empty($input['date_lastbackup'])) {
         unset($input['date_lastbackup']);
      }

      if (isset($input['size']) && empty($input['size'])) {
         unset($input['size']);
      }

      return parent::prepareInputForAdd($input);
   }

   static function getAdditionalMenuLinks() {
      $links = [];
      if (static::canView()) {
         $insts = "<i class=\"fas fa-database pointer\" title=\"" . DatabaseInstance::getTypeName(Session::getPluralNumber()) .
            "\"></i><span class=\"sr-only\">" . DatabaseInstance::getTypeName(Session::getPluralNumber()). "</span>";
         $links[$insts] = DatabaseInstance::getSearchURL(false);

      }
      if (count($links)) {
         return $links;
      }
      return false;
   }

   static function getAdditionalMenuOptions() {
      if (static::canView()) {
         return [
            'databaseinstance' => [
               'title' => DatabaseInstance::getTypeName(Session::getPluralNumber()),
               'page'  => DatabaseInstance::getSearchURL(false),
               'links' => [
                  'add'    => '/front/databaseinstance.form.php',
                  'search' => '/front/dabataseinstance.php',
               ]
            ]
         ];
      }
   }
}
