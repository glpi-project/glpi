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
* */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * NetworkName Class
 *
 * represent the internet name of an element.
 * It is compose of the name itself, its domain and one or several IP addresses (IPv4 and/or IPv6).
 * An address can be affected to an item, or can be "free" to be reuse by another item
 * (for instance, in case of maintenance, when you change the network card of a computer,
 *  but not its network information)
 *
 * @since 0.84
**/
class NetworkName extends FQDNLabel {

   // From CommonDBChild
   static public $itemtype              = 'itemtype';
   static public $items_id              = 'items_id';
   public $dohistory                    = true;

   static protected $forward_entity_to  = ['IPAddress', 'NetworkAlias'];

   static public $canDeleteOnItemClean  = false;

   static public $checkParentRights     = CommonDBConnexity::HAVE_SAME_RIGHT_ON_ITEM;

   static public $mustBeAttached        = false;

   static $rightname                   = 'internet';


   static function getTypeName($nb = 0) {
      return _n('Network name', 'Network names', $nb);
   }


   function defineTabs($options = []) {

      $ong  = [];
      $this->addDefaultFormTab($ong);
      $this->addStandardTab('NetworkAlias', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   /**
    * Print the network name form
    *
    * @param $ID        integer ID of the item
    * @param $options   array
    *     - target for the Form
    *     - withtemplate template or basic computer
    *
    *@return Nothing (display)
   **/
   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      $this->initForm($ID, $options);

      $recursiveItems = $this->recursivelyGetItems();
      if (count($recursiveItems) != 0) {
         $lastItem               = $recursiveItems[count($recursiveItems) - 1];
         $options['entities_id'] = $lastItem->getField('entities_id');
      }

      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td>";
      if (count($recursiveItems) > 0) {
         $this->displayRecursiveItems($recursiveItems, 'Type');
      }
      echo "</td>\n<td colspan='3'>";

      if (!($ID > 0)) {
         echo "<input type='hidden' name='items_id' value='".$this->fields["items_id"]."'>\n";
         echo "<input type='hidden' name='itemtype' value='".$this->fields["itemtype"]."'>\n";
      }
      $this->displayRecursiveItems($recursiveItems, "Link");
      if ((count($recursiveItems) > 0) && $this->canUpdate()) {
         Html::showSimpleForm($this->getFormURL(), 'unaffect', _sx('button', 'Dissociate'),
                              ['id' => $ID]);
      }

      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Name') . "</td><td>\n";
      Html::autocompletionTextField($this, "name");
      echo "</td>\n";

      echo "<td>".FQDN::getTypeName(1)."</td><td>";
      Dropdown::show(getItemTypeForTable(getTableNameForForeignKeyField("fqdns_id")),
                     ['value'       => $this->fields["fqdns_id"],
                           'name'        => 'fqdns_id',
                           'entity'      => $this->getEntityID(),
                           'displaywith' => ['view']]);
      echo "</td>\n</tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".IPAddress::getTypeName(Session::getPluralNumber());
      IPAddress::showAddChildButtonForItemForm($this, '_ipaddresses');
      echo "</td>";
      echo "<td>";
      IPAddress::showChildsForItemForm($this, '_ipaddresses');
      echo "</td>\n";

      echo "<td rowspan='3'>".__('Comments')."</td>";
      echo "<td rowspan='3'><textarea cols='45' rows='4' name='comment' >".$this->fields["comment"];
      echo "</textarea></td>\n";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>".IPNetwork::getTypeName(Session::getPluralNumber())."</td><td>";
      echo __('IP network is not included in the database. However, you can see current available networks.');
      echo "</td></tr>";
      echo "<tr class='tab_bg_1'><td>&nbsp;</td><td>";
      IPNetwork::showIPNetworkProperties($this->getEntityID());
      echo "</td></tr>\n";

      $this->showFormButtons($options);

      return true;
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '12',
         'table'              => 'glpi_fqdns',
         'field'              => 'fqdn',
         'name'               => FQDN::getTypeName(1),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => 'glpi_ipaddresses',
         'field'              => 'name',
         'name'               => IPAddress::getTypeName(1),
         'joinparams'         => [
            'jointype'           => 'itemtype_item'
         ],
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '20',
         'table'              => $this->getTable(),
         'field'              => 'itemtype',
         'name'               => __('Type'),
         'datatype'           => 'itemtype',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '21',
         'table'              => $this->getTable(),
         'field'              => 'items_id',
         'name'               => __('ID'),
         'datatype'           => 'integer',
         'massiveaction'      => false
      ];

      return $tab;
   }


   /**
    * @param $tab          array   the array to fill
    * @param $joinparams   array
   **/
   static function rawSearchOptionsToAdd(array &$tab, array $joinparams) {
      $tab[] = [
         'id'                 => '126',
         'table'              => 'glpi_ipaddresses',
         'field'              => 'name',
         'name'               => __('IP'),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'  => 'mainitemtype_mainitem',
            'condition' => 'AND NEWTABLE.`is_deleted` = 0'
         ]
      ];

      $tab[] = [
         'id'                 => '127',
         'table'              => 'glpi_networknames',
         'field'              => 'name',
         'name'               => self::getTypeName(Session::getPluralNumber()),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => $joinparams
      ];

      $tab[] = [
         'id'                 => '128',
         'table'              => 'glpi_networkaliases',
         'field'              => 'name',
         'name'               => NetworkAlias::getTypeName(Session::getPluralNumber()),
         'forcegroupby'       => true,
         'massiveaction'      => false,
         'joinparams'         => [
            'jointype'   => 'child',
            'beforejoin' => [
               'table'      => 'glpi_networknames',
               'joinparams' => $joinparams
            ]
         ]
      ];
   }


   /**
    * \brief Update IPAddress database
    * Update IPAddress database to remove old IPs and add new ones.
   **/
   function post_workOnItem() {

      if ((isset($this->input['_ipaddresses']))
          && (is_array($this->input['_ipaddresses']))) {
         $input = ['itemtype' => 'NetworkName',
                        'items_id' => $this->getID()];
         foreach ($this->input['_ipaddresses'] as $id => $ip) {
            $ipaddress     = new IPAddress();
            $input['name'] = $ip;
            if ($id < 0) {
               if (!empty($ip)) {
                  $ipaddress->add($input);
               }
            } else {
               if (!empty($ip)) {
                  $input['id'] = $id;
                  $ipaddress->update($input);
                  unset($input['id']);
               } else {
                  $ipaddress->delete(['id' => $id]);
               }
            }
         }
      }
   }


   function post_addItem() {

      $this->post_workOnItem();
      parent::post_addItem();
   }


   function post_updateItem($history = 1) {
      global $DB;

      $this->post_workOnItem();
      if (count($this->updates)) {
         // Update Ticket Tco
         if (in_array("itemtype", $this->updates)
             || in_array("items_id", $this->updates)) {

            $ip = new IPAddress();
            // Update IPAddress
            foreach ($DB->request('glpi_ipaddresses',
                                  ['itemtype' => 'NetworkName',
                                        'items_id' => $this->getID()]) as $data) {
               $ip->update(['id'       => $data['id'],
                                 'itemtype' => 'NetworkName',
                                 'items_id' => $this->getID()]);
            }
         }
      }
      parent::post_updateItem($history);
   }


   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            IPAddress::class,
            NetworkAlias::class,
         ]
      );
   }


   /**
    * \brief dettach an address from an item
    *
    * The address can be unaffected, and remain "free"
    *
    * @param $items_id  the id of the item
    * @param $itemtype  the type of the item
   **/
   static function unaffectAddressesOfItem($items_id, $itemtype) {
      global $DB;

      $query = "SELECT `id`
                FROM `glpi_networknames`
                WHERE `items_id` = '".$items_id."'
                AND `itemtype` = '".$itemtype."'";

      foreach ($DB->request($query) as $networkNameID) {
         self::unaffectAddressByID($networkNameID['id']);
      }
   }


   /**
    * \brief dettach an address from an item
    *
    * The address can be unaffected, and remain "free"
    *
    * @param $networkNameID the id of the NetworkName
   **/
   static function unaffectAddressByID($networkNameID) {
      return self::affectAddress($networkNameID, 0, '');
   }


   /**
    * @param $networkNameID
    * @param $items_id
    * @param $itemtype
   **/
   static function affectAddress($networkNameID, $items_id, $itemtype) {
      global $DB;

      $networkName = new self();
      return $networkName->update(['id'       => $networkNameID,
                                        'items_id' => $items_id,
                                        'itemtype' => $itemtype]);
   }


   /**
    * Get the full name (internet name) of a NetworkName
    *
    * @param $ID ID of the NetworkName
    *
    * @return its internet name, or empty string if invalid NetworkName
   **/
   static function getInternetNameFromID($ID) {

      $networkName = new self();

      if ($networkName->can($ID, READ)) {
         return FQDNLabel::getInternetNameFromLabelAndDomainID(
                 $networkName->fields["name"],
                 $networkName->fields["fqdns_id"]);
      }
      return "";
   }


   /**
    * @param $networkPortID
   **/
   static function showFormForNetworkPort($networkPortID) {
      global $DB, $CFG_GLPI;

      $name         = new self();
      $number_names = 0;

      if ($networkPortID > 0) {
         $query = "SELECT `id`
                   FROM `".$name->getTable()."`
                   WHERE `itemtype` = 'NetworkPort'
                   AND `items_id` = '$networkPortID'
                   AND `is_deleted` = 0";

         $result = $DB->query($query);

         if ($DB->numrows($result) > 1) {
            echo "<tr class='tab_bg_1'><th colspan='4'>" .
                   __("Several network names available! Go to the tab 'Network Name' to manage them.") .
                 "</th></tr>\n";
            return;
         }

         switch ($DB->numrows($result)) {
            case 1 :
               $nameID = $DB->fetch_assoc($result);
               $name->getFromDB($nameID['id']);
               break;

            case 0 :
               $name->getEmpty();
               break;
         }

      } else {
         $name->getEmpty();
      }

      echo "<tr class='tab_bg_1'><th colspan='4'>";
       // If the networkname is defined, we must be able to edit it. So we make a link
      if ($name->getID() > 0) {
         echo "<a href='".$name->getLinkURL()."'>".self::getTypeName(1)."</a>";
         echo "<input type='hidden' name='NetworkName_id' value='".$name->getID()."'>&nbsp;\n";
         Html::showSimpleForm($name->getFormURL(), 'unaffect', _sx('button', 'Dissociate'),
                              ['id' => $name->getID()],
                              $CFG_GLPI["root_doc"].'/pics/sub_dropdown.png');
      } else {
         echo self::getTypeName(1);
      }
      echo "</th>\n";

      echo "</tr><tr class='tab_bg_1'>";

      echo "<td>" . self::getTypeName(1) . "</td><td>\n";
      Html::autocompletionTextField($name, "name", ['name' => 'NetworkName_name']);
      echo "</td>\n";

      echo "<td>".FQDN::getTypeName(1)."</td><td>";
      Dropdown::show(getItemTypeForTable(getTableNameForForeignKeyField("fqdns_id")),
                     ['value'       => $name->fields["fqdns_id"],
                           'name'        => 'NetworkName_fqdns_id',
                           'entity'      => $name->getEntityID(),
                           'displaywith' => ['view']]);
      echo "</td>\n";

      echo "</tr><tr class='tab_bg_1'>\n";

      echo "<td>".IPAddress::getTypeName(Session::getPluralNumber());
      IPAddress::showAddChildButtonForItemForm($name, 'NetworkName__ipaddresses');
      echo "</td>";
      echo "<td>";
      IPAddress::showChildsForItemForm($name, 'NetworkName__ipaddresses');
      echo "</td>";

      // MoYo : really need to display it here ?
      // make confure because not updatable
      // echo "<td>".IPNetwork::getTypeName(Session::getPluralNumber())."&nbsp;";
      // Html::showToolTip(__('IP network is not included in the database. However, you can see current available networks.'));
      // echo "</td><td>";
      // IPNetwork::showIPNetworkProperties($name->getEntityID());
      // echo "</td>\n";
      echo "<td colspan='2'>&nbsp;</td>";

      echo "</tr>\n";
   }


   /**
    * @since 0.84
    *
    * @param $itemtype
    * @param $base            HTMLTableBase object
    * @param $super           HTMLTableSuperHeader object (default NULL
    * @param $father          HTMLTableHeader object (default NULL)
    * @param $options   array
   **/
   static function getHTMLTableHeader($itemtype, HTMLTableBase $base,
                                      HTMLTableSuperHeader $super = null,
                                      HTMLTableHeader $father = null, array $options = []) {

      $column_name = __CLASS__;
      if (isset($options['massiveactionnetworkname'])
          && $options['massiveactionnetworkname']) {
          $delete_all_column = $base->addHeader('delete',
                                                Html::getCheckAllAsCheckbox('mass'.__CLASS__.
                                                                            $options['rand']),
                                                $super, $father);
          $delete_all_column->setHTMLClass('center');
      }
      if (!isset($options['dont_display'][$column_name])) {

         $content = self::getTypeName();
         if (isset($options['column_links'][$column_name])) {
            $content = "<a href='".$options['column_links'][$column_name]."'>$content</a>";
         }
         $father = $base->addHeader($column_name, $content, $super, $father);
         $father->setItemType('NetworkName');

         if (isset($options['display_isDynamic']) && ($options['display_isDynamic'])) {
            $father = $base->addHeader($column_name.'_dynamic',
                                        __('Automatic inventory'), $super, $father);
         }
      }

      NetworkAlias::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
      IPAddress::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
   }


   /**
    * @since 0.84
    *
    * @param $row             HTMLTableRow object (default NULL)
    * @param $item            CommonDBTM object (default NULL)
    * @param $father          HTMLTableCell object (default NULL)
    * @param $options   array
   **/
   static function getHTMLTableCellsForItem(HTMLTableRow $row = null, CommonDBTM $item = null,
                                            HTMLTableCell $father = null, array $options = []) {
      global $DB, $CFG_GLPI;

      $column_name = __CLASS__;

      if (empty($item)) {
         if (empty($father)) {
            return;
         }
         $item = $father->getItem();
      }

      switch ($item->getType()) {
         case 'FQDN' :
            $JOINS = "";
            $ORDER = "`glpi_networknames`.`name`";

            if (isset($options['order'])) {
               switch ($options['order']) {
                  case 'name' :
                     break;

                  case 'ip' :
                     $JOINS = " LEFT JOIN `glpi_ipaddresses`
                                    ON (`glpi_ipaddresses`.`items_id` = `glpi_networknames`.`id`
                                        AND `glpi_ipaddresses`.`itemtype` = 'NetworkName'
                                        AND `glpi_ipaddresses`.`is_deleted` = 0)";
                     $ORDER = "ISNULL (`glpi_ipaddresses`.`id`),
                               `glpi_ipaddresses`.`binary_3`, `glpi_ipaddresses`.`binary_2`,
                               `glpi_ipaddresses`.`binary_1`, `glpi_ipaddresses`.`binary_0`";
                     break;

                  case 'alias' :
                     $JOINS = " LEFT JOIN `glpi_networkaliases`
                                    ON (`glpi_networkaliases`.`networknames_id`
                                          = `glpi_networknames`.`id`)";
                     $ORDER = "ISNULL(`glpi_networkaliases`.`name`),
                               `glpi_networkaliases`.`name`";
                     break;
               }
            }

            $query = "SELECT `glpi_networknames`.`id`
                      FROM `glpi_networknames`
                      $JOINS
                      WHERE `glpi_networknames`.`fqdns_id` = '".$item->fields["id"]."'
                            AND `glpi_networknames`.`is_deleted` = 0
                      ORDER BY $ORDER";
            break;

         case 'NetworkPort' :
            $query = "SELECT `id`
                      FROM `glpi_networknames`
                      WHERE `itemtype` = '".$item->getType()."'
                            AND `items_id` = '".$item->getID()."'
                            AND `glpi_networknames`.`is_deleted` = 0";
            break;

         case 'NetworkEquipment' :
            $query = "SELECT `glpi_networknames`.`id`
                      FROM `glpi_networknames`, `glpi_networkports`
                      WHERE `glpi_networkports`.`itemtype` = '".$item->getType()."'
                            AND `glpi_networkports`.`items_id` ='".$item->getID()."'
                            AND `glpi_networknames`.`itemtype` = 'NetworkPort'
                            AND `glpi_networknames`.`items_id` = `glpi_networkports`.`id`
                            AND `glpi_networknames`.`is_deleted` = 0";
            break;

      }

      if (isset($options['SQL_options'])) {
         $query .= " ".$options['SQL_options'];
      }

      $canedit              = (isset($options['canedit']) && $options['canedit']);
      $createRow            = (isset($options['createRow']) && $options['createRow']);
      $options['createRow'] = false;
      $address              = new self();

      foreach ($DB->request($query) as $line) {
         if ($address->getFromDB($line["id"])) {

            if ($createRow) {
               $row = $row->createAnotherRow();
            }

            if (isset($options['massiveactionnetworkname'])
               && $options['massiveactionnetworkname']) {
               $header      = $row->getGroup()->getHeaderByName('Internet', 'delete');
               $cell_value  = Html::getMassiveActionCheckBox(__CLASS__, $line["id"]);
               $delete_cell = $row->addCell($header, $cell_value, $father);
            }

            $internetName = $address->getInternetName();
            if (empty($internetName)) {
               $internetName = "(".$line["id"].")";
            }
            $content  = $internetName;
            if (Session::haveRight('internet', READ)) {
               $content  = "<a href='" . $address->getLinkURL(). "'>".$internetName."</a>";
            }

            if (!isset($options['dont_display'][$column_name])) {
               $header              = $row->getGroup()->getHeaderByName('Internet', $column_name);
               $name_cell           = $row->addCell($header, $content, $father, $address);
               if (isset($options['display_isDynamic']) && ($options['display_isDynamic'])) {
                  $dyn_header   = $row->getGroup()->getHeaderByName('Internet',
                                                                    $column_name.'_dynamic');
                  $dynamic_cell = $row->addCell($dyn_header,
                                                Dropdown::getYesNo($address->fields['is_dynamic']),
                                                $name_cell);
                  $father_for_children = $dynamic_cell;
               } else {
                  $father_for_children = $name_cell;
               }
            } else {
               $father_for_children = $father;
            }

            NetworkAlias::getHTMLTableCellsForItem($row, $address, $father_for_children, $options);
            IPAddress::getHTMLTableCellsForItem($row, $address, $father_for_children, $options);

         }
      }
   }


   /**
    * \brief Show names for an item from its form
    * Beware that the rendering can be different if readden from direct item form (ie : add new
    * NetworkName, remove, ...) or if readden from item of the item (for instance from the computer
    * form through NetworkPort::ShowForItem).
    *
    * @param $item                     CommonGLPI object
    * @param $withtemplate   integer   withtemplate param (default 0)
   **/
   static function showForItem(CommonDBTM $item, $withtemplate = 0) {
      global $DB, $CFG_GLPI;

      $ID = $item->getID();
      if (!$item->can($ID, READ)) {
         return false;
      }

      $rand = mt_rand();

      if (($item->getType() == 'NetworkPort')
          && Session::haveRight('internet', UPDATE)
          && $item->canUpdateItem()) {

         $items_id = $item->getID();
         $itemtype = $item->getType();

         echo "<div class='firstbloc'>\n";
         echo "<form method='post' action='".static::getFormURL()."'>\n";
         echo "<table class='tab_cadre_fixe'>\n";
         echo "<tr><th colspan='4'>".__('Add a network name')."</th></tr>";

         echo "<tr class='tab_bg_1'><td class='right'>";
         echo "<input type='hidden' name='items_id' value='$items_id'>\n";
         echo "<input type='hidden' name='itemtype' value='$itemtype'>\n";
         echo __('Not associated');
         echo "</td><td class='left'>";
         self::dropdown(['name'      => 'addressID',
                              'condition' => '`items_id`=0']);
         echo "</td><td class='left'>";
         echo "<input type='submit' name='assign_address' value='"._sx('button', 'Associate').
                "' class='submit'>";
         echo "</td>";
         if (static::canCreate()) {
            echo "<td class='right' width='30%'>";
            echo "<a href=\"" . static::getFormURL()."?items_id=$items_id&amp;itemtype=$itemtype\">";
            echo __('Create a new network name')."</a>";
            echo "</td>";
         }
         echo "</tr>\n";

         echo "</table>\n";
         Html::closeForm();
         echo "</div>\n";
      }

      $table_options = ['createRow' => true];

      if (($item->getType() == 'FQDN')
          || ($item->getType() == 'NetworkEquipment')) {
         if (isset($_GET["start"])) {
            $start = $_GET["start"];
         } else {
            $start = 0;
         }

         if (!empty($_GET["order"])) {
            $table_options['order'] = $_GET["order"];
         } else {
            $table_options['order'] = 'name';
         }

         if ($item->getType() == 'FQDN') {
            $table_options['column_links'] = ['NetworkName'
                                                         => 'javascript:reloadTab("order=name");',
                                                   'NetworkAlias'
                                                         => 'javascript:reloadTab("order=alias");',
                                                   'IPAddress'
                                                         => 'javascript:reloadTab("order=ip");'];
         }

         $table_options['SQL_options']  = "LIMIT ".$_SESSION['glpilist_limit']."
                                           OFFSET $start";

         $canedit = false;

      } else {
         $canedit = Session::haveRight('internet', UPDATE) && $item->canUpdateItem();
      }

      $table_options['canedit']                  = false;
      $table_options['rand']                     = $rand;
      $table_options['massiveactionnetworkname'] = $canedit;
      $table                                     = new HTMLTableMain();
      $column                                    = $table->addHeader('Internet',
                                                                     self::getTypeName(Session::getPluralNumber()));
      $t_group                                   = $table->createGroup('Main', '');

      self::getHTMLTableHeader(__CLASS__, $t_group, $column, null, $table_options);

      $t_row   = $t_group->createRow();

      self::getHTMLTableCellsForItem($t_row, $item, null, $table_options);

      if ($table->getNumberOfRows() > 0) {
         $number = $table->getNumberOfRows();
         if ($item->getType() == 'FQDN') {
            $number = min($_SESSION['glpilist_limit'], $table->getNumberOfRows());
            Html::printAjaxPager(self::getTypeName(Session::getPluralNumber()), $start, self::countForItem($item));
         }
         Session::initNavigateListItems(__CLASS__,
                                 //TRANS : %1$s is the itemtype name,
                                 //        %2$s is the name of the item (used for headings of a list)
                                        sprintf(__('%1$s = %2$s'),
                                                $item->getTypeName(1), $item->getName()));
         if ($canedit && $number) {
            Html::openMassiveActionsForm('mass'.__CLASS__.$rand);
            $massiveactionparams = ['num_displayed'    => min($_SESSION['glpilist_limit'], $number),
                              'container'        => 'mass'.__CLASS__.$rand];
            Html::showMassiveActions($massiveactionparams);
         }

         $table->display(['display_title_for_each_group'          => false,
                               'display_thead'                         => false,
                               'display_tfoot'                         => false,
                               'display_header_on_foot_for_each_group' => true]);

         if ($canedit && $number) {
            $massiveactionparams['ontop'] = false;
            Html::showMassiveActions($massiveactionparams);
            Html::closeForm();
         }

         if ($item->getType() == 'FQDN') {
            Html::printAjaxPager(self::getTypeName(Session::getPluralNumber()), $start, self::countForItem($item));
         }
      } else {
         echo "<table class='tab_cadre_fixe'><tr><th>".__('No network name found')."</th></tr>";
         echo "</table>";
      }
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case 'NetworkPort' :
         case 'FQDN' :
         case 'NetworkEquipment' :
            self::showForItem($item, $withtemplate);
            break;
      }
   }


   /**
    * @param $item      CommonDBTM object
   **/
   static function countForItem(CommonDBTM $item) {
      global $DB;

      switch ($item->getType()) {
         case 'FQDN' :
            return countElementsInTable('glpi_networknames',
                                        ['fqdns_id'   => $item->fields["id"],
                                         'is_deleted' => 0 ]);

         case 'NetworkPort' :
            return countElementsInTable('glpi_networknames',
                                       ['itemtype'   => $item->getType(),
                                        'items_id'   => $item->getID(),
                                        'is_deleted' => 0 ]);

         case 'NetworkEquipment' :
            $query = "SELECT DISTINCT COUNT(*) AS cpt
                      FROM `glpi_networknames`, `glpi_networkports`
                      WHERE `glpi_networkports`.`itemtype` = '".$item->getType()."'
                            AND `glpi_networkports`.`items_id` ='".$item->getID()."'
                            AND `glpi_networkports`.`is_deleted` ='0'
                            AND `glpi_networknames`.`itemtype` = 'NetworkPort'
                            AND `glpi_networknames`.`items_id` = `glpi_networkports`.`id`
                            AND `glpi_networknames`.`is_deleted` = 0";
            $result = $DB->query($query);
            $ligne  = $DB->fetch_assoc($result);
            return $ligne['cpt'];
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getID()
          && $item->can($item->getField('id'), READ)) {
         $nb = 0;
         if ($_SESSION['glpishow_count_on_tabs']) {
            $nb = self::countForItem($item);
         }
         return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb);
      }
      return '';
   }

}
