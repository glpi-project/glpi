<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
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

if (strpos($_SERVER['PHP_SELF'], "dropdownSoftwareLicense.php")) {
   $AJAX_INCLUDE = 1;
   include ('../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkRight("software", UPDATE);

if ($_POST['softwares_id'] > 0) {
   if (!isset($_POST['value'])) {
      $_POST['value'] = 0;
   }

   // Make a select box
   $iterator = $DB->request([
      'DISTINCT'  => true,
      'FROM'      => 'glpi_softwarelicenses',
      'WHERE'     => [
         'glpi_softwarelicenses.softwares_id'   => (int)$_POST['softwares_id']
      ] + getEntitiesRestrictCriteria('glpi_softwarelicenses', 'entities_id', $_POST['entity_restrict'], true),
      'ORDERBY'   => 'name'
   ]);
   $number = count($iterator);

   $values = [];
   if ($number) {
      while ($data = $iterator->next()) {
         $ID     = $data['id'];
         $output = $data['name'];

         if (empty($output) || $_SESSION['glpiis_ids_visible']) {
            $output = sprintf(__('%1$s (%2$s)'), $output, $ID);
         }

         $values[$ID] = $output;
      }
   }
   Dropdown::showFromArray($_POST['myname'], $values, ['display_emptychoice' => true]);
}
