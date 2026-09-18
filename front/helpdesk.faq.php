<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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

require_once(__DIR__ . '/_check_webserver_config.php');

use Glpi\Application\View\TemplateRenderer;
use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\NotFoundHttpException;

global $CFG_GLPI;

// Redirect management
if (isset($_GET["redirect"])) {
    Toolbox::manageRedirect($_GET["redirect"]);
}

// Checked before any output so the error page can be rendered (same codes as the central knowledge base).
$kb = new KnowbaseItem();
if (isset($_GET["id"])) {
    $id = (int) $_GET["id"];
    if (!$kb->getFromDB($id)) {
        throw new NotFoundHttpException();
    }
    if (!$kb->can($id, READ)) {
        throw new AccessDeniedHttpException();
    }
}

// The FAQ opens on the root article, as `front/knowbaseitem.php` does. Without
// one, control falls to the legacy view that roadmap#492 replaces with a 404.
if (!isset($_GET["id"]) && KnowbaseItem::hasRoot()) {
    $root_id = KnowbaseItem::getRootId();
    $root    = new KnowbaseItem();
    if ($root->getFromDB($root_id) && $root->can($root_id, READ)) {
        Html::redirect(KnowbaseItem::getFormURLWithID($root_id));
    }
}

if (Session::getLoginUserID()) {
    Html::helpHeader(__('FAQ'), 'faq');
} else {
    $_SESSION["glpilanguage"] = $_SESSION['glpilanguage'] ?? Session::getPreferredLanguage();
    // Anonymous FAQ
    Html::simpleHeader(__('FAQ'), [
        __('Authentication') => '/',
        __('FAQ')            => '/front/helpdesk.faq.php',
    ]);
}

if (isset($_GET["id"])) {
    // Same two-column layout as the central knowledge base (see CommonGLPI::display()).
    echo TemplateRenderer::getInstance()->render('pages/tools/kb/faq_article.html.twig', [
        'aside'   => $kb->getAsideContent(),
        'slug'    => Toolbox::slugify(KnowbaseItem::class),
        'article' => $kb->showFull(['display' => false]),
    ]);
} else {
    // Manage forcetab : non standard system (file name <> class name)
    if (isset($_GET['forcetab'])) {
        Session::setActiveTab('Knowbase', $_GET['forcetab']);
        unset($_GET['forcetab']);
    }

    $kb = new Knowbase();
    $kb->display($_GET);
}

Html::helpFooter();
