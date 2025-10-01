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

class Ticket_Contract extends CommonDBRelation
{
    public static $itemtype_1 = 'Ticket';
    public static $items_id_1 = 'tickets_id';

    public static $itemtype_2 = 'Contract';
    public static $items_id_2 = 'contracts_id';
    public static $checkItem_2_Rights = self::HAVE_VIEW_RIGHT_ON_ITEM;
    public static $check_entity_coherency = false;

    public static function getTypeName($nb = 0)
    {
        return __('Tickets / Contracts');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (Contract::canView()) {
            $nb = 0;
            if ($item::class === Ticket::class) {
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $nb = count(self::getListForItem($item));
                }
                return self::createTabEntry(Contract::getTypeName(Session::getPluralNumber()), $nb, $item::class);
            } elseif ($item::class === Contract::class) {
                if ($_SESSION['glpishow_count_on_tabs']) {
                    $nb = count(self::getListForItem($item));
                }
                return self::createTabEntry(Ticket::getTypeName(Session::getPluralNumber()), $nb, $item::class);
            } else {
                return '';
            }
        }
        return '';
    }

    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        $rand = mt_rand();

        $twig_params = [
            'item' => $item,
            'btn_label' => _x('button', 'Add'),
        ];
        if ($item instanceof Ticket) {
            $item_a_fkey = self::$items_id_1;
            $linked_itemtype = self::$itemtype_2;
        } elseif ($item instanceof Contract) {
            $item_a_fkey = self::$items_id_2;
            $linked_itemtype = self::$itemtype_1;
        } else {
            return false;
        }
        $twig_params['item_a_fkey'] = $item_a_fkey;
        /** @var class-string<Ticket|Contract> $linked_itemtype */
        $twig_params['linked_itemtype'] = $linked_itemtype;

        $ID = $item->getField('id');
        $twig_params['id'] = $ID;

        if (!static::canView() || !$item->can($ID, READ)) {
            return false;
        }

        $canedit = $item->canEdit($ID);

        $linked_items = array_map(static function ($entry) use ($linked_itemtype, $ID) {
            $entry['itemtype'] = $linked_itemtype;
            $entry['item_id'] = $ID;
            return $entry;
        }, iterator_to_array(self::getListForItem($item), false));
        $twig_params['used'] = [];
        foreach ($linked_items as $linked_item) {
            $twig_params['used'][$linked_item['id']] = $linked_item['id'];
        }

        if ($canedit) {
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                <div class="mb-3">
                    <form method="post" action="{{ 'Ticket_Contract'|itemtype_form_path }}">
                        <div class="d-flex">
                            <input type="hidden" name="{{ item_a_fkey }}" value="{{ id }}">
                            <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
                            {{ fields.dropdownField(linked_itemtype, linked_itemtype|itemtype_foreign_key, 0, null, {
                                used: used,
                                displaywith: ['id'],
                                entity: item.fields['entities_id'],
                                nochecklimit: true
                            }) }}
                            {% set btn %}
                                <button type="submit" class="btn btn-primary" name="add">{{ btn_label }}</button>
                            {% endset %}
                            {{ fields.htmlField('', btn, null) }}
                        </div>
                    </form>
                </div>
TWIG, $twig_params);
        }

        if ($linked_itemtype === Ticket::class) {
            [$columns, $formatters] = array_values(Ticket::getCommonDatatableColumns(['ticket_stats' => true]));
            $entries = Ticket::getDatatableEntries($linked_items, ['ticket_stats' => true]);
        } else {
            $columns = [
                'name' => __('Name'),
                'entity' => Entity::getTypeName(1),
                'type' => _n('Type', 'Types', 1),
                'num' => _x('phone', 'Number'),
                'begin_date' => __('Start date'),
                'end_date' => __('End date'),
                'comment' => _n('Comment', 'Comments', Session::getPluralNumber()),
            ];
            $formatters = [
                'name' => 'raw_html',
                'begin_date' => 'date', // No formatter for end_date as Infocom::getWarrantyExpir() already returns a formatted date
            ];

            $entries = [];
            $entity_cache = [];
            $type_cache = [];
            foreach ($linked_items as $data) {
                if (!($item = getItemForItemtype($linked_itemtype))) {
                    continue;
                }
                if (!$item->getFromDB($data['id'])) {
                    continue;
                }
                $entry = [
                    'itemtype' => self::class,
                    'id' => $data['id'],
                    'name' => $item->getLink(),
                    'num' => $item->fields['num'],
                    'begin_date' => $item->fields['begin_date'],
                    'comment' => $item->fields['comment'],
                ];
                if (!isset($entity_cache[$item->fields['entities_id']])) {
                    $entity_cache[$item->fields['entities_id']] = Dropdown::getDropdownName(Entity::getTable(), $item->fields['entities_id']);
                }
                $entry['entity'] = $entity_cache[$item->fields['entities_id']];
                if (!isset($type_cache[$item->fields['contracttypes_id']])) {
                    $type_cache[$item->fields['contracttypes_id']] = Dropdown::getDropdownName(ContractType::getTable(), $item->fields['contracttypes_id']);
                }
                $entry['type'] = $type_cache[$item->fields['contracttypes_id']];
                $entry['end_date'] = Infocom::getWarrantyExpir($item->fields['begin_date'], $item->fields['duration'], 0, true);
                $entries[] = $entry;
            }
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => $columns,
            'formatters' => $formatters,
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
                'specific_actions' => [
                    'purge' => _x('button', 'Delete permanently'),
                ],
                'extraparams'      => [$item_a_fkey => $item->getID()],
            ],
        ]);

        return true;
    }
}
