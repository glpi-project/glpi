<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2003-2011 by the INDEPNET Development Team.

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

/// Class NetworkAlias
/// since version 0.84
class NetworkAlias extends FQDNLabel {

   var $refresh_page = true;
   // From CommonDBChild
   public $itemtype  = 'NetworkName';
   public $items_id  = 'networknames_id';
   public $dohistory = true;


   function canCreate() {

      if (!Session::haveRight('internet', 'w')) {
         return false;
      }

      if (!empty($this->fields['networknames_id'])) {
         $item = new NetworkName();
         if ($item->getFromDB($this->fields['networknames_id'])) {
            return $item->canCreate();
         }
      }

      return true;
   }


   function canView() {

      if (!Session::haveRight('internet', 'r')) {
         return false;
      }

      if (!empty($this->fields['networknames_id'])) {
         $item = new NetworkName();
         if ($item->getFromDB($this->fields['networknames_id']))
            return $item->canView();
      }

      return true;
   }


   static function getTypeName($nb=0) {
      global $LANG;

      if ($nb > 1) {
         return $LANG['Internet'][29];
      }
      return $LANG['Internet'][30];
   }


   /**
    * Get the full name (internet name) of a NetworkName
    *
    * @param $ID ID of the NetworkName
    *
    * @return its internet name, or empty string if invalid NetworkName
   **/
   static function getInternetNameFromID($ID) {

      $networkAlias = new NetworkAlias();
      if ($networkalias->can($ID, 'r'))
         return FQDNLabel::getInternetNameFromLabelAndDomainID($this->fields["name"],
                                                               $this->fields["fqdns_id"]);
      return "";
   }


   /**
    * Print the network alias form
    *
    * @param $ID integer ID of the item
    * @param $options array
    *     - target for the Form
    *     - withtemplate template or basic computer
    *
    *@return Nothing (display)
   **/
   function showForm ($ID, $options=array()) {
      global $CFG_GLPI, $LANG;

      if (!Session::haveRight("internet", "r")) {
         return false;
      }

      if ($ID > 0) {
         $this->check($ID,'r');
      } else {
         $this->check(-1, 'w', $options);
      }

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
      echo "&nbsp;:</td>\n<td>";

      if (!($ID>0)) {
         echo "<input type='hidden' name='networknames_id' value='".
               $this->fields["networknames_id"]."'>\n";
      }
      $this->displayRecursiveItems($recursiveItems, (isset($options['popup']) ? "Name" : "Link"));
      echo "</td><td>" . __('Name') . "</td><td>\n";
      Html::autocompletionTextField($this, "name");
      echo "</td></tr>\n";

      echo "<tr>";
      echo "<td>".FQDN::getTypeName()."&nbsp;:</td><td>";
      Dropdown::show(getItemTypeForTable(getTableNameForForeignKeyField("fqdns_id")),
                     array('value'        => $this->fields["fqdns_id"],
                           'name'         => 'fqdns_id',
                           'entity'       => $this->getEntityID(),
                           'displaywith'  => array('view')));
      echo "</td><td></td>";
      echo "</tr>\n";

      $this->showFormButtons($options);
      $this->addDivForTabs();

      return true;
   }


   /**
    * Print the tab for NetworkName object
    *
    * @param $networkNameID integer ID of the NetworkName
    * @param $fromForm display change if from the NetworkName form or other
    * @param $withtemplate integer : withtemplate param
    *
    *@return Nothing (display)
   **/
   static function showForNetworkName($networkNameID, $fromForm, $withtemplate=0) {
      global $DB, $LANG, $CFG_GLPI;

      $query = "SELECT *
                FROM `glpi_networkaliases`
                WHERE `networknames_id` = '$networkNameID'";

      $alias  = new NetworkAlias();
      $result = $DB->query($query);
      if ($DB->numrows($result) > 0) {
         if (!$fromForm) {
            echo "\n<ul>";
         }

         while ($line = $DB->fetch_array($result)) {
            if ($alias->getFromDB($line["id"])) {
               if ($fromForm) {
                  echo "<tr><td><a href='" . $alias->getLinkURL(). "'>";
               } else {
                  echo "<li>";
               }
               echo $alias->getInternetName();
               if ($fromForm) {
                  echo "</a>";
                  echo "<a href='" . $alias->getFormURL(). "?remove_alias=remove&id=" .
                         $alias->getID() . "'>&nbsp;";
                  //echo "<a href='#' onclick='javascript:reloadTab(\"remove_alias=" .
                  //     $alias->getID() . "\") ; return false;'>&nbsp;";
                  echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/delete.png\" alt=\"" .
                     __s('Delete') . "\" title=\"" . __s('Delete') . "\"></a>";
                  echo "</td></tr>";
               } else {
                  echo "</li>\n";
               }
            }
         }
         if (!$fromForm) {
            echo "</ul>\n";
         }
      } else {
         if (!$fromForm) {
            echo "&nbsp;";
         }
      }
   }


   /**
    * \brief Show aliases for an item from its form
    * Beware that the rendering can be different if readden from direct item form (ie : add new
    * NetworkAlias, remove, ...) or if readden from item of the item (for instance from the computer
    * form through NetworkPort::ShowForItem and NetworkName::ShowForItem).
    *
    * @param $item CommonDBTM object
    * @param $withtemplate integer : withtemplate param
   **/
   static function showForNetworkNameForm(CommonGLPI $item, $withtemplate=0) {
      global $DB, $CFG_GLPI, $LANG;

      $alias = new NetworkAlias();

      if (isset($_POST["remove_alias"])) {
         $alias->delete(array('id' => $_POST["remove_alias"]));
      }

      $networkNameID = $item->getID();
      echo "\n<div class='center'>";
      echo "\n<table class='tab_cadre'>\n";
      echo "<tr><th>" . self::getTypeName(2) . "</th></tr>\n";

      self::showForNetworkName($networkNameID, true, $withtemplate);

      echo "<tr><td class='center'>".
           "<a href='' onClick=\"var w = window.open('".$alias->getFormURL()."?networknames_id=" .
             $networkNameID ."&amp;popup=1&amp;rand=1' ,'glpipopup', 'height=400, ".
             "width=1000, top=100, left=100, scrollbars=yes' );w.focus();\">".__('Add').
           "</a></td></tr>\n";
      echo "\n</table>";
      echo "</div>\n";
   }


   /**
    * Show the aliases contained by the alias
    *
    * @param $item the FQDN owning the aliases
    * @param $withtemplate integer : withtemplate param
    *
   **/
   static function showForFQDN(CommonGLPI $item, $withtemplate) {
      global $DB, $CFG_GLPI, $LANG;

      $alias    = new NetworkAlias();
      $address = new NetworkName();
      $item->check($item->getID(), 'r');
      $canedit = $item->can($item->getID(), 'w');

      if (isset($_REQUEST["start"])) {
         $start = $_REQUEST["start"];
      } else {
         $start = 0;
      }
      if (!empty($_REQUEST["order"])) {
         $order = $_REQUEST["order"];
      } else {
         $order = "alias";
      }

      $number = countElementsInTable($alias->getTable(), "`fqdns_id`='".$item->getID()."'");

      echo "<br><div class='center'>";

      if ($number < 1) {
         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".self::getTypeName()."</th><th>".__('No item found')."</th></tr>";
         echo "</table>\n";
      } else {
         Html::printAjaxPager(self::getTypeName($number),$start,$number);

         echo "<table class='tab_cadre_fixe'><tr>";

         echo "<th><a href='javascript:reloadTab(\"order=alias\");'>".self::getTypeName().
              "</a></th>"; // Alias
         echo "<th><a href='javascript:reloadTab(\"order=realname\");'>".__("Computer's name").
              "</a></th>"; 
         echo "<th>".__('Comments')."</th>"; 
         echo "</tr>\n";

         Session::initNavigateListItems($item->getType(), $alias->getTypeName()."=".
                                        $item->fields['name']);

         $query = "SELECT `glpi_networkaliases`.`id` AS alias_id,
                          `glpi_networkaliases`.`name` AS alias,
                          `glpi_networknames`.`id` AS address_id,
                          `glpi_networkaliases`.`comment` AS comment
                   FROM `glpi_networkaliases`, `glpi_networknames`
                   WHERE `glpi_networkaliases`.`fqdns_id`='".$item->getID()."'
                         AND  `glpi_networknames`.`id` = `glpi_networkaliases`.`networknames_id`
                   ORDER BY `$order`
                   LIMIT ".$_SESSION['glpilist_limit']."
                   OFFSET $start";

         foreach ($DB->request($query) as $data) {
            Session::addToNavigateListItems($alias->getType(),$data["alias_id"]);
            if ($address->getFromDB($data["address_id"])) {
               echo "<tr class='tab_bg_1'>";
               echo "<td><a href='".$alias->getFormURL().'?id='.$data['alias_id']."'>" .
                          $data['alias']. "</a></td>";
               echo "<td><a href='".$address->getLinkURL()."'>".$address->getInternetName().
                    "</a></td>";
               echo "<td>".$data['comment']."</td>";
               echo "</tr>\n";
            }
         }

         echo "</table>\n";
      }
      echo "</div>\n";
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      switch ($item->getType()) {
         case 'NetworkName' :
            self::showForNetworkNameForm($item, $withtemplate);
            break;

         case 'FQDN' :
            self::showForFQDN($item, $withtemplate);
            break;
      }
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {
      global $LANG;

      if ($item->getID() && $item->can($item->getField('id'),'r')) {
         if ($_SESSION['glpishow_count_on_tabs']) {
            switch ($item->getType()) {
               case 'NetworkName' :
                  $numberElements = countElementsInTable($this->getTable(),
                                                         "networknames_id='".$item->getID()."'");
                  break;

               case 'FQDN' :
                  $numberElements = countElementsInTable($this->getTable(),
                                                         "fqdns_id='".$item->getID()."'");
            }
            return self::createTabEntry( self::getTypeName($numberElements), $numberElements);
         }
         return self::getTypeName(2);
      }
      return '';
   }
}
?>
