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

include ('../inc/includes.php');


$translation = new KnowbaseItemTranslation();
if (isset($_POST['add'])) {
   $translation->add($_POST);
   Html::back();
} else if (isset($_POST['update'])) {
   $translation->update($_POST);
   Html::back();
} else if (isset($_POST["purge"])) {
   $translation->delete($_POST, true);
   Html::redirect(KnowbaseItem::getFormURLWithID($_POST['knowbaseitems_id']));
} else if (isset($_GET["id"]) and isset($_GET['to_rev'])) {
   $translation->check($_GET["id"], UPDATE);
   if ($translation->revertTo($_GET['to_rev'])) {
      Session::addMessageAfterRedirect(
         sprintf(
            __('Knowledge base item translation has been reverted to revision %s'),
            $_GET['to_rev']
         )
      );
   } else {
      Session::addMessageAfterRedirect(
         sprintf(
            __('Knowledge base item translation has not been reverted to revision %s'),
            $_GET['to_rev']
         ),
         false,
         ERROR
      );
   }
   Html::redirect($translation->getFormURLWithID($_GET['id']));
} else if (isset($_GET["id"])) {
   // modifier un item dans la base de connaissance
   $translation->check($_GET["id"], READ);

   if (Session::getLoginUserID()) {
      if (Session::getCurrentInterface() == "central") {
         Html::header(KnowbaseItem::getTypeName(1), $_SERVER['PHP_SELF'], "tools", "knowbaseitemtranslation");
      } else {
         Html::helpHeader(__('FAQ'), $_SERVER['PHP_SELF']);
      }
      Html::helpHeader(__('FAQ'), $_SERVER['PHP_SELF'], $_SESSION["glpiname"]);
   } else {
      $_SESSION["glpilanguage"] = $CFG_GLPI['language'];
      // Anonymous FAQ
      Html::simpleHeader(__('FAQ'),
                         [__('Authentication')
                                         => $CFG_GLPI['root_doc'].'/',
                               __('FAQ') => $CFG_GLPI['root_doc'].'/front/helpdesk.faq.php']);
   }

   $translation->display(['id' => $_GET['id']]);

   if (Session::getLoginUserID()) {
      if (Session::getCurrentInterface() == "central") {
         Html::footer();
      } else {
         Html::helpFooter();
      }
   } else {
      Html::helpFooter();
   }
}
