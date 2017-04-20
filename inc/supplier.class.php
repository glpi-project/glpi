<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015-2016 Teclib'.

 http://glpi-project.org

 based on GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2014 by the INDEPNET Development Team.

 -------------------------------------------------------------------------

 LICENSE

 This file is part of GLPI.

 GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

/** @file
* @brief
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
   static function getTypeName($nb=0) {
      return _n('Supplier', 'Suppliers', $nb);
   }


   function cleanDBonPurge() {
      global $DB;

      $supplierjob = new Supplier_Ticket();
      $supplierjob->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $ps = new Problem_Supplier();
      $ps->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $cs = new Change_Supplier();
      $cs->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $query1 = "DELETE
                 FROM `glpi_projecttaskteams`
                 WHERE `items_id` = '".$this->fields['id']."'
                       AND `itemtype` = '".__CLASS__."'";
      $DB->query($query1);

      $query1 = "DELETE
                 FROM `glpi_projectteams`
                 WHERE `items_id` = '".$this->fields['id']."'
                       AND `itemtype` = '".__CLASS__."'";
      $DB->query($query1);


      $cs  = new Contract_Supplier();
      $cs->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      $cs  = new Contact_Supplier();
      $cs->cleanDBonItemDelete($this->getType(), $this->fields['id']);

      // Ticket rules use suppliers_id_assign
      Rule::cleanForItemAction($this, 'suppliers_id%');
   }


   function defineTabs($options=array()) {

      $ong = array();
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
   function showForm($ID, $options=array()) {

      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Name')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>".__('Third party type')."</td>";
      echo "<td>";
      SupplierType::dropdown(array('value' => $this->fields["suppliertypes_id"]));
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
      Html::autocompletionTextField($this, "postcode", array('size' => 10));
      echo "&nbsp;&nbsp;". __('City'). "&nbsp;";
      Html::autocompletionTextField($this, "town", array('size' => 23));
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>"._x('location','State')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "state");
      echo "</td></tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".__('Country')."</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "country");
      echo "</td></tr>";

      $this->showFormButtons($options);

      return true;

   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem=NULL) {

      $isadmin = static::canUpdate();
      $actions = parent::getSpecificMassiveActions($checkitem);
      if ($isadmin) {
         $actions['Contact_Supplier'.MassiveAction::CLASS_ACTION_SEPARATOR.'add']
               = _x('button', 'Add a contact');

         MassiveAction::getAddTransferList($actions);
      }
      return $actions;
   }


   function getSearchOptions() {

      $tab                          = array();

      $tab['common']                = __('Characteristics');

      $tab[1]['table']              = $this->getTable();
      $tab[1]['field']              = 'name';
      $tab[1]['name']               = __('Name');
      $tab[1]['datatype']           = 'itemlink';
      $tab[1]['massiveaction']      = false;

      $tab[2]['table']              = $this->getTable();
      $tab[2]['field']              = 'id';
      $tab[2]['name']               = __('ID');
      $tab[2]['massiveaction']      = false;
      $tab[2]['datatype']           = 'number';

      $tab[3]['table']              = $this->getTable();
      $tab[3]['field']              = 'address';
      $tab[3]['name']               = __('Address');
      $tab[3]['datatype']           = 'text';

      $tab[10]['table']             = $this->getTable();
      $tab[10]['field']             = 'fax';
      $tab[10]['name']              = __('Fax');
      $tab[10]['datatype']          = 'string';

      $tab[11]['table']             = $this->getTable();
      $tab[11]['field']             = 'town';
      $tab[11]['name']              = __('City');
      $tab[11]['datatype']          = 'string';

      $tab[14]['table']             = $this->getTable();
      $tab[14]['field']             = 'postcode';
      $tab[14]['name']              = __('Postal code');
      $tab[14]['datatype']          = 'string';

      $tab[12]['table']             = $this->getTable();
      $tab[12]['field']             = 'state';
      $tab[12]['name']              = _x('location','State');
      $tab[12]['datatype']          = 'string';

      $tab[13]['table']             = $this->getTable();
      $tab[13]['field']             = 'country';
      $tab[13]['name']              = __('Country');
      $tab[13]['datatype']          = 'string';

      $tab[4]['table']              = $this->getTable();
      $tab[4]['field']              = 'website';
      $tab[4]['name']               = __('Website');
      $tab[4]['datatype']           = 'weblink';

      $tab[5]['table']              = $this->getTable();
      $tab[5]['field']              = 'phonenumber';
      $tab[5]['name']               =  __('Phone');
      $tab[5]['datatype']           = 'string';

      $tab[6]['table']              = $this->getTable();
      $tab[6]['field']              = 'email';
      $tab[6]['name']               = _n('Email', 'Emails', 1);
      $tab[6]['datatype']           = 'email';

      $tab[9]['table']              = 'glpi_suppliertypes';
      $tab[9]['field']              = 'name';
      $tab[9]['name']               = __('Third party type');
      $tab[9]['datatype']           = 'dropdown';

      $tab[19]['table']          = $this->getTable();
      $tab[19]['field']          = 'date_mod';
      $tab[19]['name']           = __('Last update');
      $tab[19]['datatype']       = 'datetime';
      $tab[19]['massiveaction']  = false;

      $tab[121]['table']          = $this->getTable();
      $tab[121]['field']          = 'date_creation';
      $tab[121]['name']           = __('Creation date');
      $tab[121]['datatype']       = 'datetime';
      $tab[121]['massiveaction']  = false;

      if ($_SESSION["glpinames_format"] == User::FIRSTNAME_BEFORE) {
         $name1 = 'firstname';
         $name2 = 'name';
      } else {
         $name1 = 'name';
         $name2 = 'firstname';
      }
      $tab[8]['table']              = 'glpi_contacts';
      $tab[8]['field']              = 'completename';
      $tab[8]['name']               = _n('Associated contact', 'Associated contacts', Session::getPluralNumber());
      $tab[8]['forcegroupby']       = true;
      $tab[8]['datatype']           = 'itemlink';
      $tab[8]['massiveaction']      = false;
      $tab[8]['computation']        = "CONCAT(TABLE.`$name1`, ' ', TABLE.`$name2`)";
      $tab[8]['computationgroupby'] = true;
      $tab[8]['joinparams']         = array('beforejoin'
                                             => array('table'      => 'glpi_contacts_suppliers',
                                                      'joinparams' => array('jointype' => 'child')));

      $tab[16]['table']             = $this->getTable();
      $tab[16]['field']             = 'comment';
      $tab[16]['name']              = __('Comments');
      $tab[16]['datatype']          = 'text';

      $tab[80]['table']             = 'glpi_entities';
      $tab[80]['field']             = 'completename';
      $tab[80]['name']              = __('Entity');
      $tab[80]['massiveaction']     = false;
      $tab[80]['datatype']          = 'dropdown';

      $tab[86]['table']             = $this->getTable();
      $tab[86]['field']             = 'is_recursive';
      $tab[86]['name']              = __('Child entities');
      $tab[86]['datatype']          = 'bool';

      $tab[29]['table']             = 'glpi_contracts';
      $tab[29]['field']             = 'name';
      $tab[29]['name']              = _n('Associated contract', 'Associated contracts', Session::getPluralNumber());
      $tab[29]['forcegroupby']      = true;
      $tab[29]['datatype']          = 'itemlink';
      $tab[29]['massiveaction']     = false;
      $tab[29]['joinparams']        = array('beforejoin'
                                             => array('table'      => 'glpi_contracts_suppliers',
                                                      'joinparams' => array('jointype' => 'child')));

      // add objectlock search options
      $tab += ObjectLock::getSearchOptionsToAdd( get_class($this) ) ;

      $tab += Notepad::getSearchOptionsToAdd();

      return $tab;
   }


   /**
    * Get links for an enterprise (website / edit)
    *
    * @param $withname boolean : also display name ? (false by default)
   **/
   function getLinks($withname=false) {
      global $CFG_GLPI;

      $ret = '&nbsp;&nbsp;&nbsp;&nbsp;';

      if ($withname) {
         $ret .= $this->fields["name"];
         $ret .= "&nbsp;&nbsp;";
      }

      if (!empty($this->fields['website'])) {
         $ret .= "<a href='".formatOutputWebLink($this->fields['website'])."' target='_blank'>
                  <img src='".$CFG_GLPI["root_doc"]."/pics/web.png' class='middle' alt=\"".
                   __s('Web')."\" title=\"".__s('Web')."\"></a>&nbsp;&nbsp;";
      }

      if ($this->can($this->fields['id'], READ)) {
         $ret .= "<a href='".$CFG_GLPI["root_doc"]."/front/supplier.form.php?id=".
                   $this->fields['id']."'>
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

      $query = "SELECT DISTINCT `itemtype`
                FROM `glpi_infocoms`
                WHERE `suppliers_id` = '$instID'
                      AND `itemtype` NOT IN ('ConsumableItem', 'CartridgeItem', 'Software')
                ORDER BY `itemtype`";

      $result = $DB->query($query);
      $number = $DB->numrows($result);

      echo "<div class='spaced'><table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='2'>";
      Html::printPagerForm();
      echo "</th><th colspan='3'>";
      if ($DB->numrows($result) == 0) {
         _e('No associated item');
      } else {
         echo _n('Associated item', 'Associated items', $DB->numrows($result));
      }
      echo "</th></tr>";
      echo "<tr><th>".__('Type')."</th>";
      echo "<th>".__('Entity')."</th>";
      echo "<th>".__('Name')."</th>";
      echo "<th>".__('Serial number')."</th>";
      echo "<th>".__('Inventory number')."</th>";
      echo "</tr>";

      $num = 0;
      for ($i=0 ; $i < $number ; $i++) {
         $itemtype = $DB->result($result, $i, "itemtype");

         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }

         if ($item->canView()) {
            $linktype  = $itemtype;
            $linkfield = 'id';
            $itemtable = getTableForItemType($itemtype);

            $query = "SELECT `glpi_infocoms`.`entities_id`, `NAME_FIELD`, `$itemtable`.*
                      FROM `glpi_infocoms`
                      INNER JOIN `$itemtable` ON (`$itemtable`.`id` = `glpi_infocoms`.`items_id`) ";

            // Set $linktype for entity restriction AND link to search engine
            if ($itemtype == 'Cartridge') {
               $query .= "INNER JOIN `glpi_cartridgeitems`
                            ON (`glpi_cartridgeitems`.`id`=`glpi_cartridges`.`cartridgeitems_id`) ";

               $linktype  = 'CartridgeItem';
               $linkfield = 'cartridgeitems_id';
            }

            if ($itemtype == 'Consumable' ) {
               $query .= "INNER JOIN `glpi_consumableitems`
                            ON (`glpi_consumableitems`.`id`=`glpi_consumables`.`consumableitems_id`) ";

               $linktype  = 'ConsumableItem';
               $linkfield = 'consumableitems_id';
            }

            if ($itemtype == 'Item_DeviceControl') {
               $query .= "INNER JOIN `glpi_devicecontrols`
                           ON (`glpi_items_devicecontrols`.`devicecontrols_id`=`glpi_devicecontrols`.`id`)";
               $linktype = 'DeviceControl';
               $linkfield = 'devicecontrols_id';
            }

            $linktable = getTableForItemType($linktype);

            $query = str_replace('NAME_FIELD', $linktype::getNameField(), $query);
            $query .= "WHERE `glpi_infocoms`.`itemtype` = '$itemtype'
                             AND `glpi_infocoms`.`suppliers_id` = '$instID'".
                             getEntitiesRestrictRequest(" AND", $linktable) ."
                       ORDER BY `glpi_infocoms`.`entities_id`,
                                `$linktable`.`" . $linktype::getNameField() . "`";

            $result_linked = $DB->query($query);
            $nb            = $DB->numrows($result_linked);

            if ($nb > $_SESSION['glpilist_limit']) {
               echo "<tr class='tab_bg_1'>";
               $title = $item->getTypeName($nb);
               if ($nb > 0) {
                  $title = sprintf(__('%1$s: %2$s'), $title, $nb);
               }
               echo "<td class='center'>".$title."</td>";
               echo "<td class='center' colspan='2'>";
               $opt = array('order'      => 'ASC',
                            'is_deleted' => 0,
                            'reset'      => 'reset',
                            'start'      => 0,
                            'sort'       => 80,
                            'criteria'   => array(0 => array('value'      => '$$$$'.$instID,
                                                             'searchtype' => 'contains',
                                                             'field'      => 53)));
              $link = $linktype::getSearchURL();
              $link.= (strpos($link,'?') ? '&amp;':'?');

               echo "<a href='$link" .
                     Toolbox::append_params($opt). "'>" . __('Device list')."</a></td>";

               echo "<td class='center'>-</td><td class='center'>-</td></tr>";

            } else if ($nb) {
               for ($prem=true ; $data=$DB->fetch_assoc($result_linked) ; $prem=false) {
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
