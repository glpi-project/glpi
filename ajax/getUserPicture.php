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

$AJAX_INCLUDE = 1;

include ('../inc/includes.php');

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (!isset($_GET['users_id'])) {
   // Bad request
   Toolbox::logError("Missing users_id parameter");
   http_response_code(400);
   return;
} else if (!is_array($_GET['users_id'])) {
   $_GET['users_id'] = [$_GET['users_id']];
}

$_GET['users_id'] = array_unique($_GET['users_id']);

if (!isset($_GET['size'])) {
   $_GET['size'] = '100%';
}

if (!isset($_GET['allow_blank'])) {
   $_GET['allow_blank'] = false;
}

$user = new User();
$imgs = [];

foreach ($_GET['users_id'] as $user_id) {
   if ($user->getFromDB($user_id)) {
      if (!empty($user->fields['picture']) || $_GET['allow_blank']) {
         if (isset($_GET['type']) && $_GET['type'] == 'thumbnail') {
            $path = User::getThumbnailURLForPicture($user->fields['picture']);
         } else {
            $path = User::getURLForPicture($user->fields['picture']);
         }
         $img = Html::image($path, [
            'title'  => getUserName($user_id),
            'width'  => $_GET['size'],
            'height' => $_GET['size'],
            'class'  => isset($_GET['class']) ? $_GET['class'] : ''
         ]);
         if (isset($_GET['link']) && $_GET['link']) {
            $imgs[$user_id] = Html::link($img, User::getFormURLWithID($user_id));
         } else {
            $imgs[$user_id] = $img;
         }
      } else {
         // No picture and default image is not allowed.
         continue;
      }
   }
}

echo json_encode($imgs, JSON_FORCE_OBJECT);