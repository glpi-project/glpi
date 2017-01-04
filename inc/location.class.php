<?php
/*
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

/// Location class
class Location extends CommonTreeDropdown {

   // From CommonDBTM
   public $dohistory          = true;
   public $can_be_translated  = true;

   static $rightname          = 'location';



   function getAdditionalFields() {

      return array(array('name'  => $this->getForeignKeyField(),
                         'label' => __('As child of'),
                         'type'  => 'parent',
                         'list'  => false),
                   array('name'  => 'building',
                         'label' => __('Building number'),
                         'type'  => 'text',
                         'list'  => true),
                   array('name'  => 'room',
                         'label' => __('Room number'),
                         'type'  => 'text',
                         'list'  => true),
                   array('name'  => 'longitude',
                         'label' => __('Longitude'),
                         'type'  => 'text',
                         'list'  => true),
                   array('name'  => 'latitude',
                         'label' => __('Latitude'),
                         'type'  => 'text',
                         'list'  => true),
                   array('name'  => 'altitude',
                         'label' => __('Altitude'),
                         'type'  => 'text',
                         'list'  => true),
                         );
   }


   static function getTypeName($nb=0) {
      return _n('Location', 'Locations', $nb);
   }


   /**
    * Get the Search options to add to an item for the given Type
    *
    * @return a *not indexed* array of search options
    * More information on https://forge.indepnet.net/wiki/glpi/SearchEngine
    * @since 9.2
   **/
   static public function getSearchOptionsToAddNew() {
      $tab = [];

      $tab[] = [
         'id'                 => '3',
         'table'              => 'glpi_locations',
         'field'              => 'completename',
         'name'               => __('Location'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '91',
         'table'              => 'glpi_locations',
         'field'              => 'building',
         'name'               => __('Building number'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '92',
         'table'              => 'glpi_locations',
         'field'              => 'room',
         'name'               => __('Room number'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '93',
         'table'              => 'glpi_locations',
         'field'              => 'comment',
         'name'               => __('Location comments'),
         'massiveaction'      => false,
         'datatype'           => 'text'
      ];

      return $tab;
   }

   function getSearchOptionsNew() {
      $tab = parent::getSearchOptionsNew();

      $tab[] = [
         'id'                 => '11',
         'table'              => 'glpi_locations',
         'field'              => 'building',
         'name'               => __('Building number'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => 'glpi_locations',
         'field'              => 'room',
         'name'               => __('Room number'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '20',
         'table'              => 'glpi_locations',
         'field'              => 'longitude',
         'name'               => __('Longitude'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '21',
         'table'              => 'glpi_locations',
         'field'              => 'latitude',
         'name'               => __('Latitude'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '22',
         'table'              => 'glpi_locations',
         'field'              => 'altitude',
         'name'               => __('Altitude'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      return $tab;
   }


   function defineTabs($options=array()) {

      $ong = parent::defineTabs($options);
      $this->addStandardTab('Netpoint', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab(__CLASS__, $ong, $options);

      return $ong;
   }


   function cleanDBonPurge() {

      Rule::cleanForItemAction($this);
      Rule::cleanForItemCriteria($this, 'users_locations');
   }


   /**
    * @since version 0.85
    *
    * @see CommonTreeDropdown::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate=0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case __CLASS__ :
               $ong    = array();
               $ong[1] = $this->getTypeName(Session::getPluralNumber());
               $ong[2] = _n('Item', 'Items', Session::getPluralNumber());
               return $ong;
         }
      }
      return '';
   }


   /**
    * @since version 0.85
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum=1, $withtemplate=0) {

      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 1 :
               $item->showChildren();
               break;
            case 2 :
               $item->showItems();
               break;
         }
      }
      return true;
   }


   /**
    * Print the HTML array of items for a location
    *
    * @since version 0.85
    *
    * @return Nothing (display)
   **/
   function showItems() {
      global $DB, $CFG_GLPI;

      $locations_id = $this->fields['id'];
      $crit         = Session::getSavedOption(__CLASS__, 'criterion', '');

      if (!$this->can($locations_id, READ)) {
         return false;
      }

      $first = 1;
      $query = '';

      if ($crit) {
         $table = getTableForItemType($crit);
         $query = "SELECT `$table`.`id`, '$crit' AS type
                   FROM `$table`
                   WHERE `$table`.`locations_id` = '$locations_id' ".
                         getEntitiesRestrictRequest(" AND", $table, "entities_id");
      } else {
         foreach ($CFG_GLPI['location_types'] as $type) {
            $table = getTableForItemType($type);
            $query .= ($first ? "SELECT " : " UNION SELECT  ")."`id`, '$type' AS type
                      FROM `$table`
                      WHERE `$table`.`locations_id` = '$locations_id' ".
                            getEntitiesRestrictRequest(" AND", $table, "entities_id");
            $first = 0;
         }
      }

      $result = $DB->query($query);
      $number = $DB->numrows($result);
      $start  = (isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0);
      if ($start >= $number) {
         $start = 0;
      }
      // Mini Search engine
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><th colspan='2'>".__('Type')."</th></tr>";
      echo "<tr class='tab_bg_1'><td class='center'>";
      echo __('Type')."&nbsp;";
      Dropdown::showItemType($CFG_GLPI['location_types'],
                             array('value'      => $crit,
                                   'on_change'  => 'reloadTab("start=0&criterion="+this.value)'));
      echo "</td></tr></table>";

      if ($number) {
         echo "<div class='spaced'>";
         Html::printAjaxPager('', $start, $number);

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr><th>".__('Type')."</th>";
         echo "<th>".__('Entity')."</th>";
         echo "<th>".__('Name')."</th>";
         echo "<th>".__('Serial number')."</th>";
         echo "<th>".__('Inventory number')."</th>";
         echo "</tr>";

         $DB->data_seek($result, $start);
         for ($row=0 ; ($data=$DB->fetch_assoc($result)) && ($row<$_SESSION['glpilist_limit']) ; $row++) {
            $item = getItemForItemtype($data['type']);
            $item->getFromDB($data['id']);
            echo "<tr class='tab_bg_1'><td class='center top'>".$item->getTypeName()."</td>";
            echo "<td class='center'>".Dropdown::getDropdownName("glpi_entities",
                                                                 $item->getEntityID());
            echo "</td><td class='center'>".$item->getLink()."</td>";
            echo "<td class='center'>".
                  (isset($item->fields["serial"])? "".$item->fields["serial"]."" :"-");
            echo "</td>";
            echo "<td class='center'>".
                  (isset($item->fields["otherserial"])? "".$item->fields["otherserial"]."" :"-");
            echo "</td></tr>";
         }
      } else {
         echo "<p class='center b'>".__('No item found')."</p>";
      }
      echo "</table></div>";

   }

}
