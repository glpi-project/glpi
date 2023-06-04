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
 * @var DB $DB
 * @var Migration $migration
 */

$migration->addField('glpi_cartridgeitems', 'type_tag', "varchar(255)");
$migration->addField('glpi_cartridgeitems', `warn_level` int unsigned NOT NULL DEFAULT '0');
$migration->addKey('glpi_cartridgeitems', 'type_tag', 'type_tag');

$migration->addField('glpi_entities', 'printer_cartridge_levels_alert_repeat', "int NOT NULL DEFAULT '-2'");

// Add crontask for low level notifications
$crontask = new CronTask();
if (empty($crontask->find(['name' => 'PrinterCartridgeLevelAlert']))) {
    $cron_added = CronTask::register(
        'PrinterCartridgeLevelAlert',
        'PrinterCartridgeLevelAlert',
        1 * DAY_TIMESTAMP,
        [
            'state' => CronTask::STATE_DISABLE,
            'mode' => CronTask::MODE_EXTERNAL
        ]
    );
}
if (!$cron_added) {
        die("Can't add PrinterCartridgeLevelAlert cron");
}

// Insert notification
$query = "INSERT INTO `glpi_notifications` (`name`, `entities_id`, `itemtype`, `event`, `is_recursive`, `is_active`)
            VALUES ('Printer Cartridge Levels', 0, 'PrinterCartridgeLevelAlert', 'alert', 1, 1)";
$DB->queryOrDie($query);

// Get notification ID
$query_id = "SELECT `id`
                FROM `glpi_notifications`
                WHERE `itemtype`='PrinterCartridgeLevelAlert' AND `name` = 'Printer Cartridge Levels'";
$DB->query($query_id);
$result = $DB->query($query_id) or die($DB->error());
$notification = $DB->result($result, 0, 'id');

// Insert notification template
$DB->queryOrDie("INSERT INTO `glpi_notificationtemplates` ( `date_creation`, `id`, `name`, `itemtype`, `date_mod`, `comment`, `css`) 
            VALUES ('2023-05-26 09:44:46',NULL, 'Printer Cartridge Levels', 'PrinterCartridgeLevelAlert', '2023-05-26 09:44:46','',NULL)");

// Get notification template ID
$query_id = "SELECT `id`
                FROM `glpi_notificationtemplates`
                WHERE `itemtype`='PrinterCartridgeLevelAlert' AND `name` = 'Printer Cartridge Levels'";
$result = $DB->query($query_id) or die($DB->error());
$notificationtemplate = $DB->result($result, 0, 'id');

// Link notification and notificicationtemplate
$query = "INSERT INTO `glpi_notifications_notificationtemplates` (`notifications_id`, `mode`, `notificationtemplates_id`)
            VALUES (" . $notification . ", 'mailing', " . $notificationtemplate . ");";
$DB->queryOrDie($query);

// Insert translation for the notification
$query = "INSERT INTO `glpi_notificationtemplatetranslations`                                 VALUES(NULL, " . $notificationtemplate . ", '','##cartridge.action## : ##cartridge.entity##',
'##FOREACHcartridges##
##lang.cartridge.printer## : ##cartridge.printer##
##lang.cartridge.entity## : ##cartridge.entity##
##lang.cartridge.item## : ##cartridge.item##
##lang.cartridge.level## : ##cartridge.level##
##ENDFOREACHcartridges##',
'&#60;table class=\"tab_cadre\" style=\"width: 85.7401%; height: 23.25px;\" border=\"1\" cellspacing=\"2\" cellpadding=\"3\"&#62;
&#60;tbody&#62;
&#60;tr style=\"height: 23.25px;\"&#62;
&#60;th style=\"text-align: left; width: 16.4225%; height: 23.25px;\" bgcolor=\"#cccccc\"&#62;&#60;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&#62;##lang.cartridge.printer##&#60;/span&#62;&#60;/th&#62;
&#60;th style=\"text-align: left; width: 35.3892%; height: 28.25px;\" bgcolor=\"#cccccc\"&#62;&#60;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&#62;##lang.cartridge.entity##&#60;/span&#62;&#60;/th&#62;
&#60;th style=\"text-align: left; width: 34.2083%; height: 28.25px;\" bgcolor=\"#cccccc\"&#62;&#60;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&#62;##lang.cartridge.item##&#60;/span&#62;&#60;/th&#62;
&#60;th style=\"text-align: left; width: 14.3559%; height: 13.25px;\" bgcolor=\"#cccccc\"&#62;&#60;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&#62;##lang.cartridge.level##&#60;/span&#62;&#60;/th&#62;
&#60;/tr&#62;
##FOREACHcartridges##
&#60;tr style=\"height: 19.25px;\"&#62;
&#60;td style=\"text-align: left; width: 16.4225%;\"&#62;&#60;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&#62;##cartridge.printer##&#60;/span&#62;&#60;/td&#62;
&#60;td style=\"text-align: left; width: 35.3892%;\"&#62;&#60;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&#62;##cartridge.entity##&#60;/span&#62;&#60;/td&#62;
&#60;td style=\"text-align: left; width: 34.2083%;\"&#62;&#60;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&#62;##cartridge.item##&#60;/span&#62;&#60;/td&#62;
&#60;td style=\"text-align: left; width: 14.3559%;\"&#62;&#60;span style=\"font-family: Verdana; font-size: 11px; text-align: left;\"&#62;##cartridge.level##&#60;/span&#62;&#60;/td&#62;
&#60;/tr&#62;
##ENDFOREACHcartridges##
&#60;/tbody&#62;
&#60;/table&#62; ;')";
$DB->queryOrDie($query);

// Insert Entity Administrator as default target for low level cartridge notifications
$query = "INSERT INTO `glpi_notificationtargets` (`items_id`, `type`, `notifications_id`)
            VALUES (11, 1, " . $notification . ");";
$DB->query($query);
