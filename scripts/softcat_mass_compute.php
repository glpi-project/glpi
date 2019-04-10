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

// COMPUTER ONLY UNDEF CATEGORIES
$ONLY_UNDEFINED = true;


$softcatrule = new RuleSoftwareCategoryCollection();
$soft        = new Software();

$query = "SELECT `id`, `softwarecategories_id`
          FROM `glpi_softwares`";

if ($result=$DB->query($query)) {
   if ($DB->numrows($result)>0) {
      while ($data=$DB->fetchArray($result)) {
         if (!$ONLY_UNDEFINED || $data['softwarecategories_id']==0) {
            $params = [];

            //Get software name and manufacturer
            $soft->getFromDB($data['id']);
            $params["name"]             = $soft->fields["name"];
            $params["manufacturers_id"] = $soft->fields["manufacturers_id"];

            //Process rules
            $soft->update($softcatrule->processAllRules(null, $soft->fields, $params));
         }
      }
   }
}
