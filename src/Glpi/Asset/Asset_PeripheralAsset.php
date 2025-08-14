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

namespace Glpi\Asset;

use CommonDBRelation;
use CommonDBTM;
use CommonGLPI;
use Dropdown;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Glpi\DBAL\QueryFunction;
use Html;
use LogicException;
use MassiveAction;
use Override;
use Session;

final class Asset_PeripheralAsset extends CommonDBRelation
{
    public static $itemtype_1          = 'itemtype_asset';
    public static $items_id_1          = 'items_id_asset';

    public static $itemtype_2          = 'itemtype_peripheral';
    public static $items_id_2          = 'items_id_peripheral';
    public static $checkItem_2_Rights  = self::HAVE_VIEW_RIGHT_ON_ITEM;


    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public static function getIcon()
    {
        return 'ti ti-sitemap';
    }

    /**
     * Count connections between an item and a peripheral.
     *
     * @param CommonDBTM $main_item
     * @param CommonDBTM $peripheral_item
     *
     * @return boolean
     */
    private static function isAlreadyConnected(CommonDBTM $main_item, CommonDBTM $peripheral_item): bool
    {
        $connections = countElementsInTable(
            self::getTable(),
            [
                'itemtype_asset'      => $main_item::class,
                'items_id_asset'      => $main_item->getID(),
                'itemtype_peripheral' => $peripheral_item::class,
                'items_id_peripheral' => $peripheral_item->getID(),
            ]
        );
        return $connections > 0;
    }

    public function prepareInputForAdd($input)
    {
        $peripheral = self::getItemFromArray(self::$itemtype_2, self::$items_id_2, $input);

        if (
            !($peripheral instanceof CommonDBTM)
            || (!$peripheral->isGlobal()
              && (self::countLinkedAssets($peripheral) > 0))
        ) {
            return false;
        }

        $asset = self::getItemFromArray(self::$itemtype_1, self::$items_id_1, $input);
        if (
            !($asset instanceof CommonDBTM)
            || self::isAlreadyConnected($asset, $peripheral)
            || !(in_array($asset::class, self::getPeripheralHostItemtypes(), true))
        ) {
            // no duplicates
            return false;
        }

        if (!$peripheral->isGlobal()) {
            // Autoupdate some fields - should be in post_addItem (here to avoid more DB access)
            $updates = [];

            if (
                $asset->fields['locations_id'] !== $peripheral->getField('locations_id')
                && Entity::getUsedConfig('is_location_autoupdate', $asset->getEntityID())
            ) {
                $updates['locations_id'] = $asset->fields['locations_id'];
                Session::addMessageAfterRedirect(
                    __s('Location updated. The connected items have been moved in the same location.'),
                    true
                );
            }
            if (
                (Entity::getUsedConfig('is_user_autoupdate', $asset->getEntityID())
                && ($asset->fields['users_id'] !== $peripheral->getField('users_id')))
                || (Entity::getUsedConfig('is_group_autoupdate', $asset->getEntityID())
                 && ($asset->fields['groups_id'] !== $peripheral->getField('groups_id')))
            ) {
                if (Entity::getUsedConfig('is_user_autoupdate', $asset->getEntityID())) {
                    $updates['users_id'] = $asset->fields['users_id'];
                }
                if (Entity::getUsedConfig('is_group_autoupdate', $asset->getEntityID())) {
                    $updates['groups_id'] = $asset->fields['groups_id'];
                }
                Session::addMessageAfterRedirect(
                    __s('User or group updated. The connected items have been moved in the same values.'),
                    true
                );
            }

            if (
                (($asset->fields['contact'] !== $peripheral->fields['contact'])
                 || ($asset->fields['contact_num'] !== $peripheral->fields['contact_num']))
                && Entity::getUsedConfig('is_contact_autoupdate', $peripheral->getEntityID())
            ) {
                $updates['contact']     = $asset->fields['contact'] ?? '';
                $updates['contact_num'] = $asset->fields['contact_num'] ?? '';
                $updates['is_dynamic']  = $asset->fields['is_dynamic'] ?? 0;
                Session::addMessageAfterRedirect(
                    __s('Alternate username updated. The connected items have been updated using this alternate username.'),
                    true
                );
            }

            $state_autoupdate_mode = Entity::getUsedConfig('state_autoupdate_mode', $peripheral->getEntityID());
            if (
                ($state_autoupdate_mode < 0)
                && ($asset->fields['states_id'] !== $peripheral->fields['states_id'])
            ) {
                $updates['states_id'] = $asset->fields['states_id'];
                Session::addMessageAfterRedirect(
                    __s('Status updated. The connected items have been updated using this status.'),
                    true
                );
            }

            if (
                ($state_autoupdate_mode > 0)
                && ($peripheral->fields['states_id'] !== $state_autoupdate_mode)
            ) {
                $updates['states_id'] = $state_autoupdate_mode;
            }

            if (count($updates)) {
                $updates['id'] = $input['items_id_peripheral'];
                $history = true;
                if (isset($input['_no_history']) && $input['_no_history']) {
                    $history = false;
                }
                $peripheral->update($updates, $history);
            }
        }
        return parent::prepareInputForAdd($input);
    }

    public function cleanDBonPurge()
    {
        if (!isset($this->input['_no_auto_action'])) {
            // Get the item
            $asset = getItemForItemtype($this->fields['itemtype_asset']);
            if (!($asset instanceof CommonDBTM) || !$asset->getFromDB($this->fields['items_id_asset'])) {
                return;
            }

            $is_mainitem_dynamic = (bool) ($asset->fields['is_dynamic'] ?? false);

            // Get peripheral fields
            if ($peripheral = getItemForItemtype($this->fields['itemtype_peripheral'])) {
                if ($peripheral->getFromDB($this->fields['items_id_peripheral'])) {
                    if (!$peripheral->fields['is_global']) {
                        $updates = [];
                        if (Entity::getUsedConfig('is_location_autoclean', $peripheral->getEntityID()) && $peripheral->isField('locations_id')) {
                            $updates['locations_id'] = 0;
                        }
                        if (Entity::getUsedConfig('is_user_autoclean', $peripheral->getEntityID()) && $peripheral->isField('users_id')) {
                            $updates['users_id'] = 0;
                        }
                        if (Entity::getUsedConfig('is_group_autoclean', $peripheral->getEntityID()) && $peripheral->isField('groups_id')) {
                            $updates['groups_id'] = 0;
                        }
                        if (Entity::getUsedConfig('is_contact_autoclean', $peripheral->getEntityID()) && $peripheral->isField('contact')) {
                            $updates['contact'] = "";
                        }
                        if (Entity::getUsedConfig('is_contact_autoclean', $peripheral->getEntityID()) && $peripheral->isField('contact_num')) {
                            $updates['contact_num'] = "";
                        }

                        $state_autoclean_mode = Entity::getUsedConfig('state_autoclean_mode', $peripheral->getEntityID());
                        if (
                            ($state_autoclean_mode < 0)
                            && $peripheral->isField('states_id')
                        ) {
                            $updates['states_id'] = 0;
                        }

                        if (
                            ($state_autoclean_mode > 0)
                            && $peripheral->isField('states_id')
                            && ($peripheral->fields['states_id'] !== $state_autoclean_mode)
                        ) {
                            $updates['states_id'] = $state_autoclean_mode;
                        }

                        if (count($updates)) {
                            //propage is_dynamic value if needed to prevent locked fields
                            if ((bool) ($peripheral->fields['is_dynamic'] ?? false) && $is_mainitem_dynamic) {
                                $updates['is_dynamic'] = 1;
                            }
                            $updates['id'] = $this->fields['items_id_peripheral'];
                            $peripheral->update($updates);
                        }
                    }
                }
            }
        }
    }

    public static function getMassiveActionsForItemtype(
        array &$actions,
        $itemtype,
        $is_deleted = false,
        ?CommonDBTM $checkitem = null
    ) {
        $action_prefix = self::class . MassiveAction::CLASS_ACTION_SEPARATOR;
        $specificities = self::getRelationMassiveActionsSpecificities();

        if (in_array($itemtype, $specificities['itemtypes'], true)) {
            $actions[$action_prefix . 'add']    = "<i class='ti ti-plug'></i>" . _sx('button', 'Connect');
            $actions[$action_prefix . 'remove'] = "<i class='ti ti-plug-off'></i>" . _sx('button', 'Disconnect');
        }
        parent::getMassiveActionsForItemtype($actions, $itemtype, $is_deleted, $checkitem);
    }

    public static function getRelationMassiveActionsSpecificities()
    {
        global $CFG_GLPI;

        $specificities              = parent::getRelationMassiveActionsSpecificities();
        $specificities['itemtypes'] = $CFG_GLPI['directconnect_types'];
        $specificities['select_items_options_1']['itemtypes']       = self::getPeripheralHostItemtypes();
        $specificities['select_items_options_2']['entity_restrict'] = $_SESSION['glpiactive_entity'];
        $specificities['select_items_options_2']['itemtypes']       = $CFG_GLPI['directconnect_types'];
        $specificities['select_items_options_2']['onlyglobal']      = true;
        $specificities['only_remove_all_at_once']                   = true;

        // Set the labels for add_item and remove_item
        $specificities['button_labels']['add']                      = _sx('button', 'Connect');
        $specificities['button_labels']['remove']                   = _sx('button', 'Disconnect');

        return $specificities;
    }

    /**
     * Print the form for computers or templates connections to printers, screens or peripherals
     *
     * @param CommonDBTM $asset        CommonDBTM object
     * @param integer    $withtemplate Template or basic item (default 0)
     *
     * @return void
     **/
    private static function showForAsset(CommonDBTM $asset, $withtemplate = 0): void
    {
        global $CFG_GLPI;

        $ID      = $asset->fields['id'];
        $canedit = $asset->canEdit($ID);
        $rand    = mt_rand();

        $datas = [];
        $used  = [];
        foreach ($CFG_GLPI['directconnect_types'] as $itemtype) {
            if ($itemtype::canView()) {
                $iterator = self::getPeripheralAssets($asset, $itemtype);

                foreach ($iterator as $data) {
                    $data['assoc_itemtype'] = $itemtype;
                    $datas[]           = $data;
                    $used[$itemtype][] = $data['id'];
                }
            }
        }

        if (
            $canedit
            && !(!empty($withtemplate) && ($withtemplate == 2))
        ) {
            $entities = $asset->fields["entities_id"];
            if ($asset->isRecursive()) {
                $entities = getSonsOf("glpi_entities", $asset->getEntityID());
            }

            $dropdown_params = [
                'itemtype'        => '__VALUE__',
                'fromtype'        => $asset::class,
                'value'           => 0,
                'myname'          => 'items_id_peripheral',
                'onlyglobal'      => (int) $withtemplate === 1 ? 1 : 0,
                'entity_restrict' => $entities,
                'used'            => $used,
            ];

            $twig_params = [
                'rand' => mt_rand(),
                'dropdown_params' => $dropdown_params,
                'asset' => $asset,
                'label' => __('Connect an item'),
                'btn_label' => _x('button', 'Connect'),
                'withtemplate' => (int) $withtemplate === 1 ? 1 : 0,
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                <div class="mb-3">
                    <form method="post" action="{{ 'Glpi\\\\Asset\\\\Asset_PeripheralAsset'|itemtype_form_path }}">
                        {{ fields.hiddenField('items_id_asset', asset.getID()) }}
                        {{ fields.hiddenField('itemtype_asset', asset.getType()) }}
                        {{ fields.hiddenField('_glpi_csrf_token', csrf_token()) }}
                        {{ withtemplate ? fields.hiddenField('_no_history', 1) }}
                        {{ fields.dropdownItemTypes('itemtype_peripheral', 0, label, {
                            types: config('directconnect_types'),
                            checkright: true,
                        }) }}
                        <div id="show_items_id_peripheral{{ rand }}"></div>
                        <script>
                            $(() => {
                                $('select[name="itemtype_peripheral"]').on('change', (e) => {
                                    const params = Object.assign({{ dropdown_params|json_encode|raw }}, { itemtype: e.target.value });
                                    $('#show_items_id_peripheral{{ rand }}').load(CFG_GLPI.root_doc + '/ajax/dropdownConnect.php', params);
                                });
                            });
                        </script>
                        <div class="d-flex flex-row-reverse">
                            <button type="submit" name="add" class="btn btn-primary">{{ btn_label }}</button>
                        </div>
                    </form>
                </div>
TWIG, $twig_params);
        }

        $entries = [];
        $entity_cache = [];
        $type_cache = [];
        foreach ($datas as $data) {
            $linkname = $data["name"];
            $itemtype = $data['assoc_itemtype'];

            $entry = [
                'itemtype' => self::class,
                'id' => $data['linkid'],
                '_itemtype' => $itemtype::getTypeName(1),
                'row_class' => (isset($data['is_deleted']) && $data['is_deleted']) ? 'table-danger' : '',
                'is_dynamic' => $data[self::getTable() . '_is_dynamic'] ? __('Yes') : __('No'),
                'serial' => $data["serial"] ?? "-",
                'otherserial' => $data['otherserial'] ?? '-',
            ];

            $type_class = $itemtype . "Type";
            $type_table = getTableForItemType($type_class);
            $type_field = getForeignKeyFieldForTable($type_table);

            if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                $linkname = sprintf(__('%1$s (%2$s)'), $linkname, $data["id"]);
            }
            $link = $itemtype::getFormURLWithID($data["id"]);
            $label = sprintf('<i class="%s"></i> %s', htmlescape($itemtype::getIcon()), htmlescape($linkname));
            $name = '<a href="' . htmlescape($link) . '">' . $label . '</a>';
            $entry['name'] = $name;

            if (!isset($entity_cache[$data['entities_id']])) {
                $entity_cache[$data['entities_id']] = Dropdown::getDropdownName(
                    "glpi_entities",
                    $data['entities_id']
                );
            }
            $entry['entity'] = $entity_cache[$data['entities_id']];

            if (isset($data[$type_field])) {
                if (!isset($type_cache[$data[$type_field]])) {
                    $type_cache[$data[$type_field]] = Dropdown::getDropdownName(
                        $type_table,
                        $data[$type_field]
                    );
                }
                $entry['type'] = $type_cache[$data[$type_field]];
            } else {
                $entry['type'] = '-';
            }
            $entries[] = $entry;
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'name' => __('Name'),
                '_itemtype' => __('Item type'),
                'is_dynamic' => __('Automatic inventory'),
                'entity' => Entity::getTypeName(1),
                'serial' => __('Serial number'),
                'otherserial' => __('Inventory number'),
                'type' => _n('Type', 'Types', 1),
            ],
            'formatters' => [
                'name' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container' => 'massAsset_PeripheralAsset' . $rand,
                'specific_actions' => ['purge' => _x('button', 'Disconnect')],
            ],
        ]);
    }

    /**
     *
     * Print the form for a peripheral
     *
     * @param CommonDBTM $peripheral         CommonDBTM object
     * @param integer    $withtemplate Template or basic item (default 0)
     *
     * @return void
     **/
    private static function showForPeripheral(CommonDBTM $peripheral, $withtemplate = 0): void
    {
        $ID      = $peripheral->fields['id'];

        if (!$peripheral->can($ID, READ)) {
            return;
        }

        $canedit = $peripheral->canEdit($ID);
        $rand    = mt_rand();

        // Is global connection ?
        $global  = $peripheral->fields['is_global'];

        $linked_assets = [];
        $used          = [];
        $itemtypes     = self::getPeripheralHostItemtypes();
        foreach ($itemtypes as $itemtype) {
            if (is_a($itemtype, CommonDBTM::class, true) && $itemtype::canView()) {
                $iterator = self::getItemConnectionsForItemtype($peripheral, $itemtype);

                foreach ($iterator as $data) {
                    $data['assoc_itemtype'] = $itemtype;
                    $linked_assets[]        = $data;
                    $used[$itemtype][]      = $data['id'];
                }
            }
        }
        $number = count($linked_assets);

        if (
            $canedit
            && ($global || $number === 0)
            && !(!empty($withtemplate) && ($withtemplate == 2))
        ) {
            $entities = $peripheral->fields["entities_id"];
            if ($peripheral->isRecursive()) {
                $entities = getSonsOf("glpi_entities", $peripheral->getEntityID());
            }
            $dropdown_params = [
                'items_id_name'   => 'items_id_asset',
                'itemtype_name'   => 'itemtype_asset',
                'itemtypes'       => $itemtypes,
                'checkright'      => true,
                'entity_restrict' => $entities,
                'used'            => $used,
            ];

            $twig_params = [
                'label' => __('Connect to an item'),
                'peripheral' => $peripheral,
                'dropdown_params' => $dropdown_params,
                'btn_label' => _x('button', 'Connect'),
                'withtemplate' => (int) $withtemplate === 1 ? 1 : 0,
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                <div class="mb-3">
                    <form method="post" action="{{ 'Glpi\\\\Asset\\\\Asset_PeripheralAsset'|itemtype_form_path }}">
                        {{ fields.dropdownItemsFromItemtypes('', label, dropdown_params) }}
                        {{ fields.hiddenField('items_id_peripheral', peripheral.getID()) }}
                        {{ fields.hiddenField('itemtype_peripheral', peripheral.getType()) }}
                        {{ fields.hiddenField('_glpi_csrf_token', csrf_token()) }}
                        {{ withtemplate ? fields.hiddenField('_no_history', 1) }}
                        <div class="d-flex flex-row-reverse">
                            <button type="submit" name="add" class="btn btn-primary">{{ btn_label }}</button>
                        </div>
                    </form>
                </div>
TWIG, $twig_params);
        }

        $entries = [];
        $entity_cache = [];
        foreach ($linked_assets as $data) {
            $entry = [
                'itemtype' => self::class,
                'id'       => $data['linkid'],
                '_itemtype'     => $data['assoc_itemtype']::getTypeName(1),
                'row_class' => (isset($data['is_deleted']) && $data['is_deleted']) ? 'table-danger' : '',
                'is_dynamic' => $data[self::getTable() . '_is_dynamic'] ? __('Yes') : __('No'),
                'serial' => $data["serial"] ?? "-",
                'otherserial' => $data['otherserial'] ?? '-',
            ];
            $linkname = $data["name"];
            $itemtype = $data['assoc_itemtype'];
            if ($_SESSION["glpiis_ids_visible"] || empty($data["name"])) {
                $linkname = sprintf(__('%1$s (%2$s)'), $linkname, $data["id"]);
            }
            $link = $itemtype::getFormURLWithID($data["id"]);
            $name = '<a href="' . htmlescape($link) . '">' . htmlescape($linkname) . '</a>';
            $entry['name'] = $name;

            if (!isset($entity_cache[$data['entities_id']])) {
                $entity_cache[$data['entities_id']] = Dropdown::getDropdownName(
                    "glpi_entities",
                    $data['entities_id']
                );
            }
            $entry['entity'] = $entity_cache[$data['entities_id']];

            $entries[] = $entry;
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                '_itemtype' => __('Item type'),
                'name' => __('Name'),
                'is_dynamic' => __('Automatic inventory'),
                'entity' => Entity::getTypeName(1),
                'serial' => __('Serial number'),
                'otherserial' => __('Inventory number'),
            ],
            'formatters' => [
                'name' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container' => 'massAsset_PeripheralAsset' . $rand,
                'specific_actions' => ['purge' => _x('button', 'Disconnect')],
            ],
        ]);
    }

    /**
     * Unglobalize an item : duplicate item and connections
     *
     * @param CommonDBTM $item object to unglobalize
     **/
    public static function unglobalizeItem(CommonDBTM $item): void
    {
        global $DB, $CFG_GLPI;

        if (
            !\in_array($item::class, $CFG_GLPI['directconnect_types'], true)
            || !$item->isField('is_global')
        ) {
            throw new LogicException(\sprintf('Item of class "%s" does not support being unglobalized', $item::class));
        }

        // Update item to unit management :
        if ($item->getField('is_global')) {
            $input = [
                'id'        => $item->fields['id'],
                'is_global' => 0,
            ];
            $item->update($input);

            // Get connect_wire for this connection
            $iterator = $DB->request([
                'SELECT' => ['id'],
                'FROM'   => self::getTable(),
                'WHERE'  => [
                    'items_id_peripheral' => $item->getID(),
                    'itemtype_peripheral' => $item->getType(),
                ],
            ]);

            $first = true;
            foreach ($iterator as $data) {
                if ($first) {
                    $first = false;
                    unset($input['id']);
                } else {
                    $temp = clone $item;
                    unset($temp->fields['id']);
                    if ($newID = $temp->add($temp->fields)) {
                        $conn = new self();
                        $conn->update([
                            'id'                  => $data['id'],
                            'items_id_peripheral' => $newID,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Make a select box for connections
     *
     * @param string            $itemtype        type to connect
     * @param string            $fromtype        from where the connection is
     * @param string            $myname          select name
     * @param integer|integer[] $entity_restrict Restrict to a defined entity (default = -1)
     * @param boolean           $onlyglobal      display only global devices (used for templates) (default 0)
     * @param integer[]         $used            Already used items ID: not to display in dropdown
     *
     * @return integer Random generated number used for select box ID (select box HTML is printed)
     */
    public static function dropdownConnect(
        $itemtype,
        $fromtype,
        $myname,
        $entity_restrict = -1,
        $onlyglobal = false,
        $used = []
    ): int {
        global $CFG_GLPI;

        $rand     = mt_rand();

        $field_id = Html::cleanId("dropdown_" . $myname . $rand);
        $param    = [
            'entity_restrict' => $entity_restrict,
            'fromtype'        => $fromtype,
            'itemtype'        => $itemtype,
            'onlyglobal'      => $onlyglobal,
            'used'            => $used,
            '_idor_token'     => Session::getNewIDORToken($itemtype, [
                'entity_restrict' => $entity_restrict,
            ]),
        ];

        echo Html::jsAjaxDropdown(
            $myname,
            $field_id,
            $CFG_GLPI['root_doc'] . "/ajax/getDropdownConnect.php",
            $param
        );

        return $rand;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        global $CFG_GLPI;

        // can exists for Template
        /** @var CommonDBTM $item */
        if ($item->can($item->getID(), READ)) {
            $nb = 0;

            if (in_array($item::class, $CFG_GLPI['directconnect_types'], true)) {
                $canview = true;
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $nb = self::countLinkedAssets($item);
                }
            } else {
                $canview = self::canViewPeripherals($item);
                if ($canview && $_SESSION['glpishow_count_on_tabs']) {
                    $nb = self::countPeripherals($item);
                }
            }

            if ($canview) {
                return self::createTabEntry(
                    _n('Connection', 'Connections', Session::getPluralNumber()),
                    $nb,
                    $item::class
                );
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        global $CFG_GLPI;

        if (!$item instanceof CommonDBTM || !$item->can($item->getID(), READ)) {
            return false;
        }

        if (in_array($item::class, $CFG_GLPI['directconnect_types'], true)) {
            self::showForPeripheral($item, $withtemplate);
            return true;
        } elseif (self::canViewPeripherals($item)) {
            self::showForAsset($item, $withtemplate);
            return true;
        }

        return false;
    }

    public static function canUnrecursSpecif(CommonDBTM $item, $entities)
    {
        global $DB;

        if (in_array($item::class, self::getPeripheralHostItemtypes(), true)) {
            // RELATION : peripherals -> items
            $iterator = $DB->request([
                'SELECT' => [
                    'itemtype_peripheral',
                    QueryFunction::groupConcat(
                        expression: 'items_id_peripheral',
                        distinct: true,
                        alias: 'ids'
                    ),
                ],
                'FROM' => self::getTable(),
                'WHERE' => [
                    'itemtype_asset' => $item->getType(),
                    'items_id_asset' => $item->getID(),
                ],
                'GROUP' => 'itemtype_peripheral',
            ]);

            foreach ($iterator as $data) {
                if (!class_exists($data['itemtype_peripheral'])) {
                    continue;
                }
                if (
                    countElementsInTable(
                        $data['itemtype_peripheral']::getTable(),
                        [
                            'id' => explode(',', $data['ids']),
                            'NOT' => ['entities_id' => $entities],
                        ]
                    ) > 0
                ) {
                    return false;
                }
            }
        } else {
            // RELATION : computers -> items
            $iterator = $DB->request([
                'SELECT' => [
                    'itemtype_peripheral',
                    QueryFunction::groupConcat(
                        expression: 'items_id_peripheral',
                        distinct: true,
                        alias: 'ids'
                    ),
                    'itemtype_asset',
                    'items_id_asset',
                ],
                'FROM'   => self::getTable(),
                'WHERE'  => [
                    'itemtype_peripheral' => $item->getType(),
                    'items_id_peripheral' => $item->fields['id'],
                ],
                'GROUP'  => 'itemtype_peripheral',
            ]);

            foreach ($iterator as $data) {
                if (countElementsInTable($data['itemtype_asset']::getTable(), ['id' => $data['items_id_asset'], 'NOT' => ['entities_id' => $entities]]) > 0) {
                    return false;
                }
            }
        }

        return true;
    }

    protected static function getListForItemParams(CommonDBTM $item, $noent = false)
    {
        $params = parent::getListForItemParams($item, $noent);
        $params['WHERE'][self::getTable() . '.is_deleted'] = 0;
        return $params;
    }

    #[Override]
    public static function getTypeItemsQueryParams_Select(CommonDBTM $item): array
    {
        $table = self::getTable();
        $select = parent::getTypeItemsQueryParams_Select($item);
        $select[] = "$table.is_dynamic AS {$table}_is_dynamic";

        return $select;
    }

    public static function getTypeName($nb = 0)
    {
        return _n('Connection', 'Connections', $nb);
    }

    public static function rawSearchOptionsToAdd($itemtype = null)
    {
        global $CFG_GLPI;

        $tab = [];
        $peripherals = $CFG_GLPI['directconnect_types'];

        foreach ($peripherals as $peripheral) {
            if (class_exists($peripheral) && method_exists($peripheral, 'rawSearchOptionsToAdd')) {
                $tab = [...$tab, ...$peripheral::rawSearchOptionsToAdd($itemtype)];
            }
        }

        return $tab;
    }

    #[Override]
    public static function getRelationMassiveActionsPeerForSubForm(MassiveAction $ma)
    {
        global $CFG_GLPI;

        $items = $ma->getItems();

        if (
            count(array_intersect(
                array_keys($items),
                $CFG_GLPI['directconnect_types']
            )) > 0
        ) {
            return 1;
        }

        if (
            empty(array_diff(
                array_keys($items),
                self::getPeripheralHostItemtypes()
            ))
        ) {
            return 2;
        }

        // Else we cannot define !
        return 0;
    }

    /**
     * Check whether the user can view peripherals from the given item.
     */
    private static function canViewPeripherals(CommonDBTM $item): bool
    {
        if (!$item::canView()) {
            return false;
        }

        foreach (self::getPeripheralHostItemtypes() as $itemtype) {
            if ($item instanceof $itemtype) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns itemtypes of assets that can have peripherals.
     *
     * @return class-string<CommonDBTM>[]
     */
    public static function getPeripheralHostItemtypes(): array
    {
        global $CFG_GLPI;

        return $CFG_GLPI['peripheralhost_types'];
    }

    /**
     * Return peripheral assets count for given main asset.
     */
    private static function countPeripherals(CommonDBTM $asset): int
    {
        global $CFG_GLPI;

        $count = 0;

        foreach ($CFG_GLPI['directconnect_types'] as $itemtype) {
            $count += count(self::getPeripheralAssets($asset, $itemtype));
        }

        return $count;
    }

    /**
     * Return linked assets count for given peripheral asset.
     */
    private static function countLinkedAssets(CommonDBTM $peripheral): int
    {
        $count = 0;

        foreach (self::getPeripheralHostItemtypes() as $itemtype) {
            $count += count(self::getItemConnectionsForItemtype($peripheral, $itemtype));
        }

        return $count;
    }

    #[Override]
    public static function countForItem(CommonDBTM $item)
    {
        return self::countLinkedAssets($item);
    }

    #[Override]
    public static function getItemField($itemtype): string
    {
        global $CFG_GLPI;

        if (in_array($itemtype, self::getPeripheralHostItemtypes(), true)) {
            return 'items_id_asset';
        }
        if (in_array($itemtype, $CFG_GLPI['directconnect_types'], true)) {
            return 'items_id_peripheral';
        }

        return parent::getItemField($itemtype);
    }

    /**
     * Returns peripheral assets data for given main asset.
     *
     * @param CommonDBTM $asset Main asset.
     * @param string $itemtype  Itemtype of the peripherals to retrieve.
     * @return iterable
     */
    private static function getPeripheralAssets(CommonDBTM $asset, string $itemtype): iterable
    {
        global $DB;

        $peripheral = getItemForItemtype($itemtype);

        return $DB->request([
            'SELECT' => self::getTypeItemsQueryParams_Select($peripheral),
            'FROM'   => $peripheral::getTable(),
            'LEFT JOIN' => [
                self::getTable() => [
                    'FKEY' => [
                        self::getTable()      => 'items_id_peripheral',
                        $peripheral::getTable() => 'id',
                        [
                            'AND' => [
                                self::getTable() . '.itemtype_peripheral' => $itemtype,
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE' => [
                self::getTable() . '.is_deleted'     => 0,
                self::getTable() . '.itemtype_asset' => $asset::class,
                self::getTable() . '.items_id_asset' => $asset->getID(),
            ] + getEntitiesRestrictCriteria($peripheral::getTable()),
            'ORDER' => $peripheral::getTable() . '.' . $peripheral::getNameField(),
        ]);
    }

    /**
     * Returns linked main assets data for given peripheral asset.
     *
     * @param CommonDBTM $peripheral Peripheral asset.
     * @param string     $itemtype   Itemtype of the main assets to retrieve.
     * @return iterable
     */
    private static function getItemConnectionsForItemtype(CommonDBTM $peripheral, string $itemtype): iterable
    {
        global $DB;

        $item = getItemForItemtype($itemtype);

        return $DB->request([
            'SELECT' => self::getTypeItemsQueryParams_Select($item),
            'FROM'   => $item::getTable(),
            'LEFT JOIN' => [
                self::getTable() => [
                    'FKEY' => [
                        self::getTable() => 'items_id_asset',
                        $item::getTable()  => 'id',
                        [
                            'AND' => [
                                self::getTable() . '.itemtype_asset' => $itemtype,
                            ],
                        ],
                    ],
                ],
            ],
            'WHERE' => [
                self::getTable() . '.is_deleted'          => 0,
                self::getTable() . '.itemtype_peripheral' => $peripheral::class,
                self::getTable() . '.items_id_peripheral' => $peripheral->getID(),
            ] + getEntitiesRestrictCriteria($item::getTable()),
            'ORDER' => $item::getTable() . '.' . $item::getNameField(),
        ]);
    }
}
