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

include ('../inc/includes.php');
$dropdown = new Socket();
// Add a socket from item : format data
// see socket.class.php:607
if (isset($_REQUEST['_add_fromitem'])
      && isset($_REQUEST['_from_itemtype'])
      && isset($_REQUEST['_from_items_id'])) {

      $options['_add_fromitem'] = [
         '_from_itemtype' => $_REQUEST['_from_itemtype'] ,
         '_from_items_id' => $_REQUEST['_from_items_id'] ,
      ];

}

include (GLPI_ROOT . "/front/dropdown.common.form.php");





