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

Session::checkLoginUser();

if (Session::getCurrentInterface() == "helpdesk") {
   Html::helpHeader(Ticket::getTypeName(Session::getPluralNumber()), '', $_SESSION["glpiname"]);
} else {
   Html::header(Ticket::getTypeName(Session::getPluralNumber()), '', "helpdesk", "ticket");
}

$callback = <<<JS
   if ($('div[role="dialog"]:visible').length === 0) {
      window.location.reload();
   }
JS;

echo Html::manageRefreshPage(false, $callback);

if ($default = Glpi\Dashboard\Grid::getDefaultDashboardForMenu('mini_ticket', true)) {
   $dashboard = new Glpi\Dashboard\Grid($default, 33, 2, 'mini_core');
   $dashboard->show(true);
}

Search::show('Ticket');

if (Session::getCurrentInterface() == "helpdesk") {
   Html::helpFooter();
} else {
   Html::footer();
}

