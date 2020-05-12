<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

class DomainRelation extends CommonDropdown {
   const BELONGS = 1;
   const MANAGE = 2;
   // From CommonDBTM
   public $dohistory                   = true;
   static $rightname                   = 'domain';

   static public $knowrelations = [
      [
         'id'        => self::BELONGS,
         'name'      => 'Belongs',
         'comment'   => 'Item belongs to domain'
      ], [
         'id'        => self::MANAGE,
         'name'      => 'Manage',
         'comment'   => 'Item manages domain'
      ]
   ];

   static function getTypeName($nb = 0) {
      return _n('Domain relation', 'Domains relations', $nb);
   }

   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Domain_Item', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }

   /**
    * Print the form
    *
    * @param $ID        integer ID of the item
    * @param $options   array of possible options:
    *     - target for the Form
    *     - withtemplate : template or basic item
    *
    * @return void
    **/
   function showForm($ID, $options = []) {

      $rowspan = 3;
      if ($ID > 0) {
         $rowspan++;
      }

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";

      echo "<td>" . __('Comments')."</td>";
      echo "<td>
      <textarea cols='45' rows='10' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      $this->showFormButtons($options);
      return true;
   }

   public static function getDefaults() {
      return array_map(
         function($e) {
            $e['is_recursive'] = 1;
            return $e;
         },
         self::$knowrelations
      );
   }

   public function pre_deleteItem() {
      if (in_array([self::BELONGS, self::MANAGE], $this->fields['id'])) {
         //keep defaults
         return false;
      }
      return true;
   }
}
