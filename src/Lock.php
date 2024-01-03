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

use Glpi\Plugin\Hooks;

/**
 * This class manages locks
 * Lock management is available for objects and link between objects. It relies on the use of
 * a is_dynamic field, to incidate if item supports lock, and is_deleted field to incidate if the
 * item or link is locked
 * By setting is_deleted to 0 again, the item is unlock
 *
 * Note : GLPI's core supports locks for objects. It's up to the external inventory tool to manage
 * locks for fields
 *
 * @since 0.84
 **/
class Lock extends CommonGLPI
{
    public static function getTypeName($nb = 0)
    {
        return _n('Lock', 'Locks', $nb);
    }


    /**
     * Display form to unlock fields and links
     *
     * @param CommonDBTM $item the source item
     **/
    public static function showForItem(CommonDBTM $item)
    {
        /**
         * @var array $CFG_GLPI
         * @var \DBmysql $DB
         */
        global $CFG_GLPI, $DB;

        $ID       = $item->getID();
        $itemtype = $item->getType();
        $header   = false;

        //If user doesn't have update right on the item, lock form must not be displayed
        if (!$item->isDynamic() || !$item->can($item->fields['id'], UPDATE)) {
            return false;
        }

        echo "<div class='alert alert-primary d-flex align-items-center' role='alert'>";
        echo "<i class='fas fa-info-circle fa-xl'></i>";
        echo "<span class='ms-2'>";
        echo __("A locked field is a manually modified field.");
        echo "<br>";
        echo __("The automatic inventory will no longer modify this field, unless you unlock it.");
        echo "</span>";
        echo "</div>";

        $lockedfield = new Lockedfield();
        if ($lockedfield->isHandled($item)) {
            $subquery = [];

            //get locked field for current itemtype
            $subquery[] = new \QuerySubQuery([
                'SELECT' => $lockedfield->getTable() . ".*",
                'FROM'   => $lockedfield->getTable(),
                'WHERE'  => [
                    'OR' => [
                        [
                            $lockedfield->getTable() . '.itemtype'  => $itemtype,
                            $lockedfield->getTable() . '.items_id'  => $ID
                        ], [
                            $lockedfield->getTable() . '.itemtype'  => $itemtype,
                            $lockedfield->getTable() . '.is_global' => 1
                        ]
                    ]
                ]
            ]);

            //get locked field for other lockable object
            foreach ($CFG_GLPI['inventory_lockable_objects'] as $lockable_itemtype) {
                $lockable_object = new $lockable_itemtype();
                $query  = [
                    'SELECT' => $lockedfield->getTable() . ".*",
                    'FROM'   => $lockedfield->getTable(),
                    'LEFT JOIN' => [
                        getTableForItemType($lockable_itemtype)   => [
                            'FKEY'   => [
                                $lockedfield->getTable()  => 'items_id',
                                getTableForItemType($lockable_itemtype)   => 'id'
                            ]
                        ]
                    ],
                    'WHERE'  => [
                        'OR' => [
                            [
                                $lockedfield->getTable() . '.itemtype'  => $lockable_itemtype,
                                $lockedfield->getTable() . '.items_id'  => new \QueryExpression(getTableForItemType($lockable_itemtype) . '.id')
                            ], [
                                $lockedfield->getTable() . '.itemtype'  => $lockable_itemtype,
                                $lockedfield->getTable() . '.is_global' => 1
                            ]
                        ]
                    ]
                ];

                if ($lockable_object instanceof CommonDBConnexity) {
                    $connexity_criteria = $lockable_itemtype::getSQLCriteriaToSearchForItem($itemtype, $ID);
                    if ($connexity_criteria === null) {
                        continue;
                    }
                    $query['WHERE'][] = $connexity_criteria['WHERE'];
                    if ($lockable_object->isField('is_deleted')) {
                        $query['WHERE'][] = [
                            $lockable_object::getTableField('is_deleted') => 0
                        ];
                    }
                } elseif (in_array($lockable_itemtype, $CFG_GLPI['directconnect_types'])) {
                    //we need to restrict scope with Computer_Item to prevent loading of all lockedfield
                    $query['LEFT JOIN'][Computer_Item::getTable()] =
                    [
                        'FKEY'   => [
                            Computer_Item::getTable()  => 'items_id',
                            $lockable_itemtype::getTable()   => 'id'
                        ]
                    ];
                    $query['WHERE'][] = [
                        Computer_Item::getTable() . '.computers_id'  => $ID,
                        Computer_Item::getTable() . '.is_deleted' => 0
                    ];
                } elseif ($lockable_object->isField('itemtype') && $lockable_object->isField('items_id')) {
                    $query['WHERE'][] = [
                        $lockable_itemtype::getTable() . '.itemtype'  => $itemtype,
                        $lockable_itemtype::getTable() . '.items_id'  => $ID
                    ];
                    if ($lockable_object->isField('is_deleted')) {
                        $query['WHERE'][] = [
                            $lockable_object::getTableField('is_deleted') => 0
                        ];
                    }
                }
                $subquery[] = new \QuerySubQuery($query);
            }

            $union = new \QueryUnion($subquery);
            $locked_iterator = $DB->request([
                'FROM' => $union
            ]);

            if (count($locked_iterator)) {
                $rand = mt_rand();
                Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
                $massiveactionparams = [
                    'container'      => 'mass' . __CLASS__ . $rand,
                ];
                Html::showMassiveActions($massiveactionparams);

                echo "<table class='tab_cadre_fixehov'>";

                echo "<tr>";
                echo "<tr><th colspan='5'  class='center'>" . __('Locked fields') . "</th></tr>";
                echo "<th width='10'>";
                Html::showCheckbox(['criterion' => ['tag_for_massive' => 'select_' . $lockedfield::getType()]]);
                echo "</th>";
                echo "<th>" . _n('Field', 'Fields', Session::getPluralNumber())  . "</th>";
                echo "<th>" . __('Itemtype') . "</th>";
                echo "<th>" . _n('Link', 'Links', Session::getPluralNumber()) . "</th>";
                echo "<th>" . __('Last inventoried value')  . "</th></tr>";


                //get fields labels
                $search_options = Search::getOptions($itemtype);
                foreach ($search_options as $search_option) {
                    //exclude SO added by dropdown part (to get real name)
                    //ex : Manufacturer != Firmware : Manufacturer
                    if (isset($search_option['table']) && $search_option['table'] == getTableForItemType($itemtype)) {
                        if (isset($search_option['linkfield'])) {
                            $so_fields[$search_option['linkfield']] = $search_option['name'];
                        } else if (isset($search_option['field'])) {
                            $so_fields[$search_option['field']] = $search_option['name'];
                        }
                    }
                }

                foreach ($locked_iterator as $row) {
                    echo "<tr class='tab_bg_1'>";
                    echo "<td class='center' width='10'>";
                    if ($row['is_global'] == 0 && ($lockedfield->can($row['id'], UPDATE) || $lockedfield->can($row['id'], PURGE))) {
                        $header = true;
                        echo Html::getMassiveActionCheckBox(Lockedfield::class, $row['id'], ['massive_tags' => 'select_' . $lockedfield::getType()]);
                    }
                    echo "</td>";
                    $field_label = $row['field'];
                    if (isset($so_fields[$row['field']])) {
                        $field_label = $so_fields[$row['field']];
                    } else if (isForeignKeyField($row['field'])) {
                    //on fkey, we can try to retrieve the object
                        $object = getItemtypeForForeignKeyField($row['field']);
                        if ($object != 'UNKNOWN') {
                            $field_label = $object::getTypeName(1);
                        }
                    }

                    if ($row['is_global']) {
                        $field_label .= ' (' . __('Global') . ')';
                    }
                    echo "<td class='left'>" . $field_label . "</td>";

                    //load object
                    $object = new $row['itemtype']();
                    $object->getFromDB($row['items_id']);

                    $default_itemtype_label = $row['itemtype']::getTypeName();
                    $default_object_link    = $object->getLink();
                    $default_itemtype       = $row['itemtype'];
                    $default_items_id       = null;

                    //get real type name from Item_Devices
                    // ex: get 'Hard drives' instead of 'Hard drive items'
                    if (get_parent_class($row['itemtype']) == Item_Devices::class) {
                        $default_itemtype =  $row['itemtype']::$itemtype_2;
                        $default_items_id =  $row['itemtype']::$items_id_2;
                        $default_itemtype_label = $row['itemtype']::$itemtype_2::getTypeName();
                    //get real type name from CommonDBRelation
                    // ex: get 'Operating System' instead of 'Item operating systems'
                    } elseif (get_parent_class($row['itemtype']) == CommonDBRelation::class) {
                        //For CommonDBRelation
                        // $itemtype_1 / $items_id_1 and $itemtype_2 / $items_id_2 can be inverted

                        //ex: Item_Software have
                        // $itemtype_1 = 'itemtype';
                        // $items_id_1 = 'items_id';
                        // $itemtype_2 = 'SoftwareVersion';
                        // $items_id_2 = 'softwareversions_id';
                        if (preg_match('/^itemtype/', $row['itemtype']::$itemtype_1)) {
                            $default_itemtype =  $row['itemtype']::$itemtype_2;
                            $default_items_id =  $row['itemtype']::$items_id_2;
                            $default_itemtype_label = $row['itemtype']::$itemtype_2::getTypeName();
                        } else {
                            //ex: Item_OperatingSystem have
                            // $itemtype_1 = 'OperatingSystem';
                            // $items_id_1 = 'operatingsystems_id';
                            // $itemtype_2 = 'itemtype';
                            // $items_id_2 = 'items_id';
                            $default_itemtype =  $row['itemtype']::$itemtype_1;
                            $default_items_id =  $row['itemtype']::$items_id_1;
                            $default_itemtype_label = $row['itemtype']::$itemtype_1::getTypeName();
                        }
                    }

                    // specific link for CommonDBRelation itemtype (like Item_OperatingSystem)
                    // get 'real' object name inside URL name
                    // ex: get 'Ubuntu 22.04.1 LTS' instead of 'Computer asus-desktop'
                    if ($default_items_id !== null && is_a($row['itemtype'], CommonDBRelation::class, true)) {
                        $related_object = new $default_itemtype();
                        $related_object->getFromDB($object->fields[$default_items_id]);
                        $default_object_link = "<a href='" . $object->getLinkURL() . "'" . $related_object->getName() . ">" . $related_object->getName() . "</a>";
                    }

                    echo "<td class='left'>" . $default_itemtype_label . "</td>";
                    echo "<td class='left'>" . $default_object_link . "</td>";
                    echo "<td class='left'>" . $row['value'] . "</td>";
                    echo "</tr>\n";
                }

                echo "</table>";
                $massiveactionparams['ontop'] = false;
                Html::showMassiveActions($massiveactionparams);
                Html::closeForm();
            } else {
                echo "<table class='tab_cadre_fixehov'>";
                echo "<tbody>";
                echo "<tr><th colspan='5' class='center'>" . _n('Locked field', 'Locked fields', Session::getPluralNumber()) . "</th></tr>";
                echo "<tr class='tab_bg_2'><td class='center' colspan='5'>" . __('No locked fields') . "</td></tr>";
                echo "</tbody>";
                echo "</table>";
            }
        }
        echo "</br><div width='100%'>";
        echo "<form method='post' id='lock_form' name='lock_form' action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";
        echo "<input type='hidden' name='id' value='$ID'>\n";
        echo "<input type='hidden' name='itemtype' value='$itemtype'>\n";
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr><th colspan='5' class='center'>" . __('Locked items') . "</th></tr>";

        //reset_header
        $header = false;
        //Use a hook to allow external inventory tools to manage per field lock
        $results =  Plugin::doHookFunction(Hooks::DISPLAY_LOCKED_FIELDS, ['item'   => $item,
            'header' => $header
        ]);
        $header |= $results['header'];

        echo "<div class='alert alert-primary d-flex align-items-center mb-4' role='alert'>";
        echo "<i class='fas fa-info-circle fa-xl'></i>";
        echo "<span class='ms-2'>";
        echo __("A locked item is a manually deleted item, for example a monitor.");
        echo "<br>";
        echo __("The automatic inventory will no longer handle this item, unless you unlock it.");
        echo "</span>";
        echo "</div>";

        //Special locks for computers only
        if ($itemtype == 'Computer') {
            //computer_item
            $computer_item = new Computer_Item();
            $types = $CFG_GLPI['directconnect_types'];
            foreach ($types as $type) {
                $params = ['is_dynamic'    => 1,
                    'is_deleted'    => 1,
                    'computers_id'  => $ID,
                    'itemtype'      => $type
                ];
                $params['FIELDS'] = ['id', 'items_id'];
                $first  = true;
                foreach ($DB->request('glpi_computers_items', $params) as $line) {
                    /** @var CommonDBTM $asset */
                    $asset = new $type();
                    $asset->getFromDB($line['items_id']);
                    if ($first) {
                        echo "<tr>";
                        echo "<th width='10'></th>";
                        echo "<th>" . $asset::getTypeName(Session::getPluralNumber()) . "</th>";
                        echo "<th>" . __('Serial number') . "</th>";
                        echo "<th>" . __('Inventory number') . "</th>";
                        echo "<th>" . __('Automatic inventory') . "</th>";
                        echo "</tr>";
                        $first = false;
                    }

                    echo "<tr class='tab_bg_1'>";
                    echo "<td class='center' width='10'>";
                    if ($computer_item->can($line['id'], UPDATE) || $computer_item->can($line['id'], PURGE)) {
                        $header = true;
                        echo "<input type='checkbox' name='Computer_Item[" . $line['id'] . "]'>";
                    }
                    echo "</td>";

                    echo "<td class='left'>" . $asset->getLink() . "</td>";
                    echo "<td class='left'>" . $asset->fields['serial'] . "</td>";
                    echo "<td class='left'>" . $asset->fields['otherserial'] . "</td>";
                    echo "<td class='left'>" . Dropdown::getYesNo($asset->fields['is_dynamic']) . "</td>";
                    echo "</tr>";
                }
            }

            //items disks
            $item_disk = new Item_Disk();
            $params = [
                'is_dynamic'   => 1,
                'is_deleted'   => 1,
                'items_id'     => $ID,
                'itemtype'     => $itemtype
            ];
            $params['FIELDS'] = ['id', 'name'];
            $first  = true;
            foreach ($DB->request($item_disk->getTable(), $params) as $line) {
                if ($first) {
                    echo "<tr>";
                    echo "<th width='10'></th>";
                    echo "<th>" . $item_disk->getTypeName(Session::getPluralNumber()) . "</th>";
                    echo "<th>" . __('Partition') . "</th>";
                    echo "<th>" . __('Mount point') . "</th>";
                    echo "<th>" . __('Automatic inventory') . "</th>";
                    echo "</tr>";
                    $first = false;
                }

                $item_disk->getFromDB($line['id']);
                echo "<tr class='tab_bg_1'>";
                echo "<td class='center' width='10'>";
                if ($item_disk->can($line['id'], UPDATE) || $item_disk->can($line['id'], PURGE)) {
                    $header = true;
                    echo "<input type='checkbox' name='Item_Disk[" . $line['id'] . "]'>";
                }
                echo "</td>";
                echo "<td class='left'>" . $item_disk->getLink() . "</td>";
                echo "<td class='left'>" . $item_disk->fields['device'] . "</td>";
                echo "<td class='left'>" . $item_disk->fields['mountpoint'] . "</td>";
                echo "<td class='left'>" . Dropdown::getYesNo($item_disk->fields['is_dynamic']) . "</td>";
                echo "</tr>\n";
            }

            $computer_vm = new ComputerVirtualMachine();
            $params = ['is_dynamic'    => 1,
                'is_deleted'    => 1,
                'computers_id'  => $ID
            ];
            $params['FIELDS'] = ['id', 'name'];
            $first  = true;
            foreach ($DB->request($computer_vm->getTable(), $params) as $line) {
                if ($first) {
                    echo "<tr>";
                    echo "<th width='10'></th>";
                    echo "<th>" . $computer_vm->getTypeName(Session::getPluralNumber()) . "</th>";
                    echo "<th>" . __('UUID') . "</th>";
                    echo "<th>" . __('Machine') . "</th>";
                    echo "<th>" . __('Automatic inventory') . "</th>";
                    echo "</tr>";
                    $first = false;
                }

                $computer_vm->getFromDB($line['id']);
                echo "<tr class='tab_bg_1'>";
                echo "<td class='center' width='10'>";
                if ($computer_vm->can($line['id'], UPDATE) || $computer_vm->can($line['id'], PURGE)) {
                    $header = true;
                    echo "<input type='checkbox' name='ComputerVirtualMachine[" . $line['id'] . "]'>";
                }
                echo "</td>";
                echo "<td class='left'>" . $computer_vm->getLink() . "</td>";
                echo "<td class='left'>" . $computer_vm->fields['uuid'] . "</td>";

                $url = "";
                if ($link_computer = ComputerVirtualMachine::findVirtualMachine($computer_vm->fields)) {
                    $computer = new Computer();
                    if ($computer->can($link_computer, READ)) {
                        $url  = "<a href='" . $computer->getFormURLWithID($link_computer) . "'>";
                        $url .= $computer->fields["name"] . "</a>";

                        $tooltip = "<table><tr><td>" . __('Name') . "</td><td>" . $computer->fields['name'] .
                            '</td></tr>';
                        $tooltip .= "<tr><td>" . __('Serial number') . "</td><td>" . $computer->fields['serial'] .
                            '</td></tr>';
                        $tooltip .= "<tr><td>" . __('Comments') . "</td><td>" . $computer->fields['comment'] .
                            '</td></tr></table>';

                        $url .= "&nbsp; " . Html::showToolTip($tooltip, ['display' => false]);
                    } else {
                        $url = $computer->fields['name'];
                    }
                }
                echo "<td class='left'>" . $url . "</td>";
                echo "<td class='left'>" . Dropdown::getYesNo($computer_vm->fields['is_dynamic']) . "</td>";
                echo "</tr>\n";
            }
        }

        //Software versions
        $item_sv = new Item_SoftwareVersion();
        $item_sv_table = Item_SoftwareVersion::getTable();

        $iterator = $DB->request([
            'SELECT'    => [
                'isv.id AS id',
                'sv.name AS version',
                's.name AS software'
            ],
            'FROM'      => "{$item_sv_table} AS isv",
            'LEFT JOIN' => [
                'glpi_softwareversions AS sv' => [
                    'FKEY' => [
                        'isv' => 'softwareversions_id',
                        'sv'  => 'id'
                    ]
                ],
                'glpi_softwares AS s'         => [
                    'FKEY' => [
                        'sv'  => 'softwares_id',
                        's'   => 'id'
                    ]
                ]
            ],
            'WHERE'     => [
                'isv.is_deleted'  => 1,
                'isv.is_dynamic'  => 1,
                'isv.items_id'    => $ID,
                'isv.itemtype'    => $itemtype,
            ]
        ]);
        $first  = true;
        foreach ($iterator as $data) {
            if ($first) {
                echo "<tr>";
                echo "<th width='10'></th>";
                echo "<th>" . Software::getTypeName(Session::getPluralNumber()) . "</th>";
                echo "<th>" . SoftwareVersion::getTypeName(Session::getPluralNumber()) . "</th>";
                echo "<th>" . __('Installation date') . "</th>";
                echo "<th>" . __('Automatic inventory') . "</th>";
                echo "</tr>";
                $first = false;
            }

            $item_sv->getFromDB($data['id']);
            echo "<tr class='tab_bg_1'>";
            echo "<td class='center' width='10'>";
            if ($item_sv->can($data['id'], UPDATE) || $item_sv->can($data['id'], PURGE)) {
                $header = true;
                echo "<input type='checkbox' name='Item_SoftwareVersion[" . $data['id'] . "]'>";
            }

            $sversion = new SoftwareVersion();
            $sversion->getFromDB($item_sv->fields['softwareversions_id']);

            $software = new Software();
            $software->getFromDB($sversion->fields['softwares_id']);

            echo "</td>";
            echo "<td class='left'>" . $software->fields['name'] . "</td>"; //no form for item software version
            echo "<td class='left'>" . $sversion->fields['name'] . "</td>";
            echo "<td class='left'>" . Html::convDateTime($item_sv->fields['date_install']) . "</td>";
            echo "<td class='left'>" . Dropdown::getYesNo($item_sv->fields['is_dynamic']) . "</td>";
            echo "</tr>";
        }

        //Software licenses
        $item_sl = new Item_SoftwareLicense();
        $item_sl_table = Item_SoftwareLicense::getTable();

        $iterator = $DB->request([
            'SELECT'    => [
                'isl.id AS id',
                'sl.name AS version',
                's.name AS software'
            ],
            'FROM'      => "{$item_sl_table} AS isl",
            'LEFT JOIN' => [
                'glpi_softwarelicenses AS sl' => [
                    'FKEY' => [
                        'isl' => 'softwarelicenses_id',
                        'sl'  => 'id'
                    ]
                ],
                'glpi_softwares AS s'         => [
                    'FKEY' => [
                        'sl'  => 'softwares_id',
                        's'   => 'id'
                    ]
                ]
            ],
            'WHERE'     => [
                'isl.is_deleted'  => 1,
                'isl.is_dynamic'  => 1,
                'isl.items_id'    => $ID,
                'isl.itemtype'    => $itemtype,
            ]
        ]);

        $first = true;
        foreach ($iterator as $data) {
            if ($first) {
                echo "<tr>";
                echo "<th width='10'></th>";
                echo "<th>" . SoftwareLicense::getTypeName(Session::getPluralNumber()) . "</th>";
                echo "<th>" . __('Version in use') . "</th>";
                echo "<th>" . Software::getTypeName(Session::getPluralNumber()) . "</th>";
                echo "<th>" . __('Automatic inventory') . "</th>";
                echo "</tr>";
                $first = false;
            }

            $item_sl->getFromDB($data['id']);
            echo "<tr class='tab_bg_1'>";
            echo "<td class='center' width='10'>";
            if ($item_sl->can($data['id'], UPDATE) || $item_sl->can($data['id'], PURGE)) {
                $header = true;
                echo "<input type='checkbox' name='Item_SoftwareLicense[" . $data['id'] . "]'>";
            }

            $slicence = new SoftwareLicense();
            $slicence->getFromDB($item_sl->fields['softwarelicenses_id']);

            echo "</td>";
            echo "<td class='left'>" . $slicence->fields['name'] . "</td>"; //no form for item software license

            $software = new Software();
            $software_name = "";
            if ($software->getFromDB($slicence->fields['softwares_id'])) {
                $software_name = $software->fields['name'];
            }
            echo "<td class='left'>" . $software_name . "</td>";

            $sversion = new SoftwareVersion();
            $version_name = "";
            if ($sversion->getFromDB($slicence->fields['softwareversions_id_use'])) {
                $version_name = $sversion->fields['name'];
            }
            echo "<td class='left'>" . $version_name . "</td>";
            echo "<td class='left'>" . Dropdown::getYesNo($item_sl->fields['is_dynamic']) . "</td>";
            echo "</tr>";
        }

        $first  = true;
        $networkport = new NetworkPort();
        $params = ['is_dynamic' => 1,
            'is_deleted' => 1,
            'items_id'   => $ID,
            'itemtype'   => $itemtype
        ];
        $params['FIELDS'] = ['id'];
        foreach ($DB->request($networkport->getTable(), $params) as $line) {
            $networkport->getFromDB($line['id']);
            if ($first) {
                echo "<tr>";
                echo "<th width='10'></th>";
                echo "<th>" . $networkport->getTypeName(Session::getPluralNumber()) . "</th>";
                echo "<th>" . NetworkPortType::getTypeName(1) . "</th>";
                echo "<th>" . __('MAC') . "</th>";
                echo "<th>" . __('Automatic inventory') . "</th>";
                echo "</tr>";
                $first = false;
            }

            echo "<tr class='tab_bg_1'>";
            echo "<td class='center' width='10'>";
            if ($networkport->can($line['id'], UPDATE) || $networkport->can($line['id'], PURGE)) {
                $header = true;
                echo "<input type='checkbox' name='NetworkPort[" . $line['id'] . "]'>";
            }
            echo "</td>";
            echo "<td class='left'>" . $networkport->getLink() . "</td>";
            echo "<td class='left'>" . $networkport->fields['instantiation_type'] . "</td>";
            echo "<td class='left'>" . $networkport->fields['mac'] . "</td>";
            echo "<td class='left'>" . Dropdown::getYesNo($networkport->fields['is_dynamic']) . "</td>";
            echo "</tr>\n";
        }

        $first = true;
        $networkname = new NetworkName();
        $params = [
            'glpi_networknames.is_dynamic' => 1,
            'glpi_networknames.is_deleted' => 1,
            'glpi_networknames.itemtype'   => 'NetworkPort',
            'glpi_networknames.items_id'   => new QueryExpression($DB->quoteName('glpi_networkports.id')),
            'glpi_networkports.items_id'   => $ID,
            'glpi_networkports.itemtype'   => $itemtype
        ];
        $params['FIELDS'] = ['glpi_networknames' => 'id'];
        foreach ($DB->request(['glpi_networknames', 'glpi_networkports'], $params) as $line) {
            $networkname->getFromDB($line['id']);
            if ($first) {
                echo "<tr>";
                echo "<th width='10'></th>";
                echo "<th>" . $networkname->getTypeName(Session::getPluralNumber()) . "</th>";
                echo "<th>" . __('FQDN') . "</th>";
                echo "<th></th>";
                echo "<th>" . __('Automatic inventory') . "</th>";
                echo "</tr>";
                $first = false;
            }

            echo "<tr class='tab_bg_1'>";
            echo "<td class='center' width='10'>";
            if ($networkname->can($line['id'], UPDATE) || $networkname->can($line['id'], PURGE)) {
                $header = true;
                echo "<input type='checkbox' name='NetworkName[" . $line['id'] . "]'>";
            }
            echo "</td>";
            echo "<td class='left'>" . $networkname->getLink() . "</td>";

            $fqdn = new FQDN();
            $fqdn_name = "";
            if ($fqdn->getFromDB($networkname->fields['fqdns_id'])) {
                $fqdn_name = $fqdn->fields['name'];
            }
            echo "<td class='left'>" . $fqdn_name . "</td>";
            echo "<td class='left'></td>";
            echo "<td class='left'>" . Dropdown::getYesNo($networkname->fields['is_dynamic']) . "</td>";
            echo "</tr>\n";
        }

        $first  = true;
        $ipaddress = new IPAddress();
        $params = [
            'glpi_ipaddresses.is_dynamic' => 1,
            'glpi_ipaddresses.is_deleted' => 1,
            'glpi_ipaddresses.itemtype'   => 'NetworkName',
            'glpi_ipaddresses.items_id'   => new QueryExpression($DB->quoteName('glpi_networknames.id')),
            'glpi_networknames.itemtype'  => 'NetworkPort',
            'glpi_networknames.items_id'  => new QueryExpression($DB->quoteName('glpi_networkports.id')),
            'glpi_networkports.items_id'  => $ID,
            'glpi_networkports.itemtype'  => $itemtype
        ];
        $params['FIELDS'] = ['glpi_ipaddresses' => 'id'];
        foreach (
            $DB->request(['glpi_ipaddresses',
                'glpi_networknames',
                'glpi_networkports'
            ], $params) as $line
        ) {
            $ipaddress->getFromDB($line['id']);
            if ($first) {
                echo "<tr>";
                echo "<th width='10'></th>";
                echo "<th>" . $ipaddress->getTypeName(Session::getPluralNumber()) . "</th>";
                echo "<th>" . SoftwareVersion::getTypeName(Session::getPluralNumber()) . "</th>";
                echo "<th></th>";
                echo "<th>" . __('Automatic inventory') . "</th>";
                echo "</tr>";
                $first = false;
            }

            echo "<tr class='tab_bg_1'>";
            echo "<td class='center' width='10'>";
            if ($ipaddress->can($line['id'], UPDATE) || $ipaddress->can($line['id'], PURGE)) {
                $header = true;
                echo "<input type='checkbox' name='IPAddress[" . $line['id'] . "]'>";
            }
            echo "</td>";
            echo "<td class='left'>" . $ipaddress->fields['name'] . "</td>"; //no form for IP address
            echo "<td class='left'>" . $ipaddress->fields['version'] . "</td>";
            echo "<td class='left'></td>";
            echo "<td class='left'>" . Dropdown::getYesNo($ipaddress->fields['is_dynamic']) . "</td>";
            echo "</tr>\n";
        }

        $types = Item_Devices::getDeviceTypes();
        $nb    = 0;
        foreach ($types as $type) {
            $nb += countElementsInTable(
                getTableForItemType($type),
                ['items_id'   => $ID,
                    'itemtype'   => $itemtype,
                    'is_dynamic' => 1,
                    'is_deleted' => 1
                ]
            );
        }
        if ($nb) {
            echo "<tr>";
            echo "<th width='10'></th>";
            echo "<th>" . _n('Component', 'Components', Session::getPluralNumber()) . "</th>";
            echo "<th></th>";
            echo "<th></th>";
            echo "<th>" . __('Automatic inventory') . "</th>";
            echo "</tr>";
            foreach ($types as $type) {
                $type_item = new $type();

                $associated_type  = str_replace('Item_', '', $type);
                $associated_table = getTableForItemType($associated_type);
                $fk               = getForeignKeyFieldForTable($associated_table);

                $iterator = $DB->request([
                    'SELECT'    => [
                        'i.id',
                        't.designation AS name'
                    ],
                    'FROM'      => getTableForItemType($type) . ' AS i',
                    'LEFT JOIN' => [
                        "$associated_table AS t"   => [
                            'ON' => [
                                't'   => 'id',
                                'i'   => $fk
                            ]
                        ]
                    ],
                    'WHERE'     => [
                        'itemtype'     => $itemtype,
                        'items_id'     => $ID,
                        'is_dynamic'   => 1,
                        'is_deleted'   => 1
                    ]
                ]);

                foreach ($iterator as $data) {
                    echo "<tr class='tab_bg_1'>";
                    echo "<td class='center' width='10'>";
                    if ($type_item->can($data['id'], UPDATE) || $type_item->can($data['id'], PURGE)) {
                        $header = true;
                        echo "<input type='checkbox' name='" . $type . "[" . $data['id'] . "]'>";
                    }
                    echo "</td>";
                    $object_item_type = new $type();
                    $object_item_type->getFromDB($data['id']);
                    $object_link = "<a href='" . $object_item_type->getLinkURL() . "'" . $data['name'] . ">" . $data['name'] . "</a>";

                    echo "<td class='left'>";
                    echo $object_link;
                    echo "</td>";
                    echo "<td></td>";
                    echo "<td></td>";
                    echo "<td class='left'>" . Dropdown::getYesNo($object_item_type->fields['is_dynamic']) . "</td>";

                    echo "</tr>\n";
                }
            }
        }

        // Show deleted DatabaseInstance
        $data = $DB->request([
            'SELECT' => 'id',
            'FROM' => DatabaseInstance::getTable(),
            'WHERE' => [
                DatabaseInstance::getTableField('is_dynamic') => 1,
                DatabaseInstance::getTableField('is_deleted') => 1,
                DatabaseInstance::getTableField('items_id')   =>  $ID,
                DatabaseInstance::getTableField('itemtype')   => $itemtype,
            ]
        ]);
        if (count($data)) {
            // Print header
            echo "<tr>";
            echo "<th width='10'></th>";
            echo "<th>" . DatabaseInstance::getTypeName(Session::getPluralNumber()) . "</th>";
            echo "<th>" . __('Name') . "</th>";
            echo "<th>" . _n('Version', 'Versions', 1) . "</th>";
            echo "<th>" . __('Automatic inventory') . "</th>";
            echo "</tr>";
        }

        foreach ($data as $row) {
            $database_instance = DatabaseInstance::getById($row['id']);
            echo "<tr class='tab_bg_1'>";
            echo "<td class='center' width='10'>";
            if ($database_instance->can($row['id'], UPDATE) || $database_instance->can($row['id'], PURGE)) {
                $header = true;
                echo "<input type='checkbox' name='DatabaseInstance[" . $row['id'] . "]'>";
            }
            echo "</td>";
            echo "<td class='left'>" . $database_instance->getLink() . "</td>";
            echo "<td class='left'>" . $database_instance->getName() . "</td>";
            echo "<td class='left'>" . $database_instance->fields['version'] . "</td>";
            echo "<td class='left'>" . Dropdown::getYesNo($database_instance->fields['is_dynamic']) . "</td>";
            echo "</tr>\n";
        }

        // Show deleted Domain_Item
        $data = $DB->request([
            'SELECT' => '*',
            'FROM' => Domain_Item::getTable(),
            'WHERE' => [
                Domain_Item::getTableField('is_dynamic') => 1,
                Domain_Item::getTableField('is_deleted') => 1,
                Domain_Item::getTableField('items_id')   =>  $ID,
                Domain_Item::getTableField('itemtype')   => $itemtype,
            ]
        ]);
        if (count($data)) {
            // Print header
            echo "<tr>";
            echo "<th width='10'></th>";
            echo "<th>" . Domain::getTypeName(Session::getPluralNumber()) . "</th>";
            echo "<th>" . DomainRelation::getTypeName(1) . "</th>";
            echo "<th></th><th></th>";
            echo "</tr>";
        }

        foreach ($data as $row) {
            $domain_item = new Domain_Item();
            $domain = new Domain();
            $domain_relation = new DomainRelation();

            $link = '';
            if ($domain->getFromDB($row['domains_id'])) {
                $link = $domain->getLink();
            }

            $relation_name = "";
            if ($domain_relation->getFromDB($row['domainrelations_id'])) {
                $relation_name = $domain_relation->getName();
            }

            echo "<tr class='tab_bg_1'>";
            echo "<td class='center' width='10'>";
            if ($domain_item->can($row['id'], UPDATE) || $domain_item->can($row['id'], PURGE)) {
                $header = true;
                echo "<input type='checkbox' name='Domain_Item[" . $row['id'] . "]'>";
            }
            echo "</td>";
            echo "<td class='left'>" . $link . "</td>";
            echo "<td class='left'>" . $relation_name . "</td>";
            echo "<td></td>";
            echo "<td></td>";
            echo "</tr>\n";
        }

        if ($header) {
            echo "<tr><th>";
            echo "</th><th colspan='4'>&nbsp</th></tr>\n";
            echo "</table>";

            $formname = 'lock_form';
            echo "<table width='950px'>";
            $arrow = "fas fa-level-up-alt";

            echo "<tr>";
            echo "<td><i class='$arrow fa-flip-horizontal fa-lg mx-2'></i></td>";
            echo "<td class='center' style='white-space:nowrap;'>";
            echo "<a onclick= \"if ( markCheckboxes('$formname') ) return false;\" href='#'>" . __('Check all') . "</a></td>";
            echo "<td>/</td>";
            echo "<td class='center' style='white-space:nowrap;'>";
            echo "<a onclick= \"if ( unMarkCheckboxes('$formname') ) return false;\" href='#'>" . __('Uncheck all') . "</a></td>";
            echo "<td class='left' width='80%'>";

            echo "<input type='submit' name='unlock' ";
            echo "value=\"" . addslashes(_sx('button', 'Unlock')) . "\" class='btn btn-primary'>&nbsp;";

            echo "<input type='submit' name='purge' ";
            echo "value=\"" . addslashes(_sx('button', 'Delete permanently')) . "\" class='btn btn-primary'>&nbsp;";
            echo "</td></tr>";
            echo "</table>";
        } else {
            echo "<tr class='tab_bg_2'>";
            echo "<td class='center' colspan='5'>" . __('No locked item') . "</td></tr>";
            echo "</table>";
        }
        Html::closeForm();

        echo "</div>\n";
    }


    /**
     * @see CommonGLPI::getTabNameForItem()
     *
     * @param $item               CommonGLPI object
     * @param $withtemplate       (default 0)
     **/
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if ($item->isDynamic() && $item->can($item->fields['id'], UPDATE)) {
            return Lock::getTypeName(Session::getPluralNumber());
        }
        return '';
    }


    /**
     * @param $item            CommonGLPI object
     * @param $tabnum          (default 1)
     * @param $withtemplate    (default 0)
     **/
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if ($item->isDynamic() && $item->can($item->fields['id'], UPDATE)) {
            self::showForItem($item);
        }
        return true;
    }


    /**
     * Get infos to build an SQL query to get locks fields in a table
     *
     * @param string $itemtype      itemtype of the item to look for locked fields
     * @param string $baseitemtype  itemtype of the based item
     *
     * @return array  which contains necessary information to build the SQL query
     **/
    public static function getLocksQueryInfosByItemType($itemtype, $baseitemtype)
    {
        /** @var \DBmysql $DB */
        global $DB;

        $condition = [];
        $table     = false;
        $field     = '';
        $type      = $itemtype;

        switch ($itemtype) {
            case 'Peripheral':
            case 'Monitor':
            case 'Printer':
            case 'Phone':
                $condition = ['itemtype'   => $itemtype,
                    'is_dynamic' => 1,
                    'is_deleted' => 1
                ];
                $table     = 'glpi_computers_items';
                $field     = 'computers_id';
                $type      = 'Computer_Item';
                break;

            case 'NetworkPort':
                $condition = ['itemtype'   => $baseitemtype,
                    'is_dynamic' => 1,
                    'is_deleted' => 1
                ];
                $table     = 'glpi_networkports';
                $field     = 'items_id';
                break;

            case 'NetworkName':
                $condition = [
                    'glpi_networknames.is_dynamic' => 1,
                    'glpi_networknames.is_deleted' => 1,
                    'glpi_networknames.itemtype'   => 'NetworkPort',
                    'glpi_networknames.items_id'   => new QueryExpression($DB->quoteName('glpi_networkports.id')),
                    'glpi_networkports.itemtype'   => $baseitemtype
                ];
                $condition['FIELDS']
                       = ['glpi_networknames' => 'id'];
                $table     = ['glpi_networknames', 'glpi_networkports'];
                $field     = 'glpi_networkports.items_id';
                break;

            case 'IPAddress':
                $condition = [
                    'glpi_ipaddresses.is_dynamic'   => 1,
                    'glpi_ipaddresses.is_deleted'   => 1,
                    'glpi_ipaddresses.itemtype'     => 'NetworkName',
                    'glpi_ipaddresses.items_id'     => 'glpi_networknames.id',
                    'glpi_networknames.itemtype'    => 'NetworkPort',
                    'glpi_networknames.items_id'    => 'glpi_networkports.id',
                    'glpi_networkports.itemtype'    => $baseitemtype
                ];
                $condition['FIELDS']
                       = ['glpi_ipaddresses' => 'id'];
                $table     = ['glpi_ipaddresses', 'glpi_networknames', 'glpi_networkports'];
                $field     = 'glpi_networkports.items_id';
                break;

            case 'Item_Disk':
                $condition = [
                    'is_dynamic' => 1,
                    'is_deleted' => 1,
                    'itemtype'   => $itemtype
                ];
                $table     = Item_Disk::getTable();
                $field     = 'items_id';
                break;

            case 'ComputerVirtualMachine':
                $condition = [
                    'is_dynamic' => 1,
                    'is_deleted' => 1,
                    'itemtype'   => $itemtype
                ];
                $table     = 'glpi_computervirtualmachines';
                $field     = 'computers_id';
                break;

            case 'SoftwareVersion':
                $condition = [
                    'is_dynamic' => 1,
                    'is_deleted' => 1,
                    'itemtype'   => $itemtype
                ];
                $table     = 'glpi_items_softwareversions';
                $field     = 'items_id';
                $type      = 'Item_SoftwareVersion';
                break;

            default:
               // Devices
                if (preg_match('/^Item\_Device/', $itemtype)) {
                    $condition = ['itemtype'   => $baseitemtype,
                        'is_dynamic' => 1,
                        'is_deleted' => 1
                    ];
                    $table     = getTableForItemType($itemtype);
                    $field     = 'items_id';
                }
        }

        return ['condition' => $condition,
            'table'     => $table,
            'field'     => $field,
            'type'      => $type
        ];
    }


    /**
     * @since 0.85
     *
     * @see CommonDBTM::getMassiveActionsForItemtype()
     **/
    public static function getMassiveActionsForItemtype(
        array &$actions,
        $itemtype,
        $is_deleted = false,
        CommonDBTM $checkitem = null
    ) {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $action_unlock_component = __CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'unlock_component';
        $action_unlock_fields = __CLASS__ . MassiveAction::CLASS_ACTION_SEPARATOR . 'unlock_fields';

        if (
            Session::haveRight(strtolower($itemtype), UPDATE)
            && in_array($itemtype, $CFG_GLPI['inventory_types'] + $CFG_GLPI['inventory_lockable_objects'])
        ) {
            $actions[$action_unlock_component] = __('Unlock components');
            $actions[$action_unlock_fields] = __('Unlock fields');
        }
    }


    /**
     * @since 0.85
     *
     * @see CommonDBTM::showMassiveActionsSubForm()
     **/
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'unlock_component':
                $types = ['Monitor'                => _n('Monitor', 'Monitors', Session::getPluralNumber()),
                    'Peripheral'             => Peripheral::getTypeName(Session::getPluralNumber()),
                    'Printer'                => Printer::getTypeName(Session::getPluralNumber()),
                    'SoftwareVersion'        => SoftwareVersion::getTypeName(Session::getPluralNumber()),
                    'NetworkPort'            => NetworkPort::getTypeName(Session::getPluralNumber()),
                    'NetworkName'            => NetworkName::getTypeName(Session::getPluralNumber()),
                    'IPAddress'              => IPAddress::getTypeName(Session::getPluralNumber()),
                    'Item_Disk'              => Item_Disk::getTypeName(Session::getPluralNumber()),
                    'Device'                 => _n('Component', 'Components', Session::getPluralNumber()),
                    'ComputerVirtualMachine' => ComputerVirtualMachine::getTypeName(Session::getPluralNumber())
                ];

                echo __('Select the type of the item that must be unlock');
                echo "<br><br>\n";

                Dropdown::showFromArray(
                    'attached_item',
                    $types,
                    ['multiple' => true,
                        'size'     => 5,
                        'values'   => array_keys($types)
                    ]
                );

                echo "<br><br>" . Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
            break;
            case 'unlock_fields':
                $related_itemtype = $ma->getItemtype(false);
                $lockedfield = new Lockedfield();
                $fields = $lockedfield->getFieldsToLock($related_itemtype);

                echo __('Select fields of the item that must be unlock');
                echo "<br><br>\n";
                Dropdown::showFromArray(
                    'attached_fields',
                    $fields,
                    [
                        'multiple' => true,
                        'size'     => 5
                    ]
                );
                echo "<br><br>" . Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
            break;
        }
        return false;
    }


    /**
     * @since 0.85
     *
     * @see CommonDBTM::processMassiveActionsForOneItemtype()
     **/
    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $baseitem,
        array $ids
    ) {
        /** @var \DBmysql $DB */
        global $DB;

        switch ($ma->getAction()) {
            case 'unlock_fields':
                $input = $ma->getInput();
                if (isset($input['attached_fields'])) {
                    $base_itemtype = $baseitem->getType();
                    foreach ($ids as $id) {
                        $lock_fields_name = [];
                        foreach ($input['attached_fields'] as $fields) {
                            list($itemtype, $field) = explode(' - ', $fields);
                            $lock_fields_name[] = $field;
                        }
                        $lockfield = new Lockedfield();
                        $res = $lockfield->deleteByCriteria([
                            "itemtype" => $base_itemtype,
                            "items_id" => $id,
                            "field" => $lock_fields_name,
                            "is_global" => 0
                        ]);
                        if ($res) {
                            $ma->itemDone($base_itemtype, $id, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($base_itemtype, $id, MassiveAction::ACTION_KO);
                        }
                    }
                }
                return;
            case 'unlock_component':
                $input = $ma->getInput();
                if (isset($input['attached_item'])) {
                    $attached_items = $input['attached_item'];
                    if (($device_key = array_search('Device', $attached_items)) !== false) {
                        unset($attached_items[$device_key]);
                        $attached_items = array_merge($attached_items, Item_Devices::getDeviceTypes());
                    }
                    $links = [];
                    foreach ($attached_items as $attached_item) {
                        $infos = self::getLocksQueryInfosByItemType($attached_item, $baseitem->getType());
                        if ($item = getItemForItemtype($infos['type'])) {
                             $infos['item'] = $item;
                             $links[$attached_item] = $infos;
                        }
                    }
                    foreach ($ids as $id) {
                        $action_valid = false;
                        foreach ($links as $infos) {
                            $infos['condition'][$infos['field']] = $id;
                            $locked_items = $DB->request($infos['table'], $infos['condition']);

                            if ($locked_items->count() === 0) {
                                $action_valid = true;
                                continue;
                            }
                            foreach ($locked_items as $data) {
                             // Restore without history
                                $action_valid = $infos['item']->restore(['id' => $data['id']]);
                            }
                        }

                        $baseItemType = $baseitem->getType();
                        if ($action_valid) {
                            $ma->itemDone($baseItemType, $id, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($baseItemType, $id, MassiveAction::ACTION_KO);

                            $erroredItem = new $baseItemType();
                            $erroredItem->getFromDB($id);
                            $ma->addMessage($erroredItem->getErrorMessage(ERROR_ON_ACTION));
                        }
                    }
                }
                return;
        }
    }
}
