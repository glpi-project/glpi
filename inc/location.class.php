<?php
/*
 * @version $Id$
 -------------------------------------------------------------------------
 GLPI - Gestionnaire Libre de Parc Informatique
 Copyright (C) 2015 Teclib'.

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
   public $dohistory       = true;
   var $can_be_translated  = true;

   static $rightname       = 'location';
   const CONFIG_PARENT                  = 1;
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
                   array('name'  => 'address',
                         'label' => __('Address'),
                         'type'  => 'textarea',
                         'list'  => true),
                   array('name'  => 'inheritance',
                         'label' => __('Inheritance of the parent entity'),
                         'type'  => 'bool',
                         'list'  => true),
                   array('name'  => 'inheritance_address',
                         'label' => '',
                         'type'  => 'link',
                         'list'  => false),
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
   /**
    * @see CommonDropdown::displaySpecificTypeField()
   **/
   function displaySpecificTypeField($ID, $field=array()) {
      global $CFG_GLPI;
      switch ($field['type']) {
         case 'link' :
            echo "<span id='address_parent'>";
            $location = new Location();
            if ($location->getFromDB($ID)) {
               if (isset($location->fields['inheritance']) && $location->fields['inheritance'] && $location->fields['locations_id'] != 0) {
                  $ancestors       = getAncestorsOf('glpi_locations', $ID);
                  $location_parent = new self();
                  if (count($ancestors) == 0) {
                     $location_parent->getFromDB($location->fields['locations_id']);
                     $address = $location_parent->fields['address'];
                  } else {
                     $location_parent->getFromDB($location->fields['locations_id']);
                     $address = $location_parent->fields['address'];
                     foreach ($ancestors as $ancestor) {
                        if ($location->getFromDB($ancestor)) {
                           if (!$location->fields['inheritance']) {
                              $address = $location->fields['address'];
                           }
                        }
                     }
                  }
                  echo $address;
               }
            }
            echo "</span>";
            $ID = empty($ID)?0:$ID;
            $root_doc = $CFG_GLPI['root_doc'];
            echo Html::scriptBlock("
               var disabledInheritance = function(inheritance, bool){
               if(bool){
                  inheritance.select2(\"val\", 0);
               }
                  inheritance.prop('disabled', bool);
               }
               var load = function(){
                  var parent = $(\"input[id^='dropdown_locations_id']\");
                  var inheritance = $(\"select[id^='dropdown_inheritance']\");
                  if(parent.val() == 0){
                     disabledInheritance(inheritance, true);
                  }
                  parent.on('change', function () {
                     getAddress(inheritance, parent);
                     if(parent.val() == 0){
                        disabledInheritance(inheritance, true);
                     }else{
                        disabledInheritance(inheritance, false);
                     }
                  });
                  inheritance.on('change', function () {
                      getAddress(inheritance, parent);
                  });
               }
               load();
               
               var getAddress = function(inheritance, parent){
                  if(inheritance.val() == 1){
                     $.ajax({
                        url:  '$root_doc/ajax/location.php',
                        type: \"POST\",
                        dataType: \"html\",
                        data: {
                            action: 'loadAddress',
                            parent: parent.val(),
                            locations_id: $ID,
                        },
                        success: function (response, opts) {
                           $('span[id=\"address_parent\"]').html(response);
                        }
                     });
                  }else{
                     $('span[id=\"address_parent\"]').html('');
                  }
               }
           " );
         break;
      }
   }
   
   /**
    * Returns the address of the parent place
    * @param type $parent
    * @return type
    */
   static function getAddress($parent) {
      $ancestors = getAncestorsOf('glpi_locations', $parent);
      $location  = new self();
      $address   = "";
      if ($location->getFromDB($parent)) {
         //if the parent does not allow inheritance
         if (!$location->fields['inheritance']) {
            $address = $location->fields['address'];
         } else {
            if (count($ancestors) == 0) {
               $address = $location->fields['address'];
            } else {
               $location->getFromDB($parent);
               $address = $location->fields['address'];
               foreach ($ancestors as $ancestor) {
                  if ($location->getFromDB($ancestor)) {
                     if (!$location->fields['inheritance']) {
                        $address = $location->fields['address'];
                     }
                  }
               }
            }
         }
      }
      return $address;
   }
   static function getTypeName($nb=0) {
      return _n('Location','Locations',$nb);
   }


   static function getSearchOptionsToAdd() {

      $tab                      = array();

      $tab[3]['table']          = 'glpi_locations';
      $tab[3]['field']          = 'completename';
      $tab[3]['name']           = __('Location');
      $tab[3]['datatype']       = 'dropdown';

      $tab[91]['table']         = 'glpi_locations';
      $tab[91]['field']         = 'building';
      $tab[91]['name']          = __('Building number');
      $tab[91]['massiveaction'] = false;
      $tab[91]['datatype']      = 'string';

      $tab[92]['table']         = 'glpi_locations';
      $tab[92]['field']         = 'room';
      $tab[92]['name']          = __('Room number');
      $tab[92]['massiveaction'] = false;
      $tab[92]['datatype']      = 'string';

      $tab[93]['table']         = 'glpi_locations';
      $tab[93]['field']         = 'comment';
      $tab[93]['name']          = __('Location comments');
      $tab[93]['massiveaction'] = false;
      $tab[93]['datatype']      = 'text';
      
      $tab[94]['table']         = 'glpi_locations';
      $tab[94]['field']         = 'address';
      $tab[94]['name']          = __('Address');
      $tab[94]['massiveaction'] = false;
      $tab[94]['datatype']      = 'specific';
      $tab[94]['nosearch']      = true;
      return $tab;
   }


   /**
    * Get search function for the class
    *
    * @return array of search option
   **/
   function getSearchOptions() {

      $tab                 = parent::getSearchOptions();

      $tab[11]['table']    = $this->getTable();
      $tab[11]['field']    = 'building';
      $tab[11]['name']     = __('Building number');
      $tab[11]['datatype'] = 'text';

      $tab[12]['table']    = $this->getTable();
      $tab[12]['field']    = 'room';
      $tab[12]['name']     = __('Room number');
      $tab[12]['datatype'] = 'text';

      $tab[20]['table']         = 'glpi_locations';
      $tab[20]['field']         = 'longitude';
      $tab[20]['name']          = __('Longitude');
      $tab[20]['massiveaction'] = false;
      $tab[20]['datatype']      = 'string';

      $tab[21]['table']         = 'glpi_locations';
      $tab[21]['field']         = 'latitude';
      $tab[21]['name']          = __('Latitude');
      $tab[21]['massiveaction'] = false;
      $tab[21]['datatype']      = 'string';

      $tab[22]['table']         = 'glpi_locations';
      $tab[22]['field']         = 'altitude';
      $tab[22]['name']          = __('Altitude');
      $tab[22]['massiveaction'] = false;
      $tab[22]['datatype']      = 'string';
      
      $tab[23]['table']         = 'glpi_locations';
      $tab[23]['field']         = 'address';
      $tab[23]['name']          = __('Address');
      $tab[23]['massiveaction'] = false;
      $tab[23]['datatype']      = 'specific';
      $tab[23]['nosearch']      = true;
      return $tab;
   }
   
   /**
    * @since version 0.84 
    *
    * @param $field
    * @param $values
    * @param $options   array
   **/
   static function getSpecificValueToDisplay($field, $values, array $options = array()) {
      if (!is_array($values)) {
         $values = array($field => $values);
      }
      switch ($field) {
         case 'address' :
            if ($values["inheritance"] == self::CONFIG_PARENT) {
               return __('Inheritance of the parent entity');
            }
            if($values["address"] == '0'){
               return " ";
            }
            return $values[$field];
      }
      return parent::getSpecificValueToDisplay($field, $values, $options);
   }
   
   function prepareInputForAdd($input) {
      if(isset($input['inheritance']) && $input['inheritance'] && isset($input['address'])){
         $input['address'] = "";
      }
      $input = parent::prepareInputForAdd($input);
      return $input;
   }
   
   function prepareInputForUpdate($input) {
      if(isset($input['inheritance']) && $input['inheritance'] && isset($input['address'])){
         $input['address'] = "";
      }
      $input = parent::prepareInputForUpdate($input);
      return $input;
   }
   function defineTabs($options=array()) {
      $ong = parent::defineTabs($options);
      $this->addStandardTab('Netpoint', $ong, $options);
      $this->addStandardTab(__CLASS__,$ong, $options);

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
         Html::printAjaxPager('',  $start, $number);

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
?>