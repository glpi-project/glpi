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

Session::checkRight("config", UPDATE);

// This has to be called before search process is called, in order to add
// "new" plugins in DB to be able to display them.
$plugin = new Plugin();
$plugin->checkStates(true);

Html::header(__('Setup'), $_SERVER['PHP_SELF'], "config", "plugin");

$catalog_btn = '<div class="center">'
   . '<a href="http://plugins.glpi-project.org" class="vsubmit" target="_blank">'
   . __('See the catalog of plugins')
   . '</a>'
   . '</div>';

echo $catalog_btn;

Search::show('Plugin');

echo $catalog_btn;

Html::footer();
