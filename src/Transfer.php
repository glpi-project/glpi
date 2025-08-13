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
use Glpi\Asset\Asset_PeripheralAsset;
use Glpi\Asset\AssetDefinitionManager;
use Glpi\Error\ErrorHandler;
use Glpi\Plugin\Hooks;
use Glpi\Socket;
use Glpi\Toolbox\URL;

/**
 * Transfer engine.
 * This class is used to move data between entities.
 */
final class Transfer extends CommonDBTM
{
    /**
     * Array of items that have already been transferred
     * @var array
     */
    public array $already_transfer      = [];

    /**
     * Items simulate to move - non-recursive item or recursive item not visible in destination entity
     * @var array<class-string<CommonDBTM>, int[]>
     */
    public array $needtobe_transfer     = [];

    /**
     * Items simulate to move - recursive item visible in destination entity
     * @var array<class-string<CommonDBTM>, int[]>
     */
    public array $noneedtobe_transfer   = [];

    /**
     * Options used to transfer
     * @var array
     */
    public array $options               = [];

    /**
     * Destination entity id
     * @var int
     */
    public int $to                    = -1;

    private ?array $to_entity_ancestors = null;

    public static $rightname = 'transfer';

    public static function getTypeName($nb = 0)
    {
        return __('Transfer');
    }

    public static function getSectorizedDetails(): array
    {
        return ['admin', Rule::class, self::class];
    }

    public static function getLogDefaultServiceName(): string
    {
        return 'setup';
    }

    public function getFormOptionsFromUrl(array $query_params): array
    {
        return [
            // Required for pagination
            'target' => self::getFormURL(),
        ];
    }

    public function maxActionsCount()
    {
        return 0;
    }

    public function rawSearchOptions()
    {
        $tab = [];

        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => '1',
            'table'              => self::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '19',
            'table'              => self::getTable(),
            'field'              => 'date_mod',
            'name'               => __('Last update'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '121',
            'table'              => self::getTable(),
            'field'              => 'date_creation',
            'name'               => __('Creation date'),
            'datatype'           => 'datetime',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '16',
            'table'              => self::getTable(),
            'field'              => 'comment',
            'name'               => _n('Comment', 'Comments', Session::getPluralNumber()),
            'datatype'           => 'text',
        ];

        return $tab;
    }

    /**
     * Transfer items
     *
     * Associated items will be evaluated based on the passed options and transferred/copied as well if required.
     * This will disable notifications for the rest of the request execution.
     *
     * @param array $items    Array of items to transfer in the format [itemtype => [ids]]
     * @param int $to         entity destination ID
     * @param array $options  options used to transfer
     *
     * @return void
     **/
    public function moveItems(array $items, int $to, array $options): void
    {
        global $DB;

        // unset notifications
        NotificationSetting::disableAll();

        $this->options = array_replace([
            'keep_ticket'         => 0,
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
            'clean_certificate'   => 0,

            'lock_updated_fields' => 0,
            'keep_location'       => 1,
        ], $options);

        if ($to < 0) {
            return;
        }

        // Store to
        $this->to = $to;

        try {
            $DB->beginTransaction();

            // Simulate transfers To know which items need to be transfer
            $this->simulateTransfer($items);

            $INVENTORY_TYPES = $this->getItemtypes();

            foreach ($INVENTORY_TYPES as $itemtype) {
                if (isset($items[$itemtype]) && count($items[$itemtype])) {
                    foreach ($items[$itemtype] as $ID) {
                        $this->transferItem($itemtype, $ID, $ID);
                    }
                }
            }

            // handle all other types
            foreach (array_keys($items) as $itemtype) {
                if (!in_array($itemtype, $INVENTORY_TYPES, true)) {
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
            $this->cleanSoftwares();
            $DB->commit();
        } catch (Throwable $e) {
            $DB->rollBack();
            ErrorHandler::logCaughtException($e);
            ErrorHandler::displayCaughtExceptionMessage($e);
        }
    }

    /**
     * Add an item in the needtobe_transfer list.
     * Will remove it from noneedtobe_transfer list if it's already in it
     *
     * @param class-string<CommonDBTM> $itemtype Itemtype of the item
     * @param int $ID ID of the item
     *
     * @return void
     **/
    private function addToBeTransfer(string $itemtype, int $ID): void
    {
        unset($this->noneedtobe_transfer[$itemtype][$ID]);
        $this->needtobe_transfer[$itemtype][$ID] = $ID;
    }

    /**
     * Add an item in the noneedtobe_transfer list but only if it's not already in needtobe_transfer
     *
     * @param class-string<CommonDBTM> $itemtype Itemtype of the item
     * @param int $ID ID of the item
     *
     * @return void
     **/
    private function addNotToBeTransfer(string $itemtype, int $ID): void
    {
        // Can't be in both list (in fact, always true)
        if (!isset($this->needtobe_transfer[$itemtype][$ID])) {
            $this->noneedtobe_transfer[$itemtype][$ID] = $ID;
        }
    }

    private function getDestinationEntityAncestors(): array
    {
        if ($this->to_entity_ancestors === null) {
            $this->to_entity_ancestors = getAncestorsOf("glpi_entities", $this->to);
        }
        return $this->to_entity_ancestors;
    }

    private function haveItemsToTransfer(string $itemtype): bool
    {
        return isset($this->needtobe_transfer[$itemtype]) && !empty($this->needtobe_transfer[$itemtype]);
    }

    /**
     * Determines if an item needs to be transferred and adds it to the appropriate list based on the items current entity and recursive status.
     *
     * If the entity ID is specified as a parameter, the item will not be loaded. If class loadability and item existance checks are needed, the entity ID should not be specified.
     * @param class-string<CommonDBTM> $itemtype
     * @param int $id The ID of the item
     * @param int|null $entities_id If specified, the entity of the item used without loading the item
     * @param bool|null $is_recursive If specified, the recursive status of the item used without loading the item.
     * @return void
     * @see Transfer::addToBeTransfer()
     * @see Transfer::addNotToBeTransfer()
     */
    private function evaluateTransfer(string $itemtype, int $id, ?int $entities_id = null, ?bool $is_recursive = null): void
    {
        if ($entities_id === null) {
            if (!($item = getItemForItemtype($itemtype)) || !($item->getFromDB($id) && $item->isEntityAssign())) {
                // itemtype not loadable, item missing or not able to be assigned to entit, so don't transfer
                $this->addNotToBeTransfer($itemtype, $id);
                return;
            }
            $entities_id = $item->getEntityID();
            $is_recursive = (bool) $item->isRecursive();
        }
        $is_recursive ??= false;
        if (
            $is_recursive
            && in_array($entities_id, $this->getDestinationEntityAncestors(), true)
        ) {
            $this->addNotToBeTransfer($itemtype, $id);
        } else {
            $this->addToBeTransfer($itemtype, $id);
        }
    }

    private function simulateDirectConnections(): void
    {
        global $DB;

        $DC_CONNECT = [];
        // TODO base on directconnect_types dynamically
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

        if ($DC_CONNECT === []) {
            return;
        }

        foreach (Asset_PeripheralAsset::getPeripheralHostItemtypes() as $asset_itemtype) {
            if ($this->haveItemsToTransfer($asset_itemtype)) {
                foreach ($DC_CONNECT as $peripheral_itemtype) {
                    $peripheral_itemtable = getTableForItemType($peripheral_itemtype);
                    $relation_table = Asset_PeripheralAsset::getTable();

                    // Clean DB / Search unexisting links and force disconnect
                    $DB->delete(
                        $relation_table,
                        [
                            $peripheral_itemtable . '.id' => null,
                            $relation_table . '.itemtype_asset'      => $asset_itemtype,
                            $relation_table . '.itemtype_peripheral' => $peripheral_itemtype,
                        ],
                        [
                            'LEFT JOIN' => [
                                $peripheral_itemtable => [
                                    'ON' => [
                                        $relation_table       => 'items_id_peripheral',
                                        $peripheral_itemtable => 'id',
                                    ],
                                ],
                            ],
                        ]
                    );

                    if (!($peripheral = getItemForItemtype($peripheral_itemtype))) {
                        continue;
                    }
                    if (!$this->haveItemsToTransfer($asset_itemtype)) {
                        continue;
                    }

                    $iterator = $DB->request([
                        'SELECT'          => ['items_id_peripheral'],
                        'DISTINCT'        => true,
                        'FROM'            => $relation_table,
                        'WHERE'           => [
                            'itemtype_peripheral' => $peripheral_itemtype,
                            'itemtype_asset'      => $asset_itemtype,
                            'items_id_asset'      => $this->needtobe_transfer[$asset_itemtype],
                        ],
                    ]);

                    foreach ($iterator as $data) {
                        $this->evaluateTransfer($peripheral_itemtype, $data['items_id_peripheral']);
                    }
                }
            }
        }
    }

    private function simulateSoftware(): void
    {
        global $CFG_GLPI, $DB;

        if (!$this->options['keep_software']) {
            return;
        }
        // Clean DB
        $DB->delete('glpi_items_softwareversions', ['glpi_softwareversions.id'  => null], [
            'LEFT JOIN' => [
                'glpi_softwareversions'  => [
                    'ON' => [
                        'glpi_items_softwareversions' => 'softwareversions_id',
                        'glpi_softwareversions'       => 'id',
                    ],
                ],
            ],
        ]);

        // Clean DB
        $DB->delete('glpi_softwareversions', ['glpi_softwares.id'  => null], [
            'LEFT JOIN' => [
                'glpi_softwares'  => [
                    'ON' => [
                        'glpi_softwareversions' => 'softwares_id',
                        'glpi_softwares'        => 'id',
                    ],
                ],
            ],
        ]);
        foreach ($CFG_GLPI['software_types'] as $itemtype) {
            $itemtable = getTableForItemType($itemtype);
            // Clean DB
            $DB->delete('glpi_items_softwareversions', [
                "{$itemtable}.id"  => null,
                'glpi_items_softwareversions.itemtype' => $itemtype,
            ], [
                'LEFT JOIN' => [
                    $itemtable  => [
                        'ON' => [
                            'glpi_items_softwareversions' => 'items_id',
                            $itemtable                    => 'id',
                        ],
                    ],
                ],
            ]);

            if ($this->haveItemsToTransfer($itemtype)) {
                $iterator = $DB->request([
                    'SELECT'       => [
                        'glpi_softwares.id',
                        'glpi_softwares.entities_id',
                        'glpi_softwares.is_recursive',
                        'glpi_softwareversions.id AS vID',
                    ],
                    'FROM'         => 'glpi_items_softwareversions',
                    'INNER JOIN'   => [
                        'glpi_softwareversions' => [
                            'ON' => [
                                'glpi_items_softwareversions' => 'softwareversions_id',
                                'glpi_softwareversions'       => 'id',
                            ],
                        ],
                        'glpi_softwares'        => [
                            'ON' => [
                                'glpi_softwareversions' => 'softwares_id',
                                'glpi_softwares'        => 'id',
                            ],
                        ],
                    ],
                    'WHERE'        => [
                        'glpi_items_softwareversions.items_id' => $this->needtobe_transfer[$itemtype],
                        'glpi_items_softwareversions.itemtype' => $itemtype,
                    ],
                ]);

                foreach ($iterator as $data) {
                    $this->evaluateTransfer(SoftwareVersion::class, $data['vID'], $data['entities_id'], $data['is_recursive']);
                }
            }
        }
    }

    private function simulateSoftwareLicenses(): void
    {
        global $DB;
        if ($this->haveItemsToTransfer(Software::class)) {
            // Move license of software
            // TODO : should we transfer "affected license" ?
            $iterator = $DB->request([
                'SELECT' => ['id', 'softwareversions_id_buy', 'softwareversions_id_use'],
                'FROM'   => 'glpi_softwarelicenses',
                'WHERE'  => ['softwares_id' => $this->needtobe_transfer['Software']],
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
    }

    private function simulateDevices(): void
    {
        global $DB;

        if (!$this->options['keep_device']) {
            return;
        }
        foreach (Item_Devices::getConcernedItems() as $itemtype) {
            if (!$this->haveItemsToTransfer($itemtype)) {
                continue;
            }
            foreach (Item_Devices::getItemAffinities($itemtype) as $itemdevicetype) {
                $itemdevicetable = getTableForItemType($itemdevicetype);
                $devicetype      = $itemdevicetype::getDeviceType();
                $devicetable     = getTableForItemType($devicetype);
                $fk              = getForeignKeyFieldForTable($devicetable);
                $iterator = $DB->request([
                    'SELECT'          => [
                        "$itemdevicetable.$fk",
                        "$devicetable.entities_id",
                        "$devicetable.is_recursive",
                    ],
                    'DISTINCT'        => true,
                    'FROM'            => $itemdevicetable,
                    'LEFT JOIN'       => [
                        $devicetable   => [
                            'ON' => [
                                $itemdevicetable  => $fk,
                                $devicetable      => 'id',
                            ],
                        ],
                    ],
                    'WHERE'           => [
                        "$itemdevicetable.itemtype"   => $itemtype,
                        "$itemdevicetable.items_id"   => $this->needtobe_transfer[$itemtype],
                    ],
                ]);

                foreach ($iterator as $data) {
                    if (
                        $data['is_recursive']
                        && in_array($data['entities_id'], $this->getDestinationEntityAncestors(), true)
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
                                    'items_id'  => $this->needtobe_transfer[$itemtype],
                                ],
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

    private function simulateTickets(): void
    {
        global $CFG_GLPI, $DB;

        if (!$this->options['keep_ticket']) {
            return;
        }
        foreach ($CFG_GLPI["ticket_types"] as $itemtype) {
            if (!$this->haveItemsToTransfer($itemtype)) {
                continue;
            }
            $iterator = $DB->request([
                'SELECT'    => 'glpi_tickets.id',
                'FROM'      => 'glpi_tickets',
                'LEFT JOIN' => [
                    'glpi_items_tickets' => [
                        'ON' => [
                            'glpi_items_tickets' => 'tickets_id',
                            'glpi_tickets'       => 'id',
                        ],
                    ],
                ],
                'WHERE'     => [
                    'itemtype'  => $itemtype,
                    'items_id'  => $this->needtobe_transfer[$itemtype],
                ],
            ]);

            foreach ($iterator as $data) {
                $this->addToBeTransfer('Ticket', $data['id']);
            }
        }
    }

    private function simulateCertificates(): void
    {
        global $CFG_GLPI, $DB;

        if (!$this->options['keep_certificate']) {
            return;
        }
        foreach ($CFG_GLPI["certificate_types"] as $itemtype) {
            if (!$this->haveItemsToTransfer($itemtype)) {
                continue;
            }
            $itemtable = getTableForItemType($itemtype);

            // Clean DB
            $DB->delete(
                'glpi_certificates_items',
                [
                    "$itemtable.id"                 => null,
                    "glpi_certificates_items.itemtype" => $itemtype,
                ],
                [
                    'LEFT JOIN' => [
                        $itemtable  => [
                            'ON' => [
                                'glpi_certificates_items'  => 'items_id',
                                $itemtable              => 'id',
                            ],
                        ],
                    ],
                ]
            );

            // Clean DB
            $DB->delete(
                'glpi_certificates_items',
                [
                    'glpi_certificates.id'  => null,
                ],
                [
                    'LEFT JOIN' => [
                        'glpi_certificates'  => [
                            'ON' => [
                                'glpi_certificates_items'  => 'certificates_id',
                                'glpi_certificates'        => 'id',
                            ],
                        ],
                    ],
                ]
            );

            $iterator = $DB->request([
                'SELECT'    => [
                    'certificates_id',
                    'glpi_certificates.entities_id',
                    'glpi_certificates.is_recursive',
                ],
                'FROM'      => 'glpi_certificates_items',
                'LEFT JOIN' => [
                    'glpi_certificates' => [
                        'ON' => [
                            'glpi_certificates_items'  => 'certificates_id',
                            'glpi_certificates'        => 'id',
                        ],
                    ],
                ],
                'WHERE'     => [
                    'itemtype'  => $itemtype,
                    'items_id'  => $this->needtobe_transfer[$itemtype],
                ],
            ]);

            foreach ($iterator as $data) {
                $this->evaluateTransfer(Certificate::class, $data['certificates_id'], $data['entities_id'], $data['is_recursive']);
            }
        }
    }

    private function simulateContracts(): void
    {
        global $CFG_GLPI, $DB;
        if (!$this->options['keep_contract']) {
            return;
        }
        foreach ($CFG_GLPI["contract_types"] as $itemtype) {
            if (!$this->haveItemsToTransfer($itemtype)) {
                continue;
            }
            $itemtable = getTableForItemType($itemtype);

            // Clean DB
            $DB->delete(
                'glpi_contracts_items',
                [
                    "$itemtable.id"                 => null,
                    "glpi_contracts_items.itemtype" => $itemtype,
                ],
                [
                    'LEFT JOIN' => [
                        $itemtable  => [
                            'ON' => [
                                'glpi_contracts_items'  => 'items_id',
                                $itemtable              => 'id',
                            ],
                        ],
                    ],
                ]
            );

            // Clean DB
            $DB->delete('glpi_contracts_items', ['glpi_contracts.id'  => null], [
                'LEFT JOIN' => [
                    'glpi_contracts'  => [
                        'ON' => [
                            'glpi_contracts_items'  => 'contracts_id',
                            'glpi_contracts'        => 'id',
                        ],
                    ],
                ],
            ]);

            $iterator = $DB->request([
                'SELECT'    => [
                    'contracts_id',
                    'glpi_contracts.entities_id',
                    'glpi_contracts.is_recursive',
                ],
                'FROM'      => 'glpi_contracts_items',
                'LEFT JOIN' => [
                    'glpi_contracts' => [
                        'ON' => [
                            'glpi_contracts_items'  => 'contracts_id',
                            'glpi_contracts'        => 'id',
                        ],
                    ],
                ],
                'WHERE'     => [
                    'itemtype'  => $itemtype,
                    'items_id'  => $this->needtobe_transfer[$itemtype],
                ],
            ]);

            foreach ($iterator as $data) {
                $this->evaluateTransfer(Contract::class, $data['contracts_id'], $data['entities_id'], $data['is_recursive']);
            }
        }
    }

    private function simulateSuppliers(): void
    {
        global $DB;

        if (!$this->options['keep_supplier']) {
            return;
        }

        // Clean DB
        $DB->delete('glpi_contracts_suppliers', ['glpi_contracts.id'  => null], [
            'LEFT JOIN' => [
                'glpi_contracts'  => [
                    'ON' => [
                        'glpi_contracts_suppliers' => 'contracts_id',
                        'glpi_contracts'           => 'id',
                    ],
                ],
            ],
        ]);

        // Clean DB
        $DB->delete('glpi_contracts_suppliers', ['glpi_suppliers.id'  => null], [
            'LEFT JOIN' => [
                'glpi_suppliers'  => [
                    'ON' => [
                        'glpi_contracts_suppliers' => 'suppliers_id',
                        'glpi_suppliers'           => 'id',
                    ],
                ],
            ],
        ]);

        if ($this->haveItemsToTransfer(Contract::class)) {
            // Supplier Contract
            $iterator = $DB->request([
                'SELECT'    => [
                    'suppliers_id',
                    'glpi_suppliers.entities_id',
                    'glpi_suppliers.is_recursive',
                ],
                'FROM'      => 'glpi_contracts_suppliers',
                'LEFT JOIN' => [
                    'glpi_suppliers' => [
                        'ON' => [
                            'glpi_contracts_suppliers' => 'suppliers_id',
                            'glpi_suppliers'           => 'id',
                        ],
                    ],
                ],
                'WHERE'     => [
                    'contracts_id' => $this->needtobe_transfer[Contract::class],
                ],
            ]);

            foreach ($iterator as $data) {
                $this->evaluateTransfer(Supplier::class, $data['suppliers_id'], $data['entities_id'], $data['is_recursive']);
            }
        }

        /** @var array<class-string<CommonDBTM>, class-string<CommonITILActor>> $itil_with_suppliers */
        $itil_with_suppliers = [
            Ticket::class => Supplier_Ticket::class,
            Problem::class => Problem_Supplier::class,
            Change::class => Change_Supplier::class,
        ];
        foreach ($itil_with_suppliers as $itil_class => $itil_supplier_class) {
            if (!$this->haveItemsToTransfer($itil_class)) {
                continue;
            }
            $itil_table = $itil_class::getTable();
            $link_table = $itil_supplier_class::getTable();
            $iterator = $DB->request([
                'SELECT' => [
                    "$link_table.suppliers_id",
                    'glpi_suppliers.entities_id',
                    'glpi_suppliers.is_recursive',
                ],
                'FROM' => $itil_table,
                'LEFT JOIN' => [
                    $link_table => [
                        'ON' => [
                            $link_table => $itil_class::getForeignKeyField(),
                            $itil_table => 'id',
                        ],
                    ],
                    'glpi_suppliers' => [
                        'ON' => [
                            $link_table => 'suppliers_id',
                            'glpi_suppliers' => 'id',
                        ],
                    ],
                ],
                'WHERE'     => [
                    "$link_table.suppliers_id" => ['>', 0],
                    "$itil_table.id" => $this->needtobe_transfer[$itil_class],
                ],
            ]);

            foreach ($iterator as $data) {
                $this->evaluateTransfer(Supplier::class, $data['suppliers_id'], $data['entities_id'], $data['is_recursive']);
            }
        }

        // Supplier infocoms
        if ($this->options['keep_infocom']) {
            foreach (Infocom::getItemtypesThatCanHave() as $itemtype) {
                if (!$this->haveItemsToTransfer($itemtype)) {
                    continue;
                }
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
                                ],
                            ],
                        ],
                    ]
                );

                $iterator = $DB->request([
                    'SELECT'    => [
                        'suppliers_id',
                        'glpi_suppliers.entities_id',
                        'glpi_suppliers.is_recursive',
                    ],
                    'FROM'      => 'glpi_infocoms',
                    'LEFT JOIN' => [
                        'glpi_suppliers'  => [
                            'ON' => [
                                'glpi_infocoms'   => 'suppliers_id',
                                'glpi_suppliers'  => 'id',
                            ],
                        ],
                    ],
                    'WHERE'     => [
                        'suppliers_id' => ['>', 0],
                        'itemtype'     => $itemtype,
                        'items_id'     => $this->needtobe_transfer[$itemtype],
                    ],
                ]);

                foreach ($iterator as $data) {
                    $this->evaluateTransfer(Supplier::class, $data['suppliers_id'], $data['entities_id'], $data['is_recursive']);
                }
            }
        }
    }

    private function simulateContacts(): void
    {
        global $DB;
        if (!$this->options['keep_contact']) {
            return;
        }

        // Clean DB
        $DB->delete('glpi_contacts_suppliers', ['glpi_contacts.id'  => null], [
            'LEFT JOIN' => [
                'glpi_contacts' => [
                    'ON' => [
                        'glpi_contacts_suppliers'  => 'contacts_id',
                        'glpi_contacts'            => 'id',
                    ],
                ],
            ],
        ]);

        // Clean DB
        $DB->delete('glpi_contacts_suppliers', ['glpi_suppliers.id'  => null], [
            'LEFT JOIN' => [
                'glpi_suppliers' => [
                    'ON' => [
                        'glpi_contacts_suppliers'  => 'suppliers_id',
                        'glpi_suppliers'           => 'id',
                    ],
                ],
            ],
        ]);

        if ($this->haveItemsToTransfer(Supplier::class)) {
            // Supplier Contact
            $iterator = $DB->request([
                'SELECT'    => [
                    'contacts_id',
                    'glpi_contacts.entities_id',
                    'glpi_contacts.is_recursive',
                ],
                'FROM'      => 'glpi_contacts_suppliers',
                'LEFT JOIN' => [
                    'glpi_contacts'  => [
                        'ON' => [
                            'glpi_contacts_suppliers'  => 'contacts_id',
                            'glpi_contacts'            => 'id',
                        ],
                    ],
                ],
                'WHERE'     => [
                    'suppliers_id' => $this->needtobe_transfer[Supplier::class],
                ],
            ]);

            foreach ($iterator as $data) {
                $this->evaluateTransfer(Contact::class, $data['contacts_id'], $data['entities_id'], $data['is_recursive']);
            }
        }
    }

    private function simulateDocuments(): void
    {
        global $DB;
        if (!$this->options['keep_document']) {
            return;
        }
        foreach (Document::getItemtypesThatCanHave() as $itemtype) {
            if (!$this->haveItemsToTransfer($itemtype)) {
                continue;
            }
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
                            ],
                        ],
                    ],
                ]
            );

            $iterator = $DB->request([
                'SELECT'    => [
                    'documents_id',
                    'glpi_documents.entities_id',
                    'glpi_documents.is_recursive',
                ],
                'FROM'      => 'glpi_documents_items',
                'LEFT JOIN' => [
                    'glpi_documents'  => [
                        'ON' => [
                            'glpi_documents_items'  => 'documents_id',
                            'glpi_documents'        => 'id', [
                                'AND' => [
                                    'itemtype' => $itemtype,
                                ],
                            ],
                        ],
                    ],
                ],
                'WHERE'     => [
                    'items_id' => $this->needtobe_transfer[$itemtype],
                ],
            ]);

            foreach ($iterator as $data) {
                $this->evaluateTransfer(Document::class, $data['documents_id'], $data['entities_id'], $data['is_recursive']);
            }
        }
    }

    private function simulateCartridges(): void
    {
        global $DB;

        if (!$this->options['keep_cartridgeitem'] || !$this->haveItemsToTransfer(Printer::class)) {
            return;
        }
        $iterator = $DB->request([
            'SELECT' => 'cartridgeitems_id',
            'FROM'   => 'glpi_cartridges',
            'WHERE'  => ['printers_id' => $this->needtobe_transfer[Printer::class]],
        ]);

        foreach ($iterator as $data) {
            $this->addToBeTransfer('CartridgeItem', $data['cartridgeitems_id']);
        }
    }

    /**
     * Simulate the transfer to know which items need to be transfer.
     * This method will reset the needtobe_transfer and noneedtobe_transfer arrays.
     *
     * @param array<string, int[]> $items Array of items to transfer in the format [itemtype => [ids]]
     *
     * @return void
     **/
    private function simulateTransfer(array $items): void
    {
        global $CFG_GLPI;

        // Init types :
        $types = $this->getItemtypes();

        $types = array_merge($types, $CFG_GLPI['device_types']);
        $types = array_merge($types, Item_Devices::getDeviceTypes());

        $this->needtobe_transfer = array_fill_keys($types, []);
        $this->noneedtobe_transfer = array_fill_keys($types, []);
        $this->already_transfer = [];

        // Copy items to needtobe_transfer
        foreach ($items as $key => $tab) {
            foreach ($tab as $ID) {
                $this->addToBeTransfer($key, $ID);
            }
        }

        $this->simulateDirectConnections();
        $this->simulateSoftware();
        $this->simulateSoftwareLicenses();
        $this->simulateDevices();
        $this->simulateTickets();
        $this->simulateCertificates();
        $this->simulateContracts();
        $this->simulateSuppliers();
        $this->simulateContacts();
        $this->simulateDocuments();
        $this->simulateCartridges();
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
    private function transferItem($itemtype, $ID, $newID)
    {
        global $CFG_GLPI;

        if (!($item = getItemForItemtype($itemtype))) {
            return;
        }
        // Is already transferred or item doesn't exist
        if (isset($this->already_transfer[$itemtype][$ID]) || !$item->getFromDB($newID)) {
            return;
        }

        // Network connection ? keep connected / keep_disconnected / delete
        if (in_array($itemtype, $CFG_GLPI['networkport_types'], true)) {
            $this->transferNetworkLink($itemtype, $ID, $newID);
        }

        // Device : keep / delete : network case : delete if net connection delete in import case
        if (in_array($itemtype, Item_Devices::getConcernedItems(), true)) {
            $this->transferDevices($itemtype, $ID, $newID);
        }

        // Reservation : keep / delete
        if (in_array($itemtype, $CFG_GLPI["reservation_types"], true)) {
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

        if ($itemtype === Software::class) {
            $this->transferSoftwareLicensesAndVersions($ID);
        }

        // Connected item is transferred
        if (in_array($itemtype, $CFG_GLPI["directconnect_types"], true)) {
            $this->managePeripheralMainAsset($itemtype, $ID);
        }

        // Certificate : keep / delete + clean unused / keep unused
        if (in_array($itemtype, $CFG_GLPI["certificate_types"], true)) {
            $this->transferCertificates($itemtype, $ID, $newID);
        }

        // Contract : keep / delete + clean unused / keep unused
        if (in_array($itemtype, $CFG_GLPI["contract_types"], true)) {
            $this->transferContracts($itemtype, $ID, $newID);
        }

        // Contact / Supplier : keep / delete + clean unused / keep unused
        if ($itemtype === Supplier::class) {
            $this->transferSupplierContacts($ID, $newID);
        }

        // Document : keep / delete + clean unused / keep unused
        if (Document::canApplyOn($itemtype)) {
            $this->transferDocuments($itemtype, $ID, $newID);

            if (is_a($itemtype, CommonITILObject::class, true)) {
                // Transfer ITIL childs documents too
                /** @var CommonITILObject $itil_item */
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
        if ($itemtype === CartridgeItem::class) {
            $this->transferCompatiblePrinters($ID, $newID);
        }

        // Cartridges and cartridges items linked to printer
        if ($itemtype === Printer::class) {
            $this->transferPrinterCartridges($ID, $newID);
        }

        // Transfer Item
        $input = [
            'id'                   => $newID,
            'entities_id'          => $this->to,
            '_transfer'            => 1,
            '_lock_updated_fields' => $this->options['lock_updated_fields'],
        ];

        // Manage Location dropdown
        if (isset($item->fields['locations_id']) && $this->options['keep_location']) {
            $input['locations_id'] = $this->transferDropdownLocation($item->fields['locations_id']);
        } else {
            $input['locations_id'] = 0;
        }

        if (in_array($itemtype, ['Ticket', 'Problem', 'Change'])) {
            $input2 = $this->transferHelpdeskAdditionalInformations($item->fields);
            $input  = array_merge($input, $input2);
        }

        $item->update($input);
        $this->addToAlreadyTransfer($itemtype, $ID, $newID);

        // Do it after item transfer for entity checks
        if (in_array($itemtype, ['Ticket', 'Problem', 'Change'])) {
            $this->transferTaskCategory($itemtype, $ID, $newID);
            $this->transferLinkedSuppliers($itemtype, $ID, $newID);
        }

        if (in_array($itemtype, Asset_PeripheralAsset::getPeripheralHostItemtypes(), true)) {
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

        if (in_array($itemtype, $CFG_GLPI['software_types'], true)) {
            // License / Software :  keep / delete + clean unused / keep unused
            $this->transferItemSoftwares($itemtype, $ID);
        }

        Plugin::doHook(Hooks::ITEM_TRANSFER, [
            'type'        => $itemtype,
            'id'          => $ID,
            'newID'       => $newID,
            'entities_id' => $this->to,
        ]);
    }

    /**
     * Add an item to already transfer array
     *
     * @param string $key         Itemtype of the item
     * @param int    $ID          ID of the item
     * @param int    $newID       ID of the new item
     *
     * @return void
     *
     * @FIXME Parameter $key should be class-string<CommonDBTM> (and `$already_transfer` array shape should be specified).
     **/
    private function addToAlreadyTransfer(string $key, int $ID, int $newID): void
    {
        $this->already_transfer[$key][$ID] = $newID;
    }

    /**
     * Transfer location
     *
     * @param int $locID location ID
     *
     * @return int The new location ID. May be 0 if the location is not transfered.
     **/
    private function transferDropdownLocation(int $locID): int
    {
        if ($locID > 0) {
            if (isset($this->already_transfer['locations_id'][$locID])) {
                return $this->already_transfer['locations_id'][$locID];
            }
            // else  // Not already transfer
            // Search init item
            $location = new Location();
            if ($location->getFromDB($locID)) {
                $data = $location->fields;

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
     * @return int The new socket ID. May be 0 if the socket is not transfered.
     **/
    private function transferDropdownSocket(int $sockets_id): int
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
                $data  = $socket->fields;
                $locID = $this->transferDropdownLocation($socket->fields['locations_id']);

                // Search if the locations_id already exists in the destination entity
                $iterator = $DB->request([
                    'SELECT' => 'id',
                    'FROM'   => 'glpi_sockets',
                    'WHERE'  => [
                        'entities_id'  => $this->to,
                        'name'         => $socket->fields['name'],
                        'locations_id' => $locID,
                    ],
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
                $newID    = $socket->add([
                    'name'         => $data['name'],
                    'comment'      => $data['comment'],
                    'entities_id'  => $this->to,
                    'locations_id' => $locID,
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
    private function transferPrinterCartridges($ID, $newID): void
    {
        global $DB;

        // Get cartrdiges linked
        $iterator = $DB->request([
            'SELECT' => ['id', 'cartridgeitems_id'],
            'FROM'   => 'glpi_cartridges',
            'WHERE'  => ['printers_id' => $ID],
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
                        if ($this->haveItemsToTransfer(Printer::class)) {
                            // Not already transfer cartype
                            $ccriteria = [
                                'COUNT'  => 'cpt',
                                'FROM'   => 'glpi_cartridges',
                                'WHERE'  => [
                                    'cartridgeitems_id'  => $data['cartridgeitems_id'],
                                    'printers_id'        => ['>', 0],
                                    'NOT'                => [
                                        'printers_id'  => $this->needtobe_transfer[Printer::class],
                                    ],
                                ],
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
                                        'name'         => $carttype->fields['name'],
                                    ],
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
                                    $carttype->fields = [];
                                    $newcarttypeID        = $carttype->add($input);
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
                            'cartridgeitems_id' => $newcarttypeID,
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
                            'cartridgeitems_id'  => $data['cartridgeitems_id'],
                        ],
                    ])->current();

                    if ($result['cpt'] === 0) {
                        if ($this->options['clean_cartridgeitem'] == 1) { // delete
                            $carttype->delete(['id' => $data['cartridgeitems_id']]);
                        }
                        if ($this->options['clean_cartridgeitem'] == 2) { // purge
                            $carttype->delete(['id' => $data['cartridgeitems_id']], true);
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
    private function copySingleSoftware($ID): int
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
                        'name'         => $soft->fields['name'],
                    ] + $manufacturer,
                ]);

                if ($data = $iterator->current()) {
                    $newsoftID = $data["id"];
                } else {
                    // create new item (don't check if move possible => clean needed)
                    unset($soft->fields['id']);
                    $input                = $soft->fields;
                    $input['entities_id'] = $this->to;
                    $soft->fields = [];
                    $newsoftID            = $soft->add($input);
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
    private function copySingleVersion($ID): int
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
                        'name'         => $vers->fields['name'],
                    ],
                ]);

                if ($data = $iterator->current()) {
                    $newversID = $data["id"];
                } else {
                    // create new item (don't check if move possible => clean needed)
                    unset($vers->fields['id']);
                    $input                 = $vers->fields;
                    $vers->fields = [];
                    // entities_id and is_recursive from new software are set in prepareInputForAdd
                    // they must be emptied to be computed
                    unset($input['entities_id']);
                    unset($input['is_recursive']);
                    $input['softwares_id'] = $newsoftID;
                    $newversID             = $vers->add($input);
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
    private function transferItem_Disks($itemtype, $ID): void
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
    private function transferItemSoftwares($itemtype, $ID): void
    {
        global $DB;

        // Get Installed version
        $criteria = [
            'SELECT' => ['id', 'softwareversions_id'],
            'FROM'   => 'glpi_items_softwareversions',
            'WHERE'  => [
                'items_id'     => $ID,
                'itemtype'     => $itemtype,
            ],
        ];

        if (!empty($this->noneedtobe_transfer['SoftwareVersion'])) {
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
                            'softwareversions_id' => $newversID,
                        ],
                        [
                            'id' => $data['id'],
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
                    'itemtype'  => $itemtype,
                ],
            ]);
            foreach ($iterator as $data) {
                $this->transferAffectedLicense($data['id']);
            }
        } else {
            $DB->delete('glpi_items_softwarelicenses', [
                'items_id'  => $ID,
                'itemtype'  => $itemtype,
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
    private function transferAffectedLicense($ID): void
    {
        global $DB;

        $item_softwarelicense = new Item_SoftwareLicense();
        $license                  = new SoftwareLicense();

        if ($item_softwarelicense->getFromDB($ID)) {
            if ($license->getFromDB($item_softwarelicense->getField('softwarelicenses_id'))) {
                //// Update current : decrement number by 1 if valid
                if ($license->getField('number') > 1) {
                    $license->update([
                        'id'     => $license->getID(),
                        'number' => ($license->getField('number') - 1),
                    ]);
                } elseif ($license->getField('number') == 1) {
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
                            'name'         => $license->fields['name'],
                            'serial'       => $license->fields['serial'],
                        ],
                    ]);

                    $newlicID = -1;
                    //// If exists : increment number by 1
                    if (count($iterator)) {
                        $data     = $iterator->current();
                        $newlicID = $data['id'];
                        $license->update(['id'     => $data['id'],
                            'number' => $data['number'] + 1,
                        ]);
                    } else {
                        //// If not exists : create with number = 1
                        $input = $license->fields;
                        foreach (
                            ['softwareversions_id_buy',
                                'softwareversions_id_use',
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
                        $newlicID              = $license->add($input);
                    }

                    if ($newlicID > 0) {
                        $input = ['id'                  => $ID,
                            'softwarelicenses_id' => $newlicID,
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
    private function transferSoftwareLicensesAndVersions($ID): void
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => 'glpi_softwarelicenses',
            'WHERE'  => ['softwares_id' => $ID],
        ]);

        foreach ($iterator as $data) {
            $this->transferItem('SoftwareLicense', $data['id'], $data['id']);
        }

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM'   => 'glpi_softwareversions',
            'WHERE'  => ['softwares_id' => $ID],
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
    private function cleanSoftwareVersions(): void
    {
        if (!isset($this->already_transfer['SoftwareVersion'])) {
            return;
        }

        $vers = new SoftwareVersion();
        foreach ($this->already_transfer['SoftwareVersion'] as $old => $new) {
            if (
                (countElementsInTable("glpi_softwarelicenses", ['softwareversions_id_buy' => $old]) === 0)
                && (countElementsInTable("glpi_softwarelicenses", ['softwareversions_id_use' => $old]) === 0)
                && (countElementsInTable(
                    "glpi_items_softwareversions",
                    ['softwareversions_id' => $old]
                ) === 0)
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
        if (!isset($this->already_transfer['Software']) || (int) $this->options['clean_software'] === 0) {
            // Nothing to clean
            return;
        }

        $soft = new Software();
        foreach ($this->already_transfer['Software'] as $old => $new) {
            if (
                (countElementsInTable("glpi_softwarelicenses", ['softwares_id' => $old]) == 0)
                && (countElementsInTable("glpi_softwareversions", ['softwares_id' => $old]) == 0)
            ) {
                if ($this->options['clean_software'] == 1) { // delete
                    $soft->delete(['id' => $old]);
                } elseif ($this->options['clean_software'] == 2) { // purge
                    $soft->delete(['id' => $old], true);
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
    private function transferCertificates($itemtype, $ID, $newID): void
    {
        global $DB;

        // if keep
        if ($this->options['keep_certificate']) {
            $certificate = new Certificate();
            // Get certificates for the item
            $certificates_items_query = [
                'SELECT' => ['id', 'certificates_id'],
                'FROM'   => 'glpi_certificates_items',
                'WHERE'  => [
                    'items_id'  => $ID,
                    'itemtype'  => $itemtype,
                ],
            ];
            if (!empty($this->noneedtobe_transfer[Certificate::class])) {
                $certificates_items_query['WHERE'][] = [
                    'NOT' => ['certificates_id' => $this->noneedtobe_transfer[Certificate::class]],
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

                        if ($this->haveItemsToTransfer($dtype)) {
                            // No items to transfer -> exists links
                            $result = $DB->request([
                                'COUNT'  => 'cpt',
                                'FROM'   => 'glpi_certificates_items',
                                'WHERE'  => [
                                    'certificates_id' => $item_ID,
                                    'itemtype'        => $dtype,
                                    'NOT'             => ['items_id' => $this->needtobe_transfer[$dtype]],
                                ],
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
                                'name'         => $certificate->fields['name'],
                            ],
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
                            $certificate->fields = [];
                            $newcertificateID     = $certificate->add($input);
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
                                'certificates_id' => $newcertificateID,
                            ],
                            [
                                'id' => $data['id'],
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
                                'itemtype'        => $itemtype,
                            ]
                        );
                    } else { // same certificate for new item update link
                        $DB->update(
                            'glpi_certificates_items',
                            [
                                'items_id' => $newID,
                            ],
                            [
                                'id' => $data['id'],
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
                        'WHERE'  => ['certificates_id' => $item_ID],
                    ])->current();

                    if ($remain['cpt'] == 0) {
                        if ($this->options['clean_certificate'] == 1) {
                            $certificate->delete(['id' => $item_ID]);
                        }
                        if ($this->options['clean_certificate'] == 2) { // purge
                            $certificate->delete(['id' => $item_ID], true);
                        }
                    }
                }
            }
        } else {// else unlink
            $DB->delete(
                'glpi_certificates_items',
                [
                    'items_id'  => $ID,
                    'itemtype'  => $itemtype,
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
    private function transferContracts($itemtype, $ID, $newID): void
    {
        global $DB;

        // if keep
        if ($this->options['keep_contract']) {
            $contract = new Contract();
            // Get contracts for the item
            $contracts_items_query = [
                'SELECT' => ['id', 'contracts_id'],
                'FROM'   => 'glpi_contracts_items',
                'WHERE'  => [
                    'items_id'  => $ID,
                    'itemtype'  => $itemtype,
                ],
            ];
            if (!empty($this->noneedtobe_transfer[Contract::class])) {
                $contracts_items_query['WHERE'][] = [
                    'NOT' => ['contracts_id' => $this->noneedtobe_transfer[Contract::class]],
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

                        if ($this->haveItemsToTransfer($dtype)) {
                            // No items to transfer -> exists links
                            $result = $DB->request([
                                'COUNT'  => 'cpt',
                                'FROM'   => 'glpi_contracts_items',
                                'WHERE'  => [
                                    'contracts_id' => $item_ID,
                                    'itemtype'     => $dtype,
                                    'NOT'          => ['items_id' => $this->needtobe_transfer[$dtype]],
                                ],
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
                                'name'         => $contract->fields['name'],
                            ],
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
                            $contract->fields = [];
                            $newcontractID        = $contract->add($input);
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
                                'contracts_id' => $newcontractID,
                            ],
                            [
                                'id' => $data['id'],
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
                                'itemtype'     => $itemtype,
                            ]
                        );
                    } else { // same contract for new item update link
                        $DB->update(
                            'glpi_contracts_items',
                            [
                                'items_id' => $newID,
                            ],
                            [
                                'id' => $data['id'],
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
                        'WHERE'  => ['contracts_id' => $item_ID],
                    ])->current();

                    if ($remain['cpt'] == 0) {
                        if ($this->options['clean_contract'] == 1) {
                            $contract->delete(['id' => $item_ID]);
                        }
                        if ($this->options['clean_contract'] == 2) { // purge
                            $contract->delete(['id' => $item_ID], true);
                        }
                    }
                }
            }
        } else {// else unlink
            $DB->delete(
                'glpi_contracts_items',
                [
                    'items_id'  => $ID,
                    'itemtype'  => $itemtype,
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
    private function transferDocuments($itemtype, $ID, $newID): void
    {
        global $DB;

        // if keep
        if ($this->options['keep_document']) {
            $document = new Document();
            // Get documents for the item
            $documents_items_query = [
                'SELECT' => ['id', 'documents_id'],
                'FROM'   => 'glpi_documents_items',
                'WHERE'  => [
                    'items_id'  => $ID,
                    'itemtype'  => $itemtype,
                ],
            ];
            if (!empty($this->noneedtobe_transfer[Document::class])) {
                $documents_items_query['WHERE'][] = [
                    'NOT' => ['documents_id' => $this->noneedtobe_transfer[Document::class]],
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
                            if (!empty($this->noneedtobe_transfer[$dtype])) {
                                $NOT = [...$NOT, ...$this->noneedtobe_transfer[$dtype]];
                            }

                            $where = [
                                'documents_id' => $item_ID,
                                'itemtype'     => $dtype,
                            ];
                            if (count($NOT)) {
                                $where['NOT'] = ['items_id' => $NOT];
                            }

                            $result = $DB->request([
                                'COUNT'  => 'cpt',
                                'FROM'   => 'glpi_documents_items',
                                'WHERE'  => $where,
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
                                'name'         => $document->fields['name'],
                            ],
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
                            $document->fields = [];
                            $newdocID = $document->add($input);
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
                                'documents_id' => $newdocID,
                            ],
                            [
                                'id' => $data['id'],
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
                                'itemtype'     => $itemtype,
                            ]
                        );
                    } else { // same doc for new item update link
                        $DB->update(
                            'glpi_documents_items',
                            [
                                'items_id' => $newID,
                            ],
                            [
                                'id' => $data['id'],
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
                            'documents_id' => $item_ID,
                        ],
                    ])->current();

                    if ($remain['cpt'] == 0) {
                        if ($this->options['clean_document'] == 1) {
                            $document->delete(['id' => $item_ID]);
                        }
                        if ($this->options['clean_document'] == 2) { // purge
                            $document->delete(['id' => $item_ID], true);
                        }
                    }
                }
            }
        } else {// else unlink
            $DB->delete(
                'glpi_documents_items',
                [
                    'items_id'  => $ID,
                    'itemtype'  => $itemtype,
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
    private function transferDirectConnection($itemtype, $ID, $link_type): void
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

        $link_item = getItemForItemtype($link_type);
        if (!($link_item instanceof CommonDBTM)) {
            return;
        }

        // Get connections
        $criteria = [
            'SELECT' => ['id', 'items_id_peripheral'],
            'FROM'   => Asset_PeripheralAsset::getTable(),
            'WHERE'  => [
                'itemtype_asset'      => $itemtype,
                'items_id_asset'      => $ID,
                'itemtype_peripheral' => $link_type,
            ],
        ];

        if ($link_item->maybeRecursive() && !empty($this->noneedtobe_transfer[$link_type])) {
            $criteria['WHERE']['NOT'] = ['items_id' => $this->noneedtobe_transfer[$link_type]];
        }

        $iterator = $DB->request($criteria);

        // Foreach get item
        foreach ($iterator as $data) {
            $item_ID = $data['items_id_peripheral'];
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
                            // = all linked assets need to be transfer (so not copy)
                            $asset_criteria = [
                                'COUNT'  => 'cpt',
                                'FROM'   => Asset_PeripheralAsset::getTable(),
                                'WHERE'  => [
                                    'itemtype_asset'      => $itemtype,
                                    'itemtype_peripheral' => $link_type,
                                    'items_id_peripheral' => $item_ID,
                                ],
                            ];
                            if ($this->haveItemsToTransfer($itemtype)) {
                                $asset_criteria['WHERE']['NOT'] = [
                                    'items_id_asset' => $this->needtobe_transfer[$itemtype],
                                ];
                            }
                            $result = $DB->request($asset_criteria)->current();

                            // All linked assets need to be transfer -> use unique transfer system
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
                                        'name'         => $link_item->getField('name'),
                                    ],
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

                                    $link_item = new $link_item();
                                    $newID = $link_item->add($input);
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
                                Asset_PeripheralAsset::getTable(),
                                [
                                    'items_id_peripheral' => $newID,
                                ],
                                [
                                    'id' => $data['id'],
                                ]
                            );
                        }
                    } else {
                        // Else delete link
                        // Call Disconnect for global device (no disconnect behavior, but history )
                        (new Asset_PeripheralAsset())->delete([
                            'id'              => $data['id'],
                            '_no_auto_action' => true,
                        ]);

                        $need_clean_process = true;
                    }
                    // If clean and not linked dc -> delete
                    if ($need_clean_process && $clean) {
                        $result = $DB->request([
                            'COUNT'  => 'cpt',
                            'FROM'   => Asset_PeripheralAsset::getTable(),
                            'WHERE'  => [
                                'items_id_peripheral' => $item_ID,
                                'itemtype_peripheral' => $link_type,
                            ],
                        ])->current();

                        if ($result['cpt'] == 0) {
                            if ($clean == 1) {
                                $link_item->delete(['id' => $item_ID]);
                            }
                            if ($clean == 2) { // purge
                                $link_item->delete(['id' => $item_ID], true);
                            }
                        }
                    }
                } else { // If unique :
                    //if keep -> transfer list else unlink
                    if ($keep) {
                        $this->transferItem($link_type, $item_ID, $item_ID);
                    } else {
                        // Else delete link (apply disconnect behavior)
                        (new Asset_PeripheralAsset())->delete(['id' => $data['id']]);

                        //if clean -> delete
                        if ($clean == 1) {
                            $link_item->delete(['id' => $item_ID]);
                        } elseif ($clean == 2) { // purge
                            $link_item->delete(['id' => $item_ID], true);
                        }
                    }
                }
            } else {
                // Unexisting item / Force disconnect
                (new Asset_PeripheralAsset())->delete([
                    'id'              => $data['id'],
                    '_no_history'     => true,
                    '_no_auto_action' => true,
                ]);
            }
        }
    }

    /**
     * Handle direct connection between a peripheral and its main asset when transfering the peripheral.
     *
     * @param string $peripheral_itemtype
     * @param int    $ID
     *
     * @return void
     * @since 0.84.4
     **/
    private function managePeripheralMainAsset(string $peripheral_itemtype, int $ID): void
    {
        global $DB;

        // Get connections
        $criteria = [
            'FROM'   => Asset_PeripheralAsset::getTable(),
            'WHERE'  => [
                'itemtype_peripheral' => $peripheral_itemtype,
                'items_id_peripheral' => $ID,
            ],
        ];

        $transfered_itemtypes = array_intersect(
            Asset_PeripheralAsset::getPeripheralHostItemtypes(),
            array_keys($this->needtobe_transfer)
        );
        if (count($transfered_itemtypes) > 0) {
            $where_not = [];
            foreach ($transfered_itemtypes as $itemtype) {
                if ($this->haveItemsToTransfer($itemtype)) {
                    $where_not[] = [
                        'itemtype_asset' => $itemtype,
                        'items_id_asset' => $this->needtobe_transfer[$itemtype],
                    ];
                }
            }
            if (count($where_not) > 0) {
                $criteria['WHERE'][] = ['NOT' => $where_not];
            }
        }
        $iterator = $DB->request($criteria);

        if (count($iterator)) {
            // Foreach get item
            foreach ($iterator as $data) {
                $itemtype = $data['itemtype_asset'];
                $item_id  = $data['items_id_asset'];

                $delete_params = [
                    'id' => $data['id'],
                ];
                if (
                    !is_a($itemtype, CommonDBTM::class, true)
                    || !(new $itemtype())->getFromDB($item_id)
                ) {
                    // Unexisting item / Force disconnect
                    $delete_params += [
                        '_no_history'     => true,
                        '_no_auto_action' => true,
                    ];
                }
                (new Asset_PeripheralAsset())->delete($delete_params);
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
    private function transferTickets($itemtype, $ID, $newID): void
    {
        global $DB;

        $job   = new Ticket();
        $rel   = new Item_Ticket();

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_tickets.*',
                'glpi_items_tickets.id AS _relid',
            ],
            'FROM'      => 'glpi_tickets',
            'LEFT JOIN' => [
                'glpi_items_tickets' => [
                    'ON' => [
                        'glpi_items_tickets' => 'tickets_id',
                        'glpi_tickets'       => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'items_id'  => $ID,
                'itemtype'  => $itemtype,
            ],
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
     * @param int $newID       New ITIL Object ID (not used)
     *
     * @return void
     **/
    private function transferLinkedSuppliers($itemtype, $ID, $newID): void
    {
        global $DB;

        if (!is_a($itemtype, CommonITILObject::class, true)) {
            return;
        }

        /* @var CommonITILObject $item */
        $item = new $itemtype();
        $linkclass = $item->supplierlinkclass;
        if (!is_a($linkclass, CommonITILActor::class, true)) {
            return;
        }

        /* @var CommonITILActor $link */
        $link  = new $linkclass();
        $field = getForeignKeyFieldForItemType($itemtype);
        $table = $link::getTable();

        $iterator = $DB->request([
            'FROM'   => $table,
            'WHERE'  => [$field => $ID],
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
                            'name'         => $supplier->fields['name'],
                        ],
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
                        $supplier->fields = [];
                        $newID                 = $supplier->add($input);
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
     * @param int $newID       New ITIL Object ID (not used))
     *
     * @return void
     **/
    private function transferTaskCategory($itemtype, $ID, $newID): void
    {
        global $DB;

        if (!is_a($itemtype, CommonITILObject::class, true)) {
            return;
        }

        $taskclass = $itemtype::getTaskClass();
        if (!is_a($taskclass, CommonITILTask::class, true)) {
            return;
        }

        /* @var CommonITILTask $task */
        $task  = new $taskclass();
        $field = getForeignKeyFieldForItemType($itemtype);
        $table = $task::getTable();

        $iterator = $DB->request([
            'FROM'   => $table,
            'WHERE'  => [$field => $ID],
        ]);

        foreach ($iterator as $data) {
            $input = [];

            if ($data['taskcategories_id'] > 0) {
                $categ = new TaskCategory();

                if ($categ->getFromDB($data['taskcategories_id'])) {
                    $inputcat['entities_id']  = $this->to;
                    $inputcat['completename'] = $categ->fields['completename'];
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
    private function transferHelpdeskAdditionalInformations($data): array
    {

        $input               = [];

        //TODO Is there a replacement needed for this commented code or is it obsolete?
        // if ($data['suppliers_id_assign'] > 0) {
        //   $suppliers_id_assign = $this->transferSingleSupplier($data['suppliers_id_assign']);
        // }

        // Transfer ticket category
        $catid = 0;
        if ($data['itilcategories_id'] > 0) {
            $categ = new ITILCategory();

            if ($categ->getFromDB($data['itilcategories_id'])) {
                $inputcat['entities_id']  = $this->to;
                $inputcat['completename'] = $categ->fields['completename'];
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
    private function transferHistory($itemtype, $ID, $newID): void
    {
        global $DB;

        if ($ID == $newID) {
            // Item wasn't transferred. Nothing to do.
            return;
        }
        if ($this->options['keep_history']) {
            $iterator = $DB->request([
                'FROM'   => 'glpi_logs',
                'WHERE'  => [
                    'itemtype'  => $itemtype,
                    'items_id'  => $ID,
                ],
            ]);

            foreach ($iterator as $data) {
                unset($data['id']);
                $data = [
                    'items_id'  => $newID,
                    'itemtype'  => $itemtype,
                ] + $data;
                $DB->insert('glpi_logs', $data);
            }
        } else {
            // Delete history if transferred
            $DB->delete('glpi_logs', [
                'items_id'  => $ID,
                'itemtype'  => $itemtype,
            ]);
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
    private function transferCompatiblePrinters($ID, $newID): void
    {
        global $DB;

        if ($ID == $newID) {
            // Item wasn't transferred. Nothing to do.
            return;
        }

        $iterator = $DB->request([
            'SELECT' => ['printermodels_id'],
            'FROM'   => 'glpi_cartridgeitems_printermodels',
            'WHERE'  => ['cartridgeitems_id' => $ID],
        ]);

        if (count($iterator)) {
            foreach ($iterator as $data) {
                CartridgeItem::addCompatibleType($newID, $data["printermodels_id"]);
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
    private function transferInfocoms($itemtype, $ID, $newID): void
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
                                'itemtype'  => $itemtype,
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
                        $ic->fields = [];
                        $ic->add($input);
                    } else {
                        // Same Item : manage only supplier move
                        // Update supplier
                        if (
                            ($suppliers_id > 0)
                            && ($suppliers_id != $ic->fields['suppliers_id'])
                        ) {
                            $ic->update(['id'           => $ic->fields['id'],
                                'suppliers_id' => $suppliers_id,
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
    private function transferSingleSupplier($ID): int
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
            // All linked items need to be transfer so transfer supplier ?
            // Search for contract
            $criteria = [
                'COUNT'  => 'cpt',
                'FROM'   => 'glpi_contracts_suppliers',
                'WHERE'  => [
                    'suppliers_id' => $ID,
                ],
            ];
            if ($this->haveItemsToTransfer(Contract::class)) {
                $criteria['WHERE']['NOT'] = ['contracts_id' => $this->needtobe_transfer[Contract::class]];
            }

            $result = $DB->request($criteria)->current();
            $links_remaining = $result['cpt'];

            if ($links_remaining === 0) {
                // Search for infocoms
                if ($this->options['keep_infocom']) {
                    foreach (Infocom::getItemtypesThatCanHave() as $itemtype) {
                        if ($this->haveItemsToTransfer($itemtype)) {
                            $icriteria = [
                                'COUNT'  => 'cpt',
                                'FROM'   => 'glpi_infocoms',
                                'WHERE'  => [
                                    'suppliers_id' => $ID,
                                    'itemtype'     => $itemtype,
                                ],
                            ];
                            $icriteria['WHERE']['NOT'] = ['items_id' => $this->needtobe_transfer[$itemtype]];

                            $result = $DB->request($icriteria)->current();
                            $links_remaining += $result['cpt'];
                        }
                    }
                }
            }

            // All linked items need to be transfer -> use unique transfer system
            if ($links_remaining === 0) {
                $this->transferItem('Supplier', $ID, $ID);
                $newID = $ID;
            } else { // else Transfer by Copy
                // Is existing item in the destination entity ?
                $iterator = $DB->request([
                    'FROM'   => 'glpi_suppliers',
                    'WHERE'  => [
                        'entities_id'  => $this->to,
                        'name'         => $ent->fields['name'],
                    ],
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
                    $ent->fields = [];
                    $newID                = $ent->add($input);
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
    private function transferSupplierContacts($ID, $newID): void
    {
        global $DB;

        // if keep
        if ($this->options['keep_contact']) {
            $contact = new Contact();
            // Get contracts for the item
            $criteria = [
                'FROM'   => 'glpi_contacts_suppliers',
                'WHERE'  => [
                    'suppliers_id' => $ID,
                ],
            ];
            if (!empty($this->noneedtobe_transfer[Contact::class])) {
                $criteria['WHERE']['NOT'] = ['contacts_id' => $this->noneedtobe_transfer[Contact::class]];
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
                                'contacts_id'  => $item_ID,
                            ],
                        ];
                        $exclusions = [...($this->needtobe_transfer['Supplier'] ?? []), ...($this->noneedtobe_transfer['Supplier'] ?? [])];
                        if ($exclusions !== []) {
                            $scriteria['WHERE']['NOT'] = ['suppliers_id' => $exclusions];
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
                                'name'         => $contact->fields['name'],
                                'firstname'    => $contact->fields['firstname'],
                            ],
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
                            $contact->fields = [];
                            $newcontactID         = $contact->add($input);
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
                                'contacts_id' => $newcontactID,
                            ],
                            [
                                'id' => $data['id'],
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
                                'suppliers_id' => $newID,
                            ]
                        );
                    } else { // transfer contact but copy supplier : update link
                        $DB->update(
                            'glpi_contacts_suppliers',
                            [
                                'suppliers_id' => $newID,
                            ],
                            [
                                'id' => $data['id'],
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
                        'WHERE'  => ['contacts_id' => $item_ID],
                    ])->current();

                    if ($remain['cpt'] == 0) {
                        if ($this->options['clean_contact'] == 1) {
                            $contact->delete(['id' => $item_ID]);
                        }
                        if ($this->options['clean_contact'] == 2) { // purge
                            $contact->delete(['id' => $item_ID], true);
                        }
                    }
                }
            }
        } else {// else unlink
            $DB->delete(
                'glpi_contacts_suppliers',
                [
                    'suppliers_id' => $ID,
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
    private function transferReservations($itemtype, $ID, $newID): void
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
                        $ri->fields = [];
                        $ri->add($input);
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
    private function transferDevices($itemtype, $ID, $newID): void
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
                            'itemtype'  => $itemtype,
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

                    $device          = getItemForItemtype($devicetype);

                    if (!($device instanceof CommonDevice)) {
                        continue;
                    }

                    // Get contracts for the item
                    $criteria = [
                        'FROM'   => $itemdevicetable,
                        'WHERE'  => [
                            'items_id'  => $ID,
                            'itemtype'  => $itemtype,
                        ],
                    ];
                    if (!empty($this->noneedtobe_transfer[$devicetype])) {
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
                                    'WHERE'           => [$fk => $item_ID],
                                ]);

                                foreach ($type_iterator as $data_type) {
                                    $dtype = $data_type['itemtype'];

                                    if ($this->haveItemsToTransfer($dtype)) {
                                        // No items to transfer -> exists links
                                        $dcriteria = [
                                            'COUNT'  => 'cpt',
                                            'FROM'   => $itemdevicetable,
                                            'WHERE'  => [
                                                $fk         => $item_ID,
                                                'itemtype'  => $dtype,
                                                'NOT'       => [
                                                    'items_id'  => $this->needtobe_transfer[$dtype],
                                                ],
                                            ],
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
                                            $field         => $device->fields[$field],
                                        ],
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

                                        $device = new $device();
                                        $newdeviceID = $device->add($input);
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
                                    'items_id'  => $newID,
                                ],
                                [
                                    'id' => $data['id'],
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
    private function transferNetworkLink($itemtype, $ID, $newID): void
    {
        global $DB;
        /// TODO manage with new network system
        $np = new NetworkPort();
        $nn = new NetworkPort_NetworkPort();

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_networkports.*',
            ],
            'FROM'      => 'glpi_networkports',
            'LEFT JOIN' => [
                'glpi_networkportethernets'   => [
                    'ON' => [
                        'glpi_networkportethernets'   => 'networkports_id',
                        'glpi_networkports'           => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'glpi_networkports.items_id'  => $ID,
                'glpi_networkports.itemtype'  => $itemtype,
            ],
        ]);

        if (count($iterator)) {
            switch ($this->options['keep_networklink']) {
                // Delete netport
                case 0:
                    // Not a copy -> delete
                    if ($ID == $newID) {
                        foreach ($iterator as $data) {
                            $np->delete(['id' => $data['id']], true);
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
                            $socket = new Socket();
                            if ($socket->getFromDBByCrit(["networkports_id" => $data['id']])) {
                                if ($socket->getID()) {
                                    $data['sockets_id'] = $this->transferDropdownSocket($socket->getID());
                                }
                            }
                            unset($data['id']);
                            $data['items_id'] = $newID;
                            $np->fields = [];
                            $np->add($data);
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
                            $np->fields = [];
                            $np->add($data);
                        }
                    } else {
                        foreach ($iterator as $data) {
                            // Not a copy -> only update socket
                            if (isset($data['sockets_id']) && $data['sockets_id']) {
                                $socket = new Socket();
                                if ($socket->getFromDBByCrit(["networkports_id" => $data['id']])) {
                                    if ($socket->getID()) {
                                        $socketID = $this->transferDropdownSocket($socket->getID());
                                        $input['id']         = $data['id'];
                                        $input['sockets_id'] = $socketID;
                                        $np->update($input);
                                    }
                                }
                            }
                        }
                    }
                    break;
            }
        }
    }

    public function showForm($ID, array $options = [])
    {
        $edit_form = true;
        $referer_url = Html::getRefererUrl();
        if ($referer_url === null || !str_contains($referer_url, "transfer.form.php")) {
            $edit_form = false;
        }

        $options['target'] = URL::sanitizeURL($options['target']);

        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('pages/admin/transfer.html.twig', [
            'item' => $this,
            'edit_mode' => $edit_form,
            'can_change_options' => Session::haveRightsOr("transfer", [CREATE, UPDATE, PURGE]),
            'params' => $options,
        ]);
        return true;
    }

    /**
     * Display items to transfer
     * @return void
     */
    public function showTransferList(): void
    {
        global $DB;

        $transfer_list = [];
        if (!empty($_SESSION['glpitransfer_list'])) {
            /** @var class-string<CommonDBTM> $itemtype */
            foreach ($_SESSION['glpitransfer_list'] as $itemtype => $tab) {
                if (!empty($tab)) {
                    $table = $itemtype::getTable();
                    $name_field = $itemtype::getNameField();
                    $table_name_field = sprintf('%1$s.%2$s', $table, $name_field);
                    $iterator = $DB->request([
                        'SELECT' => [
                            "$table.id",
                            $table_name_field,
                            'entities.completename AS entname',
                            'entities.id AS entID',
                        ],
                        'FROM' => $table,
                        'LEFT JOIN' => [
                            'glpi_entities AS entities' => [
                                'ON' => [
                                    'entities' => 'id',
                                    $table => 'entities_id',
                                ],
                            ],
                        ],
                        'WHERE' => ["$table.id" => $tab],
                        'ORDERBY' => ['entname', $table_name_field],
                    ]);

                    foreach ($iterator as $data) {
                        $transfer_list[$itemtype] ??= [];
                        $transfer_list[$itemtype][] = $data;
                    }
                }
            }
        }

        TemplateRenderer::getInstance()->display('pages/admin/transfer_list.html.twig', [
            'transfer_list' => $transfer_list,
        ]);
    }

    public static function getIcon()
    {
        return "ti ti-corner-right-up";
    }

    public function getItemtypes(): array
    {
        $itemtypes = [
            'Software', // Software first (to avoid copy during computer transfer)
            'Computer', // Computer before all other items
        ];

        $definitions = AssetDefinitionManager::getInstance()->getDefinitions(true);
        foreach ($definitions as $definition) {
            $itemtypes[] = $definition->getAssetClassName();
        }

        $itemtypes = array_merge(
            $itemtypes,
            [
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
                'Change',
            ]
        );

        return $itemtypes;
    }
}
