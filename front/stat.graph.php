<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2024 Teclib' and contributors.
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

use Glpi\Application\View\TemplateRenderer;
use Glpi\Stat\Data\Graph\StatDataSatisfaction;
use Glpi\Stat\Data\Graph\StatDataSatisfactionSurvey;
use Glpi\Stat\Data\Graph\StatDataTicketAverageTime;
use Glpi\Stat\Data\Graph\StatDataTicketNumber;

/**
 * @var \DBmysql $DB
 */
global $DB;

Html::header(__('Statistics'), $_SERVER['PHP_SELF'], "helpdesk", "stat");

Session::checkRight("statistic", READ);

/** @var CommonITILObject $item */
if (!$item = getItemForItemtype($_GET['itemtype'])) {
    exit;
}

//sanitize dates
foreach (['date1', 'date2'] as $key) {
    if (array_key_exists($key, $_GET) && preg_match('/\d{4}-\d{2}-\d{2}/', (string)$_GET[$key]) !== 1) {
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

$cleantarget = preg_replace("/&date[12]=[0-9-]*/", "", $_SERVER['QUERY_STRING']);
$cleantarget = preg_replace("/&*id=(\d+&?)/", "", $cleantarget);

$next    = 0;
$prev    = 0;
$title   = "";
$parent  = 0;

$val1   = null;
$val2   = null;
$values = [];

switch ($_GET["type"]) {
    case "technician_followup":
    case "technician":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
        $link    = User::canView() ? 1 : 0;
        $name    = $item->getAssignName($_GET["id"], 'User', $link);
        $title   = sprintf(
            __s('%1$s: %2$s'),
            __s('Technician'),
            $link ? $name : htmlspecialchars($name)
        );
        break;

    case "suppliers_id_assign":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
        $link    = Supplier::canView() ? 1 : 0;
        $name    = $item->getAssignName($_GET["id"], 'Supplier', $link);
        $title   = sprintf(
            __s('%1$s: %2$s'),
            Supplier::getTypeName(1),
            $link ? $name : htmlspecialchars($name)
        );
        break;

    case "users_id_recipient":
    case "user":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
        $link    = User::canView() ? 1 : 0;
        $name    = getUserName($_GET["id"], $link);
        $title   = sprintf(
            __s('%1$s: %2$s'),
            User::getTypeName(1),
            $link ? $name : htmlspecialchars($name)
        );
        break;

    case "itilcategories_tree":
        $parent = (isset($_GET['champ']) ? $_GET['champ'] : 0);
       // nobreak;

    case "itilcategories_id":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems(
            $_GET["itemtype"],
            $_GET["date1"],
            $_GET["date2"],
            $_GET["type"],
            $parent
        );
        $title   = sprintf(
            __('%1$s: %2$s'),
            _n('Category', 'Categories', 1),
            Dropdown::getDropdownName("glpi_itilcategories", $_GET["id"])
        );
        $title   = htmlspecialchars($title);
        break;

    case 'locations_tree':
        $parent = (isset($_GET['champ']) ? $_GET['champ'] : 0);
       // no break

    case 'locations_id':
        $val1    = $_GET['id'];
        $val2    = '';
        $values  = Stat::getItems(
            $_GET['itemtype'],
            $_GET['date1'],
            $_GET['date2'],
            $_GET['type'],
            $parent
        );
        $title   = sprintf(
            __('%1$s: %2$s'),
            Location::getTypeName(1),
            Dropdown::getDropdownName('glpi_locations', $_GET['id'])
        );
        $title   = htmlspecialchars($title);
        break;

    case "type":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
        $title   = sprintf(__('%1$s: %2$s'), _n('Type', 'Types', 1), Ticket::getTicketTypeName($_GET["id"]));
        $title   = htmlspecialchars($title);
        break;

    case 'group_tree':
    case 'groups_tree_assign':
        $parent = (isset($_GET['champ']) ? $_GET['champ'] : 0);
       // no break

    case "group":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems(
            $_GET["itemtype"],
            $_GET["date1"],
            $_GET["date2"],
            $_GET["type"],
            $parent
        );
        $title   = sprintf(
            __('%1$s: %2$s'),
            Group::getTypeName(1),
            Dropdown::getDropdownName("glpi_groups", $_GET["id"])
        );
        $title   = htmlspecialchars($title);
        break;

    case "groups_id_assign":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
        $title   = sprintf(
            __('%1$s: %2$s'),
            Group::getTypeName(1),
            Dropdown::getDropdownName("glpi_groups", $_GET["id"])
        );
        $title   = htmlspecialchars($title);
        break;

    case "priority":
    case "urgency":
    case "impact":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
        $title = match ($_GET['type']) {
            'priority' => sprintf(__('%1$s: %2$s'), __('Priority'), $item::getPriorityName($_GET["id"])),
            'urgency'  => sprintf(__('%1$s: %2$s'), __('Urgency'), $item::getUrgencyName($_GET["id"])),
            'impact'   => sprintf(__('%1$s: %2$s'), __('Impact'), $item->getImpactName($_GET["id"])),
        };
        $title   = htmlspecialchars($title);
        break;

    case "usertitles_id":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
        $title   = sprintf(
            __('%1$s: %2$s'),
            _x('person', 'Title'),
            Dropdown::getDropdownName("glpi_usertitles", $_GET["id"])
        );
        $title   = htmlspecialchars($title);
        break;

    case "solutiontypes_id":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
        $title   = sprintf(
            __('%1$s: %2$s'),
            SolutionType::getTypeName(1),
            Dropdown::getDropdownName("glpi_solutiontypes", $_GET["id"])
        );
        $title   = htmlspecialchars($title);
        break;

    case "usercategories_id":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
        $title   = sprintf(
            __('%1$s: %2$s'),
            _n('Category', 'Categories', 1),
            Dropdown::getDropdownName("glpi_usercategories", $_GET["id"])
        );
        $title   = htmlspecialchars($title);
        break;

    case "requesttypes_id":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_GET["date1"], $_GET["date2"], $_GET["type"]);
        $title   = sprintf(
            __('%1$s: %2$s'),
            RequestType::getTypeName(1),
            Dropdown::getDropdownName("glpi_requesttypes", $_GET["id"])
        );
        $title   = htmlspecialchars($title);
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
                    'id' => $_GET['id']
                ]
            ]);
            $current = $iterator->current();

            $title  = sprintf(
                __('%1$s: %2$s'),
                $item::getTypeName(),
                $current['designation']
            );
            $title   = htmlspecialchars($title);
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
            $title   = htmlspecialchars($title);
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
    'php_self' => $_SERVER['PHP_SELF'],
    'cleantarget' => $cleantarget,
    'prev' => $prev,
    'next' => $next,
    'title' => $title,
]);

TemplateRenderer::getInstance()->display('pages/assistance/stats/form.html.twig', [
    'target'    => 'stat.graph.php',
    'itemtype'  => $_GET['itemtype'],
    'id'        => $_GET["id"],
    'type'      => $_GET['type'],
    'date1'     => $_GET["date1"],
    'date2'     => $_GET["date2"],
    'champ'     => $_GET["champ"] ?? 0,
]);

$stat_params = [
    'itemtype' => $_GET['itemtype'],
    'date1'    => $_GET['date1'],
    'date2'    => $_GET['date2'],
    'type'     => $_GET['type'],
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
