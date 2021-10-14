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

use Glpi\Event;

include ('../inc/includes.php');

Session::checkValidSessionId();

$link = new ManualLink();
if (array_key_exists('id', $_REQUEST) && !$link->getFromDB($_REQUEST['id'])) {
   Toolbox::throwError(404, 'No item found for given id', 'string');
   Html::back();
}

if (array_key_exists('purge', $_POST) || array_key_exists('delete', $_POST)) {
   $link->check($_POST['id'], PURGE);

   if ($link->delete($_POST, 1)) {
      Event::log(
         $_POST['id'],
         'manuallinks',
         4,
         'tools',
        sprintf(__('%s purges an item'), $_SESSION['glpiname'])
      );
      $item = getItemForItemtype($link->fields['itemtype']);
      $item->getFromDB($link->fields['items_id']);
      Html::redirect($item->getLinkURL());
   }

   Html::back();
} else if (array_key_exists('add', $_POST)) {
   $link->check(-1, CREATE, $_POST);
   if ($id = $link->add($_POST)) {
      Event::log(
         $id,
         'manuallinks',
         4,
         'tools',
        sprintf(__('%1$s adds the item %2$s'), $_SESSION['glpiname'], $_POST['name'])
      );
      $item = getItemForItemtype($link->fields['itemtype']);
      $item->getFromDB($link->fields['items_id']);
      Html::redirect($item->getLinkURL());
   }
   Html::back();
} else if (array_key_exists('update', $_POST)) {
   $link->check($_POST['id'], UPDATE);
   if ($link->update($_POST)) {
      Event::log(
         $_POST['id'],
         'manuallinks',
         4,
         'tools',
        sprintf(__('%s updates an item'), $_SESSION['glpiname'])
      );
      $item = getItemForItemtype($link->fields['itemtype']);
      $item->getFromDB($link->fields['items_id']);
      Html::redirect($item->getLinkURL());
   }
   Html::back();
} else if (array_key_exists('id', $_GET)
           || (array_key_exists('itemtype', $_GET) && array_key_exists('items_id', $_GET))) {

   $id       = $link->isNewItem() ? null : $link->fields['id'];
   $itemtype = $link->isNewItem() ? $_GET['itemtype'] : $link->fields['itemtype'];
   $items_id = $link->isNewItem() ? $_GET['items_id'] : $link->fields['items_id'];

   Html::header(
      ManualLink::getTypeName(Session::getPluralNumber()),
      $_SERVER['PHP_SELF'],
      Html::getMenuSectorForItemtype($itemtype),
      $itemtype
   );

   $link->display(
      [
         'id'          => $id,
         'formoptions' => 'data-track-changes=true',

         'itemtype'    => $itemtype,
         'items_id'    => $items_id
      ]
   );

   Html::footer();

} else {
   Html::displayErrorAndDie('lost');
}
