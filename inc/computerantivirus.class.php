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
 * @since 9.1
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class ComputerAntivirus extends CommonDBChild {

   // From CommonDBChild
   static public $itemtype = 'Computer';
   static public $items_id = 'computers_id';
   public $dohistory       = true;



   static function getTypeName($nb = 0) {
      return _n('Antivirus', 'Antiviruses', $nb);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      global $IS_TWIG;

      // can exists for template
      if (($item->getType() == 'Computer')
          && Computer::canView()) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs'] && !$IS_TWIG) {
            $nb = countElementsInTable('glpi_computerantiviruses',
                                      ["computers_id" => $item->getID(), 'is_deleted' => 0 ]);
         }
         return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      self::showForComputer($item, $withtemplate);
      return true;
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * Duplicate all antirivuses from a computer template to his clone
    *
    * @param $oldid
    * @param $newid
   **/
   static function cloneComputer($oldid, $newid) {
      global $DB;

      $result = $DB->request(
         [
            'FROM'  => ComputerAntivirus::getTable(),
            'WHERE' => ['computers_id' => $oldid],
         ]
      );
      foreach ($result as $data) {
         $antirivus            = new self();
         unset($data['id']);
         $data['computers_id'] = $newid;
         $antirivus->add($data);
      }
   }


   static public function rawSearchOptionsToAdd() {
      $tab = [];
      $name = _n('Antivirus', 'Antiviruses', Session::getPluralNumber());

      $tab[] = [
         'id'                 => 'antivirus',
         'name'               => $name
      ];

      $tab[] = [
         'id'                 => '167',
         'table'              => 'glpi_computerantiviruses',
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
         'table'              => 'glpi_computerantiviruses',
         'field'              => 'antivirus_version',
         'name'               => __('Version'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'text',
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '169',
         'table'              => 'glpi_computerantiviruses',
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
         'table'              => 'glpi_computerantiviruses',
         'field'              => 'is_uptodate',
         'linkfield'          => '',
         'name'               => __('Is up to date'),
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
         'table'              => 'glpi_computerantiviruses',
         'field'              => 'signature_version',
         'name'               => __('Signature database version'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'text',
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      $tab[] = [
         'id'                 => '172',
         'table'              => 'glpi_computerantiviruses',
         'field'              => 'date_expiration',
         'name'               => __('Expiration date'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'date',
         'joinparams'         => [
            'jointype'           => 'child'
         ]
      ];

      return $tab;
   }

   /**
    * Display form for antivirus
    *
    * @param integer $ID      id of the antivirus
    * @param array   $options
    *
    * @return boolean TRUE if form is ok
   **/
   function showForm($ID, $options = []) {

      if (!Session::haveRight("computer", READ)) {
         return false;
      }

      $comp = new Computer();
      if ($ID > 0) {
         $this->check($ID, READ);
         $comp->getFromDB($this->fields['computers_id']);
      } else {
         $this->check(-1, CREATE, $options);
         $comp->getFromDB($options['computers_id']);
      }

      $this->showFormHeader($options);

      if ($this->isNewID($ID)) {
         echo "<input type='hidden' name='computers_id' value='".$options['computers_id']."'>";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Computer')."</td>";
      echo "<td>".$comp->getLink()."</td>";
      if (Plugin::haveImport()) {
         echo "<td>".__('Automatic inventory')."</td>";
         echo "<td>";
         if ($ID && $this->fields['is_dynamic']) {
            Plugin::doHook("autoinventory_information", $this);
         } else {
            echo __('No');
         }
         echo "</td>";
      } else {
         echo "<td colspan='2'></td>";
      }
      echo "</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>".__('Active')."</td>";
      echo "<td>";
      Dropdown::showYesNo('is_active', $this->fields['is_active']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Manufacturer')."</td>";
      echo "<td>";
      Dropdown::show('Manufacturer', ['value' => $this->fields["manufacturers_id"]]);
      echo "</td>";
      echo "<td>".__('Up to date')."</td>";
      echo "<td>";
      Dropdown::showYesNo('is_uptodate', $this->fields['is_uptodate']);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>". __('Antivirus version')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "antivirus_version");
      echo "</td>";
      echo "<td>".__('Signature database version')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "signature_version");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Expiration date')."</td>";
      echo "<td>";
      Html::showDateField("date_expiration", ['value' => $this->fields['date_expiration']]);
      echo "</td>";
      echo "<td colspan='2'></td>";
      echo "</tr>";

      $options['canedit'] = Session::haveRight("computer", UPDATE);
      $this->showFormButtons($options);

      return true;
   }


   /**
    * Print the computers antiviruses
    *
    * @param $comp                  Computer object
    * @param $withtemplate boolean  Template or basic item (default 0)
    *
    * @return void
   **/
   static function showForComputer(Computer $comp, $withtemplate = 0) {
      global $DB;

      $ID = $comp->fields['id'];

      if (!$comp->getFromDB($ID)
          || !$comp->can($ID, READ)) {
         return;
      }
      $canedit = $comp->canEdit($ID);

      if ($canedit
          && !(!empty($withtemplate) && ($withtemplate == 2))) {
         echo "<div class='center firstbloc'>".
               "<a class='vsubmit' href='".ComputerAntivirus::getFormURL()."?computers_id=$ID&amp;withtemplate=".
                  $withtemplate."'>";
         echo __('Add an antivirus');
         echo "</a></div>\n";
      }

      echo "<div class='spaced center'>";

      $result = $DB->request(
         [
            'FROM'  => ComputerAntivirus::getTable(),
            'WHERE' => [
               'computers_id' => $ID,
               'is_deleted'   => 0,
            ],
         ]
      );

      echo "<table class='tab_cadre_fixehov'>";
      $colspan = 7;
      if (Plugin::haveImport()) {
         $colspan++;
      }
      echo "<tr class='noHover'><th colspan='$colspan'>".self::getTypeName($result->numrows()).
           "</th></tr>";

      if ($result->numrows() != 0) {

         $header = "<tr><th>".__('Name')."</th>";
         if (Plugin::haveImport()) {
            $header .= "<th>".__('Automatic inventory')."</th>";
         }
         $header .= "<th>".__('Manufacturer')."</th>";
         $header .= "<th>".__('Antivirus version')."</th>";
         $header .= "<th>".__('Signature database version')."</th>";
         $header .= "<th>".__('Active')."</th>";
         $header .= "<th>".__('Up to date')."</th>";
         $header .= "<th>".__('Expiration date')."</th>";
         $header .= "</tr>";
         echo $header;

         Session::initNavigateListItems(__CLASS__,
                           //TRANS : %1$s is the itemtype name,
                           //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                Computer::getTypeName(1), $comp->getName()));

         $antivirus = new self();
         foreach ($result as $data) {
            $antivirus->getFromDB($data['id']);
            echo "<tr class='tab_bg_2'>";
            echo "<td>".$antivirus->getLink()."</td>";
            if (Plugin::haveImport()) {
               echo "<td>".Dropdown::getYesNo($data['is_dynamic'])."</td>";
            }
            echo "<td>";
            if ($data['manufacturers_id']) {
               echo Dropdown::getDropdownName('glpi_manufacturers',
                                              $data['manufacturers_id'])."</td>";
            } else {
               echo "</td>";
            }
            echo "<td>".$data['antivirus_version']."</td>";
            echo "<td>".$data['signature_version']."</td>";
            echo "<td>".Dropdown::getYesNo($data['is_active'])."</td>";
            echo "<td>".Dropdown::getYesNo($data['is_uptodate'])."</td>";
            echo "<td>".Html::convDate($data['date_expiration'])."</td>";
            echo "</tr>";
            Session::addToNavigateListItems(__CLASS__, $data['id']);
         }
         echo $header;
      } else {
         echo "<tr class='tab_bg_2'><th colspan='$colspan'>".__('No item found')."</th></tr>";
      }

      echo "</table>";
      echo "</div>";
   }

}
