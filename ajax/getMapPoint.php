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

include ('../inc/includes.php');
header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

$result = [];
if (!isset($_POST['itemtype']) || !isset($_POST['items_id']) || (int)$_POST['items_id'] < 1) {
   $result = [
      'success'   => false,
      'message'   => __('Required argument missing!')
   ];
} else {
   $itemtype = $_POST['itemtype'];
   $items_id = $_POST['items_id'];

   if ($itemtype != Location::getType()) {
      $item = new $itemtype();
      $found = $item->getFromDB($items_id);
      if ($found && isset($item->fields['locations_id']) && (int)$item->fields['locations_id'] > 0) {
         $itemtype = Location::getType();
         $items_id = $item->fields['locations_id'];
      } else {
         $result = [
            'success'   => false,
            'message'   => __('Element seems not geolocalized or cannot be found')
         ];
      }
   }

   if (!count($result)) {
      $item = new $itemtype();
      $item->getFromDB($items_id);
      if (!empty($item->fields['latitude']) && !empty($item->fields['longitude'])) {
         $result = [
            'name'   => $item->getName(),
            'lat'    => $item->fields['latitude'],
            'lng'    => $item->fields['longitude']
         ];
      } else {
         $result = [
            'success'   => false,
            'message'   => "<h3>".__("Location seems not geolocalized!")."</h3>".
                           "<a href='".$item->getLinkURL()."'>".__("Consider filling latitude and longitude on this location.")."</a>"
         ];
      }
   }
}

echo json_encode($result);
