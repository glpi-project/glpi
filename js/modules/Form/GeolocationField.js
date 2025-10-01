/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

/* global initMap, L */

/**
 * Shows a Leaflet map based on some other form input fields that are present on the page.
 *
 * @since 10.1.0
 */
class GeolocationField {
    constructor(element_id) {
        this.element_id = CSS.escape(element_id);
        this.rand = Math.floor(Math.random() * 10000);
        this.marker = null;
        this.#init();
    }

    #init() {
        // Geolocation may be disabled in the browser (e.g. geo.enabled = false in firefox)
        if (!navigator.geolocation) {
            this.map = initMap($(`#${this.element_id}`), `setlocation_${this.rand}`, '400px');
            this.#finalizeMap();
        } else {
            navigator.geolocation.getCurrentPosition((pos) => {
                // Try to determine an appropriate zoom level based on accuracy
                const acc = pos.coords.accuracy;
                let zoom;
                if (acc > 3000) {
                    // Very low accuracy. Most likely a device without GPS or a cellular connection
                    zoom = 10;
                } else if (acc > 1000) {
                    // Low accuracy
                    zoom = 15;
                } else if (acc > 500) {
                    // Medium accuracy
                    zoom = 17;
                } else {
                    // High accuracy
                    zoom = 20;
                }
                this.map = initMap($(`#${this.element_id}`), `setlocation_${this.rand}`, '400px', {
                    position: [pos.coords.latitude, pos.coords.longitude],
                    zoom: zoom
                });
                this.#finalizeMap();
            }, () => {
                this.map = initMap($(`#${this.element_id}`), `setlocation_${this.rand}`, '400px');
                this.#finalizeMap();
            }, {enableHighAccuracy: true});
        }
    }

    #finalizeMap() {
        const geocoder = L.Control.geocoder({
            defaultMarkGeocode: false,
            errorMessage: __('No results found'),
            placeholder: __('Search')
        });
        geocoder.on('markgeocode', (e) => {
            this._map.fitBounds(e.geocode.bbox);
        });
        this.map.addControl(geocoder);
        this.#autoSearch();

        this.map.on('click', (e) => {
            const popup = L.popup();
            popup
                .setLatLng(e.latlng)
                .setContent('SELECTPOPUP')
                .openOn(this.map);
        });

        this.map.on('popupopen', (e) => {
            const _popup = e.popup;
            const _container = $(_popup._container);

            const _clat = _popup._latlng.lat.toString();
            const _clng = _popup._latlng.lng.toString();

            _popup.setContent(`<p><a href='#'>${__('Set location here')}</a></p>`);

            $(_container).find('a').on('click', (e) => {
                e.preventDefault();
                _popup.remove();
                $('*[name=latitude]').val(_clat);
                $('*[name=longitude]').val(_clng).trigger('change');
            });
        });

        let _curlat = $('*[name=latitude]').val();
        let _curlng = $('*[name=longitude]').val();

        if (_curlat && _curlng) {
            this.#setLocation(_curlat, _curlng);
        }

        $('*[name=latitude],*[name=longitude]').on('change', () => {
            _curlat = $('*[name=latitude]').val();
            _curlng = $('*[name=longitude]').val();

            if (_curlat && _curlng) {
                this.#setLocation(_curlat, _curlng);
            }
        });
    }

    #autoSearch() {
        let _tosearch = '';
        const _address = $('*[name=address]').val();
        const _town = $('*[name=town]').val();
        const _country = $('*[name=country]').val();
        if (_address !== '') {
            _tosearch += _address;
        }
        if (_town !== '') {
            if (_address !== '') {
                _tosearch += ' ';
            }
            _tosearch += _town;
        }
        if (_country !== '') {
            if (_address !== '' || _town !== '') {
                _tosearch += ' ';
            }
            _tosearch += _country;
        }

        $('.leaflet-control-geocoder-form > input[type=text]').val(_tosearch);
    }

    #setLocation(lat, lng) {
        if (this.marker) {
            this.map.removeLayer(this.marker);
        }
        this.marker = L.marker([lat, lng]).addTo(this.map);
        this.map.fitBounds(
            L.latLngBounds([this.marker.getLatLng()]), {
                padding: [50, 50],
                maxZoom: 10
            }
        );
    }
}

export default GeolocationField;
