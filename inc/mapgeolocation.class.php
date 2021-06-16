<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2021 Teclib' and contributors.
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
 * Map geolocation
 **/
trait MapGeolocation {

   /**
    * get openstreetmap
    */
   public function showMap() {
      $rand = mt_rand();

      echo "<div id='setlocation_container_{$rand}'></div>";
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
         map_elt = initMap($('#setlocation_container_{$rand}'), 'setlocation_{$rand}', '200px');

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
   }
}
