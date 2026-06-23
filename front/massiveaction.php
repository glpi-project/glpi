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

/**
 * Used for massive action processing
 */

use Glpi\Exception\RedirectException;
use Glpi\Security\ReAuth\ReAuthManager;

require_once(__DIR__ . '/_check_webserver_config.php');

global $CFG_GLPI;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

try {
    $ma = new MassiveAction($_POST, $_GET, 'process');
    $item_types = get_item_types_from_post();

    $reauth_manager = new ReAuthManager();
    if ($reauth_manager->atLeastOneitemTypesRequiresReauthentication($item_types)) {
        // First pass (reauth needed): throws RedirectException. Before throwing,
        // redirectToReauth() stores the calling page in glpi_reauth_cancel_url.
        $reauth_manager->checkReAuthenticationOrRedirect();

        // Reached here means reauth is valid. Two sub-cases:
        // a) Second pass (back from reauth form): the ReAuth controller injects
        //    _glpi_http_referer into POST, so Html::getBackUrl() would return
        //    massiveaction.php (which is unreleavant)  — we use getCancelURL() = calling page stored on first pass.
        // b) First pass within active reauth window (15 min): no _glpi_http_referer in POST;
        //    Html::getBackUrl() reads the real HTTP Referer = calling page.
        $back_url = isset($_POST['_glpi_http_referer'])
            ? $reauth_manager->getCancelURL()
            : Html::getBackUrl();
        $ma->setRedirect($back_url);
    }
}
// process redirect exceptions
catch (RedirectException $e) {
    throw $e;
} catch (Throwable $e) {
    Html::popHeader(__('Bulk modification error'));

    echo "<div class='center'><img src='" . $CFG_GLPI["root_doc"] . "/pics/warning.png' alt='"
      . __s('Warning') . "'><br><br>";
    echo "<span class='b'>" . htmlescape($e->getMessage()) . "</span><br>";
    Html::displayBackLink();
    echo "</div>";

    Html::popFooter();
    return;
}
Html::includeHeader(__('Bulk modification'));
echo '<body><div id="page">';
$ma->displayProgressBar();
echo '</div></body></html>';
flush(); // force displaying the output

$results   = $ma->process();

$nbok       = $results['ok'];
$nbnoaction = $results['noaction'];
$nbko       = $results['ko'];
$nbnoright  = $results['noright'];

$msg_type = INFO;
if ($nbnoaction > 0 && $nbok === 0 && $nbko === 0 && $nbnoright === 0) {
    $message = __s('Operation was done but no action was required');
} elseif ($nbok == 0) {
    $message = __s('Failed operation');
    $msg_type = ERROR;
} elseif ($nbnoright || $nbko) {
    $message = __s('Operation performed partially successful');
    $msg_type = WARNING;
} else {
    $message = __s('Operation successful');
    if ($nbnoaction > 0) {
        $message .= "<br>" . htmlescape(sprintf(__('(%1$d items required no action)'), $nbnoaction));
    }
}
if ($nbnoright || $nbko) {
    //TRANS: %$1d and %$2d are numbers
    $message .= "<br>" . htmlescape(sprintf(
        __('(%1$d authorizations problems, %2$d failures)'),
        $nbnoright,
        $nbko
    ));
}
Session::addMessageAfterRedirect($message, false, $msg_type);
if (isset($results['messages']) && is_array($results['messages']) && count($results['messages'])) {
    foreach ($results['messages'] as $message) {
        Session::addMessageAfterRedirect($message, false, ERROR);
    }
}

Html::redirect($results['redirect']);

/**
 * Returns the item types from POST data, for re-authentication checks.
 *
 * @return array<class-string<CommonGLPI>>
 */
function get_item_types_from_post(): array
{
    /** @var array<class-string<CommonGLPI>> $items */
    $items = isset($_POST['items']) ? array_keys($_POST['items']) : [];
    // ensure item types are glpi class names - phpstan complains "will always evaluate to true"
    // but this filtering is required for security -> forbiddynamicinstantiationrule
    $items = array_filter($items, fn($item) => is_a($item, CommonGLPI::class, true));

    return array_values($items);
}
