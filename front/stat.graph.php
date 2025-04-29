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

use Glpi\Stat\Data\Graph\StatDataSatisfaction;
use Glpi\Stat\Data\Graph\StatDataSatisfactionSurvey;
use Glpi\Stat\Data\Graph\StatDataTicketAverageTime;
use Glpi\Stat\Data\Graph\StatDataTicketNumber;

/**
 * @var array $CFG_GLPI
 * @var \DBmysql $DB
 */
global $CFG_GLPI, $DB;

include('../inc/includes.php');

Html::header(__('Statistics'), $_SERVER['PHP_SELF'], "helpdesk", "stat");

Session::checkRight("statistic", READ);

/** @var CommonITILObject $item */
if (!$item = getItemForItemtype($_GET['itemtype'])) {
    exit;
}

//sanitize dates
foreach (['date1', 'date2'] as $key) {
    if (array_key_exists($key, $_GET) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_GET[$key]) !== 1) {
        unset($_GET[$key]);
    }
    if (array_key_exists($key, $_POST) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $_POST[$key]) !== 1) {
        unset($_POST[$key]);
    }
}
if (empty($_POST["date1"]) && empty($_POST["date2"])) {
    if (isset($_GET["date1"])) {
        $_POST["date1"] = $_GET["date1"];
    }
    if (isset($_GET["date2"])) {
        $_POST["date2"] = $_GET["date2"];
    }
}

if (
    !empty($_POST["date1"])
    && !empty($_POST["date2"])
    && (strcmp($_POST["date2"], $_POST["date1"]) < 0)
) {
    $tmp            = $_POST["date1"];
    $_POST["date1"] = $_POST["date2"];
    $_POST["date2"] = $tmp;
}

$cleantarget = preg_replace("/[&]date[12]=[0-9-]*/", "", $_SERVER['QUERY_STRING']);
$cleantarget = preg_replace("/[&]*id=([0-9]+[&]{0,1})/", "", $cleantarget);
$cleantarget = preg_replace("/&/", "&amp;", $cleantarget);

$next    = 0;
$prev    = 0;
$title   = "";
$parent  = 0;

$showuserlink = 0;
if (Session::haveRight('user', READ)) {
    $showuserlink = 1;
}

$val1   = null;
$val2   = null;
$values = [];

switch ($_GET["type"]) {
    case "technicien":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
        $title   = sprintf(
            __('%1$s: %2$s'),
            __('Technician'),
            $item->getAssignName($_GET["id"], 'User', $showuserlink)
        );
        break;

    case "technicien_followup":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
        $title   = sprintf(
            __('%1$s: %2$s'),
            __('Technician'),
            $item->getAssignName($_GET["id"], 'User', $showuserlink)
        );
        break;

    case "suppliers_id_assign":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
        $title   = sprintf(
            __('%1$s: %2$s'),
            Supplier::getTypeName(1),
            $item->getAssignName($_GET["id"], 'Supplier', $showuserlink)
        );
        break;

    case "user":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
        $title   = sprintf(__('%1$s: %2$s'), User::getTypeName(1), getUserName($_GET["id"], $showuserlink));
        break;

    case "users_id_recipient":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
        $title   = sprintf(__('%1$s: %2$s'), User::getTypeName(1), getUserName($_GET["id"], $showuserlink));
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
            $_POST["date1"],
            $_POST["date2"],
            $_GET["type"],
            $parent
        );
        $title   = sprintf(
            __('%1$s: %2$s'),
            _n('Category', 'Categories', 1),
            Dropdown::getDropdownName("glpi_itilcategories", $_GET["id"])
        );
        break;

    case 'locations_tree':
        $parent = ($_GET['champ'] ?? 0);
        // nobreak;

        // no break
    case 'locations_id':
        $val1    = $_GET['id'];
        $val2    = '';
        $values  = Stat::getItems(
            $_GET['itemtype'],
            $_POST['date1'],
            $_POST['date2'],
            $_GET['type'],
            $parent
        );
        $title   = sprintf(
            __('%1$s: %2$s'),
            Location::getTypeName(1),
            Dropdown::getDropdownName('glpi_locations', $_GET['id'])
        );
        break;

    case "type":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
        $title   = sprintf(__('%1$s: %2$s'), _n('Type', 'Types', 1), Ticket::getTicketTypeName($_GET["id"]));
        break;

    case 'group_tree':
    case 'groups_tree_assign':
        $parent = ($_GET['champ'] ?? 0);
        // nobreak;

        // no break
    case "group":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems(
            $_GET["itemtype"],
            $_POST["date1"],
            $_POST["date2"],
            $_GET["type"],
            $parent
        );
        $title   = sprintf(
            __('%1$s: %2$s'),
            Group::getTypeName(1),
            Dropdown::getDropdownName("glpi_groups", $_GET["id"])
        );
        break;

    case "groups_id_assign":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
        $title   = sprintf(
            __('%1$s: %2$s'),
            Group::getTypeName(1),
            Dropdown::getDropdownName("glpi_groups", $_GET["id"])
        );
        break;

    case "priority":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
        $title   = sprintf(__('%1$s: %2$s'), __('Priority'), $item->getPriorityName($_GET["id"]));
        break;

    case "urgency":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
        $title   = sprintf(__('%1$s: %2$s'), __('Urgency'), $item->getUrgencyName($_GET["id"]));
        break;

    case "impact":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
        $title   = sprintf(__('%1$s: %2$s'), __('Impact'), $item->getImpactName($_GET["id"]));
        break;

    case "usertitles_id":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
        $title   = sprintf(
            __('%1$s: %2$s'),
            _x('person', 'Title'),
            Dropdown::getDropdownName("glpi_usertitles", $_GET["id"])
        );
        break;

    case "solutiontypes_id":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
        $title   = sprintf(
            __('%1$s: %2$s'),
            SolutionType::getTypeName(1),
            Dropdown::getDropdownName("glpi_solutiontypes", $_GET["id"])
        );
        break;

    case "usercategories_id":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
        $title   = sprintf(
            __('%1$s: %2$s'),
            _n('Category', 'Categories', 1),
            Dropdown::getDropdownName("glpi_usercategories", $_GET["id"])
        );
        break;

    case "requesttypes_id":
        $val1    = $_GET["id"];
        $val2    = "";
        $values  = Stat::getItems($_GET["itemtype"], $_POST["date1"], $_POST["date2"], $_GET["type"]);
        $title   = sprintf(
            __('%1$s: %2$s'),
            RequestType::getTypeName(1),
            Dropdown::getDropdownName("glpi_requesttypes", $_GET["id"])
        );
        break;

    case "device":
        $val1 = $_GET["id"];
        $val2 = $_GET["champ"];
        if ($item = getItemForItemtype($_GET["champ"])) {
            $device_table = $item->getTable();
            $values       = Stat::getItems(
                $_GET["itemtype"],
                $_POST["date1"],
                $_POST["date2"],
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
                $item->getTypeName(),
                $current['designation']
            );
        }
        break;

    case "comp_champ":
        $val1  = $_GET["id"];
        $val2  = $_GET["champ"];
        if ($item = getItemForItemtype($_GET["champ"])) {
            $table  = $item->getTable();
            $values = Stat::getItems(
                $_GET["itemtype"],
                $_POST["date1"],
                $_POST["date2"],
                $_GET["champ"]
            );
            $title  = sprintf(
                __('%1$s: %2$s'),
                $item->getTypeName(),
                Dropdown::getDropdownName($table, $_GET["id"])
            );
        }
        break;
}

// Found next and prev items
$foundkey = -1;
foreach ($values as $key => $val) {
    if ($val['id'] == $_GET["id"]) {
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

echo "<div class='center'>";
echo "<table class='tab_cadre'>";
echo "<tr><td>";
if ($prev > 0) {
    echo "<a href=\"" . $_SERVER['PHP_SELF'] . "?$cleantarget&amp;date1=" . $_POST["date1"] . "&amp;date2=" .
          $_POST["date2"] . "&amp;id=$prev\">
          <img src='" . $CFG_GLPI["root_doc"] . "/pics/left.png' alt=\"" . __s('Previous') . "\"
           title=\"" . __s('Previous') . "\"></a>";
}
echo "</td>";

echo "<td width='400' class='center b'>$title</td>";
echo "<td>";
if ($next > 0) {
    echo "<a href=\"" . $_SERVER['PHP_SELF'] . "?$cleantarget&amp;date1=" . $_POST["date1"] . "&amp;date2=" .
          $_POST["date2"] . "&amp;id=$next\">
          <img src='" . $CFG_GLPI["root_doc"] . "/pics/right.png' alt=\"" . __s('Next') . "\"
           title=\"" . __s('Next') . "\"></a>";
}
echo "</td>";
echo "</tr>";
echo "</table></div><br>";

$target = preg_replace("/&/", "&amp;", $_SERVER["REQUEST_URI"]);

echo "<form method='post' name='form' action='$target'><div class='center'>";
echo "<table class='tab_cadre'>";
echo "<tr class='tab_bg_2'><td class='right'>" . __('Start date') . "</td><td>";
Html::showDateField("date1", ['value' => $_POST["date1"]]);
echo "</td><td rowspan='2' class='center'>";
echo "<input type='hidden' name='itemtype' value=\"" . htmlspecialchars($_GET['itemtype']) . "\">";
echo "<input type='submit' class='btn btn-primary' value=\"" . __s('Display report') . "\"></td></tr>";

echo "<tr class='tab_bg_2'><td class='right'>" . __('End date') . "</td><td>";
Html::showDateField("date2", ['value' => $_POST["date2"]]);
echo "</td></tr>";
echo "</table></div>";

Html::closeForm();

$stat_params = [
    'itemtype' => $_GET['itemtype'],
    'date1'    => $_POST['date1'],
    'date2'    => $_POST['date2'],
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
