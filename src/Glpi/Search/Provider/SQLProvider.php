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

namespace Glpi\Search\Provider;

use AllAssets;
use Budget;
use Calendar;
use Cartridge;
use Change;
use ChangeSatisfaction;
use CommonDBTM;
use CommonITILObject;
use CommonITILTask;
use CommonITILValidation;
use Config;
use Consumable;
use CronTask;
use DBConnection;
use DBmysql;
use DBmysqlIterator;
use Document;
use Dropdown;
use Entity;
use Entity_KnowbaseItem;
use Glpi\Application\View\TemplateRenderer;
use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\DBAL\QuerySubQuery;
use Glpi\Debug\Profiler;
use Glpi\Features\AssignableItemInterface;
use Glpi\Form\Form;
use Glpi\Plugin\Hooks;
use Glpi\RichText\RichText;
use Glpi\Search\Input\QueryBuilder;
use Glpi\Search\SearchEngine;
use Glpi\Search\SearchOption;
use Glpi\Toolbox\SanitizedStringsDecoder;
use Group;
use Group_Item;
use Group_KnowbaseItem;
use Html;
use ITILCategory;
use ITILFollowup;
use KnowbaseItem;
use KnowbaseItem_Profile;
use KnowbaseItem_User;
use Link;
use Notification;
use OLA;
use Override;
use PlanningExternalEvent;
use Plugin;
use Problem;
use Project;
use ProjectState;
use ProjectTask;
use Reminder;
use Reservation;
use ReservationItem;
use RSSFeed;
use SavedSearch;
use Search;
use Session;
use SLA;
use Software;
use Ticket;
use TicketSatisfaction;
use TicketTask;
use TicketValidation;
use Toolbox;
use User;
use ValidatorSubstitute;

use function Safe\preg_match;
use function Safe\preg_replace;
use function Safe\preg_split;
use function Safe\strtotime;

/**
 *
 * @internal Not for use outside {@link Search} class and the "Glpi\Search" namespace.
 */
final class SQLProvider implements SearchProviderInterface
{
    public static function prepareData(array &$data, array $options = []): array
    {
        self::constructSQL($data);
        self::constructData($data, $options['only_count'] ?? false);
        return $data;
    }

    private static function buildSelect(array $data, string $itemtable): string
    {
        global $DB;

        // request currentuser for SQL supervision, not displayed
        $SELECT = "SELECT DISTINCT `$itemtable`.`id` AS id, " . $DB->quote($_SESSION['glpiname'] ?? '') . " AS currentuser,
                        " . Search::addDefaultSelect($data['itemtype']);

        // Add select for all toview item
        foreach ($data['toview'] as $val) {
            $SELECT .= Search::addSelect($data['itemtype'], $val);
        }

        $as_map = isset($data['search']['as_map']) && (int) $data['search']['as_map'] === 1;
        if ($as_map && $data['itemtype'] !== 'Entity') {
            $SELECT .= ' `glpi_locations`.`id` AS loc_id, ';
        }

        return $SELECT;
    }

    /**
     * Generic function to get the default SELECT criteria for an item type
     * @param class-string<CommonDBTM> $itemtype
     * @return array
     */
    public static function getDefaultSelectCriteria(string $itemtype): array
    {
        global $DB;

        $itemtable = SearchEngine::getOrigTableName($itemtype);
        $item      = null;
        $mayberecursive = false;
        if ($itemtype != AllAssets::getType()) {
            $item           = getItemForItemtype($itemtype);
            $mayberecursive = $item->maybeRecursive();
        }
        $ret = [];
        switch ($itemtype) {
            case 'FieldUnicity':
                $ret[] = "`glpi_fieldunicities`.`itemtype` AS ITEMTYPE";
                break;

            default:
                // Plugin can override core definition for its type
                if ($plug = isPluginItemType($itemtype)) {
                    $default_select = Plugin::doOneHook($plug['plugin'], Hooks::AUTO_ADD_DEFAULT_SELECT, $itemtype);
                    // @FIXME Deprecate string result to expect array|QueryExpression|null
                    if (!empty($default_select)) {
                        $ret[] = new QueryExpression(rtrim($default_select, ' ,'));
                    }
                }
        }
        if ($itemtable === 'glpi_entities') {
            $ret[] = "`$itemtable`.`id` AS entities_id";
            $ret[] = "'1' AS is_recursive";
        } elseif ($mayberecursive) {
            if ($item->isField('entities_id')) {
                $ret[] = $DB::quoteName("$itemtable.entities_id");
            }
            if ($item->isField('is_recursive')) {
                $ret[] = $DB::quoteName("$itemtable.is_recursive");
            }
        }
        return $ret;
    }

    /**
     * Generic function to create SELECT criteria
     *
     * @param class-string<CommonDBTM> $itemtype Item type
     * @param int $ID Search option ID
     * @param bool $meta If true, this is for a meta relation
     * @param string $meta_type Meta item type
     * @return array|QueryExpression
     */
    public static function getSelectCriteria(string $itemtype, int $ID, bool $meta = false, string $meta_type = '')
    {
        global $CFG_GLPI, $DB;

        $opt_arrays = SearchOption::getOptionsForItemtype($itemtype);
        $opt = new SearchOption($opt_arrays[$ID]);
        $table        = $opt["table"];
        $opt_itemtype = $opt['itemtype'] ?? getItemTypeForTable($table);
        $field        = $opt["field"];
        $is_virtual   = $opt->isVirtual();

        $addtable    = "";
        $addtable2   = "";
        $NAME        = "ITEM_{$itemtype}_{$ID}";
        $complexjoin = '';

        if (isset($opt['joinparams'])) {
            $complexjoin = self::computeComplexJoinID($opt['joinparams']);
        }

        $is_fkey_composite_on_self = getTableNameForForeignKeyField($opt["linkfield"]) == $table
            && $opt["linkfield"] != getForeignKeyFieldForTable($table);

        $orig_table = SearchEngine::getOrigTableName($itemtype);
        if (
            ((($is_fkey_composite_on_self || $table != $orig_table)
                    && (!isset($CFG_GLPI["union_search_type"][$itemtype])
                        || ($CFG_GLPI["union_search_type"][$itemtype] != $table)))
                || !empty($complexjoin))
            && ($opt["linkfield"] != getForeignKeyFieldForTable($table))
        ) {
            $addtable .= "_" . $opt["linkfield"];
        }

        if (!empty($complexjoin)) {
            $addtable .= "_" . $complexjoin;
            $addtable2 .= "_" . $complexjoin;
        }

        $addmeta = "";
        if ($meta) {
            $addmeta = self::getMetaTableUniqueSuffix($table, $meta_type);
            $addtable  .= $addmeta;
            $addtable2 .= $addmeta;
        }

        // Plugin can override core definition for its type
        if ($plug = isPluginItemType($itemtype)) {
            $out = Plugin::doOneHook($plug['plugin'], Hooks::AUTO_ADD_SELECT, $itemtype, $ID, "{$itemtype}_{$ID}");
            // @FIXME Deprecate string result to expect array|QueryExpression|null
            if (!empty($out)) {
                return new QueryExpression($out);
            }
        }

        $tocompute      = "$table$addtable.$field";
        $tocomputeid    = "$table$addtable.id";
        $tocomputetrans = QueryFunction::ifnull("{$table}{$addtable}_trans_{$field}.value", new QueryExpression($DB::quoteValue(Search::NULLVALUE)));

        $ADDITONALFIELDS = [];
        if (
            isset($opt["additionalfields"])
            && count($opt["additionalfields"])
        ) {
            foreach ($opt["additionalfields"] as $key) {
                if (preg_match('/^TABLE\./', $key) === 1) {
                    $key = preg_replace('/^TABLE\./', '', $key);
                    $additionalfield_field = $orig_table . '.' . $key;
                } else {
                    $additionalfield_field = $table . $addtable . '.' . $key;
                }
                if ($meta || $opt->isForceGroupBy()) {
                    $ADDITONALFIELDS[] = QueryFunction::ifnull(
                        expression: QueryFunction::groupConcat(
                            expression: QueryFunction::concat([
                                QueryFunction::ifnull(
                                    expression: $additionalfield_field,
                                    value: new QueryExpression($DB::quoteValue(Search::NULLVALUE))
                                ),
                                new QueryExpression($DB::quoteValue(Search::SHORTSEP)),
                                $tocomputeid,
                            ]),
                            separator: Search::LONGSEP,
                            distinct: true,
                            order_by: $tocomputeid
                        ),
                        value: new QueryExpression($DB::quoteValue(Search::NULLVALUE)),
                        alias: "{$NAME}_{$key}"
                    );
                } else {
                    $ADDITONALFIELDS[] = $DB::quoteName("{$additionalfield_field} AS {$NAME}_{$key}");
                }
            }
        }

        // Virtual display no select: only get additional fields
        if ($is_virtual) {
            return $ADDITONALFIELDS;
        }

        switch ($table . "." . $field) {
            case "glpi_users.name":
                if ($itemtype !== User::class) {
                    if ($opt->isForceGroupBy()) {
                        $addaltemail = "";
                        if (
                            in_array($itemtype, [Ticket::class, Change::class, Problem::class])
                            && isset($opt['joinparams']['beforejoin']['table'])
                            && in_array($opt['joinparams']['beforejoin']['table'], ['glpi_tickets_users', 'glpi_changes_users', 'glpi_problems_users'])
                        ) { // For tickets_users
                            $before_join = $opt['joinparams']['beforejoin'];
                            $ticket_user_table = $before_join['table'] . "_" . Search::computeComplexJoinID($before_join['joinparams']) . $addmeta;
                            $addaltemail = QueryFunction::groupConcat(
                                expression: QueryFunction::concat([
                                    "{$ticket_user_table}.users_id",
                                    new QueryExpression($DB::quoteValue(' ')),
                                    "{$ticket_user_table}.alternative_email",
                                ]),
                                separator: Search::LONGSEP,
                                distinct: true,
                                alias: "{$NAME}_2"
                            );
                        }
                        $SELECT = [
                            QueryFunction::groupConcat(
                                expression: "{$table}{$addtable}.id",
                                separator: Search::LONGSEP,
                                distinct: true,
                                alias: $NAME
                            ),
                        ];
                        if (!empty($addaltemail)) {
                            $SELECT[] = $addaltemail;
                        }
                        return array_merge($SELECT, $ADDITONALFIELDS);
                    }
                    $SELECT = [
                        $DB::quoteName("$table$addtable.$field AS {$NAME}"),
                        $DB::quoteName("$table$addtable.realname AS {$NAME}_realname"),
                        $DB::quoteName("$table$addtable.id AS {$NAME}_id"),
                        $DB::quoteName("$table$addtable.firstname AS {$NAME}_firstname"),
                    ];
                    return array_merge($SELECT, $ADDITONALFIELDS);
                }
                break;

            case "glpi_softwarelicenses.number":
                $_table_add_table = $table . ($meta ? $addtable2 : $addtable);
                $SELECT = [
                    QueryFunction::floor(
                        expression: new QueryExpression(QueryFunction::sum("{$_table_add_table}.{$field}") . ' * '
                            . QueryFunction::count("{$_table_add_table}.id", true) . ' / '
                            . QueryFunction::count("{$_table_add_table}.id")),
                        alias: $NAME
                    ),
                    QueryFunction::min("{$_table_add_table}.{$field}", "{$NAME}_min"),
                ];
                return array_merge($SELECT, $ADDITONALFIELDS);

            case "glpi_profiles.name":
                if ($itemtype === User::class && $ID === 20) {
                    $addtable2 = '';
                    if ($meta) {
                        $addtable2 = "_" . $meta_type;
                    }
                    $SELECT = [
                        QueryFunction::groupConcat(
                            expression: QueryFunction::concat([
                                "{$table}{$addtable}.{$field}",
                                new QueryExpression($DB::quoteValue(Search::SHORTSEP)),
                                "glpi_profiles_users{$addtable2}.entities_id",
                                new QueryExpression($DB::quoteValue(Search::SHORTSEP)),
                                "glpi_profiles_users{$addtable2}.is_recursive",
                                new QueryExpression($DB::quoteValue(Search::SHORTSEP)),
                                "glpi_profiles_users{$addtable2}.is_dynamic",
                            ]),
                            distinct: true,
                            separator: Search::LONGSEP,
                            alias: $NAME
                        ),
                    ];
                    return array_merge($SELECT, $ADDITONALFIELDS);
                }
                break;

            case "glpi_entities.completename":
                if ($itemtype === User::class && $ID === 80) {
                    $addtable2 = '';
                    if ($meta) {
                        $addtable2 = "_" . $meta_type;
                    }
                    $SELECT = [
                        QueryFunction::groupConcat(
                            expression: QueryFunction::concat([
                                "{$table}{$addtable}.completename",
                                new QueryExpression($DB::quoteValue(Search::SHORTSEP)),
                                "glpi_profiles_users{$addtable2}.entities_id",
                                new QueryExpression($DB::quoteValue(Search::SHORTSEP)),
                                "glpi_profiles_users{$addtable2}.is_recursive",
                                new QueryExpression($DB::quoteValue(Search::SHORTSEP)),
                                "glpi_profiles_users{$addtable2}.is_dynamic",
                            ]),
                            distinct: true,
                            separator: Search::LONGSEP,
                            alias: $NAME
                        ),
                    ];
                    return array_merge($SELECT, $ADDITONALFIELDS);
                }
                break;

            case "glpi_softwareversions.name":
                if ($meta && $meta_type === Software::class) {
                    $SELECT = [
                        QueryFunction::groupConcat(
                            expression: QueryFunction::concat([
                                "glpi_softwares.name",
                                new QueryExpression($DB::quoteValue(" - ")),
                                "{$table}{$addtable2}.{$field}",
                                new QueryExpression($DB::quoteValue(Search::SHORTSEP)),
                                "{$table}{$addtable2}.id",
                            ]),
                            separator: Search::LONGSEP,
                            distinct: true,
                            alias: $NAME
                        ),
                    ];
                    return array_merge($SELECT, $ADDITONALFIELDS);
                }
                break;

            case "glpi_softwareversions.comment":
                $_table = ($meta && $meta_type === Software::class) ? 'glpi_softwares' : ($table . $addtable);
                $_table_add_table = $table . (($meta && $meta_type === Software::class) ? $addtable2 : $addtable);
                $SELECT = [
                    QueryFunction::groupConcat(
                        expression: QueryFunction::concat([
                            "{$_table}.name",
                            new QueryExpression($DB::quoteValue(" - ")),
                            "{$_table_add_table}.{$field}",
                            new QueryExpression($DB::quoteValue(Search::SHORTSEP)),
                            "{$_table_add_table}.id",
                        ]),
                        separator: Search::LONGSEP,
                        distinct: true,
                        alias: $NAME
                    ),
                ];
                return array_merge($SELECT, $ADDITONALFIELDS);

            case "glpi_states.name":
                if ($meta && $meta_type === Software::class) {
                    $SELECT = [
                        QueryFunction::groupConcat(
                            expression: QueryFunction::concat([
                                "glpi_softwares.name",
                                new QueryExpression($DB::quoteValue(" - ")),
                                "glpi_softwareversions{$addtable}.name",
                                new QueryExpression($DB::quoteValue(" - ")),
                                "{$table}{$addtable2}.{$field}",
                                new QueryExpression($DB::quoteValue(Search::SHORTSEP)),
                                "{$table}{$addtable2}.id",
                            ]),
                            separator: Search::LONGSEP,
                            distinct: true,
                            alias: $NAME
                        ),
                    ];
                    return array_merge($SELECT, $ADDITONALFIELDS);
                } elseif ($meta_type === Software::class) {
                    $SELECT = [
                        QueryFunction::groupConcat(
                            expression: QueryFunction::concat([
                                "glpi_softwareversions.name",
                                new QueryExpression($DB::quoteValue(" - ")),
                                "{$table}{$addtable}.{$field}",
                                new QueryExpression($DB::quoteValue(Search::SHORTSEP)),
                                "{$table}{$addtable}.id",
                            ]),
                            separator: Search::LONGSEP,
                            distinct: true,
                            alias: $NAME
                        ),
                    ];
                    return array_merge($SELECT, $ADDITONALFIELDS);
                }
                break;

            case "glpi_itilfollowups.content":
            case "glpi_tickettasks.content":
            case "glpi_changetasks.content":
                if (is_subclass_of($itemtype, CommonITILObject::class)) {
                    // force ordering by date desc
                    $SELECT = [
                        QueryFunction::groupConcat(
                            expression: QueryFunction::concat([
                                QueryFunction::ifnull($tocompute, new QueryExpression($DB::quoteValue(Search::NULLVALUE))),
                                new QueryExpression($DB::quoteValue(Search::SHORTSEP)),
                                $tocomputeid,
                            ]),
                            separator: Search::LONGSEP,
                            distinct: true,
                            order_by: "{$table}{$addtable}.date DESC",
                            alias: $NAME
                        ),
                    ];
                    return array_merge($SELECT, $ADDITONALFIELDS);
                }
                break;

            default:
                break;
        }

        //// Default cases
        // Link with plugin tables
        $plugin_table_pattern = "/^glpi_plugin_([a-z0-9]+)/";
        if (preg_match($plugin_table_pattern, $table, $matches) && count($matches) === 2) {
            $plug     = $matches[1];
            $out = Plugin::doOneHook($plug, Hooks::AUTO_ADD_SELECT, $itemtype, $ID, "{$itemtype}_{$ID}");
            // @FIXME Deprecate string result to expect array|QueryExpression|null
            if (!empty($out)) {
                return [new QueryExpression($out)];
            }
        }

        if (isset($opt["computation"])) {
            $tocompute = $opt["computation"];
            $tocompute = str_replace($DB::quoteName('TABLE'), 'TABLE', $tocompute);
            $tocompute = new QueryExpression(str_replace("TABLE", $DB::quoteName("$table$addtable"), $tocompute));
        } else {
            $tocompute = new QueryExpression($DB::quoteName($tocompute));
        }
        // Preformat items
        if (isset($opt["datatype"])) {
            switch ($opt["datatype"]) {
                case "count":
                    return array_merge([
                        QueryFunction::count(
                            expression: "$table$addtable.$field",
                            distinct: true,
                            alias: $NAME
                        ),
                    ], $ADDITONALFIELDS);

                case "date_delay":
                    $interval = $opt['delayunit'] ?? "MONTH";

                    $add_minus = '';
                    if (isset($opt["datafields"][3])) {
                        $add_minus = '-' . $DB::quoteName("{$table}{$addtable}.{$opt['datafields'][3]}");
                    }
                    if ($meta || $opt->isForceGroupBy()) {
                        return array_merge([
                            QueryFunction::groupConcat(
                                expression: QueryFunction::dateAdd(
                                    date: "{$table}{$addtable}.{$opt['datafields'][1]}",
                                    interval: new QueryExpression($DB::quoteName("{$table}{$addtable}.{$opt['datafields'][2]}") . $add_minus),
                                    interval_unit: $interval
                                ),
                                separator: Search::LONGSEP,
                                distinct: true,
                                alias: $NAME
                            ),
                        ], $ADDITONALFIELDS);
                    }
                    return array_merge([
                        QueryFunction::dateAdd(
                            date: "{$table}{$addtable}.{$opt['datafields'][1]}",
                            interval: new QueryExpression($DB::quoteName("{$table}{$addtable}.{$opt['datafields'][2]}") . $add_minus),
                            interval_unit: $interval,
                            alias: $NAME
                        ),
                    ], $ADDITONALFIELDS);

                case "itemlink":
                    if ($meta || $opt->isForceGroupBy()) {
                        $TRANS = '';
                        if (Session::haveTranslations($opt_itemtype, $field)) {
                            $TRANS = QueryFunction::groupConcat(
                                expression: QueryFunction::concat([
                                    QueryFunction::ifnull($tocomputetrans, new QueryExpression($DB::quoteValue(Search::NULLVALUE))),
                                    new QueryExpression($DB::quoteValue(Search::SHORTSEP)),
                                    $tocomputeid,
                                ]),
                                separator: Search::LONGSEP,
                                distinct: true,
                                order_by: $tocomputeid,
                                alias: "{$NAME}_trans_{$field}"
                            );
                        }
                        $SELECT = [
                            QueryFunction::groupConcat(
                                expression: QueryFunction::concat([
                                    $tocompute,
                                    new QueryExpression($DB::quoteValue(Search::SHORTSEP)),
                                    "{$table}{$addtable}.id",
                                ]),
                                separator: Search::LONGSEP,
                                distinct: true,
                                order_by: "{$table}{$addtable}.id",
                                alias: $NAME
                            ),
                        ];
                        if (!empty($TRANS)) {
                            $SELECT[] = $TRANS;
                        }
                        return array_merge($SELECT, $ADDITONALFIELDS);
                    }
                    return array_merge([
                        $tocompute . ' AS ' . $DB::quoteName($NAME),
                        $DB::quoteName("{$table}{$addtable}.id AS {$NAME}_id"),
                    ], $ADDITONALFIELDS);
            }
        }

        // Default case
        if (
            $meta
            || ($opt->isForceGroupBy()
                && (!isset($opt["computation"])
                    || $opt->isComputationGroupBy()))
        ) { // Not specific computation
            $TRANS = '';
            if (Session::haveTranslations($opt_itemtype, $field)) {
                $TRANS = QueryFunction::groupConcat(
                    expression: QueryFunction::concat([
                        QueryFunction::ifnull($tocomputetrans, new QueryExpression($DB::quoteValue(Search::NULLVALUE))),
                        new QueryExpression($DB::quoteValue(Search::SHORTSEP)),
                        $tocomputeid,
                    ]),
                    separator: $DB::quoteValue(Search::LONGSEP),
                    distinct: true,
                    order_by: $tocomputeid,
                    alias: "{$NAME}_trans_{$field}"
                );
            }
            $SELECT = [
                QueryFunction::groupConcat(
                    expression: QueryFunction::concat([
                        QueryFunction::ifnull($tocompute, new QueryExpression($DB::quoteValue(Search::NULLVALUE))),
                        new QueryExpression($DB::quoteValue(Search::SHORTSEP)),
                        $tocomputeid,
                    ]),
                    separator: Search::LONGSEP,
                    distinct: true,
                    order_by: $tocomputeid,
                    alias: $NAME
                ),
            ];
            if (!empty($TRANS)) {
                $SELECT[] = $TRANS;
            }
            return array_merge($SELECT, $ADDITONALFIELDS);
        }
        $SELECT = [
            new QueryExpression($tocompute, $NAME),
        ];
        if (Session::haveTranslations($opt_itemtype, $field)) {
            $SELECT[] = "$tocomputetrans AS " . $DB::quoteName("{$NAME}_trans_{$field}");
        }
        return array_merge($SELECT, $ADDITONALFIELDS);
    }

    private static function buildFrom(string $itemtable): string
    {
        return " FROM `$itemtable`";
    }

    /**
     * Generic Function to add default where to a request
     *
     * @param class-string<CommonDBTM> $itemtype device type
     *
     * @return array WHERE criteria array
     **/
    public static function getDefaultWhereCriteria(string $itemtype): array
    {
        global $CFG_GLPI;

        $criteria = [];

        switch ($itemtype) {
            case Reservation::class:
                $criteria = getEntitiesRestrictCriteria(ReservationItem::getTable(), '', '', true);
                break;

            case Reminder::class:
                $criteria = Reminder::getVisibilityCriteria()['WHERE'];
                break;

            case RSSFeed::class:
                $criteria = RSSFeed::getVisibilityCriteria()['WHERE'];
                break;

            case Notification::class:
                if (!Config::canView()) {
                    $criteria = [
                        'NOT' => ['glpi_notifications.itemtype' => ['CronTask', 'DBConnection']],
                    ];
                }
                break;

                // No link
            case User::class:
                // View all entities
                if (!Session::canViewAllEntities()) {
                    $criteria = getEntitiesRestrictCriteria("glpi_profiles_users", '', '', true);
                }
                break;

            case ProjectTask::class:
                if (!Session::haveRightsOr('project', [Project::READALL, Project::READMY])) {
                    // Can only see the tasks assigned to the user or one of his groups
                    $teamtable = 'glpi_projecttaskteams';
                    $group_criteria = [];
                    if (count($_SESSION['glpigroups'])) {
                        $group_criteria = [
                            "$teamtable.itemtype" => Group::class,
                            "$teamtable.items_id" => $_SESSION['glpigroups'],
                        ];
                    }
                    $user_criteria = [
                        "$teamtable.itemtype" => User::class,
                        "$teamtable.items_id" => Session::getLoginUserID(),
                    ];
                    $criteria = [
                        "glpi_projects.is_template" => 0,
                        'OR' => [
                            $user_criteria,
                        ],
                    ];
                    if ($group_criteria !== []) {
                        $criteria['OR'][] = $group_criteria;
                    }
                } elseif (Session::haveRight('project', Project::READMY)) {
                    // User must be the manager, in the manager group or in the project team
                    $teamtable = 'glpi_projectteams';
                    $group_criteria = [];
                    if (count($_SESSION['glpigroups'])) {
                        $group_criteria = [
                            "$teamtable.itemtype" => Group::class,
                            "$teamtable.items_id" => $_SESSION['glpigroups'],
                        ];
                    }
                    $user_criteria = [
                        "$teamtable.itemtype" => User::class,
                        "$teamtable.items_id" => Session::getLoginUserID(),
                    ];
                    $criteria = [
                        'OR' => [
                            $user_criteria,
                            'glpi_projects.users_id' => Session::getLoginUserID(),
                        ],
                    ];
                    if ($group_criteria !== []) {
                        $criteria['OR'][] = $group_criteria;
                    }
                }
                break;

            case Project::class:
                if (!Session::haveRight("project", Project::READALL)) {
                    $teamtable  = 'glpi_projectteams';
                    $user_criteria = [
                        "$teamtable.itemtype" => User::class,
                        "$teamtable.items_id" => Session::getLoginUserID(),
                    ];
                    $group_criteria = [
                        "$teamtable.itemtype" => Group::class,
                        "$teamtable.items_id" => $_SESSION['glpigroups'],
                    ];
                    $criteria = [
                        "OR" => [
                            "glpi_projects.users_id" => Session::getLoginUserID(),
                            $user_criteria,
                            "glpi_projects.groups_id" => $_SESSION['glpigroups'],
                            $group_criteria,
                        ],
                    ];
                }
                break;

            case Ticket::class:
                // Same structure in addDefaultJoin
                if (!Session::haveRight("ticket", Ticket::READALL)) {
                    $searchopt
                        = SearchOption::getOptionsForItemtype($itemtype);
                    $requester_table
                        = '`glpi_tickets_users_'
                        . self::computeComplexJoinID($searchopt[4]['joinparams']['beforejoin']
                        ['joinparams']) . '`';
                    $requestergroup_table
                        = '`glpi_groups_tickets_'
                        . self::computeComplexJoinID($searchopt[71]['joinparams']['beforejoin']
                        ['joinparams']) . '`';

                    $assign_table
                        = '`glpi_tickets_users_'
                        . self::computeComplexJoinID($searchopt[5]['joinparams']['beforejoin']
                        ['joinparams']) . '`';
                    $assigngroup_table
                        = '`glpi_groups_tickets_'
                        . self::computeComplexJoinID($searchopt[8]['joinparams']['beforejoin']
                        ['joinparams']) . '`';

                    $observer_table
                        = '`glpi_tickets_users_'
                        . self::computeComplexJoinID($searchopt[66]['joinparams']['beforejoin']
                        ['joinparams']) . '`';
                    $observergroup_table
                        = '`glpi_groups_tickets_'
                        . self::computeComplexJoinID($searchopt[65]['joinparams']['beforejoin']
                        ['joinparams']) . '`';

                    $condition = "(";

                    $criteria = [
                        'OR' => [],
                    ];
                    if (Session::haveRight("ticket", Ticket::READMY)) {
                        $criteria['OR'][] = [
                            'OR' => [
                                "$requester_table.users_id" => Session::getLoginUserID(),
                                "$observer_table.users_id" => Session::getLoginUserID(),
                                "glpi_tickets.users_id_recipient" => Session::getLoginUserID(),
                            ],
                        ];
                    } else {
                        $criteria['OR'][] = new QueryExpression('false');
                    }

                    if (Session::haveRight("ticket", Ticket::READGROUP)) {
                        if (count($_SESSION['glpigroups'])) {
                            $criteria['OR'][] = [
                                'OR' => [
                                    "$requestergroup_table.groups_id" => $_SESSION['glpigroups'],
                                    "$observergroup_table.groups_id" => $_SESSION['glpigroups'],
                                ],
                            ];
                        }
                    }

                    if (Session::haveRight("ticket", Ticket::OWN)) {// Can own ticket: show assign to me
                        $criteria['OR'][] = [
                            "$assign_table.users_id" => Session::getLoginUserID(),
                        ];
                    }

                    if (Session::haveRight("ticket", Ticket::READASSIGN)) { // assign to me
                        $criteria['OR'][] = [
                            "$assign_table.users_id" => Session::getLoginUserID(),
                        ];
                        if (count($_SESSION['glpigroups'])) {
                            $criteria['OR'][] = [
                                "$assigngroup_table.groups_id" => $_SESSION['glpigroups'],
                            ];
                        }
                    }

                    if (Session::haveRight('ticket', Ticket::READNEWTICKET)) {
                        $criteria['OR'][] = [
                            'glpi_tickets.status' => CommonITILObject::INCOMING,
                        ];
                    }

                    if (
                        Session::haveRightsOr(
                            'ticketvalidation',
                            [
                                TicketValidation::VALIDATEINCIDENT,
                                TicketValidation::VALIDATEREQUEST,
                            ]
                        )
                    ) {
                        $criteria['OR'][] = [
                            'AND' => [
                                "`glpi_ticketvalidations`.`itemtype_target`" => User::class,
                                "`glpi_ticketvalidations`.`items_id_target`" => Session::getLoginUserID(),
                            ],
                        ];
                        if (count($_SESSION['glpigroups'])) {
                            $criteria['OR'][] = [
                                'AND' => [
                                    "`glpi_ticketvalidations`.`itemtype_target`" => Group::class,
                                    "`glpi_ticketvalidations`.`items_id_target`" => $_SESSION['glpigroups'],
                                ],
                            ];
                        }
                    }
                }
                break;

            case Change::class:
            case Problem::class:
                if ($itemtype === Change::class) {
                    $right       = 'change';
                    $table       = 'changes';
                    $groupetable = "`glpi_changes_groups_";
                } elseif ($itemtype === Problem::class) {
                    $right       = 'problem';
                    $table       = 'problems';
                    $groupetable = "`glpi_groups_problems_";
                }
                // Same structure in addDefaultJoin
                $condition = '';
                if (!Session::haveRight("$right", $itemtype::READALL)) {
                    $criteria = [
                        'OR' => [],
                    ];

                    $searchopt       = SearchOption::getOptionsForItemtype($itemtype);
                    if (Session::haveRight("$right", $itemtype::READMY)) {
                        $requester_table      = '`glpi_' . $table . '_users_'
                            . self::computeComplexJoinID($searchopt[4]['joinparams']
                            ['beforejoin']['joinparams']) . '`';
                        $requestergroup_table = $groupetable
                            . self::computeComplexJoinID($searchopt[71]['joinparams']
                            ['beforejoin']['joinparams']) . '`';

                        $observer_table       = '`glpi_' . $table . '_users_'
                            . self::computeComplexJoinID($searchopt[66]['joinparams']
                            ['beforejoin']['joinparams']) . '`';
                        $observergroup_table  = $groupetable
                            . self::computeComplexJoinID($searchopt[65]['joinparams']
                            ['beforejoin']['joinparams']) . '`';

                        $assign_table         = '`glpi_' . $table . '_users_'
                            . self::computeComplexJoinID($searchopt[5]['joinparams']
                            ['beforejoin']['joinparams']) . '`';
                        $assigngroup_table    = $groupetable
                            . self::computeComplexJoinID($searchopt[8]['joinparams']
                            ['beforejoin']['joinparams']) . '`';

                        $criteria['OR'][] = [
                            'OR' => [
                                "$requester_table.users_id" => Session::getLoginUserID(),
                                "$observer_table.users_id" => Session::getLoginUserID(),
                                "$assign_table.users_id" => Session::getLoginUserID(),
                                "glpi_" . $table . ".users_id_recipient" => Session::getLoginUserID(),
                            ],
                        ];
                        if (count($_SESSION['glpigroups'])) {
                            $criteria['OR'][] = [
                                'OR' => [
                                    "$requestergroup_table.groups_id" => $_SESSION['glpigroups'],
                                    "$observergroup_table.groups_id" => $_SESSION['glpigroups'],
                                    "$assigngroup_table.groups_id" => $_SESSION['glpigroups'],
                                ],
                            ];
                        }
                    } else {
                        $criteria['OR'][] = new QueryExpression('false');
                    }
                }
                break;

            case Config::class:
                $availableContexts = array_merge(['core', 'inventory'], Plugin::getPlugins());
                $criteria = ["`context`" => $availableContexts];
                break;

            case SavedSearch::class:
                $criteria = SavedSearch::getVisibilityCriteria()['WHERE'];
                break;

            case TicketTask::class:
                // Filter on is_private
                $allowed_is_private = [];
                if (Session::haveRight(TicketTask::$rightname, CommonITILTask::SEEPRIVATE)) {
                    $allowed_is_private[] = 1;
                }
                if (Session::haveRight(TicketTask::$rightname, CommonITILTask::SEEPUBLIC)) {
                    $allowed_is_private[] = 0;
                }

                // If the user can't see public and private
                if (!count($allowed_is_private)) {
                    $criteria = new QueryExpression('false');
                    break;
                }

                $criteria = [
                    'OR' => [
                        'glpi_tickettasks.is_private' => $allowed_is_private,
                        // Check for assigned or created tasks
                        'glpi_tickettasks.users_id' => Session::getLoginUserID(),
                        'glpi_tickettasks.users_id_tech' => Session::getLoginUserID(),
                    ],
                ];

                // Check for parent item visibility unless the user can see all the
                // possible parents
                if (!Session::haveRight('ticket', Ticket::READALL)) {
                    $criteria[] = [
                        new QueryExpression(TicketTask::buildParentCondition()),
                    ];
                }

                break;

            case ITILFollowup::class:
                // Filter on is_private
                $allowed_is_private = [];
                if (Session::haveRight(ITILFollowup::$rightname, ITILFollowup::SEEPRIVATE)) {
                    $allowed_is_private[] = 1;
                }
                if (Session::haveRight(ITILFollowup::$rightname, ITILFollowup::SEEPUBLIC)) {
                    $allowed_is_private[] = 0;
                }

                // If the user can't see public and private
                if (!count($allowed_is_private)) {
                    $criteria = new QueryExpression('false');
                    break;
                }

                $criteria = [
                    'glpi_itilfollowups.is_private' => $allowed_is_private,
                    'OR' => [
                        new QueryExpression(ITILFollowup::buildParentCondition(Ticket::getType())),
                        new QueryExpression(ITILFollowup::buildParentCondition(
                            Change::getType(),
                            'changes_id',
                            "glpi_changes_users",
                            "glpi_changes_groups"
                        )),
                        new QueryExpression(ITILFollowup::buildParentCondition(
                            Problem::getType(),
                            'problems_id',
                            "glpi_problems_users",
                            "glpi_problems_groups"
                        )),
                    ],
                ];

                // Entity restrictions
                $entity_restrictions = [];
                foreach ($CFG_GLPI['itil_types'] as $itil_itemtype) {
                    $entity_restrictions[] = getEntitiesRestrictCriteria(
                        $itil_itemtype::getTable() . '_items_id_' . self::computeComplexJoinID([
                            'condition' => ['REFTABLE.itemtype' => $itil_itemtype],
                        ]),
                        'entities_id',
                        ''
                    );
                }
                if ($entity_restrictions !== []) {
                    $criteria[] = ['OR' => $entity_restrictions];
                }

                break;

            case PlanningExternalEvent::class:
                $criteria = PlanningExternalEvent::getVisibilityCriteria();
                break;

            case ValidatorSubstitute::class:
                if (Session::getLoginUserID() !== false) {
                    $criteria = ['users_id' => Session::getLoginUserID()];
                }

                break;

            case KnowbaseItem::class:
                $criteria = KnowbaseItem::getVisibilityCriteria(false)['WHERE'];
                break;

            case Form::class:
                // Do not show unsaved drafts in the form list
                $criteria = ['is_draft' => 0];
                break;

            default:
                // Plugin can override core definition for its type
                if ($plug = isPluginItemType($itemtype)) {
                    $default_where = Plugin::doOneHook($plug['plugin'], Hooks::AUTO_ADD_DEFAULT_WHERE, $itemtype);
                    // @FIXME Deprecate string result to expect array|QueryExpression|null
                    if (!empty($default_where)) {
                        $criteria = is_array($default_where) ? $default_where : [new QueryExpression($default_where)];
                    }
                }
                break;
        }

        $item = getItemForItemtype($itemtype);
        if ($item instanceof AssignableItemInterface) {
            $visibility_criteria = $item::getAssignableVisiblityCriteria();
            if (count($visibility_criteria)) {
                $criteria[] = $visibility_criteria;
            }
        }

        /* Hook to restrict user right on current itemtype */
        [$itemtype, $criteria] = Plugin::doHookFunction(Hooks::ADD_DEFAULT_WHERE, [$itemtype, $criteria]);
        return $criteria;
    }

    /**
     * Return where part related to system criteria of main itemtype.
     *
     * @param string $itemtype  Main itemtype
     *
     * @return string
     */
    private static function getMainItemtypeSystemSQLCriteria(string $itemtype): string
    {
        global $DB;

        if (!is_a($itemtype, CommonDBTM::class, true)) {
            return '';
        }

        $criteria = $itemtype::getSystemSQLCriteria($itemtype::getTable());

        if (count($criteria) === 0) {
            return '';
        }

        $dbi = new DBmysqlIterator($DB);
        return $dbi->analyseCrit($criteria);
    }

    public static function getWhereCriteria($nott, $itemtype, $ID, $searchtype, $val, $meta = 0): ?array
    {
        global $DB;

        $searchopt = SearchOption::getOptionsForItemtype($itemtype);
        if (!isset($searchopt[$ID]['table'])) {
            return [];
        }
        $opt = new SearchOption($searchopt[$ID]);

        $table     = $opt["table"];
        $field     = $opt["field"];

        if (
            $searchtype == 'contains'
            && !preg_match(QueryBuilder::getInputValidationPattern($opt['datatype'] ?? '')['pattern'], $val)
        ) {
            return [ // Invalid search
                new QueryExpression('false'),
            ];
        }

        $inittable = $table;
        $addtable  = '';
        $is_fkey_composite_on_self = getTableNameForForeignKeyField($opt["linkfield"]) === $table
            && $opt["linkfield"] !== getForeignKeyFieldForTable($table);
        $orig_table = SearchEngine::getOrigTableName($itemtype);
        if (
            ($table !== 'asset_types')
            && ($is_fkey_composite_on_self || $table !== $orig_table)
            && ($opt["linkfield"] !== getForeignKeyFieldForTable($table))
        ) {
            $addtable = "_" . $opt["linkfield"];
            $table   .= $addtable;
        }

        if (isset($opt['joinparams'])) {
            $complexjoin = Search::computeComplexJoinID($opt['joinparams']);

            if (!empty($complexjoin)) {
                $table .= "_" . $complexjoin;
            }
        }

        $addmeta = "";
        if ($meta) {
            $addmeta = self::getMetaTableUniqueSuffix($inittable, $itemtype);
            $table .= $addmeta;
        }

        // Hack to allow search by ID on every sub-table
        if (preg_match('/^\$\$\$\$([0-9]+)$/', $val, $regs)) {
            $criteria = [
                'OR' => [
                    "table.id" => [$nott ? "<>" : "=", $regs[1]],
                ],
            ];
            if ((int) $regs[1] === 0) {
                $criteria['OR'][] = ["table.id" =>  "IS NULL"];
            }
            return $criteria;
        }

        $SEARCH = [];
        $RAW_SEARCH = null;

        // Is the current criteria on a linked children item ? (e.g. search
        // option 65 for CommonITILObjects)
        // These search options will need an additionnal subquery in their WHERE
        // clause to ensure accurate results
        // See https://github.com/glpi-project/glpi/pull/13684 for detailed examples
        $should_use_subquery = $opt["use_subquery"] ?? false;
        // Default mode for most search types that use a subquery
        $use_subquery_on_id_search = false;
        // Special case for "contains" or "not contains" search type
        $use_subquery_on_text_search = false;
        // Special case when searching for an user (need to compare with login, firstname, ...)
        $subquery_specific_username = false;
        // The subquery operator will be "IN" or "NOT IN" depending on the context and criteria
        $subquery_operator = "";
        $subquery_specific_username_firstname_real_name = [];
        $subquery_specific_username_anonymous = [];

        // Preparse value
        if (isset($opt["datatype"])) {
            switch ($opt["datatype"]) {
                case "datetime":
                case "date":
                case "date_delay":
                    $force_day = true;
                    if (
                        $opt["datatype"] === 'datetime'
                        && !(str_contains($val, 'BEGIN') || str_contains($val, 'LAST') || str_contains($val, 'DAY'))
                    ) {
                        $force_day = false;
                    }

                    $val = Html::computeGenericDateTimeSearch($val, $force_day);

                    break;
            }
        }

        $SEARCH = [];
        switch ($searchtype) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case "notcontains":
                $nott = !$nott;
                //negated, use contains case
                // no break
            case "contains":
                // FIXME
                // `field LIKE '%test%'` condition is not supposed to be relevant, and can sometimes result in SQL performances issues/warnings/errors,
                // or at least to unexpected results, when following datatype are used:
                //  - integer
                //  - number
                //  - decimal
                //  - count
                //  - mio
                //  - percentage
                //  - timestamp
                //  - datetime
                //  - date_delay
                //  - mac
                //  - color
                //  - language
                // Values should be filtered to accept only valid pattern according to given datatype.

                if (isset($opt["datatype"]) && ($opt["datatype"] === 'decimal')) {
                    $matches = [];
                    if (preg_match('/^(\d+.?\d?)/', $val, $matches)) {
                        $val = $matches[1];
                        if (!str_contains($val, '.')) {
                            $val .= '.';
                        }
                    }
                }
                if ($should_use_subquery) {
                    // Subquery will be needed to get accurate results
                    $use_subquery_on_text_search = true;
                    // Potential negation will be handled by the subquery operator
                    $SEARCH = ["LIKE", self::makeTextSearchValue($val)];
                    $subquery_operator = $nott ? "NOT IN" : "IN";
                } else {
                    $SEARCH = [$nott ? "NOT LIKE" : "LIKE", self::makeTextSearchValue($val)];
                }
                break;

            case "equals":
                if ($should_use_subquery) {
                    // Subquery will be needed to get accurate results
                    $use_subquery_on_id_search = true;
                    // Potential negation will be handled by the subquery operator
                    $SEARCH = ["=", $val];
                    $subquery_operator = $nott ? "NOT IN" : "IN";
                } else {
                    // Default case
                    $SEARCH = [$nott ? "<>" : "=", $val];
                }
                break;

            case "notequals":
                if ($should_use_subquery) {
                    // Subquery will be needed to get accurate results
                    $use_subquery_on_id_search = true;
                    // Potential negation will be handled by the subquery operator
                    $SEARCH = ["=", $val];
                    $subquery_operator = $nott ? "IN" : "NOT IN";
                } else {
                    // Default case
                    $SEARCH = [$nott ? "=" : "<>", $val];
                }
                break;

            case "under":
                // Sometimes $val is not numeric (mygroups)
                // In this case we must set an invalid value and let the related
                // specific code handle in later on
                $sons = is_numeric($val) ? getSonsOf($inittable, $val) : 'not yet set';
                if ($should_use_subquery) {
                    // Subquery will be needed to get accurate results
                    $use_subquery_on_id_search = true;
                    // Potential negation will be handled by the subquery operatorAdd commentMore actions
                    $SEARCH = ["IN", $sons];
                    $subquery_operator = $nott ? "NOT IN" : "IN";
                } else {
                    // Default case
                    $SEARCH = [$nott ? "NOT IN" : "IN", $sons];
                }
                break;

            case "notunder":
                // Sometimes $val is not numeric (mygroups)
                // In this case we must set an invalid value and let the related
                // specific code handle in later on
                $sons = is_numeric($val) ? getSonsOf($inittable, $val) : 'not yet set';
                if ($should_use_subquery) {
                    // Subquery will be needed to get accurate results
                    $use_subquery_on_id_search = true;
                    // Potential negation will be handled by the subquery operator
                    $SEARCH = ["IN", $sons];
                    $subquery_operator = $nott ? "IN" : "NOT IN";
                } else {
                    // Default case
                    $SEARCH = [$nott ? "IN" : "NOT IN", $sons];
                }
                break;

            case "empty":
                if ($nott) {
                    $RAW_SEARCH = "%s IS NOT NULL";
                } else {
                    $SEARCH = null;
                }
                break;
        }

        //Check in current item if a specific where is defined
        if (method_exists($itemtype, 'addWhere')) {
            $out = $itemtype::addWhere('AND', $nott, $itemtype, $ID, $searchtype, $val);
            // Remove 'AND' from the beginning of the string. There may be extra spaces around it.
            $out = preg_replace('/^\s*AND\s*/', '', $out);
            if (!empty($out)) {
                return [new QueryExpression($out)];
            }
        }

        // Plugin can override core definition for its type
        if ($plug = isPluginItemType($itemtype)) {
            $out = Plugin::doOneHook(
                $plug['plugin'],
                Hooks::AUTO_ADD_WHERE,
                '',
                $nott,
                $itemtype,
                $ID,
                $val,
                $searchtype
            );
            // @FIXME Deprecate string result to expect array|QueryExpression|null
            if (!empty($out)) {
                return is_array($out) ? $out : [new QueryExpression($out)];
            }
        }

        /**
         * @param array &$criteria
         * @param string|QueryExpression $value
         * @return void
         * @note 'use' parameters are bound at the time the function is declared. To account for changes to the search parameters later, we need to pass the arrays by reference.
         */
        $append_criterion_with_search = static function (array &$criteria, $value) use (&$SEARCH, &$RAW_SEARCH, $DB): void {
            if ($RAW_SEARCH !== null) {
                $criteria[] = new QueryExpression(sprintf($RAW_SEARCH, $value));
            }
            if ($SEARCH === []) {
                return;
            }
            if (is_a($value, QueryExpression::class)) {
                $search_str = '';
                $iterator = new DBmysqlIterator(null);
                foreach ($SEARCH as $token) {
                    if ($iterator->isOperator($token)) {
                        $search_str .= " $token ";
                    } else {
                        $search_str .= $DB::quoteValue($token);
                    }
                }
                $criteria[] = new QueryExpression($value . ' ' . trim($search_str));
            } elseif (isset($criteria[$value])) {
                $criteria[] = [$value => $SEARCH];
            } else {
                $criteria[$value] = $SEARCH;
            }
        };
        switch ($inittable . "." . $field) {
            // case "glpi_users_validation.name" :

            case "glpi_users.name":
                if ($val === 'myself') {
                    switch ($searchtype) {
                        case 'equals':
                            $SEARCH = ['=', $_SESSION['glpiID']];
                            break;

                        case 'notequals':
                            if ($use_subquery_on_id_search) {
                                // Potential negation will be handled by the subquery operator
                                $SEARCH = ['=', $_SESSION['glpiID']];
                            } else {
                                $SEARCH = ['<>', $_SESSION['glpiID']];
                            }
                            break;
                    }
                }

                if ($itemtype === User::class) { // glpi_users case / not link table
                    $criteria = [
                        'OR' => [],
                    ];
                    if (in_array($searchtype, ['equals', 'notequals'])) {
                        $append_criterion_with_search($criteria['OR'], "$table.id");

                        if ($searchtype === 'notequals') {
                            $nott = !$nott;
                        }

                        // Add NULL if $val = 0 and not negative search
                        // Or negative search on real value
                        if ((!$nott && ($val == 0)) || ($nott && ($val != 0))) {
                            $criteria['OR'][] = ["$table.id" => null];
                        }

                        return $criteria;
                    }
                    return [new QueryExpression(self::makeTextCriteria("`$table`.`$field`", $val, $nott, ''))];
                }
                if ($_SESSION["glpinames_format"] == User::FIRSTNAME_BEFORE) {
                    $name1 = 'firstname';
                    $name2 = 'realname';
                } else {
                    $name1 = 'realname';
                    $name2 = 'firstname';
                }

                if (in_array($searchtype, ['equals', 'notequals'])) {
                    break;
                } elseif ($searchtype === 'empty') {
                    $criteria = [];
                    $append_criterion_with_search($criteria, "$table.id");
                    return $criteria;
                }
                $toadd   = '';

                $tmplink = 'OR';
                if ($nott) {
                    $tmplink = 'AND';
                }

                if (is_a($itemtype, CommonITILObject::class, true)) {
                    $itil_user_tables = ['glpi_tickets_users', 'glpi_changes_users', 'glpi_problems_users'];
                    $has_join         = isset($opt["joinparams"]["beforejoin"]["table"], $opt["joinparams"]["beforejoin"]["joinparams"]);
                    if ($has_join && in_array($opt["joinparams"]["beforejoin"]["table"], $itil_user_tables, true)) {
                        $bj        = $opt["joinparams"]["beforejoin"];
                        $linktable = $bj['table'] . '_' . Search::computeComplexJoinID($bj['joinparams']) . $addmeta;
                        //$toadd     = "`$linktable`.`alternative_email` $SEARCH $tmplink ";
                        $toadd     = self::makeTextCriteria(
                            "`$linktable`.`alternative_email`",
                            $val,
                            $nott,
                            $tmplink
                        );
                        // Remove $tmplink (may have spaces around it) from front of $toadd
                        $toadd = preg_replace('/^\s*' . preg_quote($tmplink, '/') . '\s*/', '', $toadd);
                        if ($val === '^$') {
                            return [
                                'OR' => [
                                    "$linktable.users_id" => null,
                                    "$linktable.alternative_email" => null,
                                ],
                            ];
                        }
                    }
                }
                if ($use_subquery_on_text_search) {
                    $subquery_specific_username = true;
                    $subquery_specific_username_firstname_real_name = [
                        'OR' => [
                            $name1 => $SEARCH,
                            $name2 => $SEARCH,
                            'RAW'  => [
                                (string) QueryFunction::concat([
                                    new QueryExpression("`$name1`"),
                                    new QueryExpression(new QueryExpression($DB::quoteValue(' '))),
                                    new QueryExpression("`$name2`"),
                                ]) => $SEARCH,
                            ],
                        ],
                    ];
                    $subquery_specific_username_anonymous = [
                        'alternative_email' => ['LIKE', self::makeTextSearchValue($val)],
                    ];
                    break;
                } else {
                    $criteria = [
                        $tmplink => [],
                    ];
                    $append_criterion_with_search($criteria[$tmplink], "$table.$name1");
                    $append_criterion_with_search($criteria[$tmplink], "$table.$name2");
                    $append_criterion_with_search($criteria[$tmplink], "$table.$field");
                    $append_criterion_with_search(
                        $criteria[$tmplink],
                        QueryFunction::concat([
                            "$table.$name1",
                            new QueryExpression($DB::quoteValue(' ')),
                            "$table.$name2",
                        ])
                    );

                    if ($nott && ($val !== 'NULL') && ($val !== 'null')) {
                        $criteria = [
                            $tmplink => [
                                'OR' => [
                                    $criteria,
                                    "$table.$field" => null,
                                ],
                                new QueryExpression($toadd),
                            ],
                        ];
                    }
                    return $criteria;
                }

                // no break
            case "glpi_groups.completename":
                if ($val === 'mygroups') {
                    switch ($searchtype) {
                        case 'equals':
                            if (count($_SESSION['glpigroups']) === 0) {
                                return [];
                            } else {
                                $SEARCH = ['IN', $_SESSION['glpigroups']];
                            }
                            break;

                        case 'notequals':
                            if (count($_SESSION['glpigroups']) === 0) {
                                return [];
                            } else {
                                if ($use_subquery_on_id_search) {
                                    // Potential negation will be handled by the subquery operator
                                    $SEARCH = ['IN', $_SESSION['glpigroups']];
                                } else {
                                    $SEARCH = ['NOT IN', $_SESSION['glpigroups']];
                                }
                            }
                            break;

                        case 'under':
                            if (count($_SESSION['glpigroups']) === 0) {
                                return [];
                            }
                            $groups = $_SESSION['glpigroups'];
                            foreach ($_SESSION['glpigroups'] as $g) {
                                $groups += getSonsOf($inittable, $g);
                            }
                            $groups = array_unique($groups);
                            $SEARCH = ['IN', $groups];
                            break;

                        case 'notunder':
                            if (count($_SESSION['glpigroups']) === 0) {
                                return [];
                            }
                            $groups = $_SESSION['glpigroups'];
                            foreach ($_SESSION['glpigroups'] as $g) {
                                $groups += getSonsOf($inittable, $g);
                            }
                            $groups = array_unique($groups);
                            if ($use_subquery_on_id_search) {
                                // Potential negation will be handled by the subquery operatorAdd commentMore actions
                                $SEARCH = ['IN', $groups];
                            } else {
                                $SEARCH = ['NOT IN', $groups];
                            }
                            break;

                        case 'empty':
                            $criteria = [];
                            $append_criterion_with_search($criteria, "$table.id");
                            return $criteria;
                    }
                }
                break;

            case "glpi_ipaddresses.name":
                if (preg_match("/^\s*([<>])([=]*)[[:space:]]*([0-9\.]+)/", $val, $regs)) {
                    if ($nott) {
                        if ($regs[1] == '<') {
                            $regs[1] = '>';
                        } else {
                            $regs[1] = '<';
                        }
                    }
                    $regs[1] .= $regs[2];
                    return [new QueryExpression("(INET_ATON(`$table`.`$field`) " . $regs[1] . " INET_ATON('" . $regs[3] . "'))")];
                }
                break;

            case "glpi_tickets.status":
            case "glpi_problems.status":
            case "glpi_changes.status":
                $tocheck = [];
                $item = getItemForItemtype($itemtype);
                if ($item instanceof CommonITILObject) {
                    switch ($val) {
                        case 'process':
                            $tocheck = $item->getProcessStatusArray();
                            break;

                        case 'notclosed':
                            $tocheck = $item::getAllStatusArray();
                            foreach ($item::getClosedStatusArray() as $status) {
                                if (isset($tocheck[$status])) {
                                    unset($tocheck[$status]);
                                }
                            }
                            $tocheck = array_keys($tocheck);
                            break;

                        case 'old':
                            $tocheck = array_merge(
                                $item::getSolvedStatusArray(),
                                $item::getClosedStatusArray()
                            );
                            break;

                        case 'notold':
                            $tocheck = $item::getNotSolvedStatusArray();
                            break;

                        case 'all':
                            $tocheck = array_keys($item::getAllStatusArray());
                            break;
                    }

                    if (count($tocheck) === 0) {
                        $statuses = $item::getAllStatusArray();
                        if (isset($statuses[$val])) {
                            $tocheck = [$val];
                        }
                    }
                }

                if (count($tocheck)) {
                    if ($nott) {
                        return [
                            "$table.$field" => ['NOT IN', $tocheck],
                        ];
                    }
                    return [
                        "$table.$field" => $tocheck,
                    ];
                }
                break;

            case "glpi_tickets_tickets.tickets_id_1":
                $tmplink = 'OR';
                $compare = '=';
                if ($nott) {
                    $tmplink = 'AND';
                    $compare = '<>';
                }

                $criteria = [
                    $tmplink => [
                        "$table.tickets_id_1" => [$compare, $val],
                        "$table.tickets_id_2" => [$compare, $val],
                    ],
                ];

                if (
                    $nott
                    && ($val != 'NULL') && ($val != 'null')
                ) {
                    $criteria = [
                        'OR' => [
                            $criteria,
                            "$table.$field" => null,
                        ],
                    ];
                }

                return $criteria;

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
                        return [
                            "$table.$field" => [$compare, $val],
                        ];
                    }
                    if ($val < 0) {
                        $compare = ($nott ? '<' : '>=');
                        return [
                            "$table.$field" => [$compare, abs($val)],
                        ];
                    }
                    // Show all
                    $compare = ($nott ? '<' : '>=');
                    return [
                        "$table.$field" => [$compare, 0],
                    ];
                }
                return [];

            case "glpi_tickets.global_validation":
            case "glpi_ticketvalidations.status":
            case "glpi_changes.global_validation":
            case "glpi_changevalidations.status":
                if ($val !== 'can' && !is_numeric($val)) {
                    return [];
                }
                $tocheck = [];
                if ($val === 'can') {
                    $tocheck = CommonITILValidation::getCanValidationStatusArray();
                } else {
                    $tocheck = [$val];
                }
                if ($nott) {
                    return [
                        "$table.$field" => ['NOT IN', $tocheck],
                    ];
                }
                return [
                    "$table.$field" => $tocheck,
                ];

            case "glpi_notifications.event":
                if (in_array($searchtype, ['equals', 'notequals']) && strpos($val, Search::SHORTSEP)) {
                    $not = 'notequals' === $searchtype ? 'NOT' : '';
                    [$itemtype_val, $event_val] = explode(Search::SHORTSEP, $val);
                    $criteria = [
                        "$table.event" => $event_val,
                        "$table.itemtype" => $itemtype_val,
                    ];
                    if ($not) {
                        $criteria = ['NOT' => $criteria];
                    }
                    return $criteria;
                }
                break;
        }

        //// Default cases

        // Link with plugin tables
        if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $inittable, $matches)) {
            if (count($matches) == 2) {
                $plug     = $matches[1];
                $out = Plugin::doOneHook(
                    $plug,
                    Hooks::AUTO_ADD_WHERE,
                    '',
                    $nott,
                    $itemtype,
                    $ID,
                    $val,
                    $searchtype
                );
                // @FIXME Deprecate string result to expect array|QueryExpression|null
                if (!empty($out)) {
                    return is_array($out) ? $out : [new QueryExpression($out)];
                }
            }
        }

        $tocompute      = "`$table`.`$field`";
        $tocomputetrans = "`" . $table . "_trans_" . $field . "`.`value`";
        if (isset($opt["computation"])) {
            $is_query_exp = is_a($opt["computation"], QueryExpression::class);
            $tocompute = $opt["computation"];
            $tocompute = str_replace($DB::quoteName('TABLE'), 'TABLE', $tocompute);
            $tocompute = str_replace("TABLE", $DB::quoteName("$table"), $tocompute);
            if ($is_query_exp) {
                $tocompute = new QueryExpression($tocompute);
            }
        }

        // Preformat items
        if (isset($opt["datatype"])) {
            if ($opt["datatype"] === "mio") {
                // Parse value as it may contain a few different formats
                $val = Toolbox::getMioSizeFromString($val);
            }

            switch ($opt["datatype"]) {
                case "itemtypename":
                    if (in_array($searchtype, ['equals', 'notequals'])) {
                        $criteria = [];
                        $append_criterion_with_search($criteria, "$table.$field");
                        return $criteria;
                    }
                    break;

                case "itemlink":
                    if ($should_use_subquery) {
                        // Condition will be handled by the subquery
                        break;
                    }
                    if (in_array($searchtype, ['equals', 'notequals', 'under', 'notunder', 'empty'])) {
                        if ($searchtype === 'empty' && $opt["field"] === 'name') {
                            $l = $nott ? 'AND' : 'OR';
                            $criteria = [
                                $l => [
                                    [
                                        $tocompute => [$nott ? '<>' : '=', ''],
                                    ],
                                ],
                            ];
                            $append_criterion_with_search($criteria[$l], $tocompute);
                        } else {
                            $criteria = [];
                            $append_criterion_with_search($criteria, "$table.id");
                        }
                        return $criteria;
                    }
                    break;

                case "datetime":
                case "date":
                case "date_delay":
                    if ($opt["datatype"] === 'datetime') {
                        // Specific search for datetime
                        if (in_array($searchtype, ['equals', 'notequals'])) {
                            $val = preg_replace("/:00$/", '', $val);
                            $val = '^' . $val;
                            if ($searchtype === 'notequals') {
                                $nott = !$nott;
                            }
                            return [new QueryExpression(self::makeTextCriteria("`$table`.`$field`", $val, $nott, ''))];
                        }
                    }
                    if ($searchtype === 'lessthan') {
                        $val = '<' . $val;
                    }
                    if ($searchtype === 'morethan') {
                        $val = '>' . $val;
                    }
                    $date_computation = null;
                    if ($searchtype) {
                        $date_computation = $tocompute;
                    }
                    if (!isset($opt["computation"]) && in_array($searchtype, ["contains", "notcontains"])) {
                        // FIXME Maybe address the existing fixme instead of bypassing it when the field is computed (uses a function)
                        // FIXME `CONVERT` operation should not be necessary if we only allow legitimate date/time chars
                        $default_charset = DBConnection::getDefaultCharset();
                        $date_computation = QueryFunction::convert($date_computation, $default_charset);
                    }
                    $search_unit = $opt['searchunit'] ?? 'MONTH';
                    if ($opt["datatype"] === "date_delay") {
                        $delay_unit = $opt['delayunit'] ?? 'MONTH';
                        $add_minus = '';
                        if (isset($opt["datafields"][3])) {
                            $add_minus = '-' . $DB::quoteName($table . '.' . $opt["datafields"][3]);
                        }
                        $date_computation = QueryFunction::dateAdd(
                            date: "$table." . $opt["datafields"][1],
                            interval: new QueryExpression($DB::quoteName("$table." . $opt["datafields"][2]) . $add_minus),
                            interval_unit: $delay_unit
                        );
                    }
                    if (in_array($searchtype, ['equals', 'notequals', 'empty'])) {
                        $criteria = [];
                        if (!empty($date_computation)) {
                            $append_criterion_with_search($criteria, $date_computation);
                        }
                        return $criteria;
                    }
                    if (preg_match("/^\s*([<>])(=?)(.+)$/", $val, $regs)) {
                        $numeric_matches = [];
                        if (preg_match('/^\s*(-?)\s*([0-9]+(.[0-9]+)?)\s*$/', $regs[3], $numeric_matches)) {
                            if ($searchtype === "notcontains") {
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
                            return [
                                new QueryExpression("$date_computation " . $regs[1] . " "
                                    . QueryFunction::dateAdd(
                                        date: QueryFunction::now(),
                                        interval: new QueryExpression($numeric_matches[1] . $numeric_matches[2]),
                                        interval_unit: $search_unit
                                    )),
                            ];
                        }
                        // ELSE Reformat date if needed
                        $regs[3] = preg_replace(
                            '@(\d{1,2})(-|/)(\d{1,2})(-|/)(\d{4})@',
                            '\5-\3-\1',
                            $regs[3]
                        );
                        if (preg_match('/[0-9]{2,4}-[0-9]{1,2}-[0-9]{1,2}/', $regs[3])) {
                            $ret = '';
                            if ($nott) {
                                $ret .= " NOT(";
                            }
                            $ret .= " $date_computation {$regs[1]}{$regs[2]} '{$regs[3]}'";
                            if ($nott) {
                                $ret .= ")";
                            }
                            return [
                                new QueryExpression($ret),
                            ];
                        }
                        return [];
                    }
                    // ELSE standard search
                    // Date format modification if needed
                    $val = preg_replace('@(\d{1,2})(-|/)(\d{1,2})(-|/)(\d{4})@', '\5-\3-\1', $val);
                    if ($date_computation) {
                        return [
                            new QueryExpression(self::makeTextCriteria($date_computation, $val, $nott, '')),
                        ];
                    }
                    return [];

                case "right":
                    if ($searchtype == 'notequals') {
                        $nott = !$nott;
                    }
                    $criteria = [$tocompute => ['&', $val]];
                    return $nott ? ['NOT' => $criteria] : $criteria;

                case "bool":
                    if (!is_numeric($val)) {
                        if (strcasecmp($val, __('No')) == 0) {
                            $val = 0;
                        } elseif (strcasecmp($val, __('Yes')) == 0) {
                            $val = 1;
                        }
                    }
                    // no break here : use number comparaison case

                case "count":
                case "mio":
                case "number":
                case "integer":
                case "decimal":
                case "timestamp":
                case "progressbar":
                    $decimal_contains = $searchopt[$ID]["datatype"] === 'decimal' && $searchtype === 'contains';

                    if (preg_match("/([<>])(=?)[[:space:]]*(-?)[[:space:]]*([0-9]+(.[0-9]+)?)/", $val, $regs)) {
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
                        return [new QueryExpression("$tocompute {$regs[1]} {$regs[3]}{$regs[4]}")];
                    }

                    if (is_numeric($val) && !$decimal_contains) {
                        $numeric_val = floatval($val);

                        if (in_array($searchtype, ["notequals", "notcontains"])) {
                            $nott = !$nott;
                        }

                        if (isset($opt["width"])) {
                            $ADD = [];
                            if (
                                $nott
                                && ($val != 'NULL') && ($val != 'null')
                            ) {
                                $ADD = [new QueryExpression("$tocompute IS NULL")];
                            }
                            $val1 = $numeric_val - $searchopt[$ID]["width"];
                            $val2 = $numeric_val + $searchopt[$ID]["width"];
                            if ($nott) {
                                return [
                                    'OR' => array_merge(
                                        [
                                            new QueryExpression("$tocompute < $val1"),
                                            new QueryExpression("$tocompute > $val2"),
                                        ],
                                        $ADD
                                    ),
                                ];
                            }
                            return array_merge(
                                [
                                    new QueryExpression("$tocompute >= $val1"),
                                    new QueryExpression("$tocompute <= $val2"),
                                ],
                                $ADD
                            );
                        }
                        if (!$nott) {
                            return [new QueryExpression($tocompute . ' = ' . $numeric_val)];
                        }
                        return [new QueryExpression($tocompute . ' <> ' . $numeric_val)];
                    }

                    if ($searchtype === 'empty') {
                        $l = $nott ? 'AND' : 'OR';
                        $operator = $nott ? '<>' : '=';
                        $criteria = [
                            $l => [
                                [
                                    new QueryExpression("$tocompute $operator 0"),
                                ],
                            ],
                        ];
                        $append_criterion_with_search($criteria[$l], $tocompute);
                        return $criteria;
                    }
                    break;

                case 'text':
                    if ($searchtype === 'empty') {
                        $l = $nott ? 'AND' : 'OR';
                        $criteria = [
                            $l => [
                                [
                                    $tocompute => [$nott ? '<>' : '=', ''],
                                ],
                            ],
                        ];
                        $append_criterion_with_search($criteria[$l], $tocompute);
                    }
                    break;
            }
        }

        // Using subquery in the WHERE clause
        if ($use_subquery_on_id_search || $use_subquery_on_text_search) {
            // Compute tables and fields names
            $main_table = getTableForItemType($itemtype);
            $fk = getForeignKeyFieldForTable($main_table);
            $beforejoin = $opt['joinparams']['beforejoin'];
            $child_table = $opt['table'];
            $link_table = $beforejoin['table'];
            $linked_fk = $beforejoin['joinparams']['linkfield'] ?? getForeignKeyFieldForTable($opt['table']);

            // Handle extra condition (e.g. filtering group type)
            $addcondition = '';

            if (isset($beforejoin['joinparams']['condition'])) {
                $placeholders = [
                    '`REFTABLE`' => "`$main_table`",
                    'REFTABLE'   => "`$main_table`",
                    '`NEWTABLE`' => "`$link_table`",
                    'NEWTABLE'   => "`$link_table`",
                ];

                // Recursively walk through add_criteria array and make the placeholder replacements in the keys and values
                $replace_placeholders = static function ($add_criteria) use (&$replace_placeholders, $placeholders) {
                    $new_criteria = [];
                    foreach ($add_criteria as $key => $value) {
                        $new_key = strtr($key, $placeholders);
                        $replaced_key = (string) $new_key !== (string) $key;

                        if (is_array($value)) {
                            $new_criteria[$new_key] = $replace_placeholders($value);
                        } elseif (is_a($value, QueryExpression::class)) {
                            $value_string = $value->getValue();
                            $new_value = strtr($value_string, $placeholders);
                            $new_criteria[$new_key] = new QueryExpression($new_value);
                        } elseif ($value !== null) {
                            $new_criteria[$new_key] = strtr($value, $placeholders);
                        } else {
                            $new_criteria[$new_key] = $value;
                        }

                        if ($replaced_key) {
                            unset($new_criteria[$key]);
                        }
                    }
                    return $new_criteria;
                };

                $addcondition = $replace_placeholders($beforejoin['joinparams']['condition']);
            }

            // If the purpose of the search is to verify whether an item of type ‘itemtype1’ is related to an item of type ‘itemtype2’
            // (e.g. : computer and application) and that this relationship is saved in a table via a pair (‘items_id’, ‘itemtype’),
            // the target field in the relational table will not be `items_id` but `itemtype2_id`.

            // This will result in an error, because the `itemtype2_id` does not exist.
            // To resolve this issue, the name of the target field must be explicitly declared in order to correctly retrieve the ID of `itemtype'.
            if ($beforejoin['table'] === $link_table && isset($beforejoin['joinparams']['field'])) {
                $fk = $beforejoin['joinparams']['field'];
            }

            $criteria = [];
            if ($use_subquery_on_id_search) {
                // Subquery for "Is not", "Not + is", "Not under" and "Not + Under" search types
                // As an example, when looking for tickets that don't have a
                // given observer group (id = 4), $out will look like this:
                //
                // AND `glpi_tickets`.`id` NOT IN (
                //     SELECT `tickets_id`
                //     FROM `glpi_groups_tickets`
                //     WHERE `groups_id` = '4' AND `glpi_groups_tickets`.`type` = '3'
                // )

                if ($val == 0) {
                    // Special case, search criteria is empty
                    $subquery_operator = $subquery_operator === "IN" ? "NOT IN" : "IN";
                    $subquery_criteria_where = [new QueryExpression('true')];
                    if (!empty($addcondition)) {
                        $subquery_criteria_where[] = $addcondition;
                    }

                    $criteria = [
                        "$main_table.id" => [
                            $subquery_operator,
                            new QuerySubQuery([
                                'SELECT' => $fk,
                                'FROM'   => $link_table,
                                'WHERE'  => $subquery_criteria_where,
                            ]),
                        ],
                    ];
                } else {
                    $sub_query_criteria = [
                        'SELECT' => $fk,
                        'FROM'   => $link_table,
                        'WHERE'  => [new QueryExpression('true')],
                    ];
                    if (!empty($addcondition)) {
                        $sub_query_criteria['WHERE'][] = $addcondition;
                    }
                    $append_criterion_with_search(
                        $sub_query_criteria['WHERE'],
                        "$linked_fk"
                    );

                    $criteria = [
                        "$main_table.id" => [$subquery_operator, new QuerySubQuery($sub_query_criteria)],
                    ];
                }
            } elseif ($use_subquery_on_text_search) {
                // Subquery for "Not contains" and "Not + contains" search types
                // As an example, when looking for tickets that don't have a
                // given observer group (name = "groupname"), $out will look like this:
                //
                // AND `glpi_tickets`.`id` NOT IN (
                //      SELECT `tickets_id`
                //      FROM `glpi_groups_tickets`
                //      WHERE `groups_id` IN (
                //          SELECT `id`
                //          FROM `glpi_groups`
                //          WHERE `completename`LIKE '%groupname%'
                //      ) AND `glpi_groups_tickets`.`type` = '3'
                // )

                if ($subquery_specific_username) {
                    $inner_subquery_criteria = [
                        'SELECT' => 'id',
                        'FROM'   => $child_table,
                        'WHERE'  => [
                            'OR' => [$subquery_specific_username_firstname_real_name],
                        ],
                    ];
                    $append_criterion_with_search(
                        $inner_subquery_criteria['WHERE']['OR'],
                        "$field"
                    );
                    $subquery_criteria_where = [
                        'OR' => [
                            "$linked_fk" => new QuerySubQuery($inner_subquery_criteria),
                            $subquery_specific_username_anonymous,
                        ],
                    ];
                    if (!empty($addcondition)) {
                        $subquery_criteria_where[] = $addcondition;
                    }
                    $criteria = [
                        "$main_table.id" => [
                            $subquery_operator,
                            new QuerySubQuery([
                                'SELECT' => $fk,
                                'FROM'   => $link_table,
                                'WHERE'  => $subquery_criteria_where,
                            ]),
                        ],
                    ];

                } else {
                    $inner_subquery_criteria = [
                        'SELECT' => 'id',
                        'FROM'   => $child_table,
                        'WHERE'  => [new QueryExpression('true')],
                    ];
                    if (!empty($addcondition)) {
                        $inner_subquery_criteria['WHERE'][] = $addcondition;
                    }
                    $append_criterion_with_search(
                        $inner_subquery_criteria['WHERE'],
                        "$field"
                    );
                    $subquery_criteria_where = [
                        "$linked_fk" => new QuerySubQuery($inner_subquery_criteria),
                    ];
                    if (!empty($addcondition)) {
                        $subquery_criteria_where[] = $addcondition;
                    }
                    $criteria = [
                        "$main_table.id" => [
                            $subquery_operator,
                            new QuerySubQuery([
                                'SELECT' => $fk,
                                'FROM'   => $link_table,
                                'WHERE'  => $subquery_criteria_where,
                            ]),
                        ],
                    ];
                }
            }
            return ['OR' => [$criteria]];
        }

        // Default case
        if (in_array($searchtype, ['equals', 'notequals','under', 'notunder'])) {
            $criteria = ['OR' => []];
            if (
                (!isset($opt['searchequalsonfield'])
                    || !$opt['searchequalsonfield'])
                && ($itemtype == AllAssets::getType()
                    || $table != $itemtype::getTable())
            ) {
                $append_criterion_with_search($criteria['OR'], "$table.id");
            } else {
                $append_criterion_with_search($criteria['OR'], "$table.$field");
            }
            if ($searchtype == 'notequals') {
                $nott = !$nott;
            }
            // Add NULL if $val = 0 and not negative search
            // Or negative search on real value
            if (
                ($inittable !== Entity::getTable())
                && (
                    !$nott && ($val == 0)
                    || ($nott && ($val != 0))
                )
            ) {
                $criteria['OR'][] = ["$table.id" => null];
            }
            return $criteria;
        }
        $transitemtype = getItemTypeForTable($inittable);
        if (Session::haveTranslations($transitemtype, $field)) {
            return [
                'OR' => [
                    new QueryExpression(self::makeTextCriteria($tocompute, $val, $nott, '')),
                    new QueryExpression(self::makeTextCriteria($tocomputetrans, $val, $nott, '')),
                ],
            ];
        }

        return [new QueryExpression(self::makeTextCriteria($tocompute, $val, $nott, ''))];
    }

    /**
     * Generic Function to add Default left join to a request
     *
     * @param class-string<CommonDBTM> $itemtype   Reference item type
     * @param class-string<CommonDBTM> $ref_table  Reference table
     * @param array &$already_link_tables  Array of tables already joined
     *
     * @return array Left join criteria array
     **/
    public static function getDefaultJoinCriteria(string $itemtype, string $ref_table, array &$already_link_tables): array
    {
        global $CFG_GLPI;

        $out = [];
        switch ($itemtype) {
            // No link
            case User::class:
                $out = self::getLeftJoinCriteria(
                    $itemtype,
                    $ref_table,
                    $already_link_tables,
                    "glpi_profiles_users",
                    "profiles_users_id",
                    false,
                    '',
                    ['jointype' => 'child']
                );
                break;

            case Reservation::class:
                $out = self::getLeftJoinCriteria(
                    $itemtype,
                    $ref_table,
                    $already_link_tables,
                    ReservationItem::getTable(),
                    ReservationItem::getForeignKeyField(),
                );
                break;

            case Reminder::class:
                $out = ['LEFT JOIN' => Reminder::getVisibilityCriteria()['LEFT JOIN']];
                break;

            case RSSFeed::class:
                $out = ['LEFT JOIN' => RSSFeed::getVisibilityCriteria()['LEFT JOIN']];
                break;

            case ProjectTask::class:
                // Same structure in addDefaultWhere
                $out = self::getLeftJoinCriteria(
                    $itemtype,
                    $ref_table,
                    $already_link_tables,
                    "glpi_projects",
                    "projects_id"
                );
                $out = array_merge_recursive($out, self::getLeftJoinCriteria(
                    $itemtype,
                    $ref_table,
                    $already_link_tables,
                    "glpi_projecttaskteams",
                    "projecttaskteams_id",
                    false,
                    '',
                    ['jointype' => 'child']
                ));
                $out = array_merge_recursive($out, self::getLeftJoinCriteria(
                    $itemtype,
                    'glpi_projects',
                    $already_link_tables,
                    "glpi_projectteams",
                    "projectteams_id",
                    false,
                    '',
                    ['jointype' => 'child']
                ));
                break;

            case Project::class:
                // Same structure in addDefaultWhere
                if (!Session::haveRight("project", Project::READALL)) {
                    $out = self::getLeftJoinCriteria(
                        $itemtype,
                        $ref_table,
                        $already_link_tables,
                        "glpi_projectteams",
                        "projectteams_id",
                        false,
                        '',
                        ['jointype' => 'child']
                    );
                }
                break;

            case Ticket::class:
                // Same structure in addDefaultWhere
                if (!Session::haveRight("ticket", Ticket::READALL)) {
                    $searchopt = SearchOption::getOptionsForItemtype($itemtype);

                    // show mine: requester
                    $out = self::getLeftJoinCriteria(
                        $itemtype,
                        $ref_table,
                        $already_link_tables,
                        "glpi_tickets_users",
                        "tickets_users_id",
                        false,
                        '',
                        $searchopt[4]['joinparams']['beforejoin']['joinparams']
                    );

                    if (Session::haveRight("ticket", Ticket::READGROUP)) {
                        if (count($_SESSION['glpigroups'])) {
                            $out = array_merge_recursive($out, self::getLeftJoinCriteria(
                                $itemtype,
                                $ref_table,
                                $already_link_tables,
                                "glpi_groups_tickets",
                                "groups_tickets_id",
                                false,
                                '',
                                $searchopt[71]['joinparams']['beforejoin']
                                ['joinparams']
                            ));
                        }
                    }

                    // show mine: observer
                    $out = array_merge_recursive($out, self::getLeftJoinCriteria(
                        $itemtype,
                        $ref_table,
                        $already_link_tables,
                        "glpi_tickets_users",
                        "tickets_users_id",
                        false,
                        '',
                        $searchopt[66]['joinparams']['beforejoin']['joinparams']
                    ));

                    if (count($_SESSION['glpigroups'])) {
                        $out = array_merge_recursive($out, self::getLeftJoinCriteria(
                            $itemtype,
                            $ref_table,
                            $already_link_tables,
                            "glpi_groups_tickets",
                            "groups_tickets_id",
                            false,
                            '',
                            $searchopt[65]['joinparams']['beforejoin']['joinparams']
                        ));
                    }

                    if (Session::haveRight("ticket", Ticket::OWN)) { // Can own ticket: show assign to me
                        $out = array_merge_recursive($out, self::getLeftJoinCriteria(
                            $itemtype,
                            $ref_table,
                            $already_link_tables,
                            "glpi_tickets_users",
                            "tickets_users_id",
                            false,
                            '',
                            $searchopt[5]['joinparams']['beforejoin']['joinparams']
                        ));
                    }

                    if (Session::haveRightsOr("ticket", [Ticket::READMY, Ticket::READASSIGN])) { // show mine + assign to me
                        $out = array_merge_recursive($out, self::getLeftJoinCriteria(
                            $itemtype,
                            $ref_table,
                            $already_link_tables,
                            "glpi_tickets_users",
                            "tickets_users_id",
                            false,
                            '',
                            $searchopt[5]['joinparams']['beforejoin']['joinparams']
                        ));

                        if (count($_SESSION['glpigroups'])) {
                            $out = array_merge_recursive($out, self::getLeftJoinCriteria(
                                $itemtype,
                                $ref_table,
                                $already_link_tables,
                                "glpi_groups_tickets",
                                "groups_tickets_id",
                                false,
                                '',
                                $searchopt[8]['joinparams']['beforejoin']
                                ['joinparams']
                            ));
                        }
                    }

                    if (
                        Session::haveRightsOr(
                            'ticketvalidation',
                            [TicketValidation::VALIDATEINCIDENT,
                                TicketValidation::VALIDATEREQUEST,
                            ]
                        )
                    ) {
                        $out = array_merge_recursive($out, self::getLeftJoinCriteria(
                            $itemtype,
                            $ref_table,
                            $already_link_tables,
                            "glpi_ticketvalidations",
                            "ticketvalidations_id",
                            false,
                            '',
                            $searchopt[58]['joinparams']['beforejoin']['joinparams']
                        ));
                    }
                }
                break;

            case Change::class:
            case Problem::class:
                if ($itemtype === Change::class) {
                    $right       = 'change';
                    $table       = 'changes';
                    $groupetable = "glpi_changes_groups";
                    $linkfield   = "changes_groups_id";
                } elseif ($itemtype === Problem::class) {
                    $right       = 'problem';
                    $table       = 'problems';
                    $groupetable = "glpi_groups_problems";
                    $linkfield   = "groups_problems_id";
                }

                // Same structure in addDefaultWhere
                $out = [];
                if (!Session::haveRight("$right", $itemtype::READALL)) {
                    $searchopt = SearchOption::getOptionsForItemtype($itemtype);

                    if (Session::haveRight("$right", $itemtype::READMY)) {
                        // show mine : requester
                        $out = array_merge_recursive($out, self::getLeftJoinCriteria(
                            $itemtype,
                            $ref_table,
                            $already_link_tables,
                            "glpi_" . $table . "_users",
                            $table . "_users_id",
                            false,
                            '',
                            $searchopt[4]['joinparams']['beforejoin']['joinparams']
                        ));
                        if (count($_SESSION['glpigroups'])) {
                            $out = array_merge_recursive($out, self::getLeftJoinCriteria(
                                $itemtype,
                                $ref_table,
                                $already_link_tables,
                                $groupetable,
                                $linkfield,
                                false,
                                '',
                                $searchopt[71]['joinparams']['beforejoin']['joinparams']
                            ));
                        }

                        // show mine : observer
                        $out = array_merge_recursive($out, self::getLeftJoinCriteria(
                            $itemtype,
                            $ref_table,
                            $already_link_tables,
                            "glpi_" . $table . "_users",
                            $table . "_users_id",
                            false,
                            '',
                            $searchopt[66]['joinparams']['beforejoin']['joinparams']
                        ));
                        if (count($_SESSION['glpigroups'])) {
                            $out = array_merge_recursive($out, self::getLeftJoinCriteria(
                                $itemtype,
                                $ref_table,
                                $already_link_tables,
                                $groupetable,
                                $linkfield,
                                false,
                                '',
                                $searchopt[65]['joinparams']['beforejoin']['joinparams']
                            ));
                        }

                        // show mine : assign
                        $out = array_merge_recursive($out, self::getLeftJoinCriteria(
                            $itemtype,
                            $ref_table,
                            $already_link_tables,
                            "glpi_" . $table . "_users",
                            $table . "_users_id",
                            false,
                            '',
                            $searchopt[5]['joinparams']['beforejoin']['joinparams']
                        ));
                        if (count($_SESSION['glpigroups'])) {
                            $out = array_merge_recursive($out, self::getLeftJoinCriteria(
                                $itemtype,
                                $ref_table,
                                $already_link_tables,
                                $groupetable,
                                $linkfield,
                                false,
                                '',
                                $searchopt[8]['joinparams']['beforejoin']['joinparams']
                            ));
                        }
                    }
                }
                break;

            case KnowbaseItem::class:
                $leftjoin = KnowbaseItem::getVisibilityCriteria(false)['LEFT JOIN'];
                $out = ['LEFT JOIN' => $leftjoin];
                foreach ($leftjoin as $table => $criteria) {
                    $already_link_tables[] = $table;
                }
                break;

            case ITILFollowup::class:
                foreach ($CFG_GLPI['itil_types'] as $itil_itemtype) {
                    $out = array_merge_recursive($out, self::getLeftJoinCriteria(
                        $itemtype,
                        $ref_table,
                        $already_link_tables,
                        $itil_itemtype::getTable(),
                        'items_id',
                        false,
                        '',
                        [
                            'condition' => ['REFTABLE.itemtype' => $itil_itemtype],
                        ]
                    ));
                }
                break;

            default:
                // Plugin can override core definition for its type
                if ($plug = isPluginItemType($itemtype)) {
                    $plugin_name   = $plug['plugin'];
                    $hook_function = 'plugin_' . strtolower($plugin_name) . '_' . Hooks::AUTO_ADD_DEFAULT_JOIN;
                    $hook_closure  = function () use ($hook_function, $itemtype, $ref_table, &$already_link_tables) {
                        if (is_callable($hook_function)) {
                            return $hook_function($itemtype, $ref_table, $already_link_tables);
                        }
                    };
                    $out = Plugin::doOneHook($plugin_name, $hook_closure);
                    // @FIXME Deprecate string result to expect array|QueryExpression|null
                    $out ??= []; // convert null into an empty array
                    if (!is_array($out)) {
                        $out = self::parseJoinString($out);
                    }
                }
                break;
        }

        [$itemtype, $out] = Plugin::doHookFunction(Hooks::ADD_DEFAULT_JOIN, [$itemtype, $out]);
        if (is_string($out)) {
            $out = self::parseJoinString($out);
        }
        return $out;
    }

    /**
     * Convert a SQL JOIN string (may contain multiple JOINs) to the iterator/array format
     * @param string $raw_joins
     * @return array
     */
    private static function parseJoinString(string $raw_joins): array
    {
        $joins = [];
        $raw_joins = trim($raw_joins);
        if (empty($raw_joins)) {
            return $joins;
        }

        $join_types = ['LEFT JOIN', 'INNER JOIN', 'RIGHT JOIN', 'JOIN'];
        // split the raw string into an array of individual join clauses
        $join_clauses = preg_split('/(' . implode('|', $join_types) . ')/', $raw_joins, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        // Re-group join types and clauses
        $join_clauses = array_chunk($join_clauses, 2);
        foreach ($join_clauses as $join_clause) {
            $matches = [];
            // Get the join table (surrounded by backticks and may have alias), and the join condition
            preg_match('/(`[a-zA-Z0-9_]+`)\s*(AS .*)?\s*ON\s*(.+)/', $join_clause[1], $matches);
            if (count($matches) < 4) {
                // Invalid join clause
                continue;
            }
            $type = $join_clause[0];
            $table = trim($matches[1]) . $matches[2]; // Table name + optional alias
            $on = $matches[3];

            if (!array_key_exists($type, $joins)) {
                $joins[$type] = [];
            }

            $joins[$type][$table] = [
                'ON' => new QueryExpression($on),
            ];
        }
        return $joins;
    }

    /**
     * Generic Function to get left join criteria
     *
     * @param class-string<CommonDBTM> $itemtype Item type
     * @param string  $ref_table            Reference table
     * @param array   $already_link_tables  Array of tables already joined
     * @param string  $new_table            New table to join
     * @param string  $linkfield            Linkfield for LeftJoin
     * @param boolean $meta                 Is it a meta item? (default 0)
     * @param class-string<CommonDBTM>|'' $meta_type Meta type table (default 0)
     * @param array   $joinparams           Array join parameters (condition / joinbefore...)
     * @param string  $field                Field to display (needed for translation join) (default '')
     *
     * @return array Left join string
     **/
    public static function getLeftJoinCriteria(
        string $itemtype,
        string $ref_table,
        array &$already_link_tables,
        string $new_table,
        string $linkfield,
        bool $meta = false,
        string $meta_type = '',
        array $joinparams = [],
        string $field = ''
    ): array {
        global $DB;
        // Rename table for meta left join
        $AS = "";
        $nt = $new_table;
        $cleannt    = $nt;

        // Virtual field no link
        if (Search::isVirtualField($linkfield)) {
            return [];
        }

        $complexjoin = Search::computeComplexJoinID($joinparams);

        $is_fkey_composite_on_self = getTableNameForForeignKeyField($linkfield) === $ref_table
            && $linkfield !== getForeignKeyFieldForTable($ref_table);

        // Auto link
        if ($ref_table === $new_table && empty($complexjoin) && !$is_fkey_composite_on_self) {
            $transitemtype = getItemTypeForTable($new_table);
            if (Session::haveTranslations($transitemtype, $field)) {
                $transAS            = $nt . '_trans_' . $field;
                return self::getDropdownTranslationJoinCriteria(
                    $transAS,
                    $nt,
                    $transitemtype,
                    $field
                );
            }
            return [];
        }

        // Multiple link possibilies case
        if (!empty($linkfield) && ($linkfield !== getForeignKeyFieldForTable($new_table))) {
            $nt .= "_" . $linkfield;
            $AS  = " AS " . $DB::quoteName($nt);
        }

        if (!empty($complexjoin)) {
            $nt .= "_" . $complexjoin;
            $AS  = " AS " . $DB::quoteName($nt);
        }

        $addmetanum = "";
        $rt         = $ref_table;
        $cleanrt    = $rt;
        if ($meta) {
            $addmetanum = self::getMetaTableUniqueSuffix($new_table, $meta_type);
            $AS         = " AS " . $DB::quoteName($nt . $addmetanum);
            $nt .= $addmetanum;
        }

        // Do not take into account standard linkfield
        $tocheck = $nt . "." . $linkfield;
        if ($linkfield === getForeignKeyFieldForTable($new_table)) {
            $tocheck = $nt;
        }

        if (in_array($tocheck, $already_link_tables, true)) {
            return [];
        }
        $already_link_tables[] = $tocheck;

        // Handle mixed group case for AllAssets and ReservationItem
        if ($tocheck === 'glpi_groups' && ($itemtype === AllAssets::class || $itemtype === ReservationItem::class)) {
            $already_link_tables[] = 'glpi_groups_items';
            return [
                'LEFT JOIN' => [
                    'glpi_groups_items' => [
                        'ON' => [
                            'glpi_groups_items' => 'items_id',
                            $rt => 'id', [
                                'AND' => [
                                    'glpi_groups_items.itemtype' => $rt . '_TYPE', // Placeholder to be replaced at the end of the SQL construction during union case handling
                                    'glpi_groups_items.type' => Group_Item::GROUP_TYPE_NORMAL,
                                ],
                            ],
                        ],
                    ],
                    'glpi_groups' => [
                        'ON' => [
                            'glpi_groups' => 'id',
                            'glpi_groups_items' => 'groups_id',
                        ],
                    ],
                ],
            ];
        }

        $specific_leftjoin_criteria = [];

        // Plugin can override core definition for its type
        if ($plug = isPluginItemType($itemtype)) {
            $plugin_name   = $plug['plugin'];
            $hook_function = 'plugin_' . strtolower($plugin_name) . '_' . Hooks::AUTO_ADD_LEFT_JOIN;
            $hook_closure  = static function () use ($hook_function, $itemtype, $ref_table, $new_table, $linkfield, &$already_link_tables) {
                if (is_callable($hook_function)) {
                    return $hook_function($itemtype, $ref_table, $new_table, $linkfield, $already_link_tables);
                }
                return [];
            };
            $specific_leftjoin_criteria = Plugin::doOneHook($plugin_name, $hook_closure);
            // @FIXME Deprecate string result to expect array|QueryExpression|null
            $specific_leftjoin_criteria ??= []; // convert null into an empty array
            if (!is_array($specific_leftjoin_criteria)) {
                $specific_leftjoin_criteria = self::parseJoinString($specific_leftjoin_criteria);
            }
        }

        // Link with plugin tables: need to know left join structure
        if (
            $specific_leftjoin_criteria === []
            && preg_match("/^glpi_plugin_([a-z0-9]+)/", $new_table, $matches)
        ) {
            if (count($matches) == 2) {
                $plugin_name   = $matches[1];
                $hook_function = 'plugin_' . strtolower($plugin_name) . '_' . Hooks::AUTO_ADD_LEFT_JOIN;
                $hook_closure  = static function () use ($hook_function, $itemtype, $ref_table, $new_table, $linkfield, &$already_link_tables) {
                    if (is_callable($hook_function)) {
                        return $hook_function($itemtype, $ref_table, $new_table, $linkfield, $already_link_tables);
                    }
                    return [];
                };
                $specific_leftjoin_criteria = Plugin::doOneHook($plugin_name, $hook_closure);
                // @FIXME Deprecate string result to expect array|QueryExpression|null
                $specific_leftjoin_criteria ??= []; // convert null into an empty array
                if (!is_array($specific_leftjoin_criteria)) {
                    $specific_leftjoin_criteria = self::parseJoinString($specific_leftjoin_criteria);
                }
            }
        }
        if (!empty($linkfield)) {
            $before_criteria = [];

            if (isset($joinparams['beforejoin']) && is_array($joinparams['beforejoin'])) {
                if (isset($joinparams['beforejoin']['table'])) {
                    $joinparams['beforejoin'] = [$joinparams['beforejoin']];
                }

                foreach ($joinparams['beforejoin'] as $tab) {
                    if (isset($tab['table'])) {
                        $intertable = $tab['table'];
                        $interlinkfield = $tab['linkfield'] ?? getForeignKeyFieldForTable($intertable);

                        $interjoinparams = $tab['joinparams'] ?? [];
                        /** @noinspection SlowArrayOperationsInLoopInspection */
                        $before_criteria = array_merge_recursive($before_criteria, self::getLeftJoinCriteria(
                            $itemtype,
                            $rt,
                            $already_link_tables,
                            $intertable,
                            $interlinkfield,
                            (bool) $meta,
                            $meta_type,
                            $interjoinparams
                        ));

                        // No direct link with the previous joins
                        if (!isset($tab['joinparams']['nolink']) || !$tab['joinparams']['nolink']) {
                            $cleanrt     = $intertable;
                            $complexjoin = self::computeComplexJoinID($interjoinparams);
                            if (!empty($interlinkfield) && ($interlinkfield !== getForeignKeyFieldForTable($intertable))) {
                                $intertable .= "_" . $interlinkfield;
                            }
                            if (!empty($complexjoin)) {
                                $intertable .= "_" . $complexjoin;
                            }
                            if ($meta) {
                                $intertable .= self::getMetaTableUniqueSuffix($cleanrt, $meta_type);
                            }
                            $rt = $intertable;
                        }
                    }
                }
            }

            $addcondition = '';
            $add_criteria = $joinparams['condition'] ?? [];
            if (!is_array($add_criteria)) {
                if (empty($add_criteria)) {
                    $add_criteria = [];
                } else {
                    $add_criteria = [new QueryExpression($add_criteria)];
                }
            }
            $append_join_criteria = static function (&$join_fkey, $additional_criteria) {
                if (empty($additional_criteria)) {
                    return;
                }
                $add_link = 'AND';
                if (is_array($additional_criteria)) {
                    $first_criteria = reset($additional_criteria) ?? '';
                    if (is_string($first_criteria) || is_a($first_criteria, QueryExpression::class)) {
                        $first_criteria = trim($first_criteria);
                        // If the first criteria starts with AND or OR, use it as a link operator
                        if (preg_match('/^(AND|OR)\s+/', $first_criteria, $matches)) {
                            $add_link = $matches[1];
                            $first_criteria = trim(substr($first_criteria, strlen($add_link)));
                            array_shift($additional_criteria);
                            array_unshift($additional_criteria, $first_criteria);
                        }
                    }
                }
                if (count(array_keys($join_fkey)) === 2) {
                    $join_fkey[] = [$add_link => $additional_criteria];
                } else {
                    // Find last key
                    $last_key = array_keys($join_fkey);
                    $last_key = array_pop($last_key);
                    // Append new criteria to the last key
                    $join_fkey[$last_key]['AND'][] = [$add_link => $additional_criteria];
                }
            };
            $placeholders = [
                $DB::quoteName('REFTABLE')  => $DB::quoteName($rt),
                'REFTABLE'                  => $DB::quoteName($rt),
                $DB::quoteName('NEWTABLE')  => $DB::quoteName($nt),
                'NEWTABLE'                  => $DB::quoteName($nt),
            ];
            // Recursively walk through add_criteria array and make the placeholder replacements in the keys and values
            $replace_placeholders = static function ($add_criteria) use (&$replace_placeholders, $placeholders) {
                $new_criteria = [];
                foreach ($add_criteria as $key => $value) {
                    $new_key = strtr($key, $placeholders);
                    $replaced_key = (string) $new_key !== (string) $key;
                    if (is_array($value)) {
                        $new_criteria[$new_key] = $replace_placeholders($value);
                    } elseif (is_a($value, QueryExpression::class)) {
                        $value_string = $value->getValue();
                        $new_value = strtr($value_string, $placeholders);
                        $new_criteria[$new_key] = new QueryExpression($new_value);
                    } elseif ($value !== null) {
                        $new_criteria[$new_key] = strtr($value, $placeholders);
                    } else {
                        $new_criteria[$new_key] = $value;
                    }
                    if ($replaced_key) {
                        unset($new_criteria[$key]);
                    }
                }
                return $new_criteria;
            };
            $add_criteria = $replace_placeholders($add_criteria);

            if (!isset($joinparams['jointype'])) {
                $joinparams['jointype'] = 'standard';
            }

            if ($specific_leftjoin_criteria === []) {
                switch ($joinparams['jointype']) {
                    case 'child':
                        $linkfield = $joinparams['linkfield'] ?? getForeignKeyFieldForTable($cleanrt);

                        // Child join
                        $child_join = [
                            'LEFT JOIN' => [
                                "$new_table$AS" => [
                                    'ON' => [
                                        $rt => 'id',
                                        $nt => $linkfield,
                                    ],
                                ],
                            ],
                        ];
                        $append_join_criteria($child_join['LEFT JOIN']["$new_table$AS"]['ON'], $add_criteria);
                        $specific_leftjoin_criteria = array_merge_recursive($specific_leftjoin_criteria, $child_join);
                        break;

                    case 'item_item':
                        // Item_Item join
                        $item_item_join = [
                            'LEFT JOIN' => [
                                "$new_table$AS" => [
                                    'ON' => [
                                        $rt => 'id',
                                        $nt => getForeignKeyFieldForTable($cleanrt) . '_1',
                                        [
                                            'OR' => [
                                                new QueryExpression($DB::quoteName("$rt.id") . ' = ' . $DB::quoteName("$nt." . getForeignKeyFieldForTable($cleanrt) . '_2')),
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ];
                        $append_join_criteria($item_item_join['LEFT JOIN']["$new_table$AS"]['ON'], $add_criteria);
                        $specific_leftjoin_criteria = array_merge_recursive($specific_leftjoin_criteria, $item_item_join);
                        break;

                    case 'item_item_revert':
                        // Item_Item join reverting previous item_item
                        $item_item_join = [
                            'LEFT JOIN' => [
                                "$new_table$AS" => [
                                    'ON' => [
                                        $nt => 'id',
                                        $rt => getForeignKeyFieldForTable($cleannt) . '_1',
                                        [
                                            'OR' => [
                                                new QueryExpression($DB::quoteName("$nt.id") . ' = ' . $DB::quoteName("$rt." . getForeignKeyFieldForTable($cleannt) . '_2')),
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ];
                        $append_join_criteria($item_item_join['LEFT JOIN']["$new_table$AS"]['ON'], $add_criteria);
                        $specific_leftjoin_criteria = array_merge_recursive($specific_leftjoin_criteria, $item_item_join);
                        break;

                    case "mainitemtype_mainitem":
                        $addmain = 'main';
                        //addmain defined to be used in itemtype_item case

                        // no break
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

                        $items_id_column = 'items_id';
                        if (
                            isset($joinparams['specific_items_id_column'])
                            && !empty($joinparams['specific_items_id_column'])
                        ) {
                            $items_id_column = $joinparams['specific_items_id_column'];
                        }

                        $itemtype_column = 'itemtype';
                        if (
                            isset($joinparams['specific_itemtype_column'])
                            && !empty($joinparams['specific_itemtype_column'])
                        ) {
                            $itemtype_column = $joinparams['specific_itemtype_column'];
                        }

                        // Itemtype join
                        $itemtype_join = [
                            'LEFT JOIN' => [
                                "$new_table$AS" => [
                                    'ON' => [
                                        $rt => 'id',
                                        $nt => "{$addmain}{$items_id_column}",
                                        [
                                            'AND' => [
                                                "$nt.{$addmain}{$itemtype_column}" => $used_itemtype,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ];
                        $append_join_criteria($itemtype_join['LEFT JOIN']["$new_table$AS"]['ON'], $add_criteria);
                        $specific_leftjoin_criteria = array_merge_recursive($specific_leftjoin_criteria, $itemtype_join);
                        break;

                    case "itemtype_item_revert":
                        $used_itemtype = $itemtype;
                        if (
                            isset($joinparams['specific_itemtype'])
                            && !empty($joinparams['specific_itemtype'])
                        ) {
                            $used_itemtype = $joinparams['specific_itemtype'];
                        }
                        // Itemtype join
                        $itemtype_join = [
                            'LEFT JOIN' => [
                                "$new_table$AS" => [
                                    'ON' => [
                                        $nt => 'id',
                                        $rt => 'items_id',
                                        [
                                            'AND' => [
                                                "$rt.itemtype" => $used_itemtype,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ];
                        $append_join_criteria($itemtype_join['LEFT JOIN']["$new_table$AS"]['ON'], $add_criteria);
                        $specific_leftjoin_criteria = array_merge_recursive($specific_leftjoin_criteria, $itemtype_join);
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
                        $itemtype_join = [
                            'LEFT JOIN' => [
                                "$new_table$AS" => [
                                    'ON' => [
                                        $nt => 'itemtype',
                                        new QueryExpression("'$used_itemtype'"),
                                    ],
                                ],
                            ],
                        ];
                        $append_join_criteria($itemtype_join['LEFT JOIN']["$new_table$AS"]['ON'], $add_criteria);
                        $specific_leftjoin_criteria = array_merge_recursive($specific_leftjoin_criteria, $itemtype_join);
                        break;

                    case "custom_condition_only":
                        $specific_leftjoin_criteria = ['LEFT JOIN' => ["$new_table$AS" => $add_criteria]];
                        $transitemtype = getItemTypeForTable($new_table);
                        if (Session::haveTranslations($transitemtype, $field)) {
                            $transAS            = $nt . '_trans_' . $field;
                            $specific_leftjoin_criteria = array_merge_recursive($specific_leftjoin_criteria, self::getDropdownTranslationJoinCriteria(
                                $transAS,
                                $nt,
                                $transitemtype,
                                $field
                            ));
                        }
                        break;

                    default:
                        // Standard join
                        $standard_join = [
                            'LEFT JOIN' => [
                                "$new_table$AS" => [
                                    'ON' => [
                                        $rt => $linkfield,
                                        $nt => 'id',
                                    ],
                                ],
                            ],
                        ];
                        $append_join_criteria($standard_join['LEFT JOIN']["$new_table$AS"]['ON'], $add_criteria);
                        $specific_leftjoin_criteria = array_merge_recursive($specific_leftjoin_criteria, $standard_join);
                        $transitemtype = getItemTypeForTable($new_table);
                        if (Session::haveTranslations($transitemtype, $field)) {
                            $transAS            = $nt . '_trans_' . $field;
                            $specific_leftjoin_criteria = array_merge_recursive($specific_leftjoin_criteria, self::getDropdownTranslationJoinCriteria(
                                $transAS,
                                $nt,
                                $transitemtype,
                                $field
                            ));
                        }
                        break;
                }
            }
            return array_merge_recursive($before_criteria, $specific_leftjoin_criteria);
        }

        return [];
    }


    /**
     * Generic Function to add left join for meta items
     *
     * @param class-string<CommonDBTM> $from_type  Reference item type ID
     * @param class-string<CommonDBTM> $to_type    Item type to add
     * @param array  $already_link_tables2         Array of tables already joined
     *
     * @return array Meta Left join criteria
     **/
    public static function getMetaLeftJoinCriteria(string $from_type, string $to_type, array &$already_link_tables2, array $joinparams = []): array
    {
        global $CFG_GLPI;

        $from_referencetype = SearchEngine::getMetaReferenceItemtype($from_type);

        $from_table = $from_type::getTable();
        $from_fk    = getForeignKeyFieldForTable($from_table);

        $to_table         = $to_type::getTable();
        $to_fk            = getForeignKeyFieldForTable($to_table);
        $to_table_alias   = $to_table . self::getMetaTableUniqueSuffix($to_table, $to_type);
        $to_criteria      = $to_type::getSystemSQLCriteria($to_table_alias);
        $to_table_join_id = $to_table . ($to_table_alias !== $to_table ? ' AS ' . $to_table_alias : '');

        $to_obj        = getItemForItemtype($to_type);
        $to_entity_restrict_criteria = $to_obj->isField('entities_id') ? getEntitiesRestrictCriteria($to_table_alias) : [];

        $complexjoin = Search::computeComplexJoinID($joinparams);
        $alias_suffix = ($complexjoin !== '' ? '_' . $complexjoin : '') . '_' . $to_type;

        $joins = [
            'LEFT JOIN' => [],
        ];

        // Specific JOIN
        if ($from_referencetype === Software::class && in_array($to_type, $CFG_GLPI['software_types'], true)) {
            // From Software to software_types
            $softwareversions_table = "glpi_softwareversions{$alias_suffix}";
            if (!in_array($softwareversions_table, $already_link_tables2, true)) {
                $already_link_tables2[] = $softwareversions_table;
                $joins['LEFT JOIN']["`glpi_softwareversions` AS `$softwareversions_table`"] = [
                    'ON' => [
                        $softwareversions_table => 'softwares_id',
                        $from_table => 'id',
                    ],
                ];
            }
            $items_softwareversions_table = "glpi_items_softwareversions_{$alias_suffix}";
            if (!in_array($items_softwareversions_table, $already_link_tables2, true)) {
                $already_link_tables2[] = $items_softwareversions_table;
                $joins['LEFT JOIN']["`glpi_items_softwareversions` AS `$items_softwareversions_table`"] = [
                    'ON' => [
                        $items_softwareversions_table => 'softwareversions_id',
                        $softwareversions_table => 'id',
                        [
                            'AND' => [
                                "$items_softwareversions_table.itemtype" => $to_type,
                                "$items_softwareversions_table.is_deleted" => 0,
                            ],
                        ],
                    ],
                ];
            }
            if (!in_array($to_table_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $to_table_alias;
                $joins['LEFT JOIN'][$to_table_join_id] = [
                    'ON' => [
                        $items_softwareversions_table => 'items_id',
                        $to_table_alias => 'id',
                        [
                            'AND' => [
                                "$items_softwareversions_table.itemtype" => $to_type,
                            ] + $to_entity_restrict_criteria + $to_criteria,
                        ],
                    ],
                ];
            }
            return $joins;
        }

        if ($to_type === Software::class && in_array($from_referencetype, $CFG_GLPI['software_types'], true)) {
            // From software_types to Software
            $items_softwareversions_table = "glpi_items_softwareversions{$alias_suffix}";
            if (!in_array($items_softwareversions_table, $already_link_tables2, true)) {
                $already_link_tables2[] = $items_softwareversions_table;
                $joins['LEFT JOIN']["`glpi_items_softwareversions` AS `$items_softwareversions_table`"] = [
                    'ON' => [
                        $items_softwareversions_table => 'items_id',
                        $from_table => 'id',
                        [
                            'AND' => [
                                "$items_softwareversions_table.itemtype" => $from_type,
                                "$items_softwareversions_table.is_deleted" => 0,
                            ],
                        ],
                    ],
                ];
            }
            $softwareversions_table = "glpi_softwareversions{$alias_suffix}";
            if (!in_array($softwareversions_table, $already_link_tables2, true)) {
                $already_link_tables2[] = $softwareversions_table;
                $joins['LEFT JOIN']["`glpi_softwareversions` AS `$softwareversions_table`"] = [
                    'ON' => [
                        $items_softwareversions_table => 'softwareversions_id',
                        $softwareversions_table => 'id',
                    ],
                ];
            }
            if (!in_array($to_table_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $to_table_alias;
                $joins['LEFT JOIN'][$to_table_join_id] = [
                    'ON' => [
                        $softwareversions_table => 'softwares_id',
                        $to_table_alias => 'id',
                    ] + $to_criteria,
                ];
            }
            $softwarelicenses_table = "glpi_softwarelicenses{$alias_suffix}";
            if (!in_array($softwarelicenses_table, $already_link_tables2, true)) {
                $already_link_tables2[] = $softwarelicenses_table;
                $joins['LEFT JOIN']["`glpi_softwarelicenses` AS `$softwarelicenses_table`"] = [
                    'ON' => [
                        $to_table_alias => 'id',
                        $softwarelicenses_table => 'softwares_id',
                        [
                            'AND' => getEntitiesRestrictCriteria($softwarelicenses_table, '', '', true),
                        ],
                    ],
                ];
            }
            return $joins;
        }

        if ($from_referencetype === Budget::class && in_array($to_type, $CFG_GLPI['infocom_types'], true)) {
            // From Budget to infocom_types
            $infocom_alias = "glpi_infocoms{$alias_suffix}";
            if (!in_array($infocom_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $infocom_alias;
                $joins['LEFT JOIN']["`glpi_infocoms` AS `$infocom_alias`"] = [
                    'ON' => [
                        $from_table => 'id',
                        $infocom_alias => 'budgets_id',
                    ],
                ];
            }
            if (!in_array($to_table_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $to_table_alias;
                $joins['LEFT JOIN'][$to_table_join_id] = [
                    'ON' => [
                        $infocom_alias => 'items_id',
                        $to_table_alias => 'id',
                        [
                            'AND' => [
                                "$infocom_alias.itemtype" => $to_type,
                            ] + $to_entity_restrict_criteria + $to_criteria,
                        ],
                    ],
                ];
            }
            return $joins;
        }

        if ($to_type === Budget::class && in_array($from_referencetype, $CFG_GLPI['infocom_types'], true)) {
            // From infocom_types to Budget
            $infocom_alias = "glpi_infocoms{$alias_suffix}";
            if (!in_array($infocom_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $infocom_alias;
                $joins['LEFT JOIN']["`glpi_infocoms` AS `$infocom_alias`"] = [
                    'ON' => [
                        $from_table => 'id',
                        $infocom_alias => 'items_id',
                        [
                            'AND' => [
                                "$infocom_alias.itemtype" => $from_type,
                            ],
                        ],
                    ],
                ];
            }
            if (!in_array($to_table_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $to_table_alias;
                $joins['LEFT JOIN'][$to_table_join_id] = [
                    'ON' => [
                        $infocom_alias => $to_fk,
                        $to_table_alias => 'id',
                        [
                            'AND' => $to_entity_restrict_criteria,
                        ] + $to_criteria,
                    ],
                ];
            }
            return $joins;
        }

        if ($from_referencetype === Reservation::class && in_array($to_type, $CFG_GLPI['reservation_types'], true)) {
            // From Reservation to reservation_types
            $reservationitems_alias = "glpi_reservationitems{$alias_suffix}";
            if (!in_array($reservationitems_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $reservationitems_alias;
                $joins['LEFT JOIN']["`glpi_reservationitems` AS `$reservationitems_alias`"] = [
                    'ON' => [
                        $from_table => 'reservationitems_id',
                        $reservationitems_alias => 'id',
                    ],
                ];
            }
            if (!in_array($to_table_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $to_table_alias;
                $joins['LEFT JOIN'][$to_table_join_id] = [
                    'ON' => [
                        $reservationitems_alias => 'items_id',
                        $to_table_alias => 'id',
                        [
                            'AND' => [
                                "$reservationitems_alias.itemtype" => $to_type,
                            ] + $to_entity_restrict_criteria + $to_criteria,
                        ],
                    ],
                ];
            }
            return $joins;
        }

        if ($to_type === Reservation::class && in_array($from_referencetype, $CFG_GLPI['reservation_types'], true)) {
            // From reservation_types to Reservation
            $reservationitems_alias = "glpi_reservationitems{$alias_suffix}";
            if (!in_array($reservationitems_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $reservationitems_alias;
                $joins['LEFT JOIN']["`glpi_reservationitems` AS `$reservationitems_alias`"] = [
                    'ON' => [
                        $from_table => 'id',
                        $reservationitems_alias => 'items_id',
                        [
                            'AND' => [
                                "$reservationitems_alias.itemtype" => $from_type,
                            ],
                        ],
                    ],
                ];
            }
            if (!in_array($to_table_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $to_table_alias;
                $joins['LEFT JOIN'][$to_table_join_id] = [
                    'ON' => [
                        $reservationitems_alias => 'id',
                        $to_table_alias => 'reservationitems_id',
                        [
                            'AND' => $to_entity_restrict_criteria + $to_criteria,
                        ],
                    ],
                ];
            }
            return $joins;
        }

        // Specific JOIN for Asset_PeripheralAsset
        if (
            (
                in_array($to_type, $CFG_GLPI['directconnect_types'])
                && in_array($from_referencetype, Asset_PeripheralAsset::getPeripheralHostItemtypes(), true)
            )
            || (
                in_array($from_referencetype, $CFG_GLPI['directconnect_types'])
                && in_array($to_type, Asset_PeripheralAsset::getPeripheralHostItemtypes(), true)
            )
        ) {
            $asset_itemtype = in_array($to_type, $CFG_GLPI['directconnect_types']) ? $from_referencetype : $to_type;
            $peripheral_itemtype = $asset_itemtype === $from_referencetype ? $to_type : $from_referencetype;
            $relation_table = Asset_PeripheralAsset::getTable();
            $relation_table_alias = $relation_table . $alias_suffix;
            if (!in_array($relation_table_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $relation_table_alias;
                $deleted_criteria = ["`$relation_table_alias`.`is_deleted`" => 0];
                $joins['LEFT JOIN']["`$relation_table` AS `$relation_table_alias`"] = [
                    'ON' => [
                        $relation_table_alias => 'items_id_asset',
                        $from_table => 'id',
                        [
                            'AND' => [
                                "$relation_table_alias." . 'itemtype_asset' => $asset_itemtype,
                                "$relation_table_alias." . 'itemtype_peripheral' => $peripheral_itemtype,
                            ] + $deleted_criteria,
                        ],
                    ],
                ];
            }
            if (!in_array($to_table_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $to_table_alias;
                $joins['LEFT JOIN'][$to_table_join_id] = [
                    'ON' => [
                        $relation_table_alias => 'items_id_peripheral',
                        $to_table_alias => 'id',
                        [
                            'AND' => [
                                "$relation_table_alias." . 'itemtype_peripheral' => $peripheral_itemtype,
                            ] + $to_entity_restrict_criteria + $to_criteria,
                        ],
                    ],
                ];
            }
            return $joins;
        }

        if ($to_type === Group::class && in_array($from_referencetype, $CFG_GLPI['assignable_types'], true)) {
            $relation_table_alias = 'glpi_groups_items' . $alias_suffix;
            if (!in_array($relation_table_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $relation_table_alias;
                $joins['LEFT JOIN']["`glpi_groups_items` AS `$relation_table_alias`"] = [
                    'ON' => [
                        $relation_table_alias => 'items_id',
                        $from_table => 'id',
                        [
                            'AND' => [
                                $relation_table_alias . '.itemtype' => $from_referencetype,
                                $relation_table_alias . '.type' => Group_Item::GROUP_TYPE_NORMAL,
                            ],
                        ],
                    ],
                ];
            }
            if (!in_array($to_table_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $to_table_alias;
                $joins['LEFT JOIN'][$to_table_alias] = [
                    'ON' => [
                        $to_table_alias => 'id',
                        $relation_table_alias => 'groups_id',
                        [
                            'AND' => $to_entity_restrict_criteria + $to_criteria,
                        ],
                    ],
                ];
            }
            return $joins;
        }

        if ($from_referencetype === Group::class && in_array($to_type, $CFG_GLPI['assignable_types'], true)) {
            $relation_table_alias = 'glpi_groups_items' . $alias_suffix;
            if (!in_array($relation_table_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $relation_table_alias;
                $joins['LEFT JOIN']["`glpi_groups_items` AS `$relation_table_alias`"] = [
                    'ON' => [
                        $relation_table_alias => 'groups_id',
                        $from_table => 'id',
                        [
                            'AND' => [
                                $relation_table_alias . '.itemtype' => $to_type,
                                $relation_table_alias . '.type' => Group_Item::GROUP_TYPE_NORMAL,
                            ],
                        ],
                    ],
                ];
            }
            if (!in_array($to_table_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $to_table_alias;
                $joins['LEFT JOIN'][$to_table_join_id] = [
                    'ON' => [
                        $relation_table_alias => 'items_id',
                        $to_table_alias => 'id',
                        [
                            'AND' => $to_entity_restrict_criteria + $to_criteria,
                        ],
                    ],
                ];
            }
            return $joins;
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
            if (!in_array($to_table_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $to_table_alias;
                $joins['LEFT JOIN'][$to_table_join_id] = [
                    'ON' => [
                        $from_table => $to_fk,
                        $to_table_alias => 'id',
                        [
                            'AND' => $to_entity_restrict_criteria + $to_criteria,
                        ],
                    ],
                ];
            }
        } elseif ($to_obj && $to_obj->isField($from_fk)) {
            // $to_table has a foreign key corresponding to $from_table
            if (!in_array($to_table_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $to_table_alias;
                $joins['LEFT JOIN'][$to_table_join_id] = [
                    'ON' => [
                        $from_table => 'id',
                        $to_table_alias => $from_fk,
                        [
                            'AND' => $to_entity_restrict_criteria + $to_criteria,
                        ],
                    ],
                ];
            }
        } elseif ($from_obj && $from_obj->isField('itemtype') && $from_obj->isField('items_id')) {
            // $from_table has items_id/itemtype fields
            if (!in_array($to_table_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $to_table_alias;
                $joins['LEFT JOIN'][$to_table_join_id] = [
                    'ON' => [
                        $from_table => 'items_id',
                        $to_table_alias => 'id',
                        [
                            'AND' => [
                                "$from_table.itemtype" => $to_type,
                            ] + $to_entity_restrict_criteria + $to_criteria,
                        ],
                    ],
                ];
            }
        } elseif ($to_obj && $to_obj->isField('itemtype') && $to_obj->isField('items_id')) {
            // $to_table has items_id/itemtype fields
            if (!in_array($to_table_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $to_table_alias;
                $joins['LEFT JOIN'][$to_table_join_id] = [
                    'ON' => [
                        $from_table => 'id',
                        $to_table_alias => 'items_id',
                        [
                            'AND' => [
                                "$to_table_alias.itemtype" => $from_type,
                            ] + $to_entity_restrict_criteria + $to_criteria,
                        ],
                    ],
                ];
            }
        } elseif ($from_item_obj && $from_item_obj->isField($from_fk)) {
            // glpi_$from_items table exists and has a foreign key corresponding to $to_table
            $items_table = $from_item_obj::getTable();
            $items_table_alias = $items_table . $alias_suffix;
            if (!in_array($items_table_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $items_table_alias;
                $deleted_criteria = $from_item_obj->isField('is_deleted') ? ["`$items_table_alias`.`is_deleted`" => 0] : [];
                $joins['LEFT JOIN']["`$items_table` AS `$items_table_alias`"] = [
                    'ON' => [
                        $items_table_alias => $from_fk,
                        $from_table => 'id',
                        [
                            'AND' => [
                                "$items_table_alias.itemtype" => $to_type,
                            ] + $deleted_criteria,
                        ],
                    ],
                ];
            }
            if (!in_array($to_table_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $to_table_alias;
                $joins['LEFT JOIN'][$to_table_join_id] = [
                    'ON' => [
                        $items_table_alias => 'items_id',
                        $to_table_alias => 'id',
                        [
                            'AND' => $to_entity_restrict_criteria + $to_criteria,
                        ],
                    ],
                ];
            }
        } elseif ($to_item_obj && $to_item_obj->isField($to_fk)) {
            // glpi_$to_items table exists and has a foreign key corresponding to $from_table
            $items_table = $to_item_obj::getTable();
            $items_table_alias = $items_table . $alias_suffix;
            if (!in_array($items_table_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $items_table_alias;
                $deleted_criteria = $to_item_obj->isField('is_deleted') ? ["`$items_table_alias`.`is_deleted`" => 0] : [];
                $joins['LEFT JOIN']["`$items_table` AS `$items_table_alias`"] = [
                    'ON' => [
                        $items_table_alias => 'items_id',
                        $from_table => 'id',
                        [
                            'AND' => [
                                "$items_table_alias.itemtype" => $from_type,
                            ] + $deleted_criteria,
                        ],
                    ],
                ];
            }
            if (!in_array($to_table_alias, $already_link_tables2, true)) {
                $already_link_tables2[] = $to_table_alias;
                $joins['LEFT JOIN'][$to_table_join_id] = [
                    'ON' => [
                        $items_table_alias => $to_fk,
                        $to_table_alias => 'id',
                        [
                            'AND' => $to_entity_restrict_criteria + $to_criteria,
                        ],
                    ],
                ];
            }
        }

        return $joins;
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
                $dbi = new DBmysqlIterator($DB);
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
                        $dbi = new DBmysqlIterator($DB);
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
     * Get join criteria for dropdown translations
     *
     * @param string $alias    Alias for translation table
     * @param string $table    Table to join on
     * @param class-string<CommonDBTM> $itemtype Item type
     * @param string $field    Field name
     *
     * @return array
     */
    public static function getDropdownTranslationJoinCriteria($alias, $table, $itemtype, $field): array
    {
        global $DB;

        return [
            'LEFT JOIN' => [
                "glpi_dropdowntranslations AS $alias" => [
                    'ON' => [
                        $alias => 'itemtype',
                        new QueryExpression($DB::quoteValue($itemtype)),
                        [
                            'AND' => [
                                "$alias.items_id" => new QueryExpression($DB::quoteName("$table.id")),
                                "$alias.language" => $_SESSION['glpilanguage'],
                                "$alias.field"    => $field,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Generic Function to add GROUP BY to a request
     *
     * @param string  $LINK           link to use
     * @param bool    $NOT            is is a negative search?
     * @param class-string<CommonDBTM>  $itemtype       item type
     * @param int     $ID             ID of the item to search
     * @param string  $searchtype     search type ('contains' or 'equals')
     * @param string  $val            value search
     *
     * @return array HAVING criteria as an array
     **/
    public static function getHavingCriteria(string $LINK, bool $NOT, string $itemtype, int $ID, string $searchtype, string $val): array
    {
        $searchopt  = SearchOption::getOptionsForItemtype($itemtype);
        if (!isset($searchopt[$ID]['table'])) {
            return [];
        }
        $table = $searchopt[$ID]["table"];
        $NAME = "ITEM_{$itemtype}_{$ID}";

        // Plugin can override core definition for its type
        if ($plug = isPluginItemType($itemtype)) {
            $out = Plugin::doOneHook(
                $plug['plugin'],
                Hooks::AUTO_ADD_HAVING,
                $LINK,
                $NOT,
                $itemtype,
                $ID,
                $val,
                "{$itemtype}_{$ID}"
            );
            // @FIXME Deprecate string result to expect array|QueryExpression|null
            if (!empty($out)) {
                return is_array($out) ? $out : [new QueryExpression($out)];
            }
        }

        //// Default cases
        // Link with plugin tables
        if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table, $matches)) {
            if (count($matches) === 2) {
                $plug     = $matches[1];
                $out = Plugin::doOneHook(
                    $plug,
                    Hooks::AUTO_ADD_HAVING,
                    $LINK,
                    $NOT,
                    $itemtype,
                    $ID,
                    $val,
                    "{$itemtype}_{$ID}"
                );
                // @FIXME Deprecate string result to expect array|QueryExpression|null
                if (!empty($out)) {
                    return is_array($out) ? $out : [new QueryExpression($out)];
                }
            }
        }

        if (in_array($searchtype, ["notequals", "notcontains"])) {
            $NOT = !$NOT;
        }

        // Preformat items
        if (isset($searchopt[$ID]["datatype"])) {
            if ($searchopt[$ID]["datatype"] === "mio") {
                // Parse value as it may contain a few different formats
                $val = (string) Toolbox::getMioSizeFromString($val);
            }

            switch ($searchopt[$ID]["datatype"]) {
                case "datetime":
                    // FIXME `addHaving` should produce same kind of criterion as `addWhere`
                    //  (i.e. using a comparison with `ADDDATE(NOW(), INTERVAL {$val} MONTH)`).

                    if (in_array($searchtype, ['contains', 'notcontains'])) {
                        break;
                    }

                    $force_day = false;
                    if (strstr($val, 'BEGIN') || strstr($val, 'LAST')) {
                        $force_day = true;
                    }

                    $val = Html::computeGenericDateTimeSearch($val, $force_day);

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

                    return [
                        $NAME => [$operator, $val],
                    ];
                case "count":
                case "mio":
                case "number":
                case "integer":
                case "decimal":
                case "timestamp":
                    if (preg_match("/([<>])(=?)[[:space:]]*(-?)[[:space:]]*([0-9]+(.[0-9]+)?)/", $val, $regs)) {
                        if ($NOT) {
                            if ($regs[1] === '<') {
                                $regs[1] = '>';
                            } else {
                                $regs[1] = '<';
                            }
                        }
                        $regs[1] .= $regs[2];
                        return [
                            $NAME => [$regs[1], floatval($regs[3] . $regs[4])],
                        ];
                    }

                    if (is_numeric($val)) {
                        $num_val = (int) $val;
                        if (isset($searchopt[$ID]["width"])) {
                            if (!$NOT) {
                                return [
                                    [$NAME => ['<', $num_val + $searchopt[$ID]["width"]]],
                                    [$NAME => ['>', $num_val - $searchopt[$ID]["width"]]],
                                ];
                            }
                            return [
                                'OR' => [
                                    [$NAME => ['>', $num_val + $searchopt[$ID]["width"]]],
                                    [$NAME => ['<', $num_val - $searchopt[$ID]["width"]]],
                                ],
                            ];
                        }
                        // Exact search
                        if (!$NOT) {
                            return [
                                $NAME => $num_val,
                            ];
                        }
                        return [
                            $NAME => ['<>', $num_val],
                        ];
                    }
                    break;
            }
        }

        return [new QueryExpression(self::makeTextCriteria("`$NAME`", $val, $NOT, ''))];
    }


    /**
     * Generic Function to add ORDER BY to a request
     *
     * @since 9.4: $key param has been dropped
     * @since 10.0.0: Parameters changed to allow multiple sort fields.
     *    Old functionality maintained by checking the type of the first parameter.
     *    This backwards compatibility will be removed in a later version.
     *
     * @param class-string<CommonDBTM> $itemtype The itemtype
     * @param array  $sort_fields The search options to order on. This array should contain one or more associative arrays containing:
     *    - id: The search option ID
     *    - order: The sort direction (Default: ASC). Invalid sort directions will be replaced with the default option
     *
     * @return array ORDER BY criteria
     *
     **/
    public static function getOrderByCriteria(string $itemtype, array $sort_fields): array
    {
        global $CFG_GLPI, $DB;

        $orderby_criteria = [];
        $searchopt = SearchOption::getOptionsForItemtype($itemtype);

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
                $out = Plugin::doOneHook(
                    $plug['plugin'],
                    Hooks::AUTO_ADD_ORDER_BY,
                    $itemtype,
                    $ID,
                    $order,
                    "{$itemtype}_{$ID}"
                );
                // @FIXME Deprecate string result to expect array|QueryExpression|null
                $out = $out !== null ? trim($out) : null;
                if (!empty($out)) {
                    $out = preg_replace('/^ORDER BY /', '', $out);
                    $criterion = $out;
                }
            }

            if ($criterion === null) {
                switch ($table . "." . $field) {
                    case "glpi_users.name":
                        if ($itemtype != User::class) {
                            if ($_SESSION["glpinames_format"] == User::FIRSTNAME_BEFORE) {
                                $name1 = 'firstname';
                                $name2 = 'realname';
                            } else {
                                $name1 = 'realname';
                                $name2 = 'firstname';
                            }
                            $addaltemail = "";
                            if (
                                in_array($itemtype, ['Ticket', 'Change', 'Problem'])
                                && isset($searchopt[$ID]['joinparams']['beforejoin']['table'])
                                && in_array($searchopt[$ID]['joinparams']['beforejoin']['table'], ['glpi_tickets_users', 'glpi_changes_users', 'glpi_problems_users'])
                            ) { // For tickets_users
                                $ticket_user_table = $searchopt[$ID]['joinparams']['beforejoin']['table'] . "_"
                                    . self::computeComplexJoinID($searchopt[$ID]['joinparams']['beforejoin']['joinparams']);
                                $addaltemail = ",
                                IFNULL(`$ticket_user_table`.`alternative_email`, '')";
                            }
                            if ((isset($searchopt[$ID]["forcegroupby"]) && $searchopt[$ID]["forcegroupby"])) {
                                $criterion = "GROUP_CONCAT(DISTINCT CONCAT(
                                    IFNULL(`$table$addtable`.`$name1`, ''),
                                    IFNULL(`$table$addtable`.`$name2`, ''),
                                    IFNULL(`$table$addtable`.`name`, '')$addaltemail
                                ) ORDER BY CONCAT(
                                    IFNULL(`$table$addtable`.`$name1`, ''),
                                    IFNULL(`$table$addtable`.`$name2`, ''),
                                    IFNULL(`$table$addtable`.`name`, '')$addaltemail) ASC
                                ) $order";
                            } else {
                                $criterion = "CONCAT(
                                    IFNULL(`$table$addtable`.`$name1`, ''),
                                    IFNULL(`$table$addtable`.`$name2`, ''),
                                    IFNULL(`$table$addtable`.`name`, '')$addaltemail
                                ) $order";
                            }
                        } else {
                            $criterion = "`" . $table . $addtable . "`.`name` $order";
                        }
                        break;
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
                    $out = Plugin::doOneHook(
                        $plug,
                        Hooks::AUTO_ADD_ORDER_BY,
                        $itemtype,
                        $ID,
                        $order,
                        "{$itemtype}_{$ID}"
                    );
                    // @FIXME Deprecate string result to expect array|QueryExpression|null
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
                        $criterion = QueryFunction::dateAdd(
                            date: "{$table}{$addtable}.{$searchopt[$ID]['datafields'][1]}",
                            interval: new QueryExpression($DB::quoteName("{$table}{$addtable}.{$searchopt[$ID]['datafields'][2]}") . " $add_minus"),
                            interval_unit: $interval,
                        ) . " $order";
                }
            }

            $orderby_criteria[] = new QueryExpression($criterion ?? "`ITEM_{$itemtype}_{$ID}` $order");
        }

        return $orderby_criteria;
    }

    #[Override]
    public static function constructSQL(array &$data)
    {
        global $CFG_GLPI, $DB;

        if (!isset($data['itemtype'])) {
            return false;
        }

        Profiler::getInstance()->start('SQLProvider::constructSQL', Profiler::CATEGORY_SEARCH);
        $data['sql']['count']  = [];
        $data['sql']['search'] = '';
        $data['sql']['raw']    = [];

        $searchopt        = SearchOption::getOptionsForItemtype($data['itemtype']);

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
        $COMMONLEFTJOIN = Search::addDefaultJoin($data['itemtype'], $itemtable, $already_link_tables);
        $FROM          .= $COMMONLEFTJOIN;

        // Add all table for toview items
        foreach ($data['tocompute'] as $val) {
            if (!in_array($searchopt[$val]["table"], $blacklist_tables)) {
                $FROM .= Search::addLeftJoin(
                    $data['itemtype'],
                    $itemtable,
                    $already_link_tables,
                    $searchopt[$val]["table"],
                    $searchopt[$val]["linkfield"],
                    false,
                    '',
                    $searchopt[$val]["joinparams"],
                    $searchopt[$val]["field"]
                );
            }
        }

        // Search all case:
        if ($data['search']['all_search']) {
            foreach ($searchopt as $key => $val) {
                // Do not search on Group Name
                if (is_array($val) && isset($val['table'])) {
                    if (!in_array($searchopt[$key]["table"], $blacklist_tables)) {
                        $FROM .= Search::addLeftJoin(
                            $data['itemtype'],
                            $itemtable,
                            $already_link_tables,
                            $searchopt[$key]["table"],
                            $searchopt[$key]["linkfield"],
                            false,
                            '',
                            $searchopt[$key]["joinparams"],
                            $searchopt[$key]["field"]
                        );
                    }
                }
            }
        }

        //// 3 - WHERE

        // default string
        $COMMONWHERE = Search::addDefaultWhere($data['itemtype']);
        $first       = empty($COMMONWHERE);

        // Add deleted if item have it
        if ($data['item'] && $data['item']->maybeDeleted()) {
            $LINK = " AND ";
            if ($first) {
                $LINK  = " ";
                $first = false;
            }
            $COMMONWHERE .= $LINK . "`$itemtable`.`is_deleted` = " . (int) $data['search']['is_deleted'] . " ";
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

            if ($data['itemtype'] == Entity::class) {
                $COMMONWHERE .= getEntitiesRestrictRequest($LINK, $itemtable);
            } elseif (isset($CFG_GLPI["union_search_type"][$data['itemtype']])) {
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
        // If there are search items
        if (count($data['search']['criteria'])) {
            $WHERE  = self::constructCriteriaSQL($data['search']['criteria'], $data, $searchopt);
            $HAVING = self::constructCriteriaSQL($data['search']['criteria'], $data, $searchopt, true);

            // if criteria (with meta flag) need additional join/from SQL
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
                        'order'        => $data['search']['order'][$i] ?? null,
                    ];
                }
            }
        }
        if (count($sort_fields)) {
            $ORDER = Search::addOrderBy($data['itemtype'], $sort_fields);
        } elseif ($data['search']['disable_order_by_fallback'] ?? false) {
            // Sort isn't requested by the user and fallback is disabled
            // -> No `ORDER BY` clause for this search request
            $ORDER = "";
        }

        $SELECT = rtrim(trim($SELECT), ',');

        //// 7 - Manage GROUP BY
        $GROUPBY = "";
        // Meta Search / Search All / Count tickets
        $criteria_with_meta = array_filter($data['search']['criteria'], fn($criterion) => isset($criterion['meta'])
            && $criterion['meta']);
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
        //No search: count number of items using a simple count(ID) request and LIMIT search
        if ($data['search']['no_search']) {
            $LIMIT = " LIMIT " . (int) $data['search']['start'] . ", " . (int) $data['search']['list_limit'];

            $count = "count(DISTINCT `$itemtable`.`id`)";
            // request currentuser for SQL supervision, not displayed
            $query_num = "SELECT $count,
                              " . $DB->quote($_SESSION['glpiname']) . " AS currentuser
                       FROM `$itemtable`"
                . $COMMONLEFTJOIN;

            $first     = true;

            if (!empty($COMMONWHERE)) {
                $LINK = " AND ";
                $LINK  = " WHERE ";
                $first = false;
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
                        if ($data['itemtype'] == AllAssets::getType()) {
                            $query_num  = str_replace(
                                $CFG_GLPI["union_search_type"][$data['itemtype']],
                                $ctable,
                                $tmpquery
                            );
                            $query_num  = str_replace($data['itemtype'], $DB->escape($ctype), $query_num);

                            $system_criteria_sql = self::getMainItemtypeSystemSQLCriteria($ctype);
                            if ($system_criteria_sql !== '') {
                                $query_num .= ' AND ' . $system_criteria_sql;
                            }

                            $query_num .= " AND `$ctable`.`id` IS NOT NULL ";

                            // Add deleted if item have it
                            if ($citem->maybeDeleted()) {
                                $query_num .= " AND `$ctable`.`is_deleted` = 0 ";
                            }

                            // Remove template items
                            if ($citem->maybeTemplate()) {
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
                                           AND `$reftable`.`itemtype` = '{$DB->escape($ctype)}')";

                            $query_num = str_replace(
                                "FROM `"
                                . $CFG_GLPI["union_search_type"][$data['itemtype']] . "`",
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
                $system_criteria_sql = self::getMainItemtypeSystemSQLCriteria($data['itemtype']);
                if ($system_criteria_sql !== '') {
                    $query_num .= (!empty($COMMONWHERE) ? ' AND ' : ' WHERE ') . $system_criteria_sql;
                }

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
                        $QUERY .= " UNION ALL ";
                    }
                    $tmpquery = "";
                    // AllAssets case
                    if ($data['itemtype'] == AllAssets::getType()) {
                        $tmpquery = $SELECT . ", '{$DB->escape($ctype)}' AS TYPE "
                            . $FROM
                            . $WHERE;

                        $system_criteria_sql = self::getMainItemtypeSystemSQLCriteria($ctype);
                        if ($system_criteria_sql !== '') {
                            $tmpquery .= ' AND ' . $system_criteria_sql;
                        }

                        $tmpquery .= " AND `$ctable`.`id` IS NOT NULL ";

                        // Add deleted if item have it
                        if ($citem->maybeDeleted()) {
                            $tmpquery .= " AND `$ctable`.`is_deleted` = 0 ";
                        }

                        // Remove template items
                        if ($citem->maybeTemplate()) {
                            $tmpquery .= " AND `$ctable`.`is_template` = 0 ";
                        }

                        $tmpquery .= $GROUPBY
                            . $HAVING;

                        // Replace 'asset_types' by itemtype table name
                        $tmpquery = str_replace(
                            $CFG_GLPI["union_search_type"][$data['itemtype']],
                            $ctable,
                            $tmpquery
                        );
                        // Replace 'AllAssets' by itemtype
                        // Use quoted value to prevent replacement of AllAssets in column identifiers
                        $tmpquery = str_replace(
                            $DB->quoteValue(AllAssets::getType()),
                            $DB->quoteValue($DB->escape($ctype)),
                            $tmpquery
                        );
                    } else {// Ref table case
                        $reftable = $data['itemtype']::getTable();

                        $tmpquery = $SELECT . ", '{$DB->escape($ctype)}' AS TYPE,
                                      `$reftable`.`id` AS refID, " . "
                                      `$ctable`.`entities_id` AS ENTITY "
                            . $FROM
                            . $WHERE;
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
                                     AND `$reftable`.`itemtype` = '{$DB->escape($ctype)}')";
                        $tmpquery = str_replace(
                            "FROM `"
                            . $CFG_GLPI["union_search_type"][$data['itemtype']] . "`",
                            $replace,
                            $tmpquery
                        );
                        $tmpquery = str_replace(
                            $CFG_GLPI["union_search_type"][$data['itemtype']] . '_TYPE',
                            $ctype,
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

                    // Performance optimization: limit each subqueries returned rows to the max number of items we need
                    // to display.
                    // This help a lot on MariaDB as it seems less smart than MySQL when dealing with these kind of
                    // UNION requests
                    // This can however only by done on the first page of a result set as it would break pagination on
                    // other pages.
                    // Sorted request also can't benefit from this optimization
                    if (
                        empty($ORDER) // No sort clause is defined
                        && $data['search']['start'] == 0 // First page of results
                    ) {
                        $tmpquery .= " LIMIT " . $data['search']['list_limit'];
                    }

                    // Wrap inner union queries to support potential limit clause
                    $QUERY .= "(" . $tmpquery . ")";
                }
            }
            if (empty($QUERY)) {
                echo Search::showError($data['display_type']);
                Profiler::getInstance()->stop('SQLProvider::constructSQL');
                return;
            }
            $QUERY .= str_replace($CFG_GLPI["union_search_type"][$data['itemtype']] . ".", "", $ORDER)
                . $LIMIT;
        } else {
            $system_criteria_sql = self::getMainItemtypeSystemSQLCriteria($data['itemtype']);
            if ($system_criteria_sql !== '') {
                $WHERE .= (!empty($WHERE) ? ' AND ' : ' WHERE ') . $system_criteria_sql;
            }

            $data['sql']['raw'] = [
                'SELECT' => $SELECT,
                'FROM' => $FROM,
                'WHERE' => $WHERE,
                'GROUPBY' => $GROUPBY,
                'HAVING' => $HAVING,
                'ORDER' => $ORDER,
                'LIMIT' => $LIMIT,
            ];
            $QUERY = $SELECT
                . $FROM
                . $WHERE
                . $GROUPBY
                . $HAVING
                . $ORDER
                . $LIMIT;
        }
        $data['sql']['search'] = $QUERY;
        Profiler::getInstance()->stop('SQLProvider::constructSQL');
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
                $meta_searchopt = SearchOption::getOptionsForItemtype($itemtype);
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
                $NOT     = false;
                $tmplink = "";

                if (
                    isset($criterion['link'])
                    && in_array($criterion['link'], array_keys(SearchEngine::getLogicalOperators()))
                ) {
                    if (strstr($criterion['link'], "NOT")) {
                        $tmplink = " " . str_replace(" NOT", "", $criterion['link']);
                        $NOT     = true;
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
                } elseif (
                    isset($meta_searchopt[$criterion['field']]["usehaving"])
                    || ($meta && "AND NOT" === $criterion['link'])
                ) {
                    if (!$is_having) {
                        // the having part will be managed in a second pass
                        continue;
                    }

                    $new_having = Search::addHaving(
                        $LINK,
                        $NOT,
                        $itemtype,
                        $criterion['field'],
                        $criterion['searchtype'],
                        $criterion['value']
                    );
                    $sql .= $new_having;
                } else {
                    if ($is_having) {
                        // the having part has been already managed in the first pass
                        continue;
                    }

                    $new_where = Search::addWhere(
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
            } elseif (
                isset($criterion['value'])
                && strlen($criterion['value']) > 0
            ) { // view and all search
                $LINK       = " OR ";
                $NOT        = false;
                $globallink = " AND ";
                if (isset($criterion['link'])) {
                    switch ($criterion['link']) {
                        case "AND":
                            $LINK       = ($criterion['searchtype'] == 'notcontains') ? ' AND ' : ' OR ';
                            $globallink = " AND ";
                            break;
                        case "AND NOT":
                            $LINK       = ($criterion['searchtype'] == 'notcontains') ? ' OR ' : ' AND ';
                            $NOT        = true;
                            $globallink = " AND ";
                            break;
                        case "OR":
                            $LINK       = ($criterion['searchtype'] == 'notcontains') ? ' AND ' : ' OR ';
                            $globallink = " OR ";
                            break;
                        case "OR NOT":
                            $LINK       = ($criterion['searchtype'] == 'notcontains') ? ' OR ' : ' AND ';
                            $NOT        = true;
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
                    if (!preg_match(QueryBuilder::getInputValidationPattern($val2['datatype'] ?? '')['pattern'], $criterion['value'])) {
                        // Do not add a clause on the current field if the searched term does not match the exepected pattern.
                        // For instance, do not filter on date fields if the searched value is a word.
                        continue;
                    }

                    if (is_array($val2)) {
                        // Add Where clause if not to be done in HAVING CLAUSE
                        if (!$is_having && !isset($val2["usehaving"])) {
                            $tmplink = $LINK;
                            if ($first2) {
                                $tmplink = " ";
                            }

                            $new_where = Search::addWhere(
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
     * Construct additional SQL (select, joins, etc) for meta-criteria
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
            $metaopt = SearchOption::getOptionsForItemtype($m_itemtype);
            $sopt    = $metaopt[$criterion['field']];

            //add toview for meta criterion
            $data['meta_toview'][$m_itemtype][] = $criterion['field'];

            $SELECT .= Search::addSelect(
                $m_itemtype,
                $criterion['field'],
                true, // meta-criterion
                $m_itemtype
            );

            $FROM .= Search::addMetaLeftJoin(
                $data['itemtype'],
                $m_itemtype,
                $already_link_tables,
                $sopt["joinparams"]
            );

            $ref_table = $m_itemtype::getTable() . self::getMetaTableUniqueSuffix($m_itemtype::getTable(), $m_itemtype);
            $FROM .= Search::addLeftJoin(
                $m_itemtype,
                $ref_table,
                $already_link_tables,
                $sopt["table"],
                $sopt["linkfield"],
                true,
                $m_itemtype,
                $sopt["joinparams"],
                $sopt["field"]
            );
        }
    }

    #[Override]
    public static function constructData(array &$data, $onlycount = false)
    {
        if (!isset($data['sql']) || !isset($data['sql']['search'])) {
            return false;
        }
        $data['data'] = [];

        Profiler::getInstance()->start('SQLProvider::constructData', Profiler::CATEGORY_SEARCH);
        // Use a ReadOnly connection if available and configured to be used
        $DBread = DBConnection::getReadConnection();
        $DBread->doQuery("SET SESSION group_concat_max_len = 8194304;");

        $DBread->execution_time = true;
        $result = $DBread->doQuery($data['sql']['search']);

        if ($result) {
            $data['data']['execution_time'] = $DBread->execution_time;
            if (isset($data['search']['savedsearches_id'])) {
                SavedSearch::updateExecutionTime(
                    (int) $data['search']['savedsearches_id'],
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
                        $result_num = $DBread->doQuery($sqlcount);
                        $data['data']['totalcount'] += $DBread->result($result_num, 0, 0);
                    }
                }
            }

            if ($onlycount) {
                Profiler::getInstance()->stop('SQLProvider::constructData');
                //we just want to count results; no need to continue process
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

            Profiler::getInstance()->start('SQLProvider::constructData - get options for main itemtype', Profiler::CATEGORY_SEARCH);
            $searchopt = SearchOption::getOptionsForItemtype($data['itemtype']);
            Profiler::getInstance()->stop('SQLProvider::constructData - get options for main itemtype');

            foreach ($data['toview'] as $opt_id) {
                $data['data']['cols'][] = [
                    'itemtype'  => $data['itemtype'],
                    'id'        => $opt_id,
                    'name'      => $searchopt[$opt_id]["name"],
                    'meta'      => 0,
                    'searchopt' => $searchopt[$opt_id],
                ];
            }

            Profiler::getInstance()->start('SQLProvider::constructData - get options for meta toview cols', Profiler::CATEGORY_SEARCH);
            // manage toview column for criteria with meta flag
            foreach ($data['meta_toview'] as $m_itemtype => $toview) {
                $m_searchopt = SearchOption::getOptionsForItemtype($m_itemtype);
                foreach ($toview as $opt_id) {
                    $data['data']['cols'][] = [
                        'itemtype'  => $m_itemtype,
                        'id'        => $opt_id,
                        'name'      => $m_searchopt[$opt_id]["name"],
                        'meta'      => 1,
                        'searchopt' => $m_searchopt[$opt_id],
                        'groupname' => $m_itemtype,
                    ];
                }
            }
            Profiler::getInstance()->stop('SQLProvider::constructData - get options for meta toview cols');

            // Display columns Headers for meta items
            $already_printed = [];

            Profiler::getInstance()->start('SQLProvider::constructData - get options for meta criteria');
            if (count($data['search']['metacriteria'])) {
                foreach ($data['search']['metacriteria'] as $metacriteria) {
                    if (
                        isset($metacriteria['itemtype']) && !empty($metacriteria['itemtype'])
                        && isset($metacriteria['value']) && (strlen($metacriteria['value']) > 0)
                    ) {
                        if (!isset($already_printed[$metacriteria['itemtype'] . $metacriteria['field']])) {
                            $m_searchopt = SearchOption::getOptionsForItemtype($metacriteria['itemtype']);

                            $data['data']['cols'][] = [
                                'itemtype'  => $metacriteria['itemtype'],
                                'id'        => $metacriteria['field'],
                                'name'      => $m_searchopt[$metacriteria['field']]["name"],
                                'meta'      => 1,
                                'searchopt' => $m_searchopt[$metacriteria['field']],
                                'groupname' => $metacriteria['itemtype'],
                            ];

                            $already_printed[$metacriteria['itemtype'] . $metacriteria['field']] = 1;
                        }
                    }
                }
            }
            Profiler::getInstance()->stop('SQLProvider::constructData - get options for meta criteria');

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
                        is_numeric(key($searchopt))
                        && is_array(current($searchopt))
                    ) {
                        prev($searchopt);
                    }
                    if (
                        key($searchopt) !== "common"
                        && !isset($data['data']['cols'][$num]['groupname'])
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

            Search::$output_type = $data['display_type'];

            Profiler::getInstance()->start('SQLProvider::constructData - giveItem', Profiler::CATEGORY_SEARCH);
            Profiler::getInstance()->pause('SQLProvider::constructData - giveItem');
            while (($i < $data['data']['totalcount']) && ($i <= $data['data']['end'])) {
                $row = $DBread->fetchAssoc($result);

                $newrow        = [];
                $newrow['raw'] = $row;

                // Parse data
                foreach ($newrow['raw'] as $key => $val) {
                    $matches = [];
                    if (preg_match('/^ITEM(_(?<itemtype>[a-z][\w\\\]*?))?_(?<num>\d+)(_(?<fieldname>.+))?$/i', $key, $matches)) {
                        $j = (!empty($matches['itemtype']) ? $matches['itemtype'] . '_' : '') . $matches['num'];
                        $fieldname = $matches['fieldname'] ?? 'name';

                        // No Group_concat case
                        if ($fieldname == 'content' || !is_string($val) || !str_contains($val, Search::LONGSEP)) {
                            $newrow[$j]['count'] = 1;

                            $handled = false;
                            if ($fieldname != 'content' && is_string($val) && str_contains($val, Search::SHORTSEP)) {
                                $split2                    = Search::explodeWithID(Search::SHORTSEP, $val);
                                if ($j == "User_80") {
                                    $newrow[$j][0][$fieldname] = $split2[0];
                                    $newrow[$j][0]["profiles_id"] = $split2[1];
                                    $newrow[$j][0]["is_recursive"] = $split2[2];
                                    $newrow[$j][0]["is_dynamic"] = $split2[3];
                                    $handled = true;
                                } elseif ($j == "User_20") {
                                    $newrow[$j][0][$fieldname] = $split2[0];
                                    $newrow[$j][0]["entities_id"] = $split2[1];
                                    $newrow[$j][0]["is_recursive"] = $split2[2];
                                    $newrow[$j][0]["is_dynamic"] = $split2[3];
                                    $handled = true;
                                } elseif (is_numeric($split2[1])) {
                                    $newrow[$j][0][$fieldname] = $split2[0];
                                    $newrow[$j][0]['id']       = $split2[1];
                                    $handled = true;
                                }
                            }

                            if (!$handled) {
                                if ($val === Search::NULLVALUE) {
                                    $newrow[$j][0][$fieldname] = null;
                                } else {
                                    $newrow[$j][0][$fieldname] = $val;
                                }
                            }
                        } else {
                            if (!isset($newrow[$j])) {
                                $newrow[$j] = [];
                            }
                            $split               = explode(Search::LONGSEP, $val);
                            $newrow[$j]['count'] = count($split);
                            foreach ($split as $key2 => $val2) {
                                $handled = false;
                                if (str_contains($val2, Search::SHORTSEP)) {
                                    $split2                  = Search::explodeWithID(Search::SHORTSEP, $val2);
                                    if ($j == "User_80") {
                                        $newrow[$j][$key2][$fieldname] = $split2[0];
                                        $newrow[$j][$key2]["profiles_id"] = $split2[1];
                                        $newrow[$j][$key2]["is_recursive"] = $split2[2];
                                        $newrow[$j][$key2]["is_dynamic"] = $split2[3];
                                        $handled = true;
                                    } elseif ($j == "User_20") {
                                        $newrow[$j][$key2][$fieldname] = $split2[0];
                                        $newrow[$j][$key2]["entities_id"] = $split2[1];
                                        $newrow[$j][$key2]["is_recursive"] = $split2[2];
                                        $newrow[$j][$key2]["is_dynamic"] = $split2[3];
                                        $handled = true;
                                    } elseif (is_numeric($split2[1])) {
                                        $newrow[$j][$key2]['id'] = $split2[1];
                                        if ($split2[0] == Search::NULLVALUE) {
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
                            if ($key == 'id' && $val !== null) {
                                $data['data']['items'][$val] = $i;
                            }
                        }
                    }
                }
                foreach ($data['data']['cols'] as $val) {
                    Profiler::getInstance()->resume('SQLProvider::constructData - giveItem');
                    $newrow[$val['itemtype'] . '_' . $val['id']]['displayname'] = self::giveItem(
                        $val['itemtype'],
                        $val['id'],
                        $newrow
                    );
                    Profiler::getInstance()->pause('SQLProvider::constructData - giveItem');
                }

                $data['data']['rows'][$i] = $newrow;
                $i++;
            }
            Profiler::getInstance()->stop('SQLProvider::constructData - giveItem');

            $data['data']['count'] = count($data['data']['rows']);
        } else {
            $error_no = $DBread->errno();
            if ($error_no == 1116) { // Too many tables; MySQL can only use 61 tables in a join
                echo Search::showError(
                    $data['search']['display_type'],
                    __("'All' criterion is not usable with this object list, "
                        . "sql query fails (too many tables). "
                        . "Please use 'Items seen' criterion instead")
                );
            }
        }
        Profiler::getInstance()->stop('SQLProvider::constructData');
    }

    /**
     * Create SQL search condition
     *
     * @param string  $field  Name (should be ` protected)
     * @param string  $val    Value to search
     * @param boolean $not    Is a negative search ? (false by default)
     * @param string  $link   With previous criteria (default 'AND')
     *
     * @return string Search SQL string
     **/
    public static function makeTextCriteria($field, $val, $not = false, $link = 'AND')
    {

        $sql = $field . self::makeTextSearch($val, $not);

        if (strtolower($val) == "null") {
            // FIXME
            // `OR field = ''` condition is not supposed to be relevant, and can sometimes result in SQL performances issues/warnings/errors,
            // when following datatype are used:
            //  - integer
            //  - number
            //  - decimal
            //  - count
            //  - mio
            //  - percentage
            //  - timestamp
            //  - datetime
            //  - date_delay
            //
            // Removing this condition requires, at least, to use the `int`/`float`/`double`/`timestamp`/`date` types in DB,
            // to ensure that the `''` value will not be stored in DB.

            if ($not) {
                $sql .= " AND $field <> ''";
            } else {
                $sql .= " OR $field = ''";
            }
        }

        if (
            ($not && ($val != 'NULL') && ($val != 'null') && ($val != '^$'))    // Not something
            || (!$not && ($val == '^$'))
        ) {   // Empty
            $sql = "($sql OR $field IS NULL)";
        }
        return " $link ($sql)";
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
        // Backslashes must be doubled in LIKE clause, according to MySQL documentation:
        // https://dev.mysql.com/doc/refman/8.0/en/string-comparison-functions.html
        // > To search for \, specify it as \\\\; this is because the backslashes are stripped once by the parser
        // > and again when the pattern match is made, leaving a single backslash to be matched against.
        //
        // At this point, we escape backslashes, that will then be escaped a second time when request will be sent.
        $val = str_replace('\\', '\\\\', $val);

        // escape _ char used as wildcard in mysql likes
        $val = str_replace('_', '\_', $val);

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
            // Add % wildcard before searched string if not beginning by a `^`
            $val = '%' . $val;
        }

        if (preg_match('/\$$/', $val)) {
            // Remove trailing `$`
            $val = rtrim(preg_replace('/\$$/', '', $val));
        } else {
            // Add % wildcard after searched string if not ending by a `$`
            $val .= '%';
        }

        return $val;
    }

    /**
     * Create SQL search condition
     *
     * @param string  $val  Value to search
     * @param boolean $not  Is a negative search? (false by default)
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
            $SEARCH = " $NOT LIKE " . DBmysql::quoteValue($search_val) . " ";
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
     * @param boolean $meta            is a meta item? (default false)
     * @param array   $addobjectparams array added parameters for union search
     * @param string  $orig_itemtype   Original itemtype, used for union_search_type
     *
     * @return string String to print
     **/
    public static function giveItem(
        $itemtype,
        $ID,
        array $data,
        $meta = false,
        array $addobjectparams = [],
        $orig_itemtype = null
    ) {
        global $CFG_GLPI;

        $searchopt = SearchOption::getOptionsForItemtype($itemtype);
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

            // Search option may not exist in subtype
            // This is the case for "Inventory number" for a Software listed from ReservationItem search
            $subtype_so = SearchOption::getOptionsForItemtype($data["TYPE"]);
            if (!array_key_exists($ID, $subtype_so)) {
                return '';
            }

            return self::giveItem($data["TYPE"], $ID, $data, $meta, $oparams, $itemtype);
        }
        $so = $searchopt[$ID];
        $so['id'] = $ID; // Keep track of search option id so it can be used by functions using $so as a parameter
        $orig_id = $ID;
        $ID = ($orig_itemtype ?? $itemtype) . '_' . $ID;

        if (count($addobjectparams)) {
            $so = array_merge($so, $addobjectparams);
        }
        // Plugin can override core definition for its type
        if ($plug = isPluginItemType($itemtype)) {
            $out = Plugin::doOneHook(
                $plug['plugin'],
                Hooks::AUTO_GIVE_ITEM,
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
            Search::$output_type,
            [
                Search::HTML_OUTPUT,
                Search::GLOBAL_SEARCH, // For a global search, output will be done in HTML context
            ]
        );

        if (isset($so["table"])) {
            $table        = $so["table"];
            $field        = $so["field"];
            $linkfield    = $so["linkfield"];
            $opt_itemtype = $so['itemtype'] ?? getItemTypeForTable($table);

            /// TODO try to clean all specific cases using SpecificToDisplay

            switch ($table . '.' . $field) {
                case "glpi_users.name":
                    // USER search case
                    if (
                        ($itemtype != User::class)
                        && isset($so["forcegroupby"]) && $so["forcegroupby"]
                    ) {
                        $out           = "";
                        $count_display = 0;
                        $added         = [];

                        for ($k = 0; $k < $data[$ID]['count']; $k++) {
                            if (
                                (isset($data[$ID][$k]['name']) && ($data[$ID][$k]['name'] > 0))
                                || (isset($data[$ID][$k][2]) && ($data[$ID][$k][2] != ''))
                            ) {
                                if ($count_display) {
                                    $out .= Search::LBBR;
                                }

                                if (isset($data[$ID][$k]['name']) && $data[$ID][$k]['name'] > 0) {
                                    if (is_subclass_of($itemtype, CommonITILObject::class)) {
                                        if (
                                            Session::getCurrentInterface() == 'helpdesk'
                                            && $orig_id == 5 // -> Assigned user
                                            && !empty($anon_name = User::getAnonymizedNameForUser(
                                                $data[$ID][$k]['name'],
                                                $itemtype::getById($data['id'])->getEntityId()
                                            ))
                                        ) {
                                            $out .= \htmlescape($anon_name);
                                        } else {
                                            $user = new User();
                                            if ($user->getFromDB($data[$ID][$k]['name'])) {
                                                $tooltip = "";
                                                if (Session::haveRight('user', READ)) {
                                                    $tooltip = Html::showToolTip(
                                                        $user->getInfoCard(),
                                                        [
                                                            'link'    => $user->getLinkURL(),
                                                            'display' => false,
                                                        ]
                                                    );
                                                }
                                                $out .= sprintf(__s('%1$s %2$s'), htmlescape($user->getName()), $tooltip);
                                            }
                                        }

                                        $count_display++;
                                    } else {
                                        $out .= getUserLink($data[$ID][$k]['name']);
                                        $count_display++;
                                    }
                                }

                                // Manage alternative_email for tickets_users
                                if (
                                    is_subclass_of($itemtype, CommonITILObject::class)
                                    && isset($data[$ID][$k][2])
                                ) {
                                    $split = explode(Search::LONGSEP, $data[$ID][$k][2]);
                                    $counter = count($split);
                                    for ($l = 0; $l < $counter; $l++) {
                                        $split2 = explode(" ", $split[$l]);
                                        if ((count($split2) == 2) && ($split2[0] == 0) && !empty($split2[1])) {
                                            if ($count_display) {
                                                $out .= Search::LBBR;
                                            }
                                            $count_display++;
                                            $out .= "<a href='mailto:" . \htmlescape($split2[1]) . "'>" . \htmlescape($split2[1]) . "</a>";
                                        }
                                    }
                                }
                            }
                        }
                        return $out;
                    }
                    if ($itemtype != User::class) {
                        $out = '';
                        if ($data[$ID][0]['id'] > 0) {
                            $toadd = '';
                            if (is_subclass_of($itemtype, CommonITILObject::class)) {
                                $user = new User();
                                if (Session::haveRight('user', READ) && $user->getFromDB($data[$ID][0]['id'])) {
                                    $toadd    = Html::showToolTip(
                                        $user->getInfoCard(),
                                        [
                                            'link'    => $user->getLinkURL(),
                                            'display' => false,
                                        ]
                                    );
                                }
                            }
                            $userlink = formatUserLink(
                                $data[$ID][0]['id'],
                                $data[$ID][0]['name'],
                                $data[$ID][0]['realname'],
                                $data[$ID][0]['firstname'],
                            );
                            $out = sprintf(__s('%1$s %2$s'), $userlink, $toadd);
                        }
                        return $out;
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
                        ($itemtype == User::class)
                        && ($orig_id == 20)
                    ) {
                        $out           = "";

                        $count_display = 0;
                        $added         = [];
                        for ($k = 0; $k < $data[$ID]['count']; $k++) {
                            if (
                                isset($data[$ID][$k]['name'])
                                && strlen(trim($data[$ID][$k]['name'])) > 0
                                && !in_array(
                                    $data[$ID][$k]['name'] . "-" . $data[$ID][$k]['entities_id'],
                                    $added
                                )
                            ) {
                                $text = sprintf(
                                    __('%1$s - %2$s'),
                                    $data[$ID][$k]['name'],
                                    Dropdown::getDropdownName(
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
                                    $out .= Search::LBBR;
                                }
                                $count_display++;
                                $out     .= htmlescape($text);
                                $added[]  = $data[$ID][$k]['name'] . "-" . $data[$ID][$k]['entities_id'];
                            }
                        }
                        return $out;
                    }
                    break;

                case "glpi_entities.completename":
                    if ($itemtype == User::class) {
                        $out           = "";
                        $added         = [];
                        $count_display = 0;
                        for ($k = 0; $k < $data[$ID]['count']; $k++) {
                            $completename = isset($data[$ID][$k]['name']) && (strlen(trim($data[$ID][$k]['name'])) > 0)
                                ? (new SanitizedStringsDecoder())->decodeHtmlSpecialCharsInCompletename($data[$ID][$k]['name'])
                                : null;
                            if (
                                $completename !== null
                                && !in_array(
                                    $data[$ID][$k]['name'] . "-" . $data[$ID][$k]['profiles_id'],
                                    $added
                                )
                            ) {
                                $text = sprintf(
                                    __s('%1$s - %2$s'),
                                    Entity::badgeCompletename($data[$ID][$k]['name']),
                                    htmlescape(Dropdown::getDropdownName('glpi_profiles', $data[$ID][$k]['profiles_id']))
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
                                    $text = sprintf(__s('%1$s %2$s'), $text, htmlescape("(" . $comp . ")"));
                                }
                                if ($count_display) {
                                    $out .= Search::LBBR;
                                }
                                $count_display++;
                                $out    .= $text;
                                $added[] = $data[$ID][$k]['name'] . "-" . $data[$ID][$k]['profiles_id'];
                            }
                        }
                        return $out;
                    } elseif (($so["datatype"] ?? "") != "itemlink" && !empty($data[$ID][0]['name'])) {
                        $completename = (new SanitizedStringsDecoder())->decodeHtmlSpecialCharsInCompletename($data[$ID][0]['name']);
                        if ($html_output) {
                            if (!$_SESSION['glpiuse_flat_dropdowntree_on_search_result']) {
                                $split_name = explode(">", $completename);
                                $entity_name = trim(end($split_name));
                                return Entity::badgeCompletename($entity_name, $completename);
                            }
                            return Entity::badgeCompletename($completename);
                        } else { //export
                            if (!$_SESSION['glpiuse_flat_dropdowntree_on_search_result']) {
                                $split_name = explode(">", $completename);
                                $entity_name = trim(end($split_name));
                                return htmlescape($entity_name);
                            }
                            return htmlescape($completename);
                        }
                    }
                    break;
                case $table . ".completename":
                    if (
                        $itemtype != $opt_itemtype
                        && $data[$ID][0]['name'] != null //column have value in DB
                        && !$_SESSION['glpiuse_flat_dropdowntree_on_search_result'] //user doesn't want the completename
                    ) {
                        $completename = (new SanitizedStringsDecoder())->decodeHtmlSpecialCharsInCompletename($data[$ID][0]['name']);
                        $split_name = explode(">", $completename);
                        return htmlescape(trim(end($split_name)));
                    }
                    break;

                case "glpi_documenttypes.icon":
                    if (!empty($data[$ID][0]['name'])) {
                        return "<img class='middle' alt='' src='" . htmlescape($CFG_GLPI["typedoc_icon_dir"] . "/" . $data[$ID][0]['name']) . "'>";
                    }
                    return '';

                case "glpi_documents.filename":
                    $doc = new Document();
                    if ($doc->getFromDB($data['id'])) {
                        return $doc->getDownloadLink();
                    }
                    return \htmlescape(NOT_AVAILABLE);

                case "glpi_tickets_tickets.tickets_id_1":
                    $out        = "";
                    $displayed  = [];
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        $linkid = ($data[$ID][$k]['tickets_id_2'] == $data['id'])
                            ? $data[$ID][$k]['name']
                            : $data[$ID][$k]['tickets_id_2'];

                        // If link ID is int or integer string, force conversion to int. Conversion to int and then string to compare is needed to ensure it isn't a decimal
                        if (is_numeric($linkid) && ((string) (int) $linkid === (string) $linkid)) {
                            $linkid = (int) $linkid;
                        }
                        if ((is_int($linkid) && $linkid > 0) && !isset($displayed[$linkid])) {
                            $link_text = Dropdown::getDropdownName('glpi_tickets', $linkid);
                            if ($_SESSION["glpiis_ids_visible"] || empty($link_text)) {
                                $link_text = sprintf(__('%1$s (%2$s)'), $link_text, $linkid);
                            }
                            $text  = "<a ";
                            $text .= "href=\"" . htmlescape(Ticket::getFormURLWithID($linkid)) . "\">";
                            $text .= \htmlescape($link_text) . "</a>";
                            if (count($displayed)) {
                                $out .= Search::LBBR;
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
                            && Session::haveRight("problem", Problem::READALL)
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

                            $out  = "<a id='problem" . \htmlescape($itemtype . $data['id']) . "' ";
                            $out .= "href=\"" . \htmlescape($CFG_GLPI["root_doc"] . "/front/problem.php?" . Toolbox::append_params($options, '&')) . "\">";
                            $out .= \htmlescape($data[$ID][0]['name']) . "</a>";
                            return $out;
                        }
                    }
                    break;

                case "glpi_tickets.id":
                    if ($so["datatype"] == 'count') {
                        if (
                            ($data[$ID][0]['name'] > 0)
                            && Session::haveRight("ticket", Ticket::READALL)
                        ) {
                            if ($itemtype == User::class) {
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
                            } elseif ($itemtype == ITILCategory::class) {
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

                            $out  = "<a id='ticket" . \htmlescape($itemtype . $data['id']) . "' ";
                            $out .= "href=\"" . \htmlescape($CFG_GLPI["root_doc"] . "/front/ticket.php?" . Toolbox::append_params($options, '&')) . "\">";
                            $out .= \htmlescape($data[$ID][0]['name']) . "</a>";
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
                        $out = htmlescape(Html::convDateTime($data[$ID][0]['name']));

                        $color = null;
                        if (
                            $data[$ID][0]['status'] == CommonITILObject::WAITING
                        ) {
                            // No due date in waiting status for TTRs
                            if (
                                $table . '.' . $field == "glpi_tickets.time_to_resolve"
                                || $table . '.' . $field == "glpi_tickets.internal_time_to_resolve"
                            ) {
                                return '';
                            } else {
                                $color = '#AAAAAA';
                            }
                        }

                        if (empty($data[$ID][0]['name'])) {
                            return '';
                        }
                        if (
                            ($data[$ID][0]['status'] == Ticket::SOLVED)
                            || ($data[$ID][0]['status'] == Ticket::CLOSED)
                        ) {
                            return $out;
                        }

                        $itemtype = $opt_itemtype;
                        $item = getItemForItemtype($itemtype);
                        $item->getFromDB($data['id']);
                        $percentage  = 0;
                        $totaltime   = 0;
                        $currenttime = 0;
                        $slaField    = 'slas_id';
                        $sla_class   = SLA::class;

                        // define correct sla field
                        switch ($table . '.' . $field) {
                            case "glpi_tickets.time_to_resolve":
                                $slaField = 'slas_id_ttr';
                                break;
                            case "glpi_tickets.time_to_own":
                                $slaField = 'slas_id_tto';
                                break;
                            case "glpi_tickets.internal_time_to_own":
                                $slaField = 'olas_id_tto';
                                $sla_class = OLA::class;
                                break;
                            case "glpi_tickets.internal_time_to_resolve":
                                $slaField = 'olas_id_ttr';
                                $sla_class = OLA::class;
                                break;
                        }

                        switch ($table . '.' . $field) {
                            // If ticket has been taken into account: no progression display
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
                            $waitingtime = $slaField === 'slas_id_ttr' ? $item->fields['sla_waiting_duration'] : 0;
                        } else {
                            $calendars_id = Entity::getUsedConfig(
                                'calendars_strategy',
                                $item->fields['entities_id'],
                                'calendars_id',
                                0
                            );
                            $calendar = new Calendar();
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
                            $waitingtime = 0;
                        }
                        if (($totaltime - $waitingtime) != 0) {
                            $percentage = round((100 * ($currenttime - $waitingtime)) / ($totaltime - $waitingtime));
                        } else {
                            // Total time is null: no active time
                            $percentage = 100;
                        }
                        if ($percentage > 100) {
                            $percentage = 100;
                        }
                        $percentage_text = $percentage;

                        $less_warn_limit = 0;
                        $less_warn       = 0;
                        if ($_SESSION['glpiduedatewarning_unit'] == '%') {
                            $less_warn_limit = $_SESSION['glpiduedatewarning_less'];
                            $less_warn       = (100 - $percentage);
                        } elseif ($_SESSION['glpiduedatewarning_unit'] == 'hour') {
                            $less_warn_limit = $_SESSION['glpiduedatewarning_less'] * HOUR_TIMESTAMP;
                            $less_warn       = ($totaltime - $currenttime);
                        } elseif ($_SESSION['glpiduedatewarning_unit'] == 'day') {
                            $less_warn_limit = $_SESSION['glpiduedatewarning_less'] * DAY_TIMESTAMP;
                            $less_warn       = ($totaltime - $currenttime);
                        }

                        $less_crit_limit = 0;
                        $less_crit       = 0;
                        if ($_SESSION['glpiduedatecritical_unit'] == '%') {
                            $less_crit_limit = $_SESSION['glpiduedatecritical_less'];
                            $less_crit       = (100 - $percentage);
                        } elseif ($_SESSION['glpiduedatecritical_unit'] == 'hour') {
                            $less_crit_limit = $_SESSION['glpiduedatecritical_less'] * HOUR_TIMESTAMP;
                            $less_crit       = ($totaltime - $currenttime);
                        } elseif ($_SESSION['glpiduedatecritical_unit'] == 'day') {
                            $less_crit_limit = $_SESSION['glpiduedatecritical_less'] * DAY_TIMESTAMP;
                            $less_crit       = ($totaltime - $currenttime);
                        }

                        if ($color === null) {
                            $color = $_SESSION['glpiduedateok_color'];
                            if ($less_crit < $less_crit_limit) {
                                $color = $_SESSION['glpiduedatecritical_color'];
                            } elseif ($less_warn < $less_warn_limit) {
                                $color = $_SESSION['glpiduedatewarning_color'];
                            }
                        }

                        if (!isset($so['datatype'])) {
                            $so['datatype'] = 'progressbar';
                        }

                        $progressbar_data = [
                            'text'         => Html::convDateTime($data[$ID][0]['name']),
                            'percent'      => $percentage,
                            'percent_text' => $percentage_text,
                            'color'        => $color,
                        ];
                    } else {
                        $is_late = false;

                        $value = $data[$ID][0]['name'];
                        $status = $data[$ID][0]['status'];

                        switch ($table . "." . $field) {
                            case "glpi_tickets.time_to_resolve":
                            case "glpi_tickets.internal_time_to_resolve":
                            case "glpi_problems.time_to_resolve":
                            case "glpi_changes.time_to_resolve":
                                $solve_date = $data[$ID][0]['solvedate'];

                                $is_late = !empty($value)
                                    && $status != CommonITILObject::WAITING
                                    && (
                                        $solve_date > $value
                                        || ($solve_date == null && $value < $_SESSION['glpi_currenttime'])
                                    );
                                break;
                            case "glpi_tickets.time_to_own":
                            case "glpi_tickets.internal_time_to_own":
                                $opening_date = $data[$ID][0]['date'];
                                $tia_delay = $data[$ID][0]['takeintoaccount_delay_stat'];
                                $tia_date = $data[$ID][0]['takeintoaccountdate'];
                                // Fallback to old and incorrect computation for tickets saved before introducing takeintoaccountdate field
                                if ($tia_delay > 0 && $tia_date == null) {
                                    $tia_date = strtotime($opening_date) + $tia_delay;
                                }

                                $is_late = !empty($value)
                                    && $status != CommonITILObject::WAITING
                                    && (
                                        $tia_date > $value
                                        || ($tia_date == null && $value < $_SESSION['glpi_currenttime'])
                                    );
                        }
                        if ($is_late) {
                            return "<div class='badge_block' style='border-color: #cf9b9b'>
                        <span style='background: #cf9b9b'></span>&nbsp;" . \htmlescape($value) . "
                       </div>";
                        }
                    }
                    break;

                case "glpi_softwarelicenses.number":
                    if ($data[$ID][0]['min'] == -1) {
                        return __s('Unlimited');
                    }
                    if (empty($data[$ID][0]['name'])) {
                        return '';
                    }
                    return \htmlescape($data[$ID][0]['name']);

                case "glpi_reservationitems.comment":
                    if (empty($data[$ID][0]['name'])) {
                        $text = __s('None');
                    } else {
                        $text = Html::resume_text(RichText::getTextFromHtml($data[$ID][0]['name']));
                    }
                    if (Session::haveRight('reservation', UPDATE)) {
                        return "<a title=\"" . __s('Modify the comment') . "\"
                           href='" . \htmlescape(ReservationItem::getFormURLWithID($data['refID'])) . "' >" . $text . "</a>";
                    }
                    return $text;

                case 'glpi_crontasks.description':
                    $tmp = new CronTask();
                    return \htmlescape($tmp->getDescription($data[$ID][0]['name']));

                case 'glpi_changes.status':
                    $status = Change::getStatus($data[$ID][0]['name']);
                    return "<span class='text-nowrap'>"
                        . Change::getStatusIcon($data[$ID][0]['name']) . "&nbsp;" . \htmlescape($status)
                        . "</span>";

                case 'glpi_problems.status':
                    $status = Problem::getStatus($data[$ID][0]['name']);
                    return "<span class='text-nowrap'>"
                        . Problem::getStatusIcon($data[$ID][0]['name']) . "&nbsp;" . \htmlescape($status)
                        . "</span>";

                case 'glpi_tickets.status':
                    $status = Ticket::getStatus($data[$ID][0]['name']);
                    return "<span class='text-nowrap'>"
                        . Ticket::getStatusIcon($data[$ID][0]['name']) . "&nbsp;" . \htmlescape($status)
                        . "</span>";

                case 'glpi_projectstates.name':
                    $name = $data[$ID][0]['name'];
                    if (isset($data[$ID][0]['trans'])) {
                        $name = $data[$ID][0]['trans'];
                    }
                    $name = \htmlescape($name);
                    if ($itemtype == 'ProjectState') {
                        $out =   "<a href='" . \htmlescape(ProjectState::getFormURLWithID($data[$ID][0]["id"])) . "'>" . $name . "</a></div>";
                    } else {
                        if (isset($data[$ID][0]['color'])) {
                            $color = \htmlescape($data[$ID][0]['color']);
                            $out = "<div class='badge_block' style='border-color: $color'>
                        <span style='background: $color'></span>&nbsp;" . $name . "
                       </div>";
                        } else {
                            $out = $name;
                        }
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
                        if ($items !== []) {
                            return implode("<br>", $items);
                        }
                    }
                    return '';

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
                                    $itemtypes[] = \htmlescape($item->getTypeName());
                                }
                            }
                        }
                        if ($itemtypes !== []) {
                            return implode("<br>", $itemtypes);
                        }
                    }

                    return '';

                case 'glpi_tickets.name':
                case 'glpi_problems.name':
                case 'glpi_changes.name':
                    if (
                        isset($data[$ID][0]['id'])
                        && isset($data[$ID][0]['status'])
                    ) {
                        $link = $itemtype::getFormURLWithID($data[$ID][0]['id']);

                        $out  = "<a id='" . \htmlescape($itemtype . $data[$ID][0]['id']) . "' href=\"" . \htmlescape($link);
                        // Force solution tab if solved
                        if ($item = getItemForItemtype($itemtype)) {
                            /** @var CommonITILObject $item */
                            if (in_array($data[$ID][0]['status'], $item->getSolvedStatusArray())) {
                                $out .= "&amp;forcetab=" . \htmlescape($itemtype) . "$2";
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
                        $out    .= \htmlescape($name) . "</a>";

                        // Add tooltip
                        $id = $data[$ID][0]['id'];
                        $itemtype = $opt_itemtype;

                        $out     = sprintf(
                            __s('%1$s %2$s'),
                            $out,
                            Html::showToolTip(
                                __s('Loading...'),
                                [
                                    'applyto' => $itemtype . $data[$ID][0]['id'],
                                    'display' => false,
                                    'url'     => "/ajax/get_item_content.php?itemtype=$itemtype&items_id=$id",
                                ]
                            )
                        );
                        return $out;
                    }
                    break;

                case 'glpi_ticketsatisfactions.satisfaction':
                    if ($html_output) {
                        return TicketSatisfaction::displaySatisfaction(
                            $data[$ID][0]['name'],
                            $data['raw']['ITEM_Ticket_62_entities_id']
                        );
                    }
                    break;

                case 'glpi_changesatisfactions.satisfaction':
                    if ($html_output) {
                        return ChangeSatisfaction::displaySatisfaction(
                            $data[$ID][0]['name'],
                            $data['raw']['ITEM_Change_262_entities_id']
                        );
                    }
                    break;

                case 'glpi_projects._virtual_planned_duration':
                    return \htmlescape(
                        Html::timestampToString(
                            ProjectTask::getTotalPlannedDurationForProject($data["id"]),
                            false
                        )
                    );

                case 'glpi_projects._virtual_effective_duration':
                    return \htmlescape(
                        Html::timestampToString(
                            ProjectTask::getTotalEffectiveDurationForProject($data["id"]),
                            false
                        )
                    );

                case 'glpi_cartridgeitems._virtual':
                    return Cartridge::getCount(
                        $data["id"],
                        $data[$ID][0]['alarm_threshold'],
                        !$html_output
                    );

                case 'glpi_printers._virtual':
                    return Cartridge::getCountForPrinter(
                        $data["id"],
                        !$html_output
                    );

                case 'glpi_consumableitems._virtual':
                    return Consumable::getCount(
                        $data["id"],
                        $data[$ID][0]['alarm_threshold'],
                        !$html_output
                    );

                case 'glpi_links._virtual':
                    $out = '';
                    if (
                        ($item = getItemForItemtype($itemtype))
                        && $item->getFromDB($data['id'])
                    ) {
                        $data = Link::getLinksDataForItem($item);
                        $count_display = 0;
                        foreach ($data as $val) {
                            $links = Link::getAllLinksFor($item, $val);
                            foreach ($links as $link) {
                                if ($count_display) {
                                    $out .=  Search::LBBR;
                                }
                                $out .= $link;
                                $count_display++;
                            }
                        }
                    }
                    return $out;

                case 'glpi_reservationitems._virtual':
                    if ($data[$ID][0]['is_active']) {
                        return "<a href='reservation.php?reservationitems_id="
                            . \htmlescape($data["refID"]) . "' title=\"" . __s('See planning') . "\">"
                            . "<i class='ti ti-calendar'></i><span class='sr-only'>" . __s('See planning') . "</span></a>";
                    } else {
                        return '';
                    }

                    // no break
                case "glpi_tickets.priority":
                case "glpi_problems.priority":
                case "glpi_changes.priority":
                case "glpi_projects.priority":
                    $index = $data[$ID][0]['name'];
                    $color = \htmlescape($_SESSION["glpipriority_$index"]);
                    $name  = CommonITILObject::getPriorityName($index);
                    return "<div class='badge_block' style='border-color: $color'>
                        <span style='background: $color'></span>&nbsp;" . \htmlescape($name) . "
                       </div>";

                case "glpi_knowbaseitems.name":
                    global $DB;
                    $result = $DB->request([
                        'SELECT' => [
                            KnowbaseItem::getTable() . '.is_faq',
                            KnowbaseItem::getTable() . '.id',
                        ],
                        'FROM'   => KnowbaseItem::getTable(),
                        'LEFT JOIN' => [
                            Entity_KnowbaseItem::getTable() => [
                                'ON'  => [
                                    Entity_KnowbaseItem::getTable() => KnowbaseItem::getForeignKeyField(),
                                    KnowbaseItem::getTable()        => 'id',
                                ],
                            ],
                            KnowbaseItem_Profile::getTable() => [
                                'ON'  => [
                                    KnowbaseItem_Profile::getTable() => KnowbaseItem::getForeignKeyField(),
                                    KnowbaseItem::getTable()         => 'id',
                                ],
                            ],
                            Group_KnowbaseItem::getTable() => [
                                'ON'  => [
                                    Group_KnowbaseItem::getTable() => KnowbaseItem::getForeignKeyField(),
                                    KnowbaseItem::getTable()       => 'id',
                                ],
                            ],
                            KnowbaseItem_User::getTable() => [
                                'ON'  => [
                                    KnowbaseItem_User::getTable() => KnowbaseItem::getForeignKeyField(),
                                    KnowbaseItem::getTable()      => 'id',
                                ],
                            ],
                        ],
                        'WHERE'  => [
                            KnowbaseItem::getTable() . '.id' => $data[$ID][0]['id'],
                            'OR' => [
                                Entity_KnowbaseItem::getTable() . '.id' => ['>=', 0],
                                KnowbaseItem_Profile::getTable() . '.id' => ['>=', 0],
                                Group_KnowbaseItem::getTable() . '.id' => ['>=', 0],
                                KnowbaseItem_User::getTable() . '.id' => ['>=', 0],
                            ],
                        ],
                    ]);
                    $name = $data[$ID][0]['name'];
                    $icon_class = "";
                    $icon_title = "";
                    $href = KnowbaseItem::getFormURLWithID($data[$ID][0]['id']);
                    if (count($result) > 0) {
                        foreach ($result as $row) {
                            if ($row['is_faq']) {
                                $icon_class = "ti ti-help faq";
                                $icon_title = __s("This item is part of the FAQ");
                            }
                        }
                    } else {
                        $icon_class = "ti ti-eye-off not-published";
                        $icon_title = __s("This item is not published yet");
                    }
                    return "<div class='kb'> <i class='$icon_class' title='$icon_title'></i> <a href='" . \htmlescape($href) . "'>" . \htmlescape($name) . "</a></div>";
                case "glpi_certificates.date_expiration":
                    if (
                        !in_array($orig_id, [151, 158, 181, 186])
                        && !empty($data[$ID][0]['name'])
                    ) {
                        $date = $data[$ID][0]['name'];
                        $before = Entity::getUsedConfig('send_certificates_alert_before_delay', $_SESSION['glpiactive_entity']);
                        $color = ($date < $_SESSION['glpi_currenttime']) ? '#cf9b9b' : null;
                        if ($before) {
                            $before = date('Y-m-d', strtotime($_SESSION['glpi_currenttime'] . " + $before days"));
                            $color = match (true) {
                                $date < $_SESSION['glpi_currenttime'] => '#d63939',
                                $date < $before => '#de5d06',
                                $date >= $before => '#a1cf66',
                                default => null
                            };
                        }
                        if ($color === null) {
                            break;
                        }
                        return "<div class='badge_block' style='border-color: " . \htmlescape($color) . "'>
                        <span style='background: " . \htmlescape($color) . "'></span>&nbsp;" . \htmlescape($date) . "
                       </div>";
                    }
                    break;
                case "glpi_domains.date_expiration":
                    if (!empty($data[$ID][0]['name'])
                        && ($data[$ID][0]['name'] < $_SESSION['glpi_currenttime'])
                    ) {
                        return "<div class='badge_block' style='border-color: #cf9b9b'>
                        <span style='background: #cf9b9b'></span>&nbsp;" . \htmlescape($data[$ID][0]['name']) . "
                       </div>";
                    }

            }
        }

        //// Default case

        if (
            is_subclass_of($itemtype, CommonITILObject::class)
            && Session::getCurrentInterface() == 'helpdesk'
            && $orig_id == 8
            && !empty($anon_name = Group::getAnonymizedName(
                $itemtype::getById($data['id'])->getEntityId()
            ))
        ) {
            // Assigned groups
            return $anon_name;
        }

        // Link with plugin tables: need to know left join structure
        if (isset($table) && isset($field)) {
            if (preg_match("/^glpi_plugin_([a-z0-9]+)/", $table . '.' . $field, $matches)) {
                if (count($matches) == 2) {
                    $plug     = $matches[1];
                    $out = Plugin::doOneHook(
                        $plug,
                        Hooks::AUTO_GIVE_ITEM,
                        $itemtype,
                        $orig_id,
                        $data,
                        $ID
                    );
                    if (!empty($out)) {
                        // We assume that plugins returns a safe HTML string.
                        // Not escaping the plugin hook result is the only way to permit plugins to use HTML tags for a
                        // custom rendering.
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
                    $linkitemtype  = $so['itemtype'] ?? getItemTypeForTable($so["table"]);

                    $out           = "";
                    $count_display = 0;
                    $separate      = Search::LBBR;
                    if (isset($so['splititems']) && $so['splititems']) {
                        $separate = Search::LBHR;
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
                            if (isset($field) && $field === 'completename') {
                                $name = (new SanitizedStringsDecoder())->decodeHtmlSpecialCharsInCompletename($data[$ID][0]['name']);
                                $chunks = \explode(' > ', $name);
                                $completename = '';
                                foreach ($chunks as $key => $element_name) {
                                    $class = $key === array_key_last($chunks) ? '' : 'class="text-muted"';
                                    $separator = $key === array_key_last($chunks) ? '' : ' &gt; ';
                                    $completename .= sprintf('<span %s>%s</span>%s', $class, \htmlescape($element_name), $separator);
                                }
                                $name = $completename;
                            } else {
                                $name = \htmlescape($name);
                            }

                            $out  .= "<a id='" . \htmlescape($linkitemtype . "_" . $data['id'] . "_" . $data[$ID][$k]['id']) . "'"
                                . " href='" . \htmlescape($page) . "'>"
                                . $name . "</a>";
                        }
                    }
                    return $out;

                case "text":
                    $separate = Search::LBBR;
                    if (isset($so['splititems']) && $so['splititems']) {
                        $separate = Search::LBHR;
                    }

                    $out           = '';
                    $count_display = 0;
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if (strlen(trim((string) $data[$ID][$k]['name'])) > 0) {
                            if ($count_display) {
                                $out .= $separate;
                            }
                            $count_display++;

                            $plaintext = '';
                            if (isset($so['htmltext']) && $so['htmltext']) {
                                if ($html_output) {
                                    $plaintext = RichText::getTextFromHtml($data[$ID][$k]['name'], false, true);
                                } else {
                                    $plaintext = RichText::getTextFromHtml($data[$ID][$k]['name'], true, true);
                                }
                            } else {
                                $plaintext = $data[$ID][$k]['name'];
                            }

                            if ($html_output && (Toolbox::strlen($plaintext) > $CFG_GLPI['cut'])) {
                                $rand = mt_rand();
                                $popup_params = [
                                    'display'       => false,
                                    'awesome-class' => 'fa-comments',
                                    'autoclose'     => false,
                                    'onclick'       => true,
                                ];
                                $out .= sprintf(
                                    __s('%1$s %2$s'),
                                    "<span id='text$rand'>" . Html::resume_text($plaintext, $CFG_GLPI['cut']) . '</span>',
                                    Html::showToolTip(
                                        '<div class="fup-popup">' . RichText::getEnhancedHtml($data[$ID][$k]['name']) . '</div>',
                                        $popup_params
                                    )
                                );
                            } else {
                                $out .= \htmlescape($plaintext);
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
                            $out .= (empty($out) ? '' : Search::LBBR) . \htmlescape($so['emptylabel']);
                        } else {
                            $out .= (empty($out) ? '' : Search::LBBR) . \htmlescape(Html::convDate($data[$ID][$k]['name']));
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
                            $out .= (empty($out) ? '' : Search::LBBR) . \htmlescape($so['emptylabel']);
                        } else {
                            $out .= (empty($out) ? '' : Search::LBBR) . \htmlescape(Html::convDateTime($data[$ID][$k]['name']));
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
                        $out .= (empty($out) ? '' : '<br>')
                            . \htmlescape(
                                Html::timestampToString(
                                    $data[$ID][$k]['name'],
                                    $withseconds,
                                    $withdays
                                )
                            );
                    }
                    $out = "<span class='text-nowrap'>$out</span>";
                    return $out;

                case "email":
                    $out           = '';
                    $count_display = 0;
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if ($count_display) {
                            $out .= Search::LBBR;
                        }
                        $count_display++;
                        if (!empty($data[$ID][$k]['name'])) {
                            $mail = \htmlescape($data[$ID][$k]['name']);
                            $out .= (empty($out) ? '' : Search::LBBR);
                            $out .= "<a href='mailto:" . $mail . "'>" . $mail;
                            $out .= "</a>";
                        }
                    }
                    return (empty($out) ? '' : $out);

                case "weblink":
                    $orig_link = trim((string) $data[$ID][0]['name']);
                    if (!empty($orig_link) && Toolbox::isValidWebUrl($orig_link)) {
                        // strip begin of link
                        $link = preg_replace('/https?:\/\/(www[^\.]*\.)?/', '', $orig_link);
                        $link = preg_replace('/\/$/', '', $link);
                        if (Toolbox::strlen($link) > $CFG_GLPI["url_maxlength"]) {
                            $link = Toolbox::substr($link, 0, $CFG_GLPI["url_maxlength"]) . "...";
                        }
                        return "<a href=\"" . \htmlescape(Toolbox::formatOutputWebLink($orig_link)) . "\" target='_blank'>" . \htmlescape($link) . "</a>";
                    }
                    return '';

                case "count":
                case "number":
                case "integer":
                case "mio":
                    $out           = "";
                    $count_display = 0;
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if (strlen(trim((string) $data[$ID][$k]['name'])) > 0) {
                            if ($count_display) {
                                $out .= Search::LBBR;
                            }
                            $count_display++;
                            if (
                                isset($so['toadd'])
                                && isset($so['toadd'][$data[$ID][$k]['name']])
                            ) {
                                $out .= $so['toadd'][$data[$ID][$k]['name']];
                            } else {
                                $out .= Dropdown::getValueWithUnit($data[$ID][$k]['name'], $unit);
                            }
                        }
                    }
                    $out = "<span class='text-nowrap'>" . \htmlescape($out) . "</span>";
                    return $out;

                case "decimal":
                    $out           = "";
                    $count_display = 0;
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if (strlen(trim((string) $data[$ID][$k]['name'])) > 0) {
                            if ($count_display) {
                                $out .= Search::LBBR;
                            }
                            $count_display++;
                            if (
                                isset($so['toadd'])
                                && isset($so['toadd'][$data[$ID][$k]['name']])
                            ) {
                                $out .= $so['toadd'][$data[$ID][$k]['name']];
                            } else {
                                $out .= Dropdown::getValueWithUnit($data[$ID][$k]['name'], $unit, $CFG_GLPI["decimal_number"]);
                            }
                        }
                    }
                    $out = "<span class='text-nowrap'>" . \htmlescape($out) . "</span>";
                    return $out;

                case "bool":
                    $out           = "";
                    $count_display = 0;
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        if (strlen(trim((string) $data[$ID][$k]['name'])) > 0) {
                            if ($count_display) {
                                $out .= Search::LBBR;
                            }
                            $count_display++;
                            $out .= Dropdown::getYesNo($data[$ID][$k]['name']);
                        }
                    }
                    return \htmlescape($out);

                case "itemtypename":
                    $out           = "";
                    $count_display = 0;
                    for ($k = 0; $k < $data[$ID]['count']; $k++) {
                        $itemtype_name = $data[$ID][$k]['name'];
                        if (empty($itemtype_name)) {
                            continue;
                        }
                        if ($count_display) {
                            $out .= Search::LBBR;
                        }
                        $count_display++;
                        if ($obj = getItemForItemtype($itemtype_name)) {
                            $out .= $obj->getTypeName();
                        } else {
                            $out .= $itemtype_name;
                        }
                    }
                    return \htmlescape($out);

                case "language":
                    if (isset($data[$ID][0]['name'], $CFG_GLPI['languages'][$data[$ID][0]['name']])) {
                        return $CFG_GLPI['languages'][$data[$ID][0]['name']][0];
                    }
                    return __('Default value');
                case 'progressbar':
                    if (!isset($progressbar_data)) {
                        $bar_color = 'green';
                        $percent   = ltrim(($data[$ID][0]['name'] ?? ""), "0");
                        $progressbar_data = [
                            'percent'      => $percent,
                            'percent_text' => $percent,
                            'color'        => $bar_color,
                            'text'         => '',
                        ];
                    }

                    $out = '<span class="text-nowrap">' . \htmlescape($progressbar_data['text']) . '</span>'
                        . '<div class="progress" style="height: 16px">'
                        . '<div class="progress-bar progress-bar-striped" role="progressbar"'
                        . ' style="width:' . \htmlescape($progressbar_data['percent']) . '%; background-color:' . \htmlescape($progressbar_data['color']) . ';"'
                        . ' aria-valuenow="' . \htmlescape($progressbar_data['percent']) . '" aria-valuemin="0" aria-valuemax="100">'
                        . \htmlescape($progressbar_data['percent_text']) . '%'
                        . '</div>'
                        . '</div>';

                    return $out;
                case 'color':
                    $color = \htmlescape($data[$ID][0]['name']);
                    return "<div class='badge_block' style='border-color: $color'>
                        <span style='background: $color'></span>&nbsp;" . $color . "
                       </div>";
            }
        }
        // Manage items with need group by / group_concat
        $out           = "";
        $count_display = 0;
        $separate      = Search::LBBR;
        if (isset($so['splititems']) && $so['splititems']) {
            $separate = Search::LBHR;
        }

        $aggregate = (isset($so['aggregate']) && $so['aggregate']);

        $append_specific = static function ($specific, $field_data, &$out) use ($so) {
            if (!empty($specific)) {
                // result of `getSpecificValueToDisplay()` is expected to be safe HTML
                $out .= $specific;
            } elseif (isset($field_data['values'])) {
                // Aggregate values; No special handling
                return;
            } else {
                if (
                    isset($so['toadd'])
                    && isset($so['toadd'][$field_data['name']])
                ) {
                    $out .= \htmlescape($so['toadd'][$field_data['name']]);
                } else {
                    // Trans field exists
                    if (isset($field_data['trans']) && !empty($field_data['trans'])) {
                        $out .= \htmlescape($field_data['trans']);
                    } elseif (isset($field_data['trans_completename']) && !empty($field_data['trans_completename'])) {
                        $value = (new SanitizedStringsDecoder())->decodeHtmlSpecialCharsInCompletename($field_data['trans_completename']);
                        $out .= \htmlescape($value);
                    } elseif (isset($field_data['trans_name']) && !empty($field_data['trans_name'])) {
                        $out .= \htmlescape($field_data['trans_name']);
                    } else {
                        $out .= \htmlescape($field_data['name'] ?: '');
                    }
                }
            }
        };
        if (isset($table) && isset($field)) {
            $opt_itemtype = $so['itemtype'] ?? getItemTypeForTable($table);
            if ($item = getItemForItemtype($opt_itemtype)) {
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
                            'raw_data'  => $data,
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
                                'raw_data' => $data,
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
     * @return array<string|null>
     **/
    public static function explodeWithID($pattern, $subject): array
    {

        $tab = explode($pattern, $subject);

        if (isset($tab[1]) && !is_numeric($tab[1])) {
            // Report $ to tab[0]
            if (preg_match('/^(\\$*)(.*)/', $tab[1], $matchs)) {
                if (is_numeric($matchs[2])) {
                    $tab[1]  = $matchs[2];
                    $tab[0] .= $matchs[1];
                }
            }
        }
        // Manage NULL value
        if ($tab[0] == Search::NULLVALUE) {
            $tab[0] = null;
        }
        return $tab;
    }

    /**
     * Returns the suffix to add to table identifiers joined for meta items.
     *
     * @param string $initial_table
     * @param string $meta_itemtype
     * @return string
     */
    private static function getMetaTableUniqueSuffix(string $initial_table, string $meta_itemtype): string
    {
        $suffix = '';

        if ($meta_itemtype::getTable() !== $initial_table) {
            $suffix .= "_" . $meta_itemtype;
        }

        $system_criteria = $meta_itemtype::getSystemSQLCriteria();
        if (count($system_criteria)) {
            $suffix .= '_' . md5(serialize($system_criteria));
        }

        return $suffix;
    }
}
