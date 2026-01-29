<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2026 Teclib' and contributors.
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
use Glpi\Asset\AssetDefinitionManager;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QuerySubQuery;
use Glpi\Features\Clonable;

/**
 * State Class
 **/
class State extends CommonTreeDropdown
{
    /** @use Clonable<static> */
    use Clonable;

    public $can_be_translated       = true;

    public static $rightname               = 'state';

    public static function getTypeName($nb = 0)
    {
        return _n('Status of items', 'Statuses of items', $nb);
    }

    public static function getFieldLabel()
    {
        return __('Status');
    }

    public function getAdditionalFields()
    {
        $fields   = parent::getAdditionalFields();

        $fields[] = [
            'label' => __('Show items with this status in assistance'),
            'name'  => 'is_helpdesk_visible',
            'type'  => 'bool',
        ];

        $fields[] = ['label' => __('Visibility'),
            'name'  => 'header',
            'list'  => false,
        ];

        foreach ($this->getvisibilityFields() as $type => $field) {
            // $type is a class-string representing an item class (eg. Computer::class)
            /** @var class-string<CommonDBTM> $type */
            $fields[] = ['name'  => $field,
                'label' => $type::getTypeName(Session::getPluralNumber()),
                'type'  => 'bool',
                'list'  => true,
            ];
        }

        return $fields;
    }


    /**
     * States for behaviour config
     *
     * @param string $lib to add for -1 value (default '')
     * @param bool $is_inheritable
     * @return array
     */
    final public static function getBehaviours(string $lib = "", bool $is_inheritable = false): array
    {
        global $DB;

        $elements = ["0" => __('Keep status')];

        if ($lib) {
            $elements["-1"] = $lib;
        }

        if ($is_inheritable) {
            $elements["-2"] = __('Inheritance of the parent entity');
        }

        $iterator = $DB->request([
            'SELECT' => ['id', 'name'],
            'FROM'   => 'glpi_states',
            'ORDER'  => 'name',
        ]);

        foreach ($iterator as $data) {
            $elements[$data["id"]] = sprintf(__('Set status: %s'), $data["name"]);
        }

        return $elements;
    }

    /**
     * Dropdown of states for behaviour config
     *
     * @param string $name  select name
     * @param string $lib   to add for -1 value (default '')
     * @param int $value
     * @param bool $is_inheritable
     * @used-by templates/pages/admin/entity/assets.html.twig
     *
     * @return void
     */
    public static function dropdownBehaviour($name, $lib = "", $value = 0, $is_inheritable = false)
    {
        $elements = self::getBehaviours($lib, $is_inheritable);
        Dropdown::showFromArray($name, $elements, ['value' => $value]);
    }

    /**
     * @return void
     */
    public static function showSummary()
    {
        global $CFG_GLPI, $DB;

        $state_type = $CFG_GLPI["state_types"];
        $states     = [];

        foreach ($state_type as $key => $itemtype) {
            if ($item = getItemForItemtype($itemtype)) {
                if (!$item::canView()) {
                    unset($state_type[$key]);
                } else {
                    $table = getTableForItemType($itemtype);
                    $WHERE = [];
                    if ($item->maybeDeleted()) {
                        $WHERE["$table.is_deleted"] = 0;
                    }
                    if ($item->maybeTemplate()) {
                        $WHERE["$table.is_template"] = 0;
                    }
                    $WHERE += getEntitiesRestrictCriteria($table);
                    $iterator = $DB->request([
                        'SELECT' => [
                            'states_id',
                            'COUNT'  => '* AS cpt',
                        ],
                        'FROM'   => $table,
                        'WHERE'  => $WHERE,
                        'GROUP'  => 'states_id',
                    ]);

                    foreach ($iterator as $data) {
                        $states[$data["states_id"]][$itemtype] = $data["cpt"];
                    }
                }
            }
        }

        $columns = [
            'state' => __('Status'),
        ];
        $formatters = [
            'state' => 'raw_html',
        ];
        $entries = [];
        $total = [];

        foreach ($state_type as $key => $itemtype) {
            if ($item = getItemForItemtype($itemtype)) {
                $columns[$itemtype] = $item::getTypeName(Session::getPluralNumber());
                $formatters[$itemtype] = 'integer';
                $total[$itemtype] = 0;
            } else {
                unset($state_type[$key]);
            }
        }

        $iterator = $DB->request([
            'FROM'   => 'glpi_states',
            'WHERE'  => getEntitiesRestrictCriteria('glpi_states', '', '', true),
            'ORDER'  => 'completename',
        ]);

        // No state
        $tot = 0;
        $no_state_entry = [
            'state' => '---',
        ];
        foreach ($state_type as $itemtype) {
            $count = $states[0][$itemtype] ?? 0;
            $no_state_entry[$itemtype] = $count;
            $total[$itemtype] += $count;
            $tot              += $count;
        }
        $no_state_entry['total'] = $tot;
        $entries[] = $no_state_entry;

        foreach ($iterator as $data) {
            $tot = 0;
            $opt = [
                'reset'    => 'reset',
                'sort'     => 1,
                'start'    => 0,
                'criteria' => [
                    '0' => [
                        'value' => '$$$$' . $data['id'],
                        'searchtype' => 'contains',
                        'field' => 31,
                    ],
                ],
            ];


            $url = htmlescape(AllAssets::getSearchURL()) . '?' . Toolbox::append_params($opt, '&amp;');
            $entry = [
                'state' => '<a href="' . $url . '">' . htmlescape($data["completename"]) . '</a>',
            ];
            foreach ($state_type as $itemtype) {
                $count = $states[$data["id"]][$itemtype] ?? 0;
                $entry[$itemtype] = $count;
                $total[$itemtype] += $count;
                $tot              += $count;
            }
            $entry['total'] = $tot;
            $entries[] = $entry;
        }

        $columns['total'] = __('Total');
        $footer = [
            'state' => __('Total'),
        ];
        foreach ($total as $itemtype => $value) {
            $footer[$itemtype] = $value;
        }
        $footer['total'] = array_sum($total);

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => $columns,
            'formatters' => $formatters,
            'entries' => $entries,
            'footers' => [$footer],
            'footer_class' => 'fw-bold',
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => false,
        ]);
    }

    public function getEmpty()
    {
        if (!parent::getEmpty()) {
            return false;
        }

        // initialize is_visible_* fields at true to keep the same behavior as in older versions
        foreach ($this->getvisibilityFields() as $field) {
            $this->fields[$field] = 1;
        }

        $this->fields['is_helpdesk_visible'] = 1;

        return true;
    }

    public function cleanDBonPurge()
    {
        Rule::cleanForItemCriteria($this);
        Rule::cleanForItemCriteria($this, '_states_id%');
    }

    public function prepareInputForAdd($input)
    {
        if (!isset($input['states_id'])) {
            $input['states_id'] = 0;
        }
        if (!$this->isUnique($input)) {
            Session::addMessageAfterRedirect(
                htmlescape(sprintf(__s('%1$s must be unique!'), static::getTypeName(1))),
                false,
                ERROR
            );
            return false;
        }

        $input = parent::prepareInputForAdd($input);

        $state = new self();
        // Get visibility information from parent if not set
        if (isset($input['states_id']) && $state->getFromDB($input['states_id'])) {
            foreach ($this->getvisibilityFields() as $type => $field) {
                if (!isset($input[$field]) && isset($state->fields[$field])) {
                    $input[$field] = $state->fields[$field];
                }
            }
        }
        return $input;
    }

    public function post_addItem()
    {
        $state_visibility = new DropdownVisibility();
        foreach ($this->getvisibilityFields() as $itemtype => $field) {
            if (isset($this->input[$field])) {
                $state_visibility->add([
                    'itemtype' => self::class,
                    'items_id' => $this->fields['id'],
                    'visible_itemtype'  => $itemtype,
                    'is_visible' => $this->input[$field],
                ]);
            }
        }

        parent::post_addItem();
    }

    public function getSpecificMassiveActions($checkitem = null)
    {
        $actions = parent::getSpecificMassiveActions($checkitem);

        if (Session::haveRight(self::$rightname, UPDATE)) {
            $actions[self::class . MassiveAction::CLASS_ACTION_SEPARATOR . 'update_visibility']
                = __('Visibility');
        }

        return $actions;
    }

    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {
        if ($ma->getAction() !== 'update_visibility') {
            return parent::showMassiveActionsSubForm($ma);
        }

        // Itemtype choice
        global $CFG_GLPI;
        $itemtype_options = [];

        if (!empty($CFG_GLPI['state_types']) && is_array($CFG_GLPI['state_types'])) {
            foreach ($CFG_GLPI['state_types'] as $itemtype) {
                // Ensure the itemtype/class exists and provides a type name
                if (class_exists($itemtype) && method_exists($itemtype, 'getTypeName')) {
                    $itemtype_options[$itemtype] = $itemtype::getTypeName(Session::getPluralNumber());
                }
            }
        }

        // Add custom asset definitions
        foreach (AssetDefinitionManager::getInstance()->getDefinitions() as $definition) {
            $asset_class = $definition->getAssetClassName();
            $itemtype_options[$asset_class] = $definition->getFriendlyName() ?: $asset_class;
        }

        echo __('Asset type') . '<br>';
        Dropdown::showFromArray('visible_itemtype', $itemtype_options, [
            'display_emptychoice' => false,
            'multiple' => true,
        ]);
        echo '<br><br>';

        // Visibility choice
        echo __('Visible') . '<br>';
        Dropdown::showYesNo('is_visible', 1);
        echo '<br><br>';

        // submit button
        echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction', 'class' => 'btn btn-primary']);

        return true;
    }

    public static function processMassiveActionsForOneItemtype(MassiveAction $ma, CommonDBTM $item, array $ids)
    {
        /** @var State $item */
        switch ($ma->getAction()) {
            case 'update_visibility':
                $form_input = $ma->getInput();

                $visible_itemtype_raw = $form_input['visible_itemtype'] ?? null;
                $is_visible = isset($form_input['is_visible']) ? (int) $form_input['is_visible'] : null;

                // On invalid input, skip all processing
                if (!is_array($visible_itemtype_raw) || $is_visible === null) {
                    foreach ($ids as $id) {
                        $ma->itemDone($item::class, $id, MassiveAction::NO_ACTION);
                    }
                    return;
                }

                $visible_itemtypes = is_array($visible_itemtype_raw)
                    ? $visible_itemtype_raw
                    : [$visible_itemtype_raw];

                // Validate itemtypes against allowed types and skip processing for invalid ones
                global $CFG_GLPI;
                $allowed_itemtypes = $CFG_GLPI['state_types'] ?? [];
                foreach (AssetDefinitionManager::getInstance()->getDefinitions() as $definition) {
                    $allowed_itemtypes[] = $definition->getAssetClassName();
                }

                foreach ($visible_itemtypes as $visible_itemtype) {
                    if ($visible_itemtype !== '' && !in_array($visible_itemtype, $allowed_itemtypes, true)) {
                        foreach ($ids as $id) {
                            $ma->itemDone($item::class, $id, MassiveAction::NO_ACTION);
                        }
                        return;
                    }
                }

                // apply visibility changes
                $_dropdown_visibility = new DropdownVisibility();

                foreach ($ids as $id) {
                    // Can user update this item ?
                    if (!$item->can($id, UPDATE)) {
                        $ma->itemDone($item::class, $id, MassiveAction::ACTION_NORIGHT);
                        continue;
                    }

                    $all_visibilities_processed = true;
                    foreach ($visible_itemtypes as $visible_itemtype) {
                        // skip empty itemtypes
                        if ($visible_itemtype === '') {
                            continue;
                        }

                        // update visibility entry
                        if ($_dropdown_visibility->getFromDBByCrit([
                            'itemtype'         => $item::class,
                            'items_id'         => $id,
                            'visible_itemtype' => $visible_itemtype,
                        ])) {
                            // update existing
                            if (!$_dropdown_visibility->update([
                                'id'         => $_dropdown_visibility->fields['id'],
                                'is_visible' => $is_visible,
                            ])) {
                                $all_visibilities_processed = false;
                            }
                        } else {
                            // create new
                            if (!$_dropdown_visibility->add([
                                'itemtype'         => $item::class,
                                'items_id'         => $id,
                                'visible_itemtype' => $visible_itemtype,
                                'is_visible'       => $is_visible,
                            ])) {
                                $all_visibilities_processed = false;
                            }
                        }
                    }

                    $ma->itemDone(
                        $item::class,
                        $id,
                        $all_visibilities_processed ? MassiveAction::ACTION_OK : MassiveAction::ACTION_KO
                    );
                }

                return;
        }

        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }

    public function post_updateItem($history = true)
    {
        $state_visibility = new DropdownVisibility();
        foreach ($this->getvisibilityFields() as $itemtype => $field) {
            if (isset($this->input[$field])) {
                if ($state_visibility->getFromDBByCrit(['itemtype' => self::class, 'items_id' => $this->input['id'], 'visible_itemtype' => $itemtype])) {
                    $state_visibility->update([
                        'id' => $state_visibility->fields['id'],
                        'is_visible' => $this->input[$field],
                    ]);
                } else {
                    $state_visibility->add([
                        'itemtype' => self::class,
                        'items_id' => $this->fields['id'],
                        'visible_itemtype' => $itemtype,
                        'is_visible' => $this->input[$field],
                    ]);
                }
            }
        }

        parent::post_updateItem();
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'                 => '21',
            'table'              => DropdownVisibility::getTable(),
            'field'              => 'is_visible',
            'name'               => sprintf(__('%1$s - %2$s'), __('Visibility'), Computer::getTypeName(Session::getPluralNumber())),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype' => 'itemtypeonly',
                'table'      => static::getTable(),
                'condition' => [
                    'NEWTABLE.visible_itemtype' => 'Computer',
                    'NEWTABLE.items_id' => new QueryExpression('REFTABLE.id'),
                ],
            ],
            'massiveaction'      => false, // managed by custom massive action @see getSpecificMassiveActions()
        ];

        $tab[] = [
            'id'                 => '22',
            'table'              => DropdownVisibility::getTable(),
            'field'              => 'is_visible',
            // on ne défini pas linkfield -> il est défini automatiquement là : src/Glpi/Search/SearchOption.php:400 en se basant sur le nom de la table
            'name'               => sprintf(
                __('%1$s - %2$s'),
                __('Visibility'),
                SoftwareVersion::getTypeName(Session::getPluralNumber())
            ),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype' => 'itemtypeonly',
                'table'      => static::getTable(),
                'condition' => [
                    'NEWTABLE.visible_itemtype' => 'SoftwareVersion',
                    'NEWTABLE.items_id' => new QueryExpression('REFTABLE.id'),
                ],
            ],
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '23',
            'table'              => DropdownVisibility::getTable(),
            'field'              => 'is_visible',
            'name'               => sprintf(__('%1$s - %2$s'), __('Visibility'), Monitor::getTypeName(Session::getPluralNumber())),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype' => 'itemtypeonly',
                'table'      => static::getTable(),
                'condition' => [
                    'NEWTABLE.visible_itemtype' => 'Monitor',
                    'NEWTABLE.items_id' => new QueryExpression('REFTABLE.id'),
                ],
            ],
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '24',
            'table'              => DropdownVisibility::getTable(),
            'field'              => 'is_visible',
            'name'               => sprintf(__('%1$s - %2$s'), __('Visibility'), Printer::getTypeName(Session::getPluralNumber())),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype' => 'itemtypeonly',
                'table'      => static::getTable(),
                'condition' => [
                    'NEWTABLE.visible_itemtype' => 'Printer',
                    'NEWTABLE.items_id' => new QueryExpression('REFTABLE.id'),
                ],
            ],
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '25',
            'table'              => DropdownVisibility::getTable(),
            'field'              => 'is_visible',
            'name'               => sprintf(__('%1$s - %2$s'), __('Visibility'), Peripheral::getTypeName(Session::getPluralNumber())),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype' => 'itemtypeonly',
                'table'      => static::getTable(),
                'condition' => [
                    'NEWTABLE.visible_itemtype' => 'Peripheral',
                    'NEWTABLE.items_id' => new QueryExpression('REFTABLE.id'),
                ],
            ],
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '26',
            'table'              => DropdownVisibility::getTable(),
            'field'              => 'is_visible',
            'name'               => sprintf(__('%1$s - %2$s'), __('Visibility'), Phone::getTypeName(Session::getPluralNumber())),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype' => 'itemtypeonly',
                'table'      => static::getTable(),
                'condition' => [
                    'NEWTABLE.visible_itemtype' => 'Phone',
                    'NEWTABLE.items_id' => new QueryExpression('REFTABLE.id'),
                ],
            ],
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '27',
            'table'              => DropdownVisibility::getTable(),
            'field'              => 'is_visible',
            'name'               => sprintf(
                __('%1$s - %2$s'),
                __('Visibility'),
                NetworkEquipment::getTypeName(Session::getPluralNumber())
            ),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype' => 'itemtypeonly',
                'table'      => static::getTable(),
                'condition' => [
                    'NEWTABLE.visible_itemtype' => 'NetworkEquipment',
                    'NEWTABLE.items_id' => new QueryExpression('REFTABLE.id'),
                ],
            ],
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '28',
            'table'              => DropdownVisibility::getTable(),
            'field'              => 'is_visible',
            'name'               => sprintf(
                __('%1$s - %2$s'),
                __('Visibility'),
                SoftwareLicense::getTypeName(Session::getPluralNumber())
            ),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype' => 'itemtypeonly',
                'table'      => static::getTable(),
                'condition' => [
                    'NEWTABLE.visible_itemtype' => 'SoftwareLicense',
                    'NEWTABLE.items_id' => new QueryExpression('REFTABLE.id'),
                ],
            ],
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '29',
            'table'              => DropdownVisibility::getTable(),
            'field'              => 'is_visible',
            'name'               => sprintf(
                __('%1$s - %2$s'),
                __('Visibility'),
                Certificate::getTypeName(Session::getPluralNumber())
            ),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype' => 'itemtypeonly',
                'table'      => static::getTable(),
                'condition' => [
                    'NEWTABLE.visible_itemtype' => 'Certificate',
                    'NEWTABLE.items_id' => new QueryExpression('REFTABLE.id'),
                ],
            ],
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '30',
            'table'              => DropdownVisibility::getTable(),
            'field'              => 'is_visible',
            'name'               => sprintf(
                __('%1$s - %2$s'),
                __('Visibility'),
                Rack::getTypeName(Session::getPluralNumber())
            ),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype' => 'itemtypeonly',
                'table'      => static::getTable(),
                'condition' => [
                    'NEWTABLE.visible_itemtype' => 'Rack',
                    'NEWTABLE.items_id' => new QueryExpression('REFTABLE.id'),
                ],
            ],
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '31',
            'table'              => DropdownVisibility::getTable(),
            'field'              => 'is_visible',
            'name'               => sprintf(
                __('%1$s - %2$s'),
                __('Visibility'),
                Line::getTypeName(Session::getPluralNumber())
            ),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype' => 'itemtypeonly',
                'table'      => static::getTable(),
                'condition' => [
                    'NEWTABLE.visible_itemtype' => 'Line',
                    'NEWTABLE.items_id' => new QueryExpression('REFTABLE.id'),
                ],
            ],
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '32',
            'table'              => DropdownVisibility::getTable(),
            'field'              => 'is_visible',
            'name'               => sprintf(
                __('%1$s - %2$s'),
                __('Visibility'),
                Enclosure::getTypeName(Session::getPluralNumber())
            ),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype' => 'itemtypeonly',
                'table'      => static::getTable(),
                'condition' => [
                    'NEWTABLE.visible_itemtype' => 'Enclosure',
                    'NEWTABLE.items_id' => new QueryExpression('REFTABLE.id'),
                ],
            ],
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '33',
            'table'              => DropdownVisibility::getTable(),
            'field'              => 'is_visible',
            'name'               => sprintf(
                __('%1$s - %2$s'),
                __('Visibility'),
                PDU::getTypeName(Session::getPluralNumber())
            ),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype' => 'itemtypeonly',
                'table'      => static::getTable(),
                'condition' => [
                    'NEWTABLE.visible_itemtype' => 'PDU',
                    'NEWTABLE.items_id' => new QueryExpression('REFTABLE.id'),
                ],
            ],
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '34',
            'table'              => DropdownVisibility::getTable(),
            'field'              => 'is_visible',
            'name'               => sprintf(
                __('%1$s - %2$s'),
                __('Visibility'),
                Cluster::getTypeName(Session::getPluralNumber())
            ),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype' => 'itemtypeonly',
                'table'      => static::getTable(),
                'condition' => [
                    'NEWTABLE.visible_itemtype' => 'Cluster',
                    'NEWTABLE.items_id' => new QueryExpression('REFTABLE.id'),
                ],
            ],
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '35',
            'table'              => DropdownVisibility::getTable(),
            'field'              => 'is_visible',
            'name'               => sprintf(
                __('%1$s - %2$s'),
                __('Visibility'),
                PassiveDCEquipment::getTypeName(Session::getPluralNumber())
            ),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype' => 'itemtypeonly',
                'table'      => static::getTable(),
                'condition' => [
                    'NEWTABLE.visible_itemtype' => 'PassiveDCEquipment',
                    'NEWTABLE.items_id' => new QueryExpression('REFTABLE.id'),
                ],
            ],
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '36',
            'table'              => DropdownVisibility::getTable(),
            'field'              => 'is_visible',
            'name'               => sprintf(
                __('%1$s - %2$s'),
                __('Visibility'),
                Contract::getTypeName(Session::getPluralNumber())
            ),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype' => 'itemtypeonly',
                'table'      => static::getTable(),
                'condition' => [
                    'NEWTABLE.visible_itemtype' => 'Contract',
                    'NEWTABLE.items_id' => new QueryExpression('REFTABLE.id'),
                ],
            ],
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '37',
            'table'              => DropdownVisibility::getTable(),
            'field'              => 'is_visible',
            'name'               => sprintf(
                __('%1$s - %2$s'),
                __('Visibility'),
                Appliance::getTypeName(Session::getPluralNumber())
            ),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype' => 'itemtypeonly',
                'table'      => static::getTable(),
                'condition' => [
                    'NEWTABLE.visible_itemtype' => 'Appliance',
                    'NEWTABLE.items_id' => new QueryExpression('REFTABLE.id'),
                ],
            ],
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '38',
            'table'              => DropdownVisibility::getTable(),
            'field'              => 'is_visible',
            'name'               => sprintf(
                __('%1$s - %2$s'),
                __('Visibility'),
                Cable::getTypeName(Session::getPluralNumber())
            ),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype' => 'itemtypeonly',
                'table'      => static::getTable(),
                'condition' => [
                    'NEWTABLE.visible_itemtype' => 'Cable',
                    'NEWTABLE.items_id' => new QueryExpression('REFTABLE.id'),
                ],
            ],
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '39',
            'table'              => DropdownVisibility::getTable(),
            'field'              => 'is_visible',
            'name'               => sprintf(
                __('%1$s - %2$s'),
                __('Visibility'),
                DatabaseInstance::getTypeName(Session::getPluralNumber())
            ),
            'datatype'           => 'bool',
            'joinparams'         => [
                'jointype' => 'itemtypeonly',
                'table'      => static::getTable(),
                'condition' => [
                    'NEWTABLE.visible_itemtype' => 'DatabaseInstance',
                    'NEWTABLE.items_id' => new QueryExpression('REFTABLE.id'),
                ],
            ],
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => '40',
            'table'              => static::getTable(),
            'field'              => 'is_helpdesk_visible',
            'name'               => __('Show items with this status in assistance'),
            'datatype'           => 'bool',
        ];

        // custom Assets
        $custom_asset_search_id = 4000; // random choice, see @todo below
        foreach (AssetDefinitionManager::getInstance()->getDefinitions() as $definition) {
            $tab[] = [
                'id'                 => $custom_asset_search_id++, // @todo I don't know what to put here...
                'table'              => DropdownVisibility::getTable(),
                'field'              => 'is_visible',
                'linkfield'          => strtolower(str_replace('\'', '_', 'is_visible_' . $definition->getAssetClassName())),
                'name'               => sprintf(
                    __('%1$s - %2$s'),
                    __('Visibility'),
                    $definition->getFriendlyName()
                ),
                'datatype'           => 'bool',
                'joinparams'         => [
                    'jointype' => 'itemtypeonly',
                    'table'      => static::getTable(),
                    'condition' => [
                        'NEWTABLE.visible_itemtype' => $definition->getAssetClassName(),
                        'NEWTABLE.items_id' => new QueryExpression('REFTABLE.id'),
                    ],
                ],
                'massiveaction'      => false,
            ];
        };

        return $tab;
    }

    public function prepareInputForUpdate($input)
    {
        if (!$this->isUnique($input)) {
            Session::addMessageAfterRedirect(
                htmlescape(sprintf(__s('%1$s must be unique per level!'), static::getTypeName(1))),
                false,
                ERROR
            );
            return false;
        }
        return parent::prepareInputForUpdate($input);
    }

    /**
     * Checks that this state is unique given the new field values.
     *    Unique fields checked:
     *       - states_id
     *       - name
     * @param array $input Array of field names and values
     * @return bool True if the new/updated record will be unique
     */
    public function isUnique($input)
    {
        global $DB;

        $unicity_fields = ['states_id', 'name'];

        $has_changed = false;
        $where = [];
        foreach ($unicity_fields as $unicity_field) {
            if (
                isset($input[$unicity_field])
                && (!isset($this->fields[$unicity_field]) || $input[$unicity_field] != $this->fields[$unicity_field])
            ) {
                $has_changed = true;
            }
            if (isset($input[$unicity_field])) {
                $where[$unicity_field] = $input[$unicity_field];
            }
        }
        if (!$has_changed) {
            // state has not changed; this is OK.
            return true;
        }

        // Apply collate
        if (isset($where['name'])) {
            $collate = $DB->use_utf8mb4 ? "utf8mb4_bin" : "utf8_bin";
            $where['name'] = new QueryExpression($DB->quote($where['name']) . " COLLATE $collate");
        }

        $query = [
            'FROM'   => static::getTable(),
            'COUNT'  => 'cpt',
            'WHERE'  => $where,
        ];
        $row = $DB->request($query)->current();
        return ((int) $row['cpt'] === 0);
    }

    /**
     * Get visibility fields from conf
     *
     * @return array<string,string>
     */
    protected function getvisibilityFields(): array
    {
        global $CFG_GLPI;
        $fields = [];
        foreach ($CFG_GLPI['state_types'] as $type) {
            $fields[$type] = 'is_visible_' . strtolower($type);
        }

        // Add custom assets
        foreach (AssetDefinitionManager::getInstance()->getDefinitions() as $definition) {
            $fields[$definition->getAssetClassName()] = strtolower(str_replace('\'', '_', 'is_visible_' . $definition->getAssetClassName()));
        }

        return $fields;
    }

    /**
     * Criteria to apply to assets dropdown when shown in assistance
     *
     * @return array
     */
    public static function getDisplayConditionForAssistance(): array
    {
        return [
            'OR' =>  [
                'states_id' => new QuerySubQuery([
                    'SELECT' => 'id',
                    'FROM'   => self::getTable(),
                    'WHERE'  => ['is_helpdesk_visible' => true],
                ]),
                ['states_id' => 0],
            ],
        ];
    }

    public function getCloneRelations(): array
    {
        return [];
    }

    public function post_getFromDB()
    {
        $statevisibility = new DropdownVisibility();

        foreach ($this->getvisibilityFields() as $visibility_field) {
            // Default value for fields that may not be yet stored in DB.
            $this->fields[$visibility_field] = 0;
        }

        $visibilities = $statevisibility->find(['itemtype' => self::class, 'items_id' => $this->fields['id']]);
        foreach ($visibilities as $visibility) {
            $this->fields['is_visible_' . strtolower($visibility['visible_itemtype'])] = $visibility['is_visible'];
        }
    }

    public static function getIcon()
    {
        return "ti ti-label";
    }
}
