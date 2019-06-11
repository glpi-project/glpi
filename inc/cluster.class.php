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
 * Cluster Class
**/
class Cluster extends CommonDBTM {

   // From CommonDBTM
   public $dohistory                   = true;
   static $rightname                   = 'cluster';

   static function getTypeName($nb = 0) {
      return _n('Cluster', 'Clusters', $nb);
   }

   function defineTabs($options = []) {
      $ong = [];
      $this->addDefaultFormTab($ong)
         ->addStandardTab('Item_Cluster', $ong, $options)
         ->addStandardTab('NetworkPort', $ong, $options)
         ->addStandardTab('Contract_Item', $ong, $options)
         ->addStandardTab('Document_Item', $ong, $options)
         ->addStandardTab('Ticket', $ong, $options)
         ->addStandardTab('Item_Problem', $ong, $options)
         ->addStandardTab('Change_Item', $ong, $options)
         ->addStandardTab('Log', $ong, $options);
      ;
      return $ong;
   }

   function showForm($ID, $options = []) {
      $rand = mt_rand();

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='textfield_name$rand'>".__('Name')."</label></td>";
      echo "<td>";
      $objectName = autoName(
         $this->fields["name"],
         "name",
         (isset($options['withtemplate']) && ( $options['withtemplate']== 2)),
         $this->getType(),
         $this->fields["entities_id"]
      );
      Html::autocompletionTextField(
         $this,
         'name',
         [
            'value'     => $objectName,
            'rand'      => $rand
         ]
      );
      echo "</td>";

      echo "<td><label for='dropdown_states_id$rand'>".__('Status')."</label></td>";
      echo "<td>";
      State::dropdown([
         'value'     => $this->fields["states_id"],
         'entity'    => $this->fields["entities_id"],
         'condition' => ['is_visible_cluster' => 1],
         'rand'      => $rand
      ]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='uuid$rand'>".__('UUID')."</label></td>";
      echo "<td>";
      Html::autocompletionTextField($this, 'uuid', ['rand' => $rand]);
      echo "</td><td><label for='version$rand'>".__('Version')."</label></td>";
      echo "<td>";
      Html::autocompletionTextField($this, 'version', ['rand' => $rand]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_types_id$rand'>".__('Type')."</label></td>";
      echo "<td>";
      ClusterType::dropdown([
         'value'  => $this->fields["clustertypes_id"],
         'entity' => $this->fields["entities_id"],
         'rand'   => $rand
      ]);
      echo "</td>";
      echo "<td><label for='dropdown_autoupdatesystems_id$rand'>".__('Update Source')."</label></td>";
      echo "<td >";
      AutoUpdateSystem::dropdown(['value' => $this->fields["autoupdatesystems_id"], 'rand' => $rand]);
      echo "</td></tr>";
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='dropdown_users_id_tech$rand'>".__('Technician in charge of the hardware')."</label></td>";
      echo "<td>";
      User::dropdown([
         'name'   => 'users_id_tech',
         'value'  => $this->fields["users_id_tech"],
         'right'  => 'own_ticket',
         'entity' => $this->fields["entities_id"],
         'rand'   => $rand
      ]);
      echo "</td>";
      echo "<td><label for='dropdown_groups_id_tech$rand'>".__('Group in charge of the hardware')."</label></td>";
      echo "<td>";
      Group::dropdown([
         'name'      => 'groups_id_tech',
         'value'     => $this->fields['groups_id_tech'],
         'entity'    => $this->fields['entities_id'],
         'condition' => ['is_assign' => 1],
         'rand'      => $rand
      ]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td><label for='comment'>".__('Comments')."</label></td>";
      echo "<td colspan='3' class='middle'>";
      echo "<textarea cols='45' rows='4' id='comment' name='comment' >".
           $this->fields["comment"];
      echo "</textarea></td></tr>";
      echo "</td></tr>\n";

      $this->showFormButtons($options);
      return true;
   }

   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '31',
         'table'              => 'glpi_states',
         'field'              => 'completename',
         'name'               => __('Status'),
         'datatype'           => 'dropdown',
         'condition'          => ['is_visible_cluster' => 1]
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'uuid',
         'name'               => __('UUID'),
         'datatype'           => 'string'
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

      $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

      return $tab;
   }

   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            Item_Cluster::class,
         ]
      );
   }
}
