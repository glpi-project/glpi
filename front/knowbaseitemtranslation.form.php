<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2023 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

/**
 * @since 0.85
 */

include('../inc/includes.php');

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
            Html::helpHeader(__('FAQ'));
        }
        Html::helpHeader(__('FAQ'));
    } else {
        $_SESSION["glpilanguage"] = $CFG_GLPI['language'];
       // Anonymous FAQ
        Html::simpleHeader(__('FAQ'), [
            __('Authentication') => '/',
            __('FAQ')            => '/front/helpdesk.faq.php'
        ]);
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
