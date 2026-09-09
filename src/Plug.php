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

/// Class Plug
class Plug extends CommonDBRelation
{
    public const POWER_SUPPLY_FIELD = 'items_devicepowersupplies_id';

    public static ?string $itemtype_1 = 'itemtype_main';
    public static ?string $items_id_1 = 'items_id_main';
    public static bool $mustBeAttached_1       = true;

    public static ?string $itemtype_2       = 'itemtype_asset';
    public static ?string $items_id_2       = 'items_id_asset';
    public static bool $mustBeAttached_2       = false;

    public bool $no_form_page                = false;

    public static function getTypeName($nb = 0)
    {
        return _n('Plug', 'Plugs', $nb);
    }

    public static function getIcon()
    {
        return "ti ti-plug";
    }

    public static function getSectorizedDetails(): array
    {
        return ['assets'];
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addDefaultFormTab($ong)
            ->addStandardTab(Log::class, $ong, $options);
        return $ong;
    }

    /**
     * Prepare input data.
     *
     * @param array<string, mixed> $input
     *
     * @return false|array<string, mixed>
     */
    private function prepareInput($input): array|false
    {
        if (isset($input['name']) && empty($input['name'])) {
            Session::addMessageAfterRedirect(
                __s('Plug name is required'),
                true,
                ERROR
            );
            return false;
        }

        if (isset($input['itemtype_main']) && !is_a($input['itemtype_main'], CommonDBTM::class, true)) {
            trigger_error(
                sprintf('Invalid itemtype_main value: %s', $input['itemtype_main']),
                E_USER_WARNING
            );
            return false;
        }

        if (
            isset($input['itemtype_asset'])
            && $input['itemtype_asset'] !== ''
            && !is_a($input['itemtype_asset'], CommonDBTM::class, true)
        ) {
            trigger_error(
                sprintf('Invalid itemtype_asset value: %s', $input['itemtype_asset']),
                E_USER_WARNING
            );
            return false;
        }

        $asset_changed = isset($input['itemtype_asset']) || isset($input['items_id_asset']);
        if ($asset_changed && !isset($input[self::POWER_SUPPLY_FIELD])) {
            // A component reference cannot be kept if its parent asset changes.
            $input[self::POWER_SUPPLY_FIELD] = 0;
        }

        $itemtype_asset = $input['itemtype_asset'] ?? $this->fields['itemtype_asset'] ?? '';
        $items_id_asset = (int) ($input['items_id_asset'] ?? $this->fields['items_id_asset'] ?? 0);
        $power_supply_id = (int) ($input[self::POWER_SUPPLY_FIELD] ?? $this->fields[self::POWER_SUPPLY_FIELD] ?? 0);

        if (
            $power_supply_id === 0
            && self::getPowerSupplyChoices($itemtype_asset, $items_id_asset) !== []
        ) {
            Session::addMessageAfterRedirect(
                __s('A specific power supply must be selected for this asset'),
                true,
                ERROR
            );
            return false;
        }

        if ($power_supply_id === 0) {
            return $input;
        }

        $power_supply = new Item_DevicePowerSupply();

        if (
            $itemtype_asset === ''
            || $items_id_asset === 0
            || !$power_supply->getFromDB($power_supply_id)
            || (int) $power_supply->fields['is_deleted'] !== 0
            || $power_supply->fields['itemtype'] !== $itemtype_asset
            || (int) $power_supply->fields['items_id'] !== $items_id_asset
        ) {
            Session::addMessageAfterRedirect(
                __s('The selected power supply does not belong to the associated asset'),
                true,
                ERROR
            );
            return false;
        }

        $criteria = [self::POWER_SUPPLY_FIELD => $power_supply_id];
        $current_id = (int) ($input['id'] ?? $this->getID());
        if ($current_id > 0) {
            $criteria['NOT'] = ['id' => $current_id];
        }
        if (countElementsInTable(self::getTable(), $criteria) > 0) {
            Session::addMessageAfterRedirect(
                __s('The selected power supply is already connected to another plug'),
                true,
                ERROR
            );
            return false;
        }

        return $input;
    }

    public function prepareInputForAdd($input)
    {
        return $this->prepareInput($input);
    }

    public function prepareInputForUpdate($input)
    {
        return $this->prepareInput($input);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        global $CFG_GLPI;

        $is_plug_host = in_array($item::class, $CFG_GLPI['plug_types'], true);
        $nb = 0;
        if ($_SESSION['glpishow_count_on_tabs']) {
            /** @var CommonDBTM $item */
            $nb = countElementsInTable(
                self::getTable(),
                $is_plug_host
                    ? [
                        'itemtype_main' => $item::class,
                        'items_id_main' => $item->getID(),
                    ]
                    : [
                        'itemtype_asset' => $item::class,
                        'items_id_asset' => $item->getID(),
                        self::POWER_SUPPLY_FIELD => ['>', 0],
                    ]
            );
        }

        $label = $is_plug_host
            ? self::getTypeName(Session::getPluralNumber())
            : __('Power connections');

        return self::createTabEntry($label, $nb, $item::class);
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        global $CFG_GLPI;

        if (!$item instanceof CommonDBTM) {
            return false;
        }

        return in_array($item::class, $CFG_GLPI['plug_types'], true)
            ? self::showItems($item)
            : self::showPowerConnections($item);
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('pages/assets/plug.html.twig', [
            'item'              => $this,
            'params'            => $options,
            'entity_restrict'   => $this->isRecursive() ? getSonsOf('glpi_entities', $this->getEntityID()) : $this->getEntityID(),
            'power_supplies'    => self::getPowerSupplyChoices(
                $this->fields['itemtype_asset'] ?? '',
                (int) ($this->fields['items_id_asset'] ?? 0)
            ),
        ]);
        return true;
    }

    /**
     * Print plugs linked to PDU
     *
     * @param CommonDBTM $item
     *
     * @return bool
     */
    public static function showItems(CommonDBTM $item): bool
    {
        global $DB;

        $ID = $item->getID();
        $rand = mt_rand();

        if (
            !$item->getFromDB($ID)
            || !$item->can($ID, READ)
        ) {
            return false;
        }
        $canedit = $item->canEdit($ID);

        $items = $DB->request([
            'SELECT' => ['*'],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'itemtype_main' => $item::class,
                'items_id_main' => $ID,
            ],
        ]);

        if (Plug::canCreate()) {
            $rand = mt_rand();
            echo "<form id='form_device_add$rand' name='form_device_add$rand'
               action='" . htmlescape(Toolbox::getItemTypeFormURL(self::class)) . "' method='post'>";
            echo "<input type='hidden' name='items_id_main' value='$ID'>";
            echo "<input type='hidden' name='itemtype_main' value='" . htmlescape($item::class) . "'>";
            echo "<table class='tab_cadre_fixe'><tr class='tab_bg_1'><td>";
            echo "<label for='dropdown_plugs_id$rand'>" . __s('Add a new plug') . "</label> <span class='form-help' data-bs-toggle='tooltip' data-bs-placement='top' data-bs-html='true'
                     data-bs-title='" . __s('Name will by suffixed by number') . "'>?</span></td>";
            echo "<td>";
            echo Html::input(
                'name',
                [
                    'type'   => 'text',
                    'required' => true,
                    'placeholder' => __('Plug name'),
                ]
            );
            echo "</td><td>";
            echo Html::input(
                'number',
                [
                    'type'   => 'number',
                    'min'    => 1,
                    'placeholder' => __('Number of plugs to add'),
                    'required' => true,
                ]
            );
            echo "</td><td>";
            echo "<input type='submit' class='btn btn-primary' name='add_several' value='" . _sx('button', 'Add') . "'>";
            echo "</td></tr></table>";
            Html::closeForm();
        }

        $entries = [];
        foreach ($items as $row) {
            $plug = new Plug();
            $plug->getFromDB($row['id']);

            $asset = is_a($plug->fields['itemtype_asset'], CommonDBTM::class, true)
                ? new $plug->fields['itemtype_asset']()
                : null;

            $entries[] = [
                'name' => $plug->getLink(),
                'itemtype' => $plug::class,
                'items_id' => $plug->getID(),
                'custom_name' => $plug->fields['custom_name'],
                'linked_item' => $asset !== null && $plug->fields['items_id_asset'] && $asset->getFromDB($plug->fields['items_id_asset'])
                    ? $asset->getLink()
                    : '',
                'power_supply' => self::getPowerSupplyLabel((int) ($plug->fields[self::POWER_SUPPLY_FIELD] ?? 0)),
                'id' => $row['id'],
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'name' => Plug::getTypeName(0),
                'custom_name' => __('Custom name'),
                'linked_item' => __s('Associated asset'),
                'power_supply' => DevicePowerSupply::getTypeName(1),
            ],
            'formatters' => [
                'name' => 'raw_html',
                'custom_name' => 'text',
                'linked_item' => 'raw_html',
                'power_supply' => 'text',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => min($_SESSION['glpilist_limit'], count($entries)),
                'container'     => 'mass' . static::class . $rand,
            ],
        ]);

        return true;
    }

    /**
     * Print the PDU plugs connected to an asset.
     */
    public static function showPowerConnections(CommonDBTM $item): bool
    {
        global $DB;

        $ID = $item->getID();
        if (
            !$item->getFromDB($ID)
            || !$item->can($ID, READ)
        ) {
            return false;
        }

        $items = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'itemtype_asset' => $item::class,
                'items_id_asset' => $ID,
                self::POWER_SUPPLY_FIELD => ['>', 0],
            ],
            'ORDER' => ['id'],
        ]);

        $entries = [];
        foreach ($items as $row) {
            $plug = new Plug();
            if (!$plug->getFromDB($row['id'])) {
                continue;
            }

            $plug_host = is_a($plug->fields['itemtype_main'], CommonDBTM::class, true)
                ? new $plug->fields['itemtype_main']()
                : null;
            $plug_host_link = '';
            if (
                $plug_host !== null
                && $plug_host->getFromDB((int) $plug->fields['items_id_main'])
                && $plug_host->can($plug_host->getID(), READ)
            ) {
                $plug_host_link = $plug_host->getLink();
            }

            $entries[] = [
                'itemtype'    => $plug::class,
                'id'          => $plug->getID(),
                'plug_host'   => $plug_host_link,
                'plug'        => $plug->getLink(),
                'custom_name' => $plug->fields['custom_name'],
                'connected_to' => self::getPowerSupplyLabel(
                    (int) $plug->fields[self::POWER_SUPPLY_FIELD]
                ),
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'plug_host' => __('Power distribution unit'),
                'plug' => self::getTypeName(1),
                'custom_name' => __('Custom name'),
                'connected_to' => __('Connected to'),
            ],
            'formatters' => [
                'plug_host' => 'raw_html',
                'plug' => 'raw_html',
                'custom_name' => 'text',
                'connected_to' => 'text',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
        ]);

        return true;
    }

    public function rawSearchOptions()
    {
        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'            => 1,
            'table'         => static::getTable(),
            'field'         => 'name',
            'name'          => __('Name'),
            'datatype'      => 'itemlink',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'       => 86,
            'table'      => static::getTable(),
            'field'      => 'is_recursive',
            'name'       => __('Child entities'),
            'datatype'   => 'bool',
            'searchtype' => 'equals',
        ];

        $tab[] = [
            'id'                 => 2,
            'table'              => $this->getTable(),
            'field'              => 'id',
            'name'               => __('ID'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => 3,
            'table'              => $this->getTable(),
            'field'              => 'itemtype_main',
            'name'               => sprintf(__('%s (%s)'), _n('Associated item type', 'Associated item types', 1), __('Support type')),
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'plug_types',
            'forcegroupby'       => true,
            'massiveaction'      => false,
        ];


        $tab[] = [
            'id'                 => 4,
            'table'              => $this->getTable(),
            'field'              => 'items_id_main',
            'name'               => sprintf(__('%s (%s)'), _n('Associated item', 'Associated items', 1), __('Support type')),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'searchtype'         => 'equals',
            'additionalfields'   => ['itemtype_main'],
        ];


        $tab[] = [
            'id'                 => 5,
            'table'              => $this->getTable(),
            'field'              => 'itemtype_asset',
            'name'               => sprintf(__('%s (%s)'), _n('Associated item type', 'Associated item types', 1), __('Associated asset')),
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'inventory_types',
            'forcegroupby'       => true,
            'massiveaction'      => false,
        ];


        $tab[] = [
            'id'                 => '8',
            'table'              => $this->getTable(),
            'field'              => 'items_id_asset',
            'name'               => sprintf(__('%s (%s)'), _n('Associated item', 'Associated items', 1), __('Associated asset')),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'searchtype'         => 'equals',
            'additionalfields'   => ['itemtype_asset'],
        ];

        $tab[] = [
            'id'                 => 9,
            'table'              => $this->getTable(),
            'field'              => self::POWER_SUPPLY_FIELD,
            'name'               => DevicePowerSupply::getTypeName(1),
            'massiveaction'      => false,
            'datatype'           => 'number',
            'searchtype'         => 'equals',
        ];

        return $tab;
    }

    /**
     * Return the installed power supplies belonging to an asset.
     *
     * @return array<int, string>
     */
    public static function getPowerSupplyChoices(string $itemtype, int $items_id): array
    {
        global $DB;

        if ($items_id <= 0 || !is_a($itemtype, CommonDBTM::class, true)) {
            return [];
        }

        $choices = [];
        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => Item_DevicePowerSupply::getTable(),
            'WHERE'  => [
                'itemtype'  => $itemtype,
                'items_id'  => $items_id,
                'is_deleted' => 0,
            ],
            'ORDER'  => ['id'],
        ]);

        foreach ($iterator as $row) {
            $choices[(int) $row['id']] = self::getPowerSupplyLabel((int) $row['id']);
        }

        return $choices;
    }

    public static function getPowerSupplyLabel(int $power_supply_id): string
    {
        if ($power_supply_id <= 0) {
            return '';
        }

        $power_supply = new Item_DevicePowerSupply();
        if (!$power_supply->getFromDB($power_supply_id)) {
            return '';
        }

        $device = new DevicePowerSupply();
        $designation = $device->getFromDB((int) $power_supply->fields['devicepowersupplies_id'])
            ? $device->getName()
            : DevicePowerSupply::getTypeName(1);

        $details = [];
        if (!empty($power_supply->fields['serial'])) {
            $details[] = sprintf(__('Serial number: %s'), $power_supply->fields['serial']);
        }
        if (!empty($power_supply->fields['otherserial'])) {
            $details[] = sprintf(__('Inventory number: %s'), $power_supply->fields['otherserial']);
        }

        $label = sprintf('%s (#%d)', $designation, $power_supply_id);
        return $details === [] ? $label : sprintf('%s — %s', $label, implode(', ', $details));
    }

}
