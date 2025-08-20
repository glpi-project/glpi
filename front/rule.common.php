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

/**
 * Following variables have to be defined before inclusion of this file:
 * @var RuleCollection $rulecollection
 */

if (!isset($_GET["id"])) {
    $_GET["id"] = "";
}

$rulecollection->checkGlobal(READ);

if (isset($_POST["action"])) {
    $rulecollection->checkGlobal(UPDATE);
    $rulecollection->changeRuleOrder($_POST["id"], $_POST["action"], $_POST['condition']);
    Html::back();
} elseif (isset($_POST["reinit"]) || isset($_GET['reinit'])) {
    //reinitialize current rules
    $ruleclass = $rulecollection->getRuleClass();
    if ($ruleclass->initRules()) {
        Session::addMessageAfterRedirect(
            htmlescape(sprintf(
                //TRANS: first parameter is the rule type name
                __('%1$s has been reset.'),
                $rulecollection->getTitle()
            ))
        );
    } else {
        Session::addMessageAfterRedirect(
            htmlescape(sprintf(
                //TRANS: first parameter is the rule type name
                __('%1$s reset failed.'),
                $rulecollection->getTitle()
            )),
            false,
            ERROR
        );
    }
    Html::back();
} elseif (isset($_POST["replay_rule"]) || isset($_GET["replay_rule"])) {
    $rulecollection->checkGlobal(UPDATE);

    // Current time
    $start = (int) round(microtime(true));

    // Reload every X seconds to refresh the progress bar
    $max = $start + 5;

    Html::header(
        Rule::getTypeName(Session::getPluralNumber()),
        '',
        "admin",
        $rulecollection->menu_type,
        $rulecollection->menu_option
    );

    if (
        !(isset($_POST['replay_confirm']) || isset($_GET['offset']))
        && $rulecollection->warningBeforeReplayRulesOnExistingDB()
    ) {
        Html::footer();
        return;
    }

    $rule_class = $rulecollection->getRuleClassName();

    if (array_key_exists('offset', $_GET)) {
        // $_GET['offset'] will exists only when page is reloaded to update the progress bar
        $manufacturer   = (int) ($_GET['manufacturer']);
        $current_offset = (int) $_GET['offset'];
        $total_items    = (int) $_GET['total'];

        $start = $_GET['start']; // global start for stat
    } else {
        $manufacturer   = (int) ($_POST['manufacturer'] ?? 0);
        $current_offset = 0;
        $total_items    = $rulecollection->countTotalItemsForRulesReplay(['manufacturer' => $manufacturer]);
    }

    if ($total_items === 0) {
        // Nothing to do
        Session::addMessageAfterRedirect(__s('No items found.'));
        Html::redirect($rule_class::getSearchURL());
    }

    echo "<div class='position-relative fw-bold'>" . htmlescape($rulecollection->getTitle()) . "<br>"
         . __s('Replay the rules dictionary') . "</div>";
    echo "<div class='text-center mb-3'>";
    echo Html::getProgressBar(
        $current_offset / $total_items * 100,
        __('Work in progress...')
    );
    echo '</div>';
    Html::footer();
    flush(); // force displaying the output

    $offset = $rulecollection->replayRulesOnExistingDB($current_offset, $max, [], ['manufacturer' => $manufacturer]);

    if ($offset < 0) {
        // Work ended
        $duration = round(microtime(true) - $start);
        Session::addMessageAfterRedirect(sprintf(
            __s('Task completed in %s'),
            htmlescape(Html::timestampToString($duration))
        ));
        Html::redirect($rule_class::getSearchURL());
    } else {
        // Need more work
        Html::redirect($rule_class::getSearchURL() . "?start=$start&replay_rule=1&offset=$offset&total=$total_items&manufacturer="
                     . "$manufacturer");
    }
}

Html::header(
    Rule::getTypeName(Session::getPluralNumber()),
    '',
    'admin',
    $rulecollection->menu_type,
    $rulecollection->menu_option
);

$rulecollection->display([
    'display_criterias' => true,
    'display_actions'   => true,
]);
Html::footer();
