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

use Glpi\Application\ErrorHandler;
use Glpi\Plugin\Hooks;
use Glpi\Socket;

/**
 * Transfer engine.
 * This class is used to move data between entities.
 */
class Transfer extends CommonDBTM
{
   // Specific ones

    /**
     * Array of items that have already been transferred
     * @var array
     */
    public $already_transfer      = [];

    /**
     * Items simulate to move - non-recursive item or recursive item not visible in destination entity
     * @var array
     */
    public $needtobe_transfer     = [];

    /**
     * Items simulate to move - recursive item visible in destination entity
     * @var array
     */
    public $noneedtobe_transfer   = [];

    /**
     * Options used to transfer
     * @var array
     */
    public $options               = [];

    /**
     * Destination entity id
     * @var int
     */
    public $to                    = -1;

    /**
     * Type of initial item transferred
     * @var string
     * @fixme This should only be a string(class name). Itemtypes haven't been refereed to by integers in a long time.
     */
    public $inittype              = 0;

    public static $rightname = 'transfer';

    public function maxActionsCount()
    {
        return 0;
    }


    /**
     * @see CommonGLPI::defineTabs()
     *
     * @since 0.85
     **/
    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addDefaultFormTab($ong);

        return $ong;
    }


    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics')
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => $this->getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => $this->getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => $this->getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => $this->getTable(),
            'field'              => 'comment',
            'name'               => __('Comments'),
            'datatype'           => 'text'
        ];

        return $tab;
    }


    /**
     * Transfer items
     *
     * @param array $items    Array of items to transfer in the format [itemtype => [ids]]
     * @param int $to         entity destination ID
     * @param array $options  options used to transfer
     *
     * @return void
     **/
    public function moveItems($items, $to, $options)
    {
        global $DB;

       // unset notifications
        NotificationSetting::disableAll();

        $this->options = ['keep_ticket'         => 0,
            'keep_networklink'    => 0,
            'keep_reservation'    => 0,
            'keep_history'        => 0,
            'keep_device'         => 0,
            'keep_infocom'        => 0,

            'keep_dc_monitor'     => 0,
            'clean_dc_monitor'    => 0,

            'keep_dc_phone'       => 0,
            'clean_dc_phone'      => 0,

            'keep_dc_peripheral'  => 0,
            'clean_dc_peripheral' => 0,

            'keep_dc_printer'     => 0,
            'clean_dc_printer'    => 0,

            'keep_supplier'       => 0,
            'clean_supplier'      => 0,

            'keep_contact'        => 0,
            'clean_contact'       => 0,

            'keep_contract'       => 0,
            'clean_contract'      => 0,

            'keep_disk'           => 0,

            'keep_software'       => 0,
            'clean_software'      => 0,

            'keep_document'       => 0,
            'clean_document'      => 0,

            'keep_cartridgeitem'  => 0,
            'clean_cartridgeitem' => 0,
            'keep_cartridge'      => 0,

            'keep_consumable'     => 0,

            'keep_certificate'    => 0,
            'clean_certificate'   => 0
        ];

        if ($to >= 0) {
           // Store to
            $this->to = $to;
           // Store options
            if (is_array($options) && count($options)) {
                foreach ($options as $key => $val) {
                    $this->options[$key] = $val;
                }
            }

            $intransaction = $DB->inTransaction();
            try {
                if (!$intransaction) {
                    $DB->beginTransaction();
                }

               // Simulate transfers To know which items need to be transfer
                $this->simulateTransfer($items);

               // Inventory Items : MONITOR....
                $INVENTORY_TYPES = [
                    'Software', // Software first (to avoid copy during computer transfer)
                    'Computer', // Computer before all other items
                    'CartridgeItem',
                    'ConsumableItem',
                    'Monitor',
                    'NetworkEquipment',
                    'Peripheral',
                    'Phone',
                    'Printer',
                    'SoftwareLicense',
                    'Certificate',
                    'Contact',
                    'Contract',
                    'Document',
                    'Supplier',
                    'Group',
                    'Link',
                    'Ticket',
                    'Problem',
                    'Change'
                ];

                foreach ($INVENTORY_TYPES as $itemtype) {
                    $this->inittype = $itemtype;
                    if (isset($items[$itemtype]) && count($items[$itemtype])) {
                        foreach ($items[$itemtype] as $ID) {
                             $this->transferItem($itemtype, $ID, $ID);
                        }
                    }
                }

               //handle all other types
                foreach (array_keys($items) as $itemtype) {
                    if (!in_array($itemtype, $INVENTORY_TYPES)) {
                        $this->inittype = $itemtype;
                        if (isset($items[$itemtype]) && count($items[$itemtype])) {
                            foreach ($items[$itemtype] as $ID) {
                                $this->transferItem($itemtype, $ID, $ID);
                            }
                        }
                    }
                }

               // Clean unused
               // FIXME: only if Software or SoftwareLicense has been changed?
                $this->cleanSoftwareVersions();
                if (!$intransaction && $DB->inTransaction()) {
                    $DB->commit();
                }
            } catch (\Exception $e) {
                if (!$intransaction && $DB->inTransaction()) {
                    $DB->rollBack();
                }
                ErrorHandler::getInstance()->handleException($e);
            }
        }
    }


    /**
     * Add an item in the needtobe_transfer list
     *
     * @param string $itemtype Itemtype of the item
     * @param int $ID          ID of the item
     *
     * @return void
     **/
    public function addToBeTransfer($itemtype, $ID)
    {

        if (!isset($this->needtobe_transfer[$itemtype])) {
            $this->needtobe_transfer[$itemtype] = [];
        }

       // Can't be in both list (in fact, always false)
        if (isset($this->noneedtobe_transfer[$itemtype][$ID])) {
            unset($this->noneedtobe_transfer[$itemtype][$ID]);
        }

        $this->needtobe_transfer[$itemtype][$ID] = $ID;
    }


    /**
     * Add an item in the noneedtobe_transfer list
     *
     * @param string $itemtype Itemtype of the item
     * @param int $ID          ID of the item
     *
     * @return void
     **/
    public function addNotToBeTransfer($itemtype, $ID)
    {

        if (!isset($this->noneedtobe_transfer[$itemtype])) {
            $this->noneedtobe_transfer[$itemtype] = [];
        }

       // Can't be in both list (in fact, always true)
        if (!isset($this->needtobe_transfer[$itemtype][$ID])) {
            $this->noneedtobe_transfer[$itemtype][$ID] = $ID;
        }
    }


    /**
     * simulate the transfer to know which items need to be transfer
     *
     * @param array $items Array of items to transfer in the format [itemtype => [ids]]
     *
     * @return void
     **/
    public function simulateTransfer($items)
    {
        global $DB, $CFG_GLPI;

       // Init types :
        $types = ['Computer', 'CartridgeItem', 'Change', 'ConsumableItem', 'Certificate', 'Contact',
            'Contract', 'Document', 'Link', 'Monitor', 'NetworkEquipment', 'Peripheral',
            'Phone', 'Printer', 'Problem', 'Software', 'SoftwareLicense',
            'SoftwareVersion', 'Supplier', 'Ticket'
        ];
        $types = array_merge($types, $CFG_GLPI['device_types']);
        $types = array_merge($types, Item_Devices::getDeviceTypes());
        foreach ($types as $t) {
            if (!isset($this->needtobe_transfer[$t])) {
                $this->needtobe_transfer[$t] = [];
            }
            if (!isset($this->noneedtobe_transfer[$t])) {
                $this->noneedtobe_transfer[$t] = [];
            }
        }

        $to_entity_ancestors = getAncestorsOf("glpi_entities", $this->to);

       // Copy items to needtobe_transfer
        foreach ($items as $key => $tab) {
            if (count($tab)) {
                foreach ($tab as $ID) {
                    $this->addToBeTransfer($key, $ID);
                }
            }
        }

       // DIRECT CONNECTIONS

        $DC_CONNECT = [];
        if ($this->options['keep_dc_monitor']) {
            $DC_CONNECT[] = 'Monitor';
        }
        if ($this->options['keep_dc_phone']) {
            $DC_CONNECT[] = 'Phone';
        }
        if ($this->options['keep_dc_peripheral']) {
            $DC_CONNECT[] = 'Peripheral';
        }
        if ($this->options['keep_dc_printer']) {
            $DC_CONNECT[] = 'Printer';
        }

        if (
            count($DC_CONNECT)
            && (count($this->needtobe_transfer['Computer']) > 0)
        ) {
            foreach ($DC_CONNECT as $itemtype) {
                $itemtable = getTableForItemType($itemtype);

               // Clean DB / Search unexisting links and force disconnect
                $DB->delete(
                    'glpi_computers_items',
                    [
                        "$itemtable.id" => null,
                        'glpi_computers_items.itemtype' => $itemtype,
                    ],
                    [
                        'LEFT JOIN' => [
                            $itemtable  => [
                                'ON' => [
                                    'glpi_computers_items'  => 'items_id',
                                    $itemtable              => 'id',
                                ]
                            ]
                        ]
                    ]
                );

                if (!($item = getItemForItemtype($itemtype))) {
                     continue;
                }

                $iterator = $DB->request([
                    'SELECT'          => 'items_id',
                    'DISTINCT'        => true,
                    'FROM'            => 'glpi_computers_items',
                    'WHERE'           => [
                        'itemtype'     => $itemtype,
                        'computers_id' => $this->needtobe_transfer['Computer']
                    ]
                ]);

                foreach ($iterator as $data) {
                    if (
                        $item->getFromDB($data['items_id'])
                        && $item->isRecursive()
                        && in_array($item->getEntityID(), $to_entity_ancestors)
                    ) {
                        $this->addNotToBeTransfer($itemtype, $data['items_id']);
                    } else {
                        $this->addToBeTransfer($itemtype, $data['items_id']);
                    }
                }
            }
        }

       // License / Software :  keep / delete + clean unused / keep unused
        if ($this->options['keep_software']) {
           // Clean DB
            $DB->delete('glpi_items_softwareversions', ['glpi_softwareversions.id'  => null], [
                'LEFT JOIN' => [
                    'glpi_softwareversions'  => [
                        'ON' => [
                            'glpi_items_softwareversions' => 'softwareversions_id',
                            'glpi_softwareversions'       => 'id'
                        ]
                    ]
                ]
            ]);

           // Clean DB
            $DB->delete('glpi_softwareversions', ['glpi_softwares.id'  => null], [
                'LEFT JOIN' => [
                    'glpi_softwares'  => [
                        'ON' => [
                            'glpi_softwareversions' => 'softwares_id',
                            'glpi_softwares'        => 'id'
                        ]
                    ]
                ]
            ]);
            foreach ($CFG_GLPI['software_types'] as $itemtype) {
                $itemtable = getTableForItemType($itemtype);
                // Clean DB
                $DB->delete('glpi_items_softwareversions', [
                    "{$itemtable}.id"  => null,
                    'glpi_items_softwareversions.itemtype' => $itemtype
                ], [
                    'LEFT JOIN' => [
                        $itemtable  => [
                            'ON' => [
                                'glpi_items_softwareversions' => 'items_id',
                                $itemtable                    => 'id'
                            ]
                        ]
                    ]
                ]);

                if (count($this->needtobe_transfer[$itemtype])) {
                     $iterator = $DB->request([
                         'SELECT'       => [
                             'glpi_softwares.id',
                             'glpi_softwares.entities_id',
                             'glpi_softwares.is_recursive',
                             'glpi_softwareversions.id AS vID'
                         ],
                         'FROM'         => 'glpi_items_softwareversions',
                         'INNER JOIN'   => [
                             'glpi_softwareversions' => [
                                 'ON' => [
                                     'glpi_items_softwareversions' => 'softwareversions_id',
                                     'glpi_softwareversions'       => 'id'
                                 ]
                             ],
                             'glpi_softwares'        => [
                                 'ON' => [
                                     'glpi_softwareversions' => 'softwares_id',
                                     'glpi_softwares'        => 'id'
                                 ]
                             ]
                         ],
                         'WHERE'        => [
                             'glpi_items_softwareversions.items_id' => $this->needtobe_transfer[$itemtype],
                             'glpi_items_softwareversions.itemtype' => $itemtype
                         ]
                     ]);

                    if (count($iterator)) {
                        foreach ($iterator as $data) {
                            if (
                                $data['is_recursive']
                                && in_array($data['entities_id'], $to_entity_ancestors)
                            ) {
                                    $this->addNotToBeTransfer('SoftwareVersion', $data['vID']);
                            } else {
                                  $this->addToBeTransfer('SoftwareVersion', $data['vID']);
                            }
                        }
                    }
                }
            }
        }

        if (count($this->needtobe_transfer['Software'])) {
           // Move license of software
           // TODO : should we transfer "affected license" ?
            $iterator = $DB->request([
                'SELECT' => ['id', 'softwareversions_id_buy', 'softwareversions_id_use'],
                'FROM'   => 'glpi_softwarelicenses',
                'WHERE'  => ['softwares_id' => $this->needtobe_transfer['Software']]
            ]);

            foreach ($iterator as $lic) {
                $this->addToBeTransfer('SoftwareLicense', $lic['id']);

                // Force version transfer
                if ($lic['softwareversions_id_buy'] > 0) {
                    $this->addToBeTransfer('SoftwareVersion', $lic['softwareversions_id_buy']);
                }
                if ($lic['softwareversions_id_use'] > 0) {
                    $this->addToBeTransfer('SoftwareVersion', $lic['softwareversions_id_use']);
                }
            }
        }

       // Devices
        if ($this->options['keep_device']) {
            foreach (Item_Devices::getConcernedItems() as $itemtype) {
                $itemtable = getTableForItemType($itemtype);
                if (isset($this->needtobe_transfer[$itemtype]) && count($this->needtobe_transfer[$itemtype])) {
                    foreach (Item_Devices::getItemAffinities($itemtype) as $itemdevicetype) {
                        $itemdevicetable = getTableForItemType($itemdevicetype);
                        $devicetype      = $itemdevicetype::getDeviceType();
                        $devicetable     = getTableForItemType($devicetype);
                        $fk              = getForeignKeyFieldForTable($devicetable);
                        $iterator = $DB->request([
                            'SELECT'          => [
                                "$itemdevicetable.$fk",
                                "$devicetable.entities_id",
                                "$devicetable.is_recursive"
                            ],
                            'DISTINCT'        => true,
                            'FROM'            => $itemdevicetable,
                            'LEFT JOIN'       => [
                                $devicetable   => [
                                    'ON' => [
                                        $itemdevicetable  => $fk,
                                        $devicetable      => 'id'
                                    ]
                                ]
                            ],
                            'WHERE'           => [
                                "$itemdevicetable.itemtype"   => $itemtype,
                                "$itemdevicetable.items_id"   => $this->needtobe_transfer[$itemtype]
                            ]
                        ]);

                        foreach ($iterator as $data) {
                            if (
                                $data['is_recursive']
                                 && in_array($data['entities_id'], $to_entity_ancestors)
                            ) {
                                $this->addNotToBeTransfer($devicetype, $data[$fk]);
                            } else {
                                if (!isset($this->needtobe_transfer[$devicetype][$data[$fk]])) {
                                    $this->addToBeTransfer($devicetype, $data[$fk]);
                                    $iterator2 = $DB->request([
                                        'SELECT' => 'id',
                                        'FROM'   => $itemdevicetable,
                                        'WHERE'  => [
                                            $fk   => $data[$fk],
                                            'itemtype'  => $itemtype,
                                            'items_id'  => $this->needtobe_transfer[$itemtype]
                                        ]
                                    ]);
                                    foreach ($iterator2 as $data2) {
                                          $this->addToBeTransfer($itemdevicetype, $data2['id']);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

       // Tickets
        if ($this->options['keep_ticket']) {
            foreach ($CFG_GLPI["ticket_types"] as $itemtype) {
                if (isset($this->needtobe_transfer[$itemtype]) && count($this->needtobe_transfer[$itemtype])) {
                    $iterator = $DB->request([
                        'SELECT'    => 'glpi_tickets.id',
                        'FROM'      => 'glpi_tickets',
                        'LEFT JOIN' => [
                            'glpi_items_tickets' => [
                                'ON' => [
                                    'glpi_items_tickets' => 'tickets_id',
                                    'glpi_tickets'       => 'id'
                                ]
                            ]
                        ],
                        'WHERE'     => [
                            'itemtype'  => $itemtype,
                            'items_id'  => $this->needtobe_transfer[$itemtype]
                        ]
                    ]);

                    foreach ($iterator as $data) {
                        $this->addToBeTransfer('Ticket', $data['id']);
                    }
                }
            }
        }

       // Certificate : keep / delete + clean unused / keep unused
        if ($this->options['keep_certificate']) {
            foreach ($CFG_GLPI["certificate_types"] as $itemtype) {
                if (isset($this->needtobe_transfer[$itemtype]) && count($this->needtobe_transfer[$itemtype])) {
                    $itemtable = getTableForItemType($itemtype);

                    // Clean DB
                    $DB->delete(
                        'glpi_certificates_items',
                        [
                            "$itemtable.id"                 => null,
                            "glpi_certificates_items.itemtype" => $itemtype
                        ],
                        [
                            'LEFT JOIN' => [
                                $itemtable  => [
                                    'ON' => [
                                        'glpi_certificates_items'  => 'items_id',
                                        $itemtable              => 'id',
                                    ]
                                ]
                            ]
                        ]
                    );

                     // Clean DB
                     $DB->delete(
                         'glpi_certificates_items',
                         [
                             'glpi_certificates.id'  => null
                         ],
                         [
                             'LEFT JOIN' => [
                                 'glpi_certificates'  => [
                                     'ON' => [
                                         'glpi_certificates_items'  => 'certificates_id',
                                         'glpi_certificates'        => 'id'
                                     ]
                                 ]
                             ]
                         ]
                     );

                     $iterator = $DB->request([
                         'SELECT'    => [
                             'certificates_id',
                             'glpi_certificates.entities_id',
                             'glpi_certificates.is_recursive'
                         ],
                         'FROM'      => 'glpi_certificates_items',
                         'LEFT JOIN' => [
                             'glpi_certificates' => [
                                 'ON' => [
                                     'glpi_certificates_items'  => 'certificates_id',
                                     'glpi_certificates'        => 'id'
                                 ]
                             ]
                         ],
                         'WHERE'     => [
                             'itemtype'  => $itemtype,
                             'items_id'  => $this->needtobe_transfer[$itemtype]
                         ]
                     ]);

                    foreach ($iterator as $data) {
                        if (
                            $data['is_recursive']
                            && in_array($data['entities_id'], $to_entity_ancestors)
                        ) {
                            $this->addNotToBeTransfer('Certificate', $data['certificates_id']);
                        } else {
                            $this->addToBeTransfer('Certificate', $data['certificates_id']);
                        }
                    }
                }
            }
        }

       // Contract : keep / delete + clean unused / keep unused
        if ($this->options['keep_contract']) {
            foreach ($CFG_GLPI["contract_types"] as $itemtype) {
                if (isset($this->needtobe_transfer[$itemtype]) && count($this->needtobe_transfer[$itemtype])) {
                    $contracts_items = [];
                    $itemtable = getTableForItemType($itemtype);

                    // Clean DB
                    $DB->delete(
                        'glpi_contracts_items',
                        [
                            "$itemtable.id"                 => null,
                            "glpi_contracts_items.itemtype" => $itemtype
                        ],
                        [
                            'LEFT JOIN' => [
                                $itemtable  => [
                                    'ON' => [
                                        'glpi_contracts_items'  => 'items_id',
                                        $itemtable              => 'id',
                                    ]
                                ]
                            ]
                        ]
                    );

                     // Clean DB
                     $DB->delete('glpi_contracts_items', ['glpi_contracts.id'  => null], [
                         'LEFT JOIN' => [
                             'glpi_contracts'  => [
                                 'ON' => [
                                     'glpi_contracts_items'  => 'contracts_id',
                                     'glpi_contracts'        => 'id'
                                 ]
                             ]
                         ]
                     ]);

                    $iterator = $DB->request([
                        'SELECT'    => [
                            'contracts_id',
                            'glpi_contracts.entities_id',
                            'glpi_contracts.is_recursive'
                        ],
                        'FROM'      => 'glpi_contracts_items',
                        'LEFT JOIN' => [
                            'glpi_contracts' => [
                                'ON' => [
                                    'glpi_contracts_items'  => 'contracts_id',
                                    'glpi_contracts'        => 'id'
                                ]
                            ]
                        ],
                        'WHERE'     => [
                            'itemtype'  => $itemtype,
                            'items_id'  => $this->needtobe_transfer[$itemtype]
                        ]
                    ]);

                    foreach ($iterator as $data) {
                        if (
                            $data['is_recursive']
                             && in_array($data['entities_id'], $to_entity_ancestors)
                        ) {
                            $this->addNotToBeTransfer('Contract', $data['contracts_id']);
                        } else {
                            $this->addToBeTransfer('Contract', $data['contracts_id']);
                        }
                    }
                }
            }
        }
       // Supplier (depending of item link) / Contract - infocoms : keep / delete + clean unused / keep unused
        if ($this->options['keep_supplier']) {
            $contracts_suppliers = [];
           // Clean DB
            $DB->delete('glpi_contracts_suppliers', ['glpi_contracts.id'  => null], [
                'LEFT JOIN' => [
                    'glpi_contracts'  => [
                        'ON' => [
                            'glpi_contracts_suppliers' => 'contracts_id',
                            'glpi_contracts'           => 'id'
                        ]
                    ]
                ]
            ]);

           // Clean DB
            $DB->delete('glpi_contracts_suppliers', ['glpi_suppliers.id'  => null], [
                'LEFT JOIN' => [
                    'glpi_suppliers'  => [
                        'ON' => [
                            'glpi_contracts_suppliers' => 'suppliers_id',
                            'glpi_suppliers'           => 'id'
                        ]
                    ]
                ]
            ]);

            if (isset($this->needtobe_transfer['Contract']) && count($this->needtobe_transfer['Contract'])) {
                // Supplier Contract
                $iterator = $DB->request([
                    'SELECT'    => [
                        'suppliers_id',
                        'glpi_suppliers.entities_id',
                        'glpi_suppliers.is_recursive'
                    ],
                    'FROM'      => 'glpi_contracts_suppliers',
                    'LEFT JOIN' => [
                        'glpi_suppliers' => [
                            'ON' => [
                                'glpi_contracts_suppliers' => 'suppliers_id',
                                'glpi_suppliers'           => 'id'
                            ]
                        ]
                    ],
                    'WHERE'     => [
                        'contracts_id' => $this->needtobe_transfer['Contract']
                    ]
                ]);

                foreach ($iterator as $data) {
                    if (
                        $data['is_recursive']
                         && in_array($data['entities_id'], $to_entity_ancestors)
                    ) {
                        $this->addNotToBeTransfer('Supplier', $data['suppliers_id']);
                    } else {
                        $this->addToBeTransfer('Supplier', $data['suppliers_id']);
                    }
                }
            }

            if (isset($this->needtobe_transfer['Ticket']) && count($this->needtobe_transfer['Ticket'])) {
               // Ticket Supplier
                $iterator = $DB->request([
                    'SELECT'    => [
                        'glpi_suppliers_tickets.suppliers_id',
                        'glpi_suppliers.entities_id',
                        'glpi_suppliers.is_recursive'
                    ],
                    'FROM'      => 'glpi_tickets',
                    'LEFT JOIN' => [
                        'glpi_suppliers_tickets'   => [
                            'ON' => [
                                'glpi_suppliers_tickets'   => 'tickets_id',
                                'glpi_tickets'             => 'id'
                            ]
                        ],
                        'glpi_suppliers'           => [
                            'ON' => [
                                'glpi_suppliers_tickets'   => 'suppliers_id',
                                'glpi_suppliers'           => 'id'
                            ]
                        ]
                    ],
                    'WHERE'     => [
                        'glpi_suppliers_tickets.suppliers_id'  => ['>', 0],
                        'glpi_tickets.id'                      => $this->needtobe_transfer['Ticket']
                    ]
                ]);

                foreach ($iterator as $data) {
                    if (
                        $data['is_recursive']
                         && in_array($data['entities_id'], $to_entity_ancestors)
                    ) {
                        $this->addNotToBeTransfer('Supplier', $data['suppliers_id']);
                    } else {
                        $this->addToBeTransfer('Supplier', $data['suppliers_id']);
                    }
                }
            }

            if (isset($this->needtobe_transfer['Problem']) && count($this->needtobe_transfer['Problem'])) {
               // Problem Supplier
                $iterator = $DB->request([
                    'SELECT'    => [
                        'glpi_problems_suppliers.suppliers_id',
                        'glpi_suppliers.entities_id',
                        'glpi_suppliers.is_recursive'
                    ],
                    'FROM'      => 'glpi_problems',
                    'LEFT JOIN' => [
                        'glpi_problems_suppliers'   => [
                            'ON' => [
                                'glpi_problems_suppliers'  => 'problems_id',
                                'glpi_problems'            => 'id'
                            ]
                        ],
                        'glpi_suppliers'           => [
                            'ON' => [
                                'glpi_problems_suppliers'  => 'suppliers_id',
                                'glpi_suppliers'           => 'id'
                            ]
                        ]
                    ],
                    'WHERE'     => [
                        'glpi_problems_suppliers.suppliers_id' => ['>', 0],
                        'glpi_problems.id'                     => $this->needtobe_transfer['Problem']
                    ]
                ]);

                foreach ($iterator as $data) {
                    if (
                        $data['is_recursive']
                         && in_array($data['entities_id'], $to_entity_ancestors)
                    ) {
                        $this->addNotToBeTransfer('Supplier', $data['suppliers_id']);
                    } else {
                        $this->addToBeTransfer('Supplier', $data['suppliers_id']);
                    }
                }
            }

            if (isset($this->needtobe_transfer['Change']) && count($this->needtobe_transfer['Change'])) {
               // Change Supplier
                $iterator = $DB->request([
                    'SELECT'    => [
                        'glpi_changes_suppliers.suppliers_id',
                        'glpi_suppliers.entities_id',
                        'glpi_suppliers.is_recursive'
                    ],
                    'FROM'      => 'glpi_changes',
                    'LEFT JOIN' => [
                        'glpi_changes_suppliers'   => [
                            'ON' => [
                                'glpi_changes_suppliers'  => 'changes_id',
                                'glpi_changes'            => 'id'
                            ]
                        ],
                        'glpi_suppliers'           => [
                            'ON' => [
                                'glpi_changes_suppliers'   => 'suppliers_id',
                                'glpi_suppliers'           => 'id'
                            ]
                        ]
                    ],
                    'WHERE'     => [
                        'glpi_changes_suppliers.suppliers_id' => ['>', 0],
                        'glpi_changes.id'                     => $this->needtobe_transfer['Change']
                    ]
                ]);

                foreach ($iterator as $data) {
                    if (
                        $data['is_recursive']
                         && in_array($data['entities_id'], $to_entity_ancestors)
                    ) {
                        $this->addNotToBeTransfer('Supplier', $data['suppliers_id']);
                    } else {
                        $this->addToBeTransfer('Supplier', $data['suppliers_id']);
                    }
                }
            }

           // Supplier infocoms
            if ($this->options['keep_infocom']) {
                foreach (Infocom::getItemtypesThatCanHave() as $itemtype) {
                    if (isset($this->needtobe_transfer[$itemtype]) && count($this->needtobe_transfer[$itemtype])) {
                        $itemtable = getTableForItemType($itemtype);

                        // Clean DB
                        $DB->delete(
                            'glpi_infocoms',
                            [
                                "$itemtable.id"  => null,
                                'glpi_infocoms.itemtype' => $itemtype,
                            ],
                            [
                                'LEFT JOIN' => [
                                    $itemtable => [
                                        'ON' => [
                                            'glpi_infocoms'   => 'items_id',
                                            $itemtable        => 'id',
                                        ]
                                    ]
                                ]
                            ]
                        );

                         $iterator = $DB->request([
                             'SELECT'    => [
                                 'suppliers_id',
                                 'glpi_suppliers.entities_id',
                                 'glpi_suppliers.is_recursive'
                             ],
                             'FROM'      => 'glpi_infocoms',
                             'LEFT JOIN' => [
                                 'glpi_suppliers'  => [
                                     'ON' => [
                                         'glpi_infocoms'   => 'suppliers_id',
                                         'glpi_suppliers'  => 'id'
                                     ]
                                 ]
                             ],
                             'WHERE'     => [
                                 'suppliers_id' => ['>', 0],
                                 'itemtype'     => $itemtype,
                                 'items_id'     => $this->needtobe_transfer[$itemtype]
                             ]
                         ]);

                        foreach ($iterator as $data) {
                            if (
                                $data['is_recursive']
                                 && in_array($data['entities_id'], $to_entity_ancestors)
                            ) {
                                $this->addNotToBeTransfer('Supplier', $data['suppliers_id']);
                            } else {
                                $this->addToBeTransfer('Supplier', $data['suppliers_id']);
                            }
                        }
                    }
                }
            }
        }

       // Contact / Supplier : keep / delete + clean unused / keep unused
        if ($this->options['keep_contact']) {
            $contact_suppliers = [];
           // Clean DB
            $DB->delete('glpi_contacts_suppliers', ['glpi_contacts.id'  => null], [
                'LEFT JOIN' => [
                    'glpi_contacts' => [
                        'ON' => [
                            'glpi_contacts_suppliers'  => 'contacts_id',
                            'glpi_contacts'            => 'id'
                        ]
                    ]
                ]
            ]);

           // Clean DB
            $DB->delete('glpi_contacts_suppliers', ['glpi_suppliers.id'  => null], [
                'LEFT JOIN' => [
                    'glpi_suppliers' => [
                        'ON' => [
                            'glpi_contacts_suppliers'  => 'suppliers_id',
                            'glpi_suppliers'           => 'id'
                        ]
                    ]
                ]
            ]);

            if (isset($this->needtobe_transfer['Supplier']) && count($this->needtobe_transfer['Supplier'])) {
                // Supplier Contact
                $iterator = $DB->request([
                    'SELECT'    => [
                        'contacts_id',
                        'glpi_contacts.entities_id',
                        'glpi_contacts.is_recursive'
                    ],
                    'FROM'      => 'glpi_contacts_suppliers',
                    'LEFT JOIN' => [
                        'glpi_contacts'  => [
                            'ON' => [
                                'glpi_contacts_suppliers'  => 'contacts_id',
                                'glpi_contacts'            => 'id'
                            ]
                        ]
                    ],
                    'WHERE'     => [
                        'suppliers_id' => $this->needtobe_transfer['Supplier']
                    ]
                ]);

                foreach ($iterator as $data) {
                    if (
                        $data['is_recursive']
                         && in_array($data['entities_id'], $to_entity_ancestors)
                    ) {
                        $this->addNotToBeTransfer('Contact', $data['contacts_id']);
                    } else {
                        $this->addToBeTransfer('Contact', $data['contacts_id']);
                    }
                }
            }
        }

       // Document : keep / delete + clean unused / keep unused
        if ($this->options['keep_document']) {
            foreach (Document::getItemtypesThatCanHave() as $itemtype) {
                if (isset($this->needtobe_transfer[$itemtype]) && count($this->needtobe_transfer[$itemtype])) {
                    $itemtable = getTableForItemType($itemtype);
                    // Clean DB
                    $DB->delete(
                        'glpi_documents_items',
                        [
                            "$itemtable.id"  => null,
                            'glpi_documents_items.itemtype' => $itemtype,
                        ],
                        [
                            'LEFT JOIN' => [
                                $itemtable => [
                                    'ON' => [
                                        'glpi_documents_items'  => 'items_id',
                                        $itemtable              => 'id',
                                    ]
                                ]
                            ]
                        ]
                    );

                     $iterator = $DB->request([
                         'SELECT'    => [
                             'documents_id',
                             'glpi_documents.entities_id',
                             'glpi_documents.is_recursive'
                         ],
                         'FROM'      => 'glpi_documents_items',
                         'LEFT JOIN' => [
                             'glpi_documents'  => [
                                 'ON' => [
                                     'glpi_documents_items'  => 'documents_id',
                                     'glpi_documents'        => 'id', [
                                         'AND' => [
                                             'itemtype' => $itemtype
                                         ]
                                     ]
                                 ]
                             ]
                         ],
                         'WHERE'     => [
                             'items_id' => $this->needtobe_transfer[$itemtype]
                         ]
                     ]);

                    foreach ($iterator as $data) {
                        if (
                            $data['is_recursive']
                            && in_array($data['entities_id'], $to_entity_ancestors)
                        ) {
                            $this->addNotToBeTransfer('Document', $data['documents_id']);
                        } else {
                            $this->addToBeTransfer('Document', $data['documents_id']);
                        }
                    }
                }
            }
        }

       // printer -> cartridges : keep / delete + clean
        if ($this->options['keep_cartridgeitem']) {
            if (isset($this->needtobe_transfer['Printer']) && count($this->needtobe_transfer['Printer'])) {
                $iterator = $DB->request([
                    'SELECT' => 'cartridgeitems_id',
                    'FROM'   => 'glpi_cartridges',
                    'WHERE'  => ['printers_id' => $this->needtobe_transfer['Printer']]
                ]);

                foreach ($iterator as $data) {
                     $this->addToBeTransfer('CartridgeItem', $data['cartridgeitems_id']);
                }
            }
        }

       // Init all types if not defined
        foreach ($types as $itemtype) {
            if (!isset($this->needtobe_transfer[$itemtype])) {
                $this->needtobe_transfer[$itemtype] = [-1];
            }
        }
    }


    /**
     * transfer an item to another item (may be the same) in the new entity
     *
     * @param string $itemtype Itemtype of the item
     * @param int $ID          ID of the item
     * @param int $newID       ID of the new item
     *
     * Transfer item to a new Item if $ID==$newID : only update entities_id field :
     *                                $ID!=$new ID -> copy datas (like template system)
     * @return void
     **/
    public function transferItem($itemtype, $ID, $newID)
    {
        global $CFG_GLPI;

        if (!($item = getItemForItemtype($itemtype))) {
            return;
        }

       // Is already transfer ?
        if (!isset($this->already_transfer[$itemtype][$ID])) {
           // Check computer exists ?
            if ($item->getFromDB($newID)) {
               // Network connection ? keep connected / keep_disconnected / delete
                if (in_array($itemtype, $CFG_GLPI['networkport_types'])) {
                    $this->transferNetworkLink($itemtype, $ID, $newID);
                }

               // Device : keep / delete : network case : delete if net connection delete in import case
                if (in_array($itemtype, Item_Devices::getConcernedItems())) {
                    $this->transferDevices($itemtype, $ID, $newID);
                }

               // Reservation : keep / delete
                if (in_array($itemtype, $CFG_GLPI["reservation_types"])) {
                    $this->transferReservations($itemtype, $ID, $newID);
                }

               // History : keep / delete
                $this->transferHistory($itemtype, $ID, $newID);
               // Ticket : delete / keep and clean ref / keep and move
                $this->transferTickets($itemtype, $ID, $newID);
               // Infocoms : keep / delete

                if (Infocom::canApplyOn($itemtype)) {
                    $this->transferInfocoms($itemtype, $ID, $newID);
                }

                if ($itemtype == 'Software') {
                    $this->transferSoftwareLicensesAndVersions($ID);
                }

               // Connected item is transferred
                if (in_array($itemtype, $CFG_GLPI["directconnect_types"])) {
                    $this->manageConnectionComputer($itemtype, $ID);
                }

               // Certificate : keep / delete + clean unused / keep unused
                if (in_array($itemtype, $CFG_GLPI["certificate_types"])) {
                    $this->transferCertificates($itemtype, $ID, $newID);
                }

               // Contract : keep / delete + clean unused / keep unused
                if (in_array($itemtype, $CFG_GLPI["contract_types"])) {
                    $this->transferContracts($itemtype, $ID, $newID);
                }

               // Contact / Supplier : keep / delete + clean unused / keep unused
                if ($itemtype == 'Supplier') {
                    $this->transferSupplierContacts($ID, $newID);
                }

               // Document : keep / delete + clean unused / keep unused
                if (Document::canApplyOn($itemtype)) {
                    $this->transferDocuments($itemtype, $ID, $newID);

                    if (is_a($itemtype, CommonITILObject::class, true)) {
                       // Transfer ITIL childs documents too
                        $itil_item = getItemForItemtype($itemtype);
                        $itil_item->getFromDB($ID);
                        $document_item_obj = new Document_Item();
                        $document_items = $document_item_obj->find(
                            $itil_item->getAssociatedDocumentsCriteria(true)
                        );
                        foreach ($document_items as $document_item) {
                              $this->transferDocuments(
                                  $document_item['itemtype'],
                                  $document_item['items_id'],
                                  $document_item['items_id']
                              );
                        }
                    }
                }

               // Transfer compatible printers
                if ($itemtype == 'CartridgeItem') {
                    $this->transferCompatiblePrinters($ID, $newID);
                }

               // Cartridges  and cartridges items linked to printer
                if ($itemtype == 'Printer') {
                    $this->transferPrinterCartridges($ID, $newID);
                }

               // Transfer Item
                $input = [
                    'id'          => $newID,
                    'entities_id' => $this->to,
                    '_transfer'   => 1
                ];

               // Manage Location dropdown
                if (isset($item->fields['locations_id'])) {
                    $input['locations_id'] = $this->transferDropdownLocation($item->fields['locations_id']);
                }

                if (in_array($itemtype, ['Ticket', 'Problem', 'Change'])) {
                    $input2 = $this->transferHelpdeskAdditionalInformations($item->fields);
                    $input  = array_merge($input, $input2);
                    $this->transferTaskCategory($itemtype, $ID, $newID);
                    $this->transferLinkedSuppliers($itemtype, $ID, $newID);
                }

                $item->update($input);
                $this->addToAlreadyTransfer($itemtype, $ID, $newID);

               // Do it after item transfer for entity checks
                if ($itemtype == 'Computer') {
                   // Monitor Direct Connect : keep / delete + clean unused / keep unused
                    $this->transferDirectConnection($itemtype, $ID, 'Monitor');
                   // Peripheral Direct Connect : keep / delete + clean unused / keep unused
                    $this->transferDirectConnection($itemtype, $ID, 'Peripheral');
                   // Phone Direct Connect : keep / delete + clean unused / keep unused
                    $this->transferDirectConnection($itemtype, $ID, 'Phone');
                   // Printer Direct Connect : keep / delete + clean unused / keep unused
                    $this->transferDirectConnection($itemtype, $ID, 'Printer');
                   // Computer Disks :  delete them or not ?
                    $this->transferItem_Disks($itemtype, $ID);
                }

                if (in_array($itemtype, $CFG_GLPI['software_types'])) {
                   // License / Software :  keep / delete + clean unused / keep unused
                    $this->transferItemSoftwares($itemtype, $ID);
                }

                Plugin::doHook(Hooks::ITEM_TRANSFER, ['type'        => $itemtype,
                    'id'          => $ID,
                    'newID'       => $newID,
                    'entities_id' => $this->to
                ]);
            }
        }
    }


    /**
     * Add an item to already transfer array
     *
     * @param string $itemtype Itemtype of the item
     * @param int $ID          ID of the item
     * @param int $newID       ID of the new item
     *
     * @return void
     **/
    public function addToAlreadyTransfer($itemtype, $ID, $newID)
    {

        if (!isset($this->already_transfer[$itemtype])) {
            $this->already_transfer[$itemtype] = [];
        }
        $this->already_transfer[$itemtype][$ID] = $newID;
    }


    /**
     * Transfer location
     *
     * @param int $locID location ID
     *
     * @return int The new location ID
     **/
    public function transferDropdownLocation($locID)
    {
        if ($locID > 0) {
            if (isset($this->already_transfer['locations_id'][$locID])) {
                return $this->already_transfer['locations_id'][$locID];
            }
           // else  // Not already transfer
           // Search init item
            $location = new Location();
            if ($location->getFromDB($locID)) {
                $data = Toolbox::addslashes_deep($location->fields);

                $input['entities_id']  = $this->to;
                $input['completename'] = $data['completename'];
                $newID                 = $location->findID($input);

                if ($newID < 0) {
                    $newID = $location->import($input);
                }

                $this->addToAlreadyTransfer('locations_id', $locID, $newID);
                return $newID;
            }
        }
        return 0;
    }


    /**
     * Transfer socket
     *
     * @param int $sockets_id socket ID
     *
     * @return int The new socket ID
     **/
    public function transferDropdownSocket($sockets_id)
    {
        global $DB;

        if ($sockets_id > 0) {
            if (isset($this->already_transfer['sockets_id'][$sockets_id])) {
                return $this->already_transfer['sockets_id'][$sockets_id];
            }
           // else  // Not already transfer
           // Search init item
            $socket = new Socket();
            if ($socket->getFromDB($sockets_id)) {
                $data  = Toolbox::addslashes_deep($socket->fields);
                $locID = $this->transferDropdownLocation($socket->fields['locations_id']);

               // Search if the locations_id already exists in the destination entity
                $iterator = $DB->request([
                    'SELECT' => 'id',
                    'FROM'   => 'glpi_sockets',
                    'WHERE'  => [
                        'entities_id'  => $this->to,
                        'name'         => Toolbox::addslashes_deep($socket->fields['name']),
                        'locations_id' => $locID
                    ]
                ]);

                if (count($iterator)) {
                      // Found : -> use it
                      $row = $iterator->current();
                      $newID = $row['id'];
                      $this->addToAlreadyTransfer('sockets_id', $sockets_id, $newID);
                      return $newID;
                }

               // Not found :
               // add item
                $newID    = $socket->add(['name'         => $data['name'],
                    'comment'      => $data['comment'],
                    'entities_id'  => $this->to,
                    'locations_id' => $locID
                ]);

                $this->addToAlreadyTransfer('sockets_id', $sockets_id, $newID);
                return $newID;
            }
        }
        return 0;
    }


    /**
     * Transfer cartridges of a printer
     *
     * @param int $ID     original ID of the printer
     * @param int $newID  new ID of the printer
     *
     * @return void
     **/
    public function transferPrinterCartridges($ID, $newID)
    {
        global $DB;

       // Get cartrdiges linked
        $iterator = $DB->request([
            'FROM'   => 'glpi_cartridges',
            'WHERE'  => ['printers_id' => $ID]
        ]);

        if (count($iterator)) {
            $cart     = new Cartridge();
            $carttype = new CartridgeItem();

            foreach ($iterator as $data) {
                $need_clean_process = false;

               // Foreach cartridges
               // if keep
                if ($this->options['keep_cartridgeitem']) {
                    $newcartID     = - 1;
                    $newcarttypeID = -1;

                    // 1 - Search carttype destination ?
                    // Already transfer carttype :
                    if (isset($this->already_transfer['CartridgeItem'][$data['cartridgeitems_id']])) {
                        $newcarttypeID
                           = $this->already_transfer['CartridgeItem'][$data['cartridgeitems_id']];
                    } else {
                        if (isset($this->needtobe_transfer['Printer']) && count($this->needtobe_transfer['Printer'])) {
                        // Not already transfer cartype
                            $ccriteria = [
                                'COUNT'  => 'cpt',
                                'FROM'   => 'glpi_cartridges',
                                'WHERE'  => [
                                    'cartridgeitems_id'  => $data['cartridgeitems_id'],
                                    'printers_id'        => ['>', 0],
                                    'NOT'                => [
                                        'printers_id'  => $this->needtobe_transfer['Printer']
                                    ]
                                ]
                            ];

                            $result = $DB->request($ccriteria)->current();

                        // Is the carttype will be completly transfer ?
                            if ($result['cpt'] == 0) {
                                   // Yes : transfer
                                   $need_clean_process = false;
                                   $this->transferItem(
                                       'CartridgeItem',
                                       $data['cartridgeitems_id'],
                                       $data['cartridgeitems_id']
                                   );
                                      $newcarttypeID = $data['cartridgeitems_id'];
                            } else {
                                   // No : copy carttype
                                   $need_clean_process = true;
                                   $carttype->getFromDB($data['cartridgeitems_id']);
                                   // Is existing carttype in the destination entity ?
                                   $items_iterator = $DB->request([
                                       'FROM'   => 'glpi_cartridgeitems',
                                       'WHERE'  => [
                                           'entities_id'  => $this->to,
                                           'name'         => addslashes($carttype->fields['name'])
                                       ]
                                   ]);

                                if (count($items_iterator)) {
                                    $row = $items_iterator->current();
                                    $newcarttypeID = $row['id'];
                                }

                             // Not found -> transfer copy
                                if ($newcarttypeID < 0) {
                                 // 1 - create new item
                                    unset($carttype->fields['id']);
                                    $input                = $carttype->fields;
                                    $input['entities_id'] = $this->to;
                                    unset($carttype->fields);
                                    $newcarttypeID        = $carttype->add(Toolbox::addslashes_deep($input));
                                 // 2 - transfer as copy
                                    $this->transferItem(
                                        'CartridgeItem',
                                        $data['cartridgeitems_id'],
                                        $newcarttypeID
                                    );
                                }
                            }

                        // Found -> use to link : nothing to do
                        }
                    }

                    // Update cartridge if needed
                    if (
                        ($newcarttypeID > 0)
                        && ($newcarttypeID != $data['cartridgeitems_id'])
                    ) {
                        $cart->update(['id'                => $data['id'],
                            'cartridgeitems_id' => $newcarttypeID
                        ]);
                    }
                } else { // Do not keep
                  // If same printer : delete cartridges
                    if ($ID == $newID) {
                         $DB->delete('glpi_cartridges', ['printers_id' => $ID]);
                    }
                    $need_clean_process = true;
                }

              // CLean process
                if (
                    $need_clean_process
                    && $this->options['clean_cartridgeitem']
                ) {
                 // Clean carttype
                    $result = $DB->request([
                        'COUNT'  => 'cpt',
                        'FROM'   => 'glpi_cartridges',
                        'WHERE'  => [
                            'cartridgeitems_id'  => $data['cartridgeitems_id']
                        ]
                    ])->current();

                    if ($result['cpt'] == 0) {
                        if ($this->options['clean_cartridgeitem'] == 1) { // delete
                            $carttype->delete(['id' => $data['cartridgeitems_id']]);
                        }
                        if ($this->options['clean_cartridgeitem'] == 2) { // purge
                            $carttype->delete(['id' => $data['cartridgeitems_id']], 1);
                        }
                    }
                }
            }
        }
    }


    /**
     * Copy (if needed) One software to the destination entity
     *
     * @param int $ID ID of the software
     *
     * @return int ID of the new software (could be the same)
     **/
    public function copySingleSoftware($ID)
    {
        global $DB;

        if (isset($this->already_transfer['Software'][$ID])) {
            return $this->already_transfer['Software'][$ID];
        }

        $soft = new Software();
        if ($soft->getFromDB($ID)) {
            if (
                $soft->fields['is_recursive']
                && in_array($soft->fields['entities_id'], getAncestorsOf(
                    "glpi_entities",
                    $this->to
                ))
            ) {
               // no need to copy
                $newsoftID = $ID;
            } else {
                $manufacturer = [];
                if (
                    isset($soft->fields['manufacturers_id'])
                    && ($soft->fields['manufacturers_id'] > 0)
                ) {
                    $manufacturer = ['manufacturers_id' => $soft->fields['manufacturers_id']];
                }

                $iterator = $DB->request([
                    'SELECT' => 'id',
                    'FROM'   => 'glpi_softwares',
                    'WHERE'  => [
                        'entities_id'  => $this->to,
                        'name'         => addslashes($soft->fields['name'])
                    ] + $manufacturer
                ]);

                if ($data = $iterator->current()) {
                    $newsoftID = $data["id"];
                } else {
                   // create new item (don't check if move possible => clean needed)
                    unset($soft->fields['id']);
                    $input                = $soft->fields;
                    $input['entities_id'] = $this->to;
                    unset($soft->fields);
                    $newsoftID            = $soft->add(Toolbox::addslashes_deep($input));
                }
            }

            $this->addToAlreadyTransfer('Software', $ID, $newsoftID);
            return $newsoftID;
        }

        return -1;
    }


    /**
     * Copy (if needed) One softwareversion to the Dest Entity
     *
     * @param int $ID ID of the version
     *
     * @return int ID of the new version (could be the same)
     **/
    public function copySingleVersion($ID)
    {
        global $DB;

        if (isset($this->already_transfer['SoftwareVersion'][$ID])) {
            return $this->already_transfer['SoftwareVersion'][$ID];
        }

        $vers = new SoftwareVersion();
        if ($vers->getFromDB($ID)) {
            $newsoftID = $this->copySingleSoftware($vers->fields['softwares_id']);

            if ($newsoftID == $vers->fields['softwares_id']) {
               // no need to copy
                $newversID = $ID;
            } else {
                $iterator = $DB->request([
                    'SELECT' => 'id',
                    'FROM'   => 'glpi_softwareversions',
                    'WHERE'  => [
                        'softwares_id' => $newsoftID,
                        'name'         => addslashes($vers->fields['name'])
                    ]
                ]);

                if ($data = $iterator->current()) {
                    $newversID = $data["id"];
                } else {
                   // create new item (don't check if move possible => clean needed)
                    unset($vers->fields['id']);
                    $input                 = $vers->fields;
                    $vers->fields = [];
                   // entities_id and is_recursive from new software are set in prepareInputForAdd
                    $input['softwares_id'] = $newsoftID;
                    $newversID             = $vers->add(Toolbox::addslashes_deep($input));
                }
            }

            $this->addToAlreadyTransfer('SoftwareVersion', $ID, $newversID);
            return $newversID;
        }

        return -1;
    }


    /**
     * Transfer disks of an item
     *
     * @param string  $itemtype Item type
     * @param integer $ID       ID of the item
     *
     * @return void
     */
    public function transferItem_Disks($itemtype, $ID)
    {
        if (!$this->options['keep_disk']) {
            $disk = new Item_Disk();
            $disk->cleanDBonItemDelete($itemtype, $ID);
        }
    }

    /**
     * Transfer software of an item
     *
     * @param string $itemtype  Type of the item
     * @param int    $ID        ID of the item
     *
     * @return void
     **/
    public function transferItemSoftwares($itemtype, $ID)
    {
        global $DB;

       // Get Installed version
        $criteria = [
            'FROM'   => 'glpi_items_softwareversions',
            'WHERE'  => [
                'items_id'     => $ID,
                'itemtype'     => $itemtype,
            ]
        ];

        if (count($this->noneedtobe_transfer['SoftwareVersion'] ?? [])) {
            $criteria['WHERE']['NOT'] = [
                'softwareversions_id' => $this->noneedtobe_transfer['SoftwareVersion'],
            ];
        }

        $iterator = $DB->request($criteria);

        foreach ($iterator as $data) {
            if ($this->options['keep_software']) {
                $newversID = $this->copySingleVersion($data['softwareversions_id']);

                if (
                    ($newversID > 0)
                    && ($newversID != $data['softwareversions_id'])
                ) {
                    $DB->update(
                        'glpi_items_softwareversions',
                        [
                            'softwareversions_id' => $newversID
                        ],
                        [
                            'id' => $data['id']
                        ]
                    );
                }
            } else { // Do not keep
               // Delete inst software for item
                $DB->delete('glpi_items_softwareversions', ['id' => $data['id']]);
            }
        }

       // Affected licenses
        if ($this->options['keep_software']) {
            $iterator = $DB->request([
                'SELECT' => 'id',
                'FROM'   => 'glpi_items_softwarelicenses',
                'WHERE'  => [
                    'items_id'  => $ID,
                    'itemtype'  => $itemtype
                ]
            ]);
            foreach ($iterator as $data) {
                $this->transferAffectedLicense($data['id']);
            }
        } else {
            $DB->delete('glpi_items_softwarelicenses', [
                'items_id'  => $ID,
                'itemtype'  => $itemtype
            ]);
        }
    }


    /**
     * Transfer affected licenses to an item
     *
     * @param int $ID ID of the License
     *
     * @return void
     **/
    public function transferAffectedLicense($ID)
    {
        global $DB;

        $item_softwarelicense = new Item_SoftwareLicense();
        $license                  = new SoftwareLicense();

        if ($item_softwarelicense->getFromDB($ID)) {
            if ($license->getFromDB($item_softwarelicense->getField('softwarelicenses_id'))) {
                //// Update current : decrement number by 1 if valid
                if ($license->getField('number') > 1) {
                    $license->update(['id'     => $license->getID(),
                        'number' => ($license->getField('number') - 1)
                    ]);
                } else if ($license->getField('number') == 1) {
                   // Drop license
                    $license->delete(['id' => $license->getID()]);
                }

                // Create new license : need to transfer softwre and versions before
                $input     = [];
                $newsoftID = $this->copySingleSoftware($license->fields['softwares_id']);

                if ($newsoftID > 0) {
                   //// If license already exists : increment number by one
                    $iterator = $DB->request([
                        'SELECT' => ['id', 'number'],
                        'FROM'   => 'glpi_softwarelicenses',
                        'WHERE'  => [
                            'softwares_id' => $newsoftID,
                            'name'         => addslashes($license->fields['name']),
                            'serial'       => addslashes($license->fields['serial'])
                        ]
                    ]);

                    $newlicID = -1;
                   //// If exists : increment number by 1
                    if (count($iterator)) {
                         $data     = $iterator->current();
                         $newlicID = $data['id'];
                         $license->update(['id'     => $data['id'],
                             'number' => $data['number'] + 1
                         ]);
                    } else {
                      //// If not exists : create with number = 1
                        $input = $license->fields;
                        foreach (
                            ['softwareversions_id_buy',
                                'softwareversions_id_use'
                            ] as $field
                        ) {
                            if ($license->fields[$field] > 0) {
                                $newversID = $this->copySingleVersion($license->fields[$field]);
                                if (
                                    ($newversID > 0)
                                    && ($newversID != $license->fields[$field])
                                ) {
                                    $input[$field] = $newversID;
                                }
                            }
                        }

                        unset($input['id']);
                        $input['number']       = 1;
                        $input['entities_id']  = $this->to;
                        $input['softwares_id'] = $newsoftID;
                        $newlicID              = $license->add(Toolbox::addslashes_deep($input));
                    }

                    if ($newlicID > 0) {
                        $input = ['id'                  => $ID,
                            'softwarelicenses_id' => $newlicID
                        ];
                        $item_softwarelicense->update($input);
                    }
                }
            }
        }
    }


    /**
     * Transfer License and Version of a Software
     *
     * @param int $ID ID of the Software
     *
     * @return void
     **/
    public function transferSoftwareLicensesAndVersions($ID)
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => 'glpi_softwarelicenses',
            'WHERE'  => ['softwares_id' => $ID]
        ]);

        foreach ($iterator as $data) {
            $this->transferItem('SoftwareLicense', $data['id'], $data['id']);
        }

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => 'glpi_softwareversions',
            'WHERE'  => ['softwares_id' => $ID]
        ]);

        foreach ($iterator as $data) {
            // Just Store the info.
            $this->addToAlreadyTransfer('SoftwareVersion', $data['id'], $data['id']);
        }
    }

    /**
     * Delete old software versions that had already been transferred
     * @return void
     */
    public function cleanSoftwareVersions()
    {

        if (!isset($this->already_transfer['SoftwareVersion'])) {
            return;
        }

        $vers = new SoftwareVersion();
        foreach ($this->already_transfer['SoftwareVersion'] as $old => $new) {
            if (
                (countElementsInTable("glpi_softwarelicenses", ['softwareversions_id_buy' => $old]) == 0)
                && (countElementsInTable("glpi_softwarelicenses", ['softwareversions_id_use' => $old]) == 0)
                && (countElementsInTable(
                    "glpi_items_softwareversions",
                    ['softwareversions_id' => $old]
                ) == 0)
            ) {
                $vers->delete(['id' => $old]);
            }
        }
    }


    /**
     * Delete old software that had already been transferred
     * @return void
     */
    public function cleanSoftwares()
    {

        if (!isset($this->already_transfer['Software'])) {
            return;
        }

        $soft = new Software();
        foreach ($this->already_transfer['Software'] as $old => $new) {
            if (
                (countElementsInTable("glpi_softwarelicenses", ['softwares_id' => $old]) == 0)
                && (countElementsInTable("glpi_softwareversions", ['softwares_id' => $old]) == 0)
            ) {
                if ($this->options['clean_software'] == 1) { // delete
                    $soft->delete(['id' => $old], 0);
                } else if ($this->options['clean_software'] ==  2) { // purge
                    $soft->delete(['id' => $old], 1);
                }
            }
        }
    }

    /**
     * Transfer certificates
     *
     * @param string $itemtype  The original type of transferred item
     * @param int $ID           Original ID of the certificate
     * @param int $newID        New ID of the certificate
     *
     * @return void
     **/
    public function transferCertificates($itemtype, $ID, $newID)
    {
        global $DB;

        $need_clean_process = false;

       // if keep
        if ($this->options['keep_certificate']) {
            $certificate = new Certificate();
           // Get certificates for the item
            $certificates_items_query = [
                'FROM'   => 'glpi_certificates_items',
                'WHERE'  => [
                    'items_id'  => $ID,
                    'itemtype'  => $itemtype,
                ]
            ];
            if (
                isset($this->noneedtobe_transfer['Certificate'])
                && count($this->noneedtobe_transfer['Certificate']) > 0
            ) {
                $certificates_items_query['WHERE'][] = [
                    'NOT' => ['certificates_id' => $this->noneedtobe_transfer['Certificate']]
                ];
            }
            $iterator = $DB->request($certificates_items_query);

           // Foreach get item
            foreach ($iterator as $data) {
                $need_clean_process = false;
                $item_ID            = $data['certificates_id'];
                $newcertificateID   = -1;

               // is already transfer ?
                if (isset($this->already_transfer['Certificate'][$item_ID])) {
                    $newcertificateID = $this->already_transfer['Certificate'][$item_ID];
                    if ($newcertificateID != $item_ID) {
                        $need_clean_process = true;
                    }
                } else {
                   // No
                   // Can be transfer without copy ? = all linked items need to be transfer (so not copy)
                    $canbetransfer = true;
                    $types_iterator = Certificate_Item::getDistinctTypes($item_ID);

                    foreach ($types_iterator as $data_type) {
                        $dtype = $data_type['itemtype'];

                        if (isset($this->needtobe_transfer[$dtype]) && count($this->needtobe_transfer[$dtype])) {
                             // No items to transfer -> exists links
                             $result = $DB->request([
                                 'COUNT'  => 'cpt',
                                 'FROM'   => 'glpi_certificates_items',
                                 'WHERE'  => [
                                     'certificates_id' => $item_ID,
                                     'itemtype'        => $dtype,
                                     'NOT'             => ['items_id' => $this->needtobe_transfer[$dtype]]
                                 ]
                             ])->current();

                            if ($result['cpt'] > 0) {
                                 $canbetransfer = false;
                            }
                        } else {
                            $canbetransfer = false;
                        }

                        if (!$canbetransfer) {
                            break;
                        }
                    }

                   // Yes : transfer
                    if ($canbetransfer) {
                        $this->transferItem('Certificate', $item_ID, $item_ID);
                        $newcertificateID = $item_ID;
                    } else {
                        $need_clean_process = true;
                        $certificate->getFromDB($item_ID);
                       // No : search certificate
                        $certificate_iterator = $DB->request([
                            'SELECT' => 'id',
                            'FROM'   => 'glpi_certificates',
                            'WHERE'  => [
                                'entities_id'  => $this->to,
                                'name'         => addslashes($certificate->fields['name'])
                            ]
                        ]);

                        if (count($certificate_iterator)) {
                            $result = $iterator->current();
                            $newcertificateID = $result['id'];
                            $this->addToAlreadyTransfer('Certificate', $item_ID, $newcertificateID);
                        }

                       // found : use it
                       // not found : copy certificate
                        if ($newcertificateID < 0) {
                           // 1 - create new item
                            unset($certificate->fields['id']);
                            $input                = $certificate->fields;
                            $input['entities_id'] = $this->to;
                            unset($certificate->fields);
                            $newcertificateID     = $certificate->add(Toolbox::addslashes_deep($input));
                           // 2 - transfer as copy
                            $this->transferItem('Certificate', $item_ID, $newcertificateID);
                        }
                    }
                }

               // Update links
                if ($ID == $newID) {
                    if ($item_ID != $newcertificateID) {
                        $DB->update(
                            'glpi_certificates_items',
                            [
                                'certificates_id' => $newcertificateID
                            ],
                            [
                                'id' => $data['id']
                            ]
                        );
                    }
                } else { // Same Item -> update links
                   // Copy Item -> copy links
                    if ($item_ID != $newcertificateID) {
                        $DB->insert(
                            'glpi_certificates_items',
                            [
                                'certificates_id' => $newcertificateID,
                                'items_id'        => $newID,
                                'itemtype'        => $itemtype
                            ]
                        );
                    } else { // same certificate for new item update link
                        $DB->update(
                            'glpi_certificates_items',
                            [
                                'items_id' => $newID
                            ],
                            [
                                'id' => $data['id']
                            ]
                        );
                    }
                }

               // If clean and unused ->
                if (
                    $need_clean_process
                    && $this->options['clean_certificate']
                ) {
                    $remain = $DB->request([
                        'COUNT'  => 'cpt',
                        'FROM'   => 'glpi_certificates_items',
                        'WHERE'  => ['certificates_id' => $item_ID]
                    ])->current();

                    if ($remain['cpt'] == 0) {
                        if ($this->options['clean_certificate'] == 1) {
                            $certificate->delete(['id' => $item_ID]);
                        }
                        if ($this->options['clean_certificate'] == 2) { // purge
                            $certificate->delete(['id' => $item_ID], 1);
                        }
                    }
                }
            }
        } else {// else unlink
            $DB->delete(
                'glpi_certificates_items',
                [
                    'items_id'  => $ID,
                    'itemtype'  => $itemtype
                ]
            );
        }
    }


    /**
     * Transfer contracts
     *
     * @param string $itemtype  The original type of transferred item
     * @param int $ID           Original ID of the contract
     * @param int $newID        New ID of the contract
     *
     * @return void
     **/
    public function transferContracts($itemtype, $ID, $newID)
    {
        global $DB;

        $need_clean_process = false;

       // if keep
        if ($this->options['keep_contract']) {
            $contract = new Contract();
           // Get contracts for the item
            $contracts_items_query = [
                'FROM'   => 'glpi_contracts_items',
                'WHERE'  => [
                    'items_id'  => $ID,
                    'itemtype'  => $itemtype,
                ]
            ];
            if (
                isset($this->noneedtobe_transfer['Contract'])
                && count($this->noneedtobe_transfer['Contract']) > 0
            ) {
                $contracts_items_query['WHERE'][] = [
                    'NOT' => ['contracts_id' => $this->noneedtobe_transfer['Contract']]
                ];
            }
            $iterator = $DB->request($contracts_items_query);

           // Foreach get item
            foreach ($iterator as $data) {
                $need_clean_process = false;
                $item_ID            = $data['contracts_id'];
                $newcontractID      = -1;

               // is already transfer ?
                if (isset($this->already_transfer['Contract'][$item_ID])) {
                    $newcontractID = $this->already_transfer['Contract'][$item_ID];
                    if ($newcontractID != $item_ID) {
                        $need_clean_process = true;
                    }
                } else {
                   // No
                   // Can be transfer without copy ? = all linked items need to be transfer (so not copy)
                    $canbetransfer = true;
                    $types_iterator = Contract_Item::getDistinctTypes($item_ID);

                    foreach ($types_iterator as $data_type) {
                        $dtype = $data_type['itemtype'];

                        if (isset($this->needtobe_transfer[$dtype]) && count($this->needtobe_transfer[$dtype])) {
                             // No items to transfer -> exists links
                             $result = $DB->request([
                                 'COUNT'  => 'cpt',
                                 'FROM'   => 'glpi_contracts_items',
                                 'WHERE'  => [
                                     'contracts_id' => $item_ID,
                                     'itemtype'     => $dtype,
                                     'NOT'          => ['items_id' => $this->needtobe_transfer[$dtype]]
                                 ]
                             ])->current();

                            if ($result['cpt'] > 0) {
                                 $canbetransfer = false;
                            }
                        } else {
                            $canbetransfer = false;
                        }

                        if (!$canbetransfer) {
                            break;
                        }
                    }

                   // Yes : transfer
                    if ($canbetransfer) {
                        $this->transferItem('Contract', $item_ID, $item_ID);
                        $newcontractID = $item_ID;
                    } else {
                        $need_clean_process = true;
                        $contract->getFromDB($item_ID);
                       // No : search contract
                        $contract_iterator = $DB->request([
                            'SELECT' => 'id',
                            'FROM'   => 'glpi_contracts',
                            'WHERE'  => [
                                'entities_id'  => $this->to,
                                'name'         => addslashes($contract->fields['name'])
                            ]
                        ]);

                        if (count($contract_iterator)) {
                            $result = $iterator->current();
                            $newcontractID = $result['id'];
                            $this->addToAlreadyTransfer('Contract', $item_ID, $newcontractID);
                        }

                       // found : use it
                       // not found : copy contract
                        if ($newcontractID < 0) {
                           // 1 - create new item
                            unset($contract->fields['id']);
                            $input                = $contract->fields;
                            $input['entities_id'] = $this->to;
                            unset($contract->fields);
                            $newcontractID        = $contract->add(Toolbox::addslashes_deep($input));
                           // 2 - transfer as copy
                            $this->transferItem('Contract', $item_ID, $newcontractID);
                        }
                    }
                }

               // Update links
                if ($ID == $newID) {
                    if ($item_ID != $newcontractID) {
                        $DB->update(
                            'glpi_contracts_items',
                            [
                                'contracts_id' => $newcontractID
                            ],
                            [
                                'id' => $data['id']
                            ]
                        );
                    }
                } else { // Same Item -> update links
                   // Copy Item -> copy links
                    if ($item_ID != $newcontractID) {
                        $DB->insert(
                            'glpi_contracts_items',
                            [
                                'contracts_id' => $newcontractID,
                                'items_id'     => $newID,
                                'itemtype'     => $itemtype
                            ]
                        );
                    } else { // same contract for new item update link
                        $DB->update(
                            'glpi_contracts_items',
                            [
                                'items_id' => $newID
                            ],
                            [
                                'id' => $data['id']
                            ]
                        );
                    }
                }

               // If clean and unused ->
                if (
                    $need_clean_process
                    && $this->options['clean_contract']
                ) {
                    $remain = $DB->request([
                        'COUNT'  => 'cpt',
                        'FROM'   => 'glpi_contracts_items',
                        'WHERE'  => ['contracts_id' => $item_ID]
                    ])->current();

                    if ($remain['cpt'] == 0) {
                        if ($this->options['clean_contract'] == 1) {
                            $contract->delete(['id' => $item_ID]);
                        }
                        if ($this->options['clean_contract'] == 2) { // purge
                            $contract->delete(['id' => $item_ID], 1);
                        }
                    }
                }
            }
        } else {// else unlink
            $DB->delete(
                'glpi_contracts_items',
                [
                    'items_id'  => $ID,
                    'itemtype'  => $itemtype
                ]
            );
        }
    }


    /**
     * Transfer documents
     *
     * @param string $itemtype  The original type of transferred item
     * @param int $ID           Original ID of the document
     * @param int $newID        New ID of the document
     *
     * @return void
     **/
    public function transferDocuments($itemtype, $ID, $newID)
    {
        global $DB;

        $need_clean_process = false;
       // if keep
        if ($this->options['keep_document']) {
            $document = new Document();
           // Get documents for the item
            $documents_items_query = [
                'FROM'   => 'glpi_documents_items',
                'WHERE'  => [
                    'items_id'  => $ID,
                    'itemtype'  => $itemtype,
                ]
            ];
            if (
                isset($this->noneedtobe_transfer['Document'])
                && count($this->noneedtobe_transfer['Document']) > 0
            ) {
                $documents_items_query['WHERE'][] = [
                    'NOT' => ['documents_id' => $this->noneedtobe_transfer['Document']]
                ];
            }
            $iterator = $DB->request($documents_items_query);

           // Foreach get item
            foreach ($iterator as $data) {
                $need_clean_process = false;
                $item_ID            = $data['documents_id'];
                $newdocID           = -1;

               // is already transfer ?
                if (isset($this->already_transfer['Document'][$item_ID])) {
                    $newdocID = $this->already_transfer['Document'][$item_ID];
                    if ($newdocID != $item_ID) {
                        $need_clean_process = true;
                    }
                } else {
                   // No
                   // Can be transfer without copy ? = all linked items need to be transfer (so not copy)
                    $canbetransfer = true;
                    $types_iterator = Document_Item::getDistinctTypes($item_ID);

                    foreach ($types_iterator as $data_type) {
                        $dtype = $data_type['itemtype'];
                        if (isset($this->needtobe_transfer[$dtype])) {
                             // No items to transfer -> exists links
                             $NOT = $this->needtobe_transfer[$dtype];

                             // contacts, contracts, and suppliers are linked as device.
                            if (isset($this->noneedtobe_transfer[$dtype])) {
                                $NOT = array_merge($NOT, $this->noneedtobe_transfer[$dtype]);
                            }

                            $where = [
                                'documents_id' => $item_ID,
                                'itemtype'     => $dtype
                            ];
                            if (count($NOT)) {
                                $where['NOT'] = ['items_id' => $NOT];
                            }

                            $result = $DB->request([
                                'COUNT'  => 'cpt',
                                'FROM'   => 'glpi_documents_items',
                                'WHERE'  => $where
                            ])->current();

                            if ($result['cpt'] > 0) {
                                 $canbetransfer = false;
                            }
                        }

                        if (!$canbetransfer) {
                            break;
                        }
                    }

                   // Yes : transfer
                    if ($canbetransfer) {
                        $this->transferItem('Document', $item_ID, $item_ID);
                        $newdocID = $item_ID;
                    } else {
                        $need_clean_process = true;
                        $document->getFromDB($item_ID);
                       // No : search contract
                        $doc_iterator = $DB->request([
                            'SELECT' => 'id',
                            'FROM'   => 'glpi_documents',
                            'WHERE'  => [
                                'entities_id'  => $this->to,
                                'name'         => addslashes($document->fields['name'])
                            ]
                        ]);

                        if (count($doc_iterator)) {
                            $result = $doc_iterator->current();
                            $newdocID = $result['id'];
                            $this->addToAlreadyTransfer('Document', $item_ID, $newdocID);
                        }

                       // found : use it
                       // not found : copy doc
                        if ($newdocID < 0) {
                           // 1 - create new item
                            unset($document->fields['id']);
                            $input    = $document->fields;
                           // Not set new entity Do by transferItem
                            unset($document->fields);
                            $newdocID = $document->add(Toolbox::addslashes_deep($input));
                           // 2 - transfer as copy
                            $this->transferItem('Document', $item_ID, $newdocID);
                        }
                    }
                }

               // Update links
                if ($ID == $newID) {
                    if ($item_ID != $newdocID) {
                        $DB->update(
                            'glpi_documents_items',
                            [
                                'documents_id' => $newdocID
                            ],
                            [
                                'id' => $data['id']
                            ]
                        );
                    }
                } else { // Same Item -> update links
                   // Copy Item -> copy links
                    if ($item_ID != $newdocID) {
                        $DB->insert(
                            'glpi_documents_items',
                            [
                                'documents_id' => $newdocID,
                                'items_id'     => $newID,
                                'itemtype'     => $itemtype
                            ]
                        );
                    } else { // same doc for new item update link
                        $DB->update(
                            'glpi_documents_items',
                            [
                                'items_id' => $newID
                            ],
                            [
                                'id' => $data['id']
                            ]
                        );
                    }
                }

               // If clean and unused ->
                if (
                    $need_clean_process
                    && $this->options['clean_document']
                ) {
                    $remain = $DB->request([
                        'COUNT'  => 'cpt',
                        'FROM'   => 'glpi_documents_items',
                        'WHERE'  => [
                            'documents_id' => $item_ID
                        ]
                    ])->current();

                    if ($remain['cpt'] == 0) {
                        if ($this->options['clean_document'] == 1) {
                            $document->delete(['id' => $item_ID]);
                        }
                        if ($this->options['clean_document'] == 2) { // purge
                            $document->delete(['id' => $item_ID], 1);
                        }
                    }
                }
            }
        } else {// else unlink
            $DB->delete(
                'glpi_documents_items',
                [
                    'items_id'  => $ID,
                    'itemtype'  => $itemtype
                ]
            );
        }
    }


    /**
     * Delete direct connection for a linked item
     *
     * @param string $itemtype  The original type of transferred item
     * @param int $ID           ID of the item
     * @param string $link_type Type of the linked items to transfer
     *
     * @return void
     **/
    public function transferDirectConnection($itemtype, $ID, $link_type)
    {
        global $DB;

       // Only same Item case : no duplication of computers
       // Default : delete
        $keep      = 0;
        $clean     = 0;

        switch ($link_type) {
            case 'Printer':
                $keep      = $this->options['keep_dc_printer'];
                $clean     = $this->options['clean_dc_printer'];
                break;

            case 'Monitor':
                $keep      = $this->options['keep_dc_monitor'];
                $clean     = $this->options['clean_dc_monitor'];
                break;

            case 'Peripheral':
                $keep      = $this->options['keep_dc_peripheral'];
                $clean     = $this->options['clean_dc_peripheral'];
                break;

            case 'Phone':
                $keep  = $this->options['keep_dc_phone'];
                $clean = $this->options['clean_dc_phone'];
                break;
        }

        if (!($link_item = getItemForItemtype($link_type))) {
            return;
        }

       // Get connections
        $criteria = [
            'FROM'   => 'glpi_computers_items',
            'WHERE'  => [
                'computers_id' => $ID,
                'itemtype'     => $link_type
            ]
        ];

        if ($link_item->maybeRecursive() && count($this->noneedtobe_transfer[$link_type])) {
            $criteria['WHERE']['NOT'] = ['items_id' => $this->noneedtobe_transfer[$link_type]];
        }

        $iterator = $DB->request($criteria);

       // Foreach get item
        foreach ($iterator as $data) {
            $item_ID = $data['items_id'];
            if ($link_item->getFromDB($item_ID)) {
               // If global :
                if ($link_item->fields['is_global'] == 1) {
                    $need_clean_process = false;
                    // if keep
                    if ($keep) {
                        $newID = -1;

                      // Is already transfer ?
                        if (isset($this->already_transfer[$link_type][$item_ID])) {
                             $newID = $this->already_transfer[$link_type][$item_ID];
                             // Already transfer as a copy : need clean process
                            if ($newID != $item_ID) {
                                $need_clean_process = true;
                            }
                        } else { // Not yet tranfer
                           // Can be managed like a non global one ?
                           // = all linked computers need to be transfer (so not copy)
                            $comp_criteria = [
                                'COUNT'  => 'cpt',
                                'FROM'   => 'glpi_computers_items',
                                'WHERE'  => [
                                    'itemtype'  => $link_type,
                                    'items_id'  => $item_ID
                                ]
                            ];
                            if (count($this->needtobe_transfer['Computer'])) {
                                $comp_criteria['WHERE']['NOT'] = ['computers_id' => $this->needtobe_transfer['Computer']];
                            }
                            $result = $DB->request($comp_criteria)->current();

                         // All linked computers need to be transfer -> use unique transfer system
                            if ($result['cpt'] == 0) {
                                $need_clean_process = false;
                                $this->transferItem($link_type, $item_ID, $item_ID);
                                $newID = $item_ID;
                            } else { // else Transfer by Copy
                                $need_clean_process = true;
                           // Is existing global item in the destination entity ?
                                $type_iterator = $DB->request([
                                    'SELECT' => 'id',
                                    'FROM'   => getTableForItemType($link_type),
                                    'WHERE'  => [
                                        'is_global'    => 1,
                                        'entities_id'  => $this->to,
                                        'name'         => addslashes($link_item->getField('name'))
                                    ]
                                ]);

                                if (count($type_iterator)) {
                                      $result = $type_iterator->current();
                                      $newID = $result['id'];
                                      $this->addToAlreadyTransfer($link_type, $item_ID, $newID);
                                }

                           // Not found -> transfer copy
                                if ($newID < 0) {
                                     // 1 - create new item
                                      unset($link_item->fields['id']);
                                      $input                = $link_item->fields;
                                      $input['entities_id'] = $this->to;
                                      unset($link_item->fields);
                                      $newID = $link_item->add(Toolbox::addslashes_deep($input));
                                     // 2 - transfer as copy
                                      $this->transferItem($link_type, $item_ID, $newID);
                                }

                           // Found -> use to link : nothing to do
                            }
                        }

                      // Finish updated link if needed
                        if (
                            ($newID > 0)
                            && ($newID != $item_ID)
                        ) {
                            $DB->update(
                                'glpi_computers_items',
                                [
                                    'items_id' => $newID
                                ],
                                [
                                    'id' => $data['id']
                                ]
                            );
                        }
                    } else {
                     // Else delete link
                     // Call Disconnect for global device (no disconnect behavior, but history )
                        $conn = new Computer_Item();
                        $conn->delete(['id'              => $data['id'],
                            '_no_auto_action' => true
                        ]);

                        $need_clean_process = true;
                    }
                   // If clean and not linked dc -> delete
                    if ($need_clean_process && $clean) {
                         $result = $DB->request([
                             'COUNT'  => 'cpt',
                             'FROM'   => 'glpi_computers_items',
                             'WHERE'  => [
                                 'items_id'  => $item_ID,
                                 'itemtype'  => $link_type
                             ]
                         ])->current();

                        if ($result['cpt'] == 0) {
                            if ($clean == 1) {
                                $link_item->delete(['id' => $item_ID]);
                            }
                            if ($clean == 2) { // purge
                                $link_item->delete(['id' => $item_ID], 1);
                            }
                        }
                    }
                } else { // If unique :
                   //if keep -> transfer list else unlink
                    if ($keep) {
                        $this->transferItem($link_type, $item_ID, $item_ID);
                    } else {
                       // Else delete link (apply disconnect behavior)
                        $conn = new Computer_Item();
                        $conn->delete(['id' => $data['id']]);

                       //if clean -> delete
                        if ($clean == 1) {
                            $link_item->delete(['id' => $item_ID]);
                        } else if ($clean == 2) { // purge
                            $link_item->delete(['id' => $item_ID], 1);
                        }
                    }
                }
            } else {
               // Unexisting item / Force disconnect
                $conn = new Computer_Item();
                $conn->delete(['id'             => $data['id'],
                    '_no_history'    => true,
                    '_no_auto_action' => true
                ]);
            }
        }
    }


    /**
     * Delete direct connection beetween an item and a computer when transfering the item
     *
     * @param string $itemtype Itemtype to tranfer
     * @param int $ID          ID of the item
     *
     * @return void
     * @since 0.84.4
     **/
    public function manageConnectionComputer($itemtype, $ID)
    {
        global $DB;

       // Get connections
        $criteria = [
            'FROM'   => 'glpi_computers_items',
            'WHERE'  => [
                'itemtype'  => $itemtype,
                'items_id'  => $ID
            ]
        ];
        if (count($this->needtobe_transfer['Computer'])) {
            $criteria['WHERE']['NOT'] = ['computers_id' => $this->needtobe_transfer['Computer']];
        }
        $iterator = $DB->request($criteria);

        if (count($iterator)) {
           // Foreach get item
            $conn = new Computer_Item();
            $comp = new Computer();
            foreach ($iterator as $data) {
                $item_ID = $data['items_id'];
                if ($comp->getFromDB($item_ID)) {
                    $conn->delete(['id' => $data['id']]);
                } else {
                   // Unexisting item / Force disconnect
                    $conn->delete(['id'             => $data['id'],
                        '_no_history'    => true,
                        '_no_auto_action' => true
                    ]);
                }
            }
        }
    }


    /**
     * Transfer tickets
     *
     * @param string $itemtype  The original type of transferred item
     * @param int $ID           Original ID of the ticket
     * @param int $newID        New ID of the ticket
     *
     * @return void
     **/
    public function transferTickets($itemtype, $ID, $newID)
    {
        global $DB;

        $job   = new Ticket();
        $rel   = new Item_Ticket();

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_tickets.*',
                'glpi_items_tickets.id AS _relid'
            ],
            'FROM'      => 'glpi_tickets',
            'LEFT JOIN' => [
                'glpi_items_tickets' => [
                    'ON' => [
                        'glpi_items_tickets' => 'tickets_id',
                        'glpi_tickets'       => 'id'
                    ]
                ]
            ],
            'WHERE'     => [
                'items_id'  => $ID,
                'itemtype'  => $itemtype
            ]
        ]);

        if (count($iterator)) {
            switch ($this->options['keep_ticket']) {
               // Transfer
                case 2:
                    // Same Item / Copy Item -> update entity
                    foreach ($iterator as $data) {
                        $input                = $this->transferHelpdeskAdditionalInformations($data);
                        $input['id']          = $data['id'];
                        $input['entities_id'] = $this->to;

                        $job->update($input);

                        $input = [];
                        $input['id']          = $data['_relid'];
                        $input['items_id']    = $newID;
                        $input['itemtype']    = $itemtype;

                        $rel->update($input);

                        $this->addToAlreadyTransfer('Ticket', $data['id'], $data['id']);
                        $this->transferTaskCategory('Ticket', $data['id'], $data['id']);
                    }
                    break;

               // Clean ref : keep ticket but clean link
                case 1:
                  // Same Item / Copy Item : keep and clean ref
                    foreach ($iterator as $data) {
                         $rel->delete(['id'       => $data['_relid']]);
                         $this->addToAlreadyTransfer('Ticket', $data['id'], $data['id']);
                    }
                    break;

               // Delete
                case 0:
                 // Same item -> delete
                    if ($ID == $newID) {
                        foreach ($iterator as $data) {
                            $job->delete(['id' => $data['id']]);
                        }
                    }
                 // Copy Item : nothing to do
                    break;
            }
        }
    }

    /**
     * Transfer suppliers for the specified ticket, change, or problem
     *
     * @since 0.84
     *
     * @param string $itemtype ITIL Object Itemtype (Only Ticket, Change, and Problem supported)
     * @param int $ID          Original ITIL Object ID
     * @param int $newID       New ITIL Object ID
     *
     * @return void
     **/
    public function transferLinkedSuppliers($itemtype, $ID, $newID)
    {
        global $DB;

        switch ($itemtype) {
            case 'Ticket':
                $table = 'glpi_suppliers_tickets';
                $field = 'tickets_id';
                $link  = new Supplier_Ticket();
                break;

            case 'Problem':
                $table = 'glpi_problems_suppliers';
                $field = 'problems_id';
                $link  = new Problem_Supplier();
                break;

            case 'Change':
                $table = 'glpi_changes_suppliers';
                $field = 'changes_id';
                $link  = new Change_Supplier();
                break;
        }

        $iterator = $DB->request([
            'FROM'   => $table,
            'WHERE'  => [$field => $ID]
        ]);

        foreach ($iterator as $data) {
            $input = [];

            if ($data['suppliers_id'] > 0) {
                $supplier = new Supplier();

                if ($supplier->getFromDB($data['suppliers_id'])) {
                    $newID = -1;
                    $iterator = $DB->request([
                        'SELECT' => 'id',
                        'FROM'   => 'glpi_suppliers',
                        'WHERE'  => [
                            'entities_id'  => $this->to,
                            'name'         => addslashes($supplier->fields['name'])
                        ]
                    ]);

                    if (count($iterator)) {
                          $result = $iterator->current();
                          $newID = $result['id'];
                    }
                    if ($newID < 0) {
                       // 1 - create new item
                        unset($supplier->fields['id']);
                        $input                 = $supplier->fields;
                        $input['entities_id']  = $this->to;
                       // Not set new entity Do by transferItem
                        unset($supplier->fields);
                        $newID                 = $supplier->add(Toolbox::addslashes_deep($input));
                    }

                    $input2['id']           = $data['id'];
                    $input2[$field]         = $ID;
                    $input2['suppliers_id'] = $newID;
                    $link->update($input2);
                }
            }
        }
    }


    /**
     * Transfer task categories for the specified ticket, change, or problem
     *
     * @since 0.83
     *
     * @param string $itemtype ITIL Object Itemtype (Only Ticket, Change, and Problem supported)
     * @param int $ID          Original ITIL Object ID
     * @param int $newID       New ITIL Object ID
     *
     * @return void
     **/
    public function transferTaskCategory($itemtype, $ID, $newID)
    {
        global $DB;

        switch ($itemtype) {
            case 'Ticket':
                $table = 'glpi_tickettasks';
                $field = 'tickets_id';
                $task  = new TicketTask();
                break;

            case 'Problem':
                $table = 'glpi_problemtasks';
                $field = 'problems_id';
                $task  = new ProblemTask();
                break;

            case 'Change':
                $table = 'glpi_changetasks';
                $field = 'changes_id';
                $task  = new ProblemTask();
                break;
        }

        $iterator = $DB->request([
            'FROM'   => $table,
            'WHERE'  => [$field => $ID]
        ]);

        foreach ($iterator as $data) {
            $input = [];

            if ($data['taskcategories_id'] > 0) {
                $categ = new TaskCategory();

                if ($categ->getFromDB($data['taskcategories_id'])) {
                    $inputcat['entities_id']  = $this->to;
                    $inputcat['completename'] = addslashes($categ->fields['completename']);
                    $catid                    = $categ->findID($inputcat);
                    if ($catid < 0) {
                        $catid = $categ->import($inputcat);
                    }
                    $input['id']                = $data['id'];
                    $input[$field]              = $ID;
                    $input['taskcategories_id'] = $catid;
                    $task->update($input);
                }
            }
        }
    }


    /**
     * Get additional/updated information for the transfer of an ITIL Object (Ticket, Change, Problem)
     *
     * @param array $data ITIL Object data
     *
     * @return array Updated ITIL Object data
     * @since 0.85 (before transferTicketAdditionalInformations)
     **/
    public function transferHelpdeskAdditionalInformations($data)
    {

        $input               = [];
        $suppliers_id_assign = 0;

       // if ($data['suppliers_id_assign'] > 0) {
       //   $suppliers_id_assign = $this->transferSingleSupplier($data['suppliers_id_assign']);
       // }

       // Transfer ticket category
        $catid = 0;
        if ($data['itilcategories_id'] > 0) {
            $categ = new ITILCategory();

            if ($categ->getFromDB($data['itilcategories_id'])) {
                $inputcat['entities_id']  = $this->to;
                $inputcat['completename'] = addslashes($categ->fields['completename']);
                $catid                    = $categ->findID($inputcat);
                if ($catid < 0) {
                    $catid = $categ->import($inputcat);
                }
            }
        }

        $input['itilcategories_id'] = $catid;
        return $input;
    }


    /**
     * Transfer history
     *
     * @param string $itemtype  The original type of transferred item
     * @param int $ID           Original ID of the item
     * @param int $newID        New ID of the item
     *
     * @return void
     **/
    public function transferHistory($itemtype, $ID, $newID)
    {
        global $DB;

        switch ($this->options['keep_history']) {
           // delete
            case 0:
                // Same item -> delete
                if ($ID == $newID) {
                    $DB->delete(
                        'glpi_logs',
                        [
                            'items_id'  => $ID,
                            'itemtype'  => $itemtype
                        ]
                    );
                }
                // Copy -> nothing to do
                break;

           // Keep history
            default:
               // Copy -> Copy datas
                if ($ID != $newID) {
                    $iterator = $DB->request([
                        'FROM'   => 'glpi_logs',
                        'WHERE'  => [
                            'itemtype'  => $itemtype,
                            'items_id'  => $ID
                        ]
                    ]);

                    foreach ($iterator as $data) {
                          unset($data['id']);
                          $data = Toolbox::addslashes_deep($data);
                          $data = [
                              'items_id'  => $newID,
                              'itemtype'  => $itemtype
                          ] + $data;
                          $DB->insert('glpi_logs', $data);
                    }
                }
               // Same item -> nothing to do
                break;
        }
    }


    /**
     * Transfer compatible printers for a cartridge type
     *
     * @param int $ID     Original ID of the cartridge type
     * @param int $newID  New ID of the cartridge type
     *
     * @return void
     **/
    public function transferCompatiblePrinters($ID, $newID)
    {
        global $DB;

        if ($ID != $newID) {
            $iterator = $DB->request([
                'FROM'   => 'glpi_cartridgeitems_printermodels',
                'WHERE'  => ['cartridgeitems_id' => $ID]
            ]);

            if (count($iterator)) {
                 $cartitem = new CartridgeItem();

                foreach ($iterator as $data) {
                    $data = Toolbox::addslashes_deep($data);
                    $cartitem->addCompatibleType($newID, $data["printermodels_id"]);
                }
            }
        }
    }


    /**
     * Transfer infocoms of an item
     *
     * @param string $itemtype  The original type of transferred item
     * @param int $ID           Original ID of the item
     * @param int $newID        New ID of the item
     *
     * @return void
     **/
    public function transferInfocoms($itemtype, $ID, $newID)
    {
        global $DB;

        $ic = new Infocom();
        if ($ic->getFromDBforDevice($itemtype, $ID)) {
            switch ($this->options['keep_infocom']) {
                // delete
                case 0:
                   // Same item -> delete
                    if ($ID == $newID) {
                        $DB->delete(
                            'glpi_infocoms',
                            [
                                'items_id'  => $ID,
                                'itemtype'  => $itemtype
                            ]
                        );
                    }
                   // Copy : nothing to do
                    break;

                // Keep
                default:
                   // transfer supplier
                    $suppliers_id = 0;
                    if ($ic->fields['suppliers_id'] > 0) {
                        $suppliers_id = $this->transferSingleSupplier($ic->fields['suppliers_id']);
                    }

                   // Copy : copy infocoms
                    if ($ID != $newID) {
                     // Copy items
                        $input                 = $ic->fields;
                        $input['items_id']     = $newID;
                        $input['suppliers_id'] = $suppliers_id;
                        unset($input['id']);
                        unset($ic->fields);
                        $ic->add(Toolbox::addslashes_deep($input));
                    } else {
                     // Same Item : manage only supplier move
                     // Update supplier
                        if (
                            ($suppliers_id > 0)
                            && ($suppliers_id != $ic->fields['suppliers_id'])
                        ) {
                            $ic->update(['id'           => $ic->fields['id'],
                                'suppliers_id' => $suppliers_id
                            ]);
                        }
                    }

                    break;
            }
        }
    }


    /**
     * Transfer a supplier
     *
     * @param int $ID ID of the supplier to transfer
     *
     * @return int ID of the new supplier
     **/
    public function transferSingleSupplier($ID)
    {
        global $DB;

       // TODO clean system : needed ?
        $ent = new Supplier();
        if (
            $this->options['keep_supplier']
            && $ent->getFromDB($ID)
        ) {
            if (isset($this->noneedtobe_transfer['Supplier'][$ID])) {
               // recursive supplier
                return $ID;
            }
            if (isset($this->already_transfer['Supplier'][$ID])) {
               // Already transfer
                return $this->already_transfer['Supplier'][$ID];
            }

            $newID           = -1;
           // Not already transfer
            $links_remaining = 0;
           // All linked items need to be transfer so transfer supplier ?
           // Search for contract
            $criteria = [
                'COUNT'  => 'cpt',
                'FROM'   => 'glpi_contracts_suppliers',
                'WHERE'  => [
                    'suppliers_id' => $ID
                ]
            ];
            if (count($this->needtobe_transfer['Contract'])) {
                $criteria['WHERE']['NOT'] = ['contracts_id' => $this->needtobe_transfer['Contract']];
            }

            $result = $DB->request($criteria)->current();
            $links_remaining = $result['cpt'];

            if ($links_remaining == 0) {
               // Search for infocoms
                if ($this->options['keep_infocom']) {
                    foreach (Infocom::getItemtypesThatCanHave() as $itemtype) {
                        if (isset($this->needtobe_transfer[$itemtype])) {
                            $icriteria = [
                                'COUNT'  => 'cpt',
                                'FROM'   => 'glpi_infocoms',
                                'WHERE'  => [
                                    'suppliers_id' => $ID,
                                    'itemtype'     => $itemtype
                                ]
                            ];
                            if (count($this->needtobe_transfer[$itemtype])) {
                                $icriteria['WHERE']['NOT'] = ['items_id' => $this->needtobe_transfer[$itemtype]];
                            }

                            $result = $DB->request($icriteria)->current();
                            $links_remaining += $result['cpt'];
                        }
                    }
                }
            }

           // All linked items need to be transfer -> use unique transfer system
            if ($links_remaining == 0) {
                $this->transferItem('Supplier', $ID, $ID);
                $newID = $ID;
            } else { // else Transfer by Copy
               // Is existing item in the destination entity ?
                $iterator = $DB->request([
                    'FROM'   => 'glpi_suppliers',
                    'WHERE'  => [
                        'entities_id'  => $this->to,
                        'name'         => addslashes($ent->fields['name'])
                    ]
                ]);

                if (count($iterator)) {
                    $result = $iterator->current();
                    $newID = $result['id'];
                    $this->addToAlreadyTransfer('Supplier', $ID, $newID);
                }

               // Not found -> transfer copy
                if ($newID < 0) {
                   // 1 - create new item
                    unset($ent->fields['id']);
                    $input                = $ent->fields;
                    $input['entities_id'] = $this->to;
                    unset($ent->fields);
                    $newID                = $ent->add(Toolbox::addslashes_deep($input));
                   // 2 - transfer as copy
                    $this->transferItem('Supplier', $ID, $newID);
                }

               // Found -> use to link : nothing to do
            }
            return $newID;
        }
        return 0;
    }


    /**
     * Transfer contacts of a supplier
     *
     * @param int $ID           Original ID of the supplier
     * @param int $newID        New ID of the supplier
     *
     * @return void
     **/
    public function transferSupplierContacts($ID, $newID)
    {
        global $DB;

        $need_clean_process = false;
       // if keep
        if ($this->options['keep_contact']) {
            $contact = new Contact();
           // Get contracts for the item
            $criteria = [
                'FROM'   => 'glpi_contacts_suppliers',
                'WHERE'  => [
                    'suppliers_id' => $ID,
                ]
            ];
            if (count($this->noneedtobe_transfer['Contact'])) {
                $criteria['WHERE']['NOT'] = ['contacts_id' => $this->noneedtobe_transfer['Contact']];
            }
            $iterator = $DB->request($criteria);

           // Foreach get item
            foreach ($iterator as $data) {
                $need_clean_process = false;
                $item_ID            = $data['contacts_id'];
                $newcontactID       = -1;

               // is already transfer ?
                if (isset($this->already_transfer['Contact'][$item_ID])) {
                    $newcontactID = $this->already_transfer['Contact'][$item_ID];
                    if ($newcontactID != $item_ID) {
                        $need_clean_process = true;
                    }
                } else {
                    $canbetransfer = true;
                   // Transfer supplier : is the contact used for another supplier ?
                    if ($ID == $newID) {
                        $scriteria = [
                            'COUNT'  => 'cpt',
                            'FROM'   => 'glpi_contacts_suppliers',
                            'WHERE'  => [
                                'contacts_id'  => $item_ID
                            ]
                        ];
                        if (
                            count($this->needtobe_transfer['Supplier'])
                            || count($this->noneedtobe_transfer['Supplier'])
                        ) {
                            $scriteria['WHERE']['NOT'] = ['suppliers_id' => $this->needtobe_transfer['Supplier'] + $this->noneedtobe_transfer['Supplier']];
                        }

                        $result = $DB->request($scriteria)->current();
                        if ($result['cpt'] > 0) {
                            $canbetransfer = false;
                        }
                    }

                   // Yes : transfer
                    if ($canbetransfer) {
                        $this->transferItem('Contact', $item_ID, $item_ID);
                        $newcontactID = $item_ID;
                    } else {
                        $need_clean_process = true;
                        $contact->getFromDB($item_ID);
                       // No : search contract
                        $contact_iterator = $DB->request([
                            'SELECT' => 'id',
                            'FROM'   => 'glpi_contacts',
                            'WHERE'  => [
                                'entities_id'  => $this->to,
                                'name'         => addslashes($contact->fields['name']),
                                'firstname'    => addslashes($contact->fields['firstname'])
                            ]
                        ]);

                        if (count($contact_iterator)) {
                            $result = $contact_iterator->current();
                            $newcontactID = $result['id'];
                            $this->addToAlreadyTransfer('Contact', $item_ID, $newcontactID);
                        }

                       // found : use it
                       // not found : copy contract
                        if ($newcontactID < 0) {
                           // 1 - create new item
                            unset($contact->fields['id']);
                            $input                = $contact->fields;
                            $input['entities_id'] = $this->to;
                            unset($contact->fields);
                            $newcontactID         = $contact->add(Toolbox::addslashes_deep($input));
                           // 2 - transfer as copy
                            $this->transferItem('Contact', $item_ID, $newcontactID);
                        }
                    }
                }

               // Update links
                if ($ID == $newID) {
                    if ($item_ID != $newcontactID) {
                        $DB->update(
                            'glpi_contacts_suppliers',
                            [
                                'contacts_id' => $newcontactID
                            ],
                            [
                                'id' => $data['id']
                            ]
                        );
                    }
                } else { // Same Item -> update links
                   // Copy Item -> copy links
                    if ($item_ID != $newcontactID) {
                        $DB->insert(
                            'glpi_contacts_suppliers',
                            [
                                'contacts_id'  => $newcontactID,
                                'suppliers_id' => $newID
                            ]
                        );
                    } else { // transfer contact but copy supplier : update link
                        $DB->update(
                            'glpi_contacts_suppliers',
                            [
                                'suppliers_id' => $newID
                            ],
                            [
                                'id' => $data['id']
                            ]
                        );
                    }
                }

               // If clean and unused ->
                if (
                    $need_clean_process
                    && $this->options['clean_contact']
                ) {
                    $remain = $DB->request([
                        'COUNT'  => 'cpt',
                        'FROM'   => 'glpi_contacts_suppliers',
                        'WHERE'  => ['contacts_id' => $item_ID]
                    ])->current();

                    if ($remain['cpt'] == 0) {
                        if ($this->options['clean_contact'] == 1) {
                            $contact->delete(['id' => $item_ID]);
                        }
                        if ($this->options['clean_contact'] == 2) { // purge
                            $contact->delete(['id' => $item_ID], 1);
                        }
                    }
                }
            }
        } else {// else unlink
            $DB->delete(
                'glpi_contacts_suppliers',
                [
                    'suppliers_id' => $ID
                ]
            );
        }
    }


    /**
     * Transfer reservations of an item
     *
     * @param string $itemtype  The original type of transferred item
     * @param int $ID           Original ID of the item
     * @param int $newID        New ID of the item
     *
     * @return void
     **/
    public function transferReservations($itemtype, $ID, $newID)
    {
        $ri = new ReservationItem();

        if ($ri->getFromDBbyItem($itemtype, $ID)) {
            switch ($this->options['keep_reservation']) {
                // delete
                case 0:
                   // Same item -> delete
                    if ($ID == $newID) {
                        $ri->delete(['id' => $ri->fields['id']], true);
                    }
                   // Copy : nothing to do
                    break;

                // Keep
                default:
                   // Copy : set item as reservable
                    if ($ID != $newID) {
                        $input['itemtype']  = $itemtype;
                        $input['items_id']  = $newID;
                        $input['is_active'] = $ri->fields['is_active'];
                        unset($ri->fields);
                        $ri->add(Toolbox::addslashes_deep($input));
                    }
                   // Same item -> nothing to do
                    break;
            }
        }
    }


    /**
     * Transfer devices of an item
     *
     * @param string $itemtype  The original type of transferred item
     * @param int $ID           Original ID of the item
     * @param int $newID        New ID of the item
     *
     * @return void
     **/
    public function transferDevices($itemtype, $ID, $newID)
    {
        global $DB;

       // Only same case because no duplication of computers
        switch ($this->options['keep_device']) {
           // delete devices
            case 0:
                foreach (Item_Devices::getItemAffinities($itemtype) as $type) {
                    $table = getTableForItemType($type);
                    $DB->delete(
                        $table,
                        [
                            'items_id'  => $ID,
                            'itemtype'  => $itemtype
                        ]
                    );
                }
                break;

            default: // Keep devices
                foreach (Item_Devices::getItemAffinities($itemtype) as $itemdevicetype) {
                    $itemdevicetable = getTableForItemType($itemdevicetype);
                    $devicetype      = $itemdevicetype::getDeviceType();
                    $devicetable     = getTableForItemType($devicetype);
                    $fk              = getForeignKeyFieldForTable($devicetable);

                    $device          = new $devicetype();
                    // Get contracts for the item
                    $criteria = [
                        'FROM'   => $itemdevicetable,
                        'WHERE'  => [
                            'items_id'  => $ID,
                            'itemtype'  => $itemtype
                        ]
                    ];
                    if (
                        isset($this->noneedtobe_transfer[$devicetype])
                        && count($this->noneedtobe_transfer[$devicetype])
                    ) {
                        $criteria['WHERE']['NOT'] = [$fk => $this->noneedtobe_transfer[$devicetype]];
                    }
                    $iterator = $DB->request($criteria);

                    if (count($iterator)) {
                       // Foreach get item
                        foreach ($iterator as $data) {
                            $item_ID     = $data[$fk];
                            $newdeviceID = -1;

                          // is already transfer ?
                            if (isset($this->already_transfer[$devicetype][$item_ID])) {
                                 $newdeviceID = $this->already_transfer[$devicetype][$item_ID];
                            } else {
                                // No
                                // Can be transfer without copy ? = all linked items need to be transfer (so not copy)
                                $canbetransfer = true;
                                $type_iterator = $DB->request([
                                    'SELECT'          => 'itemtype',
                                    'DISTINCT'        => true,
                                    'FROM'            => $itemdevicetable,
                                    'WHERE'           => [$fk => $item_ID]
                                ]);

                                foreach ($type_iterator as $data_type) {
                                    $dtype = $data_type['itemtype'];

                                    if (isset($this->needtobe_transfer[$dtype]) && count($this->needtobe_transfer[$dtype])) {
                                         // No items to transfer -> exists links
                                         $dcriteria = [
                                             'COUNT'  => 'cpt',
                                             'FROM'   => $itemdevicetable,
                                             'WHERE'  => [
                                                 $fk         => $item_ID,
                                                 'itemtype'  => $dtype,
                                                 'NOT'       => [
                                                     'items_id'  => $this->needtobe_transfer[$dtype]
                                                 ]
                                             ]
                                         ];

                                         $result = $DB->request($dcriteria)->current();

                                         if ($result['cpt'] > 0) {
                                             $canbetransfer = false;
                                         }
                                    } else {
                                        $canbetransfer = false;
                                    }

                                    if (!$canbetransfer) {
                                        break;
                                    }
                                }

                               // Yes : transfer
                                if ($canbetransfer) {
                                    $this->transferItem($devicetype, $item_ID, $item_ID);
                                    $newdeviceID = $item_ID;
                                } else {
                                    $device->getFromDB($item_ID);
                               // No : search device
                                    $field = "name";
                                    if (!$DB->fieldExists($devicetable, "name")) {
                                        $field = "designation";
                                    }

                                    $device_iterator = $DB->request([
                                        'SELECT' => 'id',
                                        'FROM'   => $devicetable,
                                        'WHERE'  => [
                                            'entities_id'  => $this->to,
                                            $field         => addslashes($device->fields[$field])
                                        ]
                                    ]);

                                    if (count($device_iterator)) {
                                        $result = $device_iterator->current();
                                        $newdeviceID = $result['id'];
                                        $this->addToAlreadyTransfer($devicetype, $item_ID, $newdeviceID);
                                    }

                               // found : use it
                               // not found : copy contract
                                    if ($newdeviceID < 0) {
                                    // 1 - create new item
                                        unset($device->fields['id']);
                                        $input                = $device->fields;
                                    // Fix for fields with NULL in DB
                                        foreach ($input as $key => $value) {
                                            if ($value == '') {
                                                unset($input[$key]);
                                            }
                                        }
                                        $input['entities_id'] = $this->to;
                                        unset($device->fields);
                                        $newdeviceID = $device->add(Toolbox::addslashes_deep($input));
                                    // 2 - transfer as copy
                                        $this->transferItem($devicetype, $item_ID, $newdeviceID);
                                    }
                                }
                            }

                          // Update links
                            $DB->update(
                                $itemdevicetable,
                                [
                                    $fk         => $newdeviceID,
                                    'items_id'  => $newID
                                ],
                                [
                                    'id' => $data['id']
                                ]
                            );
                            $this->transferItem($itemdevicetype, $data['id'], $data['id']);
                        }
                    }
                }
                break;
        }
    }


    /**
     * Transfer network links
     *
     * @param string $itemtype  The original type of transferred item
     * @param int $ID           Original ID of the item
     * @param int $newID        New ID of the item
     *
     * @return void
     **/
    public function transferNetworkLink($itemtype, $ID, $newID)
    {
        global $DB;
       /// TODO manage with new network system
        $np = new NetworkPort();
        $nn = new NetworkPort_NetworkPort();

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_networkports.*'
            ],
            'FROM'      => 'glpi_networkports',
            'LEFT JOIN' => [
                'glpi_networkportethernets'   => [
                    'ON' => [
                        'glpi_networkportethernets'   => 'networkports_id',
                        'glpi_networkports'           => 'id'
                    ]
                ]
            ],
            'WHERE'     => [
                'glpi_networkports.items_id'  => $ID,
                'glpi_networkports.itemtype'  => $itemtype
            ]
        ]);

        if (count($iterator)) {
            switch ($this->options['keep_networklink']) {
               // Delete netport
                case 0:
                    // Not a copy -> delete
                    if ($ID == $newID) {
                        foreach ($iterator as $data) {
                            $np->delete(['id' => $data['id']], 1);
                        }
                    }
                    // Copy -> do nothing
                    break;

               // Disconnect
                case 1:
                  // Not a copy -> disconnect
                    if ($ID == $newID) {
                        foreach ($iterator as $data) {
                            if ($nn->getFromDBForNetworkPort($data['id'])) {
                                 $nn->delete($data);
                            }

                         //find socket attached to NetworkPortEthernet and transfer it
                            $socket = new Socket();
                            if ($socket->getFromDBByCrit(["networkports_id" => $data['id']])) {
                                if ($socket->getID()) {
                                    $socketID  = $this->transferDropdownSocket($socket->getID());
                                    $input['id']           = $data['id'];
                                    $input['sockets_id'] = $socketID;
                                    $np->update($input);
                                }
                            }
                        }
                    } else { // Copy -> copy netports
                        foreach ($iterator as $data) {
                            $data             = Toolbox::addslashes_deep($data);
                            $socket = new Socket();
                            if ($socket->getFromDBByCrit(["networkports_id" => $data['id']])) {
                                if ($socket->getID()) {
                                    $data['sockets_id'] = $this->transferDropdownSocket($socket->getID());
                                }
                            }
                            unset($data['id']);
                            $data['items_id'] = $newID;
                            unset($np->fields);
                            $np->add(Toolbox::addslashes_deep($data));
                        }
                    }
                    break;

               // Keep network links
                default:
                 // Copy -> Copy sockets (do not keep links)
                    if ($ID != $newID) {
                        foreach ($iterator as $data) {
                            $socket = new Socket();
                            if ($socket->getFromDBByCrit(["networkports_id" => $data['id']])) {
                                if ($socket->getID()) {
                                    $data['sockets_id'] = $this->transferDropdownSocket($socket->getID());
                                }
                            }
                            unset($data['id']);
                            $data['items_id'] = $newID;
                            unset($np->fields);
                            $np->add(Toolbox::addslashes_deep($data));
                        }
                    } else {
                        foreach ($iterator as $data) {
                          // Not a copy -> only update socket
                            if ($data['sockets_id']) {
                                 $socket = new Socket();
                                if ($socket->getFromDBByCrit(["networkports_id" => $data['id']])) {
                                    if ($socket->getID()) {
                                        $socketID = $this->transferDropdownSocket($socket->getID());
                                    }
                                }
                                 $input['id']           = $data['id'];
                                 $input['sockets_id'] = $socketID;
                                 $np->update($input);
                            }
                        }
                    }
            }
        }
    }

    public function showForm($ID, array $options = [])
    {
        $edit_form = true;
        if (strpos($_SERVER['HTTP_REFERER'], "transfer.form.php") === false) {
            $edit_form = false;
        }

        $this->initForm($ID, $options);

        $params = [];
        if (!Session::haveRightsOr("transfer", [CREATE, UPDATE, PURGE])) {
            $params['readonly'] = true;
        }

        if ($edit_form) {
            $this->showFormHeader($options);
        } else {
            echo "<form method='post' name=form action='" . $options['target'] . "'>";
            echo "<div class='center' id='tabsbody' >";
            echo "<table class='tab_cadre_fixe'>";

            echo "<tr><td class='tab_bg_2 top' colspan='4'>";
            echo "<div class='center'>";
            Entity::dropdown(['name' => 'to_entity']);
            echo "&nbsp;<input type='submit' name='transfer' value=\"" . __s('Execute') . "\"
                      class='btn btn-primary'></div>";
            echo "</td></tr>";
        }

        if ($edit_form) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Name') . "</td><td>";
            echo Html::input('name', ['value' => $this->fields['name']]);
            echo "</td>";
            echo "<td rowspan='3' class='middle right'>" . __('Comments') . "</td>";
            echo "<td class='center middle' rowspan='3'>
               <textarea class='form-control' name='comment' >" . $this->fields["comment"] . "</textarea>";
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Last update') . "</td>";
            echo "<td>" . ($this->fields["date_mod"] ? Html::convDateTime($this->fields["date_mod"])
                                                : __('Never'));
            echo "</td></tr>";
        }

        $keep  = [0 => _x('button', 'Delete permanently'),
            1 => __('Preserve')
        ];

        $clean = [0 => __('Preserve'),
            1 => _x('button', 'Put in trashbin'),
            2 => _x('button', 'Delete permanently')
        ];

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Historical') . "</td><td>";
        $params['value'] = $this->fields['keep_history'];
        Dropdown::showFromArray('keep_history', $keep, $params);
        echo "</td>";
        if (!$edit_form) {
            echo "<td colspan='2'>&nbsp;</td>";
        }
        echo "</tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='4' class='center b'>" . _n('Asset', 'Assets', Session::getPluralNumber()) . "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . _n('Network port', 'Network ports', Session::getPluralNumber()) . "</td><td>";
        $options = [0 => _x('button', 'Delete permanently'),
            1 => _x('button', 'Disconnect') ,
            2 => __('Keep')
        ];
        $params['value'] = $this->fields['keep_networklink'];
        Dropdown::showFromArray('keep_networklink', $options, $params);
        echo "</td>";
        echo "<td>" . _n('Ticket', 'Tickets', Session::getPluralNumber()) . "</td><td>";
        $options = [0 => _x('button', 'Delete permanently'),
            1 => _x('button', 'Disconnect') ,
            2 => __('Keep')
        ];
        $params['value'] = $this->fields['keep_ticket'];
        Dropdown::showFromArray('keep_ticket', $options, $params);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Software of items') . "</td><td>";
        $params['value'] = $this->fields['keep_software'];
        Dropdown::showFromArray('keep_software', $keep, $params);
        echo "</td>";
        echo "<td>" . __('If software are no longer used') . "</td><td>";
        $params['value'] = $this->fields['clean_software'];
        Dropdown::showFromArray('clean_software', $clean, $params);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . _n('Reservation', 'Reservations', Session::getPluralNumber()) . "</td><td>";
        $params['value'] = $this->fields['keep_reservation'];
        Dropdown::showFromArray('keep_reservation', $keep, $params);
        echo "</td>";
        echo "<td>" . _n('Component', 'Components', Session::getPluralNumber()) . "</td><td>";
        $params['value'] = $this->fields['keep_device'];
        Dropdown::showFromArray('keep_device', $keep, $params);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Links between printers and cartridge types and cartridges');
        echo "</td><td>";
        $params['value'] = $this->fields['keep_cartridgeitem'];
        Dropdown::showFromArray('keep_cartridgeitem', $keep, $params);
        echo "</td>";
        echo "<td>" . __('If the cartridge types are no longer used') . "</td><td>";
        $params['value'] = $this->fields['clean_cartridgeitem'];
        Dropdown::showFromArray('clean_cartridgeitem', $clean, $params);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Links between cartridge types and cartridges') . "</td><td>";
        $params['value'] = $this->fields['keep_cartridge'];
        Dropdown::showFromArray('keep_cartridge', $keep, $params);
        echo "</td>";
        echo "<td>" . __('Financial and administrative information') . "</td><td>";
        $params['value'] = $this->fields['keep_infocom'];
        Dropdown::showFromArray('keep_infocom', $keep, $params);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Links between consumable types and consumables') . "</td><td>";
        $params['value'] = $this->fields['keep_consumable'];
        Dropdown::showFromArray('keep_consumable', $keep, $params);
        echo "</td>";
        echo "<td>" . __('Links between computers and volumes') . "</td><td>";
        $params['value'] = $this->fields['keep_disk'];
        Dropdown::showFromArray('keep_disk', $keep, $params);
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='4' class='center b'>" . __('Direct connections') . "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . _n('Monitor', 'Monitors', Session::getPluralNumber()) . "</td><td>";
        $params['value'] = $this->fields['keep_dc_monitor'];
        Dropdown::showFromArray('keep_dc_monitor', $keep, $params);
        echo "</td>";
        echo "<td>" . __('If monitors are no longer used') . "</td><td>";
        $params['value'] = $this->fields['clean_dc_monitor'];
        Dropdown::showFromArray('clean_dc_monitor', $clean, $params);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . _n('Printer', 'Printers', Session::getPluralNumber()) . "</td><td>";
        $params['value'] = $this->fields['keep_dc_printer'];
        Dropdown::showFromArray('keep_dc_printer', $keep, $params);
        echo "</td>";
        echo "<td>" . __('If printers are no longer used') . "</td><td>";
        $params['value'] = $this->fields['clean_dc_printer'];
        Dropdown::showFromArray('clean_dc_printer', $clean, $params);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . Peripheral::getTypeName(Session::getPluralNumber()) . "</td><td>";
        $params['value'] = $this->fields['keep_dc_peripheral'];
        Dropdown::showFromArray('keep_dc_peripheral', $keep, $params);
        echo "</td>";
        echo "<td>" . __('If devices are no longer used') . "</td><td>";
        $params['value'] = $this->fields['clean_dc_peripheral'];
        Dropdown::showFromArray('clean_dc_peripheral', $clean, $params);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . _n('Phone', 'Phones', Session::getPluralNumber()) . "</td><td>";
        $params['value'] = $this->fields['keep_dc_phone'];
        Dropdown::showFromArray('keep_dc_phone', $keep, $params);
        echo "</td>";
        echo "<td>" . __('If phones are no longer used') . "</td><td>";
        $params['value'] = $this->fields['clean_dc_phone'];
        Dropdown::showFromArray('clean_dc_phone', $clean, $params);
        echo "</td></tr>";

        echo "<tr class='tab_bg_2'>";
        echo "<td colspan='4' class='center b'>" . __('Management') . "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . _n('Supplier', 'Suppliers', Session::getPluralNumber()) . "</td><td>";
        $params['value'] = $this->fields['keep_supplier'];
        Dropdown::showFromArray('keep_supplier', $keep, $params);
        echo "</td>";
        echo "<td>" . __('If suppliers are no longer used') . "</td><td>";
        $params['value'] = $this->fields['clean_supplier'];
        Dropdown::showFromArray('clean_supplier', $clean, $params);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Links between suppliers and contacts') . "&nbsp;:</td><td>";
        $params['value'] = $this->fields['keep_contact'];
        Dropdown::showFromArray('keep_contact', $keep, $params);
        echo "</td>";
        echo "<td>" . __('If contacts are no longer used') . "</td><td>";
        $params['value'] = $this->fields['clean_contact'];
        Dropdown::showFromArray('clean_contact', $clean, $params);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . Document::getTypeName(Session::getPluralNumber()) . "</td><td>";
        $params['value'] = $this->fields['keep_document'];
        Dropdown::showFromArray('keep_document', $keep, $params);
        echo "</td>";
        echo "<td>" . __('If documents are no longer used') . "</td><td>";
        $params['value'] = $this->fields['clean_document'];
        Dropdown::showFromArray('clean_document', $clean, $params);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . _n('Contract', 'Contracts', Session::getPluralNumber()) . "</td><td>";
        $params['value'] = $this->fields['keep_contract'];
        Dropdown::showFromArray('keep_contract', $keep, $params);
        echo "</td>";
        echo "<td>" . __('If contracts are no longer used') . "</td><td>";
        $params['value'] = $this->fields['clean_contract'];
        Dropdown::showFromArray('clean_contract', $clean, $params);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . _n('Certificate', 'Certificates', Session::getPluralNumber()) . "</td><td>";
        $params['value'] = $this->fields['keep_certificate'];
        Dropdown::showFromArray('keep_certificate', $keep, $params);
        echo "</td>";
        echo "<td>" . __('If certificates are no longer used') . "</td><td>";
        $params['value'] = $this->fields['clean_certificate'];
        Dropdown::showFromArray('clean_certificate', $clean, $params);
        echo "</td></tr>";

        if ($edit_form) {
            $this->showFormButtons($options);
        } else {
            echo "</table></div>";
            Html::closeForm();
        }
        return true;
    }


    /**
     * Display items to transfer
     * @return void
     */
    public function showTransferList()
    {
        global $DB, $CFG_GLPI;

        if (isset($_SESSION['glpitransfer_list']) && count($_SESSION['glpitransfer_list'])) {
            echo "<div class='center b'>" .
                __('You can continue to add elements to be transferred or execute the transfer now');
            echo "<br>" . __('Think of making a backup before transferring items.') . "</div>";
            echo "<table class='tab_cadre_fixe' >";
            echo '<tr><th>' . __('Items to transfer') . '</th><th>' . __('Transfer mode') . "&nbsp;";
            $rand = Transfer::dropdown(['name'     => 'id',
                'comments' => false,
                'toupdate' => ['value_fieldname'
                                                                           => 'id',
                    'to_update'  => "transfer_form",
                    'url'        => $CFG_GLPI["root_doc"] .
                                                                              "/ajax/transfers.php"
                ]
            ]);
            echo '</th></tr>';

            echo "<tr><td class='tab_bg_1 top'>";
            foreach ($_SESSION['glpitransfer_list'] as $itemtype => $tab) {
                if (count($tab)) {
                    if (!($item = getItemForItemtype($itemtype))) {
                        continue;
                    }
                    $table = getTableForItemType($itemtype);

                    $iterator = $DB->request([
                        'SELECT'    => [
                            "$table.id",
                            "$table.name",
                            'entities.completename AS locname',
                            'entities.id AS entID'
                        ],
                        'FROM'      => $table,
                        'LEFT JOIN' => [
                            'glpi_entities AS entities'   => [
                                'ON' => [
                                    'entities' => 'id',
                                    $table     => 'entities_id'
                                ]
                            ]
                        ],
                        'WHERE'     => ["$table.id" => $tab],
                        'ORDERBY'   => ['locname', "$table.name"]
                    ]);
                    $entID = -1;

                    if (count($iterator)) {
                            echo '<h3>' . $item->getTypeName() . '</h3>';
                        foreach ($iterator as $data) {
                            if ($entID != $data['entID']) {
                                if ($entID != -1) {
                                    echo '<br>';
                                }
                                $entID = $data['entID'];
                                echo "<span class='b spaced'>" . $data['locname'] . "</span><br>";
                            }
                                echo ($data['name'] ? $data['name'] : "(" . $data['id'] . ")") . "<br>";
                        }
                    }
                }
            }
            echo "</td><td class='tab_bg_2 top'>";

            if (countElementsInTable('glpi_transfers') == 0) {
                echo __('No item found');
            } else {
                $params = ['id' => '__VALUE__'];
                Ajax::updateItemOnSelectEvent(
                    "dropdown_id$rand",
                    "transfer_form",
                    $CFG_GLPI["root_doc"] . "/ajax/transfers.php",
                    $params
                );
            }

            echo "<div class='center' id='transfer_form'><br>";
            Html::showSimpleForm(
                $CFG_GLPI["root_doc"] . "/front/transfer.action.php",
                'clear',
                __('To empty the list of elements to be transferred')
            );
            echo "</div>";
            echo '</td></tr>';
            echo '</table>';
        } else {
            echo __('No selected element or badly defined operation');
        }
    }

    public static function getIcon()
    {
        return "fas fa-level-up-alt";
    }
}
