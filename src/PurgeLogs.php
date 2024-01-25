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

class PurgeLogs extends CommonDBTM
{
    protected static $notable = true;

    public static function getTypeName($nb = 0)
    {
        return __('Logs purge');
    }

    public static function cronPurgeLogs($task)
    {
        $cron_status = 0;

        $logs_before = self::getLogsCount();
        if ($logs_before) {
            self::purgeSoftware();
            self::purgeInfocom();
            self::purgeUserInfos();
            self::purgeDevices();
            self::purgeRelations();
            self::purgeItems();
            self::purgeRefusedLogs();
            self::purgeOthers();
            self::purgePlugins();
            self::purgeAll();
            $logs_after = self::getLogsCount();
            Log::history(0, __CLASS__, [0, $logs_before, $logs_after], '', Log::HISTORY_LOG_SIMPLE_MESSAGE);
            $task->addVolume($logs_before - $logs_after);
            $cron_status = 1;
        } else {
            $task->addVolume(0);
        }
        return $cron_status;
    }

    public static function cronInfo($name)
    {
        return ['description' => __("Purge history")];
    }

    /**
     * Purge software logs
     *
     * @return void
     */
    public static function purgeSoftware()
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $month = self::getDateModRestriction($CFG_GLPI['purge_item_software_install']);
        if ($month) {
            $DB->delete(
                'glpi_logs',
                [
                    'itemtype'        => $CFG_GLPI['software_types'],
                    'linked_action'   => [
                        Log::HISTORY_INSTALL_SOFTWARE,
                        Log::HISTORY_UNINSTALL_SOFTWARE
                    ]
                ] + $month
            );
        }

        $month = self::getDateModRestriction($CFG_GLPI['purge_software_item_install']);
        if ($month) {
            $DB->delete(
                'glpi_logs',
                [
                    'itemtype'        => 'SoftwareVersion',
                    'linked_action'   => [
                        Log::HISTORY_INSTALL_SOFTWARE,
                        Log::HISTORY_UNINSTALL_SOFTWARE
                    ]
                ] + $month
            );
        }

        $month = self::getDateModRestriction($CFG_GLPI['purge_software_version_install']);
        if ($month) {
           //Delete software version association
            $DB->delete(
                'glpi_logs',
                [
                    'itemtype'        => 'Software',
                    'itemtype_link'   => 'SoftwareVersion',
                    'linked_action'   => [
                        Log::HISTORY_ADD_SUBITEM,
                        Log::HISTORY_UPDATE_SUBITEM,
                        Log::HISTORY_DELETE_SUBITEM
                    ]
                ] + $month
            );
        }
    }

    /**
     * Purge infocom logs
     *
     * @return void
     */
    public static function purgeInfocom()
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $month = self::getDateModRestriction($CFG_GLPI['purge_infocom_creation']);
        if ($month) {
           //Delete add infocom
            $DB->delete(
                'glpi_logs',
                [
                    'itemtype'        => 'Software',
                    'itemtype_link'   => 'Infocom',
                    'linked_action'   => Log::HISTORY_ADD_SUBITEM
                ] + $month
            );

            $DB->delete(
                'glpi_logs',
                [
                    'itemtype'        => 'Infocom',
                    'linked_action'   => Log::HISTORY_CREATE_ITEM
                ] + $month
            );
        }
    }

    /**
     * Purge users logs
     *
     * @return void
     */
    public static function purgeUserinfos()
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $month = self::getDateModRestriction($CFG_GLPI['purge_profile_user']);
        if ($month) {
           //Delete software version association
            $DB->delete(
                'glpi_logs',
                [
                    'itemtype'        => 'User',
                    'itemtype_link'   => 'Profile_User',
                    'linked_action'   => [
                        Log::HISTORY_ADD_SUBITEM,
                        Log::HISTORY_UPDATE_SUBITEM,
                        Log::HISTORY_DELETE_SUBITEM
                    ]
                ] + $month
            );
        }

        $month = self::getDateModRestriction($CFG_GLPI['purge_group_user']);
        if ($month) {
           //Delete software version association
            $DB->delete(
                'glpi_logs',
                [
                    'itemtype'        => 'User',
                    'itemtype_link'   => 'Group_User',
                    'linked_action'   => [
                        Log::HISTORY_ADD_SUBITEM,
                        Log::HISTORY_UPDATE_SUBITEM,
                        Log::HISTORY_DELETE_SUBITEM
                    ]
                ] + $month
            );
        }

        $month = self::getDateModRestriction($CFG_GLPI['purge_userdeletedfromldap']);
        if ($month) {
           //Delete software version association
            $DB->delete(
                'glpi_logs',
                [
                    'itemtype'        => 'User',
                    'linked_action'   => Log::HISTORY_LOG_SIMPLE_MESSAGE
                ] + $month
            );
        }

        $month = self::getDateModRestriction($CFG_GLPI['purge_user_auth_changes']);
        if ($month) {
           //Delete software version association
            $DB->delete(
                'glpi_logs',
                [
                    'itemtype'        => 'User',
                    'linked_action'   => Log::HISTORY_ADD_RELATION
                ] + $month
            );
        }
    }


    /**
     * Purge devices logs
     *
     * @return void
     */
    public static function purgeDevices()
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $actions = [
            Log::HISTORY_ADD_DEVICE          => "adddevice",
            Log::HISTORY_UPDATE_DEVICE       => "updatedevice",
            Log::HISTORY_DELETE_DEVICE       => "deletedevice",
            Log::HISTORY_CONNECT_DEVICE      => "connectdevice",
            Log::HISTORY_DISCONNECT_DEVICE   => "disconnectdevice"
        ];
        foreach ($actions as $key => $value) {
            $month = self::getDateModRestriction($CFG_GLPI['purge_' . $value]);
            if ($month) {
               //Delete software version association
                $DB->delete(
                    'glpi_logs',
                    [
                        'linked_action' => $key
                    ] + $month
                );
            }
        }
    }

    /**
     * Purge relations logs
     *
     * @return void
     */
    public static function purgeRelations()
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $actions = [
            Log::HISTORY_ADD_RELATION     => "addrelation",
            Log::HISTORY_UPDATE_RELATION  => "addrelation",
            Log::HISTORY_DEL_RELATION     => "deleterelation"
        ];
        foreach ($actions as $key => $value) {
            $month = self::getDateModRestriction($CFG_GLPI['purge_' . $value]);
            if ($month) {
               //Delete software version association
                $DB->delete(
                    'glpi_logs',
                    [
                        'linked_action' => $key
                    ] + $month
                );
            }
        }
    }

    /**
     * Purge items logs
     *
     * @return void
     */
    public static function purgeItems()
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $actions = [
            Log::HISTORY_CREATE_ITEM      => "createitem",
            Log::HISTORY_ADD_SUBITEM      => "createitem",
            Log::HISTORY_DELETE_ITEM      => "deleteitem",
            Log::HISTORY_DELETE_SUBITEM   => "deleteitem",
            Log::HISTORY_UPDATE_SUBITEM   => "updateitem",
            Log::HISTORY_RESTORE_ITEM     => "restoreitem"
        ];
        foreach ($actions as $key => $value) {
            $month = self::getDateModRestriction($CFG_GLPI['purge_' . $value]);
            if ($month) {
               //Delete software version association
                $DB->delete(
                    'glpi_logs',
                    [
                        'linked_action' => $key
                    ] + $month
                );
            }
        }
    }

    /**
     * Purge refused equipments logs
     *
     * @return void
     */
    public static function purgeRefusedLogs()
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $month = self::getDateModRestriction($CFG_GLPI['purge_refusedequipment']);
        if ($month) {
            $refused = new RefusedEquipment();
            $iterator = $DB->request([
                'SELECT' => 'id',
                'FROM' => RefusedEquipment::getTable()
            ] + $month);

            foreach ($iterator as $row) {
                 //purge each one
                 $refused->delete($row, true);
            }
        }
    }


    /**
     * Purge othr logs
     *
     * @return void
     */
    public static function purgeOthers()
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $actions = [
            16 => 'comments',
            19 => 'datemod'
        ];
        foreach ($actions as $key => $value) {
            $month = self::getDateModRestriction($CFG_GLPI['purge_' . $value]);
            if ($month) {
                $DB->delete(
                    'glpi_logs',
                    [
                        'id_search_option' => $key
                    ] + $month
                );
            }
        }
    }


    /**
     * Purge plugins logs
     *
     * @return void
     */
    public static function purgePlugins()
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $month = self::getDateModRestriction($CFG_GLPI['purge_plugins']);
        if ($month) {
            $DB->delete(
                'glpi_logs',
                [
                    'itemtype' => ['LIKE', 'Plugin%']
                ] + $month
            );
        }
    }


    /**
     * Purge all logs
     *
     * @return void
     */
    public static function purgeAll()
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $month = self::getDateModRestriction($CFG_GLPI['purge_all']);
        if ($month) {
            $DB->delete(
                'glpi_logs',
                $month
            );
        }
    }

    /**
     * Get modification date restriction clause
     *
     * @param integer $month Number of months
     *
     * @return array|false
     */
    public static function getDateModRestriction($month)
    {
        if ($month > 0) {
            return ['date_mod' => ['<=', new QueryExpression("DATE_ADD(NOW(), INTERVAL -$month MONTH)")]];
        } else if ($month == Config::DELETE_ALL) {
            return [1 => 1];
        } else if ($month == Config::KEEP_ALL) {
            return false;
        }

        return false; // Unknown value, keep all by default
    }

    /**
     * Count logs
     *
     * @return integer
     */
    public static function getLogsCount()
    {
        return countElementsInTable('glpi_logs');
    }
}
