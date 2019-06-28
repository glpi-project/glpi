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

// spin.js dependency
require('spin.js');

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
require('../leaflet/plugins/leaflet-control-osm-geocoder/Control.OSMGeocoder');
require('../leaflet/plugins/leaflet-control-osm-geocoder/Control.OSMGeocoder.css');
require('leaflet-fullscreen');
require('leaflet-fullscreen/dist/leaflet.fullscreen.css');
