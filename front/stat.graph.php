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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Exception\Http\BadRequestHttpException;
use Glpi\Stat\Data\Graph\StatDataSatisfaction;
use Glpi\Stat\Data\Graph\StatDataSatisfactionSurvey;
use Glpi\Stat\Data\Graph\StatDataTicketAverageTime;
use Glpi\Stat\Data\Graph\StatDataTicketNumber;

use function Safe\preg_match;
use function Safe\preg_replace;

global $DB;

Html::header(__('Statistics'), '', "helpdesk", "stat");

Session::checkRight("statistic", READ);

/** @var CommonITILObject $item */
if (!$item = getItemForItemtype($_GET['itemtype'])) {
    throw new BadRequestHttpException();
}

//sanitize dates
foreach (['date1', 'date2'] as $key) {
    if (array_key_exists($key, $_GET) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_GET[$key]) !== 1) {
        unset($_GET[$key]);
    }
}

if (
    !empty($_GET["date1"])
    && !empty($_GET["date2"])
    && (strcmp($_GET["date2"], $_GET["date1"]) < 0)
) {
    $tmp            = $_GET["date1"];
    $_GET["date1"] = $_GET["date2"];
    $_GET["date2"] = $tmp;
}

$target_params = preg_replace("/&date[12]=[0-9-]*/", "", $_SERVER['QUERY_STRING']);
$target_params = preg_replace("/&*id=(\d+&?)/", "", $target_params);

$next    = 0;
$prev    = 0;
$title   = "";
$parent  = 0;

$val1   = null;
$val2   = null;
$values = [];

/** @var string $type */
$type = $_GET['type'] ?? '';
switch ($type) {
    case "technician_followup":
    case "technician":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $type);
        $title   = sprintf(
            __s('%1$s: %2$s'),
            __s('Technician'),
            getUserLink($_GET["id"])
        );
        break;

    case "suppliers_id_assign":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $type);
        $supplier = Supplier::getById($_GET["id"]);
        $title   = sprintf(
            __s('%1$s: %2$s'),
            htmlescape(Supplier::getTypeName(1)),
            $supplier !== false ? $supplier->getLink(['comments' => true]) : ''
        );
        break;

    case "users_id_recipient":
    case "user":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $type);
        $title   = sprintf(
            __s('%1$s: %2$s'),
            htmlescape(User::getTypeName(1)),
            getUserLink($_GET["id"])
        );
        break;

    case "itilcategories_tree":
        $parent = ($_GET['champ'] ?? 0);
        // nobreak;

        // no break
    case "itilcategories_id":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems(
            $_GET["itemtype"],
            $_GET["date1"],
            $_GET["date2"],
            $type,
            $parent
        );
        $title   = sprintf(
            __('%1$s: %2$s'),
            _n('Category', 'Categories', 1),
            Dropdown::getDropdownName("glpi_itilcategories", $_GET["id"])
        );
        $title   = htmlescape($title);
        break;

    case 'locations_tree':
        $parent = ($_GET['champ'] ?? 0);
        // no break

    case 'locations_id':
        $val1    = $_GET['id'];
        $val2    = '';
        $values  = Stat::getItems(
            $_GET['itemtype'],
            $_GET['date1'],
            $_GET['date2'],
            $type,
            $parent
        );
        $title   = sprintf(
            __('%1$s: %2$s'),
            Location::getTypeName(1),
            Dropdown::getDropdownName('glpi_locations', $_GET['id'])
        );
        $title   = htmlescape($title);
        break;

    case "type":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $type);
        $title   = sprintf(__('%1$s: %2$s'), _n('Type', 'Types', 1), Ticket::getTicketTypeName($_GET["id"]));
        $title   = htmlescape($title);
        break;

    case 'group_tree':
    case 'groups_tree_assign':
        $parent = ($_GET['champ'] ?? 0);
        // no break

    case "group":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems(
            $_GET["itemtype"],
            $_GET["date1"],
            $_GET["date2"],
            $type,
            $parent
        );
        $title   = sprintf(
            __('%1$s: %2$s'),
            Group::getTypeName(1),
            Dropdown::getDropdownName("glpi_groups", $_GET["id"])
        );
        $title   = htmlescape($title);
        break;

    case "groups_id_assign":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $type);
        $title   = sprintf(
            __('%1$s: %2$s'),
            Group::getTypeName(1),
            Dropdown::getDropdownName("glpi_groups", $_GET["id"])
        );
        $title   = htmlescape($title);
        break;

    case "priority":
    case "urgency":
    case "impact":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $type);
        $title = match ($type) {
            'priority' => sprintf(__('%1$s: %2$s'), __('Priority'), $item::getPriorityName($_GET["id"])),
            'urgency'  => sprintf(__('%1$s: %2$s'), __('Urgency'), $item::getUrgencyName($_GET["id"])),
            'impact'   => sprintf(__('%1$s: %2$s'), __('Impact'), $item->getImpactName($_GET["id"])),
        };
        $title   = htmlescape($title);
        break;

    case "usertitles_id":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $type);
        $title   = sprintf(
            __('%1$s: %2$s'),
            _x('person', 'Title'),
            Dropdown::getDropdownName("glpi_usertitles", $_GET["id"])
        );
        $title   = htmlescape($title);
        break;

    case "solutiontypes_id":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $type);
        $title   = sprintf(
            __('%1$s: %2$s'),
            SolutionType::getTypeName(1),
            Dropdown::getDropdownName("glpi_solutiontypes", $_GET["id"])
        );
        $title   = htmlescape($title);
        break;

    case "usercategories_id":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $type);
        $title   = sprintf(
            __('%1$s: %2$s'),
            _n('Category', 'Categories', 1),
            Dropdown::getDropdownName("glpi_usercategories", $_GET["id"])
        );
        $title   = htmlescape($title);
        break;

    case "requesttypes_id":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $type);
        $title   = sprintf(
            __('%1$s: %2$s'),
            RequestType::getTypeName(1),
            Dropdown::getDropdownName("glpi_requesttypes", $_GET["id"])
        );
        $title   = htmlescape($title);
        break;

    case "device":
        $val1 = $_GET["id"];
        $val2 = $_GET["champ"];
        if ($item = getItemForItemtype($_GET["champ"])) {
            $device_table = $item->getTable();
            $values       = Stat::getItems(
                $_GET["itemtype"],
                $_GET["date1"],
                $_GET["date2"],
                $_GET["champ"]
            );

            $iterator = $DB->request([
                'SELECT' => ['designation'],
                'FROM'   => $device_table,
                'WHERE'  => [
                    'id' => $_GET['id'],
                ],
            ]);
            $current = $iterator->current();

            $title  = sprintf(
                __('%1$s: %2$s'),
                $item::getTypeName(),
                $current['designation']
            );
            $title   = htmlescape($title);
        }
        break;

    case "comp_champ":
        $val1  = $_GET["id"];
        $val2  = $_GET["champ"];
        if ($item = getItemForItemtype($_GET["champ"])) {
            $table  = $item::getTable();
            $values = Stat::getItems(
                $_GET["itemtype"],
                $_GET["date1"],
                $_GET["date2"],
                $_GET["champ"]
            );
            $title  = sprintf(
                __('%1$s: %2$s'),
                $item::getTypeName(),
                Dropdown::getDropdownName($table, $_GET["id"])
            );
            $title   = htmlescape($title);
        }
        break;
}

// Found next and prev items
$foundkey = -1;
foreach ($values as $key => $val) {
    if ((int) $val['id'] === (int) $_GET["id"]) {
        $foundkey = $key;
    }
}

if ($foundkey >= 0) {
    if (isset($values[$foundkey + 1])) {
        $next = $values[$foundkey + 1]['id'];
    }
    if (isset($values[$foundkey - 1])) {
        $prev = $values[$foundkey - 1]['id'];
    }
}

$stat = new Stat();

TemplateRenderer::getInstance()->display('pages/assistance/stats/single_item_pager.html.twig', [
    'target'        => 'stat.graph.php',
    'target_params' => $target_params,
    'prev'          => $prev,
    'next'          => $next,
    'title'         => $title,
]);

TemplateRenderer::getInstance()->display('pages/assistance/stats/form.html.twig', [
    'target'    => 'stat.graph.php',
    'itemtype'  => $_GET['itemtype'],
    'id'        => $_GET["id"],
    'type'      => $type,
    'date1'     => $_GET["date1"],
    'date2'     => $_GET["date2"],
    'champ'     => $_GET["champ"] ?? 0,
]);

$stat_params = [
    'itemtype' => $_GET['itemtype'],
    'date1'    => $_GET['date1'],
    'date2'    => $_GET['date2'],
    'type'     => $type,
    'val1'     => $val1,
    'val2'     => $val2,
];

$stat->displayLineGraphFromData(new StatDataTicketNumber($stat_params));
$stat->displayLineGraphFromData(new StatDataTicketAverageTime($stat_params));

if ($_GET['itemtype'] == 'Ticket') {
    $stat->displayLineGraphFromData(new StatDataSatisfactionSurvey($stat_params));
    $stat->displayLineGraphFromData(new StatDataSatisfaction($stat_params));
}

Html::footer();
