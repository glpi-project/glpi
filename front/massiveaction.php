<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
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

global $CFG_GLPI;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

try {
    $ma = new MassiveAction($_POST, $_GET, 'process');
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
