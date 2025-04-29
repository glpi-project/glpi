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

use Glpi\Socket;

/**
 * Network equipment Class
 **/
class NetworkEquipment extends CommonDBTM
{
    use Glpi\Features\DCBreadcrumb;
    use Glpi\Features\Clonable;
    use Glpi\Features\Inventoriable;

    // From CommonDBTM
    public $dohistory                   = true;
    protected static $forward_entity_to = ['Infocom', 'NetworkPort', 'ReservationItem',
        'Item_OperatingSystem', 'Item_Disk', 'Item_SoftwareVersion',
    ];

    public static $rightname                   = 'networking';
    protected $usenotepad               = true;

    /** RELATIONS */
    public function getCloneRelations(): array
    {
        return [
            Item_OperatingSystem::class,
            Item_Devices::class,
            Infocom::class,
            NetworkPort::class,
            Contract_Item::class,
            Document_Item::class,
            KnowbaseItem_Item::class,
        ];
    }
    /** /RELATIONS */


    /**
     * Name of the type
     *
     * @param $nb  integer  number of item in the type (default 0)
     **/
    public static function getTypeName($nb = 0)
    {
        return _n('Network device', 'Network devices', $nb);
    }


    public static function getAdditionalMenuOptions()
    {

        if (static::canView()) {
            $options = [
                'networkport' => [
                    'title' => NetworkPort::getTypeName(Session::getPluralNumber()),
                    'page'  => NetworkPort::getFormURL(false),
                ],
            ];
            return $options;
        }
        return false;
    }


    /**
     * @see CommonDBTM::useDeletedToLockIfDynamic()
     *
     * @since 0.84
     **/
    public function useDeletedToLockIfDynamic()
    {
        return false;
    }


    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong)
         ->addImpactTab($ong, $options)
         ->addStandardTab('Item_OperatingSystem', $ong, $options)
         ->addStandardTab('Item_SoftwareVersion', $ong, $options)
         ->addStandardTab('Item_Devices', $ong, $options)
         ->addStandardTab('Item_Disk', $ong, $options)
         ->addStandardTab('NetworkPort', $ong, $options)
         ->addStandardTab('NetworkName', $ong, $options)
         ->addStandardTab(Socket::class, $ong, $options)
         ->addStandardTab('Infocom', $ong, $options)
         ->addStandardTab('Contract_Item', $ong, $options)
         ->addStandardTab('Document_Item', $ong, $options)
         ->addStandardTab('KnowbaseItem_Item', $ong, $options)
         ->addStandardTab('Ticket', $ong, $options)
         ->addStandardTab('Item_Problem', $ong, $options)
         ->addStandardTab('Change_Item', $ong, $options)
         ->addStandardTab('ManualLink', $ong, $options)
         ->addStandardTab('Lock', $ong, $options)
         ->addStandardTab('Notepad', $ong, $options)
         ->addStandardTab('Reservation', $ong, $options)
         ->addStandardTab('Certificate_Item', $ong, $options)
         ->addStandardTab('Domain_Item', $ong, $options)
         ->addStandardTab('Appliance_Item', $ong, $options)
         ->addStandardTab('RuleMatchedLog', $ong, $options)
         ->addStandardTab('Log', $ong, $options);

        return $ong;
    }


    public function prepareInputForAdd($input)
    {

        if (isset($input["id"]) && ($input["id"] > 0)) {
            $input["_oldID"] = $input["id"];
        }
        unset($input['id']);
        unset($input['withtemplate']);

        return $input;
    }


    /**
     * Can I change recursive flag to false
     * check if there is "linked" object in another entity
     *
     * Overloaded from CommonDBTM
     *
     * @return boolean
     **/
    public function canUnrecurs()
    {
        /** @var \DBmysql $DB */
        global $DB;

        $ID = $this->fields['id'];
        if (
            ($ID < 0)
            || !$this->fields['is_recursive']
        ) {
            return true;
        }
        if (!parent::canUnrecurs()) {
            return false;
        }
        $entities = getAncestorsOf("glpi_entities", $this->fields['entities_id']);
        $entities[] = $this->fields['entities_id'];

        // RELATION : networking -> _port -> _wire -> _port -> device

        // Evaluate connection in the 2 ways
        foreach (
            ["networkports_id_1" => "networkports_id_2",
                "networkports_id_2" => "networkports_id_1",
            ] as $enda => $endb
        ) {
            $criteria = [
                'SELECT'       => [
                    'itemtype',
                    new QueryExpression('GROUP_CONCAT(DISTINCT ' . $DB->quoteName('items_id') . ') AS ' . $DB->quoteName('ids')),
                ],
                'FROM'         => 'glpi_networkports_networkports',
                'INNER JOIN'   => [
                    'glpi_networkports'  => [
                        'ON'  => [
                            'glpi_networkports_networkports' => $endb,
                            'glpi_networkports'              => 'id',
                        ],
                    ],
                ],
                'WHERE'        => [
                    'glpi_networkports_networkports.' . $enda   => new QuerySubQuery([
                        'SELECT' => 'id',
                        'FROM'   => 'glpi_networkports',
                        'WHERE'  => [
                            'itemtype'  => $this->getType(),
                            'items_id'  => $ID,
                        ],
                    ]),
                ],
                'GROUPBY'      => 'itemtype',
            ];

            $res = $DB->request($criteria);
            if ($res) {
                foreach ($res as $data) {
                    $itemtable = getTableForItemType($data["itemtype"]);
                    if ($item = getItemForItemtype($data["itemtype"])) {
                        // For each itemtype which are entity dependant
                        if ($item->isEntityAssign()) {
                            if (count($ids = explode(',', $data["ids"])) > 1) {
                                $data["ids"] = $ids;
                            }
                            if (
                                countElementsInTable($itemtable, ['id' => $data["ids"],
                                    'NOT' => ['entities_id' => $entities ],
                                ]) > 0
                            ) {
                                return false;
                            }
                        }
                    }
                }
            }
        }
        return true;
    }


    public function getSpecificMassiveActions($checkitem = null)
    {

        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($isadmin) {
            $actions += [
                'Item_SoftwareLicense' . MassiveAction::CLASS_ACTION_SEPARATOR . 'add'
               => "<i class='ma-icon fas fa-key'></i>" .
                  _x('button', 'Add a license'),
            ];
            KnowbaseItem_Item::getMassiveActionsForItemtype($actions, __CLASS__, 0, $checkitem);
        }

        return $actions;
    }


    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '2',
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

        $tab[] = [
            'id'                 => '4',
            'table'              => 'glpi_networkequipmenttypes',
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '40',
            'table'              => 'glpi_networkequipmentmodels',
            'field'              => 'name',
            'name'               => _n('Model', 'Models', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '31',
            'table'              => 'glpi_states',
            'field'              => 'completename',
            'name'               => __('Status'),
            'datatype'           => 'dropdown',
            'condition'          => ['is_visible_networkequipment' => 1],
        ];

        $tab[] = [
            'id'                 => '5',
            'table'              => $this->getTable(),
            'field'              => 'serial',
            'name'               => __('Serial number'),
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
            'id'                 => '7',
            'table'              => $this->getTable(),
            'field'              => 'contact',
            'name'               => __('Alternate username'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'contact_num',
            'name'               => __('Alternate username number'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '70',
            'table'              => 'glpi_users',
            'field'              => 'name',
            'name'               => User::getTypeName(1),
            'datatype'           => 'dropdown',
            'right'              => 'all',
        ];

        $tab[] = [
            'id'                 => '71',
            'table'              => 'glpi_groups',
            'field'              => 'completename',
            'name'               => Group::getTypeName(1),
            'datatype'           => 'dropdown',
            'condition'          => ['is_itemgroup' => 1],
        ];

        $tab[] = [
            'id'                 => '72',
            'table'              => 'glpi_autoupdatesystems',
            'field'              => 'name',
            'name'               => AutoUpdateSystem::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '73',
            'table'              => 'glpi_snmpcredentials',
            'field'              => 'name',
            'name'               => SNMPCredential::getTypeName(1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text',
        ];

        $tab[] = [
            'id'                 => '11',
            'table'              => 'glpi_devicefirmwares',
            'field'              => 'version',
            'name'               => _n('Firmware', 'Firmware', 1),
            'forcegroupby'       => true,
            'usehaving'          => true,
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
            'joinparams'         => [
                'beforejoin'         => [
                    'table'              => 'glpi_items_devicefirmwares',
                    'joinparams'         => [
                        'jointype'           => 'itemtype_item',
                        'specific_itemtype'  => 'NetworkEquipment',
                    ],
                ],
            ],
        ];

        $tab[] = [
            'id'                 => '12',
            'table'              => $this->getTable(),
            'field'              => 'uuid',
            'name'               => __('UUID'),
            'datatype'           => 'string',
        ];

        $tab[] = [
            'id'                 => '14',
            'table'              => $this->getTable(),
            'field'              => 'ram',
            'name'               => sprintf(__('%1$s (%2$s)'), _n('Memory', 'Memories', 1), __('Mio')),
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => '32',
            'table'              => 'glpi_networks',
            'field'              => 'name',
            'name'               => _n('Network', 'Networks', 1),
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
            'linkfield'          => 'groups_id_tech',
            'name'               => __('Group in charge'),
            'condition'          => ['is_assign' => 1],
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '65',
            'table'              => $this->getTable(),
            'field'              => 'template_name',
            'name'               => __('Template name'),
            'datatype'           => 'text',
            'massiveaction'      => false,
            'nosearch'           => true,
            'nodisplay'          => true,
        ];

        $tab[] = [
            'id'                 => '80',
            'table'              => 'glpi_entities',
            'field'              => 'completename',
            'name'               => Entity::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => '83',
            'table'              => self::getTable(),
            'field'              => 'last_inventory_update',
            'name'               => __('Last inventory date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        // add operating system search options
        $tab = array_merge($tab, Item_OperatingSystem::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

        $tab = array_merge($tab, Item_Devices::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, Datacenter::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, Rack::rawSearchOptionsToAdd(get_class($this)));

        $tab = array_merge($tab, Socket::rawSearchOptionsToAdd());

        $tab = array_merge($tab, SNMPCredential::rawSearchOptionsToAdd());

        $tab = array_merge($tab, DCRoom::rawSearchOptionsToAdd());

        return $tab;
    }

    public static function getIcon()
    {
        return "fas fa-network-wired";
    }
}
