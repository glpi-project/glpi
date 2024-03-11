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
use Glpi\Features\AssetImage;

//!  ConsumableItem Class
/**
 * This class is used to manage the various types of consumables.
 * @see Consumable
 * @author Julien Dombre
 */
class ConsumableItem extends CommonDBTM
{
    use Glpi\Features\Clonable;

    use AssetImage;

   // From CommonDBTM
    protected static $forward_entity_to = ['Consumable', 'Infocom'];
    public $dohistory                   = true;
    protected $usenotepad               = true;

    public static $rightname                   = 'consumable';

    public function getCloneRelations(): array
    {
        return [];
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
        $input = parent::prepareInputForAdd($input);
        return $this->managePictures($input);
    }

    public function prepareInputForUpdate($input)
    {
        $input = parent::prepareInputForUpdate($input);
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
        $alert->cleanDBonItemDelete($this->getType(), $this->fields['id']);
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
        $this->addStandardTab('Consumable', $ong, $options);
        $this->addStandardTab('Infocom', $ong, $options);
        $this->addStandardTab('Document_Item', $ong, $options);
        $this->addStandardTab('ManualLink', $ong, $options);
        $this->addStandardTab('Notepad', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);

        return $ong;
    }


    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'datatype'           => 'number',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '34',
            'table'              => $this->getTable(),
            'field'              => 'ref',
            'name'               => __('Reference'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '6',
            'table'              => $this->getTable(),
            'field'              => 'otherserial',
            'name'               => __('Inventory number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_consumableitemtypes',
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => 'glpi_manufacturers',
            'field'              => 'name',
            'name'               => Manufacturer::getTypeName(1),
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '9',
            'table'              => $this->getTable(),
            'field'              => '_virtual',
            'linkfield'          => '_virtual',
            'name'               => _n('Consumable', 'Consumables', Session::getPluralNumber()),
            'datatype'           => 'specific',
            'massiveaction'      => false,
            'nosearch'           => true,
            'nosort'             => true,
            'additionalfields'   => ['alarm_threshold']
        ];

        $tab[] = [
            'id'                 => '17',
            'table'              => 'glpi_consumables',
            'field'              => 'id',
            'name'               => __('Number of used consumables'),
            'datatype'           => 'count',
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NOT' => ['NEWTABLE.date_out' => null]]
            ]
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => 'glpi_consumables',
            'field'              => 'id',
            'name'               => __('Number of new consumables'),
            'datatype'           => 'count',
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'joinparams'         => [
                'jointype'           => 'child',
                'condition'          => ['NEWTABLE.date_out' => null]
            ]
        ];

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        $tab[] = [
            'id'                 => '24',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'linkfield'          => 'users_id_tech',
            'name'               => __('Technician in charge'),
            'datatype'           => 'dropdown',
            'right'              => 'own_ticket'
        ];

        $tab[] = [
            'id'                 => '49',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'linkfield'          => 'groups_id_tech',
            'name'               => __('Group in charge'),
            'condition'          => ['is_assign' => 1],
            'datatype'           => 'dropdown'
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'alarm_threshold',
            'name'               => __('Alert threshold'),
            'datatype'           => 'number',
            'toadd'              => [
                '-1'                 => 'Never'
            ]
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text'
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown'
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
     **/
    public static function cronConsumable(CronTask $task = null)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $cron_status = 1;

        if ($CFG_GLPI["use_notifications"]) {
            $message = [];
            $items   = [];
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
                        'FROM'      => ConsumableItem::getTable(),
                        'LEFT JOIN' => [
                            'glpi_alerts' => [
                                'FKEY' => [
                                    'glpi_alerts'         => 'items_id',
                                    'glpi_consumableitems' => 'id',
                                    [
                                        'AND' => ['glpi_alerts.itemtype' => 'ConsumableItem'],
                                    ],
                                ]
                            ]
                        ],
                        'WHERE'     => [
                            'glpi_consumableitems.is_deleted'      => 0,
                            'glpi_consumableitems.alarm_threshold' => ['>=', 0],
                            'glpi_consumableitems.entities_id'     => $entity,
                            'OR'                                  => [
                                ['glpi_alerts.date' => null],
                                ['glpi_alerts.date' => ['<', new QueryExpression('CURRENT_TIMESTAMP() - INTERVAL ' . $repeat . ' second')]],
                            ],
                        ],
                    ]
                );

                $message = "";
                $items   = [];

                foreach ($alerts_result as $consumable) {
                    if (
                        ($unused = Consumable::getUnusedNumber($consumable["consID"]))
                              <= $consumable["threshold"]
                    ) {
                       // define message alert
                       //TRANS: %1$s is the consumable name, %2$s its reference, %3$d the remaining number
                        $message .= sprintf(
                            __('Threshold of alarm reached for the type of consumable: %1$s - Reference %2$s - Remaining %3$d'),
                            $consumable['name'],
                            $consumable['ref'],
                            $unused
                        );
                        $message .= '<br>';

                        $items[$consumable["consID"]] = $consumable;

                       // if alert exists -> delete
                        if (!empty($consumable["alertID"])) {
                            $alert->delete(["id" => $consumable["alertID"]]);
                        }
                    }
                }

                if (!empty($items)) {
                    $options = [
                        'entities_id' => $entity,
                        'items'       => $items,
                    ];

                    if (NotificationEvent::raiseEvent('alert', new ConsumableItem(), $options)) {
                        if ($task) {
                             $task->log(Dropdown::getDropdownName(
                                 "glpi_entities",
                                 $entity
                             ) . " :  $message\n");
                               $task->addVolume(1);
                        } else {
                             Session::addMessageAfterRedirect(Dropdown::getDropdownName(
                                 "glpi_entities",
                                 $entity
                             ) .
                                                      " :  $message");
                        }

                        $input = [
                            'type'     => Alert::THRESHOLD,
                            'itemtype' => 'ConsumableItem',
                        ];

                      // add alerts
                        foreach ($items as $ID => $consumable) {
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
                            Session::addMessageAfterRedirect($msg, false, ERROR);
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


    /**
     * Display debug information for current object
     **/
    public function showDebug()
    {

       // see query_alert in cronConsumable()
        $item = ['consID'    => $this->fields['id'],
            'entity'    => $this->fields['entities_id'],
            'ref'       => $this->fields['ref'],
            'name'      => $this->fields['name'],
            'threshold' => $this->fields['alarm_threshold']
        ];

        $options = [];
        $options['entities_id'] = $this->getEntityID();
        $options['items']       = [$item];
        NotificationEvent::debugEvent($this, $options);
    }


    public function canUpdateItem()
    {

        if (!$this->checkEntity(true)) { //check entities recursively
            return false;
        }
        return true;
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
