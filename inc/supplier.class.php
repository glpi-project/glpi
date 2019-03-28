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
 * Supplier class (suppliers)
**/
class Supplier extends CommonDBTM {

   // From CommonDBTM
   public $dohistory           = true;

   static $rightname           = 'contact_enterprise';
   protected $usenotepad       = true;



   /**
    * Name of the type
    *
    * @param $nb : number of item in the type
   **/
   static function getTypeName($nb = 0) {
      return _n('Supplier', 'Suppliers', $nb);
   }


   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            Change_Supplier::class,
            Contact_Supplier::class,
            Contract_Supplier::class,
            Problem_Supplier::class,
            ProjectTaskTeam::class,
            ProjectTeam::class,
            Supplier_Ticket::class,
         ]
      );

      // Ticket rules use suppliers_id_assign
      Rule::cleanForItemAction($this, 'suppliers_id%');
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('Contact_Supplier', $ong, $options);
      $this->addStandardTab('Contract_Supplier', $ong, $options);
      $this->addStandardTab('Infocom', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('Ticket', $ong, $options);
      $this->addStandardTab('Item_Problem', $ong, $options);
      $this->addStandardTab('Change_Item', $ong, $options);
      $this->addStandardTab('Link', $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('KnowbaseItem_Item', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * Print the enterprise form
    *
    * @param $ID Integer : Id of the computer or the template to print
    * @param $options array
    *     - target form target
    *     - withtemplate boolean : template or basic item
    *
    *@return Nothing (display)
   **/
   function showForm($ID, $options = []) {

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>".__('Third party type')."</td>";
      echo "<td>";
      SupplierType::dropdown(['value' => $this->fields["suppliertypes_id"]]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>". __('Phone')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "phonenumber");
      echo "</td>";
      echo "<td rowspan='8' class='middle right'>".__('Comments')."</td>";
      echo "<td class='center middle' rowspan='8'>";
      echo "<textarea cols='45' rows='13' name='comment' >".$this->fields["comment"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Fax')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "fax");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Website')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "website");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._n('Email', 'Emails', 1)."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "email");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='middle'>".__('Address')."</td>";
      echo "<td class='middle'>";
      echo "<textarea cols='37' rows='3' name='address'>".$this->fields["address"]."</textarea>";
      echo "</td></tr>";

      echo "<tr class='tab_bg_1' style='white-space: nowrap'>";
      echo "<td>".__('Postal code')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "postcode", ['size' => 10]);
      echo "&nbsp;&nbsp;". __('City'). "&nbsp;";
      Html::autocompletionTextField($this, "town", ['size' => 23]);
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._x('location', 'State')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "state");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Country')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "country");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Active')."</td>";
      echo "<td>";
      Dropdown::showYesNo('is_active', $this->fields['is_active']);
      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;

   }

   static function dropdown($options = []) {
      $condition = ['is_active' => true];
      $options['condition'] = (isset($options['condition']) ? $options['condition'] + $condition : $condition);
      return Dropdown::show(get_called_class(), $options);
   }

   /**
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem = null) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);
      if ($isadmin) {
         $actions['Contact_Supplier'.MassiveAction::CLASS_ACTION_SEPARATOR.'add']
               = _x('button', 'Add a contact');
      }
      return $actions;
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
         'id'                 => '3',
         'table'              => $this->getTable(),
         'field'              => 'address',
         'name'               => __('Address'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '10',
         'table'              => $this->getTable(),
         'field'              => 'fax',
         'name'               => __('Fax'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'town',
         'name'               => __('City'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '14',
         'table'              => $this->getTable(),
         'field'              => 'postcode',
         'name'               => __('Postal code'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'state',
         'name'               => _x('location', 'State'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => $this->getTable(),
         'field'              => 'country',
         'name'               => __('Country'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '4',
         'table'              => $this->getTable(),
         'field'              => 'website',
         'name'               => __('Website'),
         'datatype'           => 'weblink'
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'phonenumber',
         'name'               => __('Phone'),
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'email',
         'name'               => _n('Email', 'Emails', 1),
         'datatype'           => 'email'
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => 'glpi_suppliertypes',
         'field'              => 'name',
         'name'               => __('Third party type'),
         'datatype'           => 'dropdown'
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

      if ($_SESSION["glpinames_format"] == User::FIRSTNAME_BEFORE) {
         $name1 = 'firstname';
         $name2 = 'name';
      } else {
         $name1 = 'name';
         $name2 = 'firstname';
      }

      $tab[] = [
         'id'                 => '8',
         'table'              => 'glpi_contacts',
         'field'              => 'completename',
         'name'               => _n('Associated contact', 'Associated contacts', Session::getPluralNumber()),
         'forcegroupby'       => true,
         'datatype'           => 'itemlink',
         'massiveaction'      => false,
         'computation'        => "CONCAT(TABLE.`$name1`, ' ', TABLE.`$name2`)",
         'computationgroupby' => true,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_contacts_suppliers',
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => __('Entity'),
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '86',
         'table'              => $this->getTable(),
         'field'              => 'is_recursive',
         'name'               => __('Child entities'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '29',
         'table'              => 'glpi_contracts',
         'field'              => 'name',
         'name'               => _n('Associated contract', 'Associated contracts', Session::getPluralNumber()),
         'forcegroupby'       => true,
         'datatype'           => 'itemlink',
         'massiveaction'      => false,
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_contracts_suppliers',
               'joinparams'         => [
                  'jointype'           => 'child'
               ]
            ]
         ]
      ];

      // add objectlock search options
      $tab = array_merge($tab, ObjectLock::rawSearchOptionsToAdd(get_class($this)));

      $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

      return $tab;
   }


   /**
    * Get links for an enterprise (website / edit)
    *
    * @param $withname boolean : also display name ? (false by default)
   **/
   function getLinks($withname = false) {
      global $CFG_GLPI;

      $ret = '&nbsp;&nbsp;&nbsp;&nbsp;';

      if ($withname) {
         $ret .= $this->fields["name"];
         $ret .= "&nbsp;&nbsp;";
      }

      if (!empty($this->fields['website'])) {
         $ret .= "<a href='".Toolbox::formatOutputWebLink($this->fields['website'])."' target='_blank'>
                  <img src='".$CFG_GLPI["root_doc"]."/pics/web.png' class='middle' alt=\"".
                   __s('Web')."\" title=\"".__s('Web')."\"></a>&nbsp;&nbsp;";
      }

      if ($this->can($this->fields['id'], READ)) {
         $ret .= "<a href='".Supplier::getFormURLWithID($this->fields['id'])."'>
                  <img src='".$CFG_GLPI["root_doc"]."/pics/edit.png' class='middle' alt=\"".
                   __s('Update')."\" title=\"".__s('Update')."\"></a>";
      }
      return $ret;
   }


   /**
    * Print the HTML array for infocoms linked
    *
    *@return Nothing (display)
    *
   **/
   function showInfocoms() {
      global $DB, $CFG_GLPI;

      $instID = $this->fields['id'];
      if (!$this->can($instID, READ)) {
         return false;
      }

      $types_iterator = InfoCom::getTypes(['suppliers_id' => $instID]);
      $number = count($types_iterator);

      echo "<div class='spaced'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>";
      Html::printPagerForm();
      echo "</th><th colspan='3'>";
      if ($number == 0) {
         echo __('No associated item');
      } else {
         echo _n('Associated item', 'Associated items', $number);
      }
      echo "</th></tr>";
      echo "<tr><th>".__('Type')."</th>";
      echo "<th>".__('Entity')."</th>";
      echo "<th>".__('Name')."</th>";
      echo "<th>".__('Serial number')."</th>";
      echo "<th>".__('Inventory number')."</th>";
      echo "</tr>";

      $num = 0;
      while ($row = $types_iterator->next()) {
         $itemtype = $row['itemtype'];

         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }

         if ($item->canView()) {
            $linktype  = $itemtype;
            $linkfield = 'id';
            $itemtable = getTableForItemType($itemtype);

            $criteria = [
               'SELECT'       => [],
               'FROM'         => 'glpi_infocoms',
               'INNER JOIN'   => [
                  $itemtable  => [
                     'ON' => [
                        'glpi_infocoms'   => 'items_id',
                        $itemtable        => 'id'
                     ]
                  ]
               ]
            ];

            // Set $linktype for entity restriction AND link to search engine
            if ($itemtype == 'Cartridge') {
               $criteria['INNER JOIN']['glpi_cartridgeitems'] = [
                  'ON' => [
                     'glpi_cartridgeitems'   => 'id',
                     'glpi_cartridges'       => 'cartridgeitems_id'
                  ]
               ];

               $linktype  = 'CartridgeItem';
               $linkfield = 'cartridgeitems_id';
            }

            if ($itemtype == 'Consumable') {
               $criteria['INNER JOIN']['glpi_consumableitems'] = [
                  'ON' => [
                     'glpi_consumableitems'  => 'id',
                     'glpi_consumables'      => 'cartridgeitems_id'
                  ]
               ];

               $linktype  = 'ConsumableItem';
               $linkfield = 'consumableitems_id';
            }

            if ($itemtype == 'Item_DeviceControl') {
               $criteria['INNER JOIN']['glpi_devicecontrols'] = [
                  'ON' => [
                     'glpi_items_devicecontrols'   => 'devicecontrols_id',
                     'glpi_devicecontrols'         => 'id'
                  ]
               ];

               $linktype = 'DeviceControl';
               $linkfield = 'devicecontrols_id';
            }

            $linktable = getTableForItemType($linktype);

            $criteria['SELECT'] = [
               'glpi_infocoms.entities_id',
               $linktype::getNameField(),
               "$itemtable.*"
            ];

            $criteria['WHERE'] = [
               'glpi_infocoms.itemtype'      => $itemtype,
               'glpi_infocoms.suppliers_id'  => $instID,
            ] + getEntitiesRestrictCriteria($linktable);

            $criteria['ORDERBY'] = [
               'glpi_infocoms.entities_id',
               "$linktable." . $linktable::getNameField()
            ];

            $iterator = $DB->request($criteria);
            $nb = count($iterator);

            if ($nb > $_SESSION['glpilist_limit']) {
               echo "<tr class='tab_bg_1'>";
               $title = $item->getTypeName($nb);
               if ($nb > 0) {
                  $title = sprintf(__('%1$s: %2$s'), $title, $nb);
               }
               echo "<td class='center'>".$title."</td>";
               echo "<td class='center' colspan='2'>";
               $opt = ['order'      => 'ASC',
                            'is_deleted' => 0,
                            'reset'      => 'reset',
                            'start'      => 0,
                            'sort'       => 80,
                            'criteria'   => [0 => ['value'      => '$$$$'.$instID,
                                                             'searchtype' => 'contains',
                                                             'field'      => 53]]];
               $link = $linktype::getSearchURL();
               $link.= (strpos($link, '?') ? '&amp;':'?');

               echo "<a href='$link" .
                     Toolbox::append_params($opt). "'>" . __('Device list')."</a></td>";

               echo "<td class='center'>-</td><td class='center'>-</td></tr>";

            } else if ($nb) {
               $prem = true;
               while ($data = $iterator->next()) {
                  $name = $data[$linktype::getNameField()];
                  if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                     $name = sprintf(__('%1$s (%2$s)'), $name, $data["id"]);
                  }
                  $link = $linktype::getFormURLWithID($data[$linkfield]);
                  $name = "<a href='$link'>".$name."</a>";

                  echo "<tr class='tab_bg_1";
                  if (isset($data['is_template']) && $data['is_template'] == 1) {
                     echo " linked-template";
                  }
                  echo "'>";
                  if ($prem) {
                     $prem = false;
                     $title = $item->getTypeName($nb);
                     if ($nb > 0) {
                        $title = sprintf(__('%1$s: %2$s'), $title, $nb);
                     }
                     echo "<td class='center top' rowspan='$nb'>".$title."</td>";
                  }
                  echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities",
                                                                       $data["entities_id"])."</td>";
                  echo "<td class='center";
                  echo ((isset($data['is_deleted']) && $data['is_deleted']) ?" tab_bg_2_2'" :"'").">";
                  echo $name."</td>";
                  echo "<td class='center'>".
                         (isset($data["serial"])?"".$data["serial"]."":"-")."</td>";
                  echo "<td class='center'>".
                         (isset($data["otherserial"])? "".$data["otherserial"]."" :"-")."</td>";
                  echo "</tr>";
               }
            }
            $num += $nb;
         }
      }
      echo "<tr class='tab_bg_2'>";
      echo "<td class='center'>".(($num > 0) ? sprintf(__('%1$s = %2$s'), __('Total'), $num)
                                             : "&nbsp;")."</td>";
      echo "<td colspan='4'>&nbsp;</td></tr> ";
      echo "</table></div>";
   }
}
