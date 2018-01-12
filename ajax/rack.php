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

$AJAX_INCLUDE = 1;
include ('../inc/includes.php');
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();

if (isset($_REQUEST['action'])) {
   $answer = [];

   switch ($_REQUEST['action']) {
      case 'move_item':
         $item_rack = new Item_Rack;
         $item_rack->getFromDB((int) $_POST['id']);
         $answer['status'] = $item_rack->update([
            'id'       => (int) $_POST['id'],
            'position' => (int) $_POST['position'],
            'hpos'     => (int) $_POST['hpos'],
         ]);
         break;

      case 'move_pdu':
         $pdu_rack = new PDU_Rack;
         $pdu_rack->getFromDB((int) $_POST['id']);
         $answer['status'] = $pdu_rack->update([
            'id'       => (int) $_POST['id'],
            'position' => (int) $_POST['position']
         ]);
         break;

      case 'move_rack':
         $rack = new Rack;
         $rack->getFromDB((int) $_POST['id']);
         $answer['status'] = $rack->update([
            'id'         => (int) $_POST['id'],
            'dcrooms_id' => (int) $_POST['dcrooms_id'],
            'position'   => (int) $_POST['x'].",".(int) $_POST['y'],
         ]);
         break;

      case 'show_pdu_form':
         PDU_Rack::showFirstForm((int) $_REQUEST['racks_id']);
         exit;
   }

   echo json_encode($answer);
}
