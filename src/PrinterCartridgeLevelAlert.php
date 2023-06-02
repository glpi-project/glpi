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
 *  PrinterCartridgeLevelAlert class
 *
 * @ since version 0.84
 **/
class PrinterCartridgeLevelAlert extends CommonGLPI
{
    protected static $notable = true;

    public static function getTypeName($nb = 0)
    {
        return __('Printer Cartridge Level Alerts');
    }

    /**
     * @since 0.85
     **/
    public static function canView()
    {
        return (Session::haveRight('printer', READ) && Session::haveRight('cartridge', READ));
    }

    /**
     * Print a good title
     *
     *@return void
     **/
    public static function title()
    {
        Html::displayTitle(
            "",
            self::getTypeName(),
            "<i class='fas fa-check fa-lg me-2'></i>" . self::getTypeName()
        );
    }

    /**
    * @param $entities
    *        $repeat for already notified management
    *
    * @return string
    */
    private static function query($entities, $repeat = null)
    {
        global $DB;

        $query = "SELECT c.id as cartridge, i.id as cartridgeitem, p.id as printer, p.entities_id as entity, l.value as cartridgelevel, a.id as alertID, a.date as alertDate
              FROM glpi_cartridges AS c
              INNER JOIN glpi_cartridgeitems AS i on c.cartridgeitems_id = i.id
              INNER JOIN glpi_printers_cartridgeinfos AS l on i.type_tag like l.property
              INNER JOIN glpi_printers as p on c.printers_id = p.id
              LEFT JOIN glpi_alerts AS a ON c.id = a.items_id and a.itemtype like 'Cartridge'
              WHERE c.date_out is NULL
                AND (
                        ( l.value REGEXP '^[0-9]+$' AND l.value <= i.warn_level )
                        OR
                        ( l.value like 'WARNING' OR l.value like 'BAD' )
                    )
                AND l.printers_id = p.id
                AND p.entities_id IN ($entities)";

        if ($repeat) {
            $query = $query . " AND ( a.date is NULL or a.date < CURRENT_TIMESTAMP() - INTERVAL " . $repeat . " second)";
        }

        $query = $query . " ORDER BY p.name";
        return $query;
    }

    /**
    * @param $data
    *
    * @return string
    */
    public static function prepareBodyValues($data)
    {
        global $CFG_GLPI;

        $cartridge = new CartridgeItem();
        $cartridge->getFromDB($data["cartridgeitem"]);

        $printer = new Printer();
        $printer->getFromDB($data["printer"]);

        $result['cartridge.printer'] = "<a href=\"" . $CFG_GLPI["url_base"] . "/front/printer.form.php?id=" . $printer->fields["id"] . "\">" . $printer->fields["name"];

        if ($_SESSION["glpiis_ids_visible"] == 1 || empty($printer->fields["name"])) {
            $result['cartridge.printer'] .= " (";
            $result['cartridge.printer'] .= $printer->fields["id"] . ")";
        }
        if (Session::isMultiEntitiesMode()) {
            $result['cartridge.entity'] = Dropdown::getDropdownName("glpi_entities", $printer->fields["entities_id"]);
        }

        $result['cartridge.item'] = "<a href=\"" . $CFG_GLPI["url_base"] . "/front/cartridgeitem.form.php?id=" . $cartridge->fields["id"] . "\">" . $cartridge->fields["name"] . " (" . $cartridge->fields["ref"] . ")</a>";

        $result['cartridge.level'] = $data["cartridgelevel"] . (preg_match('#^[0-9]+$#', $data["cartridgelevel"]) ? "%" : "");
        $result['cartridge.alertDate'] = $data["alertDate"];
        return $result;
    }

    /**
    * @param $data
    *
    * @return string
    */
    private static function displayBody($data)
    {

        $tmp = self::prepareBodyValues($data);
        $body = "<tr class='tab_bg_2'><td>" . $tmp['cartridge.printer'] . "</a></td>";
        $body .= "<td>" . $tmp['cartridge.entity'] . "</td>";
        $body .= "<td>" . $tmp['cartridge.item'] . "</td>";
        $body .= "<td>" . $tmp['cartridge.level'] . "</td>";
        $body .= "<td>" . $tmp['cartridge.alertDate'] . "</td>";
        return $body;
    }


    /**
    *
    */
    public static function displayAlerts()
    {
        global $DB;

        $CronTask = new CronTask();
        if ($CronTask->getFromDBbyName("PrinterCartridgeLevelAlert", "PrinterCartridgeLevelAlert")) {
            if ($CronTask->fields["state"] != CronTask::STATE_DISABLE) {
                if (Session::haveRight("cartridge", READ) && Session::haveRight("printer", READ)) {
                    $query  = self::query($_SESSION["glpiactiveentities_string"]);
                    $result = $DB->query($query);

                    echo "<div class='d-flex flex-column'>";
                    echo "<div class='row'>";
                    echo "<div class='col'>";
                    echo "<div class='d-flex card-tabs flex-column flex-md-row vertical'>";
                    echo "<ul class='nav nav-tabs flex-row flex-md-column d-none d-md-block' id='tabspanel' style='min-width: 200px' role='tablist'>";
                    echo "</ul>";
                    echo "<div class='tab-content p-2 flex-grow-1 card border-start-0' style='min-height: 150px'>";
                    echo "<div class='alltab'>";
                    echo __('Cartridges whose level is low', 'additionalalerts');
                    echo "</div>";

                    if ($DB->numrows($result) > 0) {
                        if (Session::isMultiEntitiesMode()) {
                            $nbcol = 4;
                        } else {
                            $nbcol = 3;
                        };

                        echo "<div class='flex-grow-1 d-flex flex-wrap flex-md-nowrap  align-items-center justify-content-between mb-2 search-pager'>";
                        echo "<div class='table-responsive'>";
                        echo "<table class='table table-hover' cellspacing='2' cellpadding='3'>";
                        echo "<thead>";
                        echo "<tr>";
                        echo "<th>" . _n('Printer', 'Printers', Session::getPluralNumber()) . "</th>";

                        if (Session::isMultiEntitiesMode()) {
                            echo "<th>" . _n('Entity', 'Entities', Session::getPluralNumber()) . "</th>";
                        }

                        echo "<th>" . _n('Cartridge', 'Cartridges', Session::getPluralNumber()) . "</th>";
                        echo "<th>" . __('Level') . "</th>";
                        echo "<th>" . _n('Email notification', 'Email notifications', false) . "</th>";
                        echo "</tr>";
                        echo "</thead>";

                        while ($data = $DB->fetchArray($result)) {
                            echo self::displayBody($data);
                        }
                        echo "</table>";
                        echo "</div>";
                        echo "</div>";
                    } else {
                        echo "<br><div align='center'><b>" . __('No cartridge is below the threshold') . "</b></div>";
                    }
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                    echo "</div>";
                }
            }
        }
    }

    /**
     * Get task description
     *
     * @return string
     */
    public static function getTaskDescription(): string
    {
        return __("Notification sending for low level printer cartridges");
    }

    /**
     *
     * @return string
     */
    public static function cronInfo($name)
    {
        return [
            'description' => self::getTaskDescription()
        ];
    }

    /**
     * Cron action on cartridges : alert if a stock is behind the threshold
     *
     * @param CronTask $task CronTask for log, display information if NULL? (default NULL)
     *
     * @return void
     **/
    public static function cronPrinterCartridgeLevelAlert($task = null)
    {
        global $DB, $CFG_GLPI;

        $cron_status = 1;
        if ($CFG_GLPI["use_notifications"]) {
            $message = [];
            $alert   = new Alert();

            foreach (Entity::getEntitiesToNotify('printer_cartridge_levels_alert_repeat') as $entity => $repeat) {
                // KKK if you change this query, please don't forget to also change in showDebug()
                $query = self::query($entity, $repeat);
                $result = $DB->query($query);
                $message = "";
                $items   = [];
                foreach ($result as $cartridge) {
                    //TRANS: %1$s is the cartridge name, %2$s its reference, %3$d the remaining number
                    //TODO: Manage long messages
                    $message .= sprintf(
                        __('Threshold of cartridge level alarm reached for the cartridge: %1$s on printer %2$s - Remaining %3$d'),
                        $cartridge["cartridgeitem"],
                        $cartridge["printer"],
                        $cartridge["cartridgelevel"]
                    );
                    $message .= '<br>';
                    $items[$cartridge["cartridge"]] = $cartridge;

                    // if alert exists -> delete
                    if (!empty($cartridge["alertID"])) {
                        $alert->delete(["id" => $cartridge["alertID"]]);
                    }
                }

                if (!empty($items)) {
                    $options = [
                        'entities_id' => $entity,
                        'items'       => $items,
                    ];

                    $entityname = Dropdown::getDropdownName("glpi_entities", $entity);
                    if (NotificationEvent::raiseEvent('alert', new PrinterCartridgeLevelAlert(), $options)) {
                        if ($task) {
                            $task->log(sprintf(__('%1$s: %2$s') . "\n", $entityname, $message));
                            $task->addVolume(1);
                        } else {
                            Session::addMessageAfterRedirect(sprintf(
                                __('%1$s: %2$s'),
                                $entityname,
                                $message
                            ));
                        }
                        $input = [
                            'type'     => Alert::THRESHOLD,
                            'itemtype' => 'Cartridge',
                        ];

                        // add alerts
                        foreach (array_keys($items) as $ID) {
                            $input["items_id"] = $ID;
                            $alert->add($input);
                            unset($alert->fields['id']);
                        }
                    } else {
                     //TRANS: %s is entity name
                        $msg = sprintf(__('%s: send cartridge alert failed'), $entityname);
                        if ($task) {
                            $task->log($msg);
                        } else {
                            //TRANS: %s is the entity
                            Session::addMessageAfterRedirect($msg, false, ERROR);
                        }
                    }
                }
            }
        }
        return 0;
    }
}
