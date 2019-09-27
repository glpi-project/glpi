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

use Glpi\Inventory\Conf;

include ('../inc/includes.php');

Session::checkRight("config", READ);

Html::header(__('Inventory'), $_SERVER['PHP_SELF'], "admin", "glpi\inventory\inventory");

$conf = new Conf();

if (isset($_FILES['importfile']) && $_FILES['importfile']['tmp_name'] != '') {
   $conf->importFile($_FILES);
   Html::back();
}

if (isset($_POST['update'])) {
   unset($_POST['update']);
   $conf->saveConf($_POST);
   Session::addMessageAfterRedirect(
      __('Configuration has been updated'),
      false,
      INFO
   );
   Html::back();
}

$conf->display(['id' => 1]);

Html::footer();
