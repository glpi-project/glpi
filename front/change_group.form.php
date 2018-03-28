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

use Glpi\Event;

if (!defined('GLPI_ROOT')) {
   include ('../inc/includes.php');
}

$link = new Change_Group();
$item = new Change();

Session ::checkLoginUser();

if (isset($_POST['delete'])) {
   $link->check($_POST['id'], DELETE);
   $link->delete($_POST);

   Event::log($link->fields['changes_id'], "change", 4, "maintain",
              sprintf(__('%s deletes an actor'), $_SESSION["glpiname"]));

   if ($item->can($link->fields["changes_id"], READ)) {
      Html::redirect(Change::getFormURLWithID($link->fields['changes_id']));
   }
   Session::addMessageAfterRedirect(__('You have been redirected because you no longer have access to this item'),
                                    true, ERROR);

   Html::redirect($CFG_GLPI["root_doc"]."/front/change.php");
}

Html::displayErrorAndDie('Lost');
