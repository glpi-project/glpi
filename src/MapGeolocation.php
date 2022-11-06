<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

/**
 * Map geolocation
 **/
trait MapGeolocation
{
    /**
     * get openstreetmap
     */
    public function showMap()
    {
        $rand = mt_rand();

        echo "<div id='setlocation_container_{$rand}'></div>";
        $js = "
      $(function(){
         var map_elt, _marker;
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
         var finalizeMap = function() {
            var geocoder = L.Control.geocoder({
                defaultMarkGeocode: false,
                errorMessage: '" . __s('No result found') . "',
                placeholder: '" . __s('Search') . "'
            });
            geocoder.on('markgeocode', function(e) {
                this._map.fitBounds(e.geocode.bbox);
            });
            map_elt.addControl(geocoder);
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

               _popup.setContent('<p><a href=\'#\'>" . __s('Set location here') . "</a></p>');

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
         }
         navigator.geolocation.getCurrentPosition(function(pos) {
            // Try to determine an appropriate zoom level based on accuracy
            var acc = pos.coords.accuracy;
            if (acc > 3000) {
                // Very low accuracy. Most likely a device without GPS or a cellular connection
                var zoom = 10;
            } else if (acc > 1000) {
                // Low accuracy
                var zoom = 15;
            } else if (acc > 500) {
                // Medium accuracy
                var zoom = 17;
            } else {
                // High accuracy
                var zoom = 20;
            }
            map_elt = initMap($('#setlocation_container_{$rand}'), 'setlocation_{$rand}', '200px', {
                position: [pos.coords.latitude, pos.coords.longitude],
                zoom: zoom
            });
            finalizeMap();
         }, function() {
            map_elt = initMap($('#setlocation_container_{$rand}'), 'setlocation_{$rand}', '200px');
            finalizeMap();
         }, {enableHighAccuracy: true});

      });";
        echo Html::scriptBlock($js);
    }
}
