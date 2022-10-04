<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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

namespace Glpi\Search\Provider;

use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;
use Glpi\Search\SearchEngine;
use Glpi\Search\SearchOption;
use Glpi\Toolbox\Sanitizer;
use Session;

/**
 *
 * @internal Not for use outside {@link Search} class and the "Glpi\Search" namespace.
 */
final class SQLProvider implements SearchProviderInterface
{
    private static function buildSelect(array $data, string $itemtable): string
    {
        // request currentuser for SQL supervision, not displayed
        $SELECT = "SELECT DISTINCT `$itemtable`.`id` AS id, '" . \Toolbox::addslashes_deep($_SESSION['glpiname'] ?? '') . "' AS currentuser,
                        " . self::addDefaultSelect($data['itemtype']);

        // Add select for all toview item
        foreach ($data['toview'] as $val) {
            $SELECT .= self::addSelect($data['itemtype'], $val);
        }

        $as_map = isset($data['search']['as_map']) && (int)$data['search']['as_map'] === 1;
        if ($as_map && $data['itemtype'] !== 'Entity') {
            $SELECT .= ' `glpi_locations`.`id` AS loc_id, ';
        }

        return $SELECT;
    }

    /**
     * Generic Function to add default select to a request
     *
     * @param string $itemtype device type
     *
     * @return string Select string
     **/
    public static function addDefaultSelect($itemtype)
    {
        global $DB;

        $itemtable = SearchEngine::getOrigTableName($itemtype);
        $item      = null;
        $mayberecursive = false;
        if ($itemtype != \AllAssets::getType()) {
            $item           = getItemForItemtype($itemtype);
            $mayberecursive = $item->maybeRecursive();
        }
        $ret = "";
        switch ($itemtype) {
            case 'FieldUnicity':
                $ret = "`glpi_fieldunicities`.`itemtype` AS ITEMTYPE,";
                break;

            default:
                // Plugin can override core definition for its type
                if ($plug = isPluginItemType($itemtype)) {
                    $ret = \Plugin::doOneHook(
                        $plug['plugin'],
                        'addDefaultSelect',
                        $itemtype
                    );
                }
        }
        if ($itemtable == 'glpi_entities') {
            $ret .= "`$itemtable`.`id` AS entities_id, '1' AS is_recursive, ";
        } else if ($mayberecursive) {
            if ($item->isField('entities_id')) {
                $ret .= $DB->quoteName("$itemtable.entities_id") . ", ";
            }
            if ($item->isField('is_recursive')) {
                $ret .= $DB->quoteName("$itemtable.is_recursive") . ", ";
            }
        }
        return $ret;
    }

    /**
     * Generic Function to add select to a request
     *
     * @since 9.4: $num param has been dropped
     *
     * @param string  $itemtype     item type
     * @param integer $ID           ID of the item to add
     * @param boolean $meta         boolean is a meta
     * @param integer $meta_type    meta type table ID (default 0)
     *
     * @return string Select string
     **/
    public static function addSelect($itemtype, $ID, $meta = 0, $meta_type = 0)
    {
        global $DB, $CFG_GLPI;

        $searchopt   = &SearchOption::getOptionsForItemtype($itemtype);
        $table       = $searchopt[$ID]["table"];
        $field       = $searchopt[$ID]["field"];
        $addtable    = "";
        $addtable2   = "";
        $NAME        = "ITEM_{$itemtype}_{$ID}";
        $complexjoin = '';

        if (isset($searchopt[$ID]['joinparams'])) {
            $complexjoin = self::computeComplexJoinID($searchopt[$ID]['joinparams']);
        }

        $is_fkey_composite_on_self = getTableNameForForeignKeyField($searchopt[$ID]["linkfield"]) == $table
            && $searchopt[$ID]["linkfield"] != getForeignKeyFieldForTable($table);

        $orig_table = SearchEngine::getOrigTableName($itemtype);
        if (
            ((($is_fkey_composite_on_self || $table != $orig_table)
                    && (!isset($CFG_GLPI["union_search_type"][$itemtype])
                        || ($CFG_GLPI["union_search_type"][$itemtype] != $table)))
                || !empty($complexjoin))
            && ($searchopt[$ID]["linkfield"] != getForeignKeyFieldForTable($table))
        ) {
            $addtable .= "_" . $searchopt[$ID]["linkfield"];
        }

        if (!empty($complexjoin)) {
            $addtable .= "_" . $complexjoin;
            $addtable2 .= "_" . $complexjoin;
        }

        $addmeta = "";
        if ($meta) {
            // $NAME = "META";
            if ($meta_type::getTable() != $table) {
                $addmeta = "_" . $meta_type;
                $addtable  .= $addmeta;
                $addtable2 .= $addmeta;
            }
        }

        // Plugin can override core definition for its type
        if ($plug = isPluginItemType($itemtype)) {
            $out = \Plugin::doOneHook(
                $plug['plugin'],
                'addSelect',
                $itemtype,
                $ID,
                "{$itemtype}_{$ID}"
            );
            if (!empty($out)) {
                return $out;
            }
        }

        $tocompute      = "`$table$addtable`.`$field`";
        $tocomputeid    = "`$table$addtable`.`id`";

        $tocomputetrans = "IFNULL(`$table" . $addtable . "_trans_" . $field . "`.`value`,'" . \Search::NULLVALUE . "') ";

        $ADDITONALFIELDS = '';
        if (
            isset($searchopt[$ID]["additionalfields"])
            && count($searchopt[$ID]["additionalfields"])
        ) {
            foreach ($searchopt[$ID]["additionalfields"] as $key) {
                if (
                    $meta
                    || (isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])
                ) {
                    $ADDITONALFIELDS .= " IFNULL(GROUP_CONCAT(DISTINCT CONCAT(IFNULL(`$table$addtable`.`$key`,
                                                                         '" . \Search::NULLVALUE . "'),
                                                   '" . \Search::SHORTSEP . "', $tocomputeid)ORDER BY $tocomputeid SEPARATOR '" . \Search::LONGSEP . "'), '" . \Search::NULLVALUE . \Search::SHORTSEP . "')
                                    AS `" . $NAME . "_$key`, ";
                } else {
                    $ADDITONALFIELDS .= "`$table$addtable`.`$key` AS `" . $NAME . "_$key`, ";
                }
            }
        }

        // Virtual display no select : only get additional fields
        if (\Search::isVirtualField($field)) {
            return $ADDITONALFIELDS;
        }

        switch ($table . "." . $field) {
            case "glpi_users.name":
                if ($itemtype != 'User') {
                    if ((isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])) {
                        $addaltemail = "";
                        if (
                            (($itemtype == 'Ticket') || ($itemtype == 'Problem'))
                            && isset($searchopt[$ID]['joinparams']['beforejoin']['table'])
                            && (($searchopt[$ID]['joinparams']['beforejoin']['table']
                                    == 'glpi_tickets_users')
                                || ($searchopt[$ID]['joinparams']['beforejoin']['table']
                                    == 'glpi_problems_users')
                                || ($searchopt[$ID]['joinparams']['beforejoin']['table']
                                    == 'glpi_changes_users'))
                        ) { // For tickets_users
                            $ticket_user_table
                                = $searchopt[$ID]['joinparams']['beforejoin']['table'] .
                                "_" . \Search::computeComplexJoinID($searchopt[$ID]['joinparams']['beforejoin']
                                ['joinparams']) . $addmeta;
                            $addaltemail
                                = "GROUP_CONCAT(DISTINCT CONCAT(`$ticket_user_table`.`users_id`, ' ',
                                                        `$ticket_user_table`.`alternative_email`)
                                                        SEPARATOR '" . \Search::LONGSEP . "') AS `" . $NAME . "_2`, ";
                        }
                        return " GROUP_CONCAT(DISTINCT `$table$addtable`.`id` SEPARATOR '" . \Search::LONGSEP . "')
                                       AS `" . $NAME . "`,
                           $addaltemail
                           $ADDITONALFIELDS";
                    }
                    return " `$table$addtable`.`$field` AS `" . $NAME . "`,
                        `$table$addtable`.`realname` AS `" . $NAME . "_realname`,
                        `$table$addtable`.`id`  AS `" . $NAME . "_id`,
                        `$table$addtable`.`firstname` AS `" . $NAME . "_firstname`,
                        $ADDITONALFIELDS";
                }
                break;

            case "glpi_softwarelicenses.number":
                if ($meta) {
                    return " FLOOR(SUM(`$table$addtable2`.`$field`)
                              * COUNT(DISTINCT `$table$addtable2`.`id`)
                              / COUNT(`$table$addtable2`.`id`)) AS `" . $NAME . "`,
                        MIN(`$table$addtable2`.`$field`) AS `" . $NAME . "_min`,
                         $ADDITONALFIELDS";
                } else {
                    return " FLOOR(SUM(`$table$addtable`.`$field`)
                              * COUNT(DISTINCT `$table$addtable`.`id`)
                              / COUNT(`$table$addtable`.`id`)) AS `" . $NAME . "`,
                        MIN(`$table$addtable`.`$field`) AS `" . $NAME . "_min`,
                         $ADDITONALFIELDS";
                }

            case "glpi_profiles.name":
                if (
                    ($itemtype == 'User')
                    && ($ID == 20)
                ) {
                    $addtable2 = '';
                    if ($meta) {
                        $addtable2 = "_" . $meta_type;
                    }
                    return " GROUP_CONCAT(`$table$addtable`.`$field` SEPARATOR '" . \Search::LONGSEP . "') AS `" . $NAME . "`,
                        GROUP_CONCAT(`glpi_profiles_users$addtable2`.`entities_id` SEPARATOR '" . \Search::LONGSEP . "')
                                    AS `" . $NAME . "_entities_id`,
                        GROUP_CONCAT(`glpi_profiles_users$addtable2`.`is_recursive` SEPARATOR '" . \Search::LONGSEP . "')
                                    AS `" . $NAME . "_is_recursive`,
                        GROUP_CONCAT(`glpi_profiles_users$addtable2`.`is_dynamic` SEPARATOR '" . \Search::LONGSEP . "')
                                    AS `" . $NAME . "_is_dynamic`,
                        $ADDITONALFIELDS";
                }
                break;

            case "glpi_entities.completename":
                if (
                    ($itemtype == 'User')
                    && ($ID == 80)
                ) {
                    $addtable2 = '';
                    if ($meta) {
                        $addtable2 = "_" . $meta_type;
                    }
                    return " GROUP_CONCAT(`$table$addtable`.`completename` SEPARATOR '" . \Search::LONGSEP . "')
                                    AS `" . $NAME . "`,
                        GROUP_CONCAT(`glpi_profiles_users$addtable2`.`profiles_id` SEPARATOR '" . \Search::LONGSEP . "')
                                    AS `" . $NAME . "_profiles_id`,
                        GROUP_CONCAT(`glpi_profiles_users$addtable2`.`is_recursive` SEPARATOR '" . \Search::LONGSEP . "')
                                    AS `" . $NAME . "_is_recursive`,
                        GROUP_CONCAT(`glpi_profiles_users$addtable2`.`is_dynamic` SEPARATOR '" . \Search::LONGSEP . "')
                                    AS `" . $NAME . "_is_dynamic`,
                        $ADDITONALFIELDS";
                }
                break;

            case "glpi_auth_tables.name":
                $user_searchopt = SearchOption::getOptionsForItemtype('User');
                return " `glpi_users`.`authtype` AS `" . $NAME . "`,
                     `glpi_users`.`auths_id` AS `" . $NAME . "_auths_id`,
                     `glpi_authldaps" . $addtable . "_" .
                    \Search::computeComplexJoinID($user_searchopt[30]['joinparams']) . $addmeta . "`.`$field`
                              AS `" . $NAME . "_" . $ID . "_ldapname`,
                     `glpi_authmails" . $addtable . "_" .
                    \Search::computeComplexJoinID($user_searchopt[31]['joinparams']) . $addmeta . "`.`$field`
                              AS `" . $NAME . "_mailname`,
                     $ADDITONALFIELDS";

            case "glpi_softwareversions.name":
                if ($meta && ($meta_type == 'Software')) {
                    return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwares`.`name`, ' - ',
                                                     `$table$addtable2`.`$field`, '" . \Search::SHORTSEP . "',
                                                     `$table$addtable2`.`id`) SEPARATOR '" . \Search::LONGSEP . "')
                                    AS `" . $NAME . "`,
                        $ADDITONALFIELDS";
                }
                break;

            case "glpi_softwareversions.comment":
                if ($meta && ($meta_type == 'Software')) {
                    return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwares`.`name`, ' - ',
                                                     `$table$addtable2`.`$field`,'" . \Search::SHORTSEP . "',
                                                     `$table$addtable2`.`id`) SEPARATOR '" . \Search::LONGSEP . "')
                                    AS `" . $NAME . "`,
                        $ADDITONALFIELDS";
                }
                return " GROUP_CONCAT(DISTINCT CONCAT(`$table$addtable`.`name`, ' - ',
                                                  `$table$addtable`.`$field`, '" . \Search::SHORTSEP . "',
                                                  `$table$addtable`.`id`) SEPARATOR '" . \Search::LONGSEP . "')
                                 AS `" . $NAME . "`,
                     $ADDITONALFIELDS";

            case "glpi_states.name":
                if ($meta && ($meta_type == 'Software')) {
                    return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwares`.`name`, ' - ',
                                                     `glpi_softwareversions$addtable`.`name`, ' - ',
                                                     `$table$addtable2`.`$field`, '" . \Search::SHORTSEP . "',
                                                     `$table$addtable2`.`id`) SEPARATOR '" . \Search::LONGSEP . "')
                                     AS `" . $NAME . "`,
                        $ADDITONALFIELDS";
                } else if ($itemtype == 'Software') {
                    return " GROUP_CONCAT(DISTINCT CONCAT(`glpi_softwareversions`.`name`, ' - ',
                                                     `$table$addtable`.`$field`,'" . \Search::SHORTSEP . "',
                                                     `$table$addtable`.`id`) SEPARATOR '" . \Search::LONGSEP . "')
                                    AS `" . $NAME . "`,
                        $ADDITONALFIELDS";
                }
                break;

            case "glpi_itilfollowups.content":
            case "glpi_tickettasks.content":
            case "glpi_changetasks.content":
                if (is_subclass_of($itemtype, "CommonITILObject")) {
                    // force ordering by date desc
                    return " GROUP_CONCAT(
                  DISTINCT CONCAT(
                     IFNULL($tocompute, '" . \Search::NULLVALUE . "'),
                     '" . \Search::SHORTSEP . "',
                     $tocomputeid
                  )
                  ORDER BY `$table$addtable`.`date` DESC
                  SEPARATOR '" . \Search::LONGSEP . "'
               ) AS `" . $NAME . "`, $ADDITONALFIELDS";
                }
                break;

            default:
                break;
        }

        //// Default cases
        // Link with plugin tables
        if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table, $matches)) {
            if (count($matches) == 2) {
                $plug     = $matches[1];
                $out = \Plugin::doOneHook(
                    $plug,
                    'addSelect',
                    $itemtype,
                    $ID,
                    "{$itemtype}_{$ID}"
                );
                if (!empty($out)) {
                    return $out;
                }
            }
        }

        if (isset($searchopt[$ID]["computation"])) {
            $tocompute = $searchopt[$ID]["computation"];
            $tocompute = str_replace($DB->quoteName('TABLE'), 'TABLE', $tocompute);
            $tocompute = str_replace("TABLE", $DB->quoteName("$table$addtable"), $tocompute);
        }
        // Preformat items
        if (isset($searchopt[$ID]["datatype"])) {
            switch ($searchopt[$ID]["datatype"]) {
                case "count":
                    return " COUNT(DISTINCT `$table$addtable`.`$field`) AS `" . $NAME . "`,
                     $ADDITONALFIELDS";

                case "date_delay":
                    $interval = "MONTH";
                    if (isset($searchopt[$ID]['delayunit'])) {
                        $interval = $searchopt[$ID]['delayunit'];
                    }

                    $add_minus = '';
                    if (isset($searchopt[$ID]["datafields"][3])) {
                        $add_minus = "-`$table$addtable`.`" . $searchopt[$ID]["datafields"][3] . "`";
                    }
                    if (
                        $meta
                        || (isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])
                    ) {
                        return " GROUP_CONCAT(DISTINCT ADDDATE(`$table$addtable`.`" .
                            $searchopt[$ID]["datafields"][1] . "`,
                                                         INTERVAL (`$table$addtable`.`" .
                            $searchopt[$ID]["datafields"][2] .
                            "` $add_minus) $interval)
                                         SEPARATOR '" . \Search::LONGSEP . "') AS `" . $NAME . "`,
                           $ADDITONALFIELDS";
                    }
                    return "ADDDATE(`$table$addtable`.`" . $searchopt[$ID]["datafields"][1] . "`,
                               INTERVAL (`$table$addtable`.`" . $searchopt[$ID]["datafields"][2] .
                        "` $add_minus) $interval) AS `" . $NAME . "`,
                       $ADDITONALFIELDS";

                case "itemlink":
                    if (
                        $meta
                        || (isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])
                    ) {
                        $TRANS = '';
                        if (Session::haveTranslations(getItemTypeForTable($table), $field)) {
                            $TRANS = "GROUP_CONCAT(DISTINCT CONCAT(IFNULL($tocomputetrans, '" . \Search::NULLVALUE . "'),
                                                             '" . \Search::SHORTSEP . "',$tocomputeid) ORDER BY $tocomputeid
                                             SEPARATOR '" . \Search::LONGSEP . "')
                                     AS `" . $NAME . "_trans_" . $field . "`, ";
                        }

                        return " GROUP_CONCAT(DISTINCT CONCAT($tocompute, '" . \Search::SHORTSEP . "' ,
                                                        `$table$addtable`.`id`) ORDER BY `$table$addtable`.`id`
                                        SEPARATOR '" . \Search::LONGSEP . "') AS `" . $NAME . "`,
                           $TRANS
                           $ADDITONALFIELDS";
                    }
                    return " $tocompute AS `" . $NAME . "`,
                        `$table$addtable`.`id` AS `" . $NAME . "_id`,
                        $ADDITONALFIELDS";
            }
        }

        // Default case
        if (
            $meta
            || (isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"]
                && (!isset($searchopt[$ID]["computation"])
                    || isset($searchopt[$ID]["computationgroupby"])
                    && $searchopt[$ID]["computationgroupby"]))
        ) { // Not specific computation
            $TRANS = '';
            if (Session::haveTranslations(getItemTypeForTable($table), $field)) {
                $TRANS = "GROUP_CONCAT(DISTINCT CONCAT(IFNULL($tocomputetrans, '" . \Search::NULLVALUE . "'),
                                                   '" . \Search::SHORTSEP . "',$tocomputeid) ORDER BY $tocomputeid SEPARATOR '" . \Search::LONGSEP . "')
                                  AS `" . $NAME . "_trans_" . $field . "`, ";
            }
            return " GROUP_CONCAT(DISTINCT CONCAT(IFNULL($tocompute, '" . \Search::NULLVALUE . "'),
                                               '" . \Search::SHORTSEP . "',$tocomputeid) ORDER BY $tocomputeid SEPARATOR '" . \Search::LONGSEP . "')
                              AS `" . $NAME . "`,
                  $TRANS
                  $ADDITONALFIELDS";
        }
        $TRANS = '';
        if (Session::haveTranslations(getItemTypeForTable($table), $field)) {
            $TRANS = $tocomputetrans . " AS `" . $NAME . "_trans_" . $field . "`, ";
        }
        return "$tocompute AS `" . $NAME . "`, $TRANS $ADDITONALFIELDS";
    }

    private static function buildFrom(string $itemtable): string
    {
        return " FROM `$itemtable`";
    }

    /**
     * Generic Function to add default where to a request
     *
     * @param string $itemtype device type
     *
     * @return string Where string
     **/
    public static function addDefaultWhere($itemtype)
    {
        $condition = '';

        switch ($itemtype) {
            case 'Reservation':
                $condition = getEntitiesRestrictRequest("", \ReservationItem::getTable(), '', '', true);
                break;

            case 'Reminder':
                $condition = \Reminder::addVisibilityRestrict();
                break;

            case 'RSSFeed':
                $condition = \RSSFeed::addVisibilityRestrict();
                break;

            case 'Notification':
                if (!\Config::canView()) {
                    $condition = " `glpi_notifications`.`itemtype` NOT IN ('CronTask', 'DBConnection') ";
                }
                break;

            // No link
            case 'User':
                // View all entities
                if (!Session::canViewAllEntities()) {
                    $condition = getEntitiesRestrictRequest("", "glpi_profiles_users", '', '', true);
                }
                break;

            case 'ProjectTask':
                $condition  = '';
                $teamtable  = 'glpi_projecttaskteams';
                $condition .= "`glpi_projects`.`is_template` = 0";
                $condition .= " AND ((`$teamtable`.`itemtype` = 'User'
                             AND `$teamtable`.`items_id` = '" . Session::getLoginUserID() . "')";
                if (count($_SESSION['glpigroups'])) {
                    $condition .= " OR (`$teamtable`.`itemtype` = 'Group'
                                    AND `$teamtable`.`items_id`
                                       IN (" . implode(",", $_SESSION['glpigroups']) . "))";
                }
                $condition .= ") ";
                break;

            case 'Project':
                $condition = '';
                if (!Session::haveRight("project", \Project::READALL)) {
                    $teamtable  = 'glpi_projectteams';
                    $condition .= "(`glpi_projects`.users_id = '" . Session::getLoginUserID() . "'
                               OR (`$teamtable`.`itemtype` = 'User'
                                   AND `$teamtable`.`items_id` = '" . Session::getLoginUserID() . "')";
                    if (count($_SESSION['glpigroups'])) {
                        $condition .= " OR (`glpi_projects`.`groups_id`
                                       IN (" . implode(",", $_SESSION['glpigroups']) . "))";
                        $condition .= " OR (`$teamtable`.`itemtype` = 'Group'
                                      AND `$teamtable`.`items_id`
                                          IN (" . implode(",", $_SESSION['glpigroups']) . "))";
                    }
                    $condition .= ") ";
                }
                break;

            case 'Ticket':
                // Same structure in addDefaultJoin
                $condition = '';
                if (!Session::haveRight("ticket", \Ticket::READALL)) {
                    $searchopt
                        = &SearchOption::getOptionsForItemtype($itemtype);
                    $requester_table
                        = '`glpi_tickets_users_' .
                        self::computeComplexJoinID($searchopt[4]['joinparams']['beforejoin']
                        ['joinparams']) . '`';
                    $requestergroup_table
                        = '`glpi_groups_tickets_' .
                        self::computeComplexJoinID($searchopt[71]['joinparams']['beforejoin']
                        ['joinparams']) . '`';

                    $assign_table
                        = '`glpi_tickets_users_' .
                        self::computeComplexJoinID($searchopt[5]['joinparams']['beforejoin']
                        ['joinparams']) . '`';
                    $assigngroup_table
                        = '`glpi_groups_tickets_' .
                        self::computeComplexJoinID($searchopt[8]['joinparams']['beforejoin']
                        ['joinparams']) . '`';

                    $observer_table
                        = '`glpi_tickets_users_' .
                        self::computeComplexJoinID($searchopt[66]['joinparams']['beforejoin']
                        ['joinparams']) . '`';
                    $observergroup_table
                        = '`glpi_groups_tickets_' .
                        self::computeComplexJoinID($searchopt[65]['joinparams']['beforejoin']
                        ['joinparams']) . '`';

                    $condition = "(";

                    if (Session::haveRight("ticket", \Ticket::READMY)) {
                        $condition .= " $requester_table.users_id = '" . Session::getLoginUserID() . "'
                                    OR $observer_table.users_id = '" . Session::getLoginUserID() . "'
                                    OR `glpi_tickets`.`users_id_recipient` = '" . Session::getLoginUserID() . "'";
                    } else {
                        $condition .= "0=1";
                    }

                    if (Session::haveRight("ticket", \Ticket::READGROUP)) {
                        if (count($_SESSION['glpigroups'])) {
                            $condition .= " OR $requestergroup_table.`groups_id`
                                             IN (" . implode(",", $_SESSION['glpigroups']) . ")";
                            $condition .= " OR $observergroup_table.`groups_id`
                                             IN (" . implode(",", $_SESSION['glpigroups']) . ")";
                        }
                    }

                    if (Session::haveRight("ticket", \Ticket::OWN)) {// Can own ticket : show assign to me
                        $condition .= " OR $assign_table.users_id = '" . Session::getLoginUserID() . "' ";
                    }

                    if (Session::haveRight("ticket", \Ticket::READASSIGN)) { // assign to me
                        $condition .= " OR $assign_table.`users_id` = '" . Session::getLoginUserID() . "'";
                        if (count($_SESSION['glpigroups'])) {
                            $condition .= " OR $assigngroup_table.`groups_id`
                                             IN (" . implode(",", $_SESSION['glpigroups']) . ")";
                        }
                        if (Session::haveRight('ticket', \Ticket::ASSIGN)) {
                            $condition .= " OR `glpi_tickets`.`status`='" . \CommonITILObject::INCOMING . "'";
                        }
                    }

                    if (
                        Session::haveRightsOr(
                            'ticketvalidation',
                            [\TicketValidation::VALIDATEINCIDENT,
                                \TicketValidation::VALIDATEREQUEST
                            ]
                        )
                    ) {
                        $condition .= " OR (`glpi_ticketvalidations`.`itemtype_target` = 'User' AND `glpi_ticketvalidations`.`items_id_target` = '" . Session::getLoginUserID() . "')";
                        if (count($_SESSION['glpigroups'])) {
                            $condition .= " OR (`glpi_ticketvalidations`.`itemtype_target` = 'Group' AND `glpi_ticketvalidations`.`items_id_target` IN (" . implode(",", $_SESSION['glpigroups']) . "))";
                        }
                    }
                    $condition .= ") ";
                }
                break;

            case 'Change':
            case 'Problem':
                if ($itemtype == 'Change') {
                    $right       = 'change';
                    $table       = 'changes';
                    $groupetable = "`glpi_changes_groups_";
                } else if ($itemtype == 'Problem') {
                    $right       = 'problem';
                    $table       = 'problems';
                    $groupetable = "`glpi_groups_problems_";
                }
                // Same structure in addDefaultJoin
                $condition = '';
                if (!Session::haveRight("$right", $itemtype::READALL)) {
                    $searchopt       = &SearchOption::getOptionsForItemtype($itemtype);
                    if (Session::haveRight("$right", $itemtype::READMY)) {
                        $requester_table      = '`glpi_' . $table . '_users_' .
                            self::computeComplexJoinID($searchopt[4]['joinparams']
                            ['beforejoin']['joinparams']) . '`';
                        $requestergroup_table = $groupetable .
                            self::computeComplexJoinID($searchopt[71]['joinparams']
                            ['beforejoin']['joinparams']) . '`';

                        $observer_table       = '`glpi_' . $table . '_users_' .
                            self::computeComplexJoinID($searchopt[66]['joinparams']
                            ['beforejoin']['joinparams']) . '`';
                        $observergroup_table  = $groupetable .
                            self::computeComplexJoinID($searchopt[65]['joinparams']
                            ['beforejoin']['joinparams']) . '`';

                        $assign_table         = '`glpi_' . $table . '_users_' .
                            self::computeComplexJoinID($searchopt[5]['joinparams']
                            ['beforejoin']['joinparams']) . '`';
                        $assigngroup_table    = $groupetable .
                            self::computeComplexJoinID($searchopt[8]['joinparams']
                            ['beforejoin']['joinparams']) . '`';
                    }
                    $condition = "(";

                    if (Session::haveRight("$right", $itemtype::READMY)) {
                        $condition .= " $requester_table.users_id = '" . Session::getLoginUserID() . "'
                                 OR $observer_table.users_id = '" . Session::getLoginUserID() . "'
                                 OR $assign_table.users_id = '" . Session::getLoginUserID() . "'
                                 OR `glpi_" . $table . "`.`users_id_recipient` = '" . Session::getLoginUserID() . "'";
                        if (count($_SESSION['glpigroups'])) {
                            $my_groups_keys = "'" . implode("','", $_SESSION['glpigroups']) . "'";
                            $condition .= " OR $requestergroup_table.groups_id IN ($my_groups_keys)
                                 OR $observergroup_table.groups_id IN ($my_groups_keys)
                                 OR $assigngroup_table.groups_id IN ($my_groups_keys)";
                        }
                    } else {
                        $condition .= "0=1";
                    }

                    $condition .= ") ";
                }
                break;

            case 'Config':
                $availableContexts = ['core'] + \Plugin::getPlugins();
                $availableContexts = implode("', '", $availableContexts);
                $condition = "`context` IN ('$availableContexts')";
                break;

            case 'SavedSearch':
                $condition = \SavedSearch::addVisibilityRestrict();
                break;

            case 'TicketTask':
                // Filter on is_private
                $allowed_is_private = [];
                if (Session::haveRight(\TicketTask::$rightname, \CommonITILTask::SEEPRIVATE)) {
                    $allowed_is_private[] = 1;
                }
                if (Session::haveRight(\TicketTask::$rightname, \CommonITILTask::SEEPUBLIC)) {
                    $allowed_is_private[] = 0;
                }

                // If the user can't see public and private
                if (!count($allowed_is_private)) {
                    $condition = "0 = 1";
                    break;
                }

                $in = "IN ('" . implode("','", $allowed_is_private) . "')";
                $condition = "(`glpi_tickettasks`.`is_private` $in ";

                // Check for assigned or created tasks
                $condition .= "OR `glpi_tickettasks`.`users_id` = " . Session::getLoginUserID() . " ";
                $condition .= "OR `glpi_tickettasks`.`users_id_tech` = " . Session::getLoginUserID() . " ";

                // Check for parent item visibility unless the user can see all the
                // possible parents
                if (!Session::haveRight('ticket', \Ticket::READALL)) {
                    $condition .= "AND " . \TicketTask::buildParentCondition();
                }

                $condition .= ")";

                break;

            case 'ITILFollowup':
                // Filter on is_private
                $allowed_is_private = [];
                if (Session::haveRight(\ITILFollowup::$rightname, \ITILFollowup::SEEPRIVATE)) {
                    $allowed_is_private[] = 1;
                }
                if (Session::haveRight(\ITILFollowup::$rightname, \ITILFollowup::SEEPUBLIC)) {
                    $allowed_is_private[] = 0;
                }

                // If the user can't see public and private
                if (!count($allowed_is_private)) {
                    $condition = "0 = 1";
                    break;
                }

                $in = "IN ('" . implode("','", $allowed_is_private) . "')";
                $condition = "(`glpi_itilfollowups`.`is_private` $in ";

                // Now filter on parent item visiblity
                $condition .= "AND (";

                // Filter for "ticket" parents
                $condition .= \ITILFollowup::buildParentCondition(\Ticket::getType());
                $condition .= "OR ";

                // Filter for "change" parents
                $condition .= \ITILFollowup::buildParentCondition(
                    \Change::getType(),
                    'changes_id',
                    "glpi_changes_users",
                    "glpi_changes_groups"
                );
                $condition .= "OR ";

                // Fitler for "problem" parents
                $condition .= \ITILFollowup::buildParentCondition(
                    \Problem::getType(),
                    'problems_id',
                    "glpi_problems_users",
                    "glpi_groups_problems"
                );
                $condition .= "))";

                break;

            default:
                // Plugin can override core definition for its type
                if ($plug = isPluginItemType($itemtype)) {
                    $condition = \Plugin::doOneHook($plug['plugin'], 'addDefaultWhere', $itemtype);
                }
                break;
        }

        /* Hook to restrict user right on current itemtype */
        [$itemtype, $condition] = \Plugin::doHookFunction('add_default_where', [$itemtype, $condition]);
        return $condition;
    }

    /**
     * Generic Function to add where to a request
     *
     * @param string  $link         Link string
     * @param boolean $nott         Is it a negative search ?
     * @param string  $itemtype     Item type
     * @param integer $ID           ID of the item to search
     * @param string  $searchtype   Searchtype used (equals or contains)
     * @param string  $val          Item num in the request
     * @param integer $meta         Is a meta search (meta=2 in search.class.php) (default 0)
     *
     * @return string Where string
     **/
    public static function addWhere($link, $nott, $itemtype, $ID, $searchtype, $val, $meta = 0)
    {

        global $DB;

        $searchopt = &SearchOption::getOptionsForItemtype($itemtype);
        if (!isset($searchopt[$ID]['table'])) {
            return false;
        }
        $table     = $searchopt[$ID]["table"];
        $field     = $searchopt[$ID]["field"];

        $inittable = $table;
        $addtable  = '';
        $is_fkey_composite_on_self = getTableNameForForeignKeyField($searchopt[$ID]["linkfield"]) == $table
            && $searchopt[$ID]["linkfield"] != getForeignKeyFieldForTable($table);
        $orig_table = SearchEngine::getOrigTableName($itemtype);
        if (
            ($table != 'asset_types')
            && ($is_fkey_composite_on_self || $table != $orig_table)
            && ($searchopt[$ID]["linkfield"] != getForeignKeyFieldForTable($table))
        ) {
            $addtable = "_" . $searchopt[$ID]["linkfield"];
            $table   .= $addtable;
        }

        if (isset($searchopt[$ID]['joinparams'])) {
            $complexjoin = \Search::computeComplexJoinID($searchopt[$ID]['joinparams']);

            if (!empty($complexjoin)) {
                $table .= "_" . $complexjoin;
            }
        }

        $addmeta = "";
        if (
            $meta
            && ($itemtype::getTable() != $inittable)
        ) {
            $addmeta = "_" . $itemtype;
            $table .= $addmeta;
        }

        // Hack to allow search by ID on every sub-table
        if (preg_match('/^\$\$\$\$([0-9]+)$/', $val, $regs)) {
            return $link . " (`$table`.`id` " . ($nott ? "<>" : "=") . $regs[1] . " " .
                (($regs[1] == 0) ? " OR `$table`.`id` IS NULL" : '') . ") ";
        }

        // Preparse value
        if (isset($searchopt[$ID]["datatype"])) {
            switch ($searchopt[$ID]["datatype"]) {
                case "datetime":
                case "date":
                case "date_delay":
                    $force_day = true;
                    if (
                        $searchopt[$ID]["datatype"] == 'datetime'
                        && !(strstr($val, 'BEGIN') || strstr($val, 'LAST') || strstr($val, 'DAY'))
                    ) {
                        $force_day = false;
                    }

                    $val = \Html::computeGenericDateTimeSearch($val, $force_day);

                    break;
            }
        }
        switch ($searchtype) {
            case "notcontains":
                $nott = !$nott;
            //negated, use contains case
            case "contains":
                if (isset($searchopt[$ID]["datatype"]) && ($searchopt[$ID]["datatype"] === 'decimal')) {
                    $matches = [];
                    if (preg_match('/^(\d+.?\d?)/', $val, $matches)) {
                        $val = $matches[1];
                        if (!str_contains($val, '.')) {
                            $val .= '.';
                        }
                    }
                }
                $SEARCH = self::makeTextSearch($val, $nott);
                break;

            case "equals":
                if ($nott) {
                    $SEARCH = " <> " . \DBmysql::quoteValue($val);
                } else {
                    $SEARCH = " = " . \DBmysql::quoteValue($val);
                }
                break;

            case "notequals":
                if ($nott) {
                    $SEARCH = " = " . \DBmysql::quoteValue($val);
                } else {
                    $SEARCH = " <> " . \DBmysql::quoteValue($val);
                }
                break;

            case "under":
                if ($nott) {
                    $SEARCH = " NOT IN ('" . implode("','", getSonsOf($inittable, $val)) . "')";
                } else {
                    $SEARCH = " IN ('" . implode("','", getSonsOf($inittable, $val)) . "')";
                }
                break;

            case "notunder":
                if ($nott) {
                    $SEARCH = " IN ('" . implode("','", getSonsOf($inittable, $val)) . "')";
                } else {
                    $SEARCH = " NOT IN ('" . implode("','", getSonsOf($inittable, $val)) . "')";
                }
                break;

            case "empty":
                if ($nott) {
                    $SEARCH = " IS NOT NULL";
                } else {
                    $SEARCH = " IS NULL";
                }
                break;
        }

        //Check in current item if a specific where is defined
        if (method_exists($itemtype, 'addWhere')) {
            $out = $itemtype::addWhere($link, $nott, $itemtype, $ID, $searchtype, $val);
            if (!empty($out)) {
                return $out;
            }
        }

        // Plugin can override core definition for its type
        if ($plug = isPluginItemType($itemtype)) {
            $out = \Plugin::doOneHook(
                $plug['plugin'],
                'addWhere',
                $link,
                $nott,
                $itemtype,
                $ID,
                $val,
                $searchtype
            );
            if (!empty($out)) {
                return $out;
            }
        }

        switch ($inittable . "." . $field) {
            // case "glpi_users_validation.name" :

            case "glpi_users.name":
                if ($val == 'myself') {
                    switch ($searchtype) {
                        case 'equals':
                            return " $link (`$table`.`id` =  " . $DB->quoteValue($_SESSION['glpiID']) . ") ";

                        case 'notequals':
                            return " $link (`$table`.`id` <>  " . $DB->quoteValue($_SESSION['glpiID']) . ") ";
                    }
                }

                if ($itemtype == 'User') { // glpi_users case / not link table
                    if (in_array($searchtype, ['equals', 'notequals'])) {
                        $search_str = "`$table`.`id`" . $SEARCH;

                        if ($searchtype == 'notequals') {
                            $nott = !$nott;
                        }

                        // Add NULL if $val = 0 and not negative search
                        // Or negative search on real value
                        if ((!$nott && ($val == 0)) || ($nott && ($val != 0))) {
                            $search_str .= " OR `$table`.`id` IS NULL";
                        }

                        return " $link ($search_str)";
                    }
                    return self::makeTextCriteria("`$table`.`$field`", $val, $nott, $link);
                }
                if ($_SESSION["glpinames_format"] == \User::FIRSTNAME_BEFORE) {
                    $name1 = 'firstname';
                    $name2 = 'realname';
                } else {
                    $name1 = 'realname';
                    $name2 = 'firstname';
                }

                if (in_array($searchtype, ['equals', 'notequals'])) {
                    return " $link (`$table`.`id`" . $SEARCH .
                        (($val == 0) ? " OR `$table`.`id` IS" .
                            (($searchtype == "notequals") ? " NOT" : "") . " NULL" : '') . ') ';
                } else if ($searchtype == 'empty') {
                    return " $link (`$table`.`id` $SEARCH)";
                }
                $toadd   = '';

                $tmplink = 'OR';
                if ($nott) {
                    $tmplink = 'AND';
                }

                if (is_a($itemtype, \CommonITILObject::class, true)) {
                    if (
                        isset($searchopt[$ID]["joinparams"]["beforejoin"]["table"])
                        && isset($searchopt[$ID]["joinparams"]["beforejoin"]["joinparams"])
                        && (($searchopt[$ID]["joinparams"]["beforejoin"]["table"]
                                == 'glpi_tickets_users')
                            || ($searchopt[$ID]["joinparams"]["beforejoin"]["table"]
                                == 'glpi_problems_users')
                            || ($searchopt[$ID]["joinparams"]["beforejoin"]["table"]
                                == 'glpi_changes_users'))
                    ) {
                        $bj        = $searchopt[$ID]["joinparams"]["beforejoin"];
                        $linktable = $bj['table'] . '_' . \Search::computeComplexJoinID($bj['joinparams']) . $addmeta;
                        //$toadd     = "`$linktable`.`alternative_email` $SEARCH $tmplink ";
                        $toadd     = self::makeTextCriteria(
                            "`$linktable`.`alternative_email`",
                            $val,
                            $nott,
                            $tmplink
                        );
                        if ($val == '^$') {
                            return $link . " ((`$linktable`.`users_id` IS NULL)
                            OR `$linktable`.`alternative_email` IS NULL)";
                        }
                    }
                }
                $toadd2 = '';
                if (
                    $nott
                    && ($val != 'NULL') && ($val != 'null')
                ) {
                    $toadd2 = " OR `$table`.`$field` IS NULL";
                }
                return $link . " (((`$table`.`$name1` $SEARCH
                            $tmplink `$table`.`$name2` $SEARCH
                            $tmplink `$table`.`$field` $SEARCH
                            $tmplink CONCAT(`$table`.`$name1`, ' ', `$table`.`$name2`) $SEARCH )
                            $toadd2) $toadd)";

            case "glpi_groups.completename":
                if ($val == 'mygroups') {
                    switch ($searchtype) {
                        case 'equals':
                            return " $link (`$table`.`id` IN ('" . implode(
                                "','",
                                $_SESSION['glpigroups']
                            ) . "')) ";

                        case 'notequals':
                            return " $link (`$table`.`id` NOT IN ('" . implode(
                                "','",
                                $_SESSION['glpigroups']
                            ) . "')) ";

                        case 'under':
                            $groups = $_SESSION['glpigroups'];
                            foreach ($_SESSION['glpigroups'] as $g) {
                                $groups += getSonsOf($inittable, $g);
                            }
                            $groups = array_unique($groups);
                            return " $link (`$table`.`id` IN ('" . implode("','", $groups) . "')) ";

                        case 'notunder':
                            $groups = $_SESSION['glpigroups'];
                            foreach ($_SESSION['glpigroups'] as $g) {
                                $groups += getSonsOf($inittable, $g);
                            }
                            $groups = array_unique($groups);
                            return " $link (`$table`.`id` NOT IN ('" . implode("','", $groups) . "')) ";

                        case 'empty':
                            return " $link (`$table`.`id` $SEARCH) ";
                    }
                }
                break;

            case "glpi_auth_tables.name":
                $user_searchopt = SearchOption::getOptionsForItemtype('User');
                $tmplink        = 'OR';
                if ($nott) {
                    $tmplink = 'AND';
                }
                return $link . " (`glpi_authmails" . $addtable . "_" .
                    \Search::computeComplexJoinID($user_searchopt[31]['joinparams']) . $addmeta . "`.`name`
                           $SEARCH
                           $tmplink `glpi_authldaps" . $addtable . "_" .
                    \Search::computeComplexJoinID($user_searchopt[30]['joinparams']) . $addmeta . "`.`name`
                           $SEARCH ) ";

            case "glpi_ipaddresses.name":
                $search  = ["/\&lt;/","/\&gt;/"];
                $replace = ["<",">"];
                $val     = preg_replace($search, $replace, $val);
                if (preg_match("/^\s*([<>])([=]*)[[:space:]]*([0-9\.]+)/", $val, $regs)) {
                    if ($nott) {
                        if ($regs[1] == '<') {
                            $regs[1] = '>';
                        } else {
                            $regs[1] = '<';
                        }
                    }
                    $regs[1] .= $regs[2];
                    return $link . " (INET_ATON(`$table`.`$field`) " . $regs[1] . " INET_ATON('" . $regs[3] . "')) ";
                }
                break;

            case "glpi_tickets.status":
            case "glpi_problems.status":
            case "glpi_changes.status":
                $tocheck = [];
                /** @var \CommonITILObject $item */
                if ($item = getItemForItemtype($itemtype)) {
                    switch ($val) {
                        case 'process':
                            $tocheck = $item->getProcessStatusArray();
                            break;

                        case 'notclosed':
                            $tocheck = $item->getAllStatusArray();
                            foreach ($item->getClosedStatusArray() as $status) {
                                if (isset($tocheck[$status])) {
                                    unset($tocheck[$status]);
                                }
                            }
                            $tocheck = array_keys($tocheck);
                            break;

                        case 'old':
                            $tocheck = array_merge(
                                $item->getSolvedStatusArray(),
                                $item->getClosedStatusArray()
                            );
                            break;

                        case 'notold':
                            $tocheck = $item::getNotSolvedStatusArray();
                            break;

                        case 'all':
                            $tocheck = array_keys($item->getAllStatusArray());
                            break;
                    }
                }

                if (count($tocheck) == 0) {
                    $statuses = $item->getAllStatusArray();
                    if (isset($statuses[$val])) {
                        $tocheck = [$val];
                    }
                }

                if (count($tocheck)) {
                    if ($nott) {
                        return $link . " `$table`.`$field` NOT IN ('" . implode("','", $tocheck) . "')";
                    }
                    return $link . " `$table`.`$field` IN ('" . implode("','", $tocheck) . "')";
                }
                break;

            case "glpi_tickets_tickets.tickets_id_1":
                $tmplink = 'OR';
                $compare = '=';
                if ($nott) {
                    $tmplink = 'AND';
                    $compare = '<>';
                }
                $toadd2 = '';
                if (
                    $nott
                    && ($val != 'NULL') && ($val != 'null')
                ) {
                    $toadd2 = " OR `$table`.`$field` IS NULL";
                }

                return $link . " (((`$table`.`tickets_id_1` $compare '$val'
                              $tmplink `$table`.`tickets_id_2` $compare '$val')
                             AND `glpi_tickets`.`id` <> '$val')
                            $toadd2)";

            case "glpi_tickets.priority":
            case "glpi_tickets.impact":
            case "glpi_tickets.urgency":
            case "glpi_problems.priority":
            case "glpi_problems.impact":
            case "glpi_problems.urgency":
            case "glpi_changes.priority":
            case "glpi_changes.impact":
            case "glpi_changes.urgency":
            case "glpi_projects.priority":
                if (is_numeric($val)) {
                    if ($val > 0) {
                        $compare = ($nott ? '<>' : '=');
                        return $link . " `$table`.`$field` $compare '$val'";
                    }
                    if ($val < 0) {
                        $compare = ($nott ? '<' : '>=');
                        return $link . " `$table`.`$field` $compare '" . abs($val) . "'";
                    }
                    // Show all
                    $compare = ($nott ? '<' : '>=');
                    return $link . " `$table`.`$field` $compare '0' ";
                }
                return "";

            case "glpi_tickets.global_validation":
            case "glpi_ticketvalidations.status":
            case "glpi_changes.global_validation":
            case "glpi_changevalidations.status":
                if ($val == 'all') {
                    return "";
                }
                $tocheck = [];
                switch ($val) {
                    case 'can':
                        $tocheck = \CommonITILValidation::getCanValidationStatusArray();
                        break;

                    case 'all':
                        $tocheck = \CommonITILValidation::getAllValidationStatusArray();
                        break;
                }
                if (count($tocheck) == 0) {
                    $tocheck = [$val];
                }
                if (count($tocheck)) {
                    if ($nott) {
                        return $link . " `$table`.`$field` NOT IN ('" . implode("','", $tocheck) . "')";
                    }
                    return $link . " `$table`.`$field` IN ('" . implode("','", $tocheck) . "')";
                }
                break;

            case "glpi_notifications.event":
                if (in_array($searchtype, ['equals', 'notequals']) && strpos($val, \Search::SHORTSEP)) {
                    $not = 'notequals' === $searchtype ? 'NOT' : '';
                    list($itemtype_val, $event_val) = explode(\Search::SHORTSEP, $val);
                    return " $link $not(`$table`.`event` = '$event_val'
                               AND `$table`.`itemtype` = '$itemtype_val')";
                }
                break;
        }

        //// Default cases

        // Link with plugin tables
        if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $inittable, $matches)) {
            if (count($matches) == 2) {
                $plug     = $matches[1];
                $out = \Plugin::doOneHook(
                    $plug,
                    'addWhere',
                    $link,
                    $nott,
                    $itemtype,
                    $ID,
                    $val,
                    $searchtype
                );
                if (!empty($out)) {
                    return $out;
                }
            }
        }

        $tocompute      = "`$table`.`$field`";
        $tocomputetrans = "`" . $table . "_trans_" . $field . "`.`value`";
        if (isset($searchopt[$ID]["computation"])) {
            $tocompute = $searchopt[$ID]["computation"];
            $tocompute = str_replace($DB->quoteName('TABLE'), 'TABLE', $tocompute);
            $tocompute = str_replace("TABLE", $DB->quoteName("$table"), $tocompute);
        }

        // Preformat items
        if (isset($searchopt[$ID]["datatype"])) {
            if ($searchopt[$ID]["datatype"] == "mio") {
                // Parse value as it may contain a few different formats
                $val = \Toolbox::getMioSizeFromString($val);
            }

            switch ($searchopt[$ID]["datatype"]) {
                case "itemtypename":
                    if (in_array($searchtype, ['equals', 'notequals'])) {
                        return " $link (`$table`.`$field`" . $SEARCH . ') ';
                    }
                    break;

                case "itemlink":
                    if (in_array($searchtype, ['equals', 'notequals', 'under', 'notunder', 'empty'])) {
                        return " $link (`$table`.`id`" . $SEARCH . ') ';
                    }
                    break;

                case "datetime":
                case "date":
                case "date_delay":
                    if ($searchopt[$ID]["datatype"] == 'datetime') {
                        // Specific search for datetime
                        if (in_array($searchtype, ['equals', 'notequals'])) {
                            $val = preg_replace("/:00$/", '', $val);
                            $val = '^' . $val;
                            if ($searchtype == 'notequals') {
                                $nott = !$nott;
                            }
                            return self::makeTextCriteria("`$table`.`$field`", $val, $nott, $link);
                        }
                    }
                    if ($searchtype == 'lessthan') {
                        $val = '<' . $val;
                    }
                    if ($searchtype == 'morethan') {
                        $val = '>' . $val;
                    }
                    if ($searchtype) {
                        $date_computation = $tocompute;
                    }
                    if (in_array($searchtype, ["contains", "notcontains"])) {
                        $default_charset = \DBConnection::getDefaultCharset();
                        $date_computation = "CONVERT($date_computation USING {$default_charset})";
                    }
                    $search_unit = ' MONTH ';
                    if (isset($searchopt[$ID]['searchunit'])) {
                        $search_unit = $searchopt[$ID]['searchunit'];
                    }
                    if ($searchopt[$ID]["datatype"] == "date_delay") {
                        $delay_unit = ' MONTH ';
                        if (isset($searchopt[$ID]['delayunit'])) {
                            $delay_unit = $searchopt[$ID]['delayunit'];
                        }
                        $add_minus = '';
                        if (isset($searchopt[$ID]["datafields"][3])) {
                            $add_minus = "-`$table`.`" . $searchopt[$ID]["datafields"][3] . "`";
                        }
                        $date_computation = "ADDDATE(`$table`." . $searchopt[$ID]["datafields"][1] . ",
                                               INTERVAL (`$table`." . $searchopt[$ID]["datafields"][2] . "
                                                         $add_minus)
                                               $delay_unit)";
                    }
                    if (in_array($searchtype, ['equals', 'notequals', 'empty'])) {
                        return " $link ($date_computation " . $SEARCH . ') ';
                    }
                    $search  = ["/\&lt;/","/\&gt;/"];
                    $replace = ["<",">"];
                    $val     = preg_replace($search, $replace, $val);
                    if (preg_match("/^\s*([<>=]+)(.*)/", $val, $regs)) {
                        if (is_numeric($regs[2])) {
                            return $link . " $date_computation " . $regs[1] . "
                            ADDDATE(NOW(), INTERVAL " . $regs[2] . " $search_unit) ";
                        }
                        // ELSE Reformat date if needed
                        $regs[2] = preg_replace(
                            '@(\d{1,2})(-|/)(\d{1,2})(-|/)(\d{4})@',
                            '\5-\3-\1',
                            $regs[2]
                        );
                        if (preg_match('/[0-9]{2,4}-[0-9]{1,2}-[0-9]{1,2}/', $regs[2])) {
                            $ret = $link;
                            if ($nott) {
                                $ret .= " NOT(";
                            }
                            $ret .= " $date_computation {$regs[1]} '{$regs[2]}'";
                            if ($nott) {
                                $ret .= ")";
                            }
                            return $ret;
                        }
                        return "";
                    }
                    // ELSE standard search
                    // Date format modification if needed
                    $val = preg_replace('@(\d{1,2})(-|/)(\d{1,2})(-|/)(\d{4})@', '\5-\3-\1', $val);
                    if ($date_computation) {
                        return self::makeTextCriteria($date_computation, $val, $nott, $link);
                    }
                    return '';

                case "right":
                    if ($searchtype == 'notequals') {
                        $nott = !$nott;
                    }
                    return $link . ($nott ? ' NOT' : '') . " ($tocompute & '$val') ";

                case "bool":
                    if (!is_numeric($val)) {
                        if (strcasecmp($val, __('No')) == 0) {
                            $val = 0;
                        } else if (strcasecmp($val, __('Yes')) == 0) {
                            $val = 1;
                        }
                    }
                // No break here : use number comparaison case

                case "count":
                case "mio":
                case "number":
                case "decimal":
                case "timestamp":
                case "progressbar":
                    $decimal_contains = $searchopt[$ID]["datatype"] === 'decimal' && $searchtype === 'contains';
                    $search  = ["/\&lt;/", "/\&gt;/"];
                    $replace = ["<", ">"];
                    $val     = preg_replace($search, $replace, $val);

                    if (preg_match("/([<>])([=]*)[[:space:]]*([0-9]+)/", $val, $regs)) {
                        if (in_array($searchtype, ["notequals", "notcontains"])) {
                            $nott = !$nott;
                        }
                        if ($nott) {
                            if ($regs[1] == '<') {
                                $regs[1] = '>';
                            } else {
                                $regs[1] = '<';
                            }
                        }
                        $regs[1] .= $regs[2];
                        return $link . " ($tocompute " . $regs[1] . " " . $regs[3] . ") ";
                    }

                    if (is_numeric($val) && !$decimal_contains) {
                        $numeric_val = (float) $val;

                        if (in_array($searchtype, ["notequals", "notcontains"])) {
                            $nott = !$nott;
                        }

                        if (isset($searchopt[$ID]["width"])) {
                            $ADD = "";
                            if (
                                $nott
                                && ($val != 'NULL') && ($val != 'null')
                            ) {
                                $ADD = " OR $tocompute IS NULL";
                            }
                            if ($nott) {
                                return $link . " ($tocompute < " . ($numeric_val - $searchopt[$ID]["width"]) . "
                                        OR $tocompute > " . ($numeric_val + $searchopt[$ID]["width"]) . "
                                        $ADD) ";
                            }
                            return $link . " (($tocompute >= " . ($numeric_val - $searchopt[$ID]["width"]) . "
                                      AND $tocompute <= " . ($numeric_val + $searchopt[$ID]["width"]) . ")
                                     $ADD) ";
                        }
                        if (!$nott) {
                            return " $link ($tocompute = $numeric_val) ";
                        }
                        return " $link ($tocompute <> $numeric_val) ";
                    }

                    if ($searchtype === 'empty') {
                        if ($nott) {
                            return $link . " ($tocompute " . $SEARCH . " AND $tocompute <> 0) ";
                        }
                        return $link . " ($tocompute " . $SEARCH . " OR $tocompute = 0) ";
                    }
                    break;

                case 'text':
                    if ($searchtype === 'empty') {
                        if ($nott) {
                            return $link . " ($tocompute " . $SEARCH . " AND $tocompute <> '') ";
                        }
                        return $link . " ($tocompute " . $SEARCH . " OR $tocompute = '') ";
                    }
                    break;
            }
        }

        // Default case
        if (in_array($searchtype, ['equals', 'notequals','under', 'notunder'])) {
            if (
                (!isset($searchopt[$ID]['searchequalsonfield'])
                    || !$searchopt[$ID]['searchequalsonfield'])
                && ($itemtype == \AllAssets::getType()
                    || $table != $itemtype::getTable())
            ) {
                $out = " $link (`$table`.`id`" . $SEARCH;
            } else {
                $out = " $link (`$table`.`$field`" . $SEARCH;
            }
            if ($searchtype == 'notequals') {
                $nott = !$nott;
            }
            // Add NULL if $val = 0 and not negative search
            // Or negative search on real value
            if (
                (!$nott && ($val == 0))
                || ($nott && ($val != 0))
            ) {
                $out .= " OR `$table`.`id` IS NULL";
            }
            $out .= ')';
            return $out;
        }
        $transitemtype = getItemTypeForTable($inittable);
        if (Session::haveTranslations($transitemtype, $field)) {
            return " $link (" . self::makeTextCriteria($tocompute, $val, $nott, '') . "
                          OR " . self::makeTextCriteria($tocomputetrans, $val, $nott, '') . ")";
        }

        return self::makeTextCriteria($tocompute, $val, $nott, $link);
    }


    /**
     * Generic Function to add Default left join to a request
     *
     * @param string $itemtype             Reference item type
     * @param string $ref_table            Reference table
     * @param array &$already_link_tables  Array of tables already joined
     *
     * @return string Left join string
     **/
    public static function addDefaultJoin($itemtype, $ref_table, array &$already_link_tables)
    {
        $out = '';

        switch ($itemtype) {
            // No link
            case 'User':
                $out = self::addLeftJoin(
                    $itemtype,
                    $ref_table,
                    $already_link_tables,
                    "glpi_profiles_users",
                    "profiles_users_id",
                    0,
                    0,
                    ['jointype' => 'child']
                );
                break;

            case 'Reservation':
                $out .= self::addLeftJoin(
                    $itemtype,
                    $ref_table,
                    $already_link_tables,
                    \ReservationItem::getTable(),
                    \ReservationItem::getForeignKeyField(),
                );
                break;

            case 'Reminder':
                $out = \Reminder::addVisibilityJoins();
                break;

            case 'RSSFeed':
                $out = \RSSFeed::addVisibilityJoins();
                break;

            case 'ProjectTask':
                // Same structure in addDefaultWhere
                $out .= self::addLeftJoin(
                    $itemtype,
                    $ref_table,
                    $already_link_tables,
                    "glpi_projects",
                    "projects_id"
                );
                $out .= self::addLeftJoin(
                    $itemtype,
                    $ref_table,
                    $already_link_tables,
                    "glpi_projecttaskteams",
                    "projecttaskteams_id",
                    0,
                    0,
                    ['jointype' => 'child']
                );
                break;

            case 'Project':
                // Same structure in addDefaultWhere
                if (!Session::haveRight("project", \Project::READALL)) {
                    $out .= self::addLeftJoin(
                        $itemtype,
                        $ref_table,
                        $already_link_tables,
                        "glpi_projectteams",
                        "projectteams_id",
                        0,
                        0,
                        ['jointype' => 'child']
                    );
                }
                break;

            case 'Ticket':
                // Same structure in addDefaultWhere
                if (!Session::haveRight("ticket", \Ticket::READALL)) {
                    $searchopt = &SearchOption::getOptionsForItemtype($itemtype);

                    // show mine : requester
                    $out .= self::addLeftJoin(
                        $itemtype,
                        $ref_table,
                        $already_link_tables,
                        "glpi_tickets_users",
                        "tickets_users_id",
                        0,
                        0,
                        $searchopt[4]['joinparams']['beforejoin']['joinparams']
                    );

                    if (Session::haveRight("ticket", \Ticket::READGROUP)) {
                        if (count($_SESSION['glpigroups'])) {
                            $out .= self::addLeftJoin(
                                $itemtype,
                                $ref_table,
                                $already_link_tables,
                                "glpi_groups_tickets",
                                "groups_tickets_id",
                                0,
                                0,
                                $searchopt[71]['joinparams']['beforejoin']
                                ['joinparams']
                            );
                        }
                    }

                    // show mine : observer
                    $out .= self::addLeftJoin(
                        $itemtype,
                        $ref_table,
                        $already_link_tables,
                        "glpi_tickets_users",
                        "tickets_users_id",
                        0,
                        0,
                        $searchopt[66]['joinparams']['beforejoin']['joinparams']
                    );

                    if (count($_SESSION['glpigroups'])) {
                        $out .= self::addLeftJoin(
                            $itemtype,
                            $ref_table,
                            $already_link_tables,
                            "glpi_groups_tickets",
                            "groups_tickets_id",
                            0,
                            0,
                            $searchopt[65]['joinparams']['beforejoin']['joinparams']
                        );
                    }

                    if (Session::haveRight("ticket", \Ticket::OWN)) { // Can own ticket : show assign to me
                        $out .= self::addLeftJoin(
                            $itemtype,
                            $ref_table,
                            $already_link_tables,
                            "glpi_tickets_users",
                            "tickets_users_id",
                            0,
                            0,
                            $searchopt[5]['joinparams']['beforejoin']['joinparams']
                        );
                    }

                    if (Session::haveRightsOr("ticket", [\Ticket::READMY, \Ticket::READASSIGN])) { // show mine + assign to me
                        $out .= self::addLeftJoin(
                            $itemtype,
                            $ref_table,
                            $already_link_tables,
                            "glpi_tickets_users",
                            "tickets_users_id",
                            0,
                            0,
                            $searchopt[5]['joinparams']['beforejoin']['joinparams']
                        );

                        if (count($_SESSION['glpigroups'])) {
                            $out .= self::addLeftJoin(
                                $itemtype,
                                $ref_table,
                                $already_link_tables,
                                "glpi_groups_tickets",
                                "groups_tickets_id",
                                0,
                                0,
                                $searchopt[8]['joinparams']['beforejoin']
                                ['joinparams']
                            );
                        }
                    }

                    if (
                        Session::haveRightsOr(
                            'ticketvalidation',
                            [\TicketValidation::VALIDATEINCIDENT,
                                \TicketValidation::VALIDATEREQUEST
                            ]
                        )
                    ) {
                        $out .= self::addLeftJoin(
                            $itemtype,
                            $ref_table,
                            $already_link_tables,
                            "glpi_ticketvalidations",
                            "ticketvalidations_id",
                            0,
                            0,
                            $searchopt[58]['joinparams']['beforejoin']['joinparams']
                        );
                    }
                }
                break;

            case 'Change':
            case 'Problem':
                if ($itemtype == 'Change') {
                    $right       = 'change';
                    $table       = 'changes';
                    $groupetable = "glpi_changes_groups";
                    $linkfield   = "changes_groups_id";
                } else if ($itemtype == 'Problem') {
                    $right       = 'problem';
                    $table       = 'problems';
                    $groupetable = "glpi_groups_problems";
                    $linkfield   = "groups_problems_id";
                }

                // Same structure in addDefaultWhere
                $out = '';
                if (!Session::haveRight("$right", $itemtype::READALL)) {
                    $searchopt = &SearchOption::getOptionsForItemtype($itemtype);

                    if (Session::haveRight("$right", $itemtype::READMY)) {
                        // show mine : requester
                        $out .= self::addLeftJoin(
                            $itemtype,
                            $ref_table,
                            $already_link_tables,
                            "glpi_" . $table . "_users",
                            $table . "_users_id",
                            0,
                            0,
                            $searchopt[4]['joinparams']['beforejoin']['joinparams']
                        );
                        if (count($_SESSION['glpigroups'])) {
                            $out .= self::addLeftJoin(
                                $itemtype,
                                $ref_table,
                                $already_link_tables,
                                $groupetable,
                                $linkfield,
                                0,
                                0,
                                $searchopt[71]['joinparams']['beforejoin']['joinparams']
                            );
                        }

                        // show mine : observer
                        $out .= self::addLeftJoin(
                            $itemtype,
                            $ref_table,
                            $already_link_tables,
                            "glpi_" . $table . "_users",
                            $table . "_users_id",
                            0,
                            0,
                            $searchopt[66]['joinparams']['beforejoin']['joinparams']
                        );
                        if (count($_SESSION['glpigroups'])) {
                            $out .= self::addLeftJoin(
                                $itemtype,
                                $ref_table,
                                $already_link_tables,
                                $groupetable,
                                $linkfield,
                                0,
                                0,
                                $searchopt[65]['joinparams']['beforejoin']['joinparams']
                            );
                        }

                        // show mine : assign
                        $out .= self::addLeftJoin(
                            $itemtype,
                            $ref_table,
                            $already_link_tables,
                            "glpi_" . $table . "_users",
                            $table . "_users_id",
                            0,
                            0,
                            $searchopt[5]['joinparams']['beforejoin']['joinparams']
                        );
                        if (count($_SESSION['glpigroups'])) {
                            $out .= self::addLeftJoin(
                                $itemtype,
                                $ref_table,
                                $already_link_tables,
                                $groupetable,
                                $linkfield,
                                0,
                                0,
                                $searchopt[8]['joinparams']['beforejoin']['joinparams']
                            );
                        }
                    }
                }
                break;

            default:
                // Plugin can override core definition for its type
                if ($plug = isPluginItemType($itemtype)) {
                    $plugin_name   = $plug['plugin'];
                    $hook_function = 'plugin_' . strtolower($plugin_name) . '_addDefaultJoin';
                    $hook_closure  = function () use ($hook_function, $itemtype, $ref_table, &$already_link_tables) {
                        if (is_callable($hook_function)) {
                            return $hook_function($itemtype, $ref_table, $already_link_tables);
                        }
                    };
                    $out = \Plugin::doOneHook($plugin_name, $hook_closure);
                }
                break;
        }

        [$itemtype, $out] = \Plugin::doHookFunction('add_default_join', [$itemtype, $out]);
        return $out;
    }


    /**
     * Generic Function to add left join to a request
     *
     * @param string  $itemtype             Item type
     * @param string  $ref_table            Reference table
     * @param array   $already_link_tables  Array of tables already joined
     * @param string  $new_table            New table to join
     * @param string  $linkfield            Linkfield for LeftJoin
     * @param boolean $meta                 Is it a meta item ? (default 0)
     * @param integer $meta_type            Meta type table (default 0)
     * @param array   $joinparams           Array join parameters (condition / joinbefore...)
     * @param string  $field                Field to display (needed for translation join) (default '')
     *
     * @return string Left join string
     **/
    public static function addLeftJoin(
        $itemtype,
        $ref_table,
        array &$already_link_tables,
        $new_table,
        $linkfield,
        $meta = 0,
        $meta_type = 0,
        $joinparams = [],
        $field = ''
    ) {

        // Rename table for meta left join
        $AS = "";
        $nt = $new_table;
        $cleannt    = $nt;

        // Virtual field no link
        if (\Search::isVirtualField($linkfield)) {
            return '';
        }

        $complexjoin = \Search::computeComplexJoinID($joinparams);

        $is_fkey_composite_on_self = getTableNameForForeignKeyField($linkfield) == $ref_table
            && $linkfield != getForeignKeyFieldForTable($ref_table);

        // Auto link
        if (
            ($ref_table == $new_table)
            && empty($complexjoin)
            && !$is_fkey_composite_on_self
        ) {
            $transitemtype = getItemTypeForTable($new_table);
            if (Session::haveTranslations($transitemtype, $field)) {
                $transAS            = $nt . '_trans_' . $field;
                return self::joinDropdownTranslations(
                    $transAS,
                    $nt,
                    $transitemtype,
                    $field
                );
            }
            return "";
        }

        // Multiple link possibilies case
        if (!empty($linkfield) && ($linkfield != getForeignKeyFieldForTable($new_table))) {
            $nt .= "_" . $linkfield;
            $AS  = " AS `$nt`";
        }

        if (!empty($complexjoin)) {
            $nt .= "_" . $complexjoin;
            $AS  = " AS `$nt`";
        }

        $addmetanum = "";
        $rt         = $ref_table;
        $cleanrt    = $rt;
        if ($meta && $meta_type::getTable() != $new_table) {
            $addmetanum = "_" . $meta_type;
            $AS         = " AS `$nt$addmetanum`";
            $nt         = $nt . $addmetanum;
        }

        // Do not take into account standard linkfield
        $tocheck = $nt . "." . $linkfield;
        if ($linkfield == getForeignKeyFieldForTable($new_table)) {
            $tocheck = $nt;
        }

        if (in_array($tocheck, $already_link_tables)) {
            return "";
        }
        array_push($already_link_tables, $tocheck);

        $specific_leftjoin = '';

        // Plugin can override core definition for its type
        if ($plug = isPluginItemType($itemtype)) {
            $plugin_name   = $plug['plugin'];
            $hook_function = 'plugin_' . strtolower($plugin_name) . '_addLeftJoin';
            $hook_closure  = function () use ($hook_function, $itemtype, $ref_table, $new_table, $linkfield, &$already_link_tables) {
                if (is_callable($hook_function)) {
                    return $hook_function($itemtype, $ref_table, $new_table, $linkfield, $already_link_tables);
                }
            };
            $specific_leftjoin = \Plugin::doOneHook($plugin_name, $hook_closure);
        }

        // Link with plugin tables : need to know left join structure
        if (
            empty($specific_leftjoin)
            && preg_match("/^glpi_plugin_([a-z0-9]+)/", $new_table, $matches)
        ) {
            if (count($matches) == 2) {
                $plugin_name   = $matches[1];
                $hook_function = 'plugin_' . strtolower($plugin_name) . '_addLeftJoin';
                $hook_closure  = function () use ($hook_function, $itemtype, $ref_table, $new_table, $linkfield, &$already_link_tables) {
                    if (is_callable($hook_function)) {
                        return $hook_function($itemtype, $ref_table, $new_table, $linkfield, $already_link_tables);
                    }
                };
                $specific_leftjoin = \Plugin::doOneHook($plugin_name, $hook_closure);
            }
        }
        if (!empty($linkfield)) {
            $before = '';

            if (isset($joinparams['beforejoin']) && is_array($joinparams['beforejoin'])) {
                if (isset($joinparams['beforejoin']['table'])) {
                    $joinparams['beforejoin'] = [$joinparams['beforejoin']];
                }

                foreach ($joinparams['beforejoin'] as $tab) {
                    if (isset($tab['table'])) {
                        $intertable = $tab['table'];
                        if (isset($tab['linkfield'])) {
                            $interlinkfield = $tab['linkfield'];
                        } else {
                            $interlinkfield = getForeignKeyFieldForTable($intertable);
                        }

                        $interjoinparams = [];
                        if (isset($tab['joinparams'])) {
                            $interjoinparams = $tab['joinparams'];
                        }
                        $before .= self::addLeftJoin(
                            $itemtype,
                            $rt,
                            $already_link_tables,
                            $intertable,
                            $interlinkfield,
                            $meta,
                            $meta_type,
                            $interjoinparams
                        );
                    }

                    // No direct link with the previous joins
                    if (!isset($tab['joinparams']['nolink']) || !$tab['joinparams']['nolink']) {
                        $cleanrt     = $intertable;
                        $complexjoin = self::computeComplexJoinID($interjoinparams);
                        if (!empty($interlinkfield) && ($interlinkfield != getForeignKeyFieldForTable($intertable))) {
                            $intertable .= "_" . $interlinkfield;
                        }
                        if (!empty($complexjoin)) {
                            $intertable .= "_" . $complexjoin;
                        }
                        if ($meta && $meta_type::getTable() != $cleanrt) {
                            $intertable .= "_" . $meta_type;
                        }
                        $rt = $intertable;
                    }
                }
            }

            $addcondition = '';
            if (isset($joinparams['condition'])) {
                $condition = $joinparams['condition'];
                if (is_array($condition)) {
                    $it = new \DBmysqlIterator(null);
                    $condition = ' AND ' . $it->analyseCrit($condition);
                }
                $from         = ["`REFTABLE`", "REFTABLE", "`NEWTABLE`", "NEWTABLE"];
                $to           = ["`$rt`", "`$rt`", "`$nt`", "`$nt`"];
                $addcondition = str_replace($from, $to, $condition);
                $addcondition = $addcondition . " ";
            }

            if (!isset($joinparams['jointype'])) {
                $joinparams['jointype'] = 'standard';
            }

            if (empty($specific_leftjoin)) {
                switch ($new_table) {
                    // No link
                    case "glpi_auth_tables":
                        $user_searchopt     = SearchOption::getOptionsForItemtype('User');

                        $specific_leftjoin  = self::addLeftJoin(
                            $itemtype,
                            $rt,
                            $already_link_tables,
                            "glpi_authldaps",
                            'auths_id',
                            0,
                            0,
                            $user_searchopt[30]['joinparams']
                        );
                        $specific_leftjoin .= self::addLeftJoin(
                            $itemtype,
                            $rt,
                            $already_link_tables,
                            "glpi_authmails",
                            'auths_id',
                            0,
                            0,
                            $user_searchopt[31]['joinparams']
                        );
                        break;
                }
            }

            if (empty($specific_leftjoin)) {
                switch ($joinparams['jointype']) {
                    case 'child':
                        $linkfield = getForeignKeyFieldForTable($cleanrt);
                        if (isset($joinparams['linkfield'])) {
                            $linkfield = $joinparams['linkfield'];
                        }

                        // Child join
                        $specific_leftjoin = " LEFT JOIN `$new_table` $AS
                                             ON (`$rt`.`id` = `$nt`.`$linkfield`
                                                 $addcondition)";
                        break;

                    case 'item_item':
                        // Item_Item join
                        $specific_leftjoin = " LEFT JOIN `$new_table` $AS
                                          ON ((`$rt`.`id`
                                                = `$nt`.`" . getForeignKeyFieldForTable($cleanrt) . "_1`
                                               OR `$rt`.`id`
                                                 = `$nt`.`" . getForeignKeyFieldForTable($cleanrt) . "_2`)
                                              $addcondition)";
                        break;

                    case 'item_item_revert':
                        // Item_Item join reverting previous item_item
                        $specific_leftjoin = " LEFT JOIN `$new_table` $AS
                                          ON ((`$nt`.`id`
                                                = `$rt`.`" . getForeignKeyFieldForTable($cleannt) . "_1`
                                               OR `$nt`.`id`
                                                 = `$rt`.`" . getForeignKeyFieldForTable($cleannt) . "_2`)
                                              $addcondition)";
                        break;

                    case "mainitemtype_mainitem":
                        $addmain = 'main';
                    //addmain defined to be used in itemtype_item case

                    case "itemtype_item":
                        if (!isset($addmain)) {
                            $addmain = '';
                        }
                        $used_itemtype = $itemtype;
                        if (
                            isset($joinparams['specific_itemtype'])
                            && !empty($joinparams['specific_itemtype'])
                        ) {
                            $used_itemtype = $joinparams['specific_itemtype'];
                        }
                        // Itemtype join
                        $specific_leftjoin = " LEFT JOIN `$new_table` $AS
                                          ON (`$rt`.`id` = `$nt`.`" . $addmain . "items_id`
                                              AND `$nt`.`" . $addmain . "itemtype` = '$used_itemtype'
                                              $addcondition) ";
                        break;

                    case "itemtype_item_revert":
                        if (!isset($addmain)) {
                            $addmain = '';
                        }
                        $used_itemtype = $itemtype;
                        if (
                            isset($joinparams['specific_itemtype'])
                            && !empty($joinparams['specific_itemtype'])
                        ) {
                            $used_itemtype = $joinparams['specific_itemtype'];
                        }
                        // Itemtype join
                        $specific_leftjoin = " LEFT JOIN `$new_table` $AS
                                          ON (`$nt`.`id` = `$rt`.`" . $addmain . "items_id`
                                              AND `$rt`.`" . $addmain . "itemtype` = '$used_itemtype'
                                              $addcondition) ";
                        break;

                    case "itemtypeonly":
                        $used_itemtype = $itemtype;
                        if (
                            isset($joinparams['specific_itemtype'])
                            && !empty($joinparams['specific_itemtype'])
                        ) {
                            $used_itemtype = $joinparams['specific_itemtype'];
                        }
                        // Itemtype join
                        $specific_leftjoin = " LEFT JOIN `$new_table` $AS
                                          ON (`$nt`.`itemtype` = '$used_itemtype'
                                              $addcondition) ";
                        break;

                    default:
                        // Standard join
                        $specific_leftjoin = "LEFT JOIN `$new_table` $AS
                                          ON (`$rt`.`$linkfield` = `$nt`.`id`
                                              $addcondition)";
                        $transitemtype = getItemTypeForTable($new_table);
                        if (Session::haveTranslations($transitemtype, $field)) {
                            $transAS            = $nt . '_trans_' . $field;
                            $specific_leftjoin .= self::joinDropdownTranslations(
                                $transAS,
                                $nt,
                                $transitemtype,
                                $field
                            );
                        }
                        break;
                }
            }
            return $before . $specific_leftjoin;
        }

        return '';
    }


    /**
     * Generic Function to add left join for meta items
     *
     * @param string $from_type             Reference item type ID
     * @param string $to_type               Item type to add
     * @param array  $already_link_tables2  Array of tables already joined
     *showGenericSearch
     * @return string Meta Left join string
     **/
    public static function addMetaLeftJoin(
        $from_type,
        $to_type,
        array &$already_link_tables2,
        $joinparams = []
    ) {
        global $CFG_GLPI;

        $from_referencetype = SearchEngine::getMetaReferenceItemtype($from_type);

        $LINK = " LEFT JOIN ";

        $from_table = $from_type::getTable();
        $from_fk    = getForeignKeyFieldForTable($from_table);
        $to_table   = $to_type::getTable();
        $to_fk      = getForeignKeyFieldForTable($to_table);

        $to_obj        = getItemForItemtype($to_type);
        $to_entity_restrict = $to_obj->isField('entities_id') ? getEntitiesRestrictRequest('AND', $to_table) : '';

        $complexjoin = \Search::computeComplexJoinID($joinparams);
        $alias_suffix = ($complexjoin != '' ? '_' . $complexjoin : '') . '_' . $to_type;

        $JOIN = "";

        // Specific JOIN
        if ($from_referencetype === 'Software' && in_array($to_type, $CFG_GLPI['software_types'])) {
            // From Software to software_types
            $softwareversions_table = "glpi_softwareversions{$alias_suffix}";
            if (!in_array($softwareversions_table, $already_link_tables2)) {
                array_push($already_link_tables2, $softwareversions_table);
                $JOIN .= "$LINK `glpi_softwareversions` AS `$softwareversions_table`
                         ON (`$softwareversions_table`.`softwares_id` = `$from_table`.`id`) ";
            }
            $items_softwareversions_table = "glpi_items_softwareversions_{$alias_suffix}";
            if (!in_array($items_softwareversions_table, $already_link_tables2)) {
                array_push($already_link_tables2, $items_softwareversions_table);
                $JOIN .= "$LINK `glpi_items_softwareversions` AS `$items_softwareversions_table`
                         ON (`$items_softwareversions_table`.`softwareversions_id` = `$softwareversions_table`.`id`
                             AND `$items_softwareversions_table`.`itemtype` = '$to_type'
                             AND `$items_softwareversions_table`.`is_deleted` = 0) ";
            }
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$items_softwareversions_table`.`items_id` = `$to_table`.`id`
                             AND `$items_softwareversions_table`.`itemtype` = '$to_type'
                             $to_entity_restrict) ";
            }
            return $JOIN;
        }

        if ($to_type === 'Software' && in_array($from_referencetype, $CFG_GLPI['software_types'])) {
            // From software_types to Software
            $items_softwareversions_table = "glpi_items_softwareversions{$alias_suffix}";
            if (!in_array($items_softwareversions_table, $already_link_tables2)) {
                array_push($already_link_tables2, $items_softwareversions_table);
                $JOIN .= "$LINK `glpi_items_softwareversions` AS `$items_softwareversions_table`
                         ON (`$items_softwareversions_table`.`items_id` = `$from_table`.`id`
                             AND `$items_softwareversions_table`.`itemtype` = '$from_type'
                             AND `$items_softwareversions_table`.`is_deleted` = 0) ";
            }
            $softwareversions_table = "glpi_softwareversions{$alias_suffix}";
            if (!in_array($softwareversions_table, $already_link_tables2)) {
                array_push($already_link_tables2, $softwareversions_table);
                $JOIN .= "$LINK `glpi_softwareversions` AS `$softwareversions_table`
                         ON (`$items_softwareversions_table`.`softwareversions_id` = `$softwareversions_table`.`id`) ";
            }
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$softwareversions_table`.`softwares_id` = `$to_table`.`id`) ";
            }
            $softwarelicenses_table = "glpi_softwarelicenses{$alias_suffix}";
            if (!in_array($softwarelicenses_table, $already_link_tables2)) {
                array_push($already_link_tables2, $softwarelicenses_table);
                $JOIN .= "$LINK `glpi_softwarelicenses` AS `$softwarelicenses_table`
                        ON ($to_table.`id` = `$softwarelicenses_table`.`softwares_id`"
                    . getEntitiesRestrictRequest(' AND', $softwarelicenses_table, '', '', true) . ") ";
            }
            return $JOIN;
        }

        if ($from_referencetype === 'Budget' && in_array($to_type, $CFG_GLPI['infocom_types'])) {
            // From Budget to infocom_types
            $infocom_alias = "glpi_infocoms{$alias_suffix}";
            if (!in_array($infocom_alias, $already_link_tables2)) {
                array_push($already_link_tables2, $infocom_alias);
                $JOIN .= "$LINK `glpi_infocoms` AS `$infocom_alias`
                         ON (`$from_table`.`id` = `$infocom_alias`.`budgets_id`) ";
            }
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$to_table`.`id` = `$infocom_alias`.`items_id`
                             AND `$infocom_alias`.`itemtype` = '$to_type'
                             $to_entity_restrict) ";
            }
            return $JOIN;
        }

        if ($to_type === 'Budget' && in_array($from_referencetype, $CFG_GLPI['infocom_types'])) {
            // From infocom_types to Budget
            $infocom_alias = "glpi_infocoms{$alias_suffix}";
            if (!in_array($infocom_alias, $already_link_tables2)) {
                array_push($already_link_tables2, $infocom_alias);
                $JOIN .= "$LINK `glpi_infocoms` AS `$infocom_alias`
                         ON (`$from_table`.`id` = `$infocom_alias`.`items_id`
                             AND `$infocom_alias`.`itemtype` = '$from_type') ";
            }
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$infocom_alias`.`$to_fk` = `$to_table`.`id`
                             $to_entity_restrict) ";
            }
            return $JOIN;
        }

        if ($from_referencetype === 'Reservation' && in_array($to_type, $CFG_GLPI['reservation_types'])) {
            // From Reservation to reservation_types
            $reservationitems_alias = "glpi_reservationitems{$alias_suffix}";
            if (!in_array($reservationitems_alias, $already_link_tables2)) {
                array_push($already_link_tables2, $reservationitems_alias);
                $JOIN .= "$LINK `glpi_reservationitems` AS `$reservationitems_alias`
                         ON (`$from_table`.`reservationitems_id` = `$reservationitems_alias`.`id`) ";
            }
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$to_table`.`id` = `$reservationitems_alias`.`items_id`
                             AND `$reservationitems_alias`.`itemtype` = '$to_type'
                             $to_entity_restrict) ";
            }
            return $JOIN;
        }

        if ($to_type === 'Reservation' && in_array($from_referencetype, $CFG_GLPI['reservation_types'])) {
            // From reservation_types to Reservation
            $reservationitems_alias = "glpi_reservationitems{$alias_suffix}";
            if (!in_array($reservationitems_alias, $already_link_tables2)) {
                array_push($already_link_tables2, $reservationitems_alias);
                $JOIN .= "$LINK `glpi_reservationitems` AS `$reservationitems_alias`
                         ON (`$from_table`.`id` = `$reservationitems_alias`.`items_id`
                             AND `$reservationitems_alias`.`itemtype` = '$from_type') ";
            }
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$reservationitems_alias`.`id` = `$to_table`.`reservationitems_id`
                             $to_entity_restrict) ";
            }
            return $JOIN;
        }

        // Generic JOIN
        $from_obj      = getItemForItemtype($from_referencetype);
        $from_item_obj = null;
        $to_obj        = getItemForItemtype($to_type);
        $to_item_obj   = null;
        if (SearchEngine::isPossibleMetaSubitemOf($from_referencetype, $to_type)) {
            $from_item_obj = getItemForItemtype($from_referencetype . '_Item');
            if (!$from_item_obj) {
                $from_item_obj = getItemForItemtype('Item_' . $from_referencetype);
            }
        }
        if (SearchEngine::isPossibleMetaSubitemOf($to_type, $from_referencetype)) {
            $to_item_obj   = getItemForItemtype($to_type . '_Item');
            if (!$to_item_obj) {
                $to_item_obj = getItemForItemtype('Item_' . $to_type);
            }
        }

        if ($from_obj && $from_obj->isField($to_fk)) {
            // $from_table has a foreign key corresponding to $to_table
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$from_table`.`$to_fk` = `$to_table`.`id`
                             $to_entity_restrict) ";
            }
        } else if ($to_obj && $to_obj->isField($from_fk)) {
            // $to_table has a foreign key corresponding to $from_table
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$from_table`.`id` = `$to_table`.`$from_fk`
                             $to_entity_restrict) ";
            }
        } else if ($from_obj && $from_obj->isField('itemtype') && $from_obj->isField('items_id')) {
            // $from_table has items_id/itemtype fields
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$from_table`.`items_id` = `$to_table`.`id`
                             AND `$from_table`.`itemtype` = '$to_type'
                             $to_entity_restrict) ";
            }
        } else if ($to_obj && $to_obj->isField('itemtype') && $to_obj->isField('items_id')) {
            // $to_table has items_id/itemtype fields
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$from_table`.`id` = `$to_table`.`items_id`
                             AND `$to_table`.`itemtype` = '$from_type'
                             $to_entity_restrict) ";
            }
        } else if ($from_item_obj && $from_item_obj->isField($from_fk)) {
            // glpi_$from_items table exists and has a foreign key corresponding to $to_table
            $items_table = $from_item_obj::getTable();
            $items_table_alias = $items_table . $alias_suffix;
            if (!in_array($items_table_alias, $already_link_tables2)) {
                array_push($already_link_tables2, $items_table_alias);
                $deleted = $from_item_obj->isField('is_deleted') ? "AND `$items_table_alias`.`is_deleted` = 0" : "";
                $JOIN .= "$LINK `$items_table` AS `$items_table_alias`
                         ON (`$items_table_alias`.`$from_fk` = `$from_table`.`id`
                             AND `$items_table_alias`.`itemtype` = '$to_type'
                             $deleted)";
            }
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$items_table_alias`.`items_id` = `$to_table`.`id`
                             $to_entity_restrict) ";
            }
        } else if ($to_item_obj && $to_item_obj->isField($to_fk)) {
            // glpi_$to_items table exists and has a foreign key corresponding to $from_table
            $items_table = $to_item_obj::getTable();
            $items_table_alias = $items_table . $alias_suffix;
            if (!in_array($items_table_alias, $already_link_tables2)) {
                array_push($already_link_tables2, $items_table_alias);
                $deleted = $to_item_obj->isField('is_deleted') ? "AND `$items_table_alias`.`is_deleted` = 0" : "";
                $JOIN .= "$LINK `$items_table` AS `$items_table_alias`
                         ON (`$items_table_alias`.`items_id` = `$from_table`.`id`
                             AND `$items_table_alias`.`itemtype` = '$from_type'
                             $deleted)";
            }
            if (!in_array($to_table, $already_link_tables2)) {
                array_push($already_link_tables2, $to_table);
                $JOIN .= "$LINK `$to_table`
                         ON (`$items_table_alias`.`$to_fk` = `$to_table`.`id`
                             $to_entity_restrict) ";
            }
        }

        return $JOIN;
    }

    /**
     * @param array $joinparams
     * @return string
     */
    public static function computeComplexJoinID(array $joinparams)
    {
        $complexjoin = '';

        if (isset($joinparams['condition'])) {
            if (!is_array($joinparams['condition'])) {
                $complexjoin .= $joinparams['condition'];
            } else {
                global $DB;
                $dbi = new \DBmysqlIterator($DB);
                $sql_clause = $dbi->analyseCrit($joinparams['condition']);
                $complexjoin .= ' AND ' . $sql_clause; //TODO: and should came from conf
            }
        }

        // For jointype == child
        if (
            isset($joinparams['jointype']) && ($joinparams['jointype'] == 'child')
            && isset($joinparams['linkfield'])
        ) {
            $complexjoin .= $joinparams['linkfield'];
        }

        if (isset($joinparams['beforejoin'])) {
            if (isset($joinparams['beforejoin']['table'])) {
                $joinparams['beforejoin'] = [$joinparams['beforejoin']];
            }
            foreach ($joinparams['beforejoin'] as $tab) {
                if (isset($tab['table'])) {
                    $complexjoin .= $tab['table'];
                }
                if (isset($tab['joinparams']) && isset($tab['joinparams']['condition'])) {
                    if (!is_array($tab['joinparams']['condition'])) {
                        $complexjoin .= $tab['joinparams']['condition'];
                    } else {
                        global $DB;
                        $dbi = new \DBmysqlIterator($DB);
                        $sql_clause = $dbi->analyseCrit($tab['joinparams']['condition']);
                        $complexjoin .= ' AND ' . $sql_clause; //TODO: and should came from conf
                    }
                }
            }
        }

        if (!empty($complexjoin)) {
            $complexjoin = md5($complexjoin);
        }
        return $complexjoin;
    }

    /**
     * Add join for dropdown translations
     *
     * @param string $alias    Alias for translation table
     * @param string $table    Table to join on
     * @param string $itemtype Item type
     * @param string $field    Field name
     *
     * @return string
     */
    public static function joinDropdownTranslations($alias, $table, $itemtype, $field): string
    {
        return "LEFT JOIN `glpi_dropdowntranslations` AS `$alias`
                  ON (`$alias`.`itemtype` = '$itemtype'
                        AND `$alias`.`items_id` = `$table`.`id`
                        AND `$alias`.`language` = '" .
            $_SESSION['glpilanguage'] . "'
                        AND `$alias`.`field` = '$field')";
    }

    /**
     * Generic Function to add GROUP BY to a request
     *
     * @param string  $LINK           link to use
     * @param string  $NOT            is is a negative search ?
     * @param string  $itemtype       item type
     * @param integer $ID             ID of the item to search
     * @param string  $searchtype     search type ('contains' or 'equals')
     * @param string  $val            value search
     *
     * @return string HAVING string
     **/
    public static function addHaving($LINK, $NOT, $itemtype, $ID, $searchtype, $val)
    {

        global $DB;

        $searchopt  = &SearchOption::getOptionsForItemtype($itemtype);
        if (!isset($searchopt[$ID]['table'])) {
            return false;
        }
        $table = $searchopt[$ID]["table"];
        $NAME = "ITEM_{$itemtype}_{$ID}";

        // Plugin can override core definition for its type
        if ($plug = isPluginItemType($itemtype)) {
            $out = \Plugin::doOneHook(
                $plug['plugin'],
                'addHaving',
                $LINK,
                $NOT,
                $itemtype,
                $ID,
                $val,
                "{$itemtype}_{$ID}"
            );
            if (!empty($out)) {
                return $out;
            }
        }

        //// Default cases
        // Link with plugin tables
        if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table, $matches)) {
            if (count($matches) == 2) {
                $plug     = $matches[1];
                $out = \Plugin::doOneHook(
                    $plug,
                    'addHaving',
                    $LINK,
                    $NOT,
                    $itemtype,
                    $ID,
                    $val,
                    "{$itemtype}_{$ID}"
                );
                if (!empty($out)) {
                    return $out;
                }
            }
        }

        if (in_array($searchtype, ["notequals", "notcontains"])) {
            $NOT = !$NOT;
        }

        // Preformat items
        if (isset($searchopt[$ID]["datatype"])) {
            if ($searchopt[$ID]["datatype"] == "mio") {
                // Parse value as it may contain a few different formats
                $val = \Toolbox::getMioSizeFromString($val);
            }

            switch ($searchopt[$ID]["datatype"]) {
                case "datetime":
                    if (in_array($searchtype, ['contains', 'notcontains'])) {
                        break;
                    }

                    $force_day = false;
                    if (strstr($val, 'BEGIN') || strstr($val, 'LAST')) {
                        $force_day = true;
                    }

                    $val = \Html::computeGenericDateTimeSearch($val, $force_day);

                    $operator = '';
                    switch ($searchtype) {
                        case 'equals':
                            $operator = !$NOT ? '=' : '!=';
                            break;
                        case 'notequals':
                            $operator = !$NOT ? '!=' : '=';
                            break;
                        case 'lessthan':
                            $operator = !$NOT ? '<' : '>';
                            break;
                        case 'morethan':
                            $operator = !$NOT ? '>' : '<';
                            break;
                    }

                    return " {$LINK} ({$DB->quoteName($NAME)} $operator {$DB->quoteValue($val)}) ";
                    break;
                case "count":
                case "mio":
                case "number":
                case "decimal":
                case "timestamp":
                    $search  = ["/\&lt;/","/\&gt;/"];
                    $replace = ["<",">"];
                    $val     = preg_replace($search, $replace, $val);
                    if (preg_match("/([<>])([=]*)[[:space:]]*([0-9]+)/", $val, $regs)) {
                        if ($NOT) {
                            if ($regs[1] == '<') {
                                $regs[1] = '>';
                            } else {
                                $regs[1] = '<';
                            }
                        }
                        $regs[1] .= $regs[2];
                        return " $LINK (`$NAME` " . $regs[1] . " " . $regs[3] . " ) ";
                    }

                    if (is_numeric($val)) {
                        if (isset($searchopt[$ID]["width"])) {
                            if (!$NOT) {
                                return " $LINK (`$NAME` < " . (intval($val) + $searchopt[$ID]["width"]) . "
                                        AND `$NAME` > " .
                                    (intval($val) - $searchopt[$ID]["width"]) . ") ";
                            }
                            return " $LINK (`$NAME` > " . (intval($val) + $searchopt[$ID]["width"]) . "
                                     OR `$NAME` < " .
                                (intval($val) - $searchopt[$ID]["width"]) . " ) ";
                        }
                        // Exact search
                        if (!$NOT) {
                            return " $LINK (`$NAME` = " . (intval($val)) . ") ";
                        }
                        return " $LINK (`$NAME` <> " . (intval($val)) . ") ";
                    }
                    break;
            }
        }

        return self::makeTextCriteria("`$NAME`", $val, $NOT, $LINK);
    }


    /**
     * Generic Function to add ORDER BY to a request
     *
     * @since 9.4: $key param has been dropped
     * @since 10.0.0: Parameters changed to allow multiple sort fields.
     *    Old functionality maintained by checking the type of the first parameter.
     *    This backwards compatibility will be removed in a later version.
     *
     * @param string $itemtype The itemtype
     * @param array  $sort_fields The search options to order on. This array should contain one or more associative arrays containing:
     *    - id: The search option ID
     *    - order: The sort direction (Default: ASC). Invalid sort directions will be replaced with the default option
     * @param ?integer $_id    field to add (Deprecated)
     *
     * @return string ORDER BY query string
     *
     **/
    public static function addOrderBy($itemtype, $sort_fields, $_id = 'ASC')
    {
        global $CFG_GLPI;

        // BC parameter conversion
        if (!is_array($sort_fields)) {
            // < 10.0.0 parameters
            \Toolbox::deprecated('The parameters for Search::addOrderBy have changed to allow sorting by multiple fields. Please update your calling code.');
            $sort_fields = [
                [
                    'searchopt_id' => $sort_fields,
                    'order'        => $_id
                ]
            ];
        }

        $orderby_criteria = [];
        $searchopt = &SearchOption::getOptionsForItemtype($itemtype);

        foreach ($sort_fields as $sort_field) {
            $ID = $sort_field['searchopt_id'];
            if (isset($searchopt[$ID]['nosort']) && $searchopt[$ID]['nosort']) {
                continue;
            }
            $order = $sort_field['order'] ?? 'ASC';
            // Order security check
            if ($order != 'ASC') {
                $order = 'DESC';
            }

            $criterion = null;

            $table = $searchopt[$ID]["table"];
            $field = $searchopt[$ID]["field"];

            $addtable = '';

            $is_fkey_composite_on_self = getTableNameForForeignKeyField($searchopt[$ID]["linkfield"]) == $table
                && $searchopt[$ID]["linkfield"] != getForeignKeyFieldForTable($table);
            $orig_table = SearchEngine::getOrigTableName($itemtype);
            if (
                ($is_fkey_composite_on_self || $table != $orig_table)
                && ($searchopt[$ID]["linkfield"] != getForeignKeyFieldForTable($table))
            ) {
                $addtable .= "_" . $searchopt[$ID]["linkfield"];
            }

            if (isset($searchopt[$ID]['joinparams'])) {
                $complexjoin = self::computeComplexJoinID($searchopt[$ID]['joinparams']);

                if (!empty($complexjoin)) {
                    $addtable .= "_" . $complexjoin;
                }
            }

            if (isset($CFG_GLPI["union_search_type"][$itemtype])) {
                $criterion = "`ITEM_{$itemtype}_{$ID}` $order";
            }

            // Plugin can override core definition for its type
            if ($criterion === null && $plug = isPluginItemType($itemtype)) {
                $out = \Plugin::doOneHook(
                    $plug['plugin'],
                    'addOrderBy',
                    $itemtype,
                    $ID,
                    $order,
                    "{$itemtype}_{$ID}"
                );
                $out = $out !== null ? trim($out) : null;
                if (!empty($out)) {
                    $out = preg_replace('/^ORDER BY /', '', $out);
                    $criterion = $out;
                }
            }

            if ($criterion === null) {
                switch ($table . "." . $field) {
                    // FIXME Dead case? Can't see any itemtype referencing this table in their search options to be able to get here.
                    case "glpi_auth_tables.name":
                        $user_searchopt = SearchOption::getOptionsForItemtype('User');
                        $criterion = "`glpi_users`.`authtype` $order,
                              `glpi_authldaps" . $addtable . "_" .
                            self::computeComplexJoinID($user_searchopt[30]['joinparams']) . "`.
                                 `name` $order,
                              `glpi_authmails" . $addtable . "_" .
                            self::computeComplexJoinID($user_searchopt[31]['joinparams']) . "`.
                                 `name` $order";
                        break;

                    case "glpi_users.name":
                        if ($itemtype != 'User') {
                            if ($_SESSION["glpinames_format"] == \User::FIRSTNAME_BEFORE) {
                                $name1 = 'firstname';
                                $name2 = 'realname';
                            } else {
                                $name1 = 'realname';
                                $name2 = 'firstname';
                            }
                            $criterion = "`" . $table . $addtable . "`.`$name1` $order,
                                 `" . $table . $addtable . "`.`$name2` $order,
                                 `" . $table . $addtable . "`.`name` $order";
                        } else {
                            $criterion = "`" . $table . $addtable . "`.`name` $order";
                        }
                        break;
                    //FIXME glpi_networkequipments.ip seems like a dead case
                    case "glpi_networkequipments.ip":
                    case "glpi_ipaddresses.name":
                        $criterion = "INET6_ATON(`$table$addtable`.`$field`) $order";
                        break;
                }
            }

            //// Default cases

            // Link with plugin tables
            if ($criterion === null && preg_match("/^glpi_plugin_([a-z0-9]+)/", $table, $matches)) {
                if (count($matches) == 2) {
                    $plug = $matches[1];
                    $out = \Plugin::doOneHook(
                        $plug,
                        'addOrderBy',
                        $itemtype,
                        $ID,
                        $order,
                        "{$itemtype}_{$ID}"
                    );
                    $out = $out !== null ? trim($out) : null;
                    if (!empty($out)) {
                        $out = preg_replace('/^ORDER BY /', '', $out);
                        $criterion = $out;
                    }
                }
            }

            // Preformat items
            if ($criterion === null && isset($searchopt[$ID]["datatype"])) {
                switch ($searchopt[$ID]["datatype"]) {
                    case "date_delay":
                        $interval = "MONTH";
                        if (isset($searchopt[$ID]['delayunit'])) {
                            $interval = $searchopt[$ID]['delayunit'];
                        }

                        $add_minus = '';
                        if (isset($searchopt[$ID]["datafields"][3])) {
                            $add_minus = "- `$table$addtable`.`" . $searchopt[$ID]["datafields"][3] . "`";
                        }
                        $criterion = "ADDDATE(`$table$addtable`.`" . $searchopt[$ID]["datafields"][1] . "`,
                                         INTERVAL (`$table$addtable`.`" .
                            $searchopt[$ID]["datafields"][2] . "` $add_minus)
                                         $interval) $order";
                }
            }

            $orderby_criteria[] = $criterion ?? "`ITEM_{$itemtype}_{$ID}` $order";
        }

        if (count($orderby_criteria) === 0) {
            return '';
        }
        return ' ORDER BY ' . implode(', ', $orderby_criteria) . ' ';
    }

    /**
     * Construct SQL request depending on search parameters
     *
     * Add to data array a field sql containing an array of requests :
     *      search : request to get items limited to wanted ones
     *      count : to count all items based on search criterias
     *                    may be an array a request : need to add counts
     *                    maybe empty : use search one to count
     *
     * @param array $data Array of search datas prepared to generate SQL
     * @return false|void
     */
    public static function constructSQL(array &$data)
    {
        global $DB, $CFG_GLPI;

        if (!isset($data['itemtype'])) {
            return false;
        }

        $data['sql']['count']  = [];
        $data['sql']['search'] = '';

        $searchopt        = &SearchOption::getOptionsForItemtype($data['itemtype']);

        $blacklist_tables = [];
        $orig_table = SearchEngine::getOrigTableName($data['itemtype']);
        if (isset($CFG_GLPI['union_search_type'][$data['itemtype']])) {
            $itemtable          = $CFG_GLPI['union_search_type'][$data['itemtype']];
            $blacklist_tables[] = $orig_table;
        } else {
            $itemtable = $orig_table;
        }

        // hack for AllAssets and ReservationItem
        if (isset($CFG_GLPI['union_search_type'][$data['itemtype']])) {
            $entity_restrict = true;
        } else {
            $entity_restrict = $data['item']->isEntityAssign() && $data['item']->isField('entities_id');
        }

        // Construct the request

        //// 1 - SELECT
        // request currentuser for SQL supervision, not displayed
        $SELECT = self::buildSelect($data, $itemtable);

        //// 2 - FROM AND LEFT JOIN
        // Set reference table
        $FROM = self::buildFrom($itemtable);

        // Init already linked tables array in order not to link a table several times
        // Put reference table
        $already_link_tables = [$itemtable];

        // Add default join
        $COMMONLEFTJOIN = self::addDefaultJoin($data['itemtype'], $itemtable, $already_link_tables);
        $FROM          .= $COMMONLEFTJOIN;

        // Add all table for toview items
        foreach ($data['tocompute'] as $val) {
            if (!in_array($searchopt[$val]["table"], $blacklist_tables)) {
                $FROM .= self::addLeftJoin(
                    $data['itemtype'],
                    $itemtable,
                    $already_link_tables,
                    $searchopt[$val]["table"],
                    $searchopt[$val]["linkfield"],
                    0,
                    0,
                    $searchopt[$val]["joinparams"],
                    $searchopt[$val]["field"]
                );
            }
        }

        // Search all case :
        if ($data['search']['all_search']) {
            foreach ($searchopt as $key => $val) {
                // Do not search on Group Name
                if (is_array($val) && isset($val['table'])) {
                    if (!in_array($searchopt[$key]["table"], $blacklist_tables)) {
                        $FROM .= self::addLeftJoin(
                            $data['itemtype'],
                            $itemtable,
                            $already_link_tables,
                            $searchopt[$key]["table"],
                            $searchopt[$key]["linkfield"],
                            0,
                            0,
                            $searchopt[$key]["joinparams"],
                            $searchopt[$key]["field"]
                        );
                    }
                }
            }
        }

        //// 3 - WHERE

        // default string
        $COMMONWHERE = self::addDefaultWhere($data['itemtype']);
        $first       = empty($COMMONWHERE);

        // Add deleted if item have it
        if ($data['item'] && $data['item']->maybeDeleted()) {
            $LINK = " AND ";
            if ($first) {
                $LINK  = " ";
                $first = false;
            }
            $COMMONWHERE .= $LINK . "`$itemtable`.`is_deleted` = " . (int)$data['search']['is_deleted'] . " ";
        }

        // Remove template items
        if ($data['item'] && $data['item']->maybeTemplate()) {
            $LINK = " AND ";
            if ($first) {
                $LINK  = " ";
                $first = false;
            }
            $COMMONWHERE .= $LINK . "`$itemtable`.`is_template` = 0 ";
        }

        // Add Restrict to current entities
        if ($entity_restrict) {
            $LINK = " AND ";
            if ($first) {
                $LINK  = " ";
                $first = false;
            }

            if ($data['itemtype'] == 'Entity') {
                $COMMONWHERE .= getEntitiesRestrictRequest($LINK, $itemtable);
            } else if (isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
                // Will be replace below in Union/Recursivity Hack
                $COMMONWHERE .= $LINK . " ENTITYRESTRICT ";
            } else {
                $COMMONWHERE .= getEntitiesRestrictRequest(
                    $LINK,
                    $itemtable,
                    '',
                    '',
                    $data['item']->maybeRecursive() && $data['item']->isField('is_recursive')
                );
            }
        }
        $WHERE  = "";
        $HAVING = "";

        // Add search conditions
        // If there is search items
        if (count($data['search']['criteria'])) {
            $WHERE  = self::constructCriteriaSQL($data['search']['criteria'], $data, $searchopt);
            $HAVING = self::constructCriteriaSQL($data['search']['criteria'], $data, $searchopt, true);

            // if criteria (with meta flag) need additional join/from sql
            self::constructAdditionalSqlForMetacriteria($data['search']['criteria'], $SELECT, $FROM, $already_link_tables, $data);
        }

        //// 4 - ORDER
        $ORDER = " ORDER BY `id` ";
        $sort_fields = [];
        $sort_count = count($data['search']['sort']);
        for ($i = 0; $i < $sort_count; $i++) {
            foreach ($data['tocompute'] as $val) {
                if ($data['search']['sort'][$i] == $val) {
                    $sort_fields[] = [
                        'searchopt_id' => $data['search']['sort'][$i],
                        'order'        => $data['search']['order'][$i] ?? null
                    ];
                }
            }
        }
        if (count($sort_fields)) {
            $ORDER = self::addOrderBy($data['itemtype'], $sort_fields);
        }

        $SELECT = rtrim(trim($SELECT), ',');

        //// 7 - Manage GROUP BY
        $GROUPBY = "";
        // Meta Search / Search All / Count tickets
        $criteria_with_meta = array_filter($data['search']['criteria'], function ($criterion) {
            return isset($criterion['meta'])
                && $criterion['meta'];
        });
        if (
            (count($data['search']['metacriteria']))
            || count($criteria_with_meta)
            || !empty($HAVING)
            || $data['search']['all_search']
        ) {
            $GROUPBY = " GROUP BY `$itemtable`.`id`";
        }

        if (empty($GROUPBY)) {
            foreach ($data['toview'] as $val2) {
                if (!empty($GROUPBY)) {
                    break;
                }
                if (isset($searchopt[$val2]["forcegroupby"])) {
                    $GROUPBY = " GROUP BY `$itemtable`.`id`";
                }
            }
        }

        $LIMIT   = "";
        $numrows = 0;
        //No search : count number of items using a simple count(ID) request and LIMIT search
        if ($data['search']['no_search']) {
            $LIMIT = " LIMIT " . (int)$data['search']['start'] . ", " . (int)$data['search']['list_limit'];

            $count = "count(DISTINCT `$itemtable`.`id`)";
            // request currentuser for SQL supervision, not displayed
            $query_num = "SELECT $count,
                              '" . \Toolbox::addslashes_deep($_SESSION['glpiname']) . "' AS currentuser
                       FROM `$itemtable`" .
                $COMMONLEFTJOIN;

            $first     = true;

            if (!empty($COMMONWHERE)) {
                $LINK = " AND ";
                if ($first) {
                    $LINK  = " WHERE ";
                    $first = false;
                }
                $query_num .= $LINK . $COMMONWHERE;
            }
            // Union Search :
            if (isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
                $tmpquery = $query_num;

                foreach ($CFG_GLPI[$CFG_GLPI["union_search_type"][$data['itemtype']]] as $ctype) {
                    $ctable = $ctype::getTable();
                    if (
                        ($citem = getItemForItemtype($ctype))
                        && $citem->canView()
                    ) {
                        // State case
                        if ($data['itemtype'] == \AllAssets::getType()) {
                            $query_num  = str_replace(
                                $CFG_GLPI["union_search_type"][$data['itemtype']],
                                $ctable,
                                $tmpquery
                            );
                            $query_num  = str_replace($data['itemtype'], $ctype, $query_num);
                            $query_num .= " AND `$ctable`.`id` IS NOT NULL ";

                            // Add deleted if item have it
                            if ($citem && $citem->maybeDeleted()) {
                                $query_num .= " AND `$ctable`.`is_deleted` = 0 ";
                            }

                            // Remove template items
                            if ($citem && $citem->maybeTemplate()) {
                                $query_num .= " AND `$ctable`.`is_template` = 0 ";
                            }
                        } else {// Ref table case
                            $reftable = $data['itemtype']::getTable();
                            if ($data['item'] && $data['item']->maybeDeleted()) {
                                $tmpquery = str_replace(
                                    "`" . $CFG_GLPI["union_search_type"][$data['itemtype']] . "`.
                                                   `is_deleted`",
                                    "`$reftable`.`is_deleted`",
                                    $tmpquery
                                );
                            }
                            $replace  = "FROM `$reftable`
                                  INNER JOIN `$ctable`
                                       ON (`$reftable`.`items_id` =`$ctable`.`id`
                                           AND `$reftable`.`itemtype` = '$ctype')";

                            $query_num = str_replace(
                                "FROM `" .
                                $CFG_GLPI["union_search_type"][$data['itemtype']] . "`",
                                $replace,
                                $tmpquery
                            );
                            $query_num = str_replace(
                                $CFG_GLPI["union_search_type"][$data['itemtype']],
                                $ctable,
                                $query_num
                            );
                        }
                        $query_num = str_replace(
                            "ENTITYRESTRICT",
                            getEntitiesRestrictRequest(
                                '',
                                $ctable,
                                '',
                                '',
                                $citem->maybeRecursive()
                            ),
                            $query_num
                        );
                        $data['sql']['count'][] = $query_num;
                    }
                }
            } else {
                $data['sql']['count'][] = $query_num;
            }
        }

        // If export_all reset LIMIT condition
        if ($data['search']['export_all']) {
            $LIMIT = "";
        }

        if (!empty($WHERE) || !empty($COMMONWHERE)) {
            if (!empty($COMMONWHERE)) {
                $WHERE = ' WHERE ' . $COMMONWHERE . (!empty($WHERE) ? ' AND ( ' . $WHERE . ' )' : '');
            } else {
                $WHERE = ' WHERE ' . $WHERE . ' ';
            }
            $first = false;
        }

        if (!empty($HAVING)) {
            $HAVING = ' HAVING ' . $HAVING;
        }

        // Create QUERY
        if (isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
            $first = true;
            $QUERY = "";
            foreach ($CFG_GLPI[$CFG_GLPI["union_search_type"][$data['itemtype']]] as $ctype) {
                $ctable = $ctype::getTable();
                if (
                    ($citem = getItemForItemtype($ctype))
                    && $citem->canView()
                ) {
                    if ($first) {
                        $first = false;
                    } else {
                        $QUERY .= " UNION ";
                    }
                    $tmpquery = "";
                    // AllAssets case
                    if ($data['itemtype'] == \AllAssets::getType()) {
                        $tmpquery = $SELECT . ", '$ctype' AS TYPE " .
                            $FROM .
                            $WHERE;

                        $tmpquery .= " AND `$ctable`.`id` IS NOT NULL ";

                        // Add deleted if item have it
                        if ($citem && $citem->maybeDeleted()) {
                            $tmpquery .= " AND `$ctable`.`is_deleted` = 0 ";
                        }

                        // Remove template items
                        if ($citem && $citem->maybeTemplate()) {
                            $tmpquery .= " AND `$ctable`.`is_template` = 0 ";
                        }

                        $tmpquery .= $GROUPBY .
                            $HAVING;

                        // Replace 'asset_types' by itemtype table name
                        $tmpquery = str_replace(
                            $CFG_GLPI["union_search_type"][$data['itemtype']],
                            $ctable,
                            $tmpquery
                        );
                        // Replace 'AllAssets' by itemtype
                        // Use quoted value to prevent replacement of AllAssets in column identifiers
                        $tmpquery = str_replace(
                            $DB->quoteValue(\AllAssets::getType()),
                            $DB->quoteValue($ctype),
                            $tmpquery
                        );
                    } else {// Ref table case
                        $reftable = $data['itemtype']::getTable();

                        $tmpquery = $SELECT . ", '$ctype' AS TYPE,
                                      `$reftable`.`id` AS refID, " . "
                                      `$ctable`.`entities_id` AS ENTITY " .
                            $FROM .
                            $WHERE;
                        if ($data['item']->maybeDeleted()) {
                            $tmpquery = str_replace(
                                "`" . $CFG_GLPI["union_search_type"][$data['itemtype']] . "`.
                                                `is_deleted`",
                                "`$reftable`.`is_deleted`",
                                $tmpquery
                            );
                        }

                        $replace = "FROM `$reftable`" . "
                              INNER JOIN `$ctable`" . "
                                 ON (`$reftable`.`items_id`=`$ctable`.`id`" . "
                                     AND `$reftable`.`itemtype` = '$ctype')";
                        $tmpquery = str_replace(
                            "FROM `" .
                            $CFG_GLPI["union_search_type"][$data['itemtype']] . "`",
                            $replace,
                            $tmpquery
                        );
                        $tmpquery = str_replace(
                            $CFG_GLPI["union_search_type"][$data['itemtype']],
                            $ctable,
                            $tmpquery
                        );
                        $name_field = $ctype::getNameField();
                        $tmpquery = str_replace("`$ctable`.`name`", "`$ctable`.`$name_field`", $tmpquery);
                    }
                    $tmpquery = str_replace(
                        "ENTITYRESTRICT",
                        getEntitiesRestrictRequest(
                            '',
                            $ctable,
                            '',
                            '',
                            $citem->maybeRecursive()
                        ),
                        $tmpquery
                    );

                    // SOFTWARE HACK
                    if ($ctype == 'Software') {
                        $tmpquery = str_replace("`glpi_softwares`.`serial`", "''", $tmpquery);
                        $tmpquery = str_replace("`glpi_softwares`.`otherserial`", "''", $tmpquery);
                    }
                    $QUERY .= $tmpquery;
                }
            }
            if (empty($QUERY)) {
                echo \Search::showError($data['display_type']);
                return;
            }
            $QUERY .= str_replace($CFG_GLPI["union_search_type"][$data['itemtype']] . ".", "", $ORDER) .
                $LIMIT;
        } else {
            $QUERY = $SELECT .
                $FROM .
                $WHERE .
                $GROUPBY .
                $HAVING .
                $ORDER .
                $LIMIT;
        }
        $data['sql']['search'] = $QUERY;
    }

    /**
     * Construct WHERE (or HAVING) part of the sql based on passed criteria
     **
     * @param  array   $criteria  list of search criterion, we should have these keys:
     *                               - link (optionnal): AND, OR, NOT AND, NOT OR
     *                               - field: id of the searchoption
     *                               - searchtype: how to match value (contains, equals, etc)
     *                               - value
     * @param  array   $data      common array used by search engine,
     *                            contains all the search part (sql, criteria, params, itemtype etc)
     *                            TODO: should be a property of the class
     * @param  array   $searchopt Search options for the current itemtype
     * @param  boolean $is_having Do we construct sql WHERE or HAVING part
     *
     * @return string             the sql sub string
     */
    public static function constructCriteriaSQL($criteria = [], $data = [], $searchopt = [], $is_having = false): string
    {
        $sql = "";

        foreach ($criteria as $criterion) {
            if (
                !isset($criterion['criteria'])
                && (!isset($criterion['value'])
                    || strlen($criterion['value']) <= 0)
            ) {
                continue;
            }

            $itemtype = $data['itemtype'];
            $meta = false;
            if (
                isset($criterion['meta'])
                && $criterion['meta']
                && isset($criterion['itemtype'])
            ) {
                $itemtype = $criterion['itemtype'];
                $meta = true;
                $meta_searchopt = &SearchOption::getOptionsForItemtype($itemtype);
            } else {
                // Not a meta, use the same search option everywhere
                $meta_searchopt = $searchopt;
            }

            // common search
            if (
                !isset($criterion['field'])
                || ($criterion['field'] != "all"
                    && $criterion['field'] != "view")
            ) {
                $LINK    = " ";
                $NOT     = 0;
                $tmplink = "";

                if (
                    isset($criterion['link'])
                    && in_array($criterion['link'], array_keys(SearchEngine::getLogicalOperators()))
                ) {
                    if (strstr($criterion['link'], "NOT")) {
                        $tmplink = " " . str_replace(" NOT", "", $criterion['link']);
                        $NOT     = 1;
                    } else {
                        $tmplink = " " . $criterion['link'];
                    }
                } else {
                    $tmplink = " AND ";
                }

                // Manage Link if not first item
                if (!empty($sql)) {
                    $LINK = $tmplink;
                }

                if (isset($criterion['criteria']) && count($criterion['criteria'])) {
                    $sub_sql = self::constructCriteriaSQL($criterion['criteria'], $data, $meta_searchopt, $is_having);
                    if (strlen($sub_sql)) {
                        if ($NOT) {
                            $sql .= "$LINK NOT($sub_sql)";
                        } else {
                            $sql .= "$LINK ($sub_sql)";
                        }
                    }
                } else if (
                    isset($meta_searchopt[$criterion['field']]["usehaving"])
                    || ($meta && "AND NOT" === $criterion['link'])
                ) {
                    if (!$is_having) {
                        // the having part will be managed in a second pass
                        continue;
                    }

                    $new_having = self::addHaving(
                        $LINK,
                        $NOT,
                        $itemtype,
                        $criterion['field'],
                        $criterion['searchtype'],
                        $criterion['value']
                    );
                    if ($new_having !== false) {
                        $sql .= $new_having;
                    }
                } else {
                    if ($is_having) {
                        // the having part has been already managed in the first pass
                        continue;
                    }

                    $new_where = self::addWhere(
                        $LINK,
                        $NOT,
                        $itemtype,
                        $criterion['field'],
                        $criterion['searchtype'],
                        $criterion['value'],
                        $meta
                    );
                    if ($new_where !== false) {
                        $sql .= $new_where;
                    }
                }
            } else if (
                isset($criterion['value'])
                && strlen($criterion['value']) > 0
            ) { // view and all search
                $LINK       = " OR ";
                $NOT        = 0;
                $globallink = " AND ";
                if (isset($criterion['link'])) {
                    switch ($criterion['link']) {
                        case "AND":
                            $LINK       = " OR ";
                            $globallink = " AND ";
                            break;
                        case "AND NOT":
                            $LINK       = " AND ";
                            $NOT        = 1;
                            $globallink = " AND ";
                            break;
                        case "OR":
                            $LINK       = " OR ";
                            $globallink = " OR ";
                            break;
                        case "OR NOT":
                            $LINK       = " AND ";
                            $NOT        = 1;
                            $globallink = " OR ";
                            break;
                    }
                } else {
                    $tmplink = " AND ";
                }
                // Manage Link if not first item
                if (!empty($sql) && !$is_having) {
                    $sql .= $globallink;
                }
                $first2 = true;
                $items = [];
                if (isset($criterion['field']) && $criterion['field'] == "all") {
                    $items = $searchopt;
                } else { // toview case : populate toview
                    foreach ($data['toview'] as $key2 => $val2) {
                        $items[$val2] = $searchopt[$val2];
                    }
                }
                $view_sql = "";
                foreach ($items as $key2 => $val2) {
                    if (isset($val2['nosearch']) && $val2['nosearch']) {
                        continue;
                    }
                    if (is_array($val2)) {
                        // Add Where clause if not to be done in HAVING CLAUSE
                        if (!$is_having && !isset($val2["usehaving"])) {
                            $tmplink = $LINK;
                            if ($first2) {
                                $tmplink = " ";
                            }

                            $new_where = self::addWhere(
                                $tmplink,
                                $NOT,
                                $itemtype,
                                $key2,
                                $criterion['searchtype'],
                                $criterion['value'],
                                $meta
                            );
                            if ($new_where !== false) {
                                $first2  = false;
                                $view_sql .=  $new_where;
                            }
                        }
                    }
                }
                if (strlen($view_sql)) {
                    $sql .= " ($view_sql) ";
                }
            }
        }
        return $sql;
    }

    /**
     * Construct aditionnal SQL (select, joins, etc) for meta-criteria
     **
     * @param  array  $criteria             list of search criterion
     * @param  string &$SELECT              TODO: should be a class property (output parameter)
     * @param  string &$FROM                TODO: should be a class property (output parameter)
     * @param  array  &$already_link_tables TODO: should be a class property (output parameter)
     * @param  array  &$data                TODO: should be a class property (output parameter)
     *
     * @return void
     */
    public static function constructAdditionalSqlForMetacriteria(
        $criteria = [],
        &$SELECT = "",
        &$FROM = "",
        &$already_link_tables = [],
        &$data = []
    ) {
        $data['meta_toview'] = [];
        foreach ($criteria as $criterion) {
            // manage sub criteria
            if (isset($criterion['criteria'])) {
                self::constructAdditionalSqlForMetacriteria(
                    $criterion['criteria'],
                    $SELECT,
                    $FROM,
                    $already_link_tables,
                    $data
                );
                continue;
            }

            // parse only criterion with meta flag
            if (
                !isset($criterion['itemtype'])
                || empty($criterion['itemtype'])
                || !isset($criterion['meta'])
                || !$criterion['meta']
                || !isset($criterion['value'])
                || strlen($criterion['value']) <= 0
            ) {
                continue;
            }

            $m_itemtype = $criterion['itemtype'];
            $metaopt = &SearchOption::getOptionsForItemtype($m_itemtype);
            $sopt    = $metaopt[$criterion['field']];

            //add toview for meta criterion
            $data['meta_toview'][$m_itemtype][] = $criterion['field'];

            $SELECT .= self::addSelect(
                $m_itemtype,
                $criterion['field'],
                true, // meta-criterion
                $m_itemtype
            );

            $FROM .= self::addMetaLeftJoin(
                $data['itemtype'],
                $m_itemtype,
                $already_link_tables,
                $sopt["joinparams"]
            );

            $FROM .= self::addLeftJoin(
                $m_itemtype,
                $m_itemtype::getTable(),
                $already_link_tables,
                $sopt["table"],
                $sopt["linkfield"],
                1,
                $m_itemtype,
                $sopt["joinparams"],
                $sopt["field"]
            );
        }
    }

    /**
     * Retrieve datas from DB : construct data array containing columns definitions and rows datas
     *
     * add to data array a field data containing :
     *      cols : columns definition
     *      rows : rows data
     *
     * @param array   $data      array of search data prepared to get data
     * @param boolean $onlycount If we just want to count results
     *
     * @return void|false
     **/
    public static function constructData(array &$data, $onlycount = false)
    {
        if (!isset($data['sql']) || !isset($data['sql']['search'])) {
            return false;
        }
        $data['data'] = [];

        // Use a ReadOnly connection if available and configured to be used
        $DBread = \DBConnection::getReadConnection();
        $DBread->query("SET SESSION group_concat_max_len = 8194304;");

        $DBread->execution_time = true;
        $result = $DBread->query($data['sql']['search']);

        if ($result) {
            $data['data']['execution_time'] = $DBread->execution_time;
            if (isset($data['search']['savedsearches_id'])) {
                \SavedSearch::updateExecutionTime(
                    (int)$data['search']['savedsearches_id'],
                    $DBread->execution_time
                );
            }

            $data['data']['totalcount'] = 0;
            // if real search or complete export : get numrows from request
            if (
                !$data['search']['no_search']
                || $data['search']['export_all']
            ) {
                $data['data']['totalcount'] = $DBread->numrows($result);
            } else {
                if (
                    !isset($data['sql']['count'])
                    || (count($data['sql']['count']) == 0)
                ) {
                    $data['data']['totalcount'] = $DBread->numrows($result);
                } else {
                    foreach ($data['sql']['count'] as $sqlcount) {
                        $result_num = $DBread->query($sqlcount);
                        $data['data']['totalcount'] += $DBread->result($result_num, 0, 0);
                    }
                }
            }

            if ($onlycount) {
                //we just want to coutn results; no need to continue process
                return;
            }

            if ($data['search']['start'] > $data['data']['totalcount']) {
                $data['search']['start'] = 0;
            }

            // Search case
            $data['data']['begin'] = $data['search']['start'];
            $data['data']['end']   = min(
                $data['data']['totalcount'],
                $data['search']['start'] + $data['search']['list_limit']
            ) - 1;
            //map case
            if (isset($data['search']['as_map'])  && $data['search']['as_map'] == 1) {
                $data['data']['end'] = $data['data']['totalcount'] - 1;
            }

            // No search Case
            if ($data['search']['no_search']) {
                $data['data']['begin'] = 0;
                $data['data']['end']   = min(
                    $data['data']['totalcount'] - $data['search']['start'],
                    $data['search']['list_limit']
                ) - 1;
            }
            // Export All case
            if ($data['search']['export_all']) {
                $data['data']['begin'] = 0;
                $data['data']['end']   = $data['data']['totalcount'] - 1;
            }

            // Get columns
            $data['data']['cols'] = [];

            $searchopt = &SearchOption::getOptionsForItemtype($data['itemtype']);

            foreach ($data['toview'] as $opt_id) {
                $data['data']['cols'][] = [
                    'itemtype'  => $data['itemtype'],
                    'id'        => $opt_id,
                    'name'      => $searchopt[$opt_id]["name"],
                    'meta'      => 0,
                    'searchopt' => $searchopt[$opt_id],
                ];
            }

            // manage toview column for criteria with meta flag
            foreach ($data['meta_toview'] as $m_itemtype => $toview) {
                $searchopt = &SearchOption::getOptionsForItemtype($m_itemtype);
                foreach ($toview as $opt_id) {
                    $data['data']['cols'][] = [
                        'itemtype'  => $m_itemtype,
                        'id'        => $opt_id,
                        'name'      => $searchopt[$opt_id]["name"],
                        'meta'      => 1,
                        'searchopt' => $searchopt[$opt_id],
                    ];
                }
            }

            // Display columns Headers for meta items
            $already_printed = [];

            if (count($data['search']['metacriteria'])) {
                foreach ($data['search']['metacriteria'] as $metacriteria) {
                    if (
                        isset($metacriteria['itemtype']) && !empty($metacriteria['itemtype'])
                        && isset($metacriteria['value']) && (strlen($metacriteria['value']) > 0)
                    ) {
                        if (!isset($already_printed[$metacriteria['itemtype'] . $metacriteria['field']])) {
                            $searchopt = &SearchOption::getOptionsForItemtype($metacriteria['itemtype']);

                            $data['data']['cols'][] = [
                                'itemtype'  => $metacriteria['itemtype'],
                                'id'        => $metacriteria['field'],
                                'name'      => $searchopt[$metacriteria['field']]["name"],
                                'meta'      => 1,
                                'searchopt' => $searchopt[$metacriteria['field']]
                            ];

                            $already_printed[$metacriteria['itemtype'] . $metacriteria['field']] = 1;
                        }
                    }
                }
            }

            // search group (corresponding of dropdown optgroup) of current col
            foreach ($data['data']['cols'] as $num => $col) {
                // search current col in searchoptions ()
                while (
                    key($searchopt) !== null
                    && key($searchopt) != $col['id']
                ) {
                    next($searchopt);
                }
                if (key($searchopt) !== null) {
                    //search optgroup (non array option)
                    while (
                        key($searchopt) !== null
                        && is_numeric(key($searchopt))
                        && is_array(current($searchopt))
                    ) {
                        prev($searchopt);
                    }
                    if (
                        key($searchopt) !== null
                        && key($searchopt) !== "common"
                    ) {
                        $data['data']['cols'][$num]['groupname'] = current($searchopt);
                    }
                }
                //reset
                reset($searchopt);
            }

            // Get rows

            // if real search seek to begin of items to display (because of complete search)
            if (!$data['search']['no_search']) {
                $DBread->dataSeek($result, $data['search']['start']);
            }

            $i = $data['data']['begin'];
            $data['data']['warning']
                = "For compatibility keep raw data  (ITEM_X, META_X) at the top for the moment. Will be drop in next version";

            $data['data']['rows']  = [];
            $data['data']['items'] = [];

            \Search::$output_type = $data['display_type'];

            while (($i < $data['data']['totalcount']) && ($i <= $data['data']['end'])) {
                $row = $DBread->fetchAssoc($result);
                $newrow        = [];
                $newrow['raw'] = $row;

                // Parse datas
                foreach ($newrow['raw'] as $key => $val) {
                    if (preg_match('/ITEM(_(\w[^\d]+))?_(\d+)(_(.+))?/', $key, $matches)) {
                        $j = $matches[3];
                        if (isset($matches[2]) && !empty($matches[2])) {
                            $j = $matches[2] . '_' . $matches[3];
                        }
                        $fieldname = 'name';
                        if (isset($matches[5])) {
                            $fieldname = $matches[5];
                        }

                        // No Group_concat case
                        if ($fieldname == 'content' || !is_string($val) || strpos($val, \Search::LONGSEP) === false) {
                            $newrow[$j]['count'] = 1;

                            $handled = false;
                            if ($fieldname != 'content' && is_string($val) && strpos($val, \Search::SHORTSEP) !== false) {
                                $split2                    = \Search::explodeWithID(\Search::SHORTSEP, $val);
                                if (is_numeric($split2[1])) {
                                    $newrow[$j][0][$fieldname] = $split2[0];
                                    $newrow[$j][0]['id']       = $split2[1];
                                    $handled = true;
                                }
                            }

                            if (!$handled) {
                                if ($val === \Search::NULLVALUE) {
                                    $newrow[$j][0][$fieldname] = null;
                                } else {
                                    $newrow[$j][0][$fieldname] = $val;
                                }
                            }
                        } else {
                            if (!isset($newrow[$j])) {
                                $newrow[$j] = [];
                            }
                            $split               = explode(\Search::LONGSEP, $val);
                            $newrow[$j]['count'] = count($split);
                            foreach ($split as $key2 => $val2) {
                                $handled = false;
                                if (strpos($val2, \Search::SHORTSEP) !== false) {
                                    $split2                  = \Search::explodeWithID(\Search::SHORTSEP, $val2);
                                    if (is_numeric($split2[1])) {
                                        $newrow[$j][$key2]['id'] = $split2[1];
                                        if ($split2[0] == \Search::NULLVALUE) {
                                            $newrow[$j][$key2][$fieldname] = null;
                                        } else {
                                            $newrow[$j][$key2][$fieldname] = $split2[0];
                                        }
                                        $handled = true;
                                    }
                                }

                                if (!$handled) {
                                    $newrow[$j][$key2][$fieldname] = $val2;
                                }
                            }
                        }
                    } else {
                        if ($key == 'currentuser') {
                            if (!isset($data['data']['currentuser'])) {
                                $data['data']['currentuser'] = $val;
                            }
                        } else {
                            $newrow[$key] = $val;
                            // Add id to items list
                            if ($key == 'id') {
                                $data['data']['items'][$val] = $i;
                            }
                        }
                    }
                }
                foreach ($data['data']['cols'] as $val) {
                    $newrow[$val['itemtype'] . '_' . $val['id']]['displayname'] = self::giveItem(
                        $val['itemtype'],
                        $val['id'],
                        $newrow
                    );
                }

                $data['data']['rows'][$i] = $newrow;
                $i++;
            }

            $data['data']['count'] = count($data['data']['rows']);
        } else {
            $error_no = $DBread->errno();
            if ($error_no == 1116) { // Too many tables; MySQL can only use 61 tables in a join
                echo \Search::showError(
                    $data['search']['display_type'],
                    __("'All' criterion is not usable with this object list, " .
                        "sql query fails (too many tables). " .
                        "Please use 'Items seen' criterion instead")
                );
            } else {
                echo $DBread->error();
            }
        }
    }

    /**
     * Create SQL search condition
     *
     * @param string  $field  Nname (should be ` protected)
     * @param string  $val    Value to search
     * @param boolean $not    Is a negative search ? (false by default)
     * @param string  $link   With previous criteria (default 'AND')
     *
     * @return string Search SQL string
     **/
    public static function makeTextCriteria($field, $val, $not = false, $link = 'AND')
    {

        $sql = $field . self::makeTextSearch($val, $not);
        // mange empty field (string with length = 0)
        $sql_or = "";
        if (strtolower($val) == "null") {
            $sql_or = "OR $field = ''";
        }

        if (
            ($not && ($val != 'NULL') && ($val != 'null') && ($val != '^$'))    // Not something
            || (!$not && ($val == '^$'))
        ) {   // Empty
            $sql = "($sql OR $field IS NULL)";
        }
        return " $link ($sql $sql_or)";
    }

    /**
     * Create SQL search value
     *
     * @since 9.4
     *
     * @param string  $val value to search
     *
     * @return string|null
     **/
    public static function makeTextSearchValue($val)
    {
        // `$val` will mostly comes from sanitized input, but may also be raw value.
        // 1. Unsanitize value to be sure to use raw value.
        // 2. Escape raw value to protect SQL special chars.
        $val = Sanitizer::dbEscape(Sanitizer::unsanitize($val));

        // escape _ char used as wildcard in mysql likes
        $val = str_replace('_', '\\_', $val);

        if ($val === 'NULL' || $val === 'null') {
            return null;
        }

        $val = trim($val);

        if ($val === '^') {
            // Special case, searching "^" means we are searching for a non-empty/null field
            return '%';
        }

        if ($val === '' || $val === '^$' || $val === '$') {
            return '';
        }

        if (preg_match('/^\^/', $val)) {
            // Remove leading `^`
            $val = ltrim(preg_replace('/^\^/', '', $val));
        } else {
            // Add % wildcard before searched string if not begining by a `^`
            $val = '%' . $val;
        }

        if (preg_match('/\$$/', $val)) {
            // Remove trailing `$`
            $val = rtrim(preg_replace('/\$$/', '', $val));
        } else {
            // Add % wildcard after searched string if not ending by a `$`
            $val = $val . '%';
        }

        return $val;
    }

    /**
     * Create SQL search condition
     *
     * @param string  $val  Value to search
     * @param boolean $not  Is a negative search ? (false by default)
     *
     * @return string Search string
     **/
    public static function makeTextSearch($val, $not = false): string
    {
        $NOT = "";
        if ($not) {
            $NOT = "NOT";
        }

        $search_val = self::makeTextSearchValue($val);
        if ($search_val == null) {
            $SEARCH = " IS $NOT NULL ";
        } else {
            $SEARCH = " $NOT LIKE " . \DBmysql::quoteValue($search_val) . " ";
        }
        return $SEARCH;
    }

    /**
     * Generic Function to display Items
     *
     * @since 9.4: $num param has been dropped
     *
     * @param string  $itemtype        item type
     * @param integer $ID              ID of the SEARCH_OPTION item
     * @param array   $data            array containing data results
     * @param boolean $meta            is a meta item ? (default 0)
     * @param array   $addobjectparams array added parameters for union search
     * @param string  $orig_itemtype   Original itemtype, used for union_search_type
     *
     * @return string String to print
     **/
    public static function giveItem(
        $itemtype,
        $ID,
        array $data,
        $meta = 0,
        array $addobjectparams = [],
        $orig_itemtype = null
    ) {
        global $CFG_GLPI;

        $searchopt = &SearchOption::getOptionsForItemtype($itemtype);
        if (
            isset($CFG_GLPI["union_search_type"][$itemtype])
            && ($CFG_GLPI["union_search_type"][$itemtype] == $searchopt[$ID]["table"])
        ) {
            $oparams = [];
            if (
                isset($searchopt[$ID]['addobjectparams'])
                && $searchopt[$ID]['addobjectparams']
            ) {
                $oparams = $searchopt[$ID]['addobjectparams'];
            }

            // Search option may not exists in subtype
            // This is the case for "Inventory number" for a Software listed from ReservationItem search
            $subtype_so = &SearchOption::getOptionsForItemtype($data["TYPE"]);
            if (!array_key_exists($ID, $subtype_so)) {
                return '';
            }

            return self::giveItem($data["TYPE"], $ID, $data, $meta, $oparams, $itemtype);
        }
        $so = $searchopt[$ID];
        $orig_id = $ID;
        $ID = ($orig_itemtype !== null ? $orig_itemtype : $itemtype) . '_' . $ID;

        if (count($addobjectparams)) {
            $so = array_merge($so, $addobjectparams);
        }
        // Plugin can override core definition for its type
        if ($plug = isPluginItemType($itemtype)) {
            $out = \Plugin::doOneHook(
                $plug['plugin'],
                'giveItem',
                $itemtype,
                $orig_id,
                $data,
                $ID
            );
            if (!empty($out)) {
                return $out;
            }
        }

        $html_output = in_array(
            \Search::$output_type,
            [
                \Search::HTML_OUTPUT,
                \Search::GLOBAL_SEARCH, // For a global search, output will be done in HTML context
            ]
        );

        if (isset($so["table"])) {
            $table     = $so["table"];
            $field     = $so["field"];
            $linkfield = $so["linkfield"];

            /// TODO try to clean all specific cases using SpecificToDisplay

            switch ($table . '.' . $field) {
                case "glpi_users.name":
                    // USER search case
                    if (
                        ($itemtype != 'User')
                        && isset($so["forcegroupby"]) && $so["forcegroupby"]
                    ) {
                        $out           = "";
                        $count_display = 0;
                        $added         = [];

                        $showuserlink = 0;
                        if (Session::haveRight('user', READ)) {
                            $showuserlink = 1;
                        }

                        for ($k = 0; $k < $data[$ID]['count']; $k++) {
                            if (
                                (isset($data[$ID][$k]['name']) && ($data[$ID][$k]['name'] > 0))
                                || (isset($data[$ID][$k][2]) && ($data[$ID][$k][2] != ''))
                            ) {
                                if ($count_display) {
                                    $out .= \Search::LBBR;
                                }

                                if ($itemtype == 'Ticket') {
                                    if (
                                        isset($data[$ID][$k]['name'])
                                        && $data[$ID][$k]['name'] > 0
                                    ) {
                                        if (
                                            Session::getCurrentInterface() == 'helpdesk'
                                            && $orig_id == 5 // -> Assigned user
                                            && !empty($anon_name = \User::getAnonymizedNameForUser(
                                                $data[$ID][$k]['name'],
                                                $itemtype::getById($data['id'])->getEntityId()
                                            ))
                                        ) {
                                            $out .= $anon_name;
                                        } else {
                                            $userdata = getUserName($data[$ID][$k]['name'], 2);
                                            $tooltip  = "";
                                            if (Session::haveRight('user', READ)) {
                                                $tooltip = \Html::showToolTip(
                                                    $userdata["comment"],
                                                    ['link'    => $userdata["link"],
                                                        'display' => false
                                                    ]
                                                );
                                            }
                                            $out .= sprintf(__('%1$s %2$s'), $userdata['name'], $tooltip);
                                        }

                                        $count_display++;
                                    }
                                } else {
                                    $out .= getUserName($data[$ID][$k]['name'], $showuserlink);
                                    $count_display++;
                                }

                                // Manage alternative_email for tickets_users
                                if (
                                    ($itemtype == 'Ticket')
                                    && isset($data[$ID][$k][2])
                                ) {
                                    $split = explode(\Search::LONGSEP, $data[$ID][$k][2]);
                                    for ($l = 0; $l < count($split); $l++) {
                                        $split2 = explode(" ", $split[$l]);
                                        if ((count($split2) == 2) && ($split2[0] == 0) && !empty($split2[1])) {
                                            if ($count_display) {
                                                $out .= \Search::LBBR;
                                            }
                                            $count_display++;
                                            $out .= "<a href='mailto:" . $split2[1] . "'>" . $split2[1] . "</a>";
                                        }
                                    }
                                }
                            }
                        }
                        return $out;
                    }
                    if ($itemtype != 'User') {
                        $toadd = '';
                        if (
                            ($itemtype == 'Ticket')
                            && ($data[$ID][0]['id'] > 0)
                        ) {
                            $userdata = getUserName($data[$ID][0]['id'], 2);
                            $toadd    = \Html::showToolTip(
                                $userdata["comment"],
                                ['link'    => $userdata["link"],
                                    'display' => false
                                ]
                            );
                        }
                        $usernameformat = formatUserName(
                            $data[$ID][0]['id'],
                            $data[$ID][0]['name'],
                            $data[$ID][0]['realname'],
                            $data[$ID][0]['firstname'],
                            1
                        );
                        return sprintf(__('%1$s %2$s'), $usernameformat, $toadd);
                    }

                    if ($html_output) {
                        $current_users_id = $data[$ID][0]['id'] ?? 0;
                        if ($current_users_id > 0) {
                            return TemplateRenderer::getInstance()->render('components/user/picture.html.twig', [
                                'users_id'      => $current_users_id,
                                'display_login' => true,
                                'force_login'   => true,
                                'avatar_size'   => "avatar-sm",
                            ]);
                        }
                    }
                    break;

                case "glpi_profiles.name":
                    if (
                        ($itemtype == 'User')
                        && ($orig_id == 20)
                    ) {
                        $out           = "";

                        $count_display = 0;
                        $added         = [];
                        for ($k = 0; $k < $data[$ID]['count']; $k++) {
                            if (
                                strlen(trim($data[$ID][$k]['name'])) > 0
                                && !in_array(
                                    $data[$ID][$k]['name'] . "-" . $data[$ID][$k]['entities_id'],
                                    $added
                                )
                            ) {
                                $text = sprintf(
                                    __('%1$s - %2$s'),
                                    $data[$ID][$k]['name'],
                                    \Dropdown::getDropdownName(
                                        'glpi_entities',
                                        $data[$ID][$k]['entities_id']
                                    )
                                );
                                $comp = '';
                                if ($data[$ID][$k]['is_recursive']) {
                                    $comp = __('R');
                                    if ($data[$ID][$k]['is_dynamic']) {
                                        $comp = sprintf(__('%1$s%2$s'), $comp, ", ");
                                    }
                                }
                                if ($data[$ID][$k]['is_dynamic']) {
                                    $comp = sprintf(__('%1$s%2$s'), $comp, __('D'));
                                }
                                if (!empty($comp)) {
                                    $text = sprintf(__('%1$s %2$s'), $text, "(" . $comp . ")");
                                }
                                if ($count_display) {
                                    $out .= \Search::LBBR;
                                }
                                $count_display++;
                                $out     .= $text;
                                $added[]  = $data[$ID][$k]['name'] . "-" . $data[$ID][$k]['entities_id'];
                            }
                        }
                        return $out;
                    }
                    break;

                case "glpi_entities.completename":
                    if ($itemtype == 'User') {
                        $out           = "";
                        $added         = [];
                        $count_display = 0;
                        for ($k = 0; $k < $data[$ID]['count']; $k++) {
                            if (
                                isset($data[$ID][$k]['name'])
                                && (strlen(trim($data[$ID][$k]['name'])) > 0)
                                && !in_array(
                                    $data[$ID][$k]['name'] . "-" . $data[$ID][$k]['profiles_id'],
                                    $added
                                )
                            ) {
                                $text = sprintf(
                                    __('%1$s - %2$s'),
                                    \Entity::badgeCompletename($data[$ID][$k]['name']),
                                    \Dropdown::getDropdownName(
                                        'glpi_profiles',
                                        $data[$ID][$k]['profiles_id']
                                    )
                                );
                                $comp = '';
                                if ($data[$ID][$k]['is_recursive']) {
                                    $comp = __('R');
                                    if ($data[$ID][$k]['is_dynamic']) {
                                        $comp = sprintf(__('%1$s%2$s'), $comp, ", ");
                                    }
                                }
                                if ($data[$ID][$k]['is_dynamic']) {
                                    $comp = sprintf(__('%1$s%2$s'), $comp, __('D'));
                                }
                                if (!empty($comp)) {
                                    $text = sprintf(__('%1$s %2$s'), $text, "(" . $comp . ")");
                                }
                                if ($count_display) {
                                    $out .= \Search::LBBR;
                                }
                                $count_display++;
                                $out    .= $text;
                                $added[] = $data[$ID][$k]['name'] . "-" . $data[$ID][$k]['profiles_id'];
                            }
                        }
                        return $out;
                    } else if (($so["datatype"] ?? "") != "itemlink" && !empty($data[$ID][0]['name'])) {
                        return \Entity::badgeCompletename($data[$ID][0]['name']);
                    }
                    break;

                case "glpi_documenttypes.icon":
                    if (!empty($data[$ID][0]['name'])) {
                        return "<img class='middle' alt='' src='" . $CFG_GLPI["typedoc_icon_dir"] . "/" .
                            $data[$ID][0]['name'] . "'>";
                    }
                    return "&nbsp;";

                case "glpi_documents.filename":
                    $doc = new \Document();
                    if ($doc->getFromDB($data['id'])) {
                        return $doc->getDownloadLink();
                    }
                    return NOT_AVAILABLE;

                case "glpi_tickets_tickets.tickets_id_1":
                    $out        = "";
                    $displayed  = [];
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        $linkid = ($data[$ID][$k]['tickets_id_2'] == $data['id'])
                            ? $data[$ID][$k]['name']
                            : $data[$ID][$k]['tickets_id_2'];

                        // If link ID is int or integer string, force conversion to int. Coversion to int and then string to compare is needed to ensure it isn't a decimal
                        if (is_numeric($linkid) && ((string)(int)$linkid === (string)$linkid)) {
                            $linkid = (int) $linkid;
                        }
                        if ((is_int($linkid) && $linkid > 0) && !isset($displayed[$linkid])) {
                            $text  = "<a ";
                            $text .= "href=\"" . \Ticket::getFormURLWithID($linkid) . "\">";
                            $text .= \Dropdown::getDropdownName('glpi_tickets', $linkid) . "</a>";
                            if (count($displayed)) {
                                $out .= \Search::LBBR;
                            }
                            $displayed[$linkid] = $linkid;
                            $out               .= $text;
                        }
                    }
                    return $out;

                case "glpi_problems.id":
                    if ($so["datatype"] == 'count') {
                        if (
                            ($data[$ID][0]['name'] > 0)
                            && Session::haveRight("problem", \Problem::READALL)
                        ) {
                            if ($itemtype == 'ITILCategory') {
                                $options['criteria'][0]['field']      = 7;
                                $options['criteria'][0]['searchtype'] = 'equals';
                                $options['criteria'][0]['value']      = $data['id'];
                                $options['criteria'][0]['link']       = 'AND';
                            } else {
                                $options['criteria'][0]['field']       = 12;
                                $options['criteria'][0]['searchtype']  = 'equals';
                                $options['criteria'][0]['value']       = 'all';
                                $options['criteria'][0]['link']        = 'AND';

                                $options['metacriteria'][0]['itemtype']   = $itemtype;
                                $options['metacriteria'][0]['field']      = SearchOption::getOptionNumber(
                                    $itemtype,
                                    'name'
                                );
                                $options['metacriteria'][0]['searchtype'] = 'equals';
                                $options['metacriteria'][0]['value']      = $data['id'];
                                $options['metacriteria'][0]['link']       = 'AND';
                            }

                            $options['reset'] = 'reset';

                            $out  = "<a id='problem$itemtype" . $data['id'] . "' ";
                            $out .= "href=\"" . $CFG_GLPI["root_doc"] . "/front/problem.php?" .
                                \Toolbox::append_params($options, '&amp;') . "\">";
                            $out .= $data[$ID][0]['name'] . "</a>";
                            return $out;
                        }
                    }
                    break;

                case "glpi_tickets.id":
                    if ($so["datatype"] == 'count') {
                        if (
                            ($data[$ID][0]['name'] > 0)
                            && Session::haveRight("ticket", \Ticket::READALL)
                        ) {
                            if ($itemtype == 'User') {
                                // Requester
                                if ($ID == 'User_60') {
                                    $options['criteria'][0]['field']      = 4;
                                    $options['criteria'][0]['searchtype'] = 'equals';
                                    $options['criteria'][0]['value']      = $data['id'];
                                    $options['criteria'][0]['link']       = 'AND';
                                }

                                // Writer
                                if ($ID == 'User_61') {
                                    $options['criteria'][0]['field']      = 22;
                                    $options['criteria'][0]['searchtype'] = 'equals';
                                    $options['criteria'][0]['value']      = $data['id'];
                                    $options['criteria'][0]['link']       = 'AND';
                                }
                                // Assign
                                if ($ID == 'User_64') {
                                    $options['criteria'][0]['field']      = 5;
                                    $options['criteria'][0]['searchtype'] = 'equals';
                                    $options['criteria'][0]['value']      = $data['id'];
                                    $options['criteria'][0]['link']       = 'AND';
                                }
                            } else if ($itemtype == 'ITILCategory') {
                                $options['criteria'][0]['field']      = 7;
                                $options['criteria'][0]['searchtype'] = 'equals';
                                $options['criteria'][0]['value']      = $data['id'];
                                $options['criteria'][0]['link']       = 'AND';
                            } else {
                                $options['criteria'][0]['field']       = 12;
                                $options['criteria'][0]['searchtype']  = 'equals';
                                $options['criteria'][0]['value']       = 'all';
                                $options['criteria'][0]['link']        = 'AND';

                                $options['metacriteria'][0]['itemtype']   = $itemtype;
                                $options['metacriteria'][0]['field']      = SearchOption::getOptionNumber(
                                    $itemtype,
                                    'name'
                                );
                                $options['metacriteria'][0]['searchtype'] = 'equals';
                                $options['metacriteria'][0]['value']      = $data['id'];
                                $options['metacriteria'][0]['link']       = 'AND';
                            }

                            $options['reset'] = 'reset';

                            $out  = "<a id='ticket$itemtype" . $data['id'] . "' ";
                            $out .= "href=\"" . $CFG_GLPI["root_doc"] . "/front/ticket.php?" .
                                \Toolbox::append_params($options, '&amp;') . "\">";
                            $out .= $data[$ID][0]['name'] . "</a>";
                            return $out;
                        }
                    }
                    break;

                case "glpi_tickets.time_to_resolve":
                case "glpi_problems.time_to_resolve":
                case "glpi_changes.time_to_resolve":
                case "glpi_tickets.time_to_own":
                case "glpi_tickets.internal_time_to_own":
                case "glpi_tickets.internal_time_to_resolve":
                    // Due date + progress
                    if (in_array($orig_id, [151, 158, 181, 186])) {
                        $out = \Html::convDateTime($data[$ID][0]['name']);

                        // No due date in waiting status
                        if ($data[$ID][0]['status'] == \CommonITILObject::WAITING) {
                            return '';
                        }
                        if (empty($data[$ID][0]['name'])) {
                            return '';
                        }
                        if (
                            ($data[$ID][0]['status'] == \Ticket::SOLVED)
                            || ($data[$ID][0]['status'] == \Ticket::CLOSED)
                        ) {
                            return $out;
                        }

                        $itemtype = getItemTypeForTable($table);
                        $item = new $itemtype();
                        $item->getFromDB($data['id']);
                        $percentage  = 0;
                        $totaltime   = 0;
                        $currenttime = 0;
                        $slaField    = 'slas_id';

                        // define correct sla field
                        switch ($table . '.' . $field) {
                            case "glpi_tickets.time_to_resolve":
                                $slaField = 'slas_id_ttr';
                                $sla_class = 'SLA';
                                break;
                            case "glpi_tickets.time_to_own":
                                $slaField = 'slas_id_tto';
                                $sla_class = 'SLA';
                                break;
                            case "glpi_tickets.internal_time_to_own":
                                $slaField = 'olas_id_tto';
                                $sla_class = 'OLA';
                                break;
                            case "glpi_tickets.internal_time_to_resolve":
                                $slaField = 'olas_id_ttr';
                                $sla_class = 'OLA';
                                break;
                        }

                        switch ($table . '.' . $field) {
                            // If ticket has been taken into account : no progression display
                            case "glpi_tickets.time_to_own":
                            case "glpi_tickets.internal_time_to_own":
                                if (($item->fields['takeintoaccount_delay_stat'] > 0)) {
                                    return $out;
                                }
                                break;
                        }

                        if ($item->isField($slaField) && $item->fields[$slaField] != 0) { // Have SLA
                            $sla = new $sla_class();
                            $sla->getFromDB($item->fields[$slaField]);
                            $currenttime = $sla->getActiveTimeBetween(
                                $item->fields['date'],
                                date('Y-m-d H:i:s')
                            );
                            $totaltime   = $sla->getActiveTimeBetween(
                                $item->fields['date'],
                                $data[$ID][0]['name']
                            );
                        } else {
                            $calendars_id = \Entity::getUsedConfig(
                                'calendars_strategy',
                                $item->fields['entities_id'],
                                'calendars_id',
                                0
                            );
                            $calendar = new \Calendar();
                            if ($calendars_id > 0 && $calendar->getFromDB($calendars_id)) { // Ticket entity have calendar
                                $currenttime = $calendar->getActiveTimeBetween(
                                    $item->fields['date'],
                                    date('Y-m-d H:i:s')
                                );
                                $totaltime   = $calendar->getActiveTimeBetween(
                                    $item->fields['date'],
                                    $data[$ID][0]['name']
                                );
                            } else { // No calendar
                                $currenttime = strtotime(date('Y-m-d H:i:s'))
                                    - strtotime($item->fields['date']);
                                $totaltime   = strtotime($data[$ID][0]['name'])
                                    - strtotime($item->fields['date']);
                            }
                        }
                        if ($totaltime != 0) {
                            $percentage  = round((100 * $currenttime) / $totaltime);
                        } else {
                            // Total time is null : no active time
                            $percentage = 100;
                        }
                        if ($percentage > 100) {
                            $percentage = 100;
                        }
                        $percentage_text = $percentage;

                        if ($_SESSION['glpiduedatewarning_unit'] == '%') {
                            $less_warn_limit = $_SESSION['glpiduedatewarning_less'];
                            $less_warn       = (100 - $percentage);
                        } else if ($_SESSION['glpiduedatewarning_unit'] == 'hour') {
                            $less_warn_limit = $_SESSION['glpiduedatewarning_less'] * HOUR_TIMESTAMP;
                            $less_warn       = ($totaltime - $currenttime);
                        } else if ($_SESSION['glpiduedatewarning_unit'] == 'day') {
                            $less_warn_limit = $_SESSION['glpiduedatewarning_less'] * DAY_TIMESTAMP;
                            $less_warn       = ($totaltime - $currenttime);
                        }

                        if ($_SESSION['glpiduedatecritical_unit'] == '%') {
                            $less_crit_limit = $_SESSION['glpiduedatecritical_less'];
                            $less_crit       = (100 - $percentage);
                        } else if ($_SESSION['glpiduedatecritical_unit'] == 'hour') {
                            $less_crit_limit = $_SESSION['glpiduedatecritical_less'] * HOUR_TIMESTAMP;
                            $less_crit       = ($totaltime - $currenttime);
                        } else if ($_SESSION['glpiduedatecritical_unit'] == 'day') {
                            $less_crit_limit = $_SESSION['glpiduedatecritical_less'] * DAY_TIMESTAMP;
                            $less_crit       = ($totaltime - $currenttime);
                        }

                        $color = $_SESSION['glpiduedateok_color'];
                        if ($less_crit < $less_crit_limit) {
                            $color = $_SESSION['glpiduedatecritical_color'];
                        } else if ($less_warn < $less_warn_limit) {
                            $color = $_SESSION['glpiduedatewarning_color'];
                        }

                        if (!isset($so['datatype'])) {
                            $so['datatype'] = 'progressbar';
                        }

                        $progressbar_data = [
                            'text'         => \Html::convDateTime($data[$ID][0]['name']),
                            'percent'      => $percentage,
                            'percent_text' => $percentage_text,
                            'color'        => $color
                        ];
                    }
                    break;

                case "glpi_softwarelicenses.number":
                    if ($data[$ID][0]['min'] == -1) {
                        return __('Unlimited');
                    }
                    if (empty($data[$ID][0]['name'])) {
                        return 0;
                    }
                    return $data[$ID][0]['name'];

                case "glpi_auth_tables.name":
                    return \Auth::getMethodName(
                        $data[$ID][0]['name'],
                        $data[$ID][0]['auths_id'],
                        1,
                        $data[$ID][0]['ldapname'] . $data[$ID][0]['mailname']
                    );

                case "glpi_reservationitems.comment":
                    if (empty($data[$ID][0]['name'])) {
                        $text = __('None');
                    } else {
                        $text = \Html::resume_text($data[$ID][0]['name']);
                    }
                    if (Session::haveRight('reservation', UPDATE)) {
                        return "<a title=\"" . __s('Modify the comment') . "\"
                           href='" . \ReservationItem::getFormURLWithID($data['refID']) . "' >" . $text . "</a>";
                    }
                    return $text;

                case 'glpi_crontasks.description':
                    $tmp = new \CronTask();
                    return $tmp->getDescription($data[$ID][0]['name']);

                case 'glpi_changes.status':
                    $status = \Change::getStatus($data[$ID][0]['name']);
                    return "<span class='text-nowrap'>" .
                        \Change::getStatusIcon($data[$ID][0]['name']) . "&nbsp;$status" .
                        "</span>";

                case 'glpi_problems.status':
                    $status = \Problem::getStatus($data[$ID][0]['name']);
                    return "<span class='text-nowrap'>" .
                        \Problem::getStatusIcon($data[$ID][0]['name']) . "&nbsp;$status" .
                        "</span>";

                case 'glpi_tickets.status':
                    $status = \Ticket::getStatus($data[$ID][0]['name']);
                    return "<span class='text-nowrap'>" .
                        \Ticket::getStatusIcon($data[$ID][0]['name']) . "&nbsp;$status" .
                        "</span>";

                case 'glpi_projectstates.name':
                    $out = '';
                    $name = $data[$ID][0]['name'];
                    if (isset($data[$ID][0]['trans'])) {
                        $name = $data[$ID][0]['trans'];
                    }
                    if ($itemtype == 'ProjectState') {
                        $out =   "<a href='" . \ProjectState::getFormURLWithID($data[$ID][0]["id"]) . "'>" . $name . "</a></div>";
                    } else {
                        $out = $name;
                    }
                    return $out;

                case 'glpi_items_tickets.items_id':
                case 'glpi_items_problems.items_id':
                case 'glpi_changes_items.items_id':
                case 'glpi_certificates_items.items_id':
                case 'glpi_appliances_items.items_id':
                    if (!empty($data[$ID])) {
                        $items = [];
                        foreach ($data[$ID] as $key => $val) {
                            if (is_numeric($key)) {
                                if (
                                    !empty($val['itemtype'])
                                    && ($item = getItemForItemtype($val['itemtype']))
                                ) {
                                    if ($item->getFromDB($val['name'])) {
                                        $items[] = $item->getLink(['comments' => true]);
                                    }
                                }
                            }
                        }
                        if (!empty($items)) {
                            return implode("<br>", $items);
                        }
                    }
                    return '&nbsp;';

                case 'glpi_items_tickets.itemtype':
                case 'glpi_items_problems.itemtype':
                    if (!empty($data[$ID])) {
                        $itemtypes = [];
                        foreach ($data[$ID] as $key => $val) {
                            if (is_numeric($key)) {
                                if (
                                    !empty($val['name'])
                                    && ($item = getItemForItemtype($val['name']))
                                ) {
                                    $item = new $val['name']();
                                    $name = $item->getTypeName();
                                    $itemtypes[] = __($name);
                                }
                            }
                        }
                        if (!empty($itemtypes)) {
                            return implode("<br>", $itemtypes);
                        }
                    }

                    return '&nbsp;';

                case 'glpi_tickets.name':
                case 'glpi_problems.name':
                case 'glpi_changes.name':
                    if (
                        isset($data[$ID][0]['content'])
                        && isset($data[$ID][0]['id'])
                        && isset($data[$ID][0]['status'])
                    ) {
                        $link = $itemtype::getFormURLWithID($data[$ID][0]['id']);

                        $out  = "<a id='$itemtype" . $data[$ID][0]['id'] . "' href=\"" . $link;
                        // Force solution tab if solved
                        /** @var \CommonITILObject $item */
                        if ($item = getItemForItemtype($itemtype)) {
                            if (in_array($data[$ID][0]['status'], $item->getSolvedStatusArray())) {
                                $out .= "&amp;forcetab=$itemtype$2";
                            }
                        }
                        $out .= "\">";
                        $name = $data[$ID][0]['name'];
                        if (
                            $_SESSION["glpiis_ids_visible"]
                            || empty($data[$ID][0]['name'])
                        ) {
                            $name = sprintf(__('%1$s (%2$s)'), $name, $data[$ID][0]['id']);
                        }
                        $out    .= $name . "</a>";
                        $out     = sprintf(
                            __('%1$s %2$s'),
                            $out,
                            \Html::showToolTip(
                                RichText::getEnhancedHtml($data[$ID][0]['content']),
                                [
                                    'applyto'        => $itemtype . $data[$ID][0]['id'],
                                    'display'        => false,
                                    'images_gallery' => false, // don't show photoswipe gallery in tooltips
                                ]
                            )
                        );
                        return $out;
                    }
                    break;

                case 'glpi_ticketvalidations.status':
                    $out   = '';
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if ($data[$ID][$k]['name']) {
                            $status  = \TicketValidation::getStatus($data[$ID][$k]['name']);
                            $bgcolor = \TicketValidation::getStatusColor($data[$ID][$k]['name']);
                            $out    .= (empty($out) ? '' : \Search::LBBR) .
                                "<div style=\"background-color:" . $bgcolor . ";\">" . $status . '</div>';
                        }
                    }
                    return $out;

                case 'glpi_cables.color':
                    //do not display 'real' value (#.....)
                    return "";

                case 'glpi_ticketsatisfactions.satisfaction':
                    if ($html_output) {
                        return \TicketSatisfaction::displaySatisfaction($data[$ID][0]['name']);
                    }
                    break;

                case 'glpi_projects._virtual_planned_duration':
                    return \Html::timestampToString(
                        \ProjectTask::getTotalPlannedDurationForProject($data["id"]),
                        false
                    );

                case 'glpi_projects._virtual_effective_duration':
                    return \Html::timestampToString(
                        \ProjectTask::getTotalEffectiveDurationForProject($data["id"]),
                        false
                    );

                case 'glpi_cartridgeitems._virtual':
                    return \Cartridge::getCount(
                        $data["id"],
                        $data[$ID][0]['alarm_threshold'],
                        !$html_output
                    );

                case 'glpi_printers._virtual':
                    return \Cartridge::getCountForPrinter(
                        $data["id"],
                        !$html_output
                    );

                case 'glpi_consumableitems._virtual':
                    return \Consumable::getCount(
                        $data["id"],
                        $data[$ID][0]['alarm_threshold'],
                        !$html_output
                    );

                case 'glpi_links._virtual':
                    $out = '';
                    $link = new \Link();
                    if (
                        ($item = getItemForItemtype($itemtype))
                        && $item->getFromDB($data['id'])
                    ) {
                        $data = \Link::getLinksDataForItem($item);
                        $count_display = 0;
                        foreach ($data as $val) {
                            $links = \Link::getAllLinksFor($item, $val);
                            foreach ($links as $link) {
                                if ($count_display) {
                                    $out .=  \Search::LBBR;
                                }
                                $out .= $link;
                                $count_display++;
                            }
                        }
                    }
                    return $out;

                case 'glpi_reservationitems._virtual':
                    if ($data[$ID][0]['is_active']) {
                        return "<a href='reservation.php?reservationitems_id=" .
                            $data["refID"] . "' title=\"" . __s('See planning') . "\">" .
                            "<i class='far fa-calendar-alt'></i><span class='sr-only'>" . __('See planning') . "</span></a>";
                    } else {
                        return "&nbsp;";
                    }

                case "glpi_tickets.priority":
                case "glpi_problems.priority":
                case "glpi_changes.priority":
                case "glpi_projects.priority":
                    $index = $data[$ID][0]['name'];
                    $color = $_SESSION["glpipriority_$index"];
                    $name  = \CommonITILObject::getPriorityName($index);
                    return "<div class='priority_block' style='border-color: $color'>
                        <span style='background: $color'></span>&nbsp;$name
                       </div>";

                case "glpi_knowbaseitems.name":
                    global $DB;
                    $result = $DB->request([
                        'SELECT' => [
                            \KnowbaseItem::getTable() . '.is_faq',
                            \KnowbaseItem::getTable() . '.id'
                        ],
                        'FROM'   => \KnowbaseItem::getTable(),
                        'LEFT JOIN' => [
                            \Entity_KnowbaseItem::getTable() => [
                                'ON'  => [
                                    \Entity_KnowbaseItem::getTable() => \KnowbaseItem::getForeignKeyField(),
                                    \KnowbaseItem::getTable()        => 'id'
                                ]
                            ],
                            \KnowbaseItem_Profile::getTable() => [
                                'ON'  => [
                                    \KnowbaseItem_Profile::getTable() => \KnowbaseItem::getForeignKeyField(),
                                    \KnowbaseItem::getTable()         => 'id'
                                ]
                            ],
                            \Group_KnowbaseItem::getTable() => [
                                'ON'  => [
                                    \Group_KnowbaseItem::getTable() => \KnowbaseItem::getForeignKeyField(),
                                    \KnowbaseItem::getTable()       => 'id'
                                ]
                            ],
                            \KnowbaseItem_User::getTable() => [
                                'ON'  => [
                                    \KnowbaseItem_User::getTable() => \KnowbaseItem::getForeignKeyField(),
                                    \KnowbaseItem::getTable()      => 'id'
                                ]
                            ],
                        ],
                        'WHERE'  => [
                            \KnowbaseItem::getTable() . '.id' => $data[$ID][0]['id'],
                            'OR' => [
                                \Entity_KnowbaseItem::getTable() . '.id' => ['>=', 0],
                                \KnowbaseItem_Profile::getTable() . '.id' => ['>=', 0],
                                \Group_KnowbaseItem::getTable() . '.id' => ['>=', 0],
                                \KnowbaseItem_User::getTable() . '.id' => ['>=', 0],
                            ]
                        ],
                    ]);
                    $name = $data[$ID][0]['name'];
                    $fa_class = "";
                    $fa_title = "";
                    $href = \KnowbaseItem::getFormURLWithID($data[$ID][0]['id']);
                    if (count($result) > 0) {
                        foreach ($result as $row) {
                            if ($row['is_faq']) {
                                $fa_class = "fa-question-circle faq";
                                $fa_title = __s("This item is part of the FAQ");
                            }
                        }
                    } else {
                        $fa_class = "fa-eye-slash not-published";
                        $fa_title = __s("This item is not published yet");
                    }
                    return "<div class='kb'> <i class='fa fa-fw $fa_class' title='$fa_title'></i> <a href='$href'>$name</a></div>";
            }
        }

        //// Default case

        if (
            $itemtype == 'Ticket'
            && Session::getCurrentInterface() == 'helpdesk'
            && $orig_id == 8
            && !empty($anon_name = \Group::getAnonymizedName(
                $itemtype::getById($data['id'])->getEntityId()
            ))
        ) {
            // Assigned groups
            return $anon_name;
        }

        // Link with plugin tables : need to know left join structure
        if (isset($table)) {
            if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table . '.' . $field, $matches)) {
                if (count($matches) == 2) {
                    $plug     = $matches[1];
                    $out = \Plugin::doOneHook(
                        $plug,
                        'giveItem',
                        $itemtype,
                        $orig_id,
                        $data,
                        $ID
                    );
                    if (!empty($out)) {
                        return $out;
                    }
                }
            }
        }
        $unit = '';
        if (isset($so['unit'])) {
            $unit = $so['unit'];
        }

        // Preformat items
        if (isset($so["datatype"])) {
            switch ($so["datatype"]) {
                case "itemlink":
                    $linkitemtype  = getItemTypeForTable($so["table"]);

                    $out           = "";
                    $count_display = 0;
                    $separate      = \Search::LBBR;
                    if (isset($so['splititems']) && $so['splititems']) {
                        $separate = \Search::LBHR;
                    }

                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if (isset($data[$ID][$k]['id'])) {
                            if ($count_display) {
                                $out .= $separate;
                            }
                            $count_display++;
                            $page  = $linkitemtype::getFormURLWithID($data[$ID][$k]['id']);
                            $name  = $data[$ID][$k]['name'];
                            if ($_SESSION["glpiis_ids_visible"] || empty($data[$ID][$k]['name'])) {
                                $name = sprintf(__('%1$s (%2$s)'), $name, $data[$ID][$k]['id']);
                            }
                            if ($field === 'completename') {
                                $chunks = preg_split('/ > /', $name);
                                $completename = '';
                                foreach ($chunks as $key => $element_name) {
                                    $class = $key === array_key_last($chunks) ? '' : 'class="text-muted"';
                                    $separator = $key === array_key_last($chunks) ? '' : ' &gt; ';
                                    $completename .= sprintf('<span %s>%s</span>%s', $class, $element_name, $separator);
                                }
                                $name = $completename;
                            }

                            $out  .= "<a id='" . $linkitemtype . "_" . $data['id'] . "_" .
                                $data[$ID][$k]['id'] . "' href='$page'>" .
                                $name . "</a>";
                        }
                    }
                    return $out;

                case "text":
                    $separate = \Search::LBBR;
                    if (isset($so['splititems']) && $so['splititems']) {
                        $separate = \Search::LBHR;
                    }

                    $out           = '';
                    $count_display = 0;
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if (strlen(trim((string)$data[$ID][$k]['name'])) > 0) {
                            if ($count_display) {
                                $out .= $separate;
                            }
                            $count_display++;


                            $plaintext = RichText::getTextFromHtml($data[$ID][$k]['name'], false, true, \Search::$output_type == \Search::HTML_OUTPUT);

                            if ($html_output && (\Toolbox::strlen($plaintext) > $CFG_GLPI['cut'])) {
                                $rand = mt_rand();
                                $popup_params = [
                                    'display'       => false,
                                    'awesome-class' => 'fa-comments',
                                    'autoclose'     => false,
                                    'onclick'       => true,
                                ];
                                $out .= sprintf(
                                    __('%1$s %2$s'),
                                    "<span id='text$rand'>" . \Html::resume_text($plaintext, $CFG_GLPI['cut']) . '</span>',
                                    \Html::showToolTip(
                                        '<div class="fup-popup">' . RichText::getEnhancedHtml($data[$ID][$k]['name']) . '</div>',
                                        $popup_params
                                    )
                                );
                            } else {
                                $out .= $plaintext;
                            }
                        }
                    }
                    return $out;

                case "date":
                case "date_delay":
                    $out   = '';
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if (
                            is_null($data[$ID][$k]['name'])
                            && isset($so['emptylabel']) && $so['emptylabel']
                        ) {
                            $out .= (empty($out) ? '' : \Search::LBBR) . $so['emptylabel'];
                        } else {
                            $out .= (empty($out) ? '' : \Search::LBBR) . \Html::convDate($data[$ID][$k]['name']);
                        }
                    }
                    $out = "<span class='text-nowrap'>$out</span>";
                    return $out;

                case "datetime":
                    $out   = '';
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if (
                            is_null($data[$ID][$k]['name'])
                            && isset($so['emptylabel']) && $so['emptylabel']
                        ) {
                            $out .= (empty($out) ? '' : \Search::LBBR) . $so['emptylabel'];
                        } else {
                            $out .= (empty($out) ? '' : \Search::LBBR) . \Html::convDateTime($data[$ID][$k]['name']);
                        }
                    }
                    $out = "<span class='text-nowrap'>$out</span>";
                    return $out;

                case "timestamp":
                    $withseconds = false;
                    if (isset($so['withseconds'])) {
                        $withseconds = $so['withseconds'];
                    }
                    $withdays = true;
                    if (isset($so['withdays'])) {
                        $withdays = $so['withdays'];
                    }

                    $out   = '';
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        $out .= (empty($out) ? '' : '<br>') . \Html::timestampToString(
                            $data[$ID][$k]['name'],
                            $withseconds,
                            $withdays
                        );
                    }
                    $out = "<span class='text-nowrap'>$out</span>";
                    return $out;

                case "email":
                    $out           = '';
                    $count_display = 0;
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if ($count_display) {
                            $out .= \Search::LBBR;
                        }
                        $count_display++;
                        if (!empty($data[$ID][$k]['name'])) {
                            $out .= (empty($out) ? '' : \Search::LBBR);
                            $out .= "<a href='mailto:" . \Html::entities_deep($data[$ID][$k]['name']) . "'>" . $data[$ID][$k]['name'];
                            $out .= "</a>";
                        }
                    }
                    return (empty($out) ? "&nbsp;" : $out);

                case "weblink":
                    $orig_link = trim((string)$data[$ID][0]['name']);
                    if (!empty($orig_link) && \Toolbox::isValidWebUrl($orig_link)) {
                        // strip begin of link
                        $link = preg_replace('/https?:\/\/(www[^\.]*\.)?/', '', $orig_link);
                        $link = preg_replace('/\/$/', '', $link);
                        if (\Toolbox::strlen($link) > $CFG_GLPI["url_maxlength"]) {
                            $link = \Toolbox::substr($link, 0, $CFG_GLPI["url_maxlength"]) . "...";
                        }
                        return "<a href=\"" . \Toolbox::formatOutputWebLink($orig_link) . "\" target='_blank'>$link</a>";
                    }
                    return "&nbsp;";

                case "count":
                case "number":
                case "mio":
                    $out           = "";
                    $count_display = 0;
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if (strlen(trim((string)$data[$ID][$k]['name'])) > 0) {
                            if ($count_display) {
                                $out .= \Search::LBBR;
                            }
                            $count_display++;
                            if (
                                isset($so['toadd'])
                                && isset($so['toadd'][$data[$ID][$k]['name']])
                            ) {
                                $out .= $so['toadd'][$data[$ID][$k]['name']];
                            } else {
                                $out .= \Dropdown::getValueWithUnit($data[$ID][$k]['name'], $unit);
                            }
                        }
                    }
                    $out = "<span class='text-nowrap'>$out</span>";
                    return $out;

                case "decimal":
                    $out           = "";
                    $count_display = 0;
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if (strlen(trim((string)$data[$ID][$k]['name'])) > 0) {
                            if ($count_display) {
                                $out .= \Search::LBBR;
                            }
                            $count_display++;
                            if (
                                isset($so['toadd'])
                                && isset($so['toadd'][$data[$ID][$k]['name']])
                            ) {
                                $out .= $so['toadd'][$data[$ID][$k]['name']];
                            } else {
                                $out .= \Dropdown::getValueWithUnit($data[$ID][$k]['name'], $unit, $CFG_GLPI["decimal_number"]);
                            }
                        }
                    }
                    $out = "<span class='text-nowrap'>$out</span>";
                    return $out;

                case "bool":
                    $out           = "";
                    $count_display = 0;
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if (strlen(trim((string)$data[$ID][$k]['name'])) > 0) {
                            if ($count_display) {
                                $out .= \Search::LBBR;
                            }
                            $count_display++;
                            $out .= \Dropdown::getYesNo($data[$ID][$k]['name']);
                        }
                    }
                    return $out;

                case "itemtypename":
                    $out           = "";
                    $count_display = 0;
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if ($obj = getItemForItemtype($data[$ID][$k]['name'])) {
                            if ($count_display) {
                                $out .= \Search::LBBR;
                            }
                            $count_display++;
                            $out .= $obj->getTypeName();
                        }
                    }
                    return $out;

                case "language":
                    if (isset($CFG_GLPI['languages'][$data[$ID][0]['name']])) {
                        return $CFG_GLPI['languages'][$data[$ID][0]['name']][0];
                    }
                    return __('Default value');
                case 'progressbar':
                    if (!isset($progressbar_data)) {
                        $bar_color = 'green';
                        $percent   = ltrim(($data[$ID][0]['name'] ?? ""), 0);
                        $progressbar_data = [
                            'percent'      => $percent,
                            'percent_text' => $percent,
                            'color'        => $bar_color,
                            'text'         => ''
                        ];
                    }

                    $out = "";
                    if ($progressbar_data['percent'] !== null) {
                        $out = <<<HTML
                  <span class='text-nowrap'>
                     {$progressbar_data['text']}
                  </span>
                  <div class="progress" style="height: 16px">
                     <div class="progress-bar progress-bar-striped" role="progressbar"
                          style="width: {$progressbar_data['percent']}%; background-color: {$progressbar_data['color']};"
                          aria-valuenow="{$progressbar_data['percent']}"
                          aria-valuemin="0" aria-valuemax="100">
                        {$progressbar_data['percent_text']}%
                     </div>
                  </div>
HTML;
                    }

                    return $out;
                    break;
            }
        }
        // Manage items with need group by / group_concat
        $out           = "";
        $count_display = 0;
        $separate      = \Search::LBBR;
        if (isset($so['splititems']) && $so['splititems']) {
            $separate = \Search::LBHR;
        }

        $aggregate = (isset($so['aggregate']) && $so['aggregate']);

        $append_specific = static function ($specific, $field_data, &$out) use ($so) {
            if (!empty($specific)) {
                $out .= $specific;
            } else if (isset($field_data['values'])) {
                // Aggregate values; No special handling
                return;
            } else {
                if (
                    isset($so['toadd'])
                    && isset($so['toadd'][$field_data['name']])
                ) {
                    $out .= $so['toadd'][$field_data['name']];
                } else {
                    // Empty is 0 or empty
                    if (empty($split[0]) && isset($so['emptylabel'])) {
                        $out .= $so['emptylabel'];
                    } else {
                        // Trans field exists
                        if (isset($field_data['trans']) && !empty($field_data['trans'])) {
                            $out .= $field_data['trans'];
                        } else {
                            $value = $field_data['name'];
                            $out .= $so['field'] === 'completename'
                                ? \CommonTreeDropdown::sanitizeSeparatorInCompletename($value)
                                : $value;
                        }
                    }
                }
            }
        };
        if (isset($table)) {
            $itemtype = getItemTypeForTable($table);
            if ($item = getItemForItemtype($itemtype)) {
                if ($aggregate) {
                    $tmpdata = [
                        'values'     => [],
                    ];
                    foreach ($data[$ID] as $k => $v) {
                        if (is_int($k)) {
                            $tmpdata['values'][$k] = $v;
                        } else {
                            $tmpdata[$k] = $v;
                        }
                    }
                    $specific = $item::getSpecificValueToDisplay(
                        $field,
                        $tmpdata,
                        [
                            'html'      => true,
                            'searchopt' => $so,
                            'raw_data'  => $data
                        ]
                    );

                    $append_specific($specific, $tmpdata, $out);
                } else {
                    $count_display = 0;
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if ($count_display) {
                            $out .= $separate;
                        }
                        $count_display++;
                        $tmpdata = $data[$ID][$k];
                        // Copy name to real field
                        $tmpdata[$field] = $data[$ID][$k]['name'] ?? '';

                        $specific = $item::getSpecificValueToDisplay(
                            $field,
                            $tmpdata,
                            [
                                'html' => true,
                                'searchopt' => $so,
                                'raw_data' => $data
                            ]
                        );

                        $append_specific($specific, $tmpdata, $out);
                    }
                }
            }
        }

        return $out;
    }

    /**
     * @param string $pattern
     * @param string $subject
     * @return string[]|false
     **/
    public static function explodeWithID($pattern, $subject)
    {

        $tab = explode($pattern, $subject);

        if (isset($tab[1]) && !is_numeric($tab[1])) {
            // Report $ to tab[0]
            if (preg_match('/^(\\$*)(.*)/', $tab[1], $matchs)) {
                if (isset($matchs[2]) && is_numeric($matchs[2])) {
                    $tab[1]  = $matchs[2];
                    $tab[0] .= $matchs[1];
                }
            }
        }
        // Manage NULL value
        if ($tab[0] == \Search::NULLVALUE) {
            $tab[0] = null;
        }
        return $tab;
    }
}
