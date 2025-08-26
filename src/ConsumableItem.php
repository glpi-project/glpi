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
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QueryFunction;
use Glpi\Features\AssetImage;
use Glpi\Features\AssignableItem;
use Glpi\Features\AssignableItemInterface;
use Glpi\Features\Clonable;

//!  ConsumableItem Class
/**
 * This class is used to manage the various types of consumables.
 * @see Consumable
 * @author Julien Dombre
 */
class ConsumableItem extends CommonDBTM implements AssignableItemInterface
{
    use Clonable;

    use AssetImage;
    use AssignableItem {
        prepareInputForAdd as prepareInputForAddAssignableItem;
        prepareInputForUpdate as prepareInputForUpdateAssignableItem;
    }

    // From CommonDBTM
    protected static $forward_entity_to = ['Consumable', 'Infocom'];
    public $dohistory                   = true;
    protected $usenotepad               = true;

    public static $rightname                   = 'consumable';

    public function getCloneRelations(): array
    {
        return [
            Infocom::class,
            ManualLink::class,
        ];
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Consumable model', 'Consumable models', $nb);
    }

    public static function getMenuName()
    {
        return Consumable::getTypeName(Session::getPluralNumber());
    }

    public static function getAdditionalMenuLinks()
    {
        if (static::canView()) {
            return ['summary' => '/front/consumableitem.php?synthese=yes'];
        }
        return false;
    }

    public function getPostAdditionalInfosForName()
    {
        if (isset($this->fields["ref"]) && !empty($this->fields["ref"])) {
            return $this->fields["ref"];
        }
        return '';
    }

    public function prepareInputForAdd($input)
    {
        $input = $this->prepareInputForAddAssignableItem($input);
        if ($input === false) {
            return false;
        }
        return $this->managePictures($input);
    }

    public function prepareInputForUpdate($input)
    {
        $input = $this->prepareInputForUpdateAssignableItem($input);
        if ($input === false) {
            return false;
        }
        return $this->managePictures($input);
    }

    public function cleanDBonPurge()
    {
        $this->deleteChildrenAndRelationsFromDb(
            [
                Consumable::class,
            ]
        );

        // Alert does not extends CommonDBConnexity
        $alert = new Alert();
        $alert->cleanDBonItemDelete(static::class, $this->fields['id']);
    }

    public function post_getEmpty()
    {
        if (isset($_SESSION['glpiactive_entity'])) {
            $this->fields["alarm_threshold"] = Entity::getUsedConfig(
                "consumables_alert_repeat",
                $_SESSION['glpiactive_entity'],
                "default_consumables_alarm_threshold",
                10
            );
        }
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab(Consumable::class, $ong, $options);
        $this->addStandardTab(Infocom::class, $ong, $options);
        $this->addStandardTab(Document_Item::class, $ong, $options);
        $this->addStandardTab(ManualLink::class, $ong, $options);
        $this->addStandardTab(Notepad::class, $ong, $options);
        $this->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public function rawSearchOptions()
    {
        global $DB;

        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '2',
            'table'              => static::getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'datatype'           => 'number',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '34',
            'table'              => static::getTable(),
            'field'              => 'ref',
            'name'               => __('Reference'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => static::getTable(),
            'field'              => 'otherserial',
            'name'               => __('Inventory number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_consumableitemtypes',
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => 'glpi_manufacturers',
            'field'              => 'name',
            'name'               => Manufacturer::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => static::getTable(),
            'field'              => '_virtual',
            'linkfield'          => '_virtual',
            'name'               => _n('Consumable', 'Consumables', Session::getPluralNumber()),
            'datatype'           => 'specific',
            'massiveaction'      => false,
            'nosearch'           => true,
            'nosort'             => true,
            'additionalfields'   => ['alarm_threshold'],
        ];

        $tab[] = [
            'id'                 => '17',
            'table'              => 'glpi_consumables',
            'field'              => 'date_out',
            'name'               => __('Number of used consumables'),
            'datatype'           => 'number',
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'nometa'             => true,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
            'computation' => new QueryExpression(
                expression: QueryFunction::sum(new QueryExpression("CASE WHEN " . $DB::quoteName('TABLE.date_out') . " IS NULL THEN 1 ELSE 0 END"))
            ),
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => 'glpi_consumables',
            'field'              => 'date_out',
            'name'               => __('Number of new consumables'),
            'datatype'           => 'number',
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'nometa'             => true,
            'joinparams'         => [
                'jointype'           => 'child',
            ],
            'computation' => new QueryExpression(
                expression: QueryFunction::sum(new QueryExpression("CASE WHEN " . $DB::quoteName('TABLE.date_out') . " IS NOT NULL THEN 1 ELSE 0 END"))
            ),
        ];

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        $tab[] = [
            'id'                 => '24',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id_tech',
            'name'               => __('Technician in charge'),
            'datatype'           => 'dropdown',
            'right'              => 'own_ticket',
        ];

        $tab[] = [
            'id'                 => '49',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'linkfield'          => 'groups_id',
            'name'               => __('Group in charge'),
            'condition'          => ['is_assign' => 1],
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_groups_items',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'condition'          => ['NEWTABLE.type' => Group_Item::GROUP_TYPE_TECH],
                    ],
                ],
            ],
            'forcegroupby'       => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => static::getTable(),
            'field'              => 'alarm_threshold',
            'name'               => __('Alert threshold'),
            'datatype'           => 'number',
            'toadd'              => [
                '-1'                 => 'Never',
            ],
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => static::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        return $tab;
    }

    public static function cronInfo($name)
    {
        return ['description' => __('Send alarms on consumables')];
    }

    /**
     * Cron action on consumables : alert if a stock is behind the threshold
     *
     * @param CronTask|null $task to log, if NULL display (default NULL)
     *
     * @return integer 0 : nothing to do 1 : done with success
     * @used-by CronTask
     **/
    public static function cronConsumable(?CronTask $task = null)
    {
        global $CFG_GLPI, $DB;

        $cron_status = 1;

        if ($CFG_GLPI["use_notifications"]) {
            $alert   = new Alert();

            foreach (Entity::getEntitiesToNotify('consumables_alert_repeat') as $entity => $repeat) {
                $alerts_result = $DB->request(
                    [
                        'SELECT'    => [
                            'glpi_consumableitems.id AS consID',
                            'glpi_consumableitems.entities_id AS entity',
                            'glpi_consumableitems.ref AS ref',
                            'glpi_consumableitems.name AS name',
                            'glpi_consumableitems.alarm_threshold AS threshold',
                            'glpi_alerts.id AS alertID',
                            'glpi_alerts.date',
                        ],
                        'FROM'      => self::getTable(),
                        'LEFT JOIN' => [
                            'glpi_alerts' => [
                                'FKEY' => [
                                    'glpi_alerts'         => 'items_id',
                                    'glpi_consumableitems' => 'id',
                                    [
                                        'AND' => ['glpi_alerts.itemtype' => 'ConsumableItem'],
                                    ],
                                ],
                            ],
                        ],
                        'WHERE'     => [
                            'glpi_consumableitems.is_deleted'      => 0,
                            'glpi_consumableitems.alarm_threshold' => ['>=', 0],
                            'glpi_consumableitems.entities_id'     => $entity,
                            'OR'                                  => [
                                ['glpi_alerts.date' => null],
                                [
                                    'glpi_alerts.date' => ['<',
                                        QueryFunction::dateSub(
                                            date: QueryFunction::now(),
                                            interval: $repeat,
                                            interval_unit: 'SECOND'
                                        ),
                                    ],
                                ],
                            ],
                        ],
                    ]
                );

                $messages = [];
                $items    = [];

                foreach ($alerts_result as $consumable) {
                    if (
                        ($unused = Consumable::getUnusedNumber($consumable["consID"]))
                              <= $consumable["threshold"]
                    ) {
                        // define message alert
                        //TRANS: %1$s is the consumable name, %2$s its reference, %3$d the remaining number
                        $messages[] = sprintf(
                            __('Threshold of alarm reached for the type of consumable: %1$s - Reference %2$s - Remaining %3$d'),
                            $consumable['name'],
                            $consumable['ref'],
                            $unused
                        );

                        $items[$consumable["consID"]] = $consumable;

                        // if alert exists -> delete
                        if (!empty($consumable["alertID"])) {
                            $alert->delete(["id" => $consumable["alertID"]]);
                        }
                    }
                }

                if ($items !== []) {
                    $options = [
                        'entities_id' => $entity,
                        'items'       => $items,
                    ];

                    if (NotificationEvent::raiseEvent('alert', new ConsumableItem(), $options)) {
                        if ($task) {
                            $task->log(
                                Dropdown::getDropdownName("glpi_entities", $entity)
                                . " : "
                                . implode("\n", $messages)
                            );
                            $task->addVolume(1);
                        } else {
                            Session::addMessageAfterRedirect(
                                htmlescape(Dropdown::getDropdownName("glpi_entities", $entity))
                                . " : "
                                . implode('<br>', array_map('htmlescape', $messages))
                            );
                        }

                        $input = [
                            'type'     => Alert::THRESHOLD,
                            'itemtype' => 'ConsumableItem',
                        ];

                        // add alerts
                        foreach (array_keys($items) as $ID) {
                            $input["items_id"] = $ID;
                            $alert->add($input);
                            unset($alert->fields['id']);
                        }
                    } else {
                        $entityname = Dropdown::getDropdownName('glpi_entities', $entity);
                        //TRANS: %s is entity name
                        $msg = sprintf(__('%s: send consumable alert failed'), $entityname);
                        if ($task) {
                            $task->log($msg);
                        } else {
                            Session::addMessageAfterRedirect(htmlescape($msg), false, ERROR);
                        }
                    }
                }
            }
        }
        return $cron_status;
    }

    public function getEvents()
    {
        return ['alert' => __('Send alarms on consumables')];
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('pages/assets/consumableitem.html.twig', [
            'item'   => $this,
            'params' => $options,
        ]);
        return true;
    }

    public static function getIcon()
    {
        return Consumable::getIcon();
    }
}
