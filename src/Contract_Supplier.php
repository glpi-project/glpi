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

use function Safe\preg_match;

// Relation between Contracts and Suppliers
class Contract_Supplier extends CommonDBRelation
{
    // From CommonDBRelation
    public static $itemtype_1 = 'Contract';
    public static $items_id_1 = 'contracts_id';

    public static $itemtype_2 = 'Supplier';
    public static $items_id_2 = 'suppliers_id';

    public function getForbiddenStandardMassiveAction()
    {
        $forbidden   = parent::getForbiddenStandardMassiveAction();
        $forbidden[] = 'update';
        return $forbidden;
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return '';
        }

        if (!$withtemplate) {
            $nb = 0;
            switch ($item::class) {
                case Supplier::class:
                    if (Contract::canView()) {
                        if ($_SESSION['glpishow_count_on_tabs']) {
                            $nb =  self::countForItem($item);
                        }
                        return self::createTabEntry(
                            Contract::getTypeName(Session::getPluralNumber()),
                            $nb,
                            $item::class
                        );
                    }
                    break;

                case Contract::class:
                    if (Session::haveRight("contact_enterprise", READ)) {
                        if ($_SESSION['glpishow_count_on_tabs']) {
                            $nb = self::countForItem($item);
                        }
                        return self::createTabEntry(Supplier::getTypeName(Session::getPluralNumber()), $nb, $item::class);
                    }
                    break;
            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if (!$item instanceof CommonDBTM) {
            return false;
        }

        switch ($item::class) {
            case Supplier::class:
                self::showForSupplier($item);
                break;

            case Contract::class:
                self::showForContract($item);
                break;
        }
        return true;
    }

    /**
     * Print an HTML array with contracts associated to the enterprise
     *
     * @since 0.84
     *
     * @param Supplier $supplier
     *
     * @return void
     **/
    public static function showForSupplier(Supplier $supplier)
    {
        $ID = $supplier->fields['id'];
        if (
            !Contract::canView()
            || !$supplier->can($ID, READ)
        ) {
            return;
        }
        $canedit = $supplier->can($ID, UPDATE);
        $rand    = mt_rand();

        $iterator = self::getListForItem($supplier);

        $contracts = [];
        $used      = [];
        foreach ($iterator as $data) {
            $contracts[$data['linkid']]   = $data;
            $used[$data['id']]            = $data['id'];
        }

        if ($canedit) {
            $twig_params = [
                'supplier' => $supplier,
                'used' => $used,
                'btn_label' => _x('button', 'Add'),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                <div class="mb-3">
                    <form method="post" action="{{ 'Contract_Supplier'|itemtype_form_path }}">
                        <div class="d-flex">
                            <input type="hidden" name="suppliers_id" value="{{ supplier.getID() }}">
                            <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
                            {{ fields.dropdownField('Contract', 'contracts_id', 0, null, {
                                used: used,
                                entity: supplier.fields['entities_id'],
                                entity_sons: supplier.fields['is_recursive'],
                                nochecklimit: true
                            }) }}
                            {% set btn %}
                                <button type="submit" name='add' class="btn btn-primary">{{ btn_label }}</button>
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
            $item = new Contract();
            $item->getFromResultSet($data);
            $entry = [
                'itemtype' => self::class,
                'id'       => $data['linkid'],
                'row_class' => $data['is_deleted'] ? 'table-deleted' : '',
                'name'     => $item->getLink(),
                'num'      => $data['num'],
                'begin_date' => $data['begin_date'],
            ];

            if (!isset($entity_cache[$data['entity']])) {
                $entity_cache[$data['entity']] = Dropdown::getDropdownName("glpi_entities", $data['entity']);
            }
            $entry['entity'] = $entity_cache[$data['entity']];

            if (!isset($type_cache[$data['contracttypes_id']])) {
                $type_cache[$data['contracttypes_id']] = Dropdown::getDropdownName("glpi_contracttypes", $data['contracttypes_id']);
            }
            $entry['type'] = $type_cache[$data['contracttypes_id']];

            $duration = sprintf(_n('%d month', '%d months', $data["duration"]), $data["duration"]);

            if (!empty($data["begin_date"])) {
                $duration .= " -> " . Infocom::getWarrantyExpir($data["begin_date"], $data["duration"], 0, true);
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
                'num' => _x('phone', 'Number'),
                'type' => ContractType::getTypeName(1),
                'begin_date' => __('Start date'),
                'duration' => __('Initial contract period'),
            ],
            'formatters' => [
                'name' => 'raw_html',
                'begin_date' => 'date',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
            ],
        ]);
    }

    /**
     * Print the HTML array of suppliers for this contract
     *
     * @since 0.84
     *
     * @param Contract $contract object
     *
     * @return void
     **/
    public static function showForContract(Contract $contract)
    {
        $instID = $contract->fields['id'];

        if (
            !$contract->can($instID, READ)
            || !Session::haveRight("contact_enterprise", READ)
        ) {
            return;
        }
        $canedit = $contract->can($instID, UPDATE);
        $rand    = mt_rand();

        $iterator = self::getListForItem($contract);

        $suppliers = [];
        $used      = [];
        foreach ($iterator as $data) {
            $suppliers[$data['linkid']]   = $data;
            $used[$data['id']]            = $data['id'];
        }

        if ($canedit) {
            $twig_params = [
                'contract' => $contract,
                'used' => $used,
                'btn_label' => _x('button', 'Add'),
            ];
            // language=Twig
            echo TemplateRenderer::getInstance()->renderFromStringTemplate(<<<TWIG
                {% import 'components/form/fields_macros.html.twig' as fields %}
                <div class="mb-3">
                    <form method="post" action="{{ 'Contract_Supplier'|itemtype_form_path }}">
                        <div class="d-flex">
                            <input type="hidden" name="contracts_id" value="{{ contract.getID() }}">
                            <input type="hidden" name="_glpi_csrf_token" value="{{ csrf_token() }}">
                            {{ fields.dropdownField('Supplier', 'suppliers_id', 0, null, {
                                used: used,
                                entity: contract.fields['entities_id'],
                                entity_sons: contract.fields['is_recursive']
                            }) }}
                            {% set btn %}
                                <button type="submit" name='add' class="btn btn-primary">{{ btn_label }}</button>
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
        foreach ($suppliers as $data) {
            $item = new Supplier();
            $item->getFromResultSet($data);
            $entry = [
                'itemtype' => self::class,
                'id'       => $data['linkid'],
                'name' => $item->getLink(),
            ];

            $website = $data['website'];
            if (!empty($website)) {
                if (!preg_match("?https*://?", $website)) {
                    $website = "http://" . $website;
                }
                $website = "<a target=_blank href='" . htmlescape($website) . "'>" . htmlescape($data['website']) . "</a>";
            }

            if (!isset($entity_cache[$data['entity']])) {
                $entity_cache[$data['entity']] = Dropdown::getDropdownName("glpi_entities", $data['entity']);
            }
            $entry['entity'] = $entity_cache[$data['entity']];
            if (!isset($type_cache[$data['suppliertypes_id']])) {
                $type_cache[$data['suppliertypes_id']] = Dropdown::getDropdownName("glpi_suppliertypes", $data['suppliertypes_id']);
            }
            $entry['type'] = $type_cache[$data['suppliertypes_id']];
            $entry['phonenumber'] = $data['phonenumber'];
            $entry['website'] = $website;
            $entries[] = $entry;
        }

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab' => true,
            'nofilter' => true,
            'nosort' => true,
            'columns' => [
                'name' => __('Name'),
                'entity' => Entity::getTypeName(1),
                'type' => SupplierType::getTypeName(1),
                'phonenumber' => Phone::getTypeName(1),
                'website' => __('Website'),
            ],
            'formatters' => [
                'name' => 'raw_html',
                'website' => 'raw_html',
            ],
            'entries' => $entries,
            'total_number' => count($entries),
            'filtered_number' => count($entries),
            'showmassiveactions' => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => 'mass' . static::class . $rand,
            ],
        ]);
    }
}
