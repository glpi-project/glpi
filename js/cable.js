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

function refreshAssetBreadcrumb(itemtype, items_id, dom_to_update) {
   // get asset breadcrumb
   $.ajax({
      method: 'GET',
      url: CFG_GLPI.root_doc + "/ajax/cable.php",
      data: {
         action: 'get_item_breadcrum',
         items_id: items_id,
         itemtype: itemtype,
      }
   }).success(function(html_breadcrum) {
      $('#' + dom_to_update).empty();
      $('#' + dom_to_update).append(html_breadcrum);
   });

}

function refreshNetworkPortDropdown(itemtype, items_id, dom_to_update) {
   // get networkport dropdown
   $.ajax({
      method: 'GET',
      url: CFG_GLPI.root_doc + "/ajax/cable.php",
      data: {
         action: 'get_networkport_dropdown',
         items_id: items_id,
         itemtype: itemtype,
      }
   }).success(function(html_data) {
      $('#' + dom_to_update).empty();
      $('#' + dom_to_update).append(html_data);
   });
}

function refreshSocketDropdown(itemtype, items_id, socketmodels_id, dom_name, dom_to_update) {
   // get networkport dropdown
   $.ajax({
      method: 'GET',
      url: CFG_GLPI.root_doc + "/ajax/cable.php",
      data: {
         action: 'get_socket_dropdown',
         items_id: items_id,
         itemtype: itemtype,
         socketmodels_id: socketmodels_id,
         dom_name: dom_name
      }
   }).success(function(html_data) {
      $('#' + dom_to_update).empty();
      $('#' + dom_to_update).append(html_data);
   });
}
