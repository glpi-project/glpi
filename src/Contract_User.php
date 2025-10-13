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

class Contract_User extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1 = 'Contract';
    public static $items_id_1 = 'contracts_id';

    public static $itemtype_2 = 'User';
    public static $items_id_2 = 'users_id';

    public static $check_entity_coherency = false; // `entities_id`/`is_recursive` fields from user should not be used here

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public function canCreateItem(): bool
    {
        // Try to load the contract
        $contract = $this->getConnexityItem(static::$itemtype_1, static::$items_id_1);
        if ($contract === false) {
            return false;
        }

        // Don't create a Contract_User on contract that is already max used
        if (
            ($contract->fields['max_links_allowed'] > 0)
            && (countElementsInTable(
                static::getTable(),
                ['contracts_id' => $this->input['contracts_id']]
            )
                >= $contract->fields['max_links_allowed'])
        ) {
            return false;
        }

        return parent::canCreateItem();
    }

    public static function getTypeName($nb = 0)
    {
        return User::getTypeName($nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (Contract::canView() && $item::class === User::class) {
            $nb = 0;
            if ($_SESSION['glpishow_count_on_tabs']) {
                $nb = countElementsInTable(Contract_User::getTable(), ['users_id' => $item->fields['id']]);
            }
            return self::createTabEntry(Contract::getTypeName(Session::getPluralNumber()), $nb, $item::class);
        }

        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (Contract::canView() && $item::class === User::class) {
            self::showForUser($item, $withtemplate);
        }

        return true;
    }

    private static function showForUser(User $user, $withtemplate = 0): void
    {
        $ID = $user->fields['id'];

        if (
            !Contract::canView()
            || !$user->can($ID, READ)
        ) {
            return;
        }

        $canedit = $user->can($ID, UPDATE);
        $rand = mt_rand();

        $iterator = self::getListForItem($user);

        $contracts = [];
        $used      = [];
        foreach ($iterator as $data) {
            $contracts[$data['id']] = $data;
            $used[$data['id']]      = $data['id'];
        }
        if ($canedit && ((int) $withtemplate !== 2)) {
            $twig_params = [
                'user' => $user,
                'used' => $used,
                'btn_label' => _x('button', 'Add'),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                <div class="mb-3">
                    <form method="post" action="{{ 'Contract_Item'|itemtype_form_path }}">
                        <input type="hidden" name="itemtype" value="{{ get_class(user) }}">
                        <input type="hidden" name="items_id" value="{{ user.getID() }}">
                        <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
                        <div class="d-flex">
                            {{ fields.dropdownField('Contract', 'contracts_id', 0, null, {
                                used: used,
                                expired: false,
                            }) }}
                            {% set btn %}
                                <button type="submit" name="add" class="btn btn-primary">{{ btn_label }}</button>
                            {% endset %}
                            {{ fields.htmlField('', btn, null) }}
                        </div>
                    </form>
                </div>
TWIG, $twig_params);
        }

        $entries = [];
        $entity_cache = [];
        $type_cache = [];
        foreach ($contracts as $data) {
            $entry = [
                'itemtype'  => self::class,
                'id'        => $data['linkid'],
                'row_class' => $data['is_deleted'] ? 'table-danger' : '',
                'num'       => $data['num'],
            ];
            $contract = new Contract();
            $contract->getFromResultSet($data);

            $entry['name'] = $contract->getLink();

            if (!isset($entity_cache[$contract->fields["entities_id"]])) {
                $entity_cache[$contract->fields["entities_id"]] = Dropdown::getDropdownName(
                    "glpi_entities",
                    $contract->fields["entities_id"]
                );
            }
            $entry['entity'] = $entity_cache[$contract->fields["entities_id"]];

            if (!isset($type_cache[$contract->fields["contracttypes_id"]])) {
                $type_cache[$contract->fields["contracttypes_id"]] = Dropdown::getDropdownName(
                    "glpi_contracttypes",
                    $contract->fields["contracttypes_id"]
                );
            }
            $entry['type'] = $type_cache[$contract->fields["contracttypes_id"]];

            $entry['supplier'] = $contract->getSuppliersNames();
            $entry['begin_date'] = $contract->fields["begin_date"];

            $duration = sprintf(
                __('%1$s %2$s'),
                $contract->fields["duration"],
                _n('month', 'months', $contract->fields["duration"])
            );

            if (!empty($contract->fields["begin_date"])) {
                $duration .= ' -> ' . Infocom::getWarrantyExpir(
                    $contract->fields["begin_date"],
                    $contract->fields["duration"],
                    0,
                    true
                );
            }
            $entry['duration'] = $duration;

            $entries[] = $entry;
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'name' => __('Name'),
                'entity' => Entity::getTypeName(1),
                'type' => _n('Type', 'Types', 1),
                'supplier' => Supplier::getTypeName(1),
                'begin_date' => __('Start date'),
                'duration' => __('Initial contract period'),
            ],
            'formatters' => [
                'name' => 'raw_html',
                'supplier' => 'raw_html',
                'begin_date' => 'date',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit && (int) $withtemplate !== 2,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
            ],
        ]);
    }
}
