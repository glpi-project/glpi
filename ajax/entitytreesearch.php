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

/**
 * @since 0.85
 */

$AJAX_INCLUDE = 1;

include ("../inc/includes.php");

header("Content-Type: application/json; charset=UTF-8");
Html::header_nocache();

Session::checkLoginUser();
$res = [];

$root_entities_for_profiles = array_column($_SESSION['glpiactiveprofile']['entities'], 'id');

if (isset($_POST['str'])) {
   $iterator = $DB->request([
      'FROM'   => 'glpi_entities',
      'WHERE'  => [
         'name' => ['LIKE', '%' . $_POST['str'] . '%']
      ],
      'ORDER'  => ['completename']
   ]);

   while ($data = $iterator->next()) {
      $ancestors = getAncestorsOf('glpi_entities', $data['id']);
      foreach ($ancestors as $val) {
         if (!in_array($val, $res)) {
            // root nodes are suffixed by, id are uniques in jstree.
            // so, in case of presence of this id in subtree of other nodes,
            // it will be removed from root nodes
            if (in_array($val, $root_entities_for_profiles)) {
               $val.= 'r';
            }
            $res[] = $val;
         }
      }
   }
}
$res = json_encode($res);
echo $res;
