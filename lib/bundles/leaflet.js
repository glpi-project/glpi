/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
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

// spin.js dependency
// Spinner object have to be accessible in window context
window.Spinner = require('spin.js').Spinner;

// Leaflet core lib
require('leaflet');
require('leaflet/dist/leaflet.css');
require('leaflet/dist/images/marker-icon-2x.png'); // image is not present in CSS and will not be copied automatically
require('leaflet/dist/images/marker-shadow.png'); // image is not present in CSS and will not be copied automatically

// Leaflet plugins
require('leaflet-spin');
require('leaflet.markercluster');
require('leaflet.markercluster/dist/MarkerCluster.css');
require('leaflet.markercluster/dist/MarkerCluster.Default.css');
require('leaflet.awesome-markers');
require('leaflet.awesome-markers/dist/leaflet.awesome-markers.css');
require('leaflet-control-geocoder');
require('leaflet-control-geocoder/dist/Control.Geocoder.css');
require('leaflet-fullscreen');
require('leaflet-fullscreen/dist/leaflet.fullscreen.css');
