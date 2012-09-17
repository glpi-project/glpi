<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2012 by the INDEPNET Development Team.

 http://indepnet.net/   http://glpi-project.org
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

// ----------------------------------------------------------------------
// Original Author of file: Damien Touraine
// Purpose of file:
// ----------------------------------------------------------------------

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

/// Class NetworkName : represent the internet name of an element. It is compose of the name itself,
/// its domain and one or several IP addresses (IPv4 and/or IPv6).
/// An address can be affected to an item, or can be "free" to be reuse by another item (for
/// instance, in case of maintenance, when you change the network card of a computer, but not its
/// network information
/// since version 0.84
class NetworkName extends FQDNLabel {

   // From CommonDBChild
   static public $itemtype              = 'itemtype';
   static public $items_id              = 'items_id';
   public $dohistory             = true;

   static protected $forward_entity_to = array('IPAddress');

   static public $canDeleteOnItemClean = false;

   static public $checkParentRights = CommonDBConnexity::HAVE_SAME_RIGHT_ON_ITEM;

   static function canCreate() {

      return (Session::haveRight('internet', 'w')
              && parent::canChild('canCreate'));
   }


   static function canView() {

      return (Session::haveRight('internet', 'r')
              && parent::canChild('canView'));
   }


   function canCreateItem() {
      return parent::canChildItem('canCreateItem', 'canCreate');
   }


   function canViewItem() {
      return parent::canChildItem('canViewItem', 'canView');
   }


   static function getTypeName($nb=0) {
      return _n('Network name', 'Network names', $nb);
   }


   function defineTabs($options=array()) {

      $ong  = array();
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
   function showForm($ID, $options=array()) {
      global $CFG_GLPI;

      $this->initForm($ID, $options);

      $recursiveItems = $this->recursivelyGetItems();
      if (count($recursiveItems) == 0) {
         return false;
      }

      $lastItem = $recursiveItems[count($recursiveItems) - 1];

      $this->showTabs();

      $options['entities_id'] = $lastItem->getField('entities_id');
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'><td>";
      $this->displayRecursiveItems($recursiveItems, 'Type');
      echo "</td>\n<td>";

      if (!($ID > 0)) {
         echo "<input type='hidden' name='items_id' value='".$this->fields["items_id"]."'>\n";
         echo "<input type='hidden' name='itemtype' value='".$this->fields["itemtype"]."'>\n";
      }
      $this->displayRecursiveItems($recursiveItems, "Link");
      echo "</td>\n";
      echo "<td>" . __('Name') . "</td><td>\n";
      Html::autocompletionTextField($this, "name");
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";

      echo "<td>".IPNetwork::getTypeName(2)."&nbsp;";
      Html::showToolTip(__('IP network is not included in the database. However, you can see current available networks.'));
      echo "</td><td>";
      IPNetwork::showIPNetworkProperties($this->getEntityID());
      echo "</td>\n";

      echo "<td>".FQDN::getTypeName(1)."</td><td>";
      Dropdown::show(getItemTypeForTable(getTableNameForForeignKeyField("fqdns_id")),
                     array('value'       => $this->fields["fqdns_id"],
                           'name'        => 'fqdns_id',
                           'entity'      => $this->getEntityID(),
                           'displaywith' => array('view')));
      echo "</td>\n</tr>\n";

      echo "<tr class='tab_bg_1'>";
      $address = new IPAddress();
      echo "<td>".$address->getTypeName(2);
      $address->showAddButtonForChildItem($this, '_ipaddresses');
      echo "</td>";
      echo "<td>";
      $address->showFieldsForItemForm($this, '_ipaddresses', 'name');
      echo "</td>\n";

      echo "<td>".__('Comments')."</td>";
      echo "<td><textarea cols='45' rows='4' name='comment' >".$this->fields["comment"];
      echo "</textarea></td>\n";
      echo "</tr>";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   function getSearchOptions() {

      $tab                      = parent::getSearchOptions();

      $tab[12]['table']         = 'glpi_fqdns';
      $tab[12]['field']         = 'fqdn';
      $tab[12]['name']          = FQDN::getTypeName(1);
      $tab[12]['datatype']      = 'dropdown';

      $tab[13]['table']         = 'glpi_ipaddresses';
      $tab[13]['field']         = 'name';
      $tab[13]['name']          = IPAddress::getTypeName(1);
      $tab[13]['joinparams']    = array('jointype' => 'itemtype_item');
      $tab[13]['forcegroupby']  = true;
      $tab[13]['massiveaction'] = false;
      $tab[13]['datatype']      = 'dropdown';

      $tab[20]['table']        = $this->getTable();
      $tab[20]['field']        = 'itemtype';
      $tab[20]['name']         = __('Type');
      $tab[20]['datatype']     = 'itemtype';
      $tab[20]['massiveation'] = false;

      $tab[21]['table']        = $this->getTable();
      $tab[21]['field']        = 'items_id';
      $tab[21]['name']         = __('ID');
      $tab[21]['datatype']     = 'integer';
      $tab[21]['massiveation'] = false;

      return $tab;
   }


   /**
    * @param $tab          array   the array to fill
    * @param $joinparams   array
    * @param $itemtype
   **/
   static function getSearchOptionsToAdd(array &$tab, array $joinparams, $itemtype) {

      $tab[20]['table']         = 'glpi_ipaddresses';
      $tab[20]['field']         = 'name';
      $tab[20]['name']          = __('IP');
      $tab[20]['forcegroupby']  = true;
      $tab[20]['massiveaction'] = false;
      $tab[20]['joinparams']    = array('jointype'          => 'itemtype_item',
                                        'specific_itemtype' => 'NetworkName',
                                        'beforejoin'        => array('table' => 'glpi_networknames',
                                                                     'joinparams'
                                                                             => $joinparams));

      $tab[27]['table']         = 'glpi_networknames';
      $tab[27]['field']         = 'name';
      $tab[27]['name']          = self::getTypeName(2);
      $tab[27]['forcegroupby']  = true;
      $tab[27]['massiveaction'] = false;
      $tab[27]['joinparams']    = $joinparams;

      $tab[28]['table']         = 'glpi_networkaliases';
      $tab[28]['field']         = 'name';
      $tab[28]['name']          = NetworkAlias::getTypeName(2);
      $tab[28]['forcegroupby']  = true;
      $tab[28]['massiveaction'] = false;
      $tab[28]['joinparams']    = array('jointype'   => 'child',
                                        'beforejoin' => array('table'      => 'glpi_networknames',
                                                              'joinparams' => $joinparams));
   }


   /**
    * \brief Update IPAddress database
    * Update IPAddress database to remove old IPs and add new ones.
   **/
   function post_workOnItem() {

      if ((isset($this->input['_ipaddresses']))
          && (is_array($this->input['_ipaddresses']))) {
         $input = array('itemtype' => 'NetworkName',
                        'items_id' => $this->getID());
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
                  $ipaddress->delete(array('id' => $id));
               }
            }
         }
      }
   }


   function post_addItem() {

      $this->post_workOnItem();
      parent::post_addItem();
   }


   function post_updateItem($history=1) {

      $this->post_workOnItem();
      parent::post_updateItem($history);
   }


   function cleanDBonPurge() {

      $alias = new NetworkAlias();
      $alias->cleanDBonItemDelete($this->getType(), $this->GetID());

      $ipAddress = new IPAddress();
      $ipAddress->cleanDBonItemDelete($this->getType(), $this->GetID());
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
      self::affectAddress($networkNameID, 0, '');
   }


   /**
    * @param $networkNameID
    * @param $items_id
    * @param $itemtype
   **/
   static function affectAddress($networkNameID, $items_id, $itemtype) {

      $networkName = new self();
      $networkName->update(array('id'       => $networkNameID,
                                 'items_id' => $items_id,
                                 'itemtype' => $itemtype));
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

      if ($networkName->can($ID, 'r')) {
         return FQDNLabel::getInternetNameFromLabelAndDomainID($this->fields["name"],
                                                               $this->fields["fqdns_id"]);
      }
      return "";
   }


   /**
    * @param $networkPortID
   **/
   static function showFormForNetworkPort($networkPortID) {
      global $DB;

      $name         = new self();
      $number_names = 0;

      if ($networkPortID > 0) {
         $query = "SELECT `id`
                   FROM `".$name->getTable()."`
                   WHERE `itemtype` = 'NetworkPort'
                   AND `items_id` = '$networkPortID'";

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
         echo "<a href='".$name->getLinkURL()."'>".$name->getTypeName(1)."</a>";
         echo "<input type='hidden' name='NetworkName_id' value='".$name->getID()."'>\n";
      } else {
         echo $name->getTypeName(1);
      }
      echo "</th>\n";

      echo "</tr><tr class='tab_bg_1'>";

      echo "<td>" . self::getTypeName(1) . "</td><td>\n";
      Html::autocompletionTextField($name, "name", array('name' => 'NetworkName_name'));
      echo "</td>\n";

      echo "<td>".FQDN::getTypeName(1)."</td><td>";
      Dropdown::show(getItemTypeForTable(getTableNameForForeignKeyField("fqdns_id")),
                     array('value'       => $name->fields["fqdns_id"],
                           'name'        => 'NetworkName_fqdns_id',
                           'entity'      => $name->getEntityID(),
                           'displaywith' => array('view')));
      echo "</td>\n";

      echo "</tr><tr class='tab_bg_1'>\n";

      $address = new IPAddress();
      echo "<td>".$address->getTypeName(2);
      $address->showAddButtonForChildItem($name, 'NetworkName__ipaddresses');
      echo "</td>";
      echo "<td>";
      $address->showFieldsForItemForm($name, 'NetworkName__ipaddresses', 'name');
      echo "</td>";

      echo "<td>".IPNetwork::getTypeName(2)."&nbsp;";
      Html::showToolTip(__('IP network is not included in the database. However, you can see current available networks.'));
      echo "</td><td>";
      IPNetwork::showIPNetworkProperties($name->getEntityID());
      echo "</td>\n";

      echo "</tr>\n";
   }


   /**
    * @since version 0.84
    *
    * @param $itemtype
    * @param $base            HTMLTableBase object
    * @param $super           HTMLTableSuperHeader object (default NULL
    * @param $father          HTMLTableHeader object (default NULL)
    * @param $options   array
   **/
   static function getHTMLTableHeader($itemtype, HTMLTableBase $base,
                                      HTMLTableSuperHeader $super=NULL,
                                      HTMLTableHeader $father=NULL, array $options=array()) {

      $column_name = __CLASS__;

      if (!isset($options['dont_display'][$column_name])) {

         $content = self::getTypeName();
         if (isset($options['column_links'][$column_name])) {
            $content = "<a href='".$options['column_links'][$column_name]."'>$content</a>";
         }
         $name_header = $base->addHeader($column_name, $content, $super, $father);
         $name_header->setItemType('NetworkName');
         $father_for_children = $name_header;

      } else {
         $father_for_children = $father;
      }

      NetworkAlias::getHTMLTableHeader(__CLASS__, $base, $super, $father_for_children, $options);
      IPAddress::getHTMLTableHeader(__CLASS__, $base, $super, $father_for_children, $options);
   }


   /**
    * @since version 0.84
    *
    * @param $row             HTMLTableRow object
    * @param $item            CommonDBTM object (default NULL)
    * @param $father          HTMLTableCell object (default NULL)
    * @param $options   array
   **/
   static function getHTMLTableCellsForItem(HTMLTableRow $row=NULL, CommonDBTM $item=NULL,
                                            HTMLTableCell $father=NULL, array $options=array()) {
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
                                        AND `glpi_ipaddresses`.`itemtype` = 'NetworkName')";
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
                      ORDER BY $ORDER";
            break;

        case 'NetworkPort' :
            $query = "SELECT `id`
                      FROM `glpi_networknames`
                      WHERE `itemtype` = '".$item->getType()."'
                            AND `items_id` = '".$item->getID()."'";
            break;

        case 'NetworkEquipment' :
            $query = "SELECT `glpi_networknames`.`id`
                      FROM `glpi_networknames`, `glpi_networkports`
                      WHERE `glpi_networkports`.`itemtype` = '".$item->getType()."'
                            AND `glpi_networkports`.`items_id` ='".$item->getID()."'
                            AND `glpi_networknames`.`itemtype` = 'NetworkPort'
                            AND `glpi_networknames`.`items_id` = `glpi_networkports`.`id`";
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

            $internetName = $address->getInternetName();
            if (empty($internetName)) {
               $internetName = "(".$line["id"].")";
            }
            $content  = "<a href='" . $address->getLinkURL(). "'>".$internetName."</a>";

            if ($canedit) {

               $content .= "<br>";
               $content .= Html::getSimpleForm($address->getFormURL(), 'remove', __('Dissociate'),
                                               array('remove_address' => 'unaffect',
                                                     'id'             => $address->getID()),
                                               $CFG_GLPI["root_doc"] . "/pics/sub_dropdown.png");

               $content .= "&nbsp;";
               $content .= Html::getSimpleForm($address->getFormURL(), 'remove', __('Purge'),
                                               array('remove_address' => 'purge',
                                                     'id'             => $address->getID()),
                                               $CFG_GLPI["root_doc"] . "/pics/delete.png");
            }

            if (!isset($options['dont_display'][$column_name])) {
               $header= $row->getGroup()->getHeaderByName('Internet', $column_name);
               $name_cell = $row->addCell($header, $content, $father, $address);
               $father_for_children = $name_cell;
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
   static function showForItem(CommonGLPI $item, $withtemplate=0) {
      global $DB, $CFG_GLPI;

      $table_options = array('createRow' => true);

      if (($item->getType() == 'FQDN')
          || ($item->getType() == 'NetworkEquipment')) {
         if (isset($_POST["start"])) {
            $start = $_POST["start"];
         } else {
            $start = 0;
         }

         if (!empty($_POST["order"])) {
            $table_options['order'] = $_POST["order"];
         } else {
            $table_options['order'] = 'name';
         }

         if ($item->getType() == 'FQDN') {
            $table_options['column_links'] = array('NetworkName'
                                                         => 'javascript:reloadTab("order=name");',
                                                   'NetworkAlias'
                                                         => 'javascript:reloadTab("order=alias");',
                                                   'IPAddress'
                                                         => 'javascript:reloadTab("order=ip");');
         }

         $table_options['SQL_options']  = "LIMIT ".$_SESSION['glpilist_limit']."
                                           OFFSET $start";

         $canedit = false;

      } else {
         $canedit = true;
      }

      $table_options['canedit']  = $canedit;
      $table                     = new HTMLTableMain();
      $column                    = $table->addHeader('Internet', self::getTypeName(2));
      $t_group                   = $table->createGroup('Main', '');
      $address                   = new self();

      self::getHTMLTableHeader(__CLASS__, $t_group, $column, NULL, $table_options);

      $t_row   = $t_group->createRow();

      // Reorder the columns for better display
      $display_table = true;
      switch ($item->getType()) {
         case 'NetworkPort' :
         case 'FQDN' :
            break;
      }

      self::getHTMLTableCellsForItem($t_row, $item, NULL, $table_options);

      // Do not display table for netwokrport if only one networkname
      if (($item->getType() == 'NetworkPort')
          && ($table->getNumberOfRows() <= 1)) {
         $display_table = false;
      }

      if ($display_table) {
         if ($table->getNumberOfRows() > 0) {

            if ($item->getType() == 'FQDN') {
               Html::printAjaxPager(self::getTypeName(2), $start, self::countForItem($item));
            }
            Session::initNavigateListItems(__CLASS__,
                                    //TRANS : %1$s is the itemtype name,
                                    //        %2$s is the name of the item (used for headings of a list)
                                           sprintf(__('%1$s = %2$s'),
                                                   $item->getTypeName(1), $item->getName()));
            $table->display(array('display_title_for_each_group' => false,
                                  'display_thead'                => false,
                                  'display_tfoot'                => false));
            if ($item->getType() == 'FQDN') {
               Html::printAjaxPager(self::getTypeName(2), $start, self::countForItem($item));
            }
         } else {
            echo "<table class='tab_cadre_fixe'><tr><th>".__('No network name found')."</th></tr>";
            echo "</table>";
         }
      }

      if ($item->getType() == 'NetworkPort') {

         $items_id = $item->getID();
         $itemtype = $item->getType();

         echo "<div class='center'>\n";
         echo "<table class='tab_cadre'>\n";

         echo "<tr><th>".__('Add a network name')."</th></tr>";

         echo "<tr><td class='center'>";
         echo "<a href=\"" . $address->getFormURL()."?items_id=$items_id&itemtype=$itemtype\">";
         echo _x('network name','New one')."</a>";
         echo "</td></tr>\n";

         echo "<tr><td class='center'>";
         echo "<form method='post' action='".$address->getFormURL()."'>\n";
         echo "<input type='hidden' name='items_id' value='$items_id'>\n";
         echo "<input type='hidden' name='itemtype' value='$itemtype'>\n";

         _e('Not associated one');
         echo "&nbsp;";
         self::dropdown(array('name'      => 'addressID',
                              'condition' => '`items_id`=0'));
         echo "&nbsp;<input type='submit' name='assign_address' value='"._sx('button','Associate').
                      "' class='submit'>";
         Html::closeForm();
         echo "</td></tr>\n";

         echo "</table>\n";
         echo "</div>\n";
      }

   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

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
                                        "`fqdns_id` = '".$item->fields["id"]."'");

         case 'NetworkPort' :
            return countElementsInTable('glpi_networknames',
                                        "itemtype = '".$item->getType()."'
                                             AND items_id = '".$item->getID()."'");

         case 'NetworkEquipment' :
            $query = "SELECT DISTINCT COUNT(*) AS cpt
                      FROM `glpi_networknames`, `glpi_networkports`
                      WHERE `glpi_networkports`.`itemtype` = '".$item->getType()."'
                            AND `glpi_networkports`.`items_id` ='".$item->getID()."'
                            AND `glpi_networknames`.`itemtype` = 'NetworkPort'
                            AND `glpi_networknames`.`items_id` = `glpi_networkports`.`id`";
            $result = $DB->query($query);
            $ligne  = $DB->fetch_assoc($result);
            return $ligne['cpt'];
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if ($item->getID()
          && $item->can($item->getField('id'),'r')) {
         if ($_SESSION['glpishow_count_on_tabs']) {
            return self::createTabEntry(self::getTypeName(2), self::countForItem($item));
         }
         return self::getTypeName(2);
      }
      return '';
   }

}
?>
