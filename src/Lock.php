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
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QuerySubQuery;
use Glpi\DBAL\QueryUnion;
use Glpi\Plugin\Hooks;
use Glpi\Search\SearchOption;

use function Safe\ob_get_clean;
use function Safe\ob_start;

/**
 * This class manages locks
 * Lock management is available for objects and link between objects. It relies on the use of
 * a is_dynamic field, to incidate if item supports lock, and is_deleted field to incidate if the
 * item or link is locked
 * By setting is_deleted to 0 again, the item is unlocked.
 *
 * Note : GLPI's core supports locks for objects. It's up to the external inventory tool to manage
 * locks for fields
 *
 * @since 0.84
 * @see ObjectLock - Object-level locks
 * @see Lockedfield - Field-level locks
 **/
class Lock extends CommonGLPI
{
    public static function getTypeName($nb = 0)
    {
        return _n('Lock', 'Locks', $nb);
    }

    public static function getIcon()
    {
        return "ti ti-lock";
    }

    /**
     * Display form to unlock fields and links
     *
     * @param CommonDBTM $item the source item
     **/
    public static function showForItem(CommonDBTM $item)
    {
        global $CFG_GLPI, $DB;

        $ID       = $item->getID();
        $itemtype = $item::class;

        //If user doesn't have update right on the item, lock form must not be displayed
        if (!$item->isDynamic() || !$item->can($item->fields['id'], UPDATE)) {
            return false;
        }

        // language=Twig
        $list_info_alert_template = <<<TWIG
            <div class="alert alert-info d-flex align-items-center" role="alert">
                <i class="ti ti-info-circle fs-1"></i>
                <span class="ms-2">
                    <span class="alert-title">{{ alert_title }}</span>
                    <br>
                    {{ alert_content }}
                </span>
            </div>
TWIG;

        echo TemplateRenderer::getInstance()->renderFromStringTemplate($list_info_alert_template, [
            'alert_title' => __("A locked field is a manually modified field."),
            'alert_content' => __("The automatic inventory will no longer modify this field, unless you unlock it."),
        ]);

        $entries = [];
        $lockedfield = new Lockedfield();
        $lockedfield_table = $lockedfield::getTable();
        if ($lockedfield->isHandled($item)) {
            $subquery = [];

            // get locked field for current itemtype
            $subquery[] = new QuerySubQuery([
                'SELECT' => $lockedfield_table . ".*",
                'FROM'   => $lockedfield_table,
                'WHERE'  => [
                    'OR' => [
                        [
                            $lockedfield_table . '.itemtype'  => $itemtype,
                            $lockedfield_table . '.items_id'  => $ID,
                        ], [
                            $lockedfield_table . '.itemtype'  => $itemtype,
                            $lockedfield_table . '.is_global' => 1,
                        ],
                    ],
                ],
            ]);

            // get locked field for other lockable object
            foreach ($CFG_GLPI['inventory_lockable_objects'] as $lockable_itemtype) {
                $lockable_itemtype_table = getTableForItemType($lockable_itemtype);
                $lockable_object = getItemForItemtype($lockable_itemtype);
                $query  = [
                    'SELECT' => $lockedfield_table . ".*",
                    'FROM'   => $lockedfield_table,
                    'LEFT JOIN' => [
                        $lockable_itemtype_table   => [
                            'FKEY'   => [
                                $lockedfield_table  => 'items_id',
                                $lockable_itemtype_table   => 'id',
                            ],
                        ],
                    ],
                    'WHERE'  => [
                        'OR' => [
                            [
                                $lockedfield_table . '.itemtype'  => $lockable_itemtype,
                                $lockedfield_table . '.items_id'  => new QueryExpression($DB::quoteName($lockable_itemtype_table . '.id')),
                            ], [
                                $lockedfield_table . '.itemtype'  => $lockable_itemtype,
                                $lockedfield_table . '.is_global' => 1,
                            ],
                        ],
                    ],
                ];

                if ($lockable_object instanceof CommonDBConnexity) {
                    $connexity_criteria = $lockable_itemtype::getSQLCriteriaToSearchForItem($itemtype, $ID);
                    if ($connexity_criteria === null) {
                        continue;
                    }
                    $query['WHERE'][] = $connexity_criteria['WHERE'];
                    if ($lockable_object->isField('is_deleted')) {
                        $query['WHERE'][] = [
                            $lockable_object::getTableField('is_deleted') => 0,
                        ];
                    }
                } elseif (in_array($lockable_itemtype, $CFG_GLPI['directconnect_types'], true)) {
                    //we need to restrict scope with Asset_PeripheralAsset to prevent loading of all lockedfield
                    $query['LEFT JOIN'][Asset_PeripheralAsset::getTable()]
                    = [
                        'FKEY'   => [
                            Asset_PeripheralAsset::getTable() => 'items_id_peripheral',
                            $lockable_itemtype::getTable()    => 'id',
                        ],
                    ];
                    $query['WHERE'][] = [
                        Asset_PeripheralAsset::getTable() . '.' . 'itemtype_asset' => $itemtype,
                        Asset_PeripheralAsset::getTable() . '.' . 'items_id_asset' => $ID,
                        Asset_PeripheralAsset::getTable() . '.is_deleted'          => 0,
                    ];
                } elseif ($lockable_object->isField('itemtype') && $lockable_object->isField('items_id')) {
                    $query['WHERE'][] = [
                        $lockable_itemtype::getTable() . '.itemtype'  => $itemtype,
                        $lockable_itemtype::getTable() . '.items_id'  => $ID,
                    ];
                    if ($lockable_object->isField('is_deleted')) {
                        $query['WHERE'][] = [
                            $lockable_object::getTableField('is_deleted') => 0,
                        ];
                    }
                }
                $subquery[] = new QuerySubQuery($query);
            }

            $union = new QueryUnion($subquery);
            $locked_iterator = $DB->request([
                'FROM' => $union,
            ]);

            //get fields labels
            $search_options = SearchOption::getOptionsForItemtype($itemtype);
            foreach ($search_options as $search_option) {
                //exclude SO added by dropdown part (to get real name)
                //ex : Manufacturer != Firmware : Manufacturer
                if (isset($search_option['table']) && $search_option['table'] === getTableForItemType($itemtype)) {
                    if (isset($search_option['linkfield'])) {
                        $so_fields[$search_option['linkfield']] = $search_option['name'];
                    } elseif (isset($search_option['field'])) {
                        $so_fields[$search_option['field']] = $search_option['name'];
                    }
                }
            }

            foreach ($locked_iterator as $row) {
                $field_label = $row['field'];
                if (isset($so_fields[$row['field']])) {
                    $field_label = $so_fields[$row['field']];
                } elseif (isForeignKeyField($row['field'])) {
                    // on fkey, we can try to retrieve the object
                    $object = getItemtypeForForeignKeyField($row['field']);
                    if ($object !== null) {
                        $field_label = $object::getTypeName(1);
                    }
                }

                if ($row['is_global']) {
                    $field_label .= ' (' . __('Global') . ')';
                }

                //load object
                $object = getItemForItemtype($row['itemtype']);
                $object->getFromDB($row['items_id']);

                $default_itemtype_label = $row['itemtype']::getTypeName();
                $default_object_link    = $object->getLink();
                $default_itemtype       = $row['itemtype'];
                $default_items_id       = null;

                // get real type name from Item_Devices
                // ex: get 'Hard drives' instead of 'Hard drive items'
                if (get_parent_class($row['itemtype']) === Item_Devices::class) {
                    $default_itemtype =  $row['itemtype']::$itemtype_2;
                    $default_items_id =  $row['itemtype']::$items_id_2;
                    $default_itemtype_label = $row['itemtype']::$itemtype_2::getTypeName();
                    // get real type name from CommonDBRelation
                    // ex: get 'Operating System' instead of 'Item operating systems'
                } elseif (get_parent_class($row['itemtype']) === CommonDBRelation::class) {
                    // For CommonDBRelation
                    // $itemtype_1 / $items_id_1 and $itemtype_2 / $items_id_2 can be inverted

                    // ex: Item_Software have
                    // $itemtype_1 = 'itemtype';
                    // $items_id_1 = 'items_id';
                    // $itemtype_2 = 'SoftwareVersion';
                    // $items_id_2 = 'softwareversions_id';
                    if (str_starts_with($row['itemtype']::$itemtype_1, 'itemtype')) {
                        $default_itemtype =  $row['itemtype']::$itemtype_2;
                        $default_items_id =  $row['itemtype']::$items_id_2;
                        $default_itemtype_label = $row['itemtype']::$itemtype_2::getTypeName();
                    } else {
                        // ex: Item_OperatingSystem have
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
                if ($default_items_id !== null && is_a($row['itemtype'], CommonDBRelation::class, true) && is_a($default_itemtype, CommonDBTM::class, true)) {
                    $related_object = new $default_itemtype();
                    $related_object->getFromDB($object->fields[$default_items_id]);
                    $name = htmlescape($related_object->getName());
                    $default_object_link = "<a href='" . htmlescape($object->getLinkURL()) . "'" . $name . ">" . $name . "</a>";
                }

                $entries[] = [
                    'itemtype' => Lockedfield::class,
                    'id'       => $row['id'],
                    'showmassiveactions' => $row['is_global'] === 0 && ($lockedfield->can($row['id'], UPDATE) || $lockedfield->can($row['id'], PURGE)),
                    'field'    => $field_label,
                    'type'     => $default_itemtype_label,
                    'link'     => $default_object_link,
                    'value'    => $row['value'],
                ];
            }
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'super_header' => _n('Locked field', 'Locked fields', Session::getPluralNumber()),
            'columns' => [
                'field' => _n('Field', 'Fields', 1),
                'type' => __('Itemtype'),
                'link' => _n('Link', 'Links', 1),
                'value' => __('Last inventoried value'),
            ],
            'formatters' => [
                'link' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => count(array_filter($entries, static fn($entry) => $entry['showmassiveactions'])) > 0,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . mt_rand(),
            ],
        ]);

        // Open the form used for the custom checkboxes to unlock items (Not using massive actions)
        $twig_params = [
            'itemtype' => $itemtype,
            'id'       => $ID,
        ];
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
            <form method="post" id="lock_form" name="lock_form" class="mt-5" action="{{ 'Lock'|itemtype_form_path }}">
                <input type="hidden" name="id" value="{{ id }}">
                <input type="hidden" name="itemtype" value="{{ itemtype }}">
TWIG, $twig_params);

        $subtables = [];
        //Use a hook to allow external inventory tools to manage per field lock
        ob_start();
        Plugin::doHookFunction(Hooks::DISPLAY_LOCKED_FIELDS, [
            'item'   => $item,
            'header' => false,
        ]);
        $results = ob_get_clean();
        $subtables[] = [
            'raw_body' => $results,
        ];

        echo TemplateRenderer::getInstance()->renderFromStringTemplate($list_info_alert_template, [
            'alert_title' => __("A locked item is a manually deleted connection, for example a monitor."),
            'alert_content' => __("The automatic inventory will no longer handle this item, unless you unlock it."),
        ]);

        if (
            in_array($itemtype, Asset_PeripheralAsset::getPeripheralHostItemtypes(), true)
            || $itemtype === Computer::class && count($CFG_GLPI['directconnect_types'])
        ) {
            $types = $CFG_GLPI['directconnect_types'];
            $it = $DB->request([
                'SELECT' => ['id', 'itemtype_peripheral', 'items_id_peripheral'],
                'FROM'   => Asset_PeripheralAsset::getTable(),
                'WHERE'  => [
                    'itemtype_asset'      => $itemtype,
                    'items_id_asset'      => $ID,
                    'is_dynamic'          => 1,
                    'is_deleted'          => 1,
                    'itemtype_peripheral' => $CFG_GLPI['directconnect_types'],
                ],
            ]);
            $results = iterator_to_array($it);
            // Calculate reverse lookup array to avoid array_search in the callback
            $types_flipped = array_flip($types);
            // Sort results to match the order of the types in $CFG_GLPI['directconnect_types']
            usort($results, static fn($a, $b) => $types_flipped[$a['itemtype_peripheral']] - $types_flipped[$b['itemtype_peripheral']]);

            $subtable = [
                'columns' => [
                    'chk' => '',
                    'type' => __('Asset type'),
                    'item' => _n('Item', 'Items', 1),
                    'serial' => __('Serial number'),
                    'inventory' => __('Inventory number'),
                    'is_dynamic' => __('Automatic inventory'),
                ],
                'formatters' => [
                    'chk' => 'raw_html',
                    'item' => 'raw_html',
                ],
                'entries' => [],
            ];

            foreach ($results as $result) {
                /** @var CommonDBTM $peripheral */
                $peripheral = getItemForItemtype($result['itemtype_peripheral']);
                if ($peripheral === false || $peripheral->getFromDB($result['items_id_peripheral']) === false) {
                    // ignore orphan data
                    continue;
                }
                $relation_item = new Asset_PeripheralAsset();
                $show_checkbox = $relation_item->can($result['id'], UPDATE) || $relation_item->can($result['id'], PURGE);
                $subtable['entries'][] = [
                    'chk' => $show_checkbox ? "<input type='checkbox' name='Glpi\\Asset\\Asset_PeripheralAsset[" . ((int) $result['id']) . "]'>" : '',
                    'type' => $peripheral::getTypeName(),
                    'item' => $peripheral->getLink(),
                    'serial' => $peripheral->fields['serial'],
                    'inventory' => $peripheral->fields['otherserial'],
                    'is_dynamic' => Dropdown::getYesNo($peripheral->fields['is_dynamic']),
                ];
            }
            $subtables[] = $subtable;
        }

        if (in_array($itemtype, $CFG_GLPI['disk_types'], true)) {
            //items disks
            $item_disk = new Item_Disk();
            $item_disks = $DB->request([
                'FROM'  => $item_disk::getTable(),
                'WHERE' => [
                    'is_dynamic'   => 1,
                    'is_deleted'   => 1,
                    'items_id'     => $ID,
                    'itemtype'     => $itemtype,
                ],
            ]);
            $subtable = [
                'nosort' => true,
                'nofilter' => true,
                'columns' => [
                    'chk' => '',
                    'item' => $item_disk::getTypeName(1),
                    'partition' => __('Partition'),
                    'mountpoint' => __('Mount point'),
                    'is_dynamic' => __('Automatic inventory'),
                ],
                'formatters' => [
                    'chk' => 'raw_html',
                    'item' => 'raw_html',
                ],
                'entries' => [],
            ];

            foreach ($item_disks as $line) {
                $item_disk->getFromResultSet($line);
                $show_checkbox = $item_disk->can($line['id'], UPDATE) || $item_disk->can($item_disk->getID(), PURGE);
                $subtable['entries'][] = [
                    'chk' => $show_checkbox ? "<input type='checkbox' name='Item_Disk[{$item_disk->getID()}]'>" : '',
                    'item' => $item_disk->getLink(),
                    'partition' => $item_disk->fields['device'],
                    'mountpoint' => $item_disk->fields['mountpoint'],
                    'is_dynamic' => Dropdown::getYesNo($item_disk->fields['is_dynamic']),
                ];
            }
            $subtables[] = $subtable;
        }

        if (in_array($itemtype, $CFG_GLPI['remote_management_types'], true)) {
            $iterator = $DB->request([
                'FROM'  => Item_RemoteManagement::getTable(),
                'WHERE' => [
                    'is_dynamic'   => 1,
                    'is_deleted'   => 1,
                    'items_id'     => $ID,
                    'itemtype'     => $itemtype,
                ],
            ]);

            $subtable = [
                'nosort' => true,
                'nofilter' => true,
                'columns' => [
                    'chk' => '',
                    'item' => Item_RemoteManagement::getTypeName(1),
                    'type' => _n('Type', 'Types', 1),
                    'is_dynamic' => __('Automatic inventory'),
                ],
                'formatters' => [
                    'chk' => 'raw_html',
                    'item' => 'raw_html',
                ],
                'entries' => [],
            ];

            foreach ($iterator as $line) {
                $remote_management = new Item_RemoteManagement();
                $remote_management->getFromResultSet($line);
                $show_checkbox = $remote_management->can($line['id'], UPDATE) || $remote_management->can($remote_management->getID(), PURGE);
                $subtable['entries'][] = [
                    'chk' => $show_checkbox ? "<input type='checkbox' name='Item_RemoteManagement[{$remote_management->getID()}]'>" : '',
                    'item' => $remote_management->getLink(),
                    'type' => $remote_management->fields['type'],
                    'is_dynamic' => Dropdown::getYesNo($remote_management->fields['is_dynamic']),
                ];
            }

            $subtables[] = $subtable;
        }

        $item_vm = new ItemVirtualMachine();
        $item_vms = $DB->request([
            'FROM'  => $item_vm::getTable(),
            'WHERE' => [
                'is_dynamic'   => 1,
                'is_deleted'   => 1,
                'itemtype'     => $itemtype,
                'items_id'     => $ID,
            ],
        ]);
        $subtable = [
            'columns' => [
                'chk' => '',
                'type' => $item_vm::getTypeName(1),
                'uuid' => __('UUID'),
                'machine' => __('Machine'),
                'is_dynamic' => __('Automatic inventory'),
            ],
            'formatters' => [
                'chk' => 'raw_html',
                'machine' => 'raw_html',
            ],
            'entries' => [],
        ];

        foreach ($item_vms as $line) {
            $item_vm->getFromResultSet($line);
            $show_checkbox = $item_vm->can($line['id'], UPDATE) || $item_vm->can($item_vm->getID(), PURGE);

            $url = "";
            if ($link_item = ItemVirtualMachine::findVirtualMachine($item_vm->fields)) {
                $item = new $itemtype();
                if ($item->can($link_item, READ)) {
                    $url  = "<a href='" . htmlescape($item->getFormURLWithID($link_item)) . "'>";
                    $url .= htmlescape($item->fields["name"]) . "</a>";

                    $tooltip = "<table><tr><td>" . __s('Name') . "</td><td>" . htmlescape($item->fields['name'])
                        . '</td></tr>';
                    if (isset($item->fields['serial'])) {
                        $tooltip .= "<tr><td>" . __s('Serial number') . "</td><td>" . htmlescape($item->fields['serial'])
                            . '</td></tr>';
                    }
                    if (isset($item->fields['comment'])) {
                        $tooltip .= "<tr><td>" . __s('Comments') . "</td><td>" . htmlescape($item->fields['comment'])
                            . '</td></tr></table>';
                    }

                    $url .= "&nbsp; " . Html::showToolTip($tooltip, ['display' => false]);
                } else {
                    $url = htmlescape($item->fields['name']);
                }
            }
            $subtable['entries'][] = [
                'chk' => $show_checkbox ? "<input type='checkbox' name='ItemVirtualMachine[{$item_vm->getID()}]'>" : '',
                'type' => $item_vm::getTypeName(),
                'uuid' => $item_vm->fields['uuid'],
                'machine' => $url,
                'is_dynamic' => Dropdown::getYesNo($item_vm->fields['is_dynamic']),
            ];
        }
        $subtables[] = $subtable;

        // Software versions
        $item_sv = new Item_SoftwareVersion();
        $item_sv_table = Item_SoftwareVersion::getTable();

        $iterator = $DB->request([
            'SELECT'    => [
                'isv.id AS id',
                'sv.name AS version',
                's.name AS software',
            ],
            'FROM'      => "{$item_sv_table} AS isv",
            'LEFT JOIN' => [
                'glpi_softwareversions AS sv' => [
                    'FKEY' => [
                        'isv' => 'softwareversions_id',
                        'sv'  => 'id',
                    ],
                ],
                'glpi_softwares AS s'         => [
                    'FKEY' => [
                        'sv'  => 'softwares_id',
                        's'   => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'isv.is_deleted'  => 1,
                'isv.is_dynamic'  => 1,
                'isv.items_id'    => $ID,
                'isv.itemtype'    => $itemtype,
            ],
        ]);
        $subtable = [
            'columns' => [
                'chk' => '',
                'software' => Software::getTypeName(1),
                'version' => SoftwareVersion::getTypeName(1),
                'date_install' => __('Installation date'),
                'is_dynamic' => __('Automatic inventory'),
            ],
            'formatters' => [
                'chk' => 'raw_html',
                'date_install' => 'datetime',
            ],
            'entries' => [],
        ];

        foreach ($iterator as $data) {
            $item_sv->getFromDB($data['id']);
            $show_checkbox = $item_sv->can($data['id'], UPDATE) || $item_sv->can($data['id'], PURGE);
            $subtable['entries'][] = [
                'chk' => $show_checkbox ? "<input type='checkbox' name='Item_SoftwareVersion[{$item_sv->getID()}]'>" : '',
                'software' => $data['software'],
                'version' => $data['version'],
                'date_install' => $item_sv->fields['date_install'],
                'is_dynamic' => Dropdown::getYesNo($item_sv->fields['is_dynamic']),
            ];
        }
        $subtables[] = $subtable;

        //Software licenses
        $item_sl = new Item_SoftwareLicense();
        $item_sl_table = Item_SoftwareLicense::getTable();

        $iterator = $DB->request([
            'SELECT'    => [
                'isl.id AS id',
                'sl.name AS license',
                's.name AS software',
            ],
            'FROM'      => "{$item_sl_table} AS isl",
            'LEFT JOIN' => [
                'glpi_softwarelicenses AS sl' => [
                    'FKEY' => [
                        'isl' => 'softwarelicenses_id',
                        'sl'  => 'id',
                    ],
                ],
                'glpi_softwares AS s'         => [
                    'FKEY' => [
                        'sl'  => 'softwares_id',
                        's'   => 'id',
                    ],
                ],
            ],
            'WHERE'     => [
                'isl.is_deleted'  => 1,
                'isl.is_dynamic'  => 1,
                'isl.items_id'    => $ID,
                'isl.itemtype'    => $itemtype,
            ],
        ]);

        $subtable = [
            'columns' => [
                'chk' => '',
                'license' => SoftwareLicense::getTypeName(1),
                'software' => Software::getTypeName(1),
                'version' => __('Version in use'),
                'is_dynamic' => __('Automatic inventory'),
            ],
            'formatters' => [
                'chk' => 'raw_html',
            ],
            'entries' => [],
        ];

        foreach ($iterator as $data) {
            $item_sl->getFromDB($data['id']);
            $show_checkbox = $item_sl->can($data['id'], UPDATE) || $item_sl->can($data['id'], PURGE);

            $slicence = new SoftwareLicense();
            $slicence->getFromDB($item_sl->fields['softwarelicenses_id']);
            $software = new Software();
            $software_name = "";
            if ($software->getFromDB($slicence->fields['softwares_id'])) {
                $software_name = $software->fields['name'];
            }
            $sversion = new SoftwareVersion();
            $version_name = "";
            if ($sversion->getFromDB($slicence->fields['softwareversions_id_use'])) {
                $version_name = $sversion->fields['name'];
            }

            $subtable['entries'][] = [
                'chk' => $show_checkbox ? "<input type='checkbox' name='Item_SoftwareLicense[{$item_sl->getID()}]'>" : '',
                'license' => $slicence->fields['name'],
                'software' => $software_name,
                'version' => $version_name,
                'is_dynamic' => Dropdown::getYesNo($item_sl->fields['is_dynamic']),
            ];
        }
        $subtables[] = $subtable;

        $networkport = new NetworkPort();
        $networkports = $DB->request([
            'FROM'  => $networkport::getTable(),
            'WHERE' => [
                'is_dynamic' => 1,
                'is_deleted' => 1,
                'items_id'   => $ID,
                'itemtype'   => $itemtype,
            ],
        ]);
        $subtable = [
            'columns' => [
                'chk' => '',
                'item' => NetworkPort::getTypeName(1),
                'port_type' => NetworkPortType::getTypeName(1),
                'mac' => __('MAC'),
                'is_dynamic' => __('Automatic inventory'),
            ],
            'formatters' => [
                'chk' => 'raw_html',
                'item' => 'raw_html',
            ],
            'entries' => [],
        ];

        foreach ($networkports as $line) {
            $networkport->getFromResultSet($line);
            $show_checkbox = $networkport->can($networkport->getID(), UPDATE) || $networkport->can($networkport->getID(), PURGE);
            $subtable['entries'][] = [
                'chk' => $show_checkbox ? "<input type='checkbox' name='NetworkPort[{$networkport->getID()}]'>" : '',
                'item' => $networkport->getLink(),
                'port_type' => $networkport->fields['instantiation_type'],
                'mac' => $networkport->fields['mac'],
                'is_dynamic' => Dropdown::getYesNo($networkport->fields['is_dynamic']),
            ];
        }
        $subtables[] = $subtable;

        $networkname = new NetworkName();
        $networknames = $DB->request([
            'SELECT' => ['glpi_networknames.*'],
            'FROM'  => $networkname::getTable(),
            'INNER JOIN' => [ // These joins are used to filter the network names that are linked to the current item's network ports
                'glpi_networkports' => [
                    'ON' => [
                        'glpi_networknames' => 'items_id',
                        'glpi_networkports' => 'id', [
                            'AND' => [
                                'glpi_networkports.itemtype'  => $itemtype,
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE' => [
                'glpi_networkports.items_id'   => $ID,
                'glpi_networknames.is_dynamic' => 1,
                'glpi_networknames.is_deleted' => 1,
                'glpi_networknames.itemtype'   => 'NetworkPort',
            ],
        ]);
        $subtable = [
            'columns' => [
                'chk' => '',
                'item' => NetworkName::getTypeName(1),
                'fqdn' => __('FQDN'),
                'placeholder' => '',
                'is_dynamic' => __('Automatic inventory'),
            ],
            'formatters' => [
                'chk' => 'raw_html',
                'item' => 'raw_html',
            ],
            'entries' => [],
        ];

        foreach ($networknames as $line) {
            $networkname->getFromResultSet($line);
            $show_checkbox = $networkname->can($networkname->getID(), UPDATE) || $networkname->can($networkname->getID(), PURGE);
            $fqdn = new FQDN();
            $fqdn_name = "";
            if ($fqdn->getFromDB($networkname->fields['fqdns_id'])) {
                $fqdn_name = $fqdn->fields['name'];
            }

            $subtable['entries'][] = [
                'chk' => $show_checkbox ? "<input type='checkbox' name='NetworkName[{$networkname->getID()}]'>" : '',
                'item' => $networkname->getLink(),
                'fqdn' => $fqdn_name,
                'placeholder' => '',
                'is_dynamic' => Dropdown::getYesNo($networkname->fields['is_dynamic']),
            ];
        }
        $subtables[] = $subtable;

        $ipaddress = new IPAddress();
        $ipaddresses = $DB->request([
            'SELECT' => ['glpi_ipaddresses.*'],
            'FROM'  => $ipaddress::getTable(),
            'INNER JOIN' => [ // These joins are used to filter the IP addresses that are linked to the current item's network ports
                'glpi_networknames' => [
                    'ON' => [
                        'glpi_ipaddresses' => 'items_id',
                        'glpi_networknames' => 'id', [
                            'AND' => [
                                'glpi_networknames.itemtype'  => 'NetworkPort',
                            ],
                        ],
                    ],
                ],
                'glpi_networkports' => [
                    'ON' => [
                        'glpi_networknames' => 'items_id',
                        'glpi_networkports' => 'id', [
                            'AND' => [
                                'glpi_networkports.itemtype'  => $itemtype,
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE' => [
                'glpi_networkports.items_id'  => $ID,
                'glpi_ipaddresses.is_dynamic' => 1,
                'glpi_ipaddresses.is_deleted' => 1,
                'glpi_ipaddresses.itemtype'   => 'NetworkName',
            ],
        ]);
        $subtable = [
            'columns' => [
                'chk' => '',
                'item' => IPAddress::getTypeName(1),
                'version' => _n('Version', 'Versions', 1),
                'placeholder' => '',
                'is_dynamic' => __('Automatic inventory'),
            ],
            'formatters' => [
                'chk' => 'raw_html',
            ],
            'entries' => [],
        ];

        foreach ($ipaddresses as $line) {
            $ipaddress->getFromResultSet($line);
            $show_checkbox = $ipaddress->can($ipaddress->getID(), UPDATE) || $ipaddress->can($ipaddress->getID(), PURGE);
            $subtable['entries'][] = [
                'chk' => $show_checkbox ? "<input type='checkbox' name='IPAddress[{$ipaddress->getID()}]'>" : '',
                'item' => $ipaddress->fields['name'],
                'version' => $ipaddress->fields['version'],
                'placeholder' => '',
                'is_dynamic' => Dropdown::getYesNo($ipaddress->fields['is_dynamic']),
            ];
        }
        $subtables[] = $subtable;

        $types = Item_Devices::getDeviceTypes();
        $nb    = 0;
        foreach ($types as $type) {
            $nb += countElementsInTable(
                getTableForItemType($type),
                [
                    'items_id'   => $ID,
                    'itemtype'   => $itemtype,
                    'is_dynamic' => 1,
                    'is_deleted' => 1,
                ]
            );
        }
        if ($nb) {
            $subtable = [
                'columns' => [
                    'chk' => '',
                    'item' => _n('Component', 'Components', 1),
                    'placeholder_1' => '',
                    'placeholder_2' => '',
                    'is_dynamic' => __('Automatic inventory'),
                ],
                'formatters' => [
                    'chk' => 'raw_html',
                    'item' => 'raw_html',
                ],
                'entries' => [],
            ];

            foreach ($types as $type) {
                $type_item = getItemForItemtype($type);

                $associated_type  = str_replace('Item_', '', $type);
                $associated_table = getTableForItemType($associated_type);
                $fk               = getForeignKeyFieldForTable($associated_table);

                $iterator = $DB->request([
                    'SELECT'    => [
                        'i.id',
                        't.designation AS name',
                    ],
                    'FROM'      => getTableForItemType($type) . ' AS i',
                    'LEFT JOIN' => [
                        "$associated_table AS t"   => [
                            'ON' => [
                                't'   => 'id',
                                'i'   => $fk,
                            ],
                        ],
                    ],
                    'WHERE'     => [
                        'itemtype'     => $itemtype,
                        'items_id'     => $ID,
                        'is_dynamic'   => 1,
                        'is_deleted'   => 1,
                    ],
                ]);

                foreach ($iterator as $data) {
                    $show_checkbox = $type_item->can($data['id'], UPDATE) || $type_item->can($data['id'], PURGE);
                    $object_item_type = getItemForItemtype($type);
                    $object_item_type->getFromDB($data['id']);
                    $object_name = htmlescape($data['name']);
                    $object_link = "<a href='" . htmlescape($object_item_type->getLinkURL()) . "'>{$object_name}</a>";

                    $subtable['entries'][] = [
                        'chk' => $show_checkbox ? "<input type='checkbox' name='" . htmlescape("{$type}[{$data['id']}") . "]'>" : '',
                        'item' => $object_link,
                        'placeholder_1' => '',
                        'placeholder_2' => '',
                        'is_dynamic' => Dropdown::getYesNo($object_item_type->fields['is_dynamic']),
                    ];
                }
            }
            $subtables[] = $subtable;
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
            ],
        ]);
        $subtable = [
            'columns' => [
                'chk' => '',
                'item' => DatabaseInstance::getTypeName(1),
                'name' => __('Name'),
                'version' => _n('Version', 'Versions', 1),
                'is_dynamic' => __('Automatic inventory'),
            ],
            'formatters' => [
                'chk' => 'raw_html',
                'item' => 'raw_html',
            ],
            'entries' => [],
        ];

        foreach ($data as $row) {
            $database_instance = DatabaseInstance::getById($row['id']);
            if ($database_instance === false) {
                continue;
            }

            $show_checkbox = $database_instance->can($database_instance->getID(), UPDATE) || $database_instance->can($database_instance->getID(), PURGE);
            $subtable['entries'][] = [
                'chk' => $show_checkbox ? "<input type='checkbox' name='DatabaseInstance[{$database_instance->getID()}]'>" : '',
                'item' => $database_instance->getLink(),
                'name' => $database_instance->getName(),
                'version' => $database_instance->fields['version'],
                'is_dynamic' => Dropdown::getYesNo($database_instance->fields['is_dynamic']),
            ];
        }
        $subtables[] = $subtable;

        // Show deleted Domain_Item
        $data = $DB->request([
            'SELECT' => '*',
            'FROM' => Domain_Item::getTable(),
            'WHERE' => [
                Domain_Item::getTableField('is_dynamic') => 1,
                Domain_Item::getTableField('is_deleted') => 1,
                Domain_Item::getTableField('items_id')   =>  $ID,
                Domain_Item::getTableField('itemtype')   => $itemtype,
            ],
        ]);
        $subtable = [
            'columns' => [
                'chk' => '',
                'item' => Domain::getTypeName(1),
                'relation' => DomainRelation::getTypeName(1),
                'placeholder_1' => '',
                'placeholder_2' => '',
            ],
            'formatters' => [
                'chk' => 'raw_html',
                'item' => 'raw_html',
            ],
            'entries' => [],
        ];

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

            $show_checkbox = $domain_item->can($row['id'], UPDATE) || $domain_item->can($row['id'], PURGE);
            $subtable['entries'][] = [
                'chk' => $show_checkbox ? "<input type='checkbox' name='Domain_Item[" . ((int) $row['id']) . "]'>" : '',
                'item' => $link,
                'relation' => $relation_name,
                'placeholder_1' => '',
                'placeholder_2' => '',
            ];
        }
        $subtables[] = $subtable;

        // This list is unique in that it has different sections for different item types.
        // So, we are using nested datatables.
        $rendered_subtables = array_map(static function ($datatable_params) {
            if (isset($datatable_params['raw_body'])) {
                return !empty($datatable_params['raw_body']) ? ['subtable' => $datatable_params['raw_body']] : '';
            }
            if (count($datatable_params['entries']) === 0) {
                return '';
            }
            // Common Params
            $datatable_params['is_tab'] = true;
            $datatable_params['nosort'] = true;
            $datatable_params['nofilter'] = true;
            $datatable_params['total_number'] = count($datatable_params['entries']);
            $datatable_params['filtered_number'] = count($datatable_params['entries']);
            return ['subtable' => TemplateRenderer::getInstance()->render('components/datatable.html.twig', $datatable_params)];
        }, $subtables);
        // Remove empty subtables
        $rendered_subtables = array_filter($rendered_subtables);

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'table_class_style' => 'table-sm',
            'nofilter' => true,
            'nosort' => true,
            'super_header' => __('Locked items'),
            'no_header' => true, // Only affects the regular columns, not the super header. We don't want column headers for the parent table.
            'columns' => [
                'subtable' => '',
            ],
            'formatters' => [
                'subtable' => 'raw_html',
            ],
            'entries' => $rendered_subtables,
            'total_number' => count($rendered_subtables),
            'filtered_number' => count($rendered_subtables),
            'showmassiveactions' => false,
        ]);

        $twig_params = [
            'check_all_msg' => __('Check all'),
            'uncheck_all_msg' => __('Uncheck all'),
            'unlock_msg' => _x('button', 'Unlock'),
            'purge_msg' => _x('button', 'Delete permanently'),
        ];
        if (count($rendered_subtables) > 0) {
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <div>
                    <i class='ti ti-corner-left-up mx-3'></i>
                    <a onclick="if ( markCheckboxes('lock_form') ) return false;" href='#'>{{ check_all_msg }}</a>
                    <span>/</span>
                    <a onclick="if ( unMarkCheckboxes('lock_form') ) return false;" href='#'>{{ uncheck_all_msg }}</a>
                    <button type="submit" name="unlock" class="btn btn-primary">{{ unlock_msg }}</button>
                    <button type="submit" name="purge" class="btn btn-danger">{{ purge_msg }}</button>
                </div>
TWIG, $twig_params);
        }

        // Close the custom form used for the unlock item checkboxes (not using massive actions)
        // language=Twig
        echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
            </form>
TWIG);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (
            ($item instanceof CommonDBTM)
            && $item->isDynamic()
            && $item->can($item->fields['id'], UPDATE)
        ) {
            return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), 0, $item::getType());
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        if (
            ($item instanceof CommonDBTM)
            && $item->isDynamic()
            && $item->can($item->fields['id'], UPDATE)
        ) {
            self::showForItem($item);
        }
        return true;
    }

    /**
     * Get infos to build an SQL query to get locks fields in a table.
     * The criteria returned will only retrieve the 'id' column of the main table by default.
     *
     * @param class-string<CommonDBTM> $itemtype      itemtype of the item to look for locked fields
     * @param class-string<CommonDBTM> $baseitemtype  itemtype of the based item
     *
     * @return array{criteria: array, field: string, type: class-string<CommonDBTM>} Necessary information to build the SQL query.
     * <ul>
     *     <li>'criteria' array contains the joins and where criteria to apply to the SQL query (DBmysqlIterator format).</li>
     *     <li>'field' refers to the criteria condition key where the item ID should be inserted. This key is not already present in the criteria array.</li>
     *     <li>'type' refers to the class of the item to look for locked fields.</li>
     * </ul>
     **/
    private static function getLocksQueryInfosByItemType($itemtype, $baseitemtype)
    {
        $criteria = [];
        $field     = '';
        $type      = $itemtype;

        switch ($itemtype) {
            case 'Peripheral':
            case 'Monitor':
            case 'Printer':
            case 'Phone':
                $relation_table = Asset_PeripheralAsset::getTable();
                $criteria = [
                    'SELECT' => [$relation_table . '.id'],
                    'FROM' => $relation_table,
                    'WHERE' => [
                        'itemtype_asset'      => $baseitemtype,
                        'itemtype_peripheral' => $itemtype,
                        'is_dynamic'          => 1,
                        'is_deleted'          => 1,
                    ],
                ];
                $field = 'items_id_asset';
                $type  = Asset_PeripheralAsset::class;
                break;

            case 'NetworkPort':
                $criteria = [
                    'SELECT' => ['glpi_networkports.id'],
                    'FROM' => 'glpi_networkports',
                    'WHERE' => [
                        'itemtype'   => $baseitemtype,
                        'is_dynamic' => 1,
                        'is_deleted' => 1,
                    ],
                ];
                $field     = 'items_id';
                break;

            case 'NetworkName':
                $criteria = [
                    'SELECT' => ['glpi_networknames.id'],
                    'FROM' => 'glpi_networknames',
                    'INNER JOIN' => [
                        'glpi_networkports' => [
                            'ON' => [
                                'glpi_networknames' => 'items_id',
                                'glpi_networkports' => 'id', [
                                    'AND' => [
                                        'glpi_networkports.itemtype'  => $baseitemtype,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'WHERE' => [
                        'glpi_networknames.is_dynamic' => 1,
                        'glpi_networknames.is_deleted' => 1,
                        'glpi_networknames.itemtype'   => 'NetworkPort',
                    ],
                ];
                $field     = 'glpi_networkports.items_id';
                break;

            case 'IPAddress':
                $criteria = [
                    'SELECT' => ['glpi_ipaddresses.id'],
                    'FROM' => 'glpi_ipaddresses',
                    'INNER JOIN' => [
                        'glpi_networknames' => [
                            'ON' => [
                                'glpi_ipaddresses' => 'items_id',
                                'glpi_networknames' => 'id', [
                                    'AND' => [
                                        'glpi_networknames.itemtype'  => 'NetworkPort',
                                    ],
                                ],
                            ],
                        ],
                        'glpi_networkports' => [
                            'ON' => [
                                'glpi_networknames' => 'items_id',
                                'glpi_networkports' => 'id', [
                                    'AND' => [
                                        'glpi_networkports.itemtype'  => $baseitemtype,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'WHERE' => [
                        'glpi_ipaddresses.is_dynamic' => 1,
                        'glpi_ipaddresses.is_deleted' => 1,
                        'glpi_ipaddresses.itemtype'   => 'NetworkName',
                    ],
                ];
                $field     = 'glpi_networkports.items_id';
                break;

            case 'Item_Disk':
                $criteria = [
                    'SELECT' => ['glpi_items_disks.id'],
                    'FROM' => 'glpi_items_disks',
                    'WHERE' => [
                        'is_dynamic' => 1,
                        'is_deleted' => 1,
                        'itemtype'   => $itemtype,
                    ],
                ];
                $field     = 'items_id';
                break;

            case 'ItemVirtualMachine':
                $table = $itemtype::getTable();
                $criteria = [
                    'SELECT' => ["$table.id"],
                    'FROM' => $table,
                    'WHERE' => [
                        'is_dynamic' => 1,
                        'is_deleted' => 1,
                        'itemtype'   => $itemtype,
                    ],
                ];
                $field     = 'items_id';
                break;

            case 'SoftwareVersion':
                $criteria = [
                    'SELECT' => ['glpi_items_softwareversions.id'],
                    'FROM' => 'glpi_items_softwareversions',
                    'WHERE' => [
                        'is_dynamic' => 1,
                        'is_deleted' => 1,
                        'itemtype'   => $itemtype,
                    ],
                ];
                $field     = 'items_id';
                $type      = 'Item_SoftwareVersion';
                break;

            default:
                // Devices
                if (str_starts_with($itemtype, "Item_Device")) {
                    $table = getTableForItemType($itemtype);
                    $criteria = [
                        'SELECT' => ["$table.id"],
                        'FROM' => $table,
                        'WHERE' => [
                            'itemtype'   => $itemtype,
                            'is_dynamic' => 1,
                            'is_deleted' => 1,
                        ],
                    ];
                    $field     = 'items_id';
                }
        }

        return [
            'criteria' => $criteria,
            'field' => $field,
            'type' => $type,
        ];
    }

    public static function getMassiveActionsForItemtype(
        array &$actions,
        $itemtype,
        $is_deleted = false,
        ?CommonDBTM $checkitem = null
    ) {
        global $CFG_GLPI;

        if (!is_subclass_of($itemtype, CommonDBTM::class)) {
            return;
        }

        $action_unlock_component = self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'unlock_component';
        $action_unlock_fields = self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'unlock_fields';

        if (
            Session::haveRight($itemtype::$rightname, UPDATE)
            && in_array($itemtype, $CFG_GLPI['inventory_types'] + $CFG_GLPI['inventory_lockable_objects'], true)
        ) {
            $actions[$action_unlock_component] = __s('Unlock components');
            $actions[$action_unlock_fields] = __s('Unlock fields');
        }
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        switch ($ma->getAction()) {
            case 'unlock_component':
                $types = [
                    'Monitor'                => _n('Monitor', 'Monitors', Session::getPluralNumber()),
                    'Peripheral'             => Peripheral::getTypeName(Session::getPluralNumber()),
                    'Printer'                => Printer::getTypeName(Session::getPluralNumber()),
                    'SoftwareVersion'        => SoftwareVersion::getTypeName(Session::getPluralNumber()),
                    'NetworkPort'            => NetworkPort::getTypeName(Session::getPluralNumber()),
                    'NetworkName'            => NetworkName::getTypeName(Session::getPluralNumber()),
                    'IPAddress'              => IPAddress::getTypeName(Session::getPluralNumber()),
                    'Item_Disk'              => Item_Disk::getTypeName(Session::getPluralNumber()),
                    'Device'                 => _n('Component', 'Components', Session::getPluralNumber()),
                    'ItemVirtualMachine'     => ItemVirtualMachine::getTypeName(Session::getPluralNumber()),
                ];

                echo __s('Select the type of the item that must be unlock');
                echo "<br><br>";

                Dropdown::showFromArray(
                    'attached_item',
                    $types,
                    ['multiple' => true,
                        'size'     => 5,
                        'values'   => array_keys($types),
                    ]
                );

                echo "<br><br>" . Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
            case 'unlock_fields':
                $related_itemtype = $ma->getItemtype(false);
                $lockedfield = new Lockedfield();
                $fields = $lockedfield->getFieldsToLock($related_itemtype);

                echo __s('Select fields of the item that must be unlock');
                echo "<br><br>";
                Dropdown::showFromArray(
                    'attached_fields',
                    $fields,
                    [
                        'multiple' => true,
                        'size'     => 5,
                    ]
                );
                echo "<br><br>" . Html::submit(_x('button', 'Post'), ['name' => 'massiveaction']);
                return true;
        }
        return false;
    }

    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $baseitem,
        array $ids
    ) {
        global $DB;

        switch ($ma->getAction()) {
            case 'unlock_fields':
                $input = $ma->getInput();
                if (isset($input['attached_fields'])) {
                    $base_itemtype = $baseitem->getType();
                    foreach ($ids as $id) {
                        $lock_fields_name = [];
                        foreach ($input['attached_fields'] as $fields) {
                            [, $field] = explode(' - ', $fields);
                            $lock_fields_name[] = $field;
                        }
                        $lockfield = new Lockedfield();
                        $res = $lockfield->deleteByCriteria([
                            "itemtype" => $base_itemtype,
                            "items_id" => $id,
                            "field" => $lock_fields_name,
                            "is_global" => 0,
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
                    if (($device_key = array_search('Device', $attached_items, true)) !== false) {
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
                            $infos['criteria']['WHERE'][$infos['field']] = $id;
                            $locked_items = $DB->request($infos['criteria']);

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

                            $erroredItem = getItemForItemtype($baseItemType);
                            $erroredItem->getFromDB($id);
                            $ma->addMessage($erroredItem->getErrorMessage(ERROR_ON_ACTION));
                        }
                    }
                }
                return;
        }
    }
}
