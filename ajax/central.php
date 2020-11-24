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

// Send UTF8 Headers
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if ((!isset($_REQUEST['params']['_idor_token']) || empty($_REQUEST['params']['_idor_token'])) || !isset($_REQUEST['itemtype'])
   || !isset($_REQUEST['widget'])) {
   http_response_code(400);
   die();
}

$idor = $_REQUEST['params']['_idor_token'];
unset($_REQUEST['params']['_idor_token']);

if (!Session::validateIDOR([
      'itemtype'     => $_REQUEST['itemtype'],
      '_idor_token'  => $idor
   ] + $_REQUEST['params'])) {
   http_response_code(400);
   die();
}

$itemtype = $_REQUEST['itemtype'];
$params = $_REQUEST['params'];

switch ($_REQUEST['widget']) {
   case 'central_count':
      if (method_exists($itemtype, 'showCentralCount')) {
         $itemtype::showCentralCount($params['foruser'] ?? false);
      }
      break;
   case 'central_list':
      if (method_exists($itemtype, 'showCentralList')) {
         if (is_subclass_of($itemtype, CommonITILObject::class)) {
            $showgroupproblems = isset($params['showgroupproblems']) ? ($params['showgroupproblems'] !== 'false') : false;
            $itemtype::showCentralList($params['start'], $params['status'] ?? 'process', $showgroupproblems);
         }
      } else if ($itemtype === RSSFeed::class) {
         $personal = $params['personal'] !== 'false';
         $itemtype::showListForCentral($personal);
      } else if ($itemtype === Planning::class) {
         $itemtype::showCentral($params['who']);
      }
      break;
   default:
      echo __('Invalid widget');
}
