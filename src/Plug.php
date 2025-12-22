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
use Glpi\Features\Inventoriable;

/// Class Plug
class Plug extends CommonDBRelation
{
    use Inventoriable;

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
            ->addStandardTab(Lock::class, $ong, $options)
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
            Session::addMessageAfterRedirect(
                __s('The Support type field cannot be empty.'),
                false,
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
        $nb = 0;
        if ($_SESSION['glpishow_count_on_tabs']) {
            /** @var CommonDBTM $item */
            $nb = countElementsInTable(
                self::getTable(),
                [
                    'itemtype_main' => $item::class,
                    'items_id_main' => $item->getID(),
                    'is_deleted'    => false, // do not dcount deleted item
                ]
            );
        }
        return self::createTabEntry(self::getTypeName(Session::getPluralNumber()), $nb, $item::class);
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }
        return self::showItems($item);
    }

    public function showForm($ID, array $options = [])
    {
        $this->initForm($ID, $options);
        TemplateRenderer::getInstance()->display('pages/assets/plug.html.twig', [
            'item'              => $this,
            'params'            => $options,
            'entity_restrict'   => $this->isRecursive() ? getSonsOf('glpi_entities', $this->getEntityID()) : $this->getEntityID(),
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
                'is_deleted'    => false,
            ],
            'ORDER' => [
                'number',
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
                'number' => $plug->fields['number'],
                'type' => Dropdown::getDropdownName(PlugType::getTable(), $plug->fields['plugtypes_id']),
                'itemtype' => $plug::class,
                'items_id' => $plug->getID(),
                'custom_name' => $plug->fields['custom_name'],
                'linked_item' => $asset !== null && $plug->fields['items_id_asset'] && $asset->getFromDB($plug->fields['items_id_asset'])
                    ? $asset->getLink()
                    : '',
                'id' => $row['id'],
            ];
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'columns' => [
                'name' => Plug::getTypeName(0),
                'number' => _x('plug', 'Number'),
                'custom_name' => __('Custom name'),
                'type' => PlugType::getTypeName(0),
                'linked_item' => __s('Associated asset'),
            ],
            'formatters' => [
                'name' => 'raw_html',
                'custom_name' => 'text',
                'linked_item' => 'raw_html',
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

    public function rawSearchOptions()
    {
        $tab[] = [
            'id'                 => 'common',
            'name'               => __('Characteristics'),
        ];

        $tab[] = [
            'id'                 => 1,
            'table'              => static::getTable(),
            'field'              => 'name',
            'name'               => __('Name'),
            'datatype'           => 'itemlink',
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => 86,
            'table'              => static::getTable(),
            'field'              => 'is_recursive',
            'name'               => __('Child entities'),
            'datatype'           => 'bool',
            'searchtype'         => 'equals',
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
            'table'              => 'glpi_plugtypes',
            'field'              => 'name',
            'name'               => _n('Type', 'Types', 1),
            'datatype'           => 'dropdown',
        ];

        $tab[] = [
            'id'                 => 4,
            'table'              => $this->getTable(),
            'field'              => 'number',
            'name'               => _x('plug', 'Number'),
            'massiveaction'      => false,
            'datatype'           => 'number',
        ];

        $tab[] = [
            'id'                 => 5,
            'table'              => $this->getTable(),
            'field'              => 'itemtype_main',
            'name'               => sprintf(__('%s (%s)'), _n('Associated item type', 'Associated item types', 1), __('Support type')),
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'plug_types',
            'forcegroupby'       => true,
            'massiveaction'      => false,
        ];

        $tab[] = [
            'id'                 => 6,
            'table'              => $this->getTable(),
            'field'              => 'items_id_main',
            'name'               => sprintf(__('%s (%s)'), _n('Associated item', 'Associated items', 1), __('Support type')),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'searchtype'         => 'equals',
            'additionalfields'   => ['itemtype_main'],
        ];


        $tab[] = [
            'id'                 => 7,
            'table'              => $this->getTable(),
            'field'              => 'itemtype_asset',
            'name'               => sprintf(__('%s (%s)'), _n('Associated item type', 'Associated item types', 1), __('Associated asset')),
            'datatype'           => 'itemtypename',
            'itemtype_list'      => 'inventory_types',
            'forcegroupby'       => true,
            'massiveaction'      => false,
        ];


        $tab[] = [
            'id'                 => 8,
            'table'              => $this->getTable(),
            'field'              => 'items_id_asset',
            'name'               => sprintf(__('%s (%s)'), _n('Associated item', 'Associated items', 1), __('Associated asset')),
            'massiveaction'      => false,
            'datatype'           => 'specific',
            'searchtype'         => 'equals',
            'additionalfields'   => ['itemtype_asset'],
        ];

        return $tab;
    }

}
