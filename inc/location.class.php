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

/// Location class
class Location extends CommonTreeDropdown {

   // From CommonDBTM
   public $dohistory          = true;
   public $can_be_translated  = true;

   static $rightname          = 'location';



   function getAdditionalFields() {

      return [
         [
            'name'  => $this->getForeignKeyField(),
            'label' => __('As child of'),
            'type'  => 'parent',
            'list'  => false
         ], [
            'name'   => 'address',
            'label'  => __('Address'),
            'type'   => 'text',
            'list'   => true
         ], [
            'name'   => 'postcode',
            'label'  => __('Postal code'),
            'type'   => 'text',
            'list'   => true
         ], [
            'name'   => 'town',
            'label'  => __('Town'),
            'type'   => 'text',
            'list'   => true
         ], [
            'name'   => 'state',
            'label'  => _x('location', 'State'),
            'type'   => 'text',
            'list'   => true
         ], [
            'name'   => 'country',
            'label'  => __('Country'),
            'type'   => 'text',
            'list'   => true
         ], [
            'name'  => 'building',
            'label' => __('Building number'),
            'type'  => 'text',
            'list'  => true
         ], [
            'name'  => 'room',
            'label' => __('Room number'),
            'type'  => 'text',
            'list'  => true
         ], [
            'name'   => 'setlocation',
            'type'   => 'setlocation',
            'label'  => __('Location on map'),
            'list'   => false
         ], [
            'name'  => 'latitude',
            'label' => __('Latitude'),
            'type'  => 'text',
            'list'  => true
         ], [
            'name'  => 'longitude',
            'label' => __('Longitude'),
            'type'  => 'text',
            'list'  => true
         ], [
            'name'  => 'altitude',
            'label' => __('Altitude'),
            'type'  => 'text',
            'list'  => true
         ]
      ];
   }


   static function getTypeName($nb = 0) {
      return _n('Location', 'Locations', $nb);
   }


   static public function rawSearchOptionsToAdd() {
      $tab = [];

      $tab[] = [
         'id'                 => '3',
         'table'              => 'glpi_locations',
         'field'              => 'completename',
         'name'               => __('Location'),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '101',
         'table'              => 'glpi_locations',
         'field'              => 'address',
         'name'               => __('Address'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '102',
         'table'              => 'glpi_locations',
         'field'              => 'postcode',
         'name'               => __('Postal code'),
         'massiveaction'      => true,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '103',
         'table'              => 'glpi_locations',
         'field'              => 'town',
         'name'               => __('Town'),
         'massiveaction'      => true,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '104',
         'table'              => 'glpi_locations',
         'field'              => 'state',
         'name'               => _x('location', 'State'),
         'massiveaction'      => true,
         'datatype'           => 'string'
      ];

      $tab[] = [
         'id'                 => '105',
         'table'              => 'glpi_locations',
         'field'              => 'country',
         'name'               => __('Country'),
         'massiveaction'      => true,
         'datatype'           => 'string'
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

      $tab[] = [
         'id'                 => '998',
         'table'              => 'glpi_locations',
         'field'              => 'latitude',
         'name'               => __('Latitude'),
         'massiveaction'      => false,
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '999',
         'table'              => 'glpi_locations',
         'field'              => 'longitude',
         'name'               => __('Longitude'),
         'massiveaction'      => false,
         'datatype'           => 'text'
      ];

      return $tab;
   }

   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

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
         'id'                 => '21',
         'table'              => 'glpi_locations',
         'field'              => 'latitude',
         'name'               => __('Latitude'),
         'massiveaction'      => false,
         'datatype'           => 'string'
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
         'id'                 => '22',
         'table'              => 'glpi_locations',
         'field'              => 'altitude',
         'name'               => __('Altitude'),
         'massiveaction'      => false,
         'datatype'           => 'string'
      ];

      return $tab;
   }


   function defineTabs($options = []) {

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
    * @since 0.85
    *
    * @see CommonTreeDropdown::getTabNameForItem()
   **/
   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         switch ($item->getType()) {
            case __CLASS__ :
               $ong    = [];
               $ong[1] = $this->getTypeName(Session::getPluralNumber());
               $ong[2] = _n('Item', 'Items', Session::getPluralNumber());
               return $ong;
         }
      }
      return '';
   }


   /**
    * @since 0.85
   **/
   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

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
    * @since 0.85
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

      if ($crit) {
         $table = getTableForItemType($crit);
         $criteria = [
            'SELECT' => [
               "$table.id",
               "$crit AS type"
            ],
            'FROM'   => $table,
            'WHERE'  => [
               "$table.locations_id"   => $locations_id,
               'is_deleted'            => 0
            ] + getEntitiesRestrictCriteria($table, 'entities_id')
         ];
      } else {
         $union = new \QueryUnion();
         foreach ($CFG_GLPI['location_types'] as $type) {
            $table = getTableForItemType($type);
            $union->addQuery([
               'SELECT' => [
                  'id',
                  new \QueryExpression("'$type' AS type")
               ],
               'FROM'   => $table,
               'WHERE'  => [
                  "$table.locations_id"   => $locations_id
               ] + getEntitiesRestrictCriteria($table, 'entities_id')
            ]);
         }
         $criteria = ['FROM' => $union];
      }

      $start  = (isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0);
      $criteria['START'] = $start;
      $criteria['LIMIT'] = $_SESSION['glpilist_limit'];

      $iterator = $DB->request($criteria);
      $number = count($iterator);;
      if ($start >= $number) {
         $start = 0;
      }
      // Mini Search engine
      echo "<table class='tab_cadre_fixe'>";
      echo "<tr class='tab_bg_1'><th colspan='2'>".__('Type')."</th></tr>";
      echo "<tr class='tab_bg_1'><td class='center'>";
      echo __('Type')."&nbsp;";
      Dropdown::showItemType($CFG_GLPI['location_types'],
                             ['value'      => $crit,
                                   'on_change'  => 'reloadTab("start=0&criterion="+this.value)']);
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

         while ($data = $iterator->next()) {
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

   function displaySpecificTypeField($ID, $field = []) {
      switch ($field['type']) {
         case 'setlocation':
            echo "<div id='setlocation_container'></div>";
            $js = "var map_elt, _marker;
            var _setLocation = function(lat, lng) {
               if (_marker) {
                  map_elt.removeLayer(_marker);
               }
               _marker = L.marker([lat, lng]).addTo(map_elt);
               map_elt.fitBounds(
                  L.latLngBounds([_marker.getLatLng()]), {
                     padding: [50, 50],
                     maxZoom: 10
                  }
               );
            };

            var _autoSearch = function() {
               var _tosearch = '';
               var _address = $('*[name=address]').val();
               var _town = $('*[name=town]').val();
               var _country = $('*[name=country]').val();
               if (_address != '') {
                  _tosearch += _address;
               }
               if (_town != '') {
                  if (_address != '') {
                     _tosearch += ' ';
                  }
                  _tosearch += _town;
               }
               if (_country != '') {
                  if (_address != '' || _town != '') {
                     _tosearch += ' ';
                  }
                  _tosearch += _country;
               }

               $('.leaflet-control-geocoder-form > input[type=text]').val(_tosearch);
            }

            $(function(){
               map_elt = initMap($('#setlocation_container'), 'setlocation', '200px');

               var osmGeocoder = new L.Control.OSMGeocoder({
                  collapsed: false,
                  placeholder: '".__s('Search')."',
                  text: '".__s('Search')."'
               });
               map_elt.addControl(osmGeocoder);
               _autoSearch();

               function onMapClick(e) {
                  var popup = L.popup();
                  popup
                     .setLatLng(e.latlng)
                     .setContent('SELECTPOPUP')
                     .openOn(map_elt);
               }

               map_elt.on('click', onMapClick);

               map_elt.on('popupopen', function(e){
                  var _popup = e.popup;
                  var _container = $(_popup._container);

                  var _clat = _popup._latlng.lat.toString();
                  var _clng = _popup._latlng.lng.toString();

                  _popup.setContent('<p><a href=\'#\'>".__s('Set location here')."</a></p>');

                  $(_container).find('a').on('click', function(e){
                     e.preventDefault();
                     _popup.remove();
                     $('*[name=latitude]').val(_clat);
                     $('*[name=longitude]').val(_clng).trigger('change');
                  });
               });

               var _curlat = $('*[name=latitude]').val();
               var _curlng = $('*[name=longitude]').val();

               if (_curlat && _curlng) {
                  _setLocation(_curlat, _curlng);
               }

               $('*[name=latitude],*[name=longitude]').on('change', function(){
                  var _curlat = $('*[name=latitude]').val();
                  var _curlng = $('*[name=longitude]').val();

                  if (_curlat && _curlng) {
                     _setLocation(_curlat, _curlng);
                  }
               });
            });";
            echo Html::scriptBlock($js);
            break;
         default:
            throw new \RuntimeException("Unknown {$field['type']}");
      }
   }
}
